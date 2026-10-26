<?php

namespace App\Http\Controllers;

use App\Mail\NewLeadImportedAdminMail;
use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LeadWebhookController extends Controller
{
    /**
     * Cabeceras CORS para permitir envíos directos desde javnxdj.com y nunezandson.com
     */
    private function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Requested-With, X-API-Key, Origin, Authorization',
        ];
    }

    /**
     * Respuesta preflight CORS: OPTIONS /api/leads
     */
    public function options(): JsonResponse
    {
        return response()->json(['status' => 'ok'], 200, $this->corsHeaders());
    }

    /**
     * Endpoint API para recibir leads directamente desde formularios web externos
     * (WordPress, Elementor, Contact Form 7, Webflow o scripts personalizados).
     *
     * URL: POST /api/leads
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Opcional: Validar API Key si está configurada en Ajustes
        $configuredApiKey = Setting::get('api_leads_secret_token');
        if (!empty($configuredApiKey)) {
            $receivedKey = $request->header('X-API-Key') ?: $request->input('api_key');
            if ($receivedKey !== $configuredApiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado: API Key inválida o no proporcionada.'
                ], 401, $this->corsHeaders());
            }
        }

        // 2. Extraer y normalizar campos recibidos (decodificando entidades HTML como &amp;)
        $rawName = trim((string)($request->input('name') ?: $request->input('nombre') ?: 'Contacto Web'));
        $name = html_entity_decode($rawName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $email = trim((string)($request->input('email') ?: $request->input('correo') ?: ''));
        $phone = trim((string)($request->input('phone') ?: $request->input('telefono') ?: $request->input('movil') ?: ''));
        $dateRaw = trim((string)($request->input('date') ?: $request->input('fecha') ?: ''));
        $rawLocation = trim((string)($request->input('location') ?: $request->input('lugar') ?: $request->input('finca') ?: 'Por definir'));
        $location = html_entity_decode($rawLocation, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $typeRaw = trim(mb_strtolower((string)($request->input('event_type') ?: $request->input('tipo') ?: 'boda')));
        $rawMessage = trim((string)($request->input('message') ?: $request->input('mensaje') ?: $request->input('observaciones') ?: ''));
        $message = html_entity_decode($rawMessage, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $brandRaw = trim(strtolower((string)($request->input('brand') ?: '')));
        $isTest = filter_var($request->input('is_test', false), FILTER_VALIDATE_BOOLEAN)
            || str_contains(mb_strtoupper($name), '[TEST]')
            || str_contains(mb_strtoupper($message), '[TEST]');

        // 3. Determinar marca (también por Origin/Referer si no viene explícita)
        if (in_array($brandRaw, ['nunez_and_son', 'javnx', 'mago_leugim'], true)) {
            $brandKey = $brandRaw;
        } elseif (in_array($brandRaw, ['nunez', 'nunezandson'], true)) {
            $brandKey = 'nunez_and_son';
        } else {
            $originHeader = strtolower((string)($request->header('Origin') ?: $request->header('Referer') ?: ''));
            if (str_contains($originHeader, 'nunez')) {
                $brandKey = 'nunez_and_son';
            } elseif (str_contains($originHeader, 'javnx')) {
                $brandKey = 'javnx';
            } else {
                $brandKey = Setting::getDetectedBrand();
            }
        }

        // 4. Normalizar Tipo de Evento
        $eventType = 'boda';
        if (str_contains($typeRaw, 'empresa') || str_contains($typeRaw, 'corp')) {
            $eventType = 'empresa';
        } elseif (str_contains($typeRaw, 'cumple') || str_contains($typeRaw, 'aniversario')) {
            $eventType = 'cumpleanos';
        } elseif (str_contains($typeRaw, 'comuni')) {
            $eventType = 'comunion';
        } elseif (str_contains($typeRaw, 'video')) {
            $eventType = 'videografo';
        } elseif (str_contains($typeRaw, 'otro') || str_contains($typeRaw, 'privad') || str_contains($typeRaw, 'fiesta')) {
            $eventType = 'otro';
        }

        // 5. Normalizar Fecha
        $eventDate = null;
        if (!empty($dateRaw)) {
            try {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRaw)) {
                    $eventDate = Carbon::parse($dateRaw)->format('Y-m-d');
                } elseif (preg_match('/^\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4}$/', $dateRaw)) {
                    $cleanDate = str_replace(['.', '-'], '/', $dateRaw);
                    $eventDate = Carbon::createFromFormat('d/m/Y', $cleanDate)->format('Y-m-d');
                } else {
                    $eventDate = Carbon::parse($dateRaw)->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                $eventDate = null;
            }
        }
        if (empty($eventDate)) {
            $eventDate = Carbon::now()->addMonths(3)->format('Y-m-d');
        }

        // 6. Normalizar Teléfono
        $cleanPhone = null;
        if (!empty($phone)) {
            $digitsOnly = preg_replace('/[^\d+]/', '', $phone);
            if (strlen(preg_replace('/[^\d]/', '', $digitsOnly)) >= 9) {
                $cleanPhone = $digitsOnly;
            }
        }

        // 7. Buscar o crear Cliente con Deduplicación Inteligente
        $client = null;

        // A. Buscar por email
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $client = User::where('email', $email)->first();
        }

        // B. Buscar por teléfono (últimos 9 dígitos)
        if (!$client && !empty($cleanPhone)) {
            $last9 = substr($cleanPhone, -9);
            $client = User::where('role', 'client')
                ->where(function($q) use ($last9, $cleanPhone) {
                    $q->where('phone', 'LIKE', "%{$last9}")
                      ->orWhere('phone', $cleanPhone);
                })->first();
        }

        // C. Buscar por nombre exacto
        if (!$client && !empty($name) && mb_strlen($name) > 2 && !in_array(mb_strtolower($name), ['contacto web', 'cliente', 'novios'])) {
            $client = User::where('role', 'client')->where('name', $name)->first();
        }

        if ($client) {
            $updates = [];
            if (empty($client->phone) && !empty($cleanPhone)) {
                $updates['phone'] = $cleanPhone;
            }
            if (!empty($email) && str_ends_with($client->email, '@eventosmusicales.local')) {
                $updates['email'] = $email;
            }
            if (!empty($updates)) {
                $client->update($updates);
            }
        } else {
            $finalEmail = (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) 
                ? $email 
                : ('lead_' . time() . '_' . rand(100, 999) . '@eventosmusicales.local');
            $client = User::create([
                'name' => $name,
                'email' => $finalEmail,
                'phone' => $cleanPhone ?: null,
                'role' => 'client',
                'password' => bcrypt(Str::random(16)),
            ]);
        }

        // 8. Crear Evento (con deduplicación de 90 segundos si enviar.php y el navegador envían a la vez, salvo en modo test)
        $typeLabel = match($eventType) {
            'empresa' => 'Evento Empresa',
            'cumpleanos' => 'Cumpleaños',
            'comunion' => 'Comunión',
            'videografo' => 'Videógrafo',
            default => 'Boda',
        };
        $eventName = "{$typeLabel} - {$name}";

        if (!$isTest && $client) {
            $recentDuplicate = Event::where('client_id', $client->id)
                ->where('brand', $brandKey)
                ->where('created_at', '>=', now()->subSeconds(90))
                ->latest('id')
                ->first();

            if ($recentDuplicate) {
                return response()->json([
                    'success' => true,
                    'deduplicated' => true,
                    'message' => 'Lead ya registrado hace unos segundos.',
                    'event_id' => $recentDuplicate->id,
                    'event_name' => $recentDuplicate->name,
                    'brand' => $brandKey,
                ], 200, $this->corsHeaders());
            }
        }

        $defaultDj = User::whereIn('role', ['admin', 'dj'])->orderBy('id')->first();

        $notesContent = "🌐 LEAD RECIBIDO VÍA WEB FORMULARIO (" . strtoupper($brandKey) . ")\n"
            . "Fecha recepción: " . now()->format('d/m/Y H:i') . "\n"
            . "Cliente: {$name}\n"
            . "Email: {$email}\n"
            . "Teléfono: " . ($cleanPhone ?: 'No indicado') . "\n"
            . "Lugar / Finca: {$location}\n"
            . "Fecha solicitada: " . ($eventDate ? Carbon::parse($eventDate)->format('d/m/Y') : 'Por definir') . "\n"
            . "----------------------------------------\n"
            . ($message ?: 'Sin comentarios adicionales.');

        $event = Event::create([
            'name' => $eventName,
            'brand' => $brandKey,
            'event_type' => $eventType,
            'event_date' => $eventDate,
            'location' => $location ?: 'Por definir',
            'client_id' => $client ? $client->id : null,
            'dj_id' => $defaultDj ? $defaultDj->id : null,
            'status' => 'no_response',
            'notes' => $notesContent,
        ]);

        // Localizar automáticamente el teléfono del restaurante / bodega / finca
        try {
            \App\Services\VenueLookupService::autoPopulateEventVenuePhone($event);
        } catch (\Throwable $e) {}

        // 9. Notificación en la App + Email + WhatsApp a los administradores
        $leadData = [
            'client_name' => $name,
            'client_email' => $email,
            'client_phone' => $cleanPhone,
            'event_date' => $eventDate,
            'location' => $location,
            'event_type' => $eventType,
            'full_body' => $notesContent,
        ];

        try {
            $leadDateStr = $eventDate ? Carbon::parse($eventDate)->format('d/m/Y') : 'Sin fecha';
            \App\Services\AppNotificationService::push(
                'email_lead',
                "📩 Nuevo Lead Web: {$name}",
                "Fecha: {$leadDateStr} • " . ($location ?: 'Por definir'),
                url("/admin/events/{$event->id}"),
                $event->id,
                $brandKey
            );
        } catch (\Throwable $e) {
            Log::warning("No se pudo registrar notificación en App para lead webhook (Evento #{$event->id}): " . $e->getMessage());
        }

        try {
            \App\Services\AppNotificationService::sendMailableToAdmins(
                fn () => new NewLeadImportedAdminMail($event, $leadData, $brandKey),
                $brandKey
            );
        } catch (\Throwable $e) {
            Log::warning("No se pudo enviar email de notificación para el nuevo lead web (Evento #{$event->id}): " . $e->getMessage());
        }

        $whatsappReport = null;
        try {
            if (\App\Services\EvolutionWhatsAppService::isEnabled()) {
                $leadDateStr = $eventDate ? Carbon::parse($eventDate)->format('d/m/Y') : 'Por definir';
                $brandDisplayName = match($brandKey) {
                    'javnx' => 'JAVNX DJ',
                    'mago_leugim' => 'MAGO LEUGIM',
                    default => 'NÚÑEZ & SON',
                };
                $shortMsg = $message ? mb_substr(preg_replace('/\s+/', ' ', $message), 0, 220) : '';
                $waLeadAlert = ($isTest ? "🧪 *[PRUEBA] " : "📥 *") . "¡NUEVO LEAD ENTRANTE ({$brandDisplayName})!*\n\n"
                    . "👤 *Contacto:* {$name}\n"
                    . "📞 *Teléfono:* " . ($cleanPhone ?: 'No indicado') . "\n"
                    . ($email ? "✉️ *Email:* {$email}\n" : "")
                    . "📅 *Fecha Evento:* {$leadDateStr}\n"
                    . "📍 *Lugar:* " . ($location ?: 'Por definir') . "\n"
                    . ($shortMsg ? "💬 *Mensaje:* {$shortMsg}\n" : "")
                    . "🏢 *Marca:* {$brandDisplayName}\n\n"
                    . "🔗 *Ficha:* " . url("/admin/events/{$event->id}");
                $whatsappReport = \App\Services\EvolutionWhatsAppService::notifyAdmins($waLeadAlert, $brandKey);
            }
        } catch (\Throwable $e) {
            Log::warning("No se pudo enviar alerta WhatsApp para nuevo lead web (Evento #{$event->id}): " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Lead registrado correctamente.',
            'event_id' => $event->id,
            'event_name' => $event->name,
            'brand' => $brandKey,
            'whatsapp_report' => $whatsappReport,
        ], 201, $this->corsHeaders());
    }
}
