<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class FotosEventosQrService
{
    public const DEFAULT_BASE_URL = 'https://fotos.appinformaticas.com';

    public static function getBaseUrl(): string
    {
        $url = trim((string) Setting::get('fotos_qr_base_url', self::DEFAULT_BASE_URL));
        if ($url === '') {
            $url = self::DEFAULT_BASE_URL;
        }
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }
        return rtrim($url, '/');
    }

    public static function getApiKey(): string
    {
        return trim((string) Setting::get('fotos_qr_api_key', ''));
    }

    public static function isConfigured(): bool
    {
        return self::getApiKey() !== '';
    }

    public static function endpointUrl(?string $baseUrl = null): string
    {
        $base = $baseUrl !== null && trim($baseUrl) !== ''
            ? rtrim(trim($baseUrl), '/')
            : self::getBaseUrl();

        if (!str_starts_with($base, 'http://') && !str_starts_with($base, 'https://')) {
            $base = 'https://' . $base;
        }

        return $base . '/api/integration/events.php';
    }

    /**
     * Verifica la conexión HTTP y autenticación con FotosEventosQR.
     */
    public static function testConnection(?string $baseUrl = null, ?string $apiKey = null): array
    {
        $key = $apiKey !== null ? trim($apiKey) : self::getApiKey();
        if ($key === '') {
            return [
                'success' => false,
                'message' => 'Introduce primero la API Key generada en FotosEventosQR → Ajustes.',
            ];
        }

        $endpoint = self::endpointUrl($baseUrl);

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $key,
                'Accept' => 'application/json',
            ])->timeout(15)->post($endpoint, [
                'action' => 'ping',
            ]);

            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'message' => 'API Key rechazada (401). Verifica que coincida con la de FotosEventosQR → Ajustes.',
                ];
            }

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Error HTTP ' . $response->status() . ' al conectar con ' . $endpoint,
                ];
            }

            $json = $response->json();
            if (!is_array($json) || empty($json['ok'])) {
                return [
                    'success' => false,
                    'message' => 'Respuesta inesperada del servidor: ' . ($json['error'] ?? 'Verifica que subiste api/integration/events.php'),
                ];
            }

            $driveInfo = !empty($json['drive_connected'])
                ? ('Google Drive conectado (' . ($json['drive_email'] ?: 'OK') . ')')
                : 'Aviso: Google Drive aún no conectado en FotosEventosQR';

            return [
                'success' => true,
                'drive_connected' => !empty($json['drive_connected']),
                'drive_email' => $json['drive_email'] ?? null,
                'message' => 'Conexión verificada con FotosEventosQR · ' . $driveInfo,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'No se pudo conectar con FotosEventosQR: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Devuelve los datos del álbum QR guardado en el evento (con fallback seguro si aún no se ejecutó la migración).
     */
    public static function getEventAlbumInfo(Event $event): ?array
    {
        $baseUrl = self::getBaseUrl();
        $slug = trim((string) ($event->photo_qr_slug ?? ''));
        $guestUrl = trim((string) ($event->photo_qr_url ?? ''));
        $driveUrl = trim((string) ($event->photo_qr_drive_url ?? ''));
        $posterUrl = trim((string) ($event->photo_qr_poster_url ?? ''));
        $createdAt = $event->photo_qr_created_at ? (string) $event->photo_qr_created_at : null;

        if ($slug === '' && $guestUrl === '') {
            $rawFallback = Setting::get('event_photo_qr_' . $event->id);
            if (!empty($rawFallback)) {
                $decoded = is_array($rawFallback) ? $rawFallback : json_decode((string) $rawFallback, true);
                if (is_array($decoded) && (!empty($decoded['slug']) || !empty($decoded['guest_url']))) {
                    $slug = trim((string) ($decoded['slug'] ?? ''));
                    $guestUrl = trim((string) ($decoded['guest_url'] ?? ''));
                    $driveUrl = trim((string) ($decoded['drive_url'] ?? ''));
                    $posterUrl = trim((string) ($decoded['poster_url'] ?? ''));
                    $createdAt = $decoded['created_at'] ?? null;
                }
            }
        }

        if ($slug === '' && $guestUrl === '') {
            return null;
        }

        if ($guestUrl === '' && $slug !== '') {
            $guestUrl = $baseUrl . '/e/' . rawurlencode($slug);
        }

        return [
            'slug' => $slug,
            'guest_url' => $guestUrl,
            'drive_url' => $driveUrl !== '' ? $driveUrl : null,
            'poster_url' => $posterUrl !== '' ? $posterUrl : null,
            'admin_url' => $baseUrl . '/admin/index.php',
            'created_at' => $createdAt,
        ];
    }

    /**
     * Guarda los enlaces del álbum QR en la tabla events (si existen las columnas) y en Setting de respaldo.
     */
    public static function persistEventAlbumInfo(Event $event, array $albumPayload): void
    {
        $slug = trim((string) ($albumPayload['slug'] ?? ''));
        $guestUrl = trim((string) ($albumPayload['guest_url'] ?? ''));
        $driveUrl = trim((string) ($albumPayload['drive_folder_url'] ?? $albumPayload['drive_url'] ?? ''));
        $posterUrl = trim((string) ($albumPayload['print_poster_url'] ?? $albumPayload['poster_url'] ?? ''));
        $nowStr = now()->toDateTimeString();

        Setting::set('event_photo_qr_' . $event->id, json_encode([
            'slug' => $slug,
            'guest_url' => $guestUrl,
            'drive_url' => $driveUrl,
            'poster_url' => $posterUrl,
            'created_at' => $nowStr,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        try {
            if (Schema::hasColumn('events', 'photo_qr_slug')) {
                $updates = [
                    'photo_qr_slug' => $slug !== '' ? $slug : null,
                    'photo_qr_url' => $guestUrl !== '' ? $guestUrl : null,
                    'photo_qr_drive_url' => $driveUrl !== '' ? $driveUrl : null,
                ];
                if (Schema::hasColumn('events', 'photo_qr_poster_url')) {
                    $updates['photo_qr_poster_url'] = $posterUrl !== '' ? $posterUrl : null;
                }
                if (Schema::hasColumn('events', 'photo_qr_created_at') && empty($event->photo_qr_created_at)) {
                    $updates['photo_qr_created_at'] = now();
                }
                $event->update($updates);
            }
        } catch (\Throwable $e) {
            // El respaldo en Setting mantiene la funcionalidad operativa aunque falte ejecutar migrate
        }
    }

    /**
     * Crea (o recupera si ya existía) el evento y su carpeta de Google Drive en FotosEventosQR.
     */
    public static function createOrSyncEventAlbum(Event $event, array $options = []): array
    {
        if (!self::isConfigured()) {
            return [
                'success' => false,
                'message' => 'Configura primero la API Key de FotosEventosQR en Ajustes → Música / Integraciones.',
            ];
        }

        $existing = self::getEventAlbumInfo($event);
        $dateStr = $event->event_date ? Carbon::parse($event->event_date)->format('Y-m-d') : '';
        $subtitle = trim((string) ($options['subtitle'] ?? ''));
        if ($subtitle === '') {
            $subtitle = $event->is_wedding
                ? '¡Sube y comparte aquí las mejores fotos que hagas durante la boda!'
                : '¡Sube y comparte aquí tus mejores fotos del evento!';
        }

        $accentColor = ($event->brand_clean ?? '') === 'javnx' ? '#6366f1' : '#d97706';

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => self::getApiKey(),
                'Accept' => 'application/json',
            ])->timeout(25)->post(self::endpointUrl(), [
                'action' => 'create_or_get',
                'external_ref' => 'em-event-' . $event->id,
                'slug' => $existing['slug'] ?? '',
                'title' => $event->name,
                'subtitle' => $subtitle,
                'event_date' => $dateStr,
                'accent_color' => $accentColor,
                'show_gallery' => array_key_exists('show_gallery', $options) ? (bool) $options['show_gallery'] : true,
                'allow_downloads' => array_key_exists('allow_downloads', $options) ? (bool) $options['allow_downloads'] : true,
                'share_drive_album' => !empty($options['share_drive_album']),
                'allow_videos' => !empty($options['allow_videos']),
            ]);

            $json = $response->json();
            if (!$response->successful() || !is_array($json) || empty($json['ok']) || empty($json['event'])) {
                return [
                    'success' => false,
                    'message' => $json['error'] ?? ('Error HTTP ' . $response->status() . ' al crear el álbum en FotosEventosQR.'),
                ];
            }

            $evPayload = $json['event'];
            self::persistEventAlbumInfo($event, $evPayload);

            return [
                'success' => true,
                'created' => !empty($json['created']),
                'warning' => $json['warning'] ?? null,
                'event' => $evPayload,
                'message' => !empty($json['created'])
                    ? '¡Álbum de Fotos QR y carpeta en Google Drive creados con éxito!'
                    : 'Álbum de Fotos QR sincronizado correctamente.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión con FotosEventosQR: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Consulta estadísticas en vivo (nº de fotos subidas y bytes) del álbum QR del evento.
     */
    public static function fetchAlbumStatus(Event $event): array
    {
        if (!self::isConfigured()) {
            return ['success' => false, 'message' => 'API Key no configurada.'];
        }

        $info = self::getEventAlbumInfo($event);

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => self::getApiKey(),
                'Accept' => 'application/json',
            ])->timeout(15)->post(self::endpointUrl(), [
                'action' => 'status',
                'slug' => $info['slug'] ?? '',
                'external_ref' => 'em-event-' . $event->id,
            ]);

            $json = $response->json();
            if (!$response->successful() || !is_array($json) || empty($json['ok'])) {
                return ['success' => false, 'message' => $json['error'] ?? 'No se pudo consultar el estado.'];
            }

            if (empty($json['found']) || empty($json['event'])) {
                return ['success' => true, 'found' => false];
            }

            self::persistEventAlbumInfo($event, $json['event']);

            return [
                'success' => true,
                'found' => true,
                'event' => $json['event'],
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Determina si el evento incluye Ceremonia o Cóctel (por horarios o presupuesto) para el regalo comercial.
     */
    public static function qualifiesForFreeGift(Event $event): bool
    {
        if (($event->brand_clean ?? '') === 'mago_leugim') {
            return false;
        }

        if (!empty($event->ceremony_time) || !empty($event->cocktail_time)) {
            return true;
        }

        $latestQuote = $event->relationLoaded('quotes')
            ? $event->quotes->sortByDesc('id')->first()
            : $event->quotes()->with('items')->latest()->first();

        if ($latestQuote && $latestQuote->items) {
            foreach ($latestQuote->items as $item) {
                $t = mb_strtolower((string) ($item->service_name ?: $item->concept ?: ''));
                if (
                    !str_contains($t, 'descuento') &&
                    !str_contains($t, 'fotografía') &&
                    !str_contains($t, 'fotografia') &&
                    !str_contains($t, 'fotos qr') &&
                    (
                        str_contains($t, 'ceremonia') ||
                        str_contains($t, 'cóctel') ||
                        str_contains($t, 'coctel') ||
                        str_contains($t, 'recepción') ||
                        str_contains($t, 'recepcion') ||
                        str_contains($t, 'aperitivo')
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
