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
        $eventType = $payload['event'] ?? ($payload['type'] ?? '');

        // Ignorar eventos que no sean mensajes entrantes/salientes (ej: qrcode.updated, connection.update)
        if (!in_array($eventType, ['messages.upsert', 'MESSAGES_UPSERT', 'message.create', ''])) {
            return response()->json(['success' => true, 'ignored' => true, 'event' => $eventType]);
        }

        $data = $payload['data'] ?? $payload;
        $key = $data['key'] ?? [];
        $fromMe = (bool)($key['fromMe'] ?? false);
        $remoteJid = $key['remoteJid'] ?? '';
        $externalId = $key['id'] ?? null;

        // Evitar procesar estados de WhatsApp o mensajes de grupos si no aplican
        if (str_contains($remoteJid, '@broadcast') || str_contains($remoteJid, '@g.us')) {
            return response()->json(['success' => true, 'ignored' => 'group_or_status']);
        }

        // Extraer número de teléfono limpio
        $rawPhone = explode('@', $remoteJid)[0];
        $cleanPhone = WhatsAppTemplateService::cleanPhone($rawPhone);
        if (empty($cleanPhone)) {
            return response()->json(['success' => true, 'ignored' => 'empty_phone']);
        }

        // Extraer texto del mensaje
        $msgObj = $data['message'] ?? [];
        $text = $msgObj['conversation'] 
            ?? ($msgObj['extendedTextMessage']['text'] 
            ?? ($msgObj['imageMessage']['caption'] 
            ?? ($msgObj['videoMessage']['caption'] 
            ?? '')));

        if (empty(trim($text))) {
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

        // 2. PRIVACIDAD: Ignorar mensajes entre teléfonos internos del equipo (socios, DJs, admins)
        if ($this->isInternalNotificationMessage($cleanPhone, $text, $brand)) {
            return response()->json(['success' => true, 'ignored' => 'internal_team_or_alert_phone']);
        }

        // 3. Asociar únicamente con Eventos reales de clientes/novios en la base de datos
        $event = $this->findMatchingEvent($cleanPhone);

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
            $alreadyExists = WhatsAppMessage::where('external_id', $externalId)->exists();
            if ($alreadyExists) {
                return response()->json(['success' => true, 'duplicate' => true]);
            }
        }

        $direction = $fromMe ? 'outbound' : 'inbound';
        $effectiveBrand = $event->brand_clean ?: $brand;

        $record = WhatsAppMessage::create([
            'event_id' => $event->id,
            'user_id' => $user ? $user->id : null,
            'phone' => $cleanPhone,
            'sender_name' => $senderName,
            'direction' => $direction,
            'message' => $text,
            'status' => $fromMe ? 'sent' : 'received',
            'external_id' => $externalId,
            'brand' => $effectiveBrand,
            'payload' => $payload,
        ]);

        // 4. AUTOMATIZACIÓN INTELIGENTE: Si los novios/clientes responden y estaban en seguimiento ("no_response")
        $reactivated = false;
        if (!$fromMe && $event->status === 'no_response') {
            $event->update([
                'status' => 'draft', // Vuelve automáticamente a Borrador activo
                'no_response_at' => null, // Resetea el reloj de sin respuesta
                'notes' => ($event->notes ? $event->notes . "\n" : "") 
                    . "[" . now()->format('d/m/Y H:i') . "] 💬 [WhatsApp Entrante de {$senderName}]: \"" . mb_substr($text, 0, 150) . "\" -> Reactivado a Borrador automáticamente.",
            ]);
            $reactivated = true;
        } elseif (!$fromMe) {
            // Registrar en notas internas para que conste en el historial del evento
            $event->update([
                'notes' => ($event->notes ? $event->notes . "\n" : "") 
                    . "[" . now()->format('d/m/Y H:i') . "] 💬 [WhatsApp de {$senderName}]: \"" . mb_substr($text, 0, 150) . "\"",
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

            // 5c. Alerta por Email a los administradores
            try {
                $adminEmails = Setting::getAdminNotificationEmails($effectiveBrand);
                if (!empty($adminEmails)) {
                    $primaryAdmin = array_shift($adminEmails);
                    $subject = "💬 Respuesta WhatsApp de {$displayName} — {$eventTitle}";
                    $emailBody = "Un cliente con evento activo ha respondido por WhatsApp:\n\n"
                        . "Cliente: {$displayName} (+{$cleanPhone})\n"
                        . "Evento: {$eventTitle}\n"
                        . ($reactivated ? "Estado: Reactivado automáticamente de 'Sin Respuesta' a 'Borrador'\n" : "")
                        . "Fecha/Hora: " . now()->format('d/m/Y H:i') . "\n"
                        . "----------------------------------------\n"
                        . $text . "\n"
                        . "----------------------------------------\n\n"
                        . "Abrir en la App: {$eventUrl}";

                    Mail::raw($emailBody, function ($msg) use ($primaryAdmin, $adminEmails, $subject) {
                        $msg->to($primaryAdmin)->subject($subject);
                        if (!empty($adminEmails)) {
                            $msg->bcc($adminEmails);
                        }
                    });
                }
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
            str_starts_with($text, '💶 *¡NUEVA SOLICITUD DE PRESUPUESTO!*')
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
                $isStaffPhone = User::whereIn('role', ['admin', 'dj', 'assistant'])
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->where(function ($q) use ($cleanPhone, $national) {
                        $q->where('phone', 'LIKE', "%{$national}%")
                          ->orWhere('phone', 'LIKE', "%{$cleanPhone}%");
                    })
                    ->exists();
                if ($isStaffPhone) {
                    return true;
                }
            }
        } catch (\Throwable $e) {}

        return false;
    }

    /**
     * Busca un evento activo cuyos novios/clientes coincidan con el número de teléfono
     */
    protected function findMatchingEvent(string $cleanPhone): ?Event
    {
        $national = strlen($cleanPhone) > 9 ? substr($cleanPhone, -9) : $cleanPhone;
        if (empty($national) || strlen($national) < 7) {
            return null;
        }

        return Event::where('status', '!=', 'cancelled')
            ->where(function ($query) use ($cleanPhone, $national) {
                $query->whereHas('client', function ($q) use ($cleanPhone, $national) {
                    $q->whereNotNull('phone')
                      ->where('phone', '!=', '')
                      ->where(function ($sq) use ($cleanPhone, $national) {
                          $sq->where('phone', 'LIKE', "%{$national}%")
                             ->orWhere('phone', 'LIKE', "%{$cleanPhone}%");
                      });
                })
                ->orWhere(function ($q) use ($cleanPhone, $national) {
                    $q->whereNotNull('partner_phone')
                      ->where('partner_phone', '!=', '')
                      ->where(function ($sq) use ($cleanPhone, $national) {
                          $sq->where('partner_phone', 'LIKE', "%{$national}%")
                             ->orWhere('partner_phone', 'LIKE', "%{$cleanPhone}%");
                      });
                });
            })
            ->orderBy('event_date', 'desc')
            ->first();
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
