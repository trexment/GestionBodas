<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Contract;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;

class WhatsAppTemplateService
{
    public static function cleanPhone($phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', (string)$phone);
        if (str_starts_with($clean, '00') && strlen($clean) > 9) {
            $clean = substr($clean, 2);
        }
        if (strlen($clean) === 9 && in_array(substr($clean, 0, 1), ['6', '7', '8', '9'], true)) {
            $clean = '34' . $clean;
        }
        return $clean;
    }

    public static function buildUrl($phone, $message): string
    {
        $cleanPhone = self::cleanPhone($phone);
        if (!empty($cleanPhone)) {
            return "https://wa.me/{$cleanPhone}?text=" . rawurlencode($message);
        }
        return "https://api.whatsapp.com/send?text=" . rawurlencode($message);
    }

    public static function getLink($phone, $message): string
    {
        return self::buildUrl($phone, $message);
    }

    public static function getWebLink($phone, $message): string
    {
        $cleanPhone = self::cleanPhone($phone);
        if (!empty($cleanPhone)) {
            return "https://web.whatsapp.com/send?phone={$cleanPhone}&text=" . rawurlencode($message);
        }
        return "https://web.whatsapp.com/send?text=" . rawurlencode($message);
    }

    /**
     * Limpia el formato de asteriscos de WhatsApp (*texto*) para su envío limpio por Email
     */
    public static function cleanTextForEmail(string $text): string
    {
        $clean = preg_replace('/\*([^*\n]+)\*/u', '$1', $text);
        return trim((string)$clean);
    }

    /**
     * Construye un enlace mailto: con asunto y cuerpo listos para cualquier gestor de correo
     */
    public static function buildMailtoUrl(?string $email, string $subject, string $body): string
    {
        $to = trim((string)$email);
        $cleanBody = self::cleanTextForEmail($body);
        return "mailto:{$to}?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($cleanBody);
    }

    /**
     * Limpia asteriscos iniciales del nombre del cliente y extrae el nombre de contacto
     * si viene con formato compuesto (ej. "Festival de Navidad / Cristina" -> "Cristina")
     */
    public static function cleanClientDisplayName(?string $rawName, string $fallback = 'pareja', ?Event $event = null): string
    {
        if ($event && !$event->is_wedding && $fallback === 'pareja') {
            $fallback = '';
        }

        $clean = trim((string)preg_replace('/^[\s\*_]+/u', '', (string)$rawName));

        if ($clean !== '' && str_contains($clean, '/')) {
            $parts = array_values(array_filter(array_map('trim', explode('/', $clean))));
            if (count($parts) >= 2) {
                $firstLower = mb_strtolower($parts[0]);
                $eventKeywords = [
                    'festival', 'evento', 'fiesta', 'boda', 'comunión', 'comunion',
                    'bautizo', 'cumpleaños', 'cumpleanos', 'colegio', 'ampa',
                    'ayuntamiento', 'empresa', 'gala', 'cena', 'sonorización',
                    'sonorizacion', 'discomovil', 'discomóvil', 'navidad', 'fin de curso',
                ];
                foreach ($eventKeywords as $kw) {
                    if (str_contains($firstLower, $kw)) {
                        $clean = end($parts);
                        break;
                    }
                }
            }
        }

        if ($event && !$event->is_wedding && mb_strtolower($clean) === 'pareja') {
            $clean = '';
        }

        return $clean !== '' ? $clean : $fallback;
    }

    /**
     * Limpia asteriscos del nombre del evento (ej. "Boda - * Alvaro y ana" -> "Boda - Alvaro y ana")
     */
    public static function cleanEventDisplayName(?string $rawEventName, string $fallback = 'vuestro evento'): string
    {
        $clean = trim((string)preg_replace('/-\s*[\*_]+\s*/u', '- ', (string)$rawEventName));
        $clean = trim((string)preg_replace('/^[\s\*_]+/u', '', $clean));
        return $clean !== '' ? $clean : $fallback;
    }

    /**
     * Resuelve el nombre propio del remitente / firma (Miguel, Fran, Ezequiel o el usuario autenticado)
     */
    public static function resolveSenderFirstName(?string $senderBrand = null, ?Event $event = null): string
    {
        $sb = mb_strtolower(trim((string)$senderBrand));
        if (in_array($sb, ['fran', 'javnx', 'javnx_dj'], true)) {
            return 'Fran';
        }
        if ($sb === 'ezequiel') {
            return 'Ezequiel';
        }
        if ($sb === 'miguel') {
            return 'Miguel';
        }

        if (auth()->check() && auth()->user()) {
            $uName = trim((string)(auth()->user()->name ?? ''));
            $uLower = mb_strtolower($uName . ' ' . (auth()->user()->email ?? ''));
            if (str_contains($uLower, 'fran') || str_contains($uLower, 'javnx')) {
                return 'Fran';
            }
            if (str_contains($uLower, 'ezequiel')) {
                return 'Ezequiel';
            }
            if (str_contains($uLower, 'miguel')) {
                return 'Miguel';
            }
            if ($uName !== '') {
                return explode(' ', $uName)[0];
            }
        }

        if ($sb === 'nunez' || $sb === 'nunez_and_son') {
            return 'Miguel';
        }

        return ($event && $event->brand_clean === 'javnx') ? 'Fran' : 'Miguel';
    }

    /**
     * Adapta dinámicamente el vocabulario de la plantilla al tipo de evento (Boda vs Evento / Colegio / Fiesta / Empresa)
     */
    public static function adaptMessageToEventType(string $text, ?Event $event = null): string
    {
        // Limpiar saludo si {nombre} quedó vacío: "¡Hola !" -> "¡Hola!"
        $updated = preg_replace('/¡Hola\s+!/u', '¡Hola!', $text);

        $isWedding = !$event || $event->is_wedding;

        if ($isWedding) {
            // Si es boda pero la plantilla personalizada tenía "vuestra evento", corregirlo a "vuestra boda"
            $updated = preg_replace('/\bvuestra\s+evento\b/iu', 'vuestra boda', $updated);
            return $updated;
        }

        // Eventos que NO son bodas (colegios, fiestas, empresa, comuniones, sonorización, etc.)
        $updated = preg_replace('/\bhorarios\s+definitivos\s+de\s+ceremonia,\s*cóctel,\s*banquete\s+y\s+barra\s+libre,\s*y\s+el\s+contacto\s+del\s+responsable\s+de\s+la\s+finca\b/iu', 'horarios definitivos de montaje, prueba de sonido y actuación, y el contacto del responsable del espacio', $updated);
        $updated = preg_replace('/\bresponsable\s+de\s+la\s+finca\b/iu', 'responsable del espacio', $updated);
        $updated = preg_replace('/\b(?:vuestra|vuestro)\s+(?:boda|evento)\b/iu', 'vuestro evento', $updated);
        $updated = preg_replace('/\btu\s+boda\b/iu', 'tu evento', $updated);
        $updated = preg_replace('/\b(?:una|un)\s+(?:boda|evento)\b/iu', 'un evento', $updated);
        $updated = preg_replace('/\bla\s+boda\b/iu', 'el evento', $updated);
        $updated = preg_replace('/\bpre-boda\b/iu', 'pre-evento', $updated);
        $updated = preg_replace('/¡Hola\s+pareja!/iu', '¡Hola!', $updated);

        return $updated;
    }

    /**
     * Adapta dinámicamente el nombre del remitente / firma (Miguel, Fran, Ezequiel) y el tipo de evento dentro de un mensaje ya redactado
     */
    public static function applySenderSignatureToMessage(string $text, ?string $senderBrand = null, ?Event $event = null): string
    {
        $senderName = self::resolveSenderFirstName($senderBrand, $event);

        // 1. Reemplazar placeholders explícitos si existen
        $updated = str_replace(['{firma}', '{remitente}', '{usuario}'], $senderName, $text);

        // 2. Reemplazar presentaciones tipo "Soy Fran,", "Soy Miguel,", "Os escribe Fran,"
        $updated = preg_replace('/\b(Soy|Os escribe|Te escribe|Me llamo)\s+(Miguel|Fran|Ezequiel)\b/iu', '$1 ' . $senderName, $updated);

        // 3. Reemplazar firmas finales tipo "¡Un saludo! Fran", "¡Gracias! Fran", "¡Gracias por confiar en nosotros! Fran"
        $updated = preg_replace('/(¡?(?:Un\s+(?:saludo|abrazo)(?:\s+grande)?|Gracias(?:\s+por\s+confiar\s+en\s+nosotros)?)[!.,]*)\s+(Miguel|Fran|Ezequiel)\b/iu', '$1 ' . $senderName, $updated);

        // 4. Adaptar vocabulario según sea Boda o Evento/Fiesta/Colegio
        return self::adaptMessageToEventType($updated, $event);
    }

    /**
     * Devuelve el asunto de correo recomendado según la plantilla y el evento
     */
    public static function getEmailSubjectForKey(Event $event, string $key): string
    {
        $companyName = ($event && $event->brand_clean === 'javnx') ? 'JAVNX DJ' : 'Núñez and Son DJ';
        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->format('d/m/Y') : 'vuestra fecha';
        $eventName = self::cleanEventDisplayName($event->name);
        $isWedding = $event->is_wedding;
        $targetEventLabel = $isWedding ? 'vuestra boda' : 'vuestro evento';
        $preEventLabel = $isWedding ? 'pre-boda' : 'del evento';

        return match ($key) {
            'primer_contacto_presupuesto', 'primer_contacto_madrigal', 'disponibilidad', 'filtro_finca', 'estandar' =>
                "Presupuesto y música para {$targetEventLabel} ({$eventDate}) - {$companyName}",
            'verificacion_lead' =>
                "Confirmación de datos para vuestra solicitud ({$eventDate}) - {$companyName}",
            'dia3_dudas', 'seguimiento_dia_3', 'conversacion_suave', 'post_presupuesto' =>
                "¿Alguna duda sobre la propuesta para {$eventName}? - {$companyName}",
            'dia7_escasez', 'seguimiento_dia_7' =>
                "Disponibilidad de fechas para {$eventName} ({$eventDate}) - {$companyName}",
            'pregunta_no', 'tecnica_no', 'tecnica_del_no' =>
                "Consulta sobre vuestra fecha ({$eventDate}) - {$companyName}",
            'psicologia_inversa', 'cierre_expediente' =>
                "Cierre de consulta por ahora - {$eventName} ({$companyName})",
            'presupuesto', 'objecion_precio', 'seguimiento_no', 'presupuesto_no', 'followup_budget' =>
                "Ajuste de propuesta a vuestro presupuesto - {$eventName} ({$companyName})",
            'no_es_nuestro_cliente' =>
                "Sobre vuestra consulta para el {$eventDate} - {$companyName}",
            'fotomaton_360' =>
                "Información y precios: Fotomatón, Plataforma 360º y Pack Dúo - {$companyName}",
            'mesa_dulce_gofres' =>
                "Información y precios: Mesa Dulce, Fuente de Chocolate y Gofres - {$companyName}",
            'llamada', 'propuesta_llamada' =>
                "¿Hablamos 10 minutos por teléfono sobre {$targetEventLabel}? - {$companyName}",
            'dossier', 'envio_dossier', 'quote_propuesta', 'resumen_presupuesto' =>
                "Dossier y propuesta para {$targetEventLabel} ({$eventDate}) - {$companyName}",
            'cuestionario' =>
                "Cuestionario musical corto para vuestra propuesta a medida - {$companyName}",
            'contrato' =>
                "Contrato para formalizar la reserva ({$eventDate}) - {$companyName}",
            'confirmacion_senal' =>
                "Confirmación de señal y reserva oficial ({$eventDate}) - {$companyName}",
            'recordatorio_pago', 'liquidacion' =>
                "Recordatorio de pago pendiente - {$eventName} ({$companyName})",
            'pre_boda' =>
                "Preparativos y logística {$preEventLabel} ({$eventDate}) - {$companyName}",
            default =>
                "Seguimiento de vuestro evento {$eventName} ({$eventDate}) - {$companyName}",
        };
    }

    /**
     * Envía un correo al cliente usando el servidor de correo configurado y lo registra
     * en la tabla whatsapp_messages (con canal 'email') para que quede reflejado en la Conversación Registrada.
     */
    public static function sendAndLogClientEmail(
        Event $event,
        string $toEmail,
        string $subject,
        string $rawBody,
        ?string $brandKey = null,
        ?int $userId = null,
        ?string $templateTitle = null
    ): array {
        $toEmail = strtolower(trim($toEmail));
        $cleanBody = self::cleanTextForEmail($rawBody);
        $subject = trim($subject) ?: self::getEmailSubjectForKey($event, 'default');
        $effectiveBrand = $brandKey ?: ($event->brand_clean ?: 'nunez_and_son');
        $brandInfo = Setting::getBrandInfo($effectiveBrand);
        $companyName = $brandInfo['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $replyToEmail = $brandInfo['email'] ?? Setting::get('company_email', config('mail.from.address'));
        $mailtoUrl = self::buildMailtoUrl($toEmail, $subject, $cleanBody);

        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'mode' => 'invalid_email',
                'message' => 'La dirección de correo electrónico no es válida.',
                'mailto_url' => $mailtoUrl,
            ];
        }

        $sentViaSmtp = false;
        $smtpError = null;

        try {
            // Convertir saltos de línea y hacer clicables las URLs en la versión HTML del correo
            $escaped = e($cleanBody);
            $htmlParagraphs = preg_replace(
                '~(https?://[^\s<]+)~i',
                '<a href="$1" style="color:#4f46e5;font-weight:bold;text-decoration:underline;" target="_blank">$1</a>',
                $escaped
            );
            $htmlContent = '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.65; color: #1e293b; max-width: 640px; margin: 0 auto; padding: 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;">'
                . nl2br($htmlParagraphs)
                . '<hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0 12px 0;">'
                . '<div style="font-size:12px;color:#64748b;"><strong>' . e($companyName) . '</strong>'
                . (!empty($brandInfo['phone']) ? ' &bull; Tel/WhatsApp: ' . e($brandInfo['phone']) : '')
                . (!empty($replyToEmail) ? ' &bull; ' . e($replyToEmail) : '')
                . '</div></div>';

            \Illuminate\Support\Facades\Mail::html($htmlContent, function ($message) use ($toEmail, $subject, $companyName, $replyToEmail) {
                $message->to($toEmail)
                        ->subject($subject);
                if (!empty($replyToEmail) && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                    $message->replyTo($replyToEmail, $companyName);
                }
            });

            $sentViaSmtp = true;
        } catch (\Throwable $e) {
            $smtpError = $e->getMessage();
            \Illuminate\Support\Facades\Log::warning("Fallo envío SMTP directo a {$toEmail}: " . $smtpError);
        }

        // Registrar siempre en Conversación Registrada (whatsapp_messages con canal 'email')
        $msgRecord = null;
        try {
            $bubbleMessage = "📧 [Email · Asunto: {$subject}]\n\n" . $cleanBody;
            $msgRecord = \App\Models\WhatsAppMessage::create([
                'event_id' => $event->id,
                'user_id' => $userId ?: (auth()->check() ? auth()->id() : null),
                'phone' => $toEmail,
                'sender_name' => $companyName,
                'direction' => 'outbound',
                'message' => $bubbleMessage,
                'status' => $sentViaSmtp ? 'sent' : 'manual',
                'brand' => $effectiveBrand,
                'payload' => [
                    'channel' => 'email',
                    'subject' => $subject,
                    'to' => $toEmail,
                    'template' => $templateTitle,
                    'smtp_sent' => $sentViaSmtp,
                ],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("No se pudo guardar registro de email en whatsapp_messages: " . $e->getMessage());
        }

        // Actualizar no_response_at y notas del evento (arriba del todo)
        try {
            $authorName = auth()->check() ? auth()->user()->name : $companyName;
            $label = $templateTitle ?: $subject;
            $newEntry = "[" . now()->format('d/m/Y H:i') . "] 📧 Email enviado a {$toEmail} ({$label}) por {$authorName}. Contador reseteado a 0.";
            $event->update([
                'no_response_at' => now(),
                'notes' => \App\Models\Event::prependNote($event->notes, $newEntry),
            ]);
        } catch (\Throwable $e) {}

        if ($sentViaSmtp) {
            return [
                'success' => true,
                'mode' => 'smtp',
                'message' => "✅ ¡Email enviado directamente a {$toEmail} y añadido a la Conversación Registrada!",
                'record_id' => $msgRecord ? $msgRecord->id : null,
                'mailto_url' => $mailtoUrl,
            ];
        }

        return [
            'success' => true,
            'mode' => 'mailto_fallback',
            'message' => "📧 Mensaje registrado en la conversación. Abriendo tu gestor de correo para enviar a {$toEmail}...",
            'error' => $smtpError,
            'record_id' => $msgRecord ? $msgRecord->id : null,
            'mailto_url' => $mailtoUrl,
        ];
    }

    /**
     * Catálogo de plantillas editables desde el panel web (con soporte de variables dinámicas)
     */
    public static function getEditableTemplatesCatalog(): array
    {
        return [
            'primer_contacto_presupuesto' => [
                'label' => '👋 1er Contacto + /presupuesto',
                'default' => "¡Hola {nombre}! Soy {firma}, de {empresa}. Gracias por escribirnos para vuestra boda del {fecha} en {lugar}. Os dejo el presupuesto: {enlace_presupuesto}. Si queréis, os explico lo que incluye y resolvemos cualquier duda. ¡Un saludo! {firma}",
            ],
            'primer_contacto_madrigal' => [
                'label' => '🟢 1er Contacto (Madrigal)',
                'default' => "¡Hola {nombre}! Soy {firma}, de {empresa}. Vi que nos escribisteis para vuestra boda del {fecha}. ¿Me contáis cómo imagináis la música y el ambiente? Así os preparo una propuesta a medida. ¡Un saludo! {firma}",
            ],
            'verificacion_lead' => [
                'label' => '🕵️ Verificación de Lead Sospechoso',
                'default' => "¡Hola! Soy {firma}, de {empresa}. Hemos recibido una solicitud de información para una boda el {fecha}. Antes de prepararos y enviaros el presupuesto, ¿me confirmáis por favor vuestro nombre completo, teléfono de contacto y el lugar del evento para anotarlo bien en la ficha? ¡Un saludo! {firma}",
            ],
            'llamada' => [
                'label' => '📞 Proponer Llamada',
                'default' => "¡Hola {nombre}! Si os viene bien, podemos hablar 10 minutos por teléfono para ver vuestras necesidades. ¿Qué día y hora os va mejor? ¡Un saludo! {firma}",
            ],
            'dossier' => [
                'label' => '📑 Envío Dossier',
                'default' => "¡Hola {nombre}! Os adjunto el dossier con nuestros trabajos y servicios: {enlace_dossier}. Si queréis, lo miramos juntos y vemos cuál encaja mejor con vuestra boda. ¡Un saludo! {firma}",
            ],
            'dia3_dudas' => [
                'label' => '💡 Dudas (Día 3)',
                'default' => "¡Hola {nombre}! ¿Os quedó alguna duda sobre la propuesta? Si algo no os cuadra, lo vemos y lo ajustamos. ¡Un saludo! {firma}",
            ],
            'dia7_escasez' => [
                'label' => '⚡ Escasez (Día 7)',
                'default' => "¡Hola {nombre}! Os aviso de que tenemos pocas fechas libres en {mes}. Si os interesa reservar, decidme y lo preparamos. ¡Un saludo! {firma}",
            ],
            'pregunta_no' => [
                'label' => '🎯 Técnica NO (Día 10)',
                'default' => "¡Hola {nombre}! Entiendo que quizá no sea el momento. ¿Me decís si lo descartáis o preferís que os escriba más adelante? ¡Un saludo! {firma}",
            ],
            'psicologia_inversa' => [
                'label' => '🔒 Cierre de Expediente',
                'default' => "¡Hola {nombre}! Como no tengo noticias, doy por cerrada la consulta por ahora. Si más adelante necesitáis DJ, aquí estaré. ¡Un saludo! {firma}",
            ],
            'presupuesto' => [
                'label' => '💰 Objeción Precio (Adaptar)',
                'default' => "¡Hola {nombre}! Entiendo lo del presupuesto. Puedo ajustar algunos servicios para que encaje con lo que tenéis en mente, sin tocar lo esencial del sonido y el montaje. ¿Os preparo esa opción? ¡Un saludo! {firma}",
            ],
            'no_es_nuestro_cliente' => [
                'label' => '👑 No es nuestro cliente (Precio)',
                'default' => "¡Hola {nombre}! Gracias por la sinceridad. Entendemos que no encaja con lo que buscáis, así que cerramos la consulta por ahora. Os deseamos mucha suerte con {evento}. ¡Un saludo! {firma}",
            ],
            'cuestionario' => [
                'label' => '📋 Cuestionario',
                'default' => "¡Hola {nombre}! Para preparar vuestra propuesta a medida, rellenad este cuestionario corto (menos de 2 minutos): {enlace_cuestionario}. ¡Gracias! {firma}",
            ],
            'contrato' => [
                'label' => '✍️ Enlace Contrato',
                'default' => "¡Hola {nombre}! Aquí tenéis el contrato para formalizar la reserva de vuestra boda del {fecha}: {enlace_contrato}. Con la firma y la señal queda reservada la fecha. Cualquier duda, me decís. ¡Gracias por confiar en nosotros! {firma}",
            ],
            'confirmacion_senal' => [
                'label' => '✅ Confirmación tras la Señal',
                'default' => "¡Hola {nombre}! Soy {firma}, de {empresa}. Confirmamos que hemos recibido la señal correctamente. Queda reservada oficialmente vuestra boda del {fecha} en {lugar} ({horario}). El próximo paso será completar el cuestionario musical unas semanas antes del evento en: {enlace_cuestionario}. ¡Gracias por confiar en nosotros! ¡Un saludo! {firma}",
            ],
            'recordatorio_pago' => [
                'label' => '⏳ Recordatorio de Pago Pendiente',
                'default' => "¡Hola {nombre}! Soy {firma}, de {empresa}. Os escribo para recordaros que está pendiente el pago de {importe_pendiente} correspondiente a vuestra boda del {fecha} (fecha límite: {fecha_limite_pago}). Cuando lo tengáis hecho, enviadnos el justificante por aquí para dejarlo anotado. ¡Un saludo! {firma}",
            ],
            'pre_boda' => [
                'label' => '🎉 Pre-boda (Logística)',
                'default' => "¡Hola {nombre}! Soy {firma}, de {empresa}. Ya queda muy poquito para vuestra boda del {fecha} en {lugar} y estamos organizando la logística y el montaje. ¿Me confirmáis los horarios definitivos de ceremonia, cóctel, banquete y barra libre, y el contacto del responsable de la finca? Si aún os falta alguna canción, podéis anotarla en: {enlace_cuestionario}. ¡Un saludo! {firma}",
            ],
        ];
    }

    public static function getRawTemplateForEdit(string $key): string
    {
        $catalog = self::getEditableTemplatesCatalog();
        $custom = Setting::get('wa_custom_tpl_' . $key, '');
        if (!empty(trim((string)$custom))) {
            return (string)$custom;
        }
        return $catalog[$key]['default'] ?? '';
    }

    public static function hasCustomTemplate(string $key): bool
    {
        return !empty(trim((string)Setting::get('wa_custom_tpl_' . $key, '')));
    }

    public static function saveCustomTemplate(string $key, string $rawText): void
    {
        Setting::set('wa_custom_tpl_' . $key, trim($rawText));
    }

    public static function resetCustomTemplate(string $key): void
    {
        Setting::set('wa_custom_tpl_' . $key, '');
    }

    /**
     * Calcula todas las variables dinámicas de un evento para interpolarlas en cualquier plantilla
     */
    public static function getTemplateVariables(?Event $event, ?string $senderBrand = null): array
    {
        $isWedding = !$event || $event->is_wedding;
        $defaultFallback = $isWedding ? 'pareja' : '';
        $rawClientName = ($event && $event->client) ? $event->client->name : ($event ? ($event->partner_name ?: $defaultFallback) : $defaultFallback);
        $clientName = self::cleanClientDisplayName($rawClientName, $defaultFallback, $event);
        $senderName = self::resolveSenderFirstName($senderBrand, $event);

        $isJavnx = ($event && $event->brand_clean === 'javnx' && in_array(mb_strtolower((string)$senderBrand), ['fran', 'javnx', 'javnx_dj'], true));
        $companyName = $isJavnx ? 'JAVNX DJ' : 'Núñez and Son DJ';

        $eventDate = ($event && $event->event_date)
            ? Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY')
            : 'vuestra fecha';
        $monthName = ($event && $event->event_date)
            ? mb_strtolower(Carbon::parse($event->event_date)->locale('es')->isoFormat('MMMM'))
            : 'ese mes';
        $location = ($event && !empty($event->location)) ? $event->location : 'vuestro espacio';
        $eventName = self::cleanEventDisplayName($event ? $event->name : null);

        $presupuestoCalcUrl = self::getQuoteCalculatorUrl($event);
        $activeQuote = $event ? $event->quotes()->latest()->first() : null;
        $onlineUrl = $activeQuote
            ? $activeQuote->public_url
            : (($event && $event->token) ? route('guest.quote.view', $event->token) : $presupuestoCalcUrl);
        $presupuestoUrl = $activeQuote ? $onlineUrl : $presupuestoCalcUrl;
        $formUrl = ($event && $event->token) ? route('guest.form', $event->token) : '';
        $signUrl = ($event && $event->token) ? route('guest.contract', $event->token) : '';

        // Importes de señal y pendiente
        $quoteAmount = $activeQuote ? (float)$activeQuote->amount : (float)($event->total_budget ?? 0);
        if ($activeQuote && $activeQuote->signal_amount > 0) {
            $signalAmount = (float)$activeQuote->signal_amount;
        } else {
            $depType = Setting::get('deposit_type', 'fixed');
            if ($depType === 'fixed') {
                $signalAmount = (float)Setting::get('deposit_fixed_amount', 200);
            } else {
                $pct = (float)Setting::get('deposit_percentage', 40);
                $signalAmount = $quoteAmount > 0 ? round(($quoteAmount * $pct) / 100, 2) : 200;
            }
        }
        $signalFormatted = number_format($signalAmount, 0, ',', '.') . ' €';

        $paidSoFar = (float)($event->deposit_paid ?? 0);
        if ($paidSoFar <= 0) {
            $pendingFormatted = $signalFormatted . ' (señal de reserva)';
        } else {
            $remaining = max(0, $quoteAmount - $paidSoFar);
            $pendingFormatted = $remaining > 0 ? (number_format($remaining, 0, ',', '.') . ' €') : $signalFormatted;
        }

        if ($activeQuote && $activeQuote->expires_at) {
            $deadlineFormatted = $activeQuote->expires_at->format('d/m/Y');
        } elseif ($event && $event->event_date) {
            $deadlineFormatted = Carbon::parse($event->event_date)->subDays(7)->format('d/m/Y');
        } else {
            $deadlineFormatted = now()->addDays(7)->format('d/m/Y');
        }

        // Horario resumido
        $scheduleParts = [];
        if ($event && !empty($event->ceremony_time)) {
            $ceremonyLabel = $isWedding ? 'Ceremonia' : 'Sonorización';
            $scheduleParts[] = $ceremonyLabel . ' ' . substr($event->ceremony_time, 0, 5) . 'h';
        }
        if ($event && !empty($event->cocktail_time)) {
            $scheduleParts[] = 'Cóctel ' . substr($event->cocktail_time, 0, 5) . 'h';
        }
        if ($event && !empty($event->banquet_time)) {
            $scheduleParts[] = 'Banquete ' . substr($event->banquet_time, 0, 5) . 'h';
        }
        if ($event && !empty($event->dance_start_time)) {
            $scheduleParts[] = 'Baile ' . $event->dance_schedule_label;
        }
        $scheduleStr = !empty($scheduleParts) ? implode(', ', $scheduleParts) : 'horario según escaleta acordada';

        return [
            '{nombre}' => $clientName,
            '{fecha}' => $eventDate,
            '{mes}' => $monthName,
            '{lugar}' => $location,
            '{empresa}' => $companyName,
            '{evento}' => $eventName,
            '{tipo_evento}' => $isWedding ? 'boda' : 'evento',
            '{vuestro_evento}' => $isWedding ? 'vuestra boda' : 'vuestro evento',
            '{firma}' => $senderName,
            '{remitente}' => $senderName,
            '{usuario}' => $senderName,
            '{horario}' => $scheduleStr,
            '{importe}' => $signalFormatted,
            '{importe_senal}' => $signalFormatted,
            '{importe_pendiente}' => $pendingFormatted,
            '{fecha_limite_pago}' => $deadlineFormatted,
            '{enlace}' => $presupuestoUrl,
            '{enlace_presupuesto}' => $presupuestoUrl,
            '{enlace_dossier}' => $onlineUrl,
            '{enlace_cuestionario}' => $formUrl,
            '{enlace_contrato}' => $signUrl,
        ];
    }

    public static function renderTemplateByKey(string $key, ?Event $event, ?string $senderBrand = null): string
    {
        $rawTemplate = self::getRawTemplateForEdit($key);
        $vars = self::getTemplateVariables($event, $senderBrand);
        $rendered = str_replace(array_keys($vars), array_values($vars), $rawTemplate);
        return self::applySenderSignatureToMessage($rendered, $senderBrand, $event);
    }

    public static function renderCustomIfPresent(string $key, ?Event $event, string $defaultRendered, ?string $senderBrand = null): string
    {
        try {
            $custom = trim((string)Setting::get('wa_custom_tpl_' . $key, ''));
            if (empty($custom) || !$event) {
                return self::applySenderSignatureToMessage($defaultRendered, $senderBrand, $event);
            }

            $vars = self::getTemplateVariables($event, $senderBrand);
            $rendered = str_replace(array_keys($vars), array_values($vars), $custom);

            return self::applySenderSignatureToMessage($rendered, $senderBrand, $event);
        } catch (\Throwable $e) {
            return self::applySenderSignatureToMessage($defaultRendered, $senderBrand, $event);
        }
    }

    public static function cuestionario(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('cuestionario', $event, $senderBrand);
    }

    public static function contrato($contractOrEvent, ?string $senderBrand = null): string
    {
        $event = ($contractOrEvent instanceof Contract) ? $contractOrEvent->event : $contractOrEvent;
        return self::renderTemplateByKey('contrato', $event, $senderBrand);
    }

    public static function confirmacionSenal(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('confirmacion_senal', $event, $senderBrand);
    }

    public static function recordatorioPagoPendiente(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('recordatorio_pago', $event, $senderBrand);
    }

    public static function verificacionLeadSospechoso(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('verificacion_lead', $event, $senderBrand);
    }

    public static function preBodaLogistica(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('pre_boda', $event, $senderBrand);
    }

    public static function liquidacion(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('recordatorio_pago', $event, $senderBrand);
    }

    public static function getQuoteCalculatorUrl(?Event $event = null): string
    {
        try {
            return url('/presupuesto');
        } catch (\Throwable $e) {
            return 'https://app.nunezandson.com/presupuesto';
        }
    }

    /**
     * ETAPA 1: APERTURA & PRIMERA TOMA DE CONTACTO (Respuesta inmediata / Lead in).
     */
    public static function primeraTomaContacto(Event $event, string $variant = 'estandar', ?string $senderBrand = null): string
    {
        if ($variant === 'filtro_finca' || $variant === 'primer_contacto_madrigal') {
            return self::renderTemplateByKey('primer_contacto_madrigal', $event, $senderBrand);
        }
        return self::renderTemplateByKey('primer_contacto_presupuesto', $event, $senderBrand);
    }

    /**
     * ETAPA 2 & 3: SEGUIMIENTO DE CONVERSACIÓN EN CURSO.
     */
    public static function seguimientoConversacion(Event $event, string $variant = 'suave', ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('dia3_dudas', $event, $senderBrand);
    }

    /**
     * PROPUESTA DE LLAMADA RÁPIDA (10 min).
     */
    public static function propuestaLlamada(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('llamada', $event, $senderBrand);
    }

    /**
     * ETAPA 4: ENVÍO DEL DOSSIER CON EL PRESUPUESTO.
     */
    public static function envioDossierPresupuesto(Event $event, ?\App\Models\Quote $quote = null, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('dossier', $event, $senderBrand);
    }

    /**
     * SEGUIMIENTO TRAS ENVIAR PRESUPUESTO (Día 3).
     */
    public static function seguimientoPostPresupuesto(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('dia3_dudas', $event, $senderBrand);
    }

    /**
     * Técnica del NO (Día 10).
     */
    public static function seguimientoPreguntaNo(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('pregunta_no', $event, $senderBrand);
    }

    /**
     * Cierre de Expediente.
     */
    public static function seguimientoPsicologiaInversa(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('psicologia_inversa', $event, $senderBrand);
    }

    /**
     * DUDAS (Día 3).
     */
    public static function seguimientoDia3Dudas(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('dia3_dudas', $event, $senderBrand);
    }

    /**
     * ESCASEZ (Día 7).
     */
    public static function seguimientoDia7Escasez(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('dia7_escasez', $event, $senderBrand);
    }

    /**
     * OBJECIÓN PRECIO (Adaptar).
     */
    public static function seguimientoPresupuestoNo(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('presupuesto', $event, $senderBrand);
    }

    /**
     * NO ES NUESTRO CLIENTE (Precio - versión cordial).
     */
    public static function descartePosicionamientoPrecio(Event $event, ?string $senderBrand = null): string
    {
        return self::renderTemplateByKey('no_es_nuestro_cliente', $event, $senderBrand);
    }

    /**
     * Determina la clave de seguimiento recomendada según los días sin respuesta (Método Madrigal)
     */
    public static function getRecommendedFollowupKey(Event $event): string
    {
        $hasQuotes = $event->relationLoaded('quotes') ? $event->quotes->isNotEmpty() : $event->quotes()->exists();
        $days = $event->days_without_response;

        if (!$hasQuotes || $days <= 1) {
            return 'primer_contacto_presupuesto';
        }
        if ($days <= 3) {
            return 'dia3_dudas';
        }
        if ($days <= 8) {
            return 'dia7_escasez';
        }
        if ($days <= 14) {
            return 'pregunta_no';
        }
        return 'psicologia_inversa';
    }

    /**
     * Devuelve todas las opciones de seguimiento con su URL y estado recomendado
     */
    public static function getFollowupOptions(Event $event, ?string $senderBrand = null): array
    {
        $recommendedKey = self::getRecommendedFollowupKey($event);
        $clientPhone = $event->client ? $event->client->phone : ($event->partner_phone ?? '');

        $options = [
            'primer_contacto_presupuesto' => [
                'key' => 'primer_contacto_presupuesto',
                'title' => '1er Contacto + /presupuesto',
                'stage' => '1er Contacto',
                'icon' => '👋',
                'description' => 'Saludo inicial con enlace al presupuesto y disposición para resolver dudas.',
                'text' => self::primeraTomaContacto($event, 'presupuesto_online', $senderBrand),
            ],
            'primer_contacto_madrigal' => [
                'key' => 'primer_contacto_madrigal',
                'title' => '1er Contacto (Madrigal)',
                'stage' => '1er Contacto',
                'icon' => '🟢',
                'description' => 'Pregunta cómo imaginan la música y el ambiente para preparar propuesta a medida.',
                'text' => self::primeraTomaContacto($event, 'filtro_finca', $senderBrand),
            ],
            'verificacion_lead' => [
                'key' => 'verificacion_lead',
                'title' => 'Verificación de Lead Sospechoso',
                'stage' => 'Filtro Lead',
                'icon' => '🕵️',
                'description' => 'Antes de enviar presupuesto, pide confirmar nombre completo y teléfono para filtrar contactos falsos.',
                'text' => self::verificacionLeadSospechoso($event, $senderBrand),
            ],
            'llamada' => [
                'key' => 'llamada',
                'title' => 'Proponer Llamada (10 min)',
                'stage' => 'Llamada',
                'icon' => '📞',
                'description' => 'Propone hablar 10 minutos por teléfono para ver sus necesidades.',
                'text' => self::propuestaLlamada($event, $senderBrand),
            ],
            'dossier' => [
                'key' => 'dossier',
                'title' => 'Envío Dossier',
                'stage' => 'Propuesta',
                'icon' => '📑',
                'description' => 'Adjunta el enlace al dossier con vuestros trabajos y servicios.',
                'text' => self::envioDossierPresupuesto($event, null, $senderBrand),
            ],
            'dia3_dudas' => [
                'key' => 'dia3_dudas',
                'title' => 'Dudas (Día 3)',
                'stage' => 'Día 3',
                'icon' => '💡',
                'description' => 'Pregunta si quedó alguna duda sobre la propuesta para verlo y ajustarlo.',
                'text' => self::seguimientoDia3Dudas($event, $senderBrand),
            ],
            'dia7_escasez' => [
                'key' => 'dia7_escasez',
                'title' => 'Escasez (Día 7)',
                'stage' => 'Día 7',
                'icon' => '⚡',
                'description' => 'Avisa de que quedan pocas fechas libres en su mes (usar solo si es real).',
                'text' => self::seguimientoDia7Escasez($event, $senderBrand),
            ],
            'pregunta_no' => [
                'key' => 'pregunta_no',
                'title' => 'Técnica NO (Día 10)',
                'stage' => 'Día 10',
                'icon' => '🎯',
                'description' => 'Pregunta si lo descartan o prefieren hablar más adelante.',
                'text' => self::seguimientoPreguntaNo($event, $senderBrand),
            ],
            'psicologia_inversa' => [
                'key' => 'psicologia_inversa',
                'title' => 'Cierre de Expediente',
                'stage' => 'Cierre',
                'icon' => '🔒',
                'description' => 'Da por cerrada la consulta por ahora quedando a disposición si más adelante necesitan DJ.',
                'text' => self::seguimientoPsicologiaInversa($event, $senderBrand),
            ],
            'presupuesto' => [
                'key' => 'presupuesto',
                'title' => 'Objeción Precio (Adaptar)',
                'stage' => 'Económica',
                'icon' => '💰',
                'description' => 'Ofrece ajustar horas de baile o cóctel sin tocar sonido ni montaje.',
                'text' => self::seguimientoPresupuestoNo($event, $senderBrand),
            ],
            'no_es_nuestro_cliente' => [
                'key' => 'no_es_nuestro_cliente',
                'title' => 'No es nuestro cliente (Precio)',
                'stage' => 'Descarte Cordial',
                'icon' => '👑',
                'description' => $event->is_wedding
                    ? 'Versión cordial: agradece la sinceridad, cierra la consulta y les desea suerte con la boda.'
                    : 'Versión cordial: agradece la sinceridad, cierra la consulta y les desea suerte con el evento.',
                'text' => self::descartePosicionamientoPrecio($event, $senderBrand),
            ],
            'cuestionario' => [
                'key' => 'cuestionario',
                'title' => 'Cuestionario (5 preguntas)',
                'stage' => 'Cualificación',
                'icon' => '📋',
                'description' => 'Pide los 5 datos clave (fecha/lugar, invitados, horarios, música y presupuesto aproximado).',
                'text' => self::cuestionario($event, $senderBrand),
            ],
            'contrato' => [
                'key' => 'contrato',
                'title' => 'Enlace Contrato',
                'stage' => 'Reserva',
                'icon' => '✍️',
                'description' => 'Envía el enlace del contrato e indica el importe de la señal para reservar la fecha.',
                'text' => self::contrato($event, $senderBrand),
            ],
            'confirmacion_senal' => [
                'key' => 'confirmacion_senal',
                'title' => 'Confirmación tras la Señal',
                'stage' => 'Post-Reserva',
                'icon' => '✅',
                'description' => 'Confirma recepción de señal, fecha, lugar, horario y próximo paso.',
                'text' => self::confirmacionSenal($event, $senderBrand),
            ],
            'recordatorio_pago' => [
                'key' => 'recordatorio_pago',
                'title' => 'Recordatorio de Pago Pendiente',
                'stage' => 'Pagos',
                'icon' => '⏳',
                'description' => 'Recordatorio amable para la señal o el resto pendiente con fecha límite.',
                'text' => self::recordatorioPagoPendiente($event, $senderBrand),
            ],
            'pre_boda' => [
                'key' => 'pre_boda',
                'title' => $event->is_wedding ? 'Pre-boda (Logística)' : 'Pre-evento (Logística)',
                'stage' => $event->is_wedding ? 'Pre-boda' : 'Pre-evento',
                'icon' => '🎉',
                'description' => $event->is_wedding
                    ? 'Mensaje de logística unas semanas antes del evento para confirmar horarios y contacto de finca.'
                    : 'Mensaje de logística antes del evento para confirmar horarios de montaje, sonido y responsable del espacio.',
                'text' => self::preBodaLogistica($event, $senderBrand),
            ],
            'fotomaton_360' => [
                'key' => 'fotomaton_360',
                'title' => 'Info & Precios: Fotomatón, Plataforma 360º y Pack Dúo',
                'stage' => 'Catálogo Extras',
                'icon' => '📸',
                'description' => 'Envía todo el detalle de lo que incluye el Fotomatón (2h/3h), la Plataforma 360º (2h/3h) y el Pack Dúo Todo Incluido.',
                'text' => self::infoFotomatonY360($event),
            ],
            'mesa_dulce_gofres' => [
                'key' => 'mesa_dulce_gofres',
                'title' => 'Info & Precios: Mesa Dulce Chocolate, Chuches y Carrito de Gofres',
                'stage' => 'Catálogo Extras',
                'icon' => '🍫',
                'description' => 'Envía la información completa y tarifas de Mesa Dulce con Fuente de Chocolate (1 o 2 fuentes), Mesa de Solo Chuches y Carrito de Gofres hechos al momento.',
                'text' => self::infoMesaDulceYGofres($event),
            ],
        ];

        $clientEmail = $event->client_contact_email ?? '';

        foreach ($options as $k => &$opt) {
            $opt['is_recommended'] = ($k === $recommendedKey);
            $opt['url'] = self::buildUrl($clientPhone, $opt['text']);
            $opt['web_url'] = self::getWebLink($clientPhone, $opt['text']);
            $opt['subject'] = self::getEmailSubjectForKey($event, $k);
            $opt['email_text'] = self::cleanTextForEmail($opt['text']);
            $opt['mailto_url'] = self::buildMailtoUrl($clientEmail, $opt['subject'], $opt['text']);
            $opt['email'] = $clientEmail;
        }

        return $options;
    }

    public static function followupReminder(Event $event, ?string $variant = null): array
    {
        $clientPhone = $event->client ? $event->client->phone : ($event->partner_phone ?? '');
        $clientEmail = $event->client_contact_email ?? '';
        if (empty($variant)) {
            $variant = self::getRecommendedFollowupKey($event);
        }

        $text = match($variant) {
            'primer_contacto_presupuesto' => self::primeraTomaContacto($event, 'presupuesto_online'),
            'primer_contacto_madrigal' => self::primeraTomaContacto($event, 'filtro_finca'),
            'dia3_dudas' => self::seguimientoDia3Dudas($event),
            'dia7_escasez' => self::seguimientoDia7Escasez($event),
            'psicologia_inversa' => self::seguimientoPsicologiaInversa($event),
            'presupuesto' => self::seguimientoPresupuestoNo($event),
            'no_es_nuestro_cliente' => self::descartePosicionamientoPrecio($event),
            'fotomaton_360' => self::infoFotomatonY360($event),
            'mesa_dulce_gofres' => self::infoMesaDulceYGofres($event),
            default => self::seguimientoPreguntaNo($event),
        };

        $title = match($variant) {
            'primer_contacto_presupuesto' => 'Día 0 (1er Contacto): Fecha Libre + Enlace /presupuesto',
            'primer_contacto_madrigal' => 'Día 0 (Madrigal): Rompehielos Finca & Fiesta',
            'dia3_dudas' => 'Día 2-3: Dudas & Flexibilidad',
            'dia7_escasez' => 'Día 5-7: Cierre por Escasez (Prioridad 24h)',
            'psicologia_inversa' => 'Psicología Inversa / Cierre de Expediente',
            'presupuesto' => 'Reactivación por Presupuesto (Técnica del NO)',
            'no_es_nuestro_cliente' => 'Descarte por Posicionamiento ("No eres nuestro perfil de cliente")',
            'fotomaton_360' => 'Info & Precios: Fotomatón, Plataforma 360º y Pack Dúo',
            'mesa_dulce_gofres' => 'Info & Precios: Mesa Dulce Chocolate, Chuches y Carrito de Gofres',
            default => 'Técnica del NO / ¿Descartáis la fecha?',
        };

        $icon = match($variant) {
            'primer_contacto_presupuesto' => '👋',
            'primer_contacto_madrigal' => '🟢',
            'dia3_dudas' => '💡',
            'dia7_escasez' => '⚡',
            'psicologia_inversa' => '🚪',
            'presupuesto' => '💶',
            'no_es_nuestro_cliente' => '👑',
            'fotomaton_360' => '📸',
            'mesa_dulce_gofres' => '🍫',
            default => '🎯',
        };

        $subject = self::getEmailSubjectForKey($event, $variant);

        return [
            'key' => $variant,
            'title' => $title,
            'icon' => $icon,
            'phone' => $clientPhone,
            'email' => $clientEmail,
            'subject' => $subject,
            'text' => $text,
            'email_text' => self::cleanTextForEmail($text),
            'url' => self::buildUrl($clientPhone, $text),
            'web_url' => self::getWebLink($clientPhone, $text),
            'mailto_url' => self::buildMailtoUrl($clientEmail, $subject, $text),
        ];
    }

    public static function hojaRutaDj(Event $event, ?User $user = null): string
    {
        $targetUser = $user ?: $event->dj;
        $userName = $targetUser ? $targetUser->name : 'Compañero';
        $date = $event->event_date ? Carbon::parse($event->event_date)->format('d/m/Y') : 'Por determinar';
        $location = $event->location ?: 'Sin ubicación fija';
        $clientName = $event->client ? $event->client->name : 'Cliente';
        $clientPhone = $event->client ? $event->client->phone : 'Sin teléfono';
        $boothUrl = route('staff.live', $event->token);

        $venueInfo = '';
        if (!empty($event->venue_contact_name) || !empty($event->venue_contact_phone)) {
            $venueInfo .= "\n🏢 *Contacto Lugar/Bodega:* " . ($event->venue_contact_name ?: 'Responsable') . ($event->venue_contact_phone ? " ({$event->venue_contact_phone})" : "");
        }
        if (!empty($event->venue_notes)) {
            $venueInfo .= "\n🔧 *Notas de Acceso/Montaje:* " . $event->venue_notes;
        }

        $scheduleInfo = '';
        if (!empty($event->setup_date) || !empty($event->start_time)) {
            $scheduleInfo .= "\n🚗 *Montaje:* " . $event->setup_schedule_label;
        }
        if (!empty($event->ceremony_time)) {
            $scheduleInfo .= "\n💍 *Ceremonia:* " . substr($event->ceremony_time, 0, 5) . " h";
        }
        if (!empty($event->cocktail_time)) {
            $scheduleInfo .= "\n🍸 *Cóctel:* " . substr($event->cocktail_time, 0, 5) . " h";
        }
        if (!empty($event->banquet_time)) {
            $scheduleInfo .= "\n🍽️ *Banquete:* " . substr($event->banquet_time, 0, 5) . " h";
        }
        if (!empty($event->dance_start_time)) {
            $scheduleInfo .= "\n⏰ *Horario Baile (DJ):* " . $event->dance_schedule_label;
        }
        if (!empty($event->schedule_notes)) {
            $scheduleInfo .= "\n📝 *Timing:* " . $event->schedule_notes;
        }

        return "¡Hola {$userName}! 🎧 Tienes asignado el evento *{$event->name}*:\n\n"
             . "📅 *Fecha:* {$date}\n"
             . "📍 *Ubicación:* {$location}\n"
             . "👤 *Cliente:* {$clientName} ({$clientPhone})"
             . $scheduleInfo
             . $venueInfo . "\n\n"
             . "📱 *Modo Cabina / Escaleta en Vivo:* {$boothUrl}\n\n"
             . "¡A darlo todo en el evento! 🔥";
    }

    public static function questionnaireReminder(Event $event): array
    {
        $clientPhone = $event->client ? $event->client->phone : '';
        $text = self::cuestionario($event);

        return [
            'title' => 'Recordatorio Cuestionario Musical',
            'icon' => '🎵',
            'phone' => $clientPhone,
            'text' => $text,
            'url' => self::buildUrl($clientPhone, $text),
        ];
    }

    public static function contractSignReminder(Event $event, ?Contract $contract = null): array
    {
        $clientPhone = $event->client ? $event->client->phone : '';
        $text = self::contrato($contract ?: $event);

        return [
            'title' => 'Enlace de Firma de Contrato',
            'icon' => '📜',
            'phone' => $clientPhone,
            'text' => $text,
            'url' => self::buildUrl($clientPhone, $text),
        ];
    }

    public static function paymentReminder(Event $event): array
    {
        $clientPhone = $event->client ? $event->client->phone : '';
        $text = self::liquidacion($event);

        return [
            'title' => 'Recordatorio de Pago / Liquidación',
            'icon' => '💶',
            'phone' => $clientPhone,
            'text' => $text,
            'url' => self::buildUrl($clientPhone, $text),
        ];
    }

    public static function staffBriefing(Event $event, User $user, string $role = 'dj'): array
    {
        $roleTitle = $role === 'dj' ? 'DJ Principal' : 'Técnico / Asistente';
        $text = self::hojaRutaDj($event, $user);

        return [
            'title' => "Hoja de Ruta {$roleTitle}",
            'icon' => '📋',
            'phone' => $user->phone,
            'text' => $text,
            'url' => self::buildUrl($user->phone, $text),
        ];
    }

    public static function meetingConfirmation(\App\Models\ClientMeeting $meeting): string
    {
        $event = $meeting->event;
        $clientName = self::cleanClientDisplayName($event && $event->client ? $event->client->name : null, ($event && !$event->is_wedding) ? '' : 'pareja', $event);
        $greeting = $clientName !== '' ? "¡Hola {$clientName}!" : "¡Hola!";
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');

        $dateFormatted = $meeting->meeting_date ? $meeting->meeting_date->translatedFormat('l, d \d\e F \a \l\a\s H:i\h') : 'Por determinar';
        $locText = $meeting->location_type === 'video_call' 
            ? "💻 *Videollamada online*" . ($meeting->video_call_url ? "\nEnlace: " . $meeting->video_call_url : '')
            : ($meeting->location_type === 'phone' ? "📱 *Llamada telefónica*" : "📍 *Lugar:* " . ($meeting->location ?: 'Lugar acordado'));

        return "{$greeting} 👋 Os escribe *{$companyName}*.\n\n"
             . "Os confirmamos nuestra cita para *{$meeting->title}* ({$meeting->meeting_type_label}):\n\n"
             . "📅 *Fecha y hora:* {$dateFormatted}\n"
             . "⏱️ *Duración estimada:* {$meeting->duration_minutes} min\n"
             . "{$locText}\n\n"
             . "¡Nos vemos pronto para avanzar con los preparativos de vuestro evento! 🎉";
    }

    public static function meetingReminder(\App\Models\ClientMeeting $meeting): string
    {
        $event = $meeting->event;
        $clientName = self::cleanClientDisplayName($event && $event->client ? $event->client->name : null, ($event && !$event->is_wedding) ? '' : 'pareja', $event);
        $greeting = $clientName !== '' ? "¡Hola {$clientName}!" : "¡Hola!";
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');

        $dateFormatted = $meeting->meeting_date ? $meeting->meeting_date->translatedFormat('d/m/Y \a \l\a\s H:i\h') : '';
        $locText = $meeting->location_type === 'video_call' 
            ? "💻 *Videollamada online*" . ($meeting->video_call_url ? "\nEnlace: " . $meeting->video_call_url : '')
            : ($meeting->location_type === 'phone' ? "📱 *Llamada telefónica*" : "📍 *Lugar:* " . ($meeting->location ?: 'Lugar acordado'));

        return "{$greeting} 👋 Recordatorio de *{$companyName}*:\n\n"
             . "Os recordamos que tenemos nuestra reunión (*{$meeting->title}*) el próximo *{$dateFormatted}*.\n\n"
             . "{$locText}\n\n"
             . "¡Hasta pronto! 🎧";
    }

    public static function resumenPresupuesto(Event $event, ?\App\Models\Quote $quote = null): string
    {
        $clientName = self::cleanClientDisplayName($event->client ? $event->client->name : null, $event->is_wedding ? 'pareja' : '', $event);
        $greeting = $clientName !== '' ? "¡Hola {$clientName}!" : "¡Hola!";
        $latestQuote = $quote ?: $event->quotes()->latest()->first();
        $quoteAmount = $latestQuote ? (float)$latestQuote->amount : 0;

        if ($latestQuote) {
            $signalAmount = $latestQuote->signal_amount;
            $remainingAmount = $latestQuote->remaining_amount;
            $signalLabel = $latestQuote->signal_label;
        } else {
            if ($event->event_type_clean === 'fiesta') {
                $signalAmount = 0.0;
                $signalLabel = 'Sin señal (0,00 €)';
            } else {
                $depType = Setting::get('deposit_type', 'percentage');
                if ($depType === 'fixed') {
                    $signalAmount = min((float)Setting::get('deposit_fixed_amount', 200), $quoteAmount);
                    $signalLabel = number_format($signalAmount, 2, ',', '.') . ' €';
                } else {
                    $pct = (float)Setting::get('deposit_percentage', 40);
                    $signalAmount = round(($quoteAmount * $pct) / 100, 2);
                    $signalLabel = rtrim(rtrim(number_format($pct, 2, ',', '.'), '0'), ',') . '% (' . number_format($signalAmount, 2, ',', '.') . ' €)';
                }
            }
            $remainingAmount = max(0, $quoteAmount - $signalAmount);
        }

        $quoteSummaryLines = [];
        if ($latestQuote && $latestQuote->items) {
            foreach ($latestQuote->items as $item) {
                $line = "• *" . $item->service_name . "*" . ($item->quantity > 1 ? " ({$item->quantity}x)" : "") . ": " . number_format($item->price * $item->quantity, 2, ',', '.') . " €";
                if (!empty(trim((string)$item->description))) {
                    $line .= "\n  _" . trim((string)$item->description) . "_";
                }
                $quoteSummaryLines[] = $line;
            }
        }
        $quoteItemsText = implode("\n", $quoteSummaryLines);
        $adminNotesText = ($latestQuote && !empty(trim((string)$latestQuote->admin_notes)))
            ? "\n📝 *NOTAS Y CONDICIONES INCLUIDAS:*\n" . trim((string)$latestQuote->admin_notes) . "\n"
            : "";
        $quoteValidityNotice = "";
        if ($latestQuote && $latestQuote->expires_at) {
            $quoteValidityNotice = "\n\n⏳ *VALIDEZ DE LA OFERTA:*\nPropuesta válida hasta el *" . $latestQuote->expires_at->format('d/m/Y') . "* (" . $latestQuote->validity_days . " días naturales). Pasada esta fecha, tarifas y disponibilidad quedan sujetas a revisión.";
        }

        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->format('d/m/Y') : '';
        $loc = $event->location ? " en " . $event->location : "";

        $paymentConditionsText = $signalAmount > 0
            ? "• Señal de reserva ({$signalLabel}): " . number_format($signalAmount, 2, ',', '.') . " €\n• Restante el día del evento: " . number_format($remainingAmount, 2, ',', '.') . " €"
            : "• Pago íntegro el día del evento: " . number_format($quoteAmount, 2, ',', '.') . " € (Sin señal previa de reserva)";

        $onlineUrl = $latestQuote ? $latestQuote->public_url : ($event->token ? route('guest.quote.view', $event->token) : '');
        $onlineLinkText = $onlineUrl ? "\n\n👉 *Ver propuesta online, descargar Dossier en PDF y Aceptar / Rechazar:*\n{$onlineUrl}" : "";

        return "{$greeting} 👋\n\nOs compartimos la propuesta detallada para vuestro evento *{$event->name}*" . ($eventDate ? " ({$eventDate}{$loc})" : "") . ":\n\n"
             . ($quoteItemsText ?: '• Servicios musicales y sonorización profesional.') . "\n"
             . $adminNotesText . "\n"
             . "💰 *TOTAL PRESUPUESTO:* " . number_format($quoteAmount, 2, ',', '.') . " €\n\n"
             . "📌 *CONDICIONES DE PAGO:*\n"
             . $paymentConditionsText
             . $quoteValidityNotice
             . $onlineLinkText . "\n\n"
             . "Cualquier duda o ajuste, estamos a vuestra entera disposición. ¡Un saludo!";
    }

    public static function senalReserva(Event $event, ?\App\Models\Quote $quote = null): string
    {
        $clientName = self::cleanClientDisplayName($event->client ? $event->client->name : null, $event->is_wedding ? 'pareja' : '', $event);
        $greeting = $clientName !== '' ? "¡Hola {$clientName}!" : "¡Hola!";
        $latestQuote = $quote ?: $event->quotes()->latest()->first();
        $quoteAmount = $latestQuote ? (float)$latestQuote->amount : 0;

        if ($latestQuote) {
            $signalAmount = $latestQuote->signal_amount;
        } else {
            $depType = Setting::get('deposit_type', 'percentage');
            if ($depType === 'fixed') {
                $signalAmount = min((float)Setting::get('deposit_fixed_amount', 200), $quoteAmount);
            } else {
                $pct = (float)Setting::get('deposit_percentage', 40);
                $signalAmount = round(($quoteAmount * $pct) / 100, 2);
            }
        }

        $iban = Setting::get('company_iban', '');
        $bizum = Setting::get('company_bizum', '');
        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->format('d/m/Y') : '';

        return "{$greeting} 👋\n\nPara confirmar la fecha de vuestro evento *{$event->name}*" . ($eventDate ? " ({$eventDate})" : "") . ", podéis abonar la *Señal de reserva (" . number_format($signalAmount, 2, ',', '.') . " €)* mediante:\n\n"
             . ($iban ? "🏦 *Transferencia Bancaria:*\n" . $iban . "\n\n" : "")
             . ($bizum ? "📱 *Bizum:* " . $bizum . "\n\n" : "")
             . "Concepto: " . $event->name . "\n\n"
             . "¡Muchas gracias y quedamos a vuestra disposición!";
    }

    public static function facturaRecibo(Event $event, \App\Models\Invoice $invoice): string
    {
        $clientName = self::cleanClientDisplayName($event->client ? $event->client->name : null, $event->is_wedding ? 'pareja' : '', $event);
        $greeting = $clientName !== '' ? "¡Hola {$clientName}!" : "¡Hola!";
        $brand = Setting::getBrandInfo($event->brand_clean);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $docTypeLabel = $invoice->type === 'recibo' ? 'Recibo / Justificante de Pago' : 'Factura Oficial';
        $issueDate = $invoice->issue_date ? Carbon::parse($invoice->issue_date)->format('d/m/Y') : now()->format('d/m/Y');
        $statusLabel = $invoice->status === 'paid' ? '✅ Cobrado / Pagado' : '⏳ Pendiente de pago';

        return "{$greeting} 👋 Os escribe *{$companyName}*.\n\n"
             . "Os compartimos los datos de vuestro documento *{$docTypeLabel} ({$invoice->invoice_number})* correspondiente al evento *{$event->name}*:\n\n"
             . "📄 *Documento:* {$docTypeLabel} Nº {$invoice->invoice_number}\n"
             . "📅 *Fecha de emisión:* {$issueDate}\n"
             . "💰 *Importe total:* " . number_format((float)$invoice->total, 2, ',', '.') . " €\n"
             . "📌 *Estado:* {$statusLabel}\n"
             . ($invoice->notes ? "📝 *Concepto / Notas:* {$invoice->notes}\n" : "")
             . "\nCualquier consulta estamos a vuestra entera disposición. ¡Muchas gracias!";
    }

    public static function resolveCouplesFirstNames(?Event $event): string
    {
        if (!$event) {
            return 'pareja';
        }
        $defaultFallback = $event->is_wedding ? 'pareja' : '';
        $c1 = $event->client && !empty($event->client->name) ? self::cleanClientDisplayName($event->client->name, '', $event) : '';
        $c2 = !empty($event->partner_name) ? self::cleanClientDisplayName($event->partner_name, '', $event) : '';
        if ($c1 && $c2 && stripos($c1, $c2) === false) {
            return "{$c1} y {$c2}";
        }
        return $c1 ?: ($c2 ?: $defaultFallback);
    }

    public static function infoFotomatonY360(?Event $event = null, ?string $provider = null): string
    {
        $clientName = self::resolveCouplesFirstNames($event);
        $greeting = $clientName !== '' ? "¡Hola {$clientName}!" : "¡Hola!";
        if (empty($provider) && $event) {
            $loc = mb_strtolower((string)($event->location ?? ''));
            $provider = (str_contains($loc, 'vitoria') || str_contains($loc, 'alava') || str_contains($loc, 'álava') || str_contains($loc, 'gasteiz') || str_contains($loc, 'araba')) ? 'vitoria' : 'logrono';
        }
        $pb = Setting::getPhotobooth360Config($provider ?: 'logrono');
        $pb2 = number_format($pb['photobooth_2h'], 0, ',', '.');
        $pb3 = number_format($pb['photobooth_3h'], 0, ',', '.');
        $v2 = number_format($pb['video_360_2h'], 0, ',', '.');
        $v3 = number_format($pb['video_360_3h'], 0, ',', '.');
        $duo2 = number_format($pb['pack_duo_2h'], 0, ',', '.');
        $duo3 = number_format($pb['pack_duo_3h'], 0, ',', '.');
        $dep = number_format($pb['deposit_per_service'], 0, ',', '.');
        $staffLabel = ($provider === 'vitoria') ? 'FotomatonCheese' : 'nuestro equipo';

        return "{$greeting} 👋 Os pasamos toda la información y tarifas de los servicios de *Fotomatón* y *Plataforma 360º*:\n\n"
             . "📸 *FOTOMATÓN!*\n"
             . "Nuestras máquinas llevan cámara réflex profesional Canon 1200, con impresora de alta calidad que imprime en 10 segundos, y ordenador táctil con efecto espejo, donde te ves continuamente.\n\n"
             . "Este servicio incluye:\n"
             . "- 2-3 horas de fotos ilimitadas.\n"
             . "- 2 copias de cada foto: una para invitados y otra para libro de firmas.\n"
             . "- Marco personalizado con nombre, fecha y temática que os guste.\n"
             . "- ¡Libro de firmas personalizado con foto, hecho por una imprenta!\n"
             . "- Formato de imagen en tamaño 10x15 o tiras (5x15).\n"
             . "- ¡Divertido atrezzo, gafotas, boas, gorros, etc.!\n"
             . "- Photocall / fondo estándar a elegir entre los que tenemos (unos 8 diferentes).\n"
             . "- ¡Siempre habrá una persona de {$staffLabel} animando y ayudando!\n"
             . "- Necesitaríamos un enchufe cerca y un espacio de 2m x 2m.\n"
             . "- USB con todas las fotos.\n"
             . "- Subimos unas cuantas fotos a redes sociales.\n\n"
             . "💰 *Precio Fotomatón:*\n"
             . "• 2 horas: *{$pb2} €*\n"
             . "• 3 horas: *{$pb3} €*\n\n"
             . "────────────────────\n\n"
             . "🎥 *PLATAFORMA 360!*\n"
             . "Se trata de una plataforma de 100cm, en la que se suben los invitados haciendo un círculo mirando hacia fuera, y nuestra cámara da vueltas alrededor de ellos grabando sus mejores caras, bailes, poses... ¡¡La diversión está asegurada!!\n\n"
             . "Este servicio incluye:\n"
             . "- 2 o 3 horas de vídeos ilimitados.\n"
             . "- Plataforma de 100 cm, en la cual entran 4 o 5 personas por vídeo.\n"
             . "- Cámara GoPro (buena calidad).\n"
             . "- Vídeos 360 grabados con slow motion y boomerang.\n"
             . "- Duración de cada vídeo: 20 seg aprox.\n"
             . "- Personalización de marco, con nombre de anfitriones, empresa o lo que os guste.\n"
             . "- Elección de canción que os guste y nos enviéis.\n"
             . "- Atrezzo divertido: gafotas, gorros, boas, etc.\n"
             . "- Una bolsa de confeti (lo usaremos si el lugar de celebración nos da permiso).\n"
             . "- 1 o 2 Animadores de {$staffLabel}.\n"
             . "- Montaje de 45 min aprox., necesitaríamos un enchufe cerca y un espacio de 2m x 2m.\n"
             . "- Transporte incluido hasta 50 km (consulta km extra).\n\n"
             . "💰 *Precio Plataforma 360º:*\n"
             . "• 2 horas por *{$v2} €*\n"
             . "• 3 horas por *{$v3} €*\n\n"
             . "────────────────────\n\n"
             . "🔹 *Tenemos un PACK DÚO de 3 horas de 🎥 Plataforma 360 + 3 horas de 📸 Fotomatón fotos impresas por: {$duo3} €* (opción de *2 horas por {$duo2} €*) 🔹 ¡¡CON TODO INCLUIDO!!\n\n"
             . "📆 Para reservar el día serían *{$dep} € por servicio contratado*, mediante cuenta bancaria y el resto del pago se realizaría la semana de antes.";
    }

    public static function infoMesaDulceYGofres(?Event $event = null, ?string $provider = null): string
    {
        $clientName = self::resolveCouplesFirstNames($event);
        $greeting = $clientName !== '' ? "¡Hola {$clientName}!" : "¡Hola!";
        $pb = Setting::getPhotobooth360Config($provider ?: 'vitoria');

        $f1_2h = number_format($pb['chocolate_fountain_1_2h'] ?? 420, 0, ',', '.');
        $f1_3h = number_format($pb['chocolate_fountain_1_3h'] ?? 520, 0, ',', '.');
        $f2_2h = number_format($pb['chocolate_fountain_2_2h'] ?? 490, 0, ',', '.');
        $f2_3h = number_format($pb['chocolate_fountain_2_3h'] ?? 590, 0, ',', '.');
        $waffleExtra = number_format($pb['chocolate_fountain_waffle_extra'] ?? 2.90, 2, ',', '.');
        $candyPrice = number_format($pb['candy_bar_price'] ?? 420, 0, ',', '.');
        $waffle2h = number_format($pb['waffle_cart_2h'] ?? 420, 0, ',', '.');
        $waffle3h = number_format($pb['waffle_cart_3h'] ?? 550, 0, ',', '.');
        $dep = number_format($pb['deposit_per_service'] ?? 100, 0, ',', '.');

        return "{$greeting} 👋 Os pasamos toda la información y tarifas de nuestras *Mesas Dulces, Fuente de Chocolate y Carrito de Gofres*:\n\n"
             . "🍫 *MESA DULCE CON FUENTE DE CHOCOLATE*\n"
             . "- Eligen el chocolate que más les gusta: blanco, negro o con leche.\n"
             . "- Donut de azúcar, gofres cortados y profiteroles de nata.\n"
             . "- Fruta 🍓🍊🍍🫐 de temporada variada, cortada a tamaño de bocado.\n"
             . "- Galletas Chips Ahoy, palmeritas, filipinos blanco y negro.\n"
             . "- Chocolatinas individuales cerradas: barritas Kinder, mini KitKat, Huesitos.\n"
             . "- Chucherías variadas.\n"
             . "- Flores de lenguas verdes y rosa, envueltas individuales.\n"
             . "- Arco de globos y decoración que os guste.\n"
             . "- Palillos para la fuente, servilletas y bolsa de basura.\n\n"
             . "⏱️ Se monta la mesa 1 o 2 horas antes del servicio y a la mitad del tiempo contratado se vuelve a rellenar.\n\n"
             . "💰 *Precio del servicio:*\n"
             . "• *1 Fuente de chocolate:*\n"
             . "   - 2h: *{$f1_2h} €*\n"
             . "   - 3h: *{$f1_3h} €*\n"
             . "• *2 Fuentes de chocolate:*\n"
             . "   - 2h: *{$f2_2h} €*\n"
             . "   - 3h: *{$f2_3h} €*\n"
             . "*(Si quieren añadir un rincón con gofres, incluiría 3 biberones de siropes —ej. dulce de leche, Nutella, chocolate blanco— + nata spray: Precio {$waffleExtra} € por unidad)*.\n\n"
             . "────────────────────\n\n"
             . "🍬 *MESA DE SOLO CHUCHES*\n"
             . "Incluye:\n"
             . "- Decoración a elegir.\n"
             . "- Gran variado de chuches (eligiendo las favoritas de los anfitriones).\n"
             . "- Globos decorativos.\n"
             . "- Se montaría un par de horas antes y se recogería a la noche o al día siguiente (en caso de sobrar material de cristal...).\n"
             . "💰 *PRECIO:* *{$candyPrice} €*\n\n"
             . "────────────────────\n\n"
             . "🧇 *CARRITO DE GOFRES*\n"
             . "- Gofres hechos al momento.\n"
             . "- 4 siropes a elegir (ej.: Kinder, Lotus, Nutella, choco blanco, choco leche, pistacho…).\n"
             . "- 4 ingredientes secos (galleta Lotus molida, Oreo, mini Lacasitos, mini nubes…).\n"
             . "- Decoración del carrito con globos y banderín con nombre.\n"
             . "- Servilletas, bandejas y cubiertos.\n\n"
             . "💰 *PRECIO:*\n"
             . "• 2 horas (unos 60 gofres): *{$waffle2h} €*\n"
             . "• 3 horas (unos 100 gofres): *{$waffle3h} €*\n\n"
             . "────────────────────\n\n"
             . "📆 Para reservar el día habría que hacer un pago adelantado de *{$dep} € por servicio*, mediante cuenta bancaria o Bizum.\n\n"
             . "Cualquier duda estaremos encantados de contestarles. ¡Un saludo!";
    }
}

