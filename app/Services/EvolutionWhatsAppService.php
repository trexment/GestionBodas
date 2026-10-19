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

    /**
     * Busca la conversación previa con un número de teléfono en Evolution API (y en la BD local)
     * e importa todos los mensajes anteriores dentro del evento indicado.
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
        }

        // Posibles JIDs para el teléfono en WhatsApp
        $candidateJids = array_values(array_unique(array_filter([
            "{$cleanPhone}@s.whatsapp.net",
            strlen($national) === 9 ? "34{$national}@s.whatsapp.net" : null,
            "{$national}@s.whatsapp.net",
        ])));

        $apiErrors = [];

        foreach ($instancesToSearch as $instance => $brandKey) {
            $jidsForInstance = $candidateJids;

            // Intentar también descubrir si el contacto tiene algún JID alternativo o chat registrado en esta instancia
            try {
                $chatsRes = Http::withHeaders([
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(8)->post("{$baseUrl}/chat/findChats/{$instance}", [
                    'where' => [
                        'remoteJid' => $candidateJids[0],
                    ],
                ]);
                if ($chatsRes->successful()) {
                    $chatsData = $chatsRes->json();
                    if (is_array($chatsData)) {
                        foreach ($chatsData as $chatItem) {
                            $cJid = $chatItem['remoteJid'] ?? ($chatItem['id'] ?? '');
                            if (!empty($cJid) && str_contains($cJid, $national) && !in_array($cJid, $jidsForInstance)) {
                                $jidsForInstance[] = $cJid;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Continuar silenciosamente con findMessages
            }

            foreach ($jidsForInstance as $jid) {
                try {
                    $res = Http::withHeaders([
                        'apikey' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])->timeout(12)->post("{$baseUrl}/chat/findMessages/{$instance}", [
                        'where' => [
                            'key' => [
                                'remoteJid' => $jid,
                            ],
                        ],
                        'limit' => 200,
                    ]);

                    if (!$res->successful()) {
                        continue;
                    }

                    $records = self::extractRecordsFromResponse($res->json());
                    if (empty($records)) {
                        continue;
                    }

                    foreach ($records as $rawMsg) {
                        if (!is_array($rawMsg)) {
                            continue;
                        }

                        $key = $rawMsg['key'] ?? [];
                        $remoteJid = (string)($key['remoteJid'] ?? ($rawMsg['remoteJid'] ?? ''));
                        $remoteJidAlt = (string)($key['remoteJidAlt'] ?? ($rawMsg['remoteJidAlt'] ?? ''));

                        // Seguridad y privacidad: verificar que el mensaje pertenece realmente a este número
                        if (!str_contains($remoteJid, $national) && !str_contains($remoteJidAlt, $national)) {
                            continue;
                        }

                        // Ignorar estados o grupos
                        if (str_contains($remoteJid, '@broadcast') || str_contains($remoteJid, '@g.us')) {
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
                            : ((!empty($pushName) && strtolower($pushName) !== 'voce' && strtolower($pushName) !== 'tú') ? $pushName : $clientName);

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

                        // Evitar duplicados en el evento
                        $alreadyExists = WhatsAppMessage::where('event_id', $event->id)
                            ->where(function ($q) use ($externalId, $direction, $text, $msgDate) {
                                if (!empty($externalId)) {
                                    $q->where('external_id', $externalId);
                                } else {
                                    $q->where('direction', $direction)
                                      ->where('message', $text)
                                      ->whereBetween('created_at', [
                                          $msgDate->copy()->subMinutes(2),
                                          $msgDate->copy()->addMinutes(2),
                                      ]);
                                }
                            })
                            ->exists();

                        if ($alreadyExists) {
                            continue;
                        }

                        // También evitar duplicar un mensaje que ya se envió desde la web sin external_id pero con el mismo texto exacto
                        $sameTextExists = WhatsAppMessage::where('event_id', $event->id)
                            ->where('direction', $direction)
                            ->where('message', $text)
                            ->exists();

                        if ($sameTextExists) {
                            continue;
                        }

                        // Si el external_id ya estaba en otro evento, sufijarlo para no chocar con índices únicos si los hubiera
                        $safeExternalId = $externalId;
                        if ($safeExternalId && WhatsAppMessage::where('external_id', $safeExternalId)->exists()) {
                            $safeExternalId = $safeExternalId . '_ev' . $event->id;
                        }

                        WhatsAppMessage::create([
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
                    }
                } catch (\Throwable $e) {
                    $apiErrors[] = $e->getMessage();
                    Log::warning("Error consultando findMessages en {$instance} para {$jid}: " . $e->getMessage());
                }
            }
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
            'message' => "ℹ️ No se encontraron mensajes nuevos en Evolution API para +{$cleanPhone} (o ya estaban todos en la conversación). Si la charla fue antes de conectar Evolution API, puedes pegarla justo debajo con «📋 Pegar / Añadir Mensajes».",
        ];
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

