<?php

namespace App\Services;

use App\Mail\NewLeadImportedAdminMail;
use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ImapLeadFetcherService
{
    /**
     * Prueba la conexión IMAP para una marca o configuración específica
     */
    public function testConnection(string $brandKey): array
    {
        $config = $this->getMailboxConfig($brandKey);

        if (empty($config['email']) || empty($config['password'])) {
            return [
                'success' => false,
                'message' => 'Faltan el email o la contraseña en la configuración de ' . $this->getBrandDisplayName($brandKey),
            ];
        }

        try {
            $connection = $this->connect($config);
            if ($connection['type'] === 'native') {
                imap_close($connection['stream']);
            } else {
                fclose($connection['stream']);
            }

            return [
                'success' => true,
                'message' => '¡Conexión IMAP establecida con éxito con el buzón ' . $config['email'] . '!',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Corrige automáticamente eventos de Madrigal/Email ya importados cuya fecha quedó como +3 meses (ej. 19 de enero)
     * por venir con formato *Fecha:* YYYY-MM-DD con asteriscos.
     */
    public static function repairMisParsedLeadDates(?Event $singleEvent = null): int
    {
        $repaired = 0;
        try {
            $events = $singleEvent
                ? collect([$singleEvent])
                : Event::where('notes', 'LIKE', '%*Fecha:*%')
                    ->orWhere('notes', 'LIKE', '%LEAD IMPORTADO VÍA EMAIL%')
                    ->get();

            foreach ($events as $ev) {
                $notes = (string)($ev->notes ?? '');
                if (empty($notes)) {
                    continue;
                }

                $updates = [];

                // 1. Extraer fecha explícita del bloque de lead en las notas (*Fecha:* 2027-09-25 o *Fecha:* 25/09/2027)
                $parsedDate = null;
                if (preg_match('/\*?Fecha(?:\s+de)?(?:\s+la)?(?:\s+boda|\s+evento)?\*?\s*:\*?\s*(20\d{2}[\/\-\.][0-1]?\d[\/\-\.][0-3]?\d)/iu', $notes, $m)) {
                    try {
                        $parsedDate = Carbon::parse(str_replace(['.', '/'], '-', trim($m[1])))->format('Y-m-d');
                    } catch (\Throwable $e) {}
                } elseif (preg_match('/\*?Fecha(?:\s+de)?(?:\s+la)?(?:\s+boda|\s+evento)?\*?\s*:\*?\s*([0-3]?\d[\/\-\.][0-1]?\d[\/\-\.]20\d{2})/iu', $notes, $m)) {
                    try {
                        $parsedDate = Carbon::createFromFormat('d/m/Y', str_replace(['.', '-'], '/', trim($m[1])))->format('Y-m-d');
                    } catch (\Throwable $e) {}
                }

                if ($parsedDate) {
                    $currentDate = $ev->event_date ? Carbon::parse($ev->event_date)->format('Y-m-d') : null;
                    if ($currentDate !== $parsedDate) {
                        // Verificar si la fecha actual era la fecha automática (+3 meses desde created_at o desde recepción)
                        $createdAt = $ev->created_at ? Carbon::parse($ev->created_at) : now();
                        $fallbackDate = $createdAt->copy()->addMonths(3);
                        $isFallbackDate = !$currentDate
                            || abs(Carbon::parse($currentDate)->diffInDays($fallbackDate, false)) <= 5
                            || str_ends_with($currentDate, '-01-19');

                        if ($isFallbackDate) {
                            $updates['event_date'] = $parsedDate;
                        }
                    }
                }

                // 2. Limpiar asterisco residual en location (ej. "* Álava" -> "Álava")
                if (!empty($ev->location) && str_starts_with(trim($ev->location), '*')) {
                    $updates['location'] = trim($ev->location, " \t\n\r\0\x0B*_");
                } elseif (($ev->location === 'Por definir' || empty($ev->location)) && preg_match('/\*?Lugar\*?\s*:\*?\s*([^\n\r]+)/iu', $notes, $m)) {
                    $locClean = trim($m[1], " \t\n\r\0\x0B*_");
                    if (mb_strlen($locClean) > 1) {
                        $updates['location'] = $locClean;
                    }
                }

                // 3. Limpiar asterisco residual en Origen: Madrigal Marketing (* Creativo X)
                if (str_contains($notes, '(* ')) {
                    $updates['notes'] = str_replace('(* ', '(', $notes);
                }

                if (!empty($updates)) {
                    $ev->update($updates);
                    $repaired++;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Error en repairMisParsedLeadDates: ' . $e->getMessage());
        }

        return $repaired;
    }

    /**
     * Sincroniza en segundo plano si ha pasado el tiempo de enfriamiento (por defecto 120s)
     */
    public function autoSyncIfDue(int $cooldownSeconds = 120): ?array
    {
        try {
            self::repairMisParsedLeadDates();
            $cacheKey = 'imap_auto_sync_last_run';
            $lastRun = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($lastRun && (time() - (int)$lastRun) < $cooldownSeconds) {
                return null;
            }
            \Illuminate\Support\Facades\Cache::put($cacheKey, time(), $cooldownSeconds);
            return $this->fetchAllActiveMailboxes();
        } catch (\Throwable $e) {
            Log::warning('AutoSync IMAP omitido: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Escanea y procesa todos los buzones activos o uno en particular
     */
    public function fetchAllActiveMailboxes(?string $specificBrand = null): array
    {
        self::repairMisParsedLeadDates();
        $brandsToScan = $specificBrand ? [$specificBrand] : ['nunez_and_son', 'javnx', 'mago_leugim'];
        $results = [
            'total_scanned' => 0,
            'total_leads_created' => 0,
            'details' => [],
        ];

        foreach ($brandsToScan as $brandKey) {
            $config = $this->getMailboxConfig($brandKey);
            if (!($config['enabled'] ?? false) || empty($config['email']) || empty($config['password'])) {
                continue;
            }

            $brandResult = $this->fetchFromMailbox($brandKey, $config);
            $results['total_scanned'] += $brandResult['scanned'];
            $results['total_leads_created'] += $brandResult['created'];
            $results['details'][$brandKey] = $brandResult;
        }

        return $results;
    }

    /**
     * Escanea un buzón IMAP específico
     */
    public function fetchFromMailbox(string $brandKey, array $config): array
    {
        $result = [
            'brand' => $brandKey,
            'email' => $config['email'],
            'scanned' => 0,
            'created' => 0,
            'errors' => [],
            'leads' => [],
        ];

        try {
            $conn = $this->connect($config);

            if ($conn['type'] === 'native') {
                $result = $this->fetchNativeImap($conn['stream'], $brandKey, $config, $result);
                imap_close($conn['stream']);
            } else {
                $result = $this->fetchSocketImap($conn['stream'], $brandKey, $config, $result);
                fclose($conn['stream']);
            }
        } catch (\Throwable $e) {
            Log::error("IMAP Fetch error for {$brandKey}: " . $e->getMessage());
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Procesa correos usando la extensión nativa PHP-IMAP si está presente
     */
    protected function fetchNativeImap($stream, string $brandKey, array $config, array $result): array
    {
        $sinceDate = date('d-M-Y', strtotime('-7 days'));
        $unseenEmails = imap_search($stream, 'UNSEEN') ?: [];
        $recentEmails = imap_search($stream, 'SINCE "' . $sinceDate . '"') ?: [];

        $emails = array_values(array_unique(array_merge($recentEmails, $unseenEmails)));
        sort($emails, SORT_NUMERIC);

        if (empty($emails)) {
            return $result;
        }

        // Limitar a los últimos 30 correos recientes para máxima velocidad
        if (count($emails) > 30) {
            $emails = array_slice($emails, -30);
        }

        $markAsRead = Setting::get('imap_mark_as_read', '1') === '1';
        $initialStatus = Setting::get('imap_default_status', 'no_response'); // draft | no_response

        foreach ($emails as $emailNumber) {
            $result['scanned']++;

            $header = imap_headerinfo($stream, $emailNumber);
            $rawFromName = $header->from[0]->personal ?? ($header->from[0]->mailbox ?? 'Cliente');
            $fromName = $this->decodeMimeHeader($rawFromName);
            $fromEmail = strtolower(($header->from[0]->mailbox ?? '') . '@' . ($header->from[0]->host ?? ''));
            $subject = $this->decodeMimeHeader($header->subject ?? 'Solicitud de información');
            $body = $this->getNativeBody($stream, $emailNumber);

            $parsed = $this->parseLeadFromEmail($subject, $body, $fromName, $fromEmail, $brandKey);

            // Si el correo proviene de un cliente ya registrado en un evento, guardar la respuesta en su Conversación Registrada
            $this->recordClientEmailReplyIfExisting($fromEmail, $fromName, $subject, $body, $brandKey);

            if ($parsed['is_lead']) {
                $event = $this->createLeadEvent($parsed, $brandKey, $initialStatus);
                if ($event) {
                    $result['created']++;
                    $result['leads'][] = [
                        'event_id' => $event->id,
                        'name' => $event->name,
                        'client' => $parsed['client_name'],
                        'email' => $parsed['client_email'],
                        'date' => $event->event_date ? $event->event_date->format('d/m/Y') : null,
                    ];
                }
                if ($markAsRead) {
                    imap_setflag_full($stream, (string)$emailNumber, "\\Seen");
                }
            }
        }

        return $result;
    }

    /**
     * Lector IMAP puro mediante sockets (sin requerir extensiones nativas)
     */
    protected function fetchSocketImap($socket, string $brandKey, array $config, array $result): array
    {
        // 1. Login
        $user = $config['email'];
        $pass = $config['password'];
        $this->sendSocketCmd($socket, "A01 LOGIN \"{$user}\" \"{$pass}\"");

        // 2. Select INBOX
        $this->sendSocketCmd($socket, "A02 SELECT INBOX");

        // 3. Search UNSEEN y recientes (últimos 7 días) para no perder leads abiertos en el móvil
        $sinceDate = date('d-M-Y', strtotime('-7 days'));
        $searchResp = $this->sendSocketCmd($socket, "A03 SEARCH SINCE {$sinceDate}");
        $unseenResp = $this->sendSocketCmd($socket, "A03B SEARCH UNSEEN");
        
        $msgIds = [];
        foreach (array_merge($searchResp, $unseenResp) as $line) {
            if (str_starts_with($line, '* SEARCH')) {
                $parts = explode(' ', trim(substr($line, 8)));
                foreach ($parts as $p) {
                    if (is_numeric($p) && (int)$p > 0) {
                        $msgIds[] = (int)$p;
                    }
                }
            }
        }

        $msgIds = array_values(array_unique($msgIds));
        sort($msgIds, SORT_NUMERIC);
        if (count($msgIds) > 30) {
            $msgIds = array_slice($msgIds, -30);
        }

        $markAsRead = Setting::get('imap_mark_as_read', '1') === '1';
        $initialStatus = Setting::get('imap_default_status', 'no_response');

        foreach ($msgIds as $id) {
            $result['scanned']++;

            // Fetch RFC822 full text
            $fetchLines = $this->sendSocketCmd($socket, "A04 FETCH {$id} (BODY.PEEK[TEXT] BODY.PEEK[HEADER.FIELDS (FROM SUBJECT DATE TO)])");
            $rawContent = implode("\n", $fetchLines);

            $rawSubject = $this->extractHeaderValue($rawContent, 'Subject') ?: 'Solicitud de información';
            $subject = $this->decodeMimeHeader($rawSubject);
            $fromRaw = $this->extractHeaderValue($rawContent, 'From') ?: $user;
            $body = $rawContent;

            $fromParsed = $this->parseFromHeader($fromRaw);
            $fromName = $this->decodeMimeHeader($fromParsed['name']);
            $fromEmail = strtolower($fromParsed['email']);
            $parsed = $this->parseLeadFromEmail($subject, $body, $fromName, $fromEmail, $brandKey);

            // Si el correo proviene de un cliente ya registrado en un evento, guardar la respuesta en su Conversación Registrada
            $this->recordClientEmailReplyIfExisting($fromEmail, $fromName, $subject, $body, $brandKey);

            if ($parsed['is_lead']) {
                $event = $this->createLeadEvent($parsed, $brandKey, $initialStatus);
                if ($event) {
                    $result['created']++;
                    $result['leads'][] = [
                        'event_id' => $event->id,
                        'name' => $event->name,
                        'client' => $parsed['client_name'],
                        'email' => $parsed['client_email'],
                        'date' => $event->event_date ? $event->event_date->format('d/m/Y') : null,
                    ];
                }
                if ($markAsRead) {
                    $this->sendSocketCmd($socket, "A05 STORE {$id} +FLAGS (\\Seen)");
                }
            }
        }

        $this->sendSocketCmd($socket, "A06 LOGOUT");

        return $result;
    }

    /**
     * Limpia y decodifica el cuerpo del correo (soporta Quoted-Printable, Multipart MIME y bloques HTML)
     */
    public function cleanEmailBody(string $rawBody): string
    {
        $text = $rawBody;

        // 1. Si hay secuencias Quoted-Printable (ej. =C3=A9, =C3=81, cortes de línea =\r\n), decodificarlas
        if (preg_match('/=[0-9A-Fa-f]{2}|=\r?\n/', $text)) {
            $text = quoted_printable_decode($text);
        }

        // 2. Eliminar cabeceras MIME residuales de partes multipart
        $text = preg_replace('/^--[^\r\n]+$/m', '', $text);
        $text = preg_replace('/^Content-(?:Type|Transfer-Encoding|Disposition):[^\r\n]*$/mi', '', $text);

        // 3. Eliminar bloques <style>, <head> y <script> completos antes de quitar etiquetas
        $text = preg_replace('/<(style|head|script)\b[^>]*>.*?<\/\1>/is', '', $text);

        // 4. Convertir cierres de bloque HTML en saltos de línea para que no se peguen los campos (ej. </div><div>)
        $text = preg_replace('/<(?:br\s*\/?|\/p|\/div|\/tr|\/li|\/h[1-6]|\/td)>/i', "\n", $text);

        // 5. Quitar resto de etiquetas HTML y decodificar entidades
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 6. Normalizar espacios y saltos de línea
        $lines = array_map(fn($l) => trim(preg_replace('/[^\S\r\n]+/', ' ', $l)), explode("\n", str_replace("\r\n", "\n", $text)));
        $lines = array_filter($lines, fn($l) => $l !== '');

        return implode("\n", $lines);
    }

    /**
     * Analizador inteligente de correos para detectar y extraer leads de bodas/eventos
     */
    public function parseLeadFromEmail(string $subject, string $body, string $fromName, string $fromEmail, string $brandKey): array
    {
        $subjectClean = $this->decodeMimeHeader($subject);
        $bodyClean = $this->cleanEmailBody($body);
        
        $fromEmailLower = strtolower(trim($fromEmail));
        $fromNameLower = mb_strtolower(trim($fromName));
        $fromDomain = substr(strrchr($fromEmailLower, "@"), 1);

        // 1. Blacklist de dominios de sistema, spam o servicios automáticos
        $blacklistedDomains = [
            'microsoft.com', 'google.com', 'apple.com', 'ptd.net', 'ganoexcel.us',
            'nunezandson.com', 'javnx.es', 'javnxdj.com', 'eventosmusicales.local',
            'email.bodas.net', 'sendgrid.net', 'mailchimp.com'
        ];

        // 2. Blacklist de correos específicos de spam o no deseados
        $blacklistedEmails = [
            'info@bodas.net', 'business@email.bodas.net', 'atencionalcliente@bodas.net',
            'noreply@bodas.net', 'muralikhalifa@gmail.com',
            'sms2333@ptd.net', 'rmassoud@ganoexcel.us'
        ];

        // 3. Prefijos de servicios automáticos / no-reply
        $blacklistedPrefixes = [
            'no-reply@', 'noreply@', 'mailer-daemon@', 'postmaster@', 'security@',
            'alertas@', 'alerts@', 'billing@', 'facturacion@', 'newsletter@',
            'news@', 'bounce@', 'notification@', 'notifications@', 'soporte@', 'support@'
        ];

        // Portales proveedores de leads (el remitente es la agencia/portal, pero los datos del cliente vienen dentro del cuerpo)
        $isFromBodasNet = str_contains($fromEmailLower, 'bodas.net') || str_contains($fromNameLower, 'bodas.net');
        $isFromMadrigal = str_contains($fromEmailLower, 'madrigalmarketing.es') || str_contains($fromNameLower, 'madrigal');
        $isFromLeadPortal = $isFromBodasNet || $isFromMadrigal;

        // Descartar inmediatamente si no viene de un portal de leads y coincide con la lista negra
        if (!$isFromLeadPortal) {
            if (in_array($fromDomain, $blacklistedDomains) || in_array($fromEmailLower, $blacklistedEmails)) {
                return ['is_lead' => false];
            }
            foreach ($blacklistedPrefixes as $pfx) {
                if (str_starts_with($fromEmailLower, $pfx)) {
                    return ['is_lead' => false];
                }
            }
        }

        // Descartar si el nombre del remitente es de sistema o spam conocido
        if (str_contains($fromNameLower, 'robertnar')
            || str_contains($fromNameLower, 'microsoft')
            || str_contains($fromNameLower, 'javnx dj')
            || str_contains($fromNameLower, 'servicio de atención')
            || str_contains($fromNameLower, 'atención al cliente')
            || str_contains($fromNameLower, 'bodas.net business')
            || str_contains($fromNameLower, 'equipo de bodas.net')) {
            return ['is_lead' => false];
        }

        $fullText = $subjectClean . "\n" . $bodyClean;
        $lowerText = mb_strtolower($fullText);

        // Palabras clave que indican una solicitud de evento real
        $leadKeywords = [
            'presupuesto', 'solicitud', 'boda', 'disponibilidad',
            'formulario', 'cotización', 'cotizacion',
            'tarifa', 'pack', 'reserva', 'información', 'informacion'
        ];

        $isLead = false;
        foreach ($leadKeywords as $kw) {
            if (str_contains($lowerText, $kw)) {
                $isLead = true;
                break;
            }
        }

        if (!$isLead && !$isFromLeadPortal) {
            return ['is_lead' => false];
        }

        $isBodasNet = $isFromBodasNet || str_contains($lowerText, 'bodas.net');
        $isMadrigal = $isFromMadrigal || str_contains($lowerText, 'madrigalmarketing.es') || str_contains($lowerText, 'madrigal marketing');
        $isPortalEmail = $isBodasNet || $isMadrigal;
        
        // 4. Extraer Teléfono (soporta etiquetas con asteriscos ej. *Telefono:* +34633546432)
        $phone = null;
        if (preg_match('/(?:Tel[eé]fono|Tel|M[oó]vil|Whatsapp|WhatsApp|Phone)[\s\*_]*[:\-]?[\s\*_]*([+\d\s().-]{9,20})/iu', $bodyClean, $m)) {
            $rawPhone = preg_replace('/[^\d+]/', '', trim($m[1]));
            if (strlen(preg_replace('/[^\d]/', '', $rawPhone)) >= 9) {
                $phone = $rawPhone;
            }
        } elseif (preg_match('/(?:\+34\s*)?[67]\d{2}[\s.-]?\d{3}[\s.-]?\d{3}\b/', $bodyClean, $m)) {
            $phone = preg_replace('/[^\d+]/', '', trim($m[0]));
        }

        // 5. Extraer Nombre del Cliente (soporta *Nombre:* Diana Lorena)
        $clientName = $isPortalEmail ? null : ($fromName ?: 'Cliente Potencial');
        if ($isPortalEmail || str_contains($lowerText, 'nombre')) {
            if (preg_match('/(?:Nombre(?:\s+completo)?|Novios?|Contacto)[\s\*_]*[:\-][\s\*_]*([^\n\r,]+)/iu', $bodyClean, $m)) {
                $candidateName = trim($m[1], " \t\n\r\0\x0B*_");
                // Limpiar si por algún motivo quedó pegada la etiqueta siguiente
                $candidateName = preg_replace('/[\s\*_]*(?:Fecha|Lugar|Tel[eé]fono|Email|Correo|Campa[ñn]a|Otro)[\s\*_]*[:\-].*$/iu', '', $candidateName);
                $candidateName = trim((string)$candidateName, " \t\n\r\0\x0B*_");
                $candidateLower = mb_strtolower($candidateName);
                if (!empty($candidateName) && !str_contains($candidateLower, 'bodas.net') && !str_contains($candidateLower, 'madrigal')) {
                    $clientName = $candidateName;
                }
            }
        }

        // 6. Extraer Email del Cliente (nunca usar el email del portal como email del cliente)
        $portalOrSystemEmails = array_merge($blacklistedEmails, [
            'info@madrigalmarketing.es', 'hola@mail.madrigalmarketing.es', 'info@nunezandson.com', 'info@javnxdj.com'
        ]);
        $clientEmail = $isPortalEmail ? null : $fromEmail;
        if ($isPortalEmail || str_contains($fromEmailLower, 'no-reply') || str_contains($fromEmailLower, 'noreply') || in_array($fromEmailLower, $portalOrSystemEmails)) {
            if (preg_match('/(?:Email|Correo|E-mail)[\s\*_]*[:\-]?[\s\*_]*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $bodyClean, $m)) {
                $candidateEmail = strtolower(trim($m[1]));
                if (!str_contains($candidateEmail, 'bodas.net')
                    && !str_contains($candidateEmail, 'madrigalmarketing.es')
                    && !in_array($candidateEmail, $portalOrSystemEmails)) {
                    $clientEmail = $candidateEmail;
                } else {
                    $clientEmail = null;
                }
            } else {
                $clientEmail = null;
            }
        }

        // 7. Extraer Fecha del Evento (soporta *Fecha:* 2027-09-25, Fecha: 25/09/2027, 25 de septiembre de 2027, etc.)
        $eventDate = null;
        $explicitDateFound = false;
        if (preg_match('/(?:Fecha(?:\s+de)?(?:\s+la)?(?:\s+boda|\s+evento)?|Día|Dia|Celebración|Celebracion|Enlace)[\s\*_]*[:\-]?[\s\*_]*([0-3]?\d[\/\-\.][0-1]?\d[\/\-\.]20\d{2})/iu', $bodyClean, $m)) {
            try {
                $dateStr = str_replace(['.', '-'], '/', trim($m[1]));
                $eventDate = Carbon::createFromFormat('d/m/Y', $dateStr)->format('Y-m-d');
                $explicitDateFound = true;
            } catch (\Throwable $e) {}
        } elseif (preg_match('/(?:Fecha(?:\s+de)?(?:\s+la)?(?:\s+boda|\s+evento)?|Día|Dia|Celebración|Celebracion|Enlace)[\s\*_]*[:\-]?[\s\*_]*(20\d{2}[\/\-\.][0-1]?\d[\/\-\.][0-3]?\d)/iu', $bodyClean, $m)) {
            try {
                $dateStr = str_replace(['.', '/'], '-', trim($m[1]));
                $eventDate = Carbon::parse($dateStr)->format('Y-m-d');
                $explicitDateFound = true;
            } catch (\Throwable $e) {}
        } elseif (preg_match('/([0-3]?\d)\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s+(?:de\s+)?(20\d{2})/iu', $bodyClean, $m)) {
            $months = [
                'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
                'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
                'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
            ];
            $day = (int)$m[1];
            $month = $months[mb_strtolower($m[2])] ?? 1;
            $year = (int)$m[3];
            try {
                $eventDate = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
                $explicitDateFound = true;
            } catch (\Throwable $e) {}
        } elseif (preg_match('/\b(202[6-9][\/\-\.][0-1]\d[\/\-\.][0-3]\d)\b/', $bodyClean, $m)) {
            try {
                $dateStr = str_replace(['.', '/'], '-', trim($m[1]));
                $eventDate = Carbon::parse($dateStr)->format('Y-m-d');
                $explicitDateFound = true;
            } catch (\Throwable $e) {}
        }

        // Si viene de un portal (Bodas.net o Madrigal Marketing), exigir que hayamos extraído el nombre real del cliente y teléfono/fecha/email
        if ($isPortalEmail) {
            $hasRealClientName = !empty($clientName)
                && !str_contains(mb_strtolower($clientName), 'bodas.net')
                && !str_contains(mb_strtolower($clientName), 'madrigal');
            if (!$hasRealClientName || (empty($phone) && empty($clientEmail) && !$explicitDateFound)) {
                return ['is_lead' => false];
            }
            $isLead = true;
        }

        if (empty($clientName)) {
            $clientName = 'Cliente Potencial';
        }

        // RESTRICCIÓN ESTRICTA DE CALIDAD DE LEADS:
        // Exigir obligatoriamente que el correo traiga al menos:
        // 1. Un teléfono de contacto válido
        // O
        // 2. Una fecha explícita para el evento en el cuerpo
        // Si no tiene ninguno de los dos, NO es una solicitud de evento y se descarta
        if (empty($phone) && !$explicitDateFound) {
            return ['is_lead' => false];
        }

        // Si no se encuentra fecha explícita pero sí tenía teléfono de contacto, se asigna fecha estimada a 3 meses
        if (empty($eventDate)) {
            $eventDate = Carbon::now()->addMonths(3)->format('Y-m-d');
        }

        // 8. Extraer Lugar / Finca / Ciudad (soporta *Lugar:* Álava)
        $location = 'Por definir';
        if (preg_match('/(?:Lugar|Finca|Restaurante|Espacio|Ubicaci[oó]n|Ciudad|Poblaci[oó]n|Provincia)[\s\*_]*[:\-]?[\s\*_]*([^\n\r,]+)/iu', $bodyClean, $m)) {
            $locClean = trim($m[1], " \t\n\r\0\x0B*_");
            $locClean = preg_replace('/[\s\*_]*(?:Tel[eé]fono|Fecha|Campa[ñn]a|Email|Otro)[\s\*_]*[:\-].*$/iu', '', $locClean);
            $locClean = trim((string)$locClean, " \t\n\r\0\x0B*_");
            if (mb_strlen($locClean) > 1) {
                $location = $locClean;
            }
        }

        // 8b. Extraer Campaña / Creativo (ej. Madrigal Marketing: *Campaña / Creativo:* Creativo 4)
        $campaign = null;
        if (preg_match('/(?:Campa[ñn]a(?:\s*\/\s*Creativo)?|Creativo|Anuncio)[\s\*_]*[:\-][\s\*_]*([^\n\r]+)/iu', $bodyClean, $m)) {
            $campaign = trim($m[1], " \t\n\r\0\x0B*_");
        }

        $sourceLabel = 'Formulario / Email';
        if ($isMadrigal) {
            $sourceLabel = 'Madrigal Marketing' . ($campaign ? " ({$campaign})" : '');
        } elseif ($isBodasNet) {
            $sourceLabel = 'Bodas.net';
        }

        // 9. Tipo de Evento
        $eventType = 'boda';
        if (str_contains($lowerText, 'empresa') || str_contains($lowerText, 'corporativ')) {
            $eventType = 'empresa';
        } elseif (str_contains($lowerText, 'cumplea') || str_contains($lowerText, 'aniversario')) {
            $eventType = 'cumpleanos';
        } elseif (str_contains($lowerText, 'comuni')) {
            $eventType = 'comunion';
        } elseif (str_contains($lowerText, 'video') || str_contains($lowerText, 'videografo') || str_contains($lowerText, 'videógrafo')) {
            $eventType = 'videografo';
        } elseif (str_contains($lowerText, 'fiesta') || str_contains($lowerText, 'privad')) {
            $eventType = 'otro';
        }

        return [
            'is_lead' => $isLead,
            'subject' => $subjectClean,
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'client_phone' => $phone,
            'event_date' => $eventDate,
            'location' => $location,
            'event_type' => $eventType,
            'source' => $sourceLabel,
            'campaign' => $campaign,
            'body_snippet' => mb_substr($bodyClean, 0, 1000),
            'full_body' => $bodyClean,
            'brand' => $brandKey,
        ];
    }

    /**
     * Genera las huellas únicas de un lead (por teléfono, nombre y fecha) para evitar duplicados o re-importaciones tras borrar
     */
    public static function buildLeadSignatures(?string $clientName, ?string $phone, ?string $eventDate): array
    {
        $sigs = [];
        $datePart = $eventDate ? trim($eventDate) : 'nodate';

        if (!empty($phone)) {
            $digits = preg_replace('/\D/', '', $phone);
            if (strlen($digits) >= 9) {
                $last9 = substr($digits, -9);
                $sigs[] = "phone:{$last9}|date:{$datePart}";
                $sigs[] = "phone:{$last9}";
            }
        }

        if (!empty($clientName)) {
            $cleanName = preg_replace('/^(?:Boda|Evento Empresa|Cumpleaños|Comunión|Videógrafo)\s*-\s*/iu', '', trim($clientName));
            $nameSlug = Str::slug($cleanName);
            if (!empty($nameSlug) && !in_array($nameSlug, ['cliente', 'cliente-potencial', 'contacto-web', 'novios'])) {
                $sigs[] = "name:{$nameSlug}|date:{$datePart}";
                $sigs[] = "name:{$nameSlug}";
            }
        }

        return array_values(array_unique($sigs));
    }

    /**
     * Comprueba si alguna huella del lead ya fue importada o eliminada manualmente antes
     */
    public function isLeadAlreadyProcessedOrDeleted(array $signatures): bool
    {
        if (empty($signatures)) {
            return false;
        }
        $raw = Setting::get('imap_processed_signatures', '[]');
        $stored = json_decode((string)$raw, true);
        if (!is_array($stored)) {
            return false;
        }
        foreach ($signatures as $sig) {
            if (in_array($sig, $stored, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Registra las huellas de un lead como ya procesadas (o eliminadas) para que IMAP no vuelva a crearlo nunca
     */
    public static function recordProcessedLeadSignatures(array $signatures): void
    {
        if (empty($signatures)) {
            return;
        }
        $raw = Setting::get('imap_processed_signatures', '[]');
        $stored = json_decode((string)$raw, true);
        if (!is_array($stored)) {
            $stored = [];
        }
        $merged = array_values(array_unique(array_merge($stored, $signatures)));
        if (count($merged) > 1000) {
            $merged = array_slice($merged, -1000);
        }
        Setting::set('imap_processed_signatures', json_encode($merged));
    }

    /**
     * Marca un evento que va a ser eliminado para que el sincronizador de correo nunca lo vuelva a recrear
     */
    public static function markEventAsDeleted(Event $event): void
    {
        $event->loadMissing('client');
        $clientName = $event->client ? $event->client->name : $event->name;
        $phone = $event->client ? $event->client->phone : null;
        $dateStr = $event->event_date ? $event->event_date->format('Y-m-d') : null;

        $sigs = self::buildLeadSignatures($clientName, $phone, $dateStr);
        self::recordProcessedLeadSignatures($sigs);
    }

    /**
     * Crea el evento y el cliente en la base de datos
     */
    protected function createLeadEvent(array $leadData, string $brandKey, string $initialStatus = 'no_response'): ?Event
    {
        if (empty($leadData['is_lead'])) {
            return null;
        }

        // 0. Verificar registro permanente de leads ya importados o eliminados manualmente
        $signatures = self::buildLeadSignatures(
            $leadData['client_name'] ?? null,
            $leadData['client_phone'] ?? null,
            $leadData['event_date'] ?? null
        );
        if ($this->isLeadAlreadyProcessedOrDeleted($signatures)) {
            return null;
        }

        $clientEmail = !empty($leadData['client_email']) ? strtolower(trim($leadData['client_email'])) : null;
        
        // Comprobar si el email está en lista negra para nunca asignarlo como email personal de cliente
        $bannedEmailDomains = ['bodas.net', 'microsoft.com', 'madrigalmarketing.es', 'ptd.net', 'ganoexcel.us'];
        if ($clientEmail) {
            foreach ($bannedEmailDomains as $bd) {
                if (str_ends_with($clientEmail, '@' . $bd) || str_contains($clientEmail, '.' . $bd)) {
                    $clientEmail = null;
                    break;
                }
            }
        }

        // 1. Buscar o reutilizar cliente existente (Deduplicación inteligente)
        $client = null;

        // A. Buscar por email válido
        if (!empty($clientEmail) && filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            $client = User::where('email', $clientEmail)->first();
        }

        // B. Buscar por teléfono (comparando los últimos 9 dígitos para ignorar +34 o formatos distintos)
        if (!$client && !empty($leadData['client_phone'])) {
            $digits = preg_replace('/\D/', '', $leadData['client_phone']);
            if (strlen($digits) >= 9) {
                $last9 = substr($digits, -9);
                $client = User::where('role', 'client')
                    ->where(function($q) use ($last9, $digits) {
                        $q->where('phone', 'LIKE', "%{$last9}")
                          ->orWhere('phone', $digits);
                    })->first();
            }
        }

        // C. Buscar por nombre exacto (evitando nombres genéricos de placeholder)
        if (!$client && !empty($leadData['client_name'])) {
            $trimName = trim($leadData['client_name']);
            if (mb_strlen($trimName) > 2 && !in_array(mb_strtolower($trimName), ['cliente', 'cliente potencial', 'contacto web', 'novios'])) {
                $client = User::where('role', 'client')
                    ->where('name', $trimName)
                    ->first();
            }
        }

        if ($client) {
            // Cliente encontrado: actualizamos datos faltantes si la solicitud trae información más completa
            $updates = [];
            if (empty($client->phone) && !empty($leadData['client_phone'])) {
                $updates['phone'] = $leadData['client_phone'];
            }
            if (!empty($clientEmail) && str_ends_with($client->email, '@eventosmusicales.local')) {
                $updates['email'] = $clientEmail;
            }
            if (!empty($updates)) {
                $client->update($updates);
            }
        } else {
            // Solo creamos nuevo cliente si no existía previamente por ningún identificador
            $finalEmail = (!empty($clientEmail) && filter_var($clientEmail, FILTER_VALIDATE_EMAIL))
                ? $clientEmail
                : ('lead_' . (!empty($leadData['client_phone']) ? preg_replace('/\D/', '', $leadData['client_phone']) : time() . '_' . rand(100, 999)) . '@eventosmusicales.local');

            // Verificar que el email autogenerado tampoco exista ya
            $existingByFinalEmail = User::where('email', $finalEmail)->first();
            if ($existingByFinalEmail) {
                $client = $existingByFinalEmail;
            } else {
                $client = User::create([
                    'name' => $leadData['client_name'],
                    'email' => $finalEmail,
                    'phone' => $leadData['client_phone'],
                    'role' => 'client',
                    'password' => bcrypt(Str::random(16)),
                ]);
            }
        }

        // Evitar duplicar evento si ya existe uno con el mismo cliente (en cualquier marca) y fecha exacta o creado recientemente
        if ($client) {
            $existingQuery = Event::where('client_id', $client->id);
            if (!empty($leadData['event_date'])) {
                $existing = (clone $existingQuery)->whereDate('event_date', $leadData['event_date'])->first();
                if ($existing) {
                    self::recordProcessedLeadSignatures($signatures);
                    return null;
                }
            }
            $recentSameClient = (clone $existingQuery)->where('created_at', '>=', now()->subDays(14))->first();
            if ($recentSameClient) {
                self::recordProcessedLeadSignatures($signatures);
                return null;
            }
        }

        // Registrar las huellas inmediatamente antes de crear el evento para evitar condiciones de carrera o duplicados entre buzones
        self::recordProcessedLeadSignatures($signatures);

        // 2. Nombre del Evento
        $typeLabel = match($leadData['event_type']) {
            'empresa' => 'Evento Empresa',
            'cumpleanos' => 'Cumpleaños',
            'comunion' => 'Comunión',
            'videografo' => 'Videógrafo',
            default => 'Boda',
        };
        $eventName = "{$typeLabel} - {$leadData['client_name']}";

        // 3. Asignar DJ por defecto
        $defaultDj = User::whereIn('role', ['admin', 'dj'])->orderBy('id')->first();

        $leadSource = $leadData['source'] ?? 'Portal / Web';

        // 4. Crear Evento
        $event = Event::create([
            'name' => $eventName,
            'brand' => $brandKey,
            'event_type' => $leadData['event_type'],
            'event_date' => $leadData['event_date'],
            'location' => $leadData['location'],
            'client_id' => $client ? $client->id : null,
            'dj_id' => $defaultDj ? $defaultDj->id : null,
            'status' => in_array($initialStatus, ['draft', 'no_response', 'confirmed']) ? $initialStatus : 'no_response',
            'notes' => "📩 LEAD IMPORTADO VÍA EMAIL (" . strtoupper($brandKey) . ")\n"
                . "Origen: " . $leadSource . "\n"
                . "Asunto: " . $leadData['subject'] . "\n"
                . "Fecha recepción: " . now()->format('d/m/Y H:i') . "\n"
                . "----------------------------------------\n"
                . $leadData['full_body'],
        ]);

        // 4a. Registrar el correo entrante inicial en Conversación Registrada (whatsapp_messages con canal 'email')
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('whatsapp_messages')) {
                $contactIdentifier = !empty($leadData['client_email'])
                    ? strtolower(trim($leadData['client_email']))
                    : (!empty($leadData['client_phone']) ? WhatsAppTemplateService::cleanPhone($leadData['client_phone']) : 'email');

                \App\Models\WhatsAppMessage::create([
                    'event_id' => $event->id,
                    'user_id' => $client ? $client->id : null,
                    'phone' => $contactIdentifier,
                    'sender_name' => $leadData['client_name'] ?? 'Cliente',
                    'direction' => 'inbound',
                    'message' => "📧 [Email recibido · {$leadSource}]\nAsunto: {$leadData['subject']}\n\n" . trim((string)$leadData['full_body']),
                    'status' => 'received',
                    'brand' => $brandKey,
                    'payload' => [
                        'channel' => 'email',
                        'subject' => $leadData['subject'],
                        'source' => $leadSource,
                        'from' => $leadData['client_email'] ?? null,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("No se pudo guardar correo inicial en whatsapp_messages (Evento #{$event->id}): " . $e->getMessage());
        }

        // 4b. Notificación en tiempo real dentro de la App (Campana + Push Navegador/PWA + Toast)
        try {
            $leadName = $leadData['client_name'] ?? 'Nuevo Contacto';
            $leadDate = !empty($leadData['event_date']) ? \Carbon\Carbon::parse($leadData['event_date'])->format('d/m/Y') : 'Sin fecha';
            $leadLoc = $leadData['location'] ?? 'Por definir';
            AppNotificationService::push(
                'email_lead',
                "📩 Nuevo Email Lead: {$leadName}",
                "{$leadSource} • Fecha: {$leadDate} • {$leadLoc}",
                url("/admin/events/{$event->id}"),
                $event->id,
                $brandKey
            );
        } catch (\Throwable $e) {
            Log::warning("No se pudo registrar notificación en App para lead IMAP (Evento #{$event->id}): " . $e->getMessage());
        }

        // 5. Notificar a los administradores de la empresa y la marca por correo ("y nos avise")
        try {
            AppNotificationService::sendMailableToAdmins(
                fn () => new NewLeadImportedAdminMail($event, $leadData, $brandKey),
                $brandKey
            );
        } catch (\Throwable $e) {
            Log::warning("No se pudo enviar notificación de nuevo lead por email (Evento #{$event->id}): " . $e->getMessage());
        }

        // 6. Notificar a los administradores / socios (Fran y su hermano) por WhatsApp
        try {
            if (EvolutionWhatsAppService::isEnabled()) {
                $leadName = $leadData['client_name'] ?? 'Nuevo Contacto';
                $leadDate = !empty($leadData['event_date']) ? \Carbon\Carbon::parse($leadData['event_date'])->format('d/m/Y') : 'Por definir';
                $leadPhone = $leadData['client_phone'] ?? 'No indicado';
                $leadLocation = $leadData['location'] ?? 'Por definir';

                $waLeadAlert = "📥 *¡NUEVO LEAD ENTRANTE!*\n\n"
                    . "👤 *Contacto:* {$leadName}\n"
                    . "📞 *Teléfono:* {$leadPhone}\n"
                    . "📅 *Fecha Evento:* {$leadDate}\n"
                    . "📍 *Lugar:* {$leadLocation}\n"
                    . "🏷️ *Origen:* {$leadSource}\n"
                    . "🏢 *Marca:* " . strtoupper($brandKey) . "\n\n"
                    . "Ya está registrado en la plataforma listo para enviar presupuesto.";

                EvolutionWhatsAppService::notifyAdmins($waLeadAlert, $brandKey);
            }
        } catch (\Throwable $e) {
            Log::warning("No se pudo enviar alerta de lead por WhatsApp (Evento #{$event->id}): " . $e->getMessage());
        }

        return $event;
    }

    /**
     * Si llega un correo desde una dirección de email que pertenece a un cliente existente,
     * lo añade automáticamente a la Conversación Registrada de su evento activo.
     */
    protected function recordClientEmailReplyIfExisting(
        string $fromEmail,
        string $fromName,
        string $subject,
        string $rawBody,
        string $brandKey
    ): bool {
        try {
            $cleanEmail = strtolower(trim($fromEmail));
            if (empty($cleanEmail) || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            // Ignorar portales de leads o correos propios de la empresa
            if (
                str_contains($cleanEmail, 'bodas.net') ||
                str_contains($cleanEmail, 'madrigalmarketing') ||
                str_contains($cleanEmail, 'nunezandson') ||
                str_contains($cleanEmail, 'javnx') ||
                str_contains($cleanEmail, 'magoleugim') ||
                str_ends_with($cleanEmail, '@eventosmusicales.local')
            ) {
                return false;
            }

            $client = User::where('email', $cleanEmail)->first();
            $event = null;
            if ($client) {
                $event = Event::where('client_id', $client->id)->orderByDesc('id')->first();
            }
            if (!$event && \Illuminate\Support\Facades\Schema::hasColumn('events', 'partner_email')) {
                $event = Event::where('partner_email', $cleanEmail)->orderByDesc('id')->first();
            }

            if (!$event || !\Illuminate\Support\Facades\Schema::hasTable('whatsapp_messages')) {
                return false;
            }

            $bodyClean = trim($this->cleanEmailBody($rawBody));
            if (empty($bodyClean)) {
                return false;
            }

            $externalSig = 'email_' . md5($event->id . '|' . $cleanEmail . '|' . $subject . '|' . substr($bodyClean, 0, 300));
            $alreadyExists = \App\Models\WhatsAppMessage::where('event_id', $event->id)
                ->where(function ($q) use ($externalSig, $bodyClean) {
                    $q->where('external_id', $externalSig)
                      ->orWhere('message', 'like', '%' . substr($bodyClean, 0, 120) . '%');
                })
                ->exists();

            if ($alreadyExists) {
                return false;
            }

            $senderDisplay = ($client && $client->name) ? $client->name : ($fromName ?: $cleanEmail);

            \App\Models\WhatsAppMessage::create([
                'event_id' => $event->id,
                'user_id' => $client ? $client->id : null,
                'phone' => $cleanEmail,
                'sender_name' => $senderDisplay,
                'direction' => 'inbound',
                'message' => "📧 [Email · Asunto: {$subject}]\n\n" . $bodyClean,
                'status' => 'received',
                'external_id' => $externalSig,
                'brand' => $brandKey,
                'payload' => [
                    'channel' => 'email',
                    'subject' => $subject,
                    'from' => $cleanEmail,
                ],
            ]);

            $eventUrl = url("/admin/events/{$event->id}");

            AppNotificationService::push(
                'email_reply',
                "📧 Respuesta por Email: {$senderDisplay}",
                $subject,
                $eventUrl,
                $event->id,
                $brandKey
            );

            // Notificar también por Email a todos los administradores
            try {
                AppNotificationService::sendAlertEmailToAdmins(
                    "📧 Respuesta por Email de {$senderDisplay} — {$event->name}",
                    "📧 Respuesta de cliente recibida por Email",
                    [
                        'Evento' => $event->name,
                        'Cliente' => "{$senderDisplay} ({$cleanEmail})",
                        'Asunto' => $subject,
                        'Mensaje recibido' => \Illuminate\Support\Str::limit($bodyClean, 1000),
                    ],
                    $eventUrl,
                    'Abrir Conversación en la Ficha',
                    $brandKey,
                    '#4f46e5'
                );
            } catch (\Throwable $e) {
            }

            // Notificar por WhatsApp a los administradores si está activo
            try {
                if (EvolutionWhatsAppService::isEnabled()) {
                    $waAlert = "📧 *¡RESPUESTA POR EMAIL RECIBIDA!*\n\n"
                        . "👤 *Cliente:* {$senderDisplay} ({$cleanEmail})\n"
                        . "🎉 *Evento:* {$event->name}\n"
                        . "📌 *Asunto:* {$subject}\n"
                        . "✉️ *Mensaje:* \"" . \Illuminate\Support\Str::limit($bodyClean, 180) . "\"\n\n"
                        . "🔗 *Ficha:* {$eventUrl}";
                    EvolutionWhatsAppService::notifyAdmins($waAlert, $brandKey);
                }
            } catch (\Throwable $e) {
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Asegura que el correo inicial del lead (si está en las notas del evento) y cualquier
     * correo recibido del cliente aparezcan como burbujas en Conversación Registrada.
     */
    public function syncEmailHistoryForEvent(Event $event, ?string $emailToSearch = null): array
    {
        $imported = 0;
        $targetEmail = strtolower(trim($emailToSearch ?: ($event->client_contact_email ?? '')));

        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('whatsapp_messages')) {
                return ['success' => false, 'imported' => 0, 'message' => 'La tabla de mensajes no está disponible.'];
            }

            // 1. Si el evento fue importado por email y tiene el texto en $event->notes pero aún no está en Conversación Registrada
            if (!empty($event->notes) && str_contains($event->notes, 'LEAD IMPORTADO VÍA EMAIL')) {
                $hasInitialLeadBubble = \App\Models\WhatsAppMessage::where('event_id', $event->id)
                    ->where('direction', 'inbound')
                    ->exists();

                if (!$hasInitialLeadBubble) {
                    $notesText = (string)$event->notes;
                    $posLead = strpos($notesText, '📩 LEAD IMPORTADO VÍA EMAIL');
                    if ($posLead === false) {
                        $posLead = strpos($notesText, 'LEAD IMPORTADO VÍA EMAIL');
                    }
                    $fromLeadText = $posLead !== false ? substr($notesText, $posLead) : $notesText;
                    // Extraer bloque inicial del lead antes de otras notas que pudieran estar debajo
                    $parts = preg_split('/\n\[\d{2}\/\d{2}\/\d{4}/', $fromLeadText);
                    $leadBlock = trim($parts[0] ?? $fromLeadText);

                    $subject = 'Solicitud de presupuesto por Email';
                    if (preg_match('/Asunto:\s*(.+)$/mi', $leadBlock, $mSub)) {
                        $subject = trim($mSub[1]);
                    }
                    $bodyPart = $leadBlock;
                    if (str_contains($leadBlock, '----------------------------------------')) {
                        $split = explode('----------------------------------------', $leadBlock, 2);
                        $bodyPart = trim($split[1] ?? $leadBlock);
                    }

                    \App\Models\WhatsAppMessage::create([
                        'event_id' => $event->id,
                        'user_id' => $event->client_id,
                        'phone' => $targetEmail ?: 'email',
                        'sender_name' => $event->client ? $event->client->name : 'Cliente',
                        'direction' => 'inbound',
                        'message' => "📧 [Email · Asunto: {$subject}]\n\n" . $bodyPart,
                        'status' => 'received',
                        'brand' => $event->brand_clean,
                        'payload' => [
                            'channel' => 'email',
                            'subject' => $subject,
                            'from' => $targetEmail,
                        ],
                        'created_at' => $event->created_at ?? now(),
                        'updated_at' => $event->created_at ?? now(),
                    ]);
                    $imported++;
                }
            }

            // 2. Vincular mensajes huérfanos en la BD local cuyo 'phone' coincida con el email del cliente
            if (!empty($targetEmail)) {
                $orphans = \App\Models\WhatsAppMessage::whereNull('event_id')
                    ->where('phone', $targetEmail)
                    ->get();
                foreach ($orphans as $orph) {
                    $orph->update(['event_id' => $event->id]);
                    $imported++;
                }
            }

            // 3. Ejecutar escaneo rápido de buzones IMAP activos para detectar nuevas respuestas del cliente
            $this->fetchAllActiveMailboxes($event->brand_clean);

        } catch (\Throwable $e) {
            Log::warning("Error en syncEmailHistoryForEvent (#{$event->id}): " . $e->getMessage());
        }

        return [
            'success' => $imported > 0,
            'imported' => $imported,
            'message' => $imported > 0
                ? "✅ Se han sincronizado {$imported} correo(s) en la Conversación Registrada."
                : "ℹ️ Buzón sincronizado. No hay correos nuevos pendientes de importar para {$targetEmail}.",
        ];
    }

    /**
     * Obtiene la configuración de conexión IMAP para la marca
     */
    protected function getMailboxConfig(string $brandKey): array
    {
        $host = Setting::get('imap_host', '127.0.0.1');
        $port = (int)Setting::get('imap_port', 993);
        $encryption = Setting::get('imap_encryption', 'ssl'); // ssl, tls, none

        if ($brandKey === 'javnx') {
            $email = Setting::get('imap_javnx_email', Setting::get('brand_javnx_email', 'info@javnxdj.com'));
            $password = Setting::get('imap_javnx_password', '');
            $enabled = Setting::get('imap_javnx_enabled', '1') === '1';
        } elseif ($brandKey === 'mago_leugim') {
            $email = Setting::get('imap_leugim_email', Setting::get('brand_leugim_email', 'info@magoleugim.com'));
            $password = Setting::get('imap_leugim_password', '');
            $enabled = Setting::get('imap_leugim_enabled', '0') === '1';
        } else {
            // nunez_and_son
            $email = Setting::get('imap_nunez_email', Setting::get('brand_nunez_email', 'info@nunezadson.com'));
            $password = Setting::get('imap_nunez_password', '');
            $enabled = Setting::get('imap_nunez_enabled', '1') === '1';
        }

        return [
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'email' => $email,
            'password' => $password,
            'enabled' => $enabled,
        ];
    }

    /**
     * Establece conexión IMAP (nativa o por socket)
     */
    protected function connect(array $config): array
    {
        $host = $config['host'];
        $port = $config['port'];
        $enc = strtolower($config['encryption']);

        // 1. Intentar con extensión nativa imap si está cargada
        if (function_exists('imap_open')) {
            $flagStr = match($enc) {
                'ssl' => '/imap/ssl/novalidate-cert',
                'tls' => '/imap/tls/novalidate-cert',
                default => '/imap/notls/novalidate-cert',
            };
            $mailbox = "{" . $host . ":" . $port . $flagStr . "}INBOX";
            
            // Suprimir advertencias temporales para capturar excepción limpia
            $stream = @imap_open($mailbox, $config['email'], $config['password'], 0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
            if ($stream) {
                return ['type' => 'native', 'stream' => $stream];
            }
        }

        // 2. Fallback: Socket Stream puro
        $prefix = ($enc === 'ssl') ? 'ssl://' : '';
        $timeout = 10;
        $errno = 0;
        $errstr = '';

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);

        $socket = @stream_socket_client($prefix . $host . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new \Exception("No se pudo conectar a {$host}:{$port} ({$errstr})");
        }

        stream_set_timeout($socket, $timeout);
        $greeting = fgets($socket);
        if (!$greeting || !str_starts_with($greeting, '* OK')) {
            fclose($socket);
            throw new \Exception("Servidor IMAP no respondió adecuadamente: " . trim($greeting ?: 'Sin respuesta'));
        }

        return ['type' => 'socket', 'stream' => $socket];
    }

    protected function sendSocketCmd($socket, string $cmd): array
    {
        fwrite($socket, $cmd . "\r\n");
        $lines = [];
        $tag = explode(' ', $cmd)[0];

        while (!feof($socket)) {
            $line = fgets($socket);
            if ($line === false) break;
            $lineTrim = trim($line);
            $lines[] = $lineTrim;

            if (str_starts_with($lineTrim, $tag . ' OK') || str_starts_with($lineTrim, $tag . ' NO') || str_starts_with($lineTrim, $tag . ' BAD')) {
                if (str_starts_with($lineTrim, $tag . ' NO') || str_starts_with($lineTrim, $tag . ' BAD')) {
                    throw new \Exception("Error en comando IMAP ({$cmd}): " . $lineTrim);
                }
                break;
            }
        }

        return $lines;
    }

    protected function getNativeBody($stream, int $msgNumber): string
    {
        $peeks = defined('FT_PEEK') ? FT_PEEK : 0;
        $body = @imap_fetchbody($stream, $msgNumber, "1.1", $peeks);
        if (empty($body)) {
            $body = @imap_fetchbody($stream, $msgNumber, "1", $peeks);
        }
        if (empty($body)) {
            $body = @imap_body($stream, $msgNumber, $peeks);
        }
        return (string)$body;
    }

    protected function decodeMimeHeader(string $str): string
    {
        if (function_exists('imap_mime_header_decode')) {
            $elements = @imap_mime_header_decode($str);
            if ($elements && is_array($elements)) {
                $out = '';
                foreach ($elements as $el) {
                    $charset = strtolower($el->charset ?? 'utf-8');
                    $text = $el->text ?? '';
                    if ($charset !== 'utf-8' && $charset !== 'default') {
                        $text = @mb_convert_encoding($text, 'utf-8', $charset);
                    }
                    $out .= $text;
                }
                return $out;
            }
        }

        if (function_exists('iconv_mime_decode')) {
            $decoded = @iconv_mime_decode($str, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($decoded !== false) {
                return $decoded;
            }
        }

        if (function_exists('mb_decode_mime_header')) {
            return @mb_decode_mime_header($str) ?: $str;
        }

        return $str;
    }

    protected function extractHeaderValue(string $raw, string $headerName): ?string
    {
        if (preg_match('/^' . preg_quote($headerName, '/') . ':\s*(.+)$/mi', $raw, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    protected function parseFromHeader(string $fromRaw): array
    {
        $name = '';
        $email = '';
        if (preg_match('/(.*)<(.+)>/', $fromRaw, $m)) {
            $name = trim(trim($m[1]), '"\'');
            $email = trim($m[2]);
        } else {
            $email = trim($fromRaw);
            $name = explode('@', $email)[0];
        }
        return [
            'name' => $name ?: 'Cliente',
            'email' => $email,
        ];
    }

    protected function getBrandDisplayName(string $brandKey): string
    {
        return match($brandKey) {
            'javnx' => 'JAVNX DJ',
            'mago_leugim' => 'Mago Leugim',
            default => 'Núñez and Son',
        };
    }
}
