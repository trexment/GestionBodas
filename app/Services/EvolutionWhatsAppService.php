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
        $clean = $brand ? strtolower(trim($brand)) : Setting::getDetectedBrand();

        if (in_array($clean, ['fran', 'javnx', 'javnx_dj'])) {
            return Setting::get('whatsapp_instance_fran', Setting::get('whatsapp_instance_javnx', 'whatsapp_principal'));
        }

        if (in_array($clean, ['ezequiel', 'ezequiel_nunez'])) {
            return Setting::get('whatsapp_instance_ezequiel', 'whatsapp_ezequiel');
        }

        if (in_array($clean, ['mago_leugim', 'leugim'])) {
            return Setting::get('whatsapp_instance_leugim', 'mago_leugim');
        }

        if (in_array($clean, ['miguel', 'nunez', 'nunez_and_son'])) {
            return Setting::get('whatsapp_instance_miguel', Setting::get('whatsapp_instance_nunez', 'whatsapp_miguel'));
        }

        // Si se pasa directamente un nombre de instancia específico
        $allConfigured = [
            Setting::get('whatsapp_instance_miguel'),
            Setting::get('whatsapp_instance_fran'),
            Setting::get('whatsapp_instance_ezequiel'),
            Setting::get('whatsapp_instance_nunez'),
            Setting::get('whatsapp_instance_javnx'),
            'whatsapp_principal',
            'whatsapp_miguel',
            'whatsapp_ezequiel',
        ];
        if (in_array($clean, array_filter(array_map('strtolower', $allConfigured)))) {
            return $clean;
        }

        return Setting::get('whatsapp_instance_miguel', Setting::get('whatsapp_instance_nunez', 'whatsapp_miguel'));
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

        self::ensureWebhookConfigured($baseUrl, $apiKey, $instance);

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

            // Fallback automático: si la instancia de la marca (ej. whatsapp_miguel en Núñez & Son) no está conectada,
            // reintentar con la instancia principal activa (whatsapp_principal de Fran)
            if (!$isSuccess) {
                $fallbackInstance = Setting::get('whatsapp_instance_fran', Setting::get('whatsapp_instance_javnx', 'whatsapp_principal'));
                if (!empty($fallbackInstance) && $fallbackInstance !== $instance) {
                    $fallbackEndpoint = "{$baseUrl}/message/sendText/{$fallbackInstance}";
                    $fallbackRes = Http::withHeaders([
                        'apikey' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])->timeout(10)->post($fallbackEndpoint, [
                        'number' => $cleanPhone,
                        'text' => $text,
                    ]);
                    $fallbackJson = $fallbackRes->json();
                    if ($fallbackRes->successful() && !empty($fallbackJson['key']['id'])) {
                        $response = $fallbackRes;
                        $json = $fallbackJson;
                        $isSuccess = true;
                        $instance = $fallbackInstance;
                    }
                }
            }

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
            // Intentar consultar fetchInstances para obtener el estado y el teléfono del propietario conectado
            $fetchRes = Http::withHeaders(['apikey' => $apiKey])->timeout(6)->get("{$baseUrl}/instance/fetchInstances");
            if ($fetchRes->successful()) {
                $instances = $fetchRes->json();
                if (is_array($instances)) {
                    foreach ($instances as $inst) {
                        if (isset($inst['name']) && strtolower(trim($inst['name'])) === strtolower(trim($instance))) {
                            $status = $inst['connectionStatus'] ?? ($inst['state'] ?? 'unknown');
                            $ownerJid = $inst['ownerJid'] ?? null;
                            $ownerPhone = $ownerJid ? ('+' . explode('@', $ownerJid)[0]) : null;

                            if ($status === 'open') {
                                self::ensureWebhookConfigured($baseUrl, $apiKey, $instance, true);
                                return [
                                    'configured' => true,
                                    'state' => 'open',
                                    'instance' => $instance,
                                    'owner' => $ownerPhone,
                                    'message' => '🟢 Conectado y listo para chatear' . ($ownerPhone ? " ({$ownerPhone})" : ""),
                                ];
                            } elseif ($status === 'connecting') {
                                return [
                                    'configured' => true,
                                    'state' => 'connecting',
                                    'instance' => $instance,
                                    'owner' => null,
                                    'message' => '🟡 Esperando escaneo de código QR (Pulsa «Vincular / Ver QR»)',
                                ];
                            } else {
                                return [
                                    'configured' => true,
                                    'state' => $status,
                                    'instance' => $instance,
                                    'owner' => null,
                                    'message' => "🔴 Desconectado (Estado: {$status})",
                                ];
                            }
                        }
                    }
                }
            }

            // Fallback directo a connectionState
            $endpoint = "{$baseUrl}/instance/connectionState/{$instance}";
            $res = Http::withHeaders(['apikey' => $apiKey])->timeout(6)->get($endpoint);

            if ($res->successful()) {
                $data = $res->json();
                $state = $data['instance']['state'] ?? ($data['state'] ?? 'unknown');
                if ($state === 'open') {
                    self::ensureWebhookConfigured($baseUrl, $apiKey, $instance, true);
                }
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
            // 1. Comprobar si ya está conectada antes de solicitar QR
            $statusCheck = self::checkInstanceStatus($brand);
            if (($statusCheck['state'] ?? '') === 'open') {
                self::ensureWebhookConfigured($baseUrl, $apiKey, $instance, true);
                return [
                    'success' => true,
                    'already_connected' => true,
                    'instance' => $instance,
                    'owner' => $statusCheck['owner'] ?? null,
                    'message' => "¡La instancia '{$instance}' ya está conectada y activa en WhatsApp" . (!empty($statusCheck['owner']) ? " ({$statusCheck['owner']})" : "") . "! No es necesario escanear QR.",
                ];
            }

            // 2. Intentar conectar instancia existente
            $endpoint = "{$baseUrl}/instance/connect/{$instance}";
            $res = Http::withHeaders(['apikey' => $apiKey])->timeout(10)->get($endpoint);

            if ($res->successful()) {
                $data = $res->json();

                if (($data['instance']['state'] ?? ($data['state'] ?? '')) === 'open') {
                    self::ensureWebhookConfigured($baseUrl, $apiKey, $instance, true);
                    return [
                        'success' => true,
                        'already_connected' => true,
                        'instance' => $instance,
                        'message' => "¡La instancia '{$instance}' ya está conectada y activa en WhatsApp! No es necesario escanear QR.",
                    ];
                }

                $base64 = $data['base64'] ?? ($data['qrcode']['base64'] ?? null);
                if ($base64) {
                    if (!str_starts_with($base64, 'data:image/')) {
                        $base64 = 'data:image/png;base64,' . $base64;
                    }
                    self::ensureWebhookConfigured($baseUrl, $apiKey, $instance);
                    return [
                        'success' => true,
                        'base64' => $base64,
                        'instance' => $instance,
                    ];
                }
            }

            // 3. Si no existe (404) o no devolvió QR, intentar crear la instancia
            $createRes = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(12)->post("{$baseUrl}/instance/create", [
                'instanceName' => $instance,
                'integration' => 'WHATSAPP-BAILEYS',
                'qrcode' => true,
                'syncFullHistory' => true,
            ]);

            if ($createRes->successful()) {
                $createData = $createRes->json();
                $base64 = $createData['qrcode']['base64'] ?? ($createData['base64'] ?? null);
                self::ensureWebhookConfigured($baseUrl, $apiKey, $instance);

                if ($base64) {
                    if (!str_starts_with($base64, 'data:image/')) {
                        $base64 = 'data:image/png;base64,' . $base64;
                    }
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
                        if (!str_starts_with($retryBase64, 'data:image/')) {
                            $retryBase64 = 'data:image/png;base64,' . $retryBase64;
                        }
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
     * Desconecta o elimina la sesión activa de WhatsApp para poder volver a escanear un QR nuevo
     */
    public static function disconnectInstance(?string $brand = null): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Gateway no activo.'];
        }

        $baseUrl = self::getBaseUrl();
        $apiKey = self::getApiKey();
        $instance = self::getInstanceName($brand);

        try {
            $res = Http::withHeaders(['apikey' => $apiKey])->timeout(8)->delete("{$baseUrl}/instance/logout/{$instance}");
            if (!$res->successful()) {
                Http::withHeaders(['apikey' => $apiKey])->timeout(8)->delete("{$baseUrl}/instance/delete/{$instance}");
            }
            return [
                'success' => true,
                'instance' => $instance,
                'message' => "Instancia '{$instance}' desconectada correctamente de WhatsApp.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al desconectar instancia: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Asegura que el webhook hacia nuestra app esté configurado en Evolution API
     */
    public static function ensureWebhookConfigured(string $baseUrl, string $apiKey, string $instance, bool $force = false): void
    {
        if (empty($baseUrl) || empty($apiKey) || empty($instance)) {
            return;
        }

        $cacheKey = 'evo_webhook_configured_' . md5($baseUrl . '|' . $instance);
        if (!$force && \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return;
        }

        try {
            $webhookUrl = url('/api/whatsapp/webhook');
            if (str_contains($webhookUrl, 'localhost') || str_contains($webhookUrl, '127.0.0.1')) {
                $appUrl = rtrim((string) config('app.url', ''), '/');
                if (!empty($appUrl) && !str_contains($appUrl, 'localhost')) {
                    $webhookUrl = $appUrl . '/api/whatsapp/webhook';
                }
            }

            // Formato Evolution API v2 (anidado en "webhook")
            $res = Http::withHeaders([
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

            // Fallback formato plano (Evolution API v1 / v2.0)
            if (!$res->successful()) {
                $res = Http::withHeaders([
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(5)->post("{$baseUrl}/webhook/set/{$instance}", [
                    'enabled' => true,
                    'url' => $webhookUrl,
                    'webhookByEvents' => false,
                    'webhookBase64' => false,
                    'events' => ['MESSAGES_UPSERT'],
                ]);
            }

            if ($res->successful()) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(15));
            }
        } catch (\Throwable $e) {
            Log::warning("No se pudo auto-configurar webhook para {$instance}: " . $e->getMessage());
        }
    }

    /**
     * Envía una notificación interna por WhatsApp a los números de los socios / administradores (Fran y su hermano).
     * Devuelve un resumen del envío para diagnóstico y modo test.
     */
    public static function notifyAdmins(string $text, ?string $brand = null): array
    {
        $report = [
            'enabled' => self::isEnabled(),
            'brand' => $brand ?: Setting::getDetectedBrand(),
            'preferred_instance' => self::getInstanceName($brand),
            'open_instances' => [],
            'recipients' => [],
            'sent_count' => 0,
        ];

        if (!self::isEnabled()) {
            Log::info('EvolutionWhatsAppService::notifyAdmins omitido: gateway desactivado o sin credenciales.');
            return $report;
        }

        $phones = Setting::getAdminNotificationPhones($brand);
        $baseUrl = self::getBaseUrl();
        $apiKey = self::getApiKey();
        $preferredInstance = self::getInstanceName($brand);

        // 1. Descubrir qué instancias están realmente conectadas ('open') y qué número propietario (owner) tiene cada una
        $openInstances = []; // [instanceName => cleanOwnerPhone]
        try {
            $fetchRes = Http::withHeaders(['apikey' => $apiKey])->timeout(5)->get("{$baseUrl}/instance/fetchInstances");
            if ($fetchRes->successful() && is_array($fetchRes->json())) {
                foreach ($fetchRes->json() as $inst) {
                    $instName = $inst['name'] ?? ($inst['instance']['instanceName'] ?? null);
                    $state = $inst['connectionStatus'] ?? ($inst['instance']['state'] ?? ($inst['instance']['status'] ?? null));
                    if (!empty($instName) && $state === 'open') {
                        $ownerJid = $inst['ownerJid'] ?? ($inst['instance']['owner'] ?? ($inst['owner'] ?? null));
                        $ownerDigits = $ownerJid ? preg_replace('/@.*$/', '', (string)$ownerJid) : '';
                        $cleanOwner = $ownerDigits ? WhatsAppTemplateService::cleanPhone($ownerDigits) : '';
                        $openInstances[$instName] = $cleanOwner;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('EvolutionWhatsAppService::notifyAdmins error consultando fetchInstances: ' . $e->getMessage());
        }

        $report['open_instances'] = $openInstances;

        // Lista ordenada de instancias candidatas a intentar (primero las 'open', luego las configuradas como respaldo)
        $configuredInstances = array_values(array_unique(array_filter([
            $preferredInstance,
            Setting::get('whatsapp_instance_fran', Setting::get('whatsapp_instance_javnx', 'whatsapp_principal')),
            Setting::get('whatsapp_instance_miguel', Setting::get('whatsapp_instance_nunez', 'whatsapp_miguel')),
            Setting::get('whatsapp_instance_ezequiel', 'whatsapp_ezequiel'),
            'whatsapp_principal',
            'whatsapp_miguel',
        ])));

        foreach ($phones as $phone) {
            $cleanPhone = WhatsAppTemplateService::cleanPhone((string)$phone);
            if (empty($cleanPhone)) {
                continue;
            }

            // Construir el orden óptimo de instancias para este destinatario:
            // - Si hay una instancia 'open' cuyo propietario NO es el propio destinatario, usarla primero
            //   para evitar auto-mensajes (fromMe: true) que WhatsApp silencia sin notificación push.
            // - A continuación, la instancia preferida de la marca si está 'open'.
            // - A continuación, cualquier otra instancia 'open' (ej. whatsapp_principal como fallback para Núñez & Son).
            // - Por último, las instancias configuradas por si fetchInstances no devolvió estado.
            $candidateInstances = [];

            foreach ($openInstances as $instName => $ownerPhone) {
                if (!empty($ownerPhone) && $ownerPhone !== $cleanPhone) {
                    $candidateInstances[] = $instName;
                }
            }
            if (isset($openInstances[$preferredInstance]) && !in_array($preferredInstance, $candidateInstances, true)) {
                $candidateInstances[] = $preferredInstance;
            }
            foreach (array_keys($openInstances) as $instName) {
                if (!in_array($instName, $candidateInstances, true)) {
                    $candidateInstances[] = $instName;
                }
            }
            foreach ($configuredInstances as $instName) {
                if (!in_array($instName, $candidateInstances, true)) {
                    $candidateInstances[] = $instName;
                }
            }

            $sentToThisPhone = false;
            $usedInstance = null;
            $lastError = null;
            $isSelfMessage = false;

            foreach ($candidateInstances as $instToTry) {
                try {
                    $ownerOfInst = $openInstances[$instToTry] ?? '';
                    $sendingToSelf = (!empty($ownerOfInst) && $ownerOfInst === $cleanPhone);

                    $endpoint = "{$baseUrl}/message/sendText/{$instToTry}";
                    $res = Http::withHeaders([
                        'apikey' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])->timeout(6)->post($endpoint, [
                        'number' => $cleanPhone,
                        'text' => $text,
                    ]);

                    $json = $res->json();
                    if ($res->successful() && (!empty($json['key']['id']) || !empty($json['status']) || !empty($json['message']))) {
                        $sentToThisPhone = true;
                        $usedInstance = $instToTry;
                        $isSelfMessage = $sendingToSelf;
                        $report['sent_count']++;
                        break;
                    }

                    $lastError = "HTTP {$res->status()} en {$instToTry}: " . substr($res->body(), 0, 180);
                } catch (\Throwable $e) {
                    $lastError = "Excepción en {$instToTry}: " . $e->getMessage();
                    Log::warning("Error enviando alerta WhatsApp a socio/admin ({$cleanPhone}) vía {$instToTry}: " . $e->getMessage());
                }
            }

            $report['recipients'][$cleanPhone] = [
                'phone' => $cleanPhone,
                'success' => $sentToThisPhone,
                'instance_used' => $usedInstance,
                'self_message' => $isSelfMessage,
                'error' => $sentToThisPhone ? null : $lastError,
            ];
        }

        return $report;
    }

    /**
     * Busca la conversación previa con un número de teléfono en Evolution API (y en la BD local)
     * e importa todos los mensajes anteriores y respuestas entrantes (incluyendo chats con @lid de Meta/Instagram)
     * dentro del evento indicado.
     */
    public static function syncConversationHistory(
        Event $event,
        string $phone,
        ?string $preferredBrand = null,
        ?int $userId = null,
        bool $searchAllInstances = true
    ): array {
        $cleanPhone = WhatsAppTemplateService::cleanPhone($phone);
        if (empty($cleanPhone) || strlen($cleanPhone) < 7) {
            return [
                'success' => false,
                'imported' => 0,
                'message' => 'Introduce un número de teléfono válido para buscar la conversación.',
            ];
        }

        $national = strlen($cleanPhone) > 9 ? substr($cleanPhone, -9) : $cleanPhone;
        $importedCount = 0;
        $newInboundMessages = [];
        $clientName = $event->client ? $event->client->name : ($event->partner_name ?: 'Cliente');

        // 1. Vincular/copiar posibles mensajes previos que ya existieran en la BD local con ese teléfono
        try {
            $localMessages = WhatsAppMessage::where(function ($q) use ($cleanPhone, $national) {
                    $q->where('phone', $cleanPhone)
                      ->orWhere('phone', 'LIKE', "%{$national}%");
                })
                ->where(function ($q) use ($event) {
                    $q->whereNull('event_id')
                      ->orWhere('event_id', '!=', $event->id);
                })
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($localMessages as $localMsg) {
                if (is_null($localMsg->event_id)) {
                    $localMsg->update(['event_id' => $event->id]);
                    $importedCount++;
                } else {
                    $existsInEvent = WhatsAppMessage::where('event_id', $event->id)
                        ->where(function ($q) use ($localMsg) {
                            if (!empty($localMsg->external_id)) {
                                $q->where('external_id', $localMsg->external_id);
                            } else {
                                $q->where('direction', $localMsg->direction)
                                  ->where('message', $localMsg->message);
                            }
                        })
                        ->exists();

                    if (!$existsInEvent) {
                        WhatsAppMessage::create([
                            'event_id' => $event->id,
                            'user_id' => $localMsg->user_id ?: $userId,
                            'phone' => $cleanPhone,
                            'sender_name' => $localMsg->sender_name ?: ($localMsg->direction === 'inbound' ? $clientName : 'Nosotros'),
                            'direction' => $localMsg->direction,
                            'message' => $localMsg->message,
                            'status' => $localMsg->status,
                            'external_id' => $localMsg->external_id ? ($localMsg->external_id . '_ev' . $event->id) : null,
                            'brand' => $localMsg->brand ?: ($preferredBrand ?: $event->brand_clean),
                            'payload' => $localMsg->payload,
                            'created_at' => $localMsg->created_at,
                            'updated_at' => $localMsg->created_at,
                        ]);
                        $importedCount++;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Error revisando mensajes locales en syncConversationHistory: " . $e->getMessage());
        }

        // 2. Si Evolution API está activo, consultar el historial directamente al servidor de WhatsApp
        if (!self::isEnabled()) {
            return [
                'success' => $importedCount > 0,
                'imported' => $importedCount,
                'message' => $importedCount > 0
                    ? "✅ Se han vinculado {$importedCount} mensaje(s) previos encontrados en la base de datos."
                    : '⚠️ Evolution API no está activo para buscar en el teléfono y no había mensajes locales pendientes para este número.',
            ];
        }

        $baseUrl = self::getBaseUrl();
        $apiKey = self::getApiKey();

        // Construir lista de instancias donde buscar (primero la seleccionada, luego el resto si searchAllInstances = true)
        $instancesToSearch = [];
        $primaryInstance = self::getInstanceName($preferredBrand);
        if (!empty($primaryInstance)) {
            $instancesToSearch[$primaryInstance] = $preferredBrand ?: 'miguel';
        }

        if ($searchAllInstances) {
            foreach (['miguel', 'fran', 'ezequiel'] as $brandKey) {
                $inst = self::getInstanceName($brandKey);
                if (!empty($inst) && !isset($instancesToSearch[$inst])) {
                    $instancesToSearch[$inst] = $brandKey;
                }
            }

            // Añadir también cualquier otra instancia conectada ('open') en Evolution API por si tuviera nombre personalizado
            try {
                $fetchRes = Http::withHeaders(['apikey' => $apiKey])->timeout(5)->get("{$baseUrl}/instance/fetchInstances");
                if ($fetchRes->successful() && is_array($fetchRes->json())) {
                    foreach ($fetchRes->json() as $item) {
                        $instName = $item['name'] ?? ($item['instance']['instanceName'] ?? ($item['instanceName'] ?? null));
                        $st = $item['connectionStatus'] ?? ($item['instance']['state'] ?? ($item['state'] ?? ''));
                        if (!empty($instName) && strtolower((string)$st) === 'open' && !isset($instancesToSearch[$instName])) {
                            $instancesToSearch[$instName] = $preferredBrand ?: 'miguel';
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Posibles JIDs iniciales para el teléfono en WhatsApp + cualquier JID/LID guardado previamente en BD
        $candidateJids = array_values(array_unique(array_filter([
            "{$cleanPhone}@s.whatsapp.net",
            strlen($national) === 9 ? "34{$national}@s.whatsapp.net" : null,
            "{$national}@s.whatsapp.net",
        ])));

        $knownExternalIds = [];
        $knownOutboundSnippets = [];
        try {
            $existingEventMsgs = WhatsAppMessage::where('event_id', $event->id)
                ->orWhere('phone', 'LIKE', "%{$national}%")
                ->get();

            foreach ($existingEventMsgs as $em) {
                if (!empty($em->external_id)) {
                    $cleanExtId = explode('_ev', (string)$em->external_id)[0];
                    if (!empty($cleanExtId)) {
                        $knownExternalIds[$cleanExtId] = true;
                    }
                }
                if ($em->direction === 'outbound' && !empty($em->message) && !str_starts_with($em->message, '📧')) {
                    $snippet = trim(mb_substr($em->message, 0, 45));
                    if (mb_strlen($snippet) >= 12) {
                        $knownOutboundSnippets[] = $snippet;
                    }
                }
                if (is_array($em->payload)) {
                    $pKey = $em->payload['key'] ?? ($em->payload['data']['key'] ?? []);
                    foreach ([
                        $pKey['remoteJid'] ?? null,
                        $pKey['remoteJidAlt'] ?? null,
                        $em->payload['remoteJid'] ?? null,
                        $em->payload['data']['remoteJid'] ?? null,
                    ] as $possibleJid) {
                        if (!empty($possibleJid) && is_string($possibleJid) && str_contains($possibleJid, '@') && !in_array($possibleJid, $candidateJids)) {
                            $candidateJids[] = $possibleJid;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {}

        // Preparar variantes del nombre del cliente/pareja para localizar chats @lid (ej. "* Alvaro y ana" -> "alvaro y ana")
        $cleanClientSearchName = trim((string)preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower($clientName)));
        $cleanClientSearchName = trim((string)preg_replace('/\s+/', ' ', $cleanClientSearchName));
        if (in_array($cleanClientSearchName, ['cliente', 'novios', 'pareja', 'evento', 'boda', 'sin nombre']) || mb_strlen($cleanClientSearchName) < 4) {
            $cleanClientSearchName = '';
        }

        foreach ($instancesToSearch as $instance => $brandKey) {
            self::ensureWebhookConfigured($baseUrl, $apiKey, $instance);

            $trustedJids = $candidateJids;
            $collectedRawMessages = [];

            // 2a. Consultar /chat/whatsappNumbers/{instance} por si devuelve un JID o LID asociado al teléfono
            try {
                $numCheckRes = Http::withHeaders([
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(6)->post("{$baseUrl}/chat/whatsappNumbers/{$instance}", [
                    'numbers' => [$cleanPhone, $national],
                ]);
                if ($numCheckRes->successful() && is_array($numCheckRes->json())) {
                    foreach ($numCheckRes->json() as $numItem) {
                        if (!is_array($numItem)) continue;
                        foreach (['jid', 'lid', 'remoteJid', 'id'] as $f) {
                            $val = $numItem[$f] ?? null;
                            if (!empty($val) && is_string($val) && str_contains($val, '@') && !in_array($val, $trustedJids)) {
                                $trustedJids[] = $val;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {}

            // 2b. Buscar en /chat/findChats y /chat/findContacts cualquier chat o contacto que contenga el teléfono o el nombre exacto del cliente (descubre @lid)
            foreach (['/chat/findChats/', '/chat/findContacts/'] as $endpointPrefix) {
                try {
                    $listRes = Http::withHeaders([
                        'apikey' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])->timeout(8)->post("{$baseUrl}{$endpointPrefix}{$instance}", (object)[]);

                    if ($listRes->successful() && is_array($listRes->json())) {
                        $items = self::extractRecordsFromResponse($listRes->json());
                        if (empty($items) && array_is_list($listRes->json())) {
                            $items = $listRes->json();
                        }
                        foreach ($items as $item) {
                            if (!is_array($item)) continue;
                            $rawJson = json_encode($item, JSON_UNESCAPED_UNICODE) ?: '';
                            $itemName = mb_strtolower(trim((string)($item['name'] ?? ($item['pushName'] ?? ($item['verifiedName'] ?? '')))));
                            $cleanItemName = trim((string)preg_replace('/\s+/', ' ', (string)preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $itemName)));

                            $matchesPhone = str_contains($rawJson, $national);
                            $matchesExtId = false;
                            foreach (array_keys($knownExternalIds) as $extId) {
                                if (str_contains($rawJson, $extId)) {
                                    $matchesExtId = true;
                                    break;
                                }
                            }
                            $matchesName = !empty($cleanClientSearchName)
                                && !empty($cleanItemName)
                                && ($cleanItemName === $cleanClientSearchName || (mb_strlen($cleanClientSearchName) >= 6 && str_contains($cleanItemName, $cleanClientSearchName)));

                            if ($matchesPhone || $matchesExtId || $matchesName) {
                                foreach (['remoteJid', 'id', 'jid', 'lid', 'remoteJidAlt'] as $jidField) {
                                    $cJid = $item[$jidField] ?? null;
                                    if (!empty($cJid) && is_string($cJid) && str_contains($cJid, '@') && !str_contains($cJid, '@g.us') && !str_contains($cJid, '@broadcast')) {
                                        if (!in_array($cJid, $trustedJids)) {
                                            $trustedJids[] = $cJid;
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }

            // 2c. Si tenemos external_id de mensajes enviados desde la app para este evento, buscar ese mensaje en Evolution API para descubrir su remoteJid real (ej. @lid)
            foreach (array_slice(array_keys($knownExternalIds), 0, 5) as $extId) {
                try {
                    $extRecords = self::fetchEvolutionMessages($baseUrl, $apiKey, $instance, [
                        'key' => ['id' => $extId],
                    ], 20);
                    foreach ($extRecords as $rMsg) {
                        if (!is_array($rMsg)) continue;
                        $rJid = $rMsg['key']['remoteJid'] ?? ($rMsg['remoteJid'] ?? null);
                        if (!empty($rJid) && is_string($rJid) && str_contains($rJid, '@') && !in_array($rJid, $trustedJids)) {
                            $trustedJids[] = $rJid;
                        }
                        $collectedRawMessages[] = $rMsg;
                    }
                } catch (\Throwable $e) {}
            }

            // 2d. Escanear los últimos 200 mensajes recientes de la instancia (sin filtro restrictivo de remoteJid)
            // Esto permite descubrir conversaciones @lid donde el número viene en remoteJidAlt/senderPn, o donde enviamos un saludo al cliente
            try {
                $recentRecords = self::fetchEvolutionMessages($baseUrl, $apiKey, $instance, [], 200);
                foreach ($recentRecords as $rMsg) {
                    if (!is_array($rMsg)) continue;
                    $rKey = $rMsg['key'] ?? [];
                    $rJid = (string)($rKey['remoteJid'] ?? ($rMsg['remoteJid'] ?? ''));
                    if (empty($rJid) || str_contains($rJid, '@g.us') || str_contains($rJid, '@broadcast')) {
                        continue;
                    }

                    $rJson = json_encode($rMsg, JSON_UNESCAPED_UNICODE) ?: '';
                    $rId = (string)($rKey['id'] ?? ($rMsg['id'] ?? ''));
                    $rText = self::extractTextFromEvolutionMessage($rMsg);
                    $rFromMe = (bool)($rKey['fromMe'] ?? ($rMsg['fromMe'] ?? false));
                    $rPushName = mb_strtolower(trim((string)($rMsg['pushName'] ?? '')));
                    $cleanPushName = trim((string)preg_replace('/\s+/', ' ', (string)preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $rPushName)));

                    $matchedThisMsg = false;

                    // Coincidencia 1: El JSON del mensaje contiene los 9 dígitos del teléfono (en remoteJid, remoteJidAlt, senderPn, participant, etc.)
                    if (str_contains($rJson, $national)) {
                        $matchedThisMsg = true;
                    }

                    // Coincidencia 2: El ID del mensaje coincide con un mensaje que ya tenemos registrado en el evento
                    if (!$matchedThisMsg && !empty($rId) && isset($knownExternalIds[$rId])) {
                        $matchedThisMsg = true;
                    }

                    // Coincidencia 3: Es un mensaje saliente nuestro que contiene el mismo texto enviado desde el evento o saluda por su nombre a la pareja
                    if (!$matchedThisMsg && !empty($rText)) {
                        foreach ($knownOutboundSnippets as $snip) {
                            if (str_contains($rText, $snip)) {
                                $matchedThisMsg = true;
                                break;
                            }
                        }
                        if (!$matchedThisMsg && $rFromMe && !empty($cleanClientSearchName)) {
                            $cleanMsgText = trim((string)preg_replace('/\s+/', ' ', (string)preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower($rText))));
                            if (str_contains($cleanMsgText, 'hola ' . $cleanClientSearchName)) {
                                $matchedThisMsg = true;
                            }
                        }
                    }

                    // Coincidencia 4: El pushName del remitente coincide exactamente con el nombre del cliente del evento
                    if (!$matchedThisMsg && !empty($cleanClientSearchName) && !empty($cleanPushName)) {
                        if ($cleanPushName === $cleanClientSearchName || (mb_strlen($cleanClientSearchName) >= 6 && str_contains($cleanPushName, $cleanClientSearchName))) {
                            $matchedThisMsg = true;
                        }
                    }

                    if ($matchedThisMsg && !in_array($rJid, $trustedJids)) {
                        $trustedJids[] = $rJid;
                    }
                }

                // Añadir todos los mensajes de la ventana reciente que pertenezcan a cualquiera de los JIDs/LIDs confiables descubiertos
                foreach ($recentRecords as $rMsg) {
                    if (!is_array($rMsg)) continue;
                    $rKey = $rMsg['key'] ?? [];
                    $rJid = (string)($rKey['remoteJid'] ?? ($rMsg['remoteJid'] ?? ''));
                    $rJidAlt = (string)($rKey['remoteJidAlt'] ?? ($rMsg['remoteJidAlt'] ?? ''));
                    if (in_array($rJid, $trustedJids, true) || (!empty($rJidAlt) && in_array($rJidAlt, $trustedJids, true)) || str_contains($rJid, $national) || str_contains($rJidAlt, $national)) {
                        $collectedRawMessages[] = $rMsg;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Error escaneando mensajes recientes en {$instance}: " . $e->getMessage());
            }

            // 2e. Para cada JID confiable (incluyendo cualquier XXXXXXX@lid descubierto), consultar el historial completo de ese JID
            foreach ($trustedJids as $jid) {
                try {
                    $jidRecords = self::fetchEvolutionMessages($baseUrl, $apiKey, $instance, [
                        'key' => [
                            'remoteJid' => $jid,
                        ],
                    ], 200);

                    foreach ($jidRecords as $rawMsg) {
                        if (is_array($rawMsg)) {
                            $collectedRawMessages[] = $rawMsg;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Error consultando findMessages en {$instance} para {$jid}: " . $e->getMessage());
                }
            }

            if (empty($collectedRawMessages)) {
                continue;
            }

            // Ordenar cronológicamente (del más antiguo al más reciente) antes de insertar
            usort($collectedRawMessages, function ($a, $b) {
                $tsA = (int)($a['messageTimestamp'] ?? ($a['timestamp'] ?? 0));
                $tsB = (int)($b['messageTimestamp'] ?? ($b['timestamp'] ?? 0));
                if ($tsA > 9999999999) $tsA = (int)floor($tsA / 1000);
                if ($tsB > 9999999999) $tsB = (int)floor($tsB / 1000);
                return $tsA <=> $tsB;
            });

            foreach ($collectedRawMessages as $rawMsg) {
                $key = $rawMsg['key'] ?? [];
                $remoteJid = (string)($key['remoteJid'] ?? ($rawMsg['remoteJid'] ?? ''));
                $remoteJidAlt = (string)($key['remoteJidAlt'] ?? ($rawMsg['remoteJidAlt'] ?? ($key['senderPn'] ?? ($rawMsg['senderPn'] ?? ''))));

                // Ignorar estados o grupos
                if (str_contains($remoteJid, '@broadcast') || str_contains($remoteJid, '@g.us')) {
                    continue;
                }

                // Verificar que pertenece a un JID confiable de este cliente (incluye su @lid) o contiene su teléfono
                $isTrustedJid = in_array($remoteJid, $trustedJids, true)
                    || (!empty($remoteJidAlt) && in_array($remoteJidAlt, $trustedJids, true))
                    || str_contains($remoteJid, $national)
                    || str_contains($remoteJidAlt, $national);

                if (!$isTrustedJid) {
                    continue;
                }

                $text = self::extractTextFromEvolutionMessage($rawMsg);
                if (empty(trim($text))) {
                    continue;
                }

                $fromMe = (bool)($key['fromMe'] ?? ($rawMsg['fromMe'] ?? false));
                $direction = $fromMe ? 'outbound' : 'inbound';
                $externalId = $key['id'] ?? ($rawMsg['id'] ?? null);
                $pushName = $rawMsg['pushName'] ?? null;
                $senderName = $fromMe
                    ? 'Nosotros'
                    : ((!empty($pushName) && !in_array(mb_strtolower($pushName), ['voce', 'você', 'tú', 'tu', 'you'])) ? $pushName : $clientName);

                $ts = $rawMsg['messageTimestamp'] ?? ($rawMsg['timestamp'] ?? null);
                $msgDate = now();
                if (is_numeric($ts) && (int)$ts > 0) {
                    $tsInt = (int)$ts;
                    if ($tsInt > 9999999999) {
                        $tsInt = (int)floor($tsInt / 1000);
                    }
                    $msgDate = \Carbon\Carbon::createFromTimestamp($tsInt)->setTimezone(config('app.timezone', 'Europe/Madrid'));
                } elseif (is_string($ts) && !empty($ts)) {
                    try {
                        $msgDate = \Carbon\Carbon::parse($ts)->setTimezone(config('app.timezone', 'Europe/Madrid'));
                    } catch (\Throwable $e) {}
                }

                // Evitar duplicados por external_id en el evento
                if (!empty($externalId)) {
                    $existsByExtId = WhatsAppMessage::where('event_id', $event->id)
                        ->where(function ($q) use ($externalId, $event) {
                            $q->where('external_id', $externalId)
                              ->orWhere('external_id', $externalId . '_ev' . $event->id);
                        })
                        ->exists();

                    if ($existsByExtId) {
                        continue;
                    }
                }

                // Evitar duplicar un mensaje con el mismo texto y dirección en una ventana cercana (±15 min)
                $sameTextNearTime = WhatsAppMessage::where('event_id', $event->id)
                    ->where('direction', $direction)
                    ->where('message', $text)
                    ->whereBetween('created_at', [
                        $msgDate->copy()->subMinutes(15),
                        $msgDate->copy()->addMinutes(15),
                    ])
                    ->exists();

                if ($sameTextNearTime) {
                    continue;
                }

                // Si es un mensaje largo (ej. plantilla comercial enviada desde la web sin external_id), evitar duplicarlo
                if (mb_strlen($text) > 30) {
                    $sameLongTextExists = WhatsAppMessage::where('event_id', $event->id)
                        ->where('direction', $direction)
                        ->where('message', $text)
                        ->exists();
                    if ($sameLongTextExists) {
                        continue;
                    }
                }

                $safeExternalId = $externalId;
                if ($safeExternalId && WhatsAppMessage::where('external_id', $safeExternalId)->exists()) {
                    $safeExternalId = $safeExternalId . '_ev' . $event->id;
                }

                $createdMsg = WhatsAppMessage::create([
                    'event_id' => $event->id,
                    'user_id' => $fromMe ? ($userId ?: auth()->id()) : ($event->client_id ?: null),
                    'phone' => $cleanPhone,
                    'sender_name' => $senderName,
                    'direction' => $direction,
                    'message' => $text,
                    'status' => $fromMe ? 'sent' : 'received',
                    'external_id' => $safeExternalId,
                    'brand' => $brandKey,
                    'payload' => $rawMsg,
                    'created_at' => $msgDate,
                    'updated_at' => $msgDate,
                ]);

                $importedCount++;
                if (!$fromMe) {
                    $newInboundMessages[] = $createdMsg;
                }
            }
        }

        // Si se han importado respuestas entrantes nuevas del cliente, anotarlas arriba del todo en Notas si no estaban
        if (!empty($newInboundMessages)) {
            try {
                $latestInbound = end($newInboundMessages);
                if ($latestInbound) {
                    $snippet = mb_substr(trim($latestInbound->message), 0, 150);
                    if (!str_contains((string)$event->notes, $snippet)) {
                        $dateFormatted = $latestInbound->created_at ? $latestInbound->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');
                        $noteLine = "[{$dateFormatted}] 💬 [WhatsApp de {$latestInbound->sender_name}]: \"{$snippet}\"";
                        $updateArr = [
                            'notes' => Event::prependNote($event->notes, $noteLine),
                        ];
                        if ($event->status === 'no_response' && (!$event->no_response_at || ($latestInbound->created_at && $latestInbound->created_at->gte($event->no_response_at)))) {
                            $updateArr['status'] = 'draft';
                            $updateArr['no_response_at'] = null;
                        }
                        $event->update($updateArr);
                    }
                }
            } catch (\Throwable $e) {}
        }

        if ($importedCount > 0) {
            return [
                'success' => true,
                'imported' => $importedCount,
                'message' => "✅ ¡Se han encontrado e importado {$importedCount} mensaje(s) de la conversación con +{$cleanPhone}!",
            ];
        }

        return [
            'success' => false,
            'imported' => 0,
            'message' => "ℹ️ No se encontraron mensajes nuevos en Evolution API para +{$cleanPhone} (o ya estaban todos en la conversación). Si la charla fue antes de conectar Evolution API, puedes pegarla justo debajo con «📋 Pegar / Añadir conversación».",
        ];
    }

    /**
     * Consulta el endpoint /chat/findMessages/{instance} de Evolution API soportando paginación v1 y v2
     * (y tanto si devuelve los mensajes en orden DESC como ASC).
     */
    protected static function fetchEvolutionMessages(
        string $baseUrl,
        string $apiKey,
        string $instance,
        array $where = [],
        int $count = 200
    ): array {
        $records = [];

        $payloadV2 = [
            'offset' => $count,
            'page' => 1,
        ];
        if (!empty($where)) {
            $payloadV2['where'] = $where;
        }

        $res = Http::withHeaders([
            'apikey' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(10)->post("{$baseUrl}/chat/findMessages/{$instance}", $payloadV2);

        if ($res->successful()) {
            $json = $res->json();
            $pageRecords = self::extractRecordsFromResponse($json);
            if (!empty($pageRecords)) {
                $records = array_merge($records, $pageRecords);
            }

            // Si hay más de 1 página y la página 1 trajo los más antiguos (orden ASC), pedir también la última página para tener los más recientes
            $totalPages = (int)($json['messages']['pages'] ?? ($json['pages'] ?? 1));
            if ($totalPages > 1 && count($pageRecords) >= 2) {
                $firstTs = (int)($pageRecords[0]['messageTimestamp'] ?? 0);
                $lastTs = (int)($pageRecords[count($pageRecords) - 1]['messageTimestamp'] ?? 0);
                if ($firstTs > 0 && $lastTs >= $firstTs) {
                    $payloadLastPage = $payloadV2;
                    $payloadLastPage['page'] = $totalPages;
                    $resLast = Http::withHeaders([
                        'apikey' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])->timeout(10)->post("{$baseUrl}/chat/findMessages/{$instance}", $payloadLastPage);
                    if ($resLast->successful()) {
                        $lastPageRecords = self::extractRecordsFromResponse($resLast->json());
                        if (!empty($lastPageRecords)) {
                            $records = array_merge($records, $lastPageRecords);
                        }
                    }
                }
            }
        }

        // Fallback si no devolvió nada con offset/page o si es Evolution API v1 (Mongoose)
        if (empty($records)) {
            $fallbackPayloads = [
                !empty($where) ? ['where' => $where, 'limit' => $count] : ['limit' => $count],
            ];
            if (isset($where['key']['remoteJid'])) {
                $fallbackPayloads[] = [
                    'where' => ['key.remoteJid' => $where['key']['remoteJid']],
                    'limit' => $count,
                ];
            }
            foreach ($fallbackPayloads as $fbPayload) {
                $resFb = Http::withHeaders([
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(10)->post("{$baseUrl}/chat/findMessages/{$instance}", $fbPayload);
                if ($resFb->successful()) {
                    $fbRecords = self::extractRecordsFromResponse($resFb->json());
                    if (!empty($fbRecords)) {
                        $records = array_merge($records, $fbRecords);
                        break;
                    }
                }
            }
        }

        return $records;
    }

    /**
     * Resuelve a qué Evento y teléfono corresponde un identificador @lid de WhatsApp cuando llega por Webhook en tiempo real.
     */
    public static function resolveEventAndPhoneFromLid(string $lidJid, ?string $instanceName = null, ?string $pushName = null): array
    {
        // 1. Buscar primero en whatsapp_messages locales si ya habíamos guardado algún mensaje con ese @lid en su payload
        try {
            $existingMsg = WhatsAppMessage::whereNotNull('event_id')
                ->where('payload', 'LIKE', '%' . $lidJid . '%')
                ->orderBy('id', 'desc')
                ->first();

            if ($existingMsg && $existingMsg->event) {
                return [
                    'event' => $existingMsg->event,
                    'phone' => $existingMsg->phone,
                ];
            }
        } catch (\Throwable $e) {}

        // 2. Consultar en Evolution API los mensajes recientes de ese @lid para ver si alguno contiene remoteJidAlt, un external_id nuestro o el saludo al nombre del cliente
        if (self::isEnabled()) {
            try {
                $baseUrl = self::getBaseUrl();
                $apiKey = self::getApiKey();
                $inst = $instanceName ?: self::getInstanceName('miguel');

                $lidMessages = self::fetchEvolutionMessages($baseUrl, $apiKey, $inst, [
                    'key' => ['remoteJid' => $lidJid],
                ], 50);

                foreach ($lidMessages as $m) {
                    if (!is_array($m)) continue;
                    $k = $m['key'] ?? [];

                    // 2a. ¿Tiene remoteJidAlt o senderPn con número de teléfono?
                    foreach ([$k['remoteJidAlt'] ?? null, $m['remoteJidAlt'] ?? null, $k['senderPn'] ?? null, $m['senderPn'] ?? null] as $alt) {
                        if (!empty($alt) && is_string($alt) && str_contains($alt, '@s.whatsapp.net')) {
                            $candidatePhone = WhatsAppTemplateService::cleanPhone(explode('@', $alt)[0]);
                            if (strlen($candidatePhone) >= 7) {
                                return [
                                    'event' => null,
                                    'phone' => $candidatePhone,
                                ];
                            }
                        }
                    }

                    // 2b. ¿Coincide el key.id con algún external_id que hayamos enviado desde un evento?
                    $mId = $k['id'] ?? ($m['id'] ?? null);
                    if (!empty($mId)) {
                        $matchedOutbound = WhatsAppMessage::where('external_id', $mId)
                            ->whereNotNull('event_id')
                            ->first();
                        if ($matchedOutbound && $matchedOutbound->event) {
                            return [
                                'event' => $matchedOutbound->event,
                                'phone' => $matchedOutbound->phone,
                            ];
                        }
                    }

                    // 2c. ¿Es un mensaje saliente nuestro que coincide con un mensaje guardado en whatsapp_messages?
                    $mFromMe = (bool)($k['fromMe'] ?? ($m['fromMe'] ?? false));
                    if ($mFromMe) {
                        $mText = self::extractTextFromEvolutionMessage($m);
                        if (!empty($mText) && mb_strlen($mText) >= 20) {
                            $matchedByText = WhatsAppMessage::where('direction', 'outbound')
                                ->whereNotNull('event_id')
                                ->where('message', $mText)
                                ->orderBy('id', 'desc')
                                ->first();
                            if ($matchedByText && $matchedByText->event) {
                                return [
                                    'event' => $matchedByText->event,
                                    'phone' => $matchedByText->phone,
                                ];
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 3. Si viene pushName (ej. "* Alvaro y ana"), comprobar si coincide con el nombre de algún cliente activo
        if (!empty($pushName)) {
            $cleanPush = trim((string)preg_replace('/\s+/', ' ', (string)preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower($pushName))));
            if (mb_strlen($cleanPush) >= 5 && !in_array($cleanPush, ['cliente', 'novios', 'pareja'])) {
                try {
                    $activeEvents = Event::with('client')
                        ->where('status', '!=', 'cancelled')
                        ->orderBy('event_date', 'desc')
                        ->limit(100)
                        ->get();

                    foreach ($activeEvents as $ev) {
                        $cName = $ev->client ? $ev->client->name : ($ev->partner_name ?: '');
                        $cleanCName = trim((string)preg_replace('/\s+/', ' ', (string)preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower((string)$cName))));
                        if (!empty($cleanCName) && $cleanCName === $cleanPush) {
                            $evPhone = $ev->client ? ($ev->client->phone ?: $ev->partner_phone) : $ev->partner_phone;
                            return [
                                'event' => $ev,
                                'phone' => WhatsAppTemplateService::cleanPhone((string)$evPhone),
                            ];
                        }
                    }
                } catch (\Throwable $e) {}
            }
        }

        return ['event' => null, 'phone' => null];
    }

    /**
     * Extrae el array de mensajes devuelto por distintas versiones de Evolution API (v1 / v2)
     */
    protected static function extractRecordsFromResponse($json): array
    {
        if (!is_array($json)) {
            return [];
        }
        if (isset($json['messages']['records']) && is_array($json['messages']['records'])) {
            return $json['messages']['records'];
        }
        if (isset($json['records']) && is_array($json['records'])) {
            return $json['records'];
        }
        if (isset($json['messages']) && is_array($json['messages']) && array_is_list($json['messages'])) {
            return $json['messages'];
        }
        if (isset($json['data']) && is_array($json['data']) && array_is_list($json['data'])) {
            return $json['data'];
        }
        if (array_is_list($json)) {
            return $json;
        }
        return [];
    }

    /**
     * Extrae el texto legible de un objeto de mensaje de Evolution API / Baileys
     */
    protected static function extractTextFromEvolutionMessage(array $rawMsg): string
    {
        $m = $rawMsg['message'] ?? [];
        if (!is_array($m)) {
            return is_string($m) ? $m : '';
        }

        if (isset($m['ephemeralMessage']['message']) && is_array($m['ephemeralMessage']['message'])) {
            $m = $m['ephemeralMessage']['message'];
        }
        if (isset($m['viewOnceMessage']['message']) && is_array($m['viewOnceMessage']['message'])) {
            $m = $m['viewOnceMessage']['message'];
        }
        if (isset($m['viewOnceMessageV2']['message']) && is_array($m['viewOnceMessageV2']['message'])) {
            $m = $m['viewOnceMessageV2']['message'];
        }

        $text = $m['conversation']
            ?? ($m['extendedTextMessage']['text']
            ?? ($m['imageMessage']['caption']
            ?? ($m['videoMessage']['caption']
            ?? ($m['documentMessage']['caption']
            ?? ($m['documentWithCaptionMessage']['message']['documentMessage']['caption']
            ?? '')))));

        if (!empty(trim((string)$text))) {
            return trim((string)$text);
        }

        if (isset($m['imageMessage'])) {
            return '📷 [Imagen adjunta]';
        }
        if (isset($m['videoMessage'])) {
            return '🎥 [Vídeo adjunto]';
        }
        if (isset($m['audioMessage'])) {
            return '🎵 [Nota de voz / Audio]';
        }
        if (isset($m['documentMessage'])) {
            $fileName = $m['documentMessage']['fileName'] ?? 'archivo';
            return "📄 [Documento: {$fileName}]";
        }

        return '';
    }

    /**
     * Permite pegar una conversación copiada de WhatsApp Web / Móvil (con múltiples mensajes y fechas)
     * o un mensaje individual anterior, y lo convierte en burbujas reales dentro de Conversación Registrada.
     */
    public static function importPastedConversation(
        Event $event,
        string $rawText,
        string $defaultDirection = 'inbound',
        ?string $defaultDate = null,
        ?string $phone = null,
        ?string $brand = null,
        ?int $userId = null
    ): int {
        $rawText = trim($rawText);
        if (empty($rawText)) {
            return 0;
        }

        $cleanPhone = WhatsAppTemplateService::cleanPhone($phone ?: ($event->client ? $event->client->phone : ($event->partner_phone ?: '')));
        $clientName = $event->client ? $event->client->name : ($event->partner_name ?: 'Cliente');
        $effectiveBrand = $brand ?: ($event->brand_clean ?: 'miguel');

        $baseDate = now();
        if (!empty($defaultDate)) {
            try {
                $baseDate = \Carbon\Carbon::parse($defaultDate);
            } catch (\Throwable $e) {}
        }

        // Detectar si el texto pegado tiene formato de historial de WhatsApp:
        // Ej. "[15/10/2026, 18:30:12] Henar: Hola..." o "[18:30, 15/10/2026] Miguel: ¡Hola!..." o "15/10/26, 18:30 - Henar: Hola..."
        $lines = preg_split('/\r\n|\r|\n/', $rawText);
        $parsedBlocks = [];

        $patternBracketDateFirst = '/^\[(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})[,\s]+(\d{1,2}:\d{2}(?::\d{2})?)\]\s*([^:]+):\s*(.*)$/u';
        $patternBracketTimeFirst = '/^\[(\d{1,2}:\d{2}(?::\d{2})?)[,\s]+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\]\s*([^:]+):\s*(.*)$/u';
        $patternDashDateFirst = '/^(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})[,\s]+(\d{1,2}:\d{2}(?::\d{2})?)\s*-\s*([^:]+):\s*(.*)$/u';

        foreach ($lines as $line) {
            if (preg_match($patternBracketDateFirst, $line, $m)) {
                $parsedBlocks[] = [
                    'date_str' => $m[1],
                    'time_str' => $m[2],
                    'author' => trim($m[3]),
                    'text' => $m[4],
                ];
            } elseif (preg_match($patternBracketTimeFirst, $line, $m)) {
                $parsedBlocks[] = [
                    'date_str' => $m[2],
                    'time_str' => $m[1],
                    'author' => trim($m[3]),
                    'text' => $m[4],
                ];
            } elseif (preg_match($patternDashDateFirst, $line, $m)) {
                $parsedBlocks[] = [
                    'date_str' => $m[1],
                    'time_str' => $m[2],
                    'author' => trim($m[3]),
                    'text' => $m[4],
                ];
            } elseif (!empty($parsedBlocks)) {
                // Línea de continuación del mensaje anterior
                $lastIndex = count($parsedBlocks) - 1;
                $parsedBlocks[$lastIndex]['text'] .= "\n" . $line;
            }
        }

        // Si no tenía cabeceras de fecha/hora de WhatsApp, guardarlo como un mensaje directo con la dirección elegida
        if (empty($parsedBlocks)) {
            WhatsAppMessage::create([
                'event_id' => $event->id,
                'user_id' => $defaultDirection === 'outbound' ? ($userId ?: auth()->id()) : ($event->client_id ?: null),
                'phone' => $cleanPhone ?: 'manual',
                'sender_name' => $defaultDirection === 'outbound' ? 'Nosotros' : $clientName,
                'direction' => $defaultDirection === 'outbound' ? 'outbound' : 'inbound',
                'message' => $rawText,
                'status' => $defaultDirection === 'outbound' ? 'sent' : 'received',
                'brand' => $effectiveBrand,
                'created_at' => $baseDate,
                'updated_at' => $baseDate,
            ]);
            return 1;
        }

        // Palabras clave para identificar si el autor de una línea del chat pegado somos nosotros (staff)
        $ourKeywords = ['miguel', 'fran', 'ezequiel', 'nunez', 'núñez', 'javnx', 'leugim', 'son', 'tú', 'tu', 'yo', 'nosotros'];
        $imported = 0;

        foreach ($parsedBlocks as $idx => $block) {
            $text = trim($block['text']);
            if (empty($text)) {
                continue;
            }

            $authorLower = mb_strtolower($block['author']);
            $isOutbound = false;
            foreach ($ourKeywords as $kw) {
                if (str_contains($authorLower, $kw)) {
                    $isOutbound = true;
                    break;
                }
            }

            $msgDate = $baseDate->copy()->addSeconds($idx);
            try {
                $dParts = preg_split('/[\/\-]/', $block['date_str']);
                if (count($dParts) === 3) {
                    $day = (int)$dParts[0];
                    $month = (int)$dParts[1];
                    $year = (int)$dParts[2];
                    if ($year < 100) {
                        $year += 2000;
                    }
                    $tParts = explode(':', $block['time_str']);
                    $hour = (int)($tParts[0] ?? 12);
                    $minute = (int)($tParts[1] ?? 0);
                    $second = (int)($tParts[2] ?? $idx);
                    $msgDate = \Carbon\Carbon::create($year, $month, $day, $hour, $minute, min(59, $second));
                }
            } catch (\Throwable $e) {}

            $direction = $isOutbound ? 'outbound' : 'inbound';

            $exists = WhatsAppMessage::where('event_id', $event->id)
                ->where('direction', $direction)
                ->where('message', $text)
                ->exists();

            if ($exists) {
                continue;
            }

            WhatsAppMessage::create([
                'event_id' => $event->id,
                'user_id' => $isOutbound ? ($userId ?: auth()->id()) : ($event->client_id ?: null),
                'phone' => $cleanPhone ?: 'manual',
                'sender_name' => $isOutbound ? $block['author'] : ($block['author'] ?: $clientName),
                'direction' => $direction,
                'message' => $text,
                'status' => $isOutbound ? 'sent' : 'received',
                'brand' => $effectiveBrand,
                'created_at' => $msgDate,
                'updated_at' => $msgDate,
            ]);
            $imported++;
        }

        return $imported;
    }
}

