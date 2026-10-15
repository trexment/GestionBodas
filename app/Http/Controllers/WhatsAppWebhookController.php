<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        // 2. Asociar con Evento y Cliente en la base de datos
        $event = $this->findMatchingEvent($cleanPhone);
        $user = $this->findMatchingUser($cleanPhone);

        // Evitar duplicados si el webhook se dispara múltiples veces con el mismo externalId
        if ($externalId) {
            $alreadyExists = WhatsAppMessage::where('external_id', $externalId)->exists();
            if ($alreadyExists) {
                return response()->json(['success' => true, 'duplicate' => true]);
            }
        }

        $direction = $fromMe ? 'outbound' : 'inbound';

        $record = WhatsAppMessage::create([
            'event_id' => $event ? $event->id : null,
            'user_id' => $user ? $user->id : null,
            'phone' => $cleanPhone,
            'sender_name' => $senderName,
            'direction' => $direction,
            'message' => $text,
            'status' => $fromMe ? 'sent' : 'received',
            'external_id' => $externalId,
            'brand' => $event ? $event->brand_clean : $brand,
            'payload' => $payload,
        ]);

        // 3. AUTOMATIZACIÓN INTELIGENTE: Si el cliente responde y estaba en seguimiento ("no_response")
        if (!$fromMe && $event && $event->status === 'no_response') {
            $event->update([
                'status' => 'draft', // Vuelve automáticamente a Borrador activo
                'no_response_at' => null, // Resetea el reloj de sin respuesta
                'notes' => ($event->notes ? $event->notes . "\n" : "") 
                    . "[" . now()->format('d/m/Y H:i') . "] 💬 [WhatsApp Entrante de {$senderName}]: \"" . mb_substr($text, 0, 150) . "\" -> Reactivado a Borrador automáticamente.",
            ]);
        } elseif (!$fromMe && $event) {
            // Registrar en notas internas para que conste en el historial
            $event->update([
                'notes' => ($event->notes ? $event->notes . "\n" : "") 
                    . "[" . now()->format('d/m/Y H:i') . "] 💬 [WhatsApp de {$senderName}]: \"" . mb_substr($text, 0, 150) . "\"",
            ]);
        }

        return response()->json([
            'success' => true,
            'message_id' => $record->id,
            'event_id' => $event ? $event->id : null,
            'client_phone' => $cleanPhone,
            'direction' => $direction,
        ]);
    }

    /**
     * Busca un evento activo que coincida con el número de teléfono
     */
    protected function findMatchingEvent(string $cleanPhone): ?Event
    {
        // Variantes de teléfono para búsqueda flexible (con prefijo 34 o sin él)
        $national = substr($cleanPhone, 2); // ej: 622634790

        return Event::whereHas('client', function ($q) use ($cleanPhone, $national) {
            $q->where('phone', 'LIKE', "%{$national}%")
              ->orWhere('phone', 'LIKE', "%{$cleanPhone}%");
        })
        ->orWhere('partner_phone', 'LIKE', "%{$national}%")
        ->orWhere('partner_phone', 'LIKE', "%{$cleanPhone}%")
        ->orWhere('venue_contact_phone', 'LIKE', "%{$national}%")
        ->orderBy('event_date', 'desc')
        ->first();
    }

    /**
     * Busca el usuario cliente correspondiente
     */
    protected function findMatchingUser(string $cleanPhone): ?User
    {
        $national = substr($cleanPhone, 2);
        return User::where('phone', 'LIKE', "%{$national}%")
            ->orWhere('phone', 'LIKE', "%{$cleanPhone}%")
            ->first();
    }
}
