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
     * Obtiene el código QR para escanear en caso de estar desconectado
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
            $endpoint = "{$baseUrl}/instance/connect/{$instance}";
            $res = Http::withHeaders(['apikey' => $apiKey])->timeout(10)->get($endpoint);

            if ($res->successful()) {
                $data = $res->json();
                $base64 = $data['base64'] ?? ($data['qrcode']['base64'] ?? null);
                if ($base64) {
                    return [
                        'success' => true,
                        'base64' => $base64,
                        'instance' => $instance,
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'No se pudo generar QR (puede que ya esté conectado).',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error obteniendo QR: ' . $e->getMessage(),
            ];
        }
    }
}
