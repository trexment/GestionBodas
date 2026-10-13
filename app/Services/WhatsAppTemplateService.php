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
        if (strlen($clean) === 9 && in_array(substr($clean, 0, 1), ['6', '7'])) {
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

    public static function cuestionario(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $companyName = Setting::get('company_name', 'Eventos Musicales');
        $formUrl = route('guest.form', $event->token);

        return "¡Hola {$clientName}! 👋 Os escribe el equipo de *{$companyName}*.\n\n"
             . "Se acerca vuestro gran día (*{$event->name}*) y estamos preparando la música y momentos especiales. 🎵💍\n\n"
             . "Por favor, cuando tengáis un momento, completad vuestras canciones y preferencias desde este enlace:\n"
             . "👉 {$formUrl}\n\n"
             . "Podréis elegir las canciones de la entrada, tarta, baile, regalos y vuestros temazos favoritos para la fiesta.\n\n"
             . "¡Cualquier duda nos decís! 🎧";
    }

    public static function contrato($contractOrEvent): string
    {
        $companyName = Setting::get('company_name', 'Eventos Musicales');
        if ($contractOrEvent instanceof Contract) {
            $event = $contractOrEvent->event;
            $token = $contractOrEvent->token ?: ($event ? $event->token : '');
            $eventName = $event ? $event->name : 'vuestro evento';
            $clientName = ($event && $event->client) ? $event->client->name : 'pareja';
        } else {
            $event = $contractOrEvent;
            $token = $event->token;
            $eventName = $event->name;
            $clientName = $event->client ? $event->client->name : 'pareja';
        }

        $signUrl = route('guest.contract', $token);

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "Os compartimos el enlace para revisar y firmar online vuestro contrato oficial de servicios para *{$eventName}*:\n\n"
             . "✍️ {$signUrl}\n\n"
             . "Podéis confirmar vuestros datos y estampar la firma digital directamente desde la pantalla de vuestro móvil en 30 segundos.\n\n"
             . "¡Un saludo!";
    }

    public static function liquidacion(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $quote = $event->quotes()->latest()->first();
        $amount = $quote ? number_format($quote->amount, 2, ',', '.') . ' €' : 'el importe acordado';

        return "¡Hola {$clientName}! 👋 Esperamos que los preparativos para *{$event->name}* vayan de maravilla.\n\n"
             . "Os recordamos que, según las condiciones acordadas, la liquidación final del servicio ({$amount}) debe realizarse antes de la fecha del evento.\n\n"
             . "Si ya habéis realizado la transferencia, ignorad este mensaje. Si necesitáis el número de cuenta o justificante, avisadnos.\n\n"
             . "¡Estamos deseando que llegue el gran día! 🎧✨";
    }

    /**
     * Técnica del NO (Chris Voss - Never Split the Difference).
     * Pregunta orientada al "NO" para reactivar clientes sin ser insistente ni desesperado.
     * Decir "NO" aporta seguridad y control al cliente, provocando una respuesta inmediata.
     */
    public static function seguimientoPreguntaNo(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : 'vuestro gran día';
        $eventName = $event->name ?: 'vuestro evento';

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "Os hago una consulta muy rápida y directa: *¿habéis decidido descartar la opción de contar con nosotros para {$eventName} ({$eventDate})?*\n\n"
             . "Os pregunto con total confianza porque nos ha entrado otra consulta para vuestra misma fecha y, por respeto y deferencia a vosotros que contactasteis primero, no queríamos disponer del día sin consultaros antes.\n\n"
             . "Si habéis elegido otra opción o preferís no seguir adelante, no os preocupéis en absoluto: respondedme simplemente *\"la descartamos\"* y así liberamos la fecha para la otra pareja con total tranquilidad.\n\n"
             . "Y si por el contrario seguís con la idea de contar con nosotros o queréis que adaptemos algún detalle del presupuesto a vuestra medida, decidnos y lo dejamos reservado hoy mismo.\n\n"
             . "¡Un abrazo y que tengáis muy buen día! 🎧✨";
    }

    /**
     * Psicología Inversa & Cierre de Expediente (Técnica "Breakup Message").
     * Asume amablemente que han elegido otra alternativa para eliminar la presión
     * y activar el miedo a perder la fecha (FOMO / aversión a la pérdida).
     */
    public static function seguimientoPsicologiaInversa(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : 'la fecha prevista';
        $eventName = $event->name ?: 'vuestro evento';

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "Imagino que estáis a tope de gestiones con los preparativos de *{$eventName}*.\n\n"
             . "Como no hemos tenido noticias vuestras tras enviaros la propuesta, doy por hecho que habéis decidido elegir otra opción o aplazar la música para más adelante (lo cual entendemos perfectamente).\n\n"
             . "Para no ser pesados ni saturaros con más mensajes, cerramos vuestro expediente por nuestra parte para no molestaros más.\n\n"
             . "Solo os escribía para despedirme y desearos lo mejor en vuestro evento. Si por algún motivo cambiáis de opinión o aún queréis contar con nosotros antes de que la fecha ({$eventDate}) se ocupe definitivamente, sabéis dónde encontrarnos.\n\n"
             . "¡Mucho éxito con todo y un abrazo grande! 🎉";
    }

    /**
     * Reactivación por Objeción de Presupuesto con Técnica del NO.
     * Pregunta si el motivo de descarte es económico para ofrecer adaptar el montaje o pack.
     */
    public static function seguimientoPresupuestoNo(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : 'vuestra fecha';
        $eventName = $event->name ?: 'vuestro evento';

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "*¿Habéis decidido descartar nuestra propuesta para {$eventName} principalmente por un tema de presupuesto?*\n\n"
             . "Si es por eso, no os preocupéis: tenemos alternativas más flexibles o podemos adaptar el equipamiento para encajar dentro de lo que teníais pensado invertir, sin renunciar a la calidad del sonido ni de la fiesta.\n\n"
             . "Si por el contrario ya tenéis otra opción contratada o preferís dejarlo, avisadnos con toda confianza para poder liberar vuestra fecha ({$eventDate}) en nuestra agenda.\n\n"
             . "¡Quedamos a vuestra disposición! 🎧";
    }

    public static function followupReminder(Event $event, string $variant = 'pregunta_no'): array
    {
        $clientPhone = $event->client ? $event->client->phone : '';
        $text = match($variant) {
            'psicologia_inversa' => self::seguimientoPsicologiaInversa($event),
            'presupuesto' => self::seguimientoPresupuestoNo($event),
            default => self::seguimientoPreguntaNo($event),
        };

        $title = match($variant) {
            'psicologia_inversa' => 'Psicología Inversa / Cierre de Expediente',
            'presupuesto' => 'Reactivación por Presupuesto (Técnica del NO)',
            default => 'Técnica del NO / ¿Descartáis la fecha?',
        };

        return [
            'title' => $title,
            'icon' => '🔥',
            'phone' => $clientPhone,
            'text' => $text,
            'url' => self::buildUrl($clientPhone, $text),
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
        $clientName = $event && $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');

        $dateFormatted = $meeting->meeting_date ? $meeting->meeting_date->translatedFormat('l, d \d\e F \a \l\a\s H:i\h') : 'Por determinar';
        $locText = $meeting->location_type === 'video_call' 
            ? "💻 *Videollamada online*" . ($meeting->video_call_url ? "\nEnlace: " . $meeting->video_call_url : '')
            : ($meeting->location_type === 'phone' ? "📱 *Llamada telefónica*" : "📍 *Lugar:* " . ($meeting->location ?: 'Lugar acordado'));

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "Os confirmamos nuestra cita para *{$meeting->title}* ({$meeting->meeting_type_label}):\n\n"
             . "📅 *Fecha y hora:* {$dateFormatted}\n"
             . "⏱️ *Duración estimada:* {$meeting->duration_minutes} min\n"
             . "{$locText}\n\n"
             . "¡Nos vemos pronto para avanzar con los preparativos de vuestro evento! 🎉";
    }

    public static function meetingReminder(\App\Models\ClientMeeting $meeting): string
    {
        $event = $meeting->event;
        $clientName = $event && $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');

        $dateFormatted = $meeting->meeting_date ? $meeting->meeting_date->translatedFormat('d/m/Y \a \l\a\s H:i\h') : '';
        $locText = $meeting->location_type === 'video_call' 
            ? "💻 *Videollamada online*" . ($meeting->video_call_url ? "\nEnlace: " . $meeting->video_call_url : '')
            : ($meeting->location_type === 'phone' ? "📱 *Llamada telefónica*" : "📍 *Lugar:* " . ($meeting->location ?: 'Lugar acordado'));

        return "¡Hola {$clientName}! 👋 Recordatorio de *{$companyName}*:\n\n"
             . "Os recordamos que tenemos nuestra reunión (*{$meeting->title}*) el próximo *{$dateFormatted}*.\n\n"
             . "{$locText}\n\n"
             . "¡Hasta pronto! 🎧";
    }
}
