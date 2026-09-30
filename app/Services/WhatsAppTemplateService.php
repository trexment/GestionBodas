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
        if (!empty($event->dance_start_time)) {
            $scheduleInfo .= "\n⏰ *Horario Baile (DJ):* " . $event->dance_schedule_label;
        }
        if (!empty($event->start_time)) {
            $scheduleInfo .= "\n🚗 *Hora Llegada / Montaje:* " . substr($event->start_time, 0, 5) . " h";
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
}
