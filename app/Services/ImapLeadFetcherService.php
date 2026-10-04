<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
     * Escanea y procesa todos los buzones activos o uno en particular
     */
    public function fetchAllActiveMailboxes(?string $specificBrand = null): array
    {
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
        $emails = imap_search($stream, 'UNSEEN');

        if (!$emails || !is_array($emails)) {
            return $result;
        }

        $markAsRead = Setting::get('imap_mark_as_read', '1') === '1';
        $initialStatus = Setting::get('imap_default_status', 'no_response'); // draft | no_response

        foreach ($emails as $emailNumber) {
            $result['scanned']++;

            $header = imap_headerinfo($stream, $emailNumber);
            $fromName = $header->from[0]->personal ?? ($header->from[0]->mailbox ?? 'Cliente');
            $fromEmail = ($header->from[0]->mailbox ?? '') . '@' . ($header->from[0]->host ?? '');
            $subject = $this->decodeMimeHeader($header->subject ?? 'Solicitud de información');
            $body = $this->getNativeBody($stream, $emailNumber);

            $parsed = $this->parseLeadFromEmail($subject, $body, $fromName, $fromEmail, $brandKey);

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
            }

            if ($markAsRead) {
                imap_setflag_full($stream, (string)$emailNumber, "\\Seen");
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

        // 3. Search UNSEEN
        $searchResp = $this->sendSocketCmd($socket, "A03 SEARCH UNSEEN");
        
        $msgIds = [];
        foreach ($searchResp as $line) {
            if (str_starts_with($line, '* SEARCH')) {
                $parts = explode(' ', trim(substr($line, 8)));
                foreach ($parts as $p) {
                    if (is_numeric($p) && (int)$p > 0) {
                        $msgIds[] = (int)$p;
                    }
                }
            }
        }

        $markAsRead = Setting::get('imap_mark_as_read', '1') === '1';
        $initialStatus = Setting::get('imap_default_status', 'no_response');

        foreach ($msgIds as $id) {
            $result['scanned']++;

            // Fetch RFC822 full text
            $fetchLines = $this->sendSocketCmd($socket, "A04 FETCH {$id} (BODY[TEXT] BODY[HEADER.FIELDS (FROM SUBJECT DATE TO)])");
            $rawContent = implode("\n", $fetchLines);

            $subject = $this->extractHeaderValue($rawContent, 'Subject') ?: 'Solicitud de información';
            $fromRaw = $this->extractHeaderValue($rawContent, 'From') ?: $user;
            $body = $rawContent;

            $fromParsed = $this->parseFromHeader($fromRaw);
            $parsed = $this->parseLeadFromEmail($subject, $body, $fromParsed['name'], $fromParsed['email'], $brandKey);

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
            }

            if ($markAsRead) {
                $this->sendSocketCmd($socket, "A05 STORE {$id} +FLAGS (\\Seen)");
            }
        }

        $this->sendSocketCmd($socket, "A06 LOGOUT");

        return $result;
    }

    /**
     * Analizador inteligente de correos para detectar y extraer leads de bodas/eventos
     */
    public function parseLeadFromEmail(string $subject, string $body, string $fromName, string $fromEmail, string $brandKey): array
    {
        $subjectClean = $this->decodeMimeHeader($subject);
        $bodyClean = strip_tags(html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        
        $fullText = $subjectClean . "\n" . $bodyClean;

        // Palabras clave que indican una solicitud o lead
        $leadKeywords = [
            'presupuesto', 'solicitud', 'contacto', 'boda', 'disponibilidad',
            'formulario', 'información', 'informacion', 'evento', 'precio',
            'bodas.net', 'zankyou', 'prontopro', 'cotización', 'cotizacion',
            'fecha', 'tarifa', 'dj', 'pack', 'reserva'
        ];

        $isLead = false;
        $lowerText = mb_strtolower($fullText);
        foreach ($leadKeywords as $kw) {
            if (str_contains($lowerText, $kw)) {
                $isLead = true;
                break;
            }
        }

        // 1. Detectar si es de Bodas.net
        $isBodasNet = str_contains($lowerText, 'bodas.net') || str_contains(mb_strtolower($fromEmail), 'bodas.net');
        
        // 2. Extraer Nombre del Cliente
        $clientName = $fromName ?: 'Cliente Potencial';
        if ($isBodasNet || str_contains($lowerText, 'nombre:')) {
            if (preg_match('/(?:Nombre|Novios?|Contacto)\s*[:\-]\s*([^\n\r,]+)/i', $bodyClean, $m)) {
                $clientName = trim($m[1]);
            }
        }

        // 3. Extraer Email del Cliente
        $clientEmail = $fromEmail;
        if ($isBodasNet || str_contains($fromEmail, 'bodas.net') || str_contains($fromEmail, 'no-reply') || str_contains($fromEmail, 'noreply')) {
            if (preg_match('/(?:Email|Correo|E-mail)\s*[:\-]?\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $bodyClean, $m)) {
                $clientEmail = trim($m[1]);
            }
        }

        // 4. Extraer Teléfono
        $phone = null;
        if (preg_match('/(?:Tel[eé]fono|Tel|M[oó]vil|Whatsapp|WhatsApp|Phone)\s*[:\-]?\s*([+\d\s().-]{9,20})/i', $bodyClean, $m)) {
            $rawPhone = preg_replace('/[^\d+]/', '', trim($m[1]));
            if (strlen(preg_replace('/[^\d]/', '', $rawPhone)) >= 9) {
                $phone = $rawPhone;
            }
        } elseif (preg_match('/\b(?:\+34\s*)?[67]\d{2}[\s.-]?\d{3}[\s.-]?\d{3}\b/', $bodyClean, $m)) {
            $phone = preg_replace('/[^\d+]/', '', trim($m[0]));
        }

        // 5. Extraer Fecha del Evento
        $eventDate = null;
        if (preg_match('/(?:Fecha|Día|Dia|Celebración|Celebracion|Enlace)\s*[:\-]?\s*([0-3]?\d[\/\-\.][0-1]?\d[\/\-\.]202[4-9])/i', $bodyClean, $m)) {
            try {
                $dateStr = str_replace(['.', '-'], '/', trim($m[1]));
                $eventDate = Carbon::createFromFormat('d/m/Y', $dateStr)->format('Y-m-d');
            } catch (\Throwable $e) {}
        } elseif (preg_match('/([0-3]?\d)\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s+(?:de\s+)?(202[4-9])/i', $bodyClean, $m)) {
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
            } catch (\Throwable $e) {}
        }

        // Si no se encuentra fecha en el texto, se asigna fecha por defecto o nula
        if (empty($eventDate)) {
            $eventDate = Carbon::now()->addMonths(3)->format('Y-m-d');
        }

        // 6. Extraer Lugar / Finca / Ciudad
        $location = 'Por definir';
        if (preg_match('/(?:Lugar|Finca|Restaurante|Espacio|Ubicaci[oó]n|Ciudad|Poblaci[oó]n)\s*[:\-]?\s*([^\n\r,]+)/i', $bodyClean, $m)) {
            $locClean = trim($m[1]);
            if (strlen($locClean) > 2) {
                $location = $locClean;
            }
        }

        // 7. Tipo de Evento
        $eventType = 'boda';
        if (str_contains($lowerText, 'empresa') || str_contains($lowerText, 'corporativ')) {
            $eventType = 'empresa';
        } elseif (str_contains($lowerText, 'cumplea') || str_contains($lowerText, 'aniversario')) {
            $eventType = 'cumpleanos';
        } elseif (str_contains($lowerText, 'comuni')) {
            $eventType = 'comunion';
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
            'body_snippet' => mb_substr($bodyClean, 0, 1000),
            'full_body' => $bodyClean,
            'brand' => $brandKey,
        ];
    }

    /**
     * Crea el evento y el cliente en la base de datos
     */
    protected function createLeadEvent(array $leadData, string $brandKey, string $initialStatus = 'no_response'): ?Event
    {
        // 1. Buscar o crear cliente
        $client = null;
        if (!empty($leadData['client_email']) && filter_var($leadData['client_email'], FILTER_VALIDATE_EMAIL)) {
            $client = User::where('email', $leadData['client_email'])->first();
            if (!$client) {
                $client = User::create([
                    'name' => $leadData['client_name'],
                    'email' => $leadData['client_email'],
                    'phone' => $leadData['client_phone'],
                    'role' => 'client',
                    'password' => bcrypt(Str::random(16)),
                ]);
            } else if (empty($client->phone) && !empty($leadData['client_phone'])) {
                $client->update(['phone' => $leadData['client_phone']]);
            }
        }

        // Evitar duplicar evento si ya existe uno con el mismo cliente, marca y fecha exacta
        if ($client && $leadData['event_date']) {
            $existing = Event::where('client_id', $client->id)
                ->where('brand', $brandKey)
                ->whereDate('event_date', $leadData['event_date'])
                ->first();
            if ($existing) {
                return null;
            }
        }

        // 2. Nombre del Evento
        $typeLabel = match($leadData['event_type']) {
            'empresa' => 'Evento Empresa',
            'cumpleanos' => 'Cumpleaños',
            'comunion' => 'Comunión',
            default => 'Boda',
        };
        $eventName = "{$typeLabel} - {$leadData['client_name']}";

        // 3. Asignar DJ por defecto
        $defaultDj = User::whereIn('role', ['admin', 'dj'])->orderBy('id')->first();

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
                . "Asunto: " . $leadData['subject'] . "\n"
                . "Fecha recepción: " . now()->format('d/m/Y H:i') . "\n"
                . "----------------------------------------\n"
                . $leadData['full_body'],
        ]);

        return $event;
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
        $body = imap_fetchbody($stream, $msgNumber, "1.1");
        if (empty($body)) {
            $body = imap_fetchbody($stream, $msgNumber, "1");
        }
        if (empty($body)) {
            $body = imap_body($stream, $msgNumber);
        }
        return $this->decodeMimeHeader($body);
    }

    protected function decodeMimeHeader(string $str): string
    {
        $elements = @imap_mime_header_decode($str);
        if (!$elements || !is_array($elements)) {
            return $str;
        }
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
