<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Setting;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionWhatsAppService
{
    /**
     * Comprueba si el gateway de Evolution API está configurado y activo
     */
    public static function isEnabled(): bool
    {
        return Setting::get('whatsapp_gateway_enabled', '0') === '1'
            && !empty(self::getBaseUrl())
            && !empty(self::getApiKey());
    }

    public static function getBaseUrl(): string
    {
        $url = trim(Setting::get('whatsapp_gateway_url', ''));
        return rtrim($url, '/');
    }

    public static function getApiKey(): string
    {
        return trim(Setting::get('whatsapp_gateway_api_key', ''));
    }

    public static function getInstanceName(?string $brand = null): string
    {
        $cleanBrand = $brand ? strtolower(trim($brand)) : Setting::getDetectedBrand();
        if ($cleanBrand === 'javnx') {
            return Setting::get('whatsapp_instance_javnx', 'javnx_dj');
        }
        if ($cleanBrand === 'mago_leugim') {
            return Setting::get('whatsapp_instance_leugim', 'mago_leugim');
        }
        return Setting::get('whatsapp_instance_nunez', 'nunez_and_son');
    }

    /**
     * Envía un mensaje de texto por WhatsApp.
     * Si el gateway está activo, lo envía directamente por la API.
     * Si no, devuelve el enlace para abrirlo en WhatsApp Web / App.
     */
    public static function sendMessage(
        string $phone,
        string $text,
        ?string $brand = null,
        ?int $eventId = null,
        ?int $userId = null
    ): array {
        $cleanPhone = WhatsAppTemplateService::cleanPhone($phone);
        $waUrl = WhatsAppTemplateService::buildUrl($cleanPhone, $text);

        if (empty($cleanPhone)) {
            return [
                'success' => false,
                'mode' => 'invalid_phone',
                'message' => 'El número de teléfono no es válido.',
                'url' => $waUrl,
            ];
        }

        if (!self::isEnabled()) {
            // Guardar registro saliente local
            if ($eventId) {
                try {
                    WhatsAppMessage::create([
                        'event_id' => $eventId,
                        'user_id' => $userId ?: (auth()->check() ? auth()->id() : null),
                        'phone' => $cleanPhone,
                        'direction' => 'outbound',
                        'message' => $text,
                        'status' => 'sent',
                        'brand' => $brand ?: Setting::getDetectedBrand(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("Error guardando WhatsAppMessage local: " . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'mode' => 'manual',
                'message' => 'Gateway Evolution no activo. Abriendo WhatsApp...',
                'url' => $waUrl,
            ];
        }

        $baseUrl = self::getBaseUrl();
        $apiKey = self::getApiKey();
        $instance = self::getInstanceName($brand);

        try {
            $endpoint = "{$baseUrl}/message/sendText/{$instance}";
            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($endpoint, [
                'number' => $cleanPhone,
                'text' => $text,
            ]);

            $json = $response->json();
            $isSuccess = $response->successful() && !empty($json['key']['id']);
            $externalId = $json['key']['id'] ?? ($json['id'] ?? null);

            $msgRecord = null;
            if ($eventId) {
                $msgRecord = WhatsAppMessage::create([
                    'event_id' => $eventId,
                    'user_id' => $userId ?: (auth()->check() ? auth()->id() : null),
                    'phone' => $cleanPhone,
                    'direction' => 'outbound',
                    'message' => $text,
                    'status' => $isSuccess ? 'sent' : 'failed',
                    'external_id' => $externalId,
                    'brand' => $brand ?: Setting::getDetectedBrand(),
                    'payload' => $json,
                ]);
            }

            if ($isSuccess) {
                return [
                    'success' => true,
                    'mode' => 'api',
                    'message' => 'Mensaje enviado correctamente vía WhatsApp API.',
                    'message_id' => $externalId,
                    'record_id' => $msgRecord ? $msgRecord->id : null,
                    'url' => $waUrl,
                ];
            }

            return [
                'success' => false,
                'mode' => 'api_error',
                'message' => 'Error de envío en Evolution API: ' . ($json['message'] ?? $response->body()),
                'url' => $waUrl,
            ];

        } catch (\Throwable $e) {
            Log::error("Excepción al conectar con Evolution API ({$instance}): " . $e->getMessage());

            return [
                'success' => false,
                'mode' => 'connection_error',
                'message' => 'No se pudo conectar con el servidor de WhatsApp: ' . $e->getMessage(),
                'url' => $waUrl,
            ];
        }
    }

    /**
     * Consulta el estado de conexión de la instancia de WhatsApp
     */
    public static function checkInstanceStatus(?string $brand = null): array
    {
        if (!self::isEnabled()) {
            return [
                'configured' => false,
                'state' => 'disabled',
                'message' => 'Módulo Evolution API no configurado o inactivo.',
            ];
        }

        $baseUrl = self::getBaseUrl();
        $apiKey = self::getApiKey();
        $instance = self::getInstanceName($brand);

        try {
            $endpoint = "{$baseUrl}/instance/connectionState/{$instance}";
            $res = Http::withHeaders(['apikey' => $apiKey])->timeout(6)->get($endpoint);

            if ($res->successful()) {
                $data = $res->json();
                $state = $data['instance']['state'] ?? ($data['state'] ?? 'unknown');
                return [
                    'configured' => true,
                    'state' => $state,
                    'instance' => $instance,
                    'message' => $state === 'open' ? '🟢 Conectado y listo para chatear' : "🟡 Estado: {$state}",
                ];
            }

            if ($res->status() === 404) {
                return [
                    'configured' => true,
                    'state' => 'not_created',
                    'instance' => $instance,
                    'message' => 'ℹ️ Instancia aún no creada en Evolution API. Pulsa «📱 Vincular / Ver QR» para generarla automáticamente.',
                ];
            }

            return [
                'configured' => true,
                'state' => 'error',
                'instance' => $instance,
                'message' => 'Error al consultar estado: HTTP ' . $res->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'configured' => true,
                'state' => 'unreachable',
                'instance' => $instance,
                'message' => 'Servidor inaccesible: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtiene el código QR para escanear. Si la instancia no existe en Evolution API, la crea automáticamente.
     */
    public static function getQrCode(?string $brand = null): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Gateway no activo.'];
        }

        $baseUrl = self::getBaseUrl();
        $apiKey = self::getApiKey();
        $instance = self::getInstanceName($brand);

        try {
            // 1. Intentar conectar instancia existente
            $endpoint = "{$baseUrl}/instance/connect/{$instance}";
            $res = Http::withHeaders(['apikey' => $apiKey])->timeout(10)->get($endpoint);

            if ($res->successful()) {
                $data = $res->json();
                $base64 = $data['base64'] ?? ($data['qrcode']['base64'] ?? null);
                if ($base64) {
                    self::ensureWebhookConfigured($baseUrl, $apiKey, $instance);
                    return [
                        'success' => true,
                        'base64' => $base64,
                        'instance' => $instance,
                    ];
                }
            }

            // 2. Si no existe (404) o no devolvió QR, intentar crear la instancia
            $createRes = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(12)->post("{$baseUrl}/instance/create", [
                'instanceName' => $instance,
                'integration' => 'WHATSAPP-BAILEYS',
                'qrcode' => true,
            ]);

            if ($createRes->successful()) {
                $createData = $createRes->json();
                $base64 = $createData['qrcode']['base64'] ?? ($createData['base64'] ?? null);
                self::ensureWebhookConfigured($baseUrl, $apiKey, $instance);

                if ($base64) {
                    return [
                        'success' => true,
                        'base64' => $base64,
                        'instance' => $instance,
                    ];
                }

                // Si se creó pero el QR se pide en un segundo paso:
                $retryRes = Http::withHeaders(['apikey' => $apiKey])->timeout(10)->get($endpoint);
                if ($retryRes->successful()) {
                    $retryData = $retryRes->json();
                    $retryBase64 = $retryData['base64'] ?? ($retryData['qrcode']['base64'] ?? null);
                    if ($retryBase64) {
                        return [
                            'success' => true,
                            'base64' => $retryBase64,
                            'instance' => $instance,
                        ];
                    }
                }
            }

            return [
                'success' => false,
                'message' => 'No se pudo generar QR (puede que ya esté conectado o el servidor devolvió un error).',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo QR: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Asegura que el webhook hacia nuestra app esté configurado en Evolution API
     */
    public static function ensureWebhookConfigured(string $baseUrl, string $apiKey, string $instance): void
    {
        try {
            $webhookUrl = url('/api/whatsapp/webhook');
            Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(5)->post("{$baseUrl}/webhook/set/{$instance}", [
                'webhook' => [
                    'enabled' => true,
                    'url' => $webhookUrl,
                    'byEvents' => false,
                    'base64' => false,
                    'events' => ['MESSAGES_UPSERT'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning("No se pudo auto-configurar webhook para {$instance}: " . $e->getMessage());
        }
    }

    /**
     * Envía una notificación interna por WhatsApp a los números de los socios / administradores (Fran y su hermano).
     */
    public static function notifyAdmins(string $text, ?string $brand = null): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $phones = Setting::getAdminNotificationPhones($brand);
        $baseUrl = self::getBaseUrl();
        $apiKey = self::getApiKey();
        $instance = self::getInstanceName($brand);

        foreach ($phones as $phone) {
            try {
                $cleanPhone = WhatsAppTemplateService::cleanPhone((string)$phone);
                if (empty($cleanPhone)) {
                    continue;
                }

                $endpoint = "{$baseUrl}/message/sendText/{$instance}";
                Http::withHeaders([
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(6)->post($endpoint, [
                    'number' => $cleanPhone,
                    'text' => $text,
                ]);
            } catch (\Throwable $e) {
                Log::warning("Error enviando alerta WhatsApp a socio/admin ({$phone}): " . $e->getMessage());
            }
        }
    }
}

