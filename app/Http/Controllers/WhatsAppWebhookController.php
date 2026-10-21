<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\AppNotificationService;
use App\Services\EvolutionWhatsAppService;
use App\Services\WhatsAppTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WhatsAppWebhookController extends Controller
{
    /**
     * Webhook para recibir eventos en tiempo real desde Evolution API / Baileys / WhatsApp Gateway.
     * URL: POST /api/whatsapp/webhook
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Validar Token de seguridad si está configurado
        $configuredToken = Setting::get('whatsapp_webhook_token');
        if (!empty($configuredToken)) {
            $receivedToken = $request->header('X-Webhook-Token') 
                ?: $request->header('apikey') 
                ?: $request->input('token');

            if ($receivedToken !== $configuredToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado: Token de webhook inválido.',
                ], 401);
            }
        }

        $payload = $request->all();
        $eventType = strtolower((string)($payload['event'] ?? ($payload['type'] ?? '')));

        // Ignorar eventos que no sean mensajes entrantes/salientes (ej: qrcode.updated, connection.update)
        if (!in_array($eventType, [
            'messages.upsert',
            'messages_upsert',
            'messages-upsert',
            'message.create',
            'send.message',
            'send_message',
            'send-message',
            '',
        ])) {
            return response()->json(['success' => true, 'ignored' => true, 'event' => $eventType]);
        }

        $data = $payload['data'] ?? $payload;
        if (isset($data['messages'][0]) && is_array($data['messages'][0])) {
            $data = $data['messages'][0];
        }

        $key = $data['key'] ?? [];
        $fromMe = (bool)($key['fromMe'] ?? ($data['fromMe'] ?? false));
        $remoteJid = (string)($key['remoteJid'] ?? ($data['remoteJid'] ?? ''));
        $remoteJidAlt = (string)($key['remoteJidAlt'] ?? ($data['remoteJidAlt'] ?? ($key['senderPn'] ?? ($data['senderPn'] ?? ($key['participantAlt'] ?? ($data['participantAlt'] ?? ''))))));
        $externalId = $key['id'] ?? ($data['id'] ?? null);

        // Evitar procesar estados de WhatsApp o mensajes de grupos si no aplican
        if (str_contains($remoteJid, '@broadcast') || str_contains($remoteJid, '@g.us')) {
            return response()->json(['success' => true, 'ignored' => 'group_or_status']);
        }

        // Extraer texto del mensaje (soportando ephemeralMessage / viewOnceMessage / extendedTextMessage)
        $msgObj = $data['message'] ?? [];
        if (isset($msgObj['ephemeralMessage']['message']) && is_array($msgObj['ephemeralMessage']['message'])) {
            $msgObj = $msgObj['ephemeralMessage']['message'];
        }
        if (isset($msgObj['viewOnceMessage']['message']) && is_array($msgObj['viewOnceMessage']['message'])) {
            $msgObj = $msgObj['viewOnceMessage']['message'];
        }
        if (isset($msgObj['viewOnceMessageV2']['message']) && is_array($msgObj['viewOnceMessageV2']['message'])) {
            $msgObj = $msgObj['viewOnceMessageV2']['message'];
        }

        $text = $msgObj['conversation'] 
            ?? ($msgObj['extendedTextMessage']['text'] 
            ?? ($msgObj['imageMessage']['caption'] 
            ?? ($msgObj['videoMessage']['caption'] 
            ?? ($msgObj['documentMessage']['caption']
            ?? ($msgObj['documentWithCaptionMessage']['message']['documentMessage']['caption']
            ?? '')))));

        if (empty(trim((string)$text))) {
            if (isset($msgObj['imageMessage'])) {
                $text = '📷 [Imagen adjunta]';
            } elseif (isset($msgObj['videoMessage'])) {
                $text = '🎥 [Vídeo adjunto]';
            } elseif (isset($msgObj['audioMessage'])) {
                $text = '🎵 [Nota de voz / Audio]';
            } elseif (isset($msgObj['documentMessage'])) {
                $fileName = $msgObj['documentMessage']['fileName'] ?? 'archivo';
                $text = "📄 [Documento: {$fileName}]";
            }
        }

        if (empty(trim((string)$text))) {
            return response()->json(['success' => true, 'ignored' => 'non_text_or_empty']);
        }

        $senderName = $data['pushName'] ?? ($fromMe ? 'Nosotros' : 'Cliente');
        $instanceName = $payload['instance'] ?? 'default';

        // Determinar marca según la instancia
        $brand = 'nunez_and_son';
        if (str_contains(strtolower($instanceName), 'javnx')) {
            $brand = 'javnx';
        } elseif (str_contains(strtolower($instanceName), 'leugim')) {
            $brand = 'mago_leugim';
        }

        // Extraer número de teléfono limpio (priorizando remoteJidAlt / senderPn si remoteJid es un @lid de Meta/WhatsApp)
        $isLid = str_ends_with($remoteJid, '@lid');
        $rawPhoneSource = $remoteJid;
        if ($isLid && !empty($remoteJidAlt) && str_contains($remoteJidAlt, '@s.whatsapp.net')) {
            $rawPhoneSource = $remoteJidAlt;
        }

        $rawPhone = explode('@', $rawPhoneSource)[0];
        // Quitar sufijo de dispositivo si viniera como 34699212825:12
        $rawPhone = explode(':', $rawPhone)[0];
        $cleanPhone = WhatsAppTemplateService::cleanPhone($rawPhone);

        $event = null;

        // Si no es un @lid (o si ya obtuvimos el teléfono real vía remoteJidAlt), buscar evento por teléfono
        if (!$isLid || (!empty($remoteJidAlt) && str_contains($remoteJidAlt, '@s.whatsapp.net'))) {
            if (!empty($cleanPhone)) {
                if ($this->isInternalNotificationMessage($cleanPhone, $text, $brand)) {
                    return response()->json(['success' => true, 'ignored' => 'internal_team_or_alert_phone']);
                }
                $event = $this->findMatchingEvent($cleanPhone);
            }
        }

        // Si el mensaje viene con @lid (Linked Identity de campaña Meta/Instagram o WhatsApp moderno) y aún no hemos resuelto el evento:
        if (!$event) {
            // A) Comprobar si cita/responde a un mensaje nuestro previo (contextInfo.stanzaId)
            $stanzaId = $msgObj['extendedTextMessage']['contextInfo']['stanzaId']
                ?? ($data['contextInfo']['stanzaId'] ?? null);
            if (!empty($stanzaId)) {
                $quotedMsg = WhatsAppMessage::where('external_id', $stanzaId)
                    ->whereNotNull('event_id')
                    ->first();
                if ($quotedMsg && $quotedMsg->event) {
                    $event = $quotedMsg->event;
                    $cleanPhone = $quotedMsg->phone ?: $cleanPhone;
                }
            }

            // B) Resolver el @lid cruzando con payloads previos, mensajes en Evolution API o nombre del cliente
            if (!$event && !empty($remoteJid)) {
                $resolved = EvolutionWhatsAppService::resolveEventAndPhoneFromLid($remoteJid, $instanceName, $senderName);
                if (!empty($resolved['phone'])) {
                    $cleanPhone = $resolved['phone'];
                }
                if (!empty($resolved['event'])) {
                    $event = $resolved['event'];
                } elseif (!empty($cleanPhone)) {
                    if ($this->isInternalNotificationMessage($cleanPhone, $text, $brand)) {
                        return response()->json(['success' => true, 'ignored' => 'internal_team_or_alert_phone']);
                    }
                    $event = $this->findMatchingEvent($cleanPhone);
                }
            }
        }

        if (empty($cleanPhone) && $event) {
            $evPhone = $event->client ? ($event->client->phone ?: $event->partner_phone) : $event->partner_phone;
            $cleanPhone = WhatsAppTemplateService::cleanPhone((string)$evPhone);
        }

        if (empty($cleanPhone)) {
            return response()->json(['success' => true, 'ignored' => 'empty_phone']);
        }

        // 2. PRIVACIDAD: Ignorar mensajes entre teléfonos internos del equipo (socios, DJs, admins)
        if ($this->isInternalNotificationMessage($cleanPhone, $text, $brand)) {
            return response()->json(['success' => true, 'ignored' => 'internal_team_or_alert_phone']);
        }

        // REGLA DE PRIVACIDAD ESTRICTA:
        // Como los números de WhatsApp conectados son personales/profesionales, si el teléfono
        // NO pertenece a ningún cliente/pareja con evento registrado, NO se guarda en la BD ni se notifica.
        if (!$event) {
            return response()->json([
                'success' => true,
                'ignored' => 'personal_contact_not_in_events',
            ]);
        }

        $user = $event->client ?: $this->findMatchingUser($cleanPhone);

        // Evitar duplicados si el webhook se dispara múltiples veces con el mismo externalId
        if ($externalId) {
            $alreadyExists = WhatsAppMessage::where('event_id', $event->id)
                ->where(function ($q) use ($externalId, $event) {
                    $q->where('external_id', $externalId)
                      ->orWhere('external_id', $externalId . '_ev' . $event->id);
                })
                ->exists();
            if ($alreadyExists) {
                return response()->json(['success' => true, 'duplicate' => true]);
            }
        }

        // Si es un mensaje saliente enviado desde la propia app hace unos segundos, actualizar su payload/external_id sin duplicar la burbuja
        if ($fromMe) {
            $recentOutbound = WhatsAppMessage::where('event_id', $event->id)
                ->where('direction', 'outbound')
                ->where('message', $text)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->orderBy('id', 'desc')
                ->first();

            if ($recentOutbound) {
                $updates = ['payload' => $payload];
                if (empty($recentOutbound->external_id) && !empty($externalId)) {
                    $updates['external_id'] = $externalId;
                }
                $recentOutbound->update($updates);
                return response()->json([
                    'success' => true,
                    'updated_existing' => true,
                    'message_id' => $recentOutbound->id,
                    'event_id' => $event->id,
                ]);
            }
        }

        $direction = $fromMe ? 'outbound' : 'inbound';
        $effectiveBrand = $event->brand_clean ?: $brand;

        $safeExternalId = $externalId;
        if ($safeExternalId && WhatsAppMessage::where('external_id', $safeExternalId)->exists()) {
            $safeExternalId = $safeExternalId . '_ev' . $event->id;
        }

        $record = WhatsAppMessage::create([
            'event_id' => $event->id,
            'user_id' => $user ? $user->id : null,
            'phone' => $cleanPhone,
            'sender_name' => $senderName,
            'direction' => $direction,
            'message' => $text,
            'status' => $fromMe ? 'sent' : 'received',
            'external_id' => $safeExternalId,
            'brand' => $effectiveBrand,
            'payload' => $payload,
        ]);

        // 4. AUTOMATIZACIÓN INTELIGENTE: Si los novios/clientes responden y estaban en seguimiento ("no_response")
        $reactivated = false;
        if (!$fromMe && $event->status === 'no_response') {
            $noteEntry = "[" . now()->format('d/m/Y H:i') . "] 💬 [WhatsApp Entrante de {$senderName}]: \"" . mb_substr($text, 0, 150) . "\" -> Reactivado a Borrador automáticamente.";
            $event->update([
                'status' => 'draft', // Vuelve automáticamente a Borrador activo
                'no_response_at' => null, // Resetea el reloj de sin respuesta
                'notes' => Event::prependNote($event->notes, $noteEntry),
            ]);
            $reactivated = true;
        } elseif (!$fromMe) {
            // Registrar arriba del todo en notas internas para que conste en el historial del evento
            $noteEntry = "[" . now()->format('d/m/Y H:i') . "] 💬 [WhatsApp de {$senderName}]: \"" . mb_substr($text, 0, 150) . "\"";
            $event->update([
                'notes' => Event::prependNote($event->notes, $noteEntry),
            ]);
        }

        // 5. NOTIFICACIONES MULTICANAL SOLO CUANDO RESPONDEN LOS NOVIOS / CLIENTES DE UN EVENTO ACTIVO
        if (!$fromMe) {
            $displayName = ($event->client && $event->client->name) ? $event->client->name : (($user && $user->name) ? $user->name : $senderName);
            $eventTitle = $event->name;
            $eventUrl = url("/admin/events/{$event->id}");
            $snippet = mb_substr(trim($text), 0, 140);

            // 5a. Notificación en la App (Campana + Push Navegador/PWA + Toast)
            try {
                AppNotificationService::push(
                    'whatsapp_inbound',
                    "💬 Respuesta de {$displayName}",
                    "[{$eventTitle}] \"{$snippet}\"",
                    $eventUrl,
                    $event->id,
                    $effectiveBrand
                );
            } catch (\Throwable $e) {
                Log::warning("No se pudo registrar notificación en App por WhatsApp entrante: " . $e->getMessage());
            }

            // 5b. Alerta de WhatsApp a los administradores / socios
            try {
                if (EvolutionWhatsAppService::isEnabled()) {
                    $waAlert = "💬 *¡RESPUESTA DE CLIENTE RECIBIDA!*\n\n"
                        . "👤 *Novios/Cliente:* {$displayName} (+{$cleanPhone})\n"
                        . "🎉 *Evento:* {$eventTitle}\n"
                        . ($reactivated ? "✅ *Estado:* Reactivado automáticamente a BORRADOR\n" : "")
                        . "✉️ *Mensaje:* \"{$snippet}\"\n\n"
                        . "🔗 *Ficha:* {$eventUrl}";
                    EvolutionWhatsAppService::notifyAdmins($waAlert, $effectiveBrand);
                }
            } catch (\Throwable $e) {
                Log::warning("No se pudo enviar alerta WhatsApp a socios por respuesta de cliente: " . $e->getMessage());
            }

            // 5c. Alerta por Email a todos los administradores (envío individual tolerante a fallos)
            try {
                $djEmail = ($event->dj && !empty($event->dj->email)) ? $event->dj->email : null;
                AppNotificationService::sendAlertEmailToAdmins(
                    "💬 Respuesta WhatsApp de {$displayName} — {$eventTitle}",
                    "💬 Nuevo WhatsApp recibido de {$displayName}",
                    [
                        'Evento' => $eventTitle,
                        'Cliente' => "{$displayName} (+{$cleanPhone})",
                        'Estado' => $reactivated ? "Reactivado automáticamente de 'Sin Respuesta' a 'Borrador'" : null,
                        'Fecha y Hora' => now()->format('d/m/Y H:i'),
                        'Mensaje recibido' => $text,
                    ],
                    $eventUrl,
                    'Ver Conversación y Responder en la App',
                    $effectiveBrand,
                    '#059669',
                    $djEmail
                );
            } catch (\Throwable $e) {
                Log::warning("No se pudo enviar email de aviso por WhatsApp entrante: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message_id' => $record->id,
            'event_id' => $event->id,
            'client_phone' => $cleanPhone,
            'direction' => $direction,
        ]);
    }

    /**
     * Evita bucles y filtra cualquier mensaje que provenga de los teléfonos propios del equipo
     * (socios, administradores, DJs, asistentes) o ecos de alertas internas.
     */
    protected function isInternalNotificationMessage(string $cleanPhone, string $text, string $brand): bool
    {
        if (
            str_starts_with($text, '💬 *¡NUEVO WHATSAPP RECIBIDO!*') ||
            str_starts_with($text, '💬 *¡RESPUESTA DE CLIENTE RECIBIDA!*') ||
            str_starts_with($text, '📥 *¡NUEVO LEAD ENTRANTE!*') ||
            str_starts_with($text, '💶 *¡NUEVA SOLICITUD DE PRESUPUESTO!*') ||
            str_starts_with($text, '✅ *¡PRESUPUESTO ACEPTADO ONLINE!*') ||
            str_starts_with($text, '❌ *PRESUPUESTO RECHAZADO ONLINE*') ||
            str_starts_with($text, '🎉 *¡NUEVO CONTRATO FIRMADO!*') ||
            str_starts_with($text, '🎵 *¡CUESTIONARIO MUSICAL RECIBIDO!*') ||
            str_starts_with($text, '📧 *¡RESPUESTA POR EMAIL RECIBIDA!*')
        ) {
            return true;
        }

        $national = strlen($cleanPhone) > 9 ? substr($cleanPhone, -9) : $cleanPhone;

        // Teléfonos de administradores / marcas configurados
        $adminPhones = Setting::getAdminNotificationPhones($brand);
        foreach ($adminPhones as $adminPhone) {
            $cleanAdmin = WhatsAppTemplateService::cleanPhone((string)$adminPhone);
            $adminNational = strlen($cleanAdmin) > 9 ? substr($cleanAdmin, -9) : $cleanAdmin;
            if (!empty($cleanAdmin) && ($cleanAdmin === $cleanPhone || ($adminNational && $adminNational === $national))) {
                return true;
            }
        }

        // Teléfonos de usuarios internos del equipo (admin, dj, assistant)
        try {
            if (!empty($national) && strlen($national) >= 7) {
                $staffPhones = User::whereIn('role', ['admin', 'dj', 'assistant'])
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->pluck('phone');

                foreach ($staffPhones as $staffPhone) {
                    $cleanStaff = WhatsAppTemplateService::cleanPhone((string)$staffPhone);
                    $staffNational = strlen($cleanStaff) > 9 ? substr($cleanStaff, -9) : $cleanStaff;
                    if (!empty($cleanStaff) && ($cleanStaff === $cleanPhone || ($staffNational && $staffNational === $national))) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {}

        return false;
    }

    /**
     * Busca un evento cuyos novios/clientes coincidan con el número de teléfono (priorizando eventos activos)
     */
    protected function findMatchingEvent(string $cleanPhone): ?Event
    {
        $national = strlen($cleanPhone) > 9 ? substr($cleanPhone, -9) : $cleanPhone;
        if (empty($national) || strlen($national) < 7) {
            return null;
        }

        // Búsqueda directa en SQL + respaldo normalizado sin espacios/guiones (priorizando eventos activos frente a cancelados/rechazados)
        $directMatch = Event::where(function ($query) use ($cleanPhone, $national) {
                $query->whereHas('client', function ($q) use ($cleanPhone, $national) {
                    $q->whereNotNull('phone')
                      ->where('phone', '!=', '')
                      ->where(function ($sq) use ($cleanPhone, $national) {
                          $sq->where('phone', 'LIKE', "%{$national}%")
                             ->orWhere('phone', 'LIKE', "%{$cleanPhone}%")
                             ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') LIKE ?", ["%{$national}%"]);
                      });
                })
                ->orWhere(function ($q) use ($cleanPhone, $national) {
                    $q->whereNotNull('partner_phone')
                      ->where('partner_phone', '!=', '')
                      ->where(function ($sq) use ($cleanPhone, $national) {
                          $sq->where('partner_phone', 'LIKE', "%{$national}%")
                             ->orWhere('partner_phone', 'LIKE', "%{$cleanPhone}%")
                             ->orWhereRaw("REPLACE(REPLACE(REPLACE(partner_phone, ' ', ''), '-', ''), '+', '') LIKE ?", ["%{$national}%"]);
                      });
                });
            })
            ->orderByRaw("CASE WHEN status IN ('cancelled', 'rejected') THEN 1 ELSE 0 END ASC")
            ->orderBy('event_date', 'desc')
            ->first();

        return $directMatch;
    }

    /**
     * Busca el usuario cliente correspondiente
     */
    protected function findMatchingUser(string $cleanPhone): ?User
    {
        $national = strlen($cleanPhone) > 9 ? substr($cleanPhone, -9) : $cleanPhone;
        if (empty($national) || strlen($national) < 7) {
            return null;
        }

        return User::where('role', 'client')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where(function ($q) use ($cleanPhone, $national) {
                $q->where('phone', 'LIKE', "%{$national}%")
                  ->orWhere('phone', 'LIKE', "%{$cleanPhone}%");
            })
            ->first();
    }
}
