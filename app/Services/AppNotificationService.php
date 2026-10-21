<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppNotificationService
{
    public const SETTING_KEY = 'app_admin_notifications';

    /**
     * Envía un Mailable a todos los correos de administradores de forma individual (tolerante a fallos:
     * si un buzón concreto rechaza el correo, el resto de administradores lo sigue recibiendo).
     */
    public static function sendMailableToAdmins($mailableOrFactory, ?string $brandKey = null, ?string $extraRecipient = null): int
    {
        $recipients = Setting::getAdminNotificationEmails($brandKey);
        if (!empty($extraRecipient)) {
            $cleanExtra = trim(mb_strtolower($extraRecipient));
            if (filter_var($cleanExtra, FILTER_VALIDATE_EMAIL) && !str_ends_with($cleanExtra, '.local') && !in_array($cleanExtra, $recipients, true)) {
                $recipients[] = $cleanExtra;
            }
        }

        $sentCount = 0;
        foreach ($recipients as $recipient) {
            try {
                $instance = is_callable($mailableOrFactory) ? $mailableOrFactory() : clone $mailableOrFactory;
                Mail::to($recipient)->send($instance);
                $sentCount++;
            } catch (\Throwable $e) {
                Log::warning("Error enviando email admin a {$recipient}: " . $e->getMessage());
            }
        }

        return $sentCount;
    }

    /**
     * Envía un correo HTML estructurado de alerta a todos los administradores (y opcionalmente al DJ asignado).
     * Envía individualmente a cada destinatario para máxima fiabilidad.
     */
    public static function sendAlertEmailToAdmins(
        string $subject,
        string $heading,
        array $details,
        ?string $actionUrl = null,
        string $actionLabel = 'Abrir Ficha del Evento',
        ?string $brandKey = null,
        string $accentColor = '#4f46e5',
        ?string $extraRecipient = null
    ): int {
        $recipients = Setting::getAdminNotificationEmails($brandKey);
        if (!empty($extraRecipient)) {
            $cleanExtra = trim(mb_strtolower($extraRecipient));
            if (filter_var($cleanExtra, FILTER_VALIDATE_EMAIL) && !str_ends_with($cleanExtra, '.local') && !in_array($cleanExtra, $recipients, true)) {
                $recipients[] = $cleanExtra;
            }
        }

        if (empty($recipients)) {
            return 0;
        }

        $brandInfo = Setting::getBrandInfo($brandKey);
        $companyName = $brandInfo['name'] ?? Setting::get('company_name', 'Núñez & Son');

        $rowsHtml = '';
        foreach ($details as $label => $value) {
            if ($value === null || trim((string) $value) === '') {
                continue;
            }
            $rowsHtml .= '<tr>'
                . '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#475569;width:36%;vertical-align:top;font-size:13px;">' . e((string) $label) . '</td>'
                . '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;color:#0f172a;font-size:14px;line-height:1.5;white-space:pre-line;">' . nl2br(e((string) $value)) . '</td>'
                . '</tr>';
        }

        $buttonHtml = '';
        if (!empty($actionUrl)) {
            $buttonHtml = '<div style="margin-top:24px;text-align:center;">'
                . '<a href="' . e($actionUrl) . '" target="_blank" style="display:inline-block;background:' . e($accentColor) . ';color:#ffffff;font-weight:700;font-size:14px;padding:12px 24px;border-radius:10px;text-decoration:none;">'
                . e($actionLabel) . ' &rarr;'
                . '</a>'
                . '</div>';
        }

        $html = '<div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;background:#f8fafc;padding:24px;">'
            . '<div style="max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(15,23,42,0.04);">'
            . '<div style="background:' . e($accentColor) . ';color:#ffffff;padding:18px 24px;">'
            . '<div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;opacity:0.9;font-weight:700;">Notificación de Administración &bull; ' . e($companyName) . '</div>'
            . '<h2 style="margin:6px 0 0 0;font-size:19px;font-weight:800;">' . e($heading) . '</h2>'
            . '</div>'
            . '<div style="padding:22px 24px;">'
            . '<table style="width:100%;border-collapse:collapse;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">'
            . $rowsHtml
            . '</table>'
            . $buttonHtml
            . '<p style="margin:20px 0 0 0;font-size:11px;color:#94a3b8;text-align:center;">Aviso automático generado el ' . now()->format('d/m/Y \a \l\a\s H:i') . ' &bull; ' . e($companyName) . '</p>'
            . '</div>'
            . '</div>'
            . '</div>';

        $sentCount = 0;
        foreach ($recipients as $recipient) {
            try {
                Mail::html($html, function ($message) use ($recipient, $subject) {
                    $message->to($recipient)->subject($subject);
                });
                $sentCount++;
            } catch (\Throwable $e) {
                Log::warning("Error enviando alerta email admin a {$recipient}: " . $e->getMessage());
            }
        }

        return $sentCount;
    }

    /**
     * Registra una nueva notificación en la App para todos los administradores / DJs
     *
     * @param string $type 'whatsapp_inbound' | 'email_lead' | 'quote_request' | 'system'
     * @param string $title Título corto de la alerta
     * @param string $body Cuerpo descriptivo de la alerta
     * @param string|null $url Enlace directo al hacer clic
     * @param int|null $eventId ID del evento relacionado
     * @param string|null $brand Marca asociada
     * @return array La notificación creada
     */
    public static function push(
        string $type,
        string $title,
        string $body,
        ?string $url = null,
        ?int $eventId = null,
        ?string $brand = null
    ): array {
        $list = self::getRawList();

        $notification = [
            'id' => uniqid('notif_', true),
            'timestamp' => now()->timestamp,
            'created_at' => now()->format('d/m/Y H:i'),
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url ?: ($eventId ? url("/admin/events/{$eventId}") : url('/admin/events')),
            'event_id' => $eventId,
            'brand' => $brand,
            'read_by' => [],
        ];

        array_unshift($list, $notification);

        // Conservar las últimas 50 notificaciones
        if (count($list) > 50) {
            $list = array_slice($list, 0, 50);
        }

        Setting::set(self::SETTING_KEY, json_encode($list, JSON_UNESCAPED_UNICODE));

        return $notification;
    }

    /**
     * Obtiene las notificaciones formateadas para un usuario específico
     */
    public static function getForUser(int $userId, int $limit = 25): array
    {
        $list = self::getRawList();
        $unreadCount = 0;
        $formatted = [];

        foreach ($list as $item) {
            $readBy = isset($item['read_by']) && is_array($item['read_by']) ? $item['read_by'] : [];
            $isUnread = !in_array($userId, $readBy);
            if ($isUnread) {
                $unreadCount++;
            }

            if (count($formatted) < $limit) {
                $item['unread'] = $isUnread;
                $formatted[] = $item;
            }
        }

        return [
            'unread_count' => $unreadCount,
            'notifications' => $formatted,
            'latest_timestamp' => !empty($list) ? ($list[0]['timestamp'] ?? 0) : 0,
        ];
    }

    /**
     * Marca todas las notificaciones como leídas para un usuario
     */
    public static function markAllRead(int $userId): void
    {
        $list = self::getRawList();
        $changed = false;

        foreach ($list as &$item) {
            $readBy = isset($item['read_by']) && is_array($item['read_by']) ? $item['read_by'] : [];
            if (!in_array($userId, $readBy)) {
                $readBy[] = $userId;
                $item['read_by'] = array_values(array_unique($readBy));
                $changed = true;
            }
        }
        unset($item);

        if ($changed) {
            Setting::set(self::SETTING_KEY, json_encode($list, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Marca una notificación específica como leída para un usuario
     */
    public static function markRead(string $notifId, int $userId): void
    {
        $list = self::getRawList();
        $changed = false;

        foreach ($list as &$item) {
            if (($item['id'] ?? '') === $notifId) {
                $readBy = isset($item['read_by']) && is_array($item['read_by']) ? $item['read_by'] : [];
                if (!in_array($userId, $readBy)) {
                    $readBy[] = $userId;
                    $item['read_by'] = array_values(array_unique($readBy));
                    $changed = true;
                }
                break;
            }
        }
        unset($item);

        if ($changed) {
            Setting::set(self::SETTING_KEY, json_encode($list, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Vacía todas las notificaciones almacenadas
     */
    public static function clearAll(): void
    {
        Setting::set(self::SETTING_KEY, '[]');
    }

    /**
     * Elimina todas las notificaciones asociadas a un evento concreto (por ejemplo al borrar el evento)
     */
    public static function removeByEventId(int $eventId): void
    {
        $raw = Setting::get(self::SETTING_KEY, '[]');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($decoded)) {
            return;
        }

        $filtered = array_values(array_filter($decoded, function ($item) use ($eventId) {
            return (int)($item['event_id'] ?? 0) !== $eventId;
        }));

        if (count($filtered) !== count($decoded)) {
            Setting::set(self::SETTING_KEY, json_encode($filtered, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Devuelve el array bruto de notificaciones almacenadas, purgando cualquier
     * aviso de WhatsApp personal o de eventos que hayan sido eliminados.
     */
    protected static function getRawList(): array
    {
        // Purgar mensajes de WhatsApp personales huérfanos (sin evento asociado) si existieran en BD
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('whatsapp_messages')) {
                \App\Models\WhatsAppMessage::whereNull('event_id')->delete();
            }
        } catch (\Throwable $e) {}

        $raw = Setting::get(self::SETTING_KEY, '[]');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($decoded) || empty($decoded)) {
            return [];
        }

        // Comprobar qué event_ids siguen existiendo en la base de datos
        $existingEventIds = [];
        try {
            $eventIds = array_values(array_unique(array_filter(array_map(function ($item) {
                $eid = (int)($item['event_id'] ?? 0);
                if ($eid > 0) {
                    return $eid;
                }
                if (!empty($item['url']) && preg_match('~/admin/events/(\d+)\b~', (string)$item['url'], $m)) {
                    return (int)$m[1];
                }
                return 0;
            }, $decoded))));

            if (!empty($eventIds)) {
                $existingEventIds = \App\Models\Event::whereIn('id', $eventIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int)$id)
                    ->all();
            }
        } catch (\Throwable $e) {
            $existingEventIds = null; // En caso de error de BD no purgar por event_id
        }

        $filtered = [];
        $listChanged = false;

        foreach ($decoded as $item) {
            // 1. Si es una notificación de WhatsApp sin event_id o con el formato antiguo previo al filtro de privacidad -> descartar
            if (
                ($item['type'] ?? '') === 'whatsapp_inbound' &&
                (empty($item['event_id']) || str_starts_with($item['title'] ?? '', '💬 WhatsApp de '))
            ) {
                $listChanged = true;
                continue;
            }

            // 2. Si apunta a un evento que ya fue eliminado -> descartar para evitar errores 404
            if (is_array($existingEventIds)) {
                $itemEventId = (int)($item['event_id'] ?? 0);
                if ($itemEventId === 0 && !empty($item['url']) && preg_match('~/admin/events/(\d+)\b~', (string)$item['url'], $m)) {
                    $itemEventId = (int)$m[1];
                }
                if ($itemEventId > 0 && !in_array($itemEventId, $existingEventIds, true)) {
                    $listChanged = true;
                    continue;
                }
            }

            $filtered[] = $item;
        }

        if ($listChanged) {
            Setting::set(self::SETTING_KEY, json_encode($filtered, JSON_UNESCAPED_UNICODE));
        }

        return $filtered;
    }
}
