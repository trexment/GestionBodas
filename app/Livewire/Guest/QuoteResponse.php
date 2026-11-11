<?php

namespace App\Livewire\Guest;

use Livewire\Component;
use App\Models\Event;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\AppNotificationService;
use App\Services\EvolutionWhatsAppService;
use Illuminate\Support\Facades\Schema;

class QuoteResponse extends Component
{
    use \App\Livewire\Traits\ThrottlesGuestActions;

    public string $token;
    public ?Event $event = null;
    public ?Quote $quote = null;

    // UI state: 'view' | 'accept_box' | 'reject_box'
    public string $actionMode = 'view';

    // Campos de respuesta del cliente
    public string $client_notes = '';
    public string $rejection_reason = '';
    public string $selected_reason_tag = '';

    public function mount(string $token)
    {
        $this->token = $token;
        Quote::EnsureQuoteResponseColumns();

        $slugQuoteId = null;
        $this->event = Event::findByTokenOrPrettySlug($token, ['client', 'quotes.items'], $slugQuoteId);

        if (!$this->event) {
            abort(404, 'Propuesta no encontrada o enlace no válido.');
        }

        if ($this->event->quotes && $this->event->quotes->isNotEmpty()) {
            $repairedAny = false;
            foreach ($this->event->quotes as $evQuote) {
                if (Quote::repairCorruptedPackDiscounts($evQuote)) {
                    $repairedAny = true;
                }
            }
            if ($repairedAny) {
                $this->event->load('quotes.items');
            }
        }

        $requestedQuoteId = request()->query('quote') ?: $slugQuoteId;
        if ($requestedQuoteId) {
            $this->quote = $this->event->quotes->firstWhere('id', (int) $requestedQuoteId);
        }

        if (!$this->quote) {
            // Priorizar la última propuesta activa (no rechazada) si existe
            $this->quote = $this->event->quotes
                ->where('status', '!==', 'rejected')
                ->sortByDesc('id')
                ->first()
                ?: $this->event->quotes->sortByDesc('id')->first();
        }

        // Redirigir automáticamente a la URL bonita si entró con el token largo o con ?quote=
        if ($this->quote && ($token === $this->event->token || request()->has('quote'))) {
            $prettyUrl = $this->quote->public_url;
            if (request()->fullUrl() !== $prettyUrl) {
                $this->redirect($prettyUrl);
                return;
            }
        }

        if ($this->quote) {
            $this->client_notes = (string) ($this->quote->client_notes ?? '');
            $this->rejection_reason = (string) ($this->quote->rejection_reason ?? '');
        }
    }

    public function selectQuote(int $quoteId): void
    {
        $found = $this->event->quotes->firstWhere('id', $quoteId);
        if ($found) {
            $this->quote = $found;
            $this->actionMode = 'view';
            $this->client_notes = (string) ($this->quote->client_notes ?? '');
            $this->rejection_reason = (string) ($this->quote->rejection_reason ?? '');
            $this->resetValidation();
        }
    }

    public function openAcceptBox(): void
    {
        if (!$this->quote || $this->quote->status === 'rejected') {
            return;
        }
        $this->actionMode = 'accept_box';
        $this->resetValidation();
    }

    public function openRejectBox(): void
    {
        if (!$this->quote || $this->quote->status === 'rejected') {
            return;
        }
        $this->actionMode = 'reject_box';
        $this->resetValidation();
    }

    public function cancelAction(): void
    {
        $this->actionMode = 'view';
        $this->resetValidation();
    }

    public function pickRejectionTag(string $tagText): void
    {
        $this->selected_reason_tag = $tagText;
        if (empty(trim($this->rejection_reason)) || in_array(trim($this->rejection_reason), [
            'Se nos va de presupuesto',
            'Ya hemos contratado otra opción',
            'Queremos adaptar o quitar algún servicio del presupuesto',
            'Cambio de fecha o planes del evento',
        ], true)) {
            $this->rejection_reason = $tagText;
        }
        $this->resetErrorBag('rejection_reason');
    }

    public function acceptQuoteOnline(): void
    {
        if (!$this->quote || !$this->event || $this->quote->status === 'rejected') {
            return;
        }

        if ($this->throttleGuestAction('quote_response', 5, 600)) {
            return;
        }

        $notesClean = trim($this->client_notes);

        $this->quote->update([
            'status' => 'accepted',
            'client_notes' => $notesClean ?: null,
            'rejection_reason' => null,
            'responded_at' => now(),
        ]);

        // Descartar automáticamente el resto de presupuestos pendientes del mismo evento
        $otherQuotesCount = $this->event->quotes()
            ->where('id', '!=', $this->quote->id)
            ->where('status', '!=', 'rejected')
            ->count();

        if ($otherQuotesCount > 0) {
            $this->event->quotes()
                ->where('id', '!=', $this->quote->id)
                ->where('status', '!=', 'rejected')
                ->update([
                    'status' => 'rejected',
                    'rejection_reason' => "Descartada automáticamente al aceptar la Propuesta #{$this->quote->id}",
                    'responded_at' => now(),
                ]);
        }

        $newEventStatus = in_array($this->event->status, ['completed'], true) ? $this->event->status : 'confirmed';
        $amountFormatted = number_format((float) $this->quote->amount, 2, ',', '.');
        $logLine = '[' . now()->format('d/m/Y H:i') . "] ✅ PRESUPUESTO #{$this->quote->id} ACEPTADO ONLINE por el cliente ({$amountFormatted} €)."
            . ($otherQuotesCount > 0 ? " (Se han cerrado automáticamente las otras {$otherQuotesCount} propuestas alternativas)." : '')
            . ($notesClean ? " Comentario: {$notesClean}" : '');

        $updateEvent = [
            'status' => $newEventStatus,
            'notes' => ($this->event->notes ? $this->event->notes . "\n" : '') . $logLine,
        ];
        if (Schema::hasColumn('events', 'no_response_at')) {
            $updateEvent['no_response_at'] = null;
        }

        try {
            $this->event->update($updateEvent);
        } catch (\Throwable $e) {
            $this->event->update(['status' => $newEventStatus]);
        }

        $this->event->refresh();
        $this->quote->refresh();
        $this->actionMode = 'view';

        // Notificación interna en tiempo real
        $clientName = $this->event->client ? $this->event->client->name : 'Cliente';
        $clientPhone = $this->event->client ? ($this->event->client->phone ?? '') : ($this->event->partner_phone ?? '');
        $clientEmail = $this->event->client_contact_email ?? '';
        $eventDate = $this->event->event_date ? \Carbon\Carbon::parse($this->event->event_date)->format('d/m/Y') : 'Sin fecha';
        $eventUrl = route('admin.events.show', $this->event->id);

        try {
            AppNotificationService::push(
                'quote_request',
                "✅ ¡Presupuesto Aceptado! ({$this->event->name})",
                "{$clientName} ha ACEPTADO online la Propuesta #{$this->quote->id} ({$amountFormatted} €)." . ($notesClean ? " Nota: {$notesClean}" : ''),
                $eventUrl,
                $this->event->id,
                $this->event->brand_clean
            );
        } catch (\Throwable $e) {
        }

        $effectiveBrand = (($this->quote && $this->quote->brand_clean === 'javnx') || $this->event->brand_clean === 'javnx' || Setting::getDetectedBrand() === 'javnx')
            ? 'javnx'
            : $this->event->brand_clean;

        // Notificación por Email a todos los Administradores (y DJ asignado)
        try {
            $djEmail = ($this->event->dj && !empty($this->event->dj->email)) ? $this->event->dj->email : null;
            AppNotificationService::sendAlertEmailToAdmins(
                "✅ ¡Presupuesto Aceptado Online! — {$this->event->name} ({$amountFormatted} €)",
                "✅ ¡El cliente ha ACEPTADO el Presupuesto #{$this->quote->id}!",
                [
                    'Evento' => $this->event->name,
                    'Fecha del Evento' => $eventDate,
                    'Lugar / Espacio' => $this->event->location ?: 'Por definir',
                    'Cliente' => $clientName,
                    'Teléfono' => $clientPhone ?: 'No indicado',
                    'Email' => $clientEmail ?: 'No indicado',
                    'Propuesta Aceptada' => "Propuesta #{$this->quote->id} — {$amountFormatted} € (" . $this->quote->tax_label . ")",
                    'Señal de Reserva' => number_format((float) $this->quote->signal_amount, 2, ',', '.') . ' € (' . $this->quote->signal_label . ')',
                    'Comentario del Cliente' => $notesClean ?: null,
                    'Nuevo Estado del Evento' => 'Confirmado ✅',
                ],
                $eventUrl,
                'Ver Evento y Presupuesto en el Panel',
                $effectiveBrand,
                '#059669',
                $djEmail
            );
        } catch (\Throwable $e) {
        }

        // Alerta opcional por WhatsApp a administradores si está activo
        try {
            $waAlert = "✅ *¡PRESUPUESTO ACEPTADO ONLINE!*\n\n"
                . "👤 *Cliente:* {$clientName}\n"
                . "🎉 *Evento:* {$this->event->name} ({$eventDate})\n"
                . "💰 *Propuesta #{$this->quote->id}:* {$amountFormatted} €\n"
                . ($notesClean ? "💬 *Nota:* {$notesClean}\n" : '')
                . "\nEl estado del evento se ha actualizado automáticamente a *Confirmado*.\n"
                . "🔗 *Ficha:* {$eventUrl}";
            EvolutionWhatsAppService::notifyAdmins($waAlert, $effectiveBrand);
        } catch (\Throwable $e) {
        }

        session()->flash('quote_response_success', '¡Muchas gracias! Hemos registrado la aceptación de vuestro presupuesto y actualizado el estado de vuestro evento.');
    }

    public function rejectQuoteOnline(): void
    {
        if (!$this->quote || !$this->event) {
            return;
        }

        if ($wait = $this->throttleGuestAction('quote_response', 5, 600)) {
            $this->addError('rejection_reason', "Demasiados intentos. Inténtalo de nuevo en {$wait} segundos.");
            return;
        }

        $this->validate([
            'rejection_reason' => 'required|string|min:4|max:1500',
        ], [
            'rejection_reason.required' => 'Es obligatorio indicar el motivo por el que rechazas el presupuesto para poder continuar.',
            'rejection_reason.min' => 'Por favor, indícanos brevemente el motivo (al menos 4 caracteres).',
        ]);

        $reasonClean = trim($this->rejection_reason);
        $amountFormatted = number_format((float) $this->quote->amount, 2, ',', '.');

        $this->quote->update([
            'status' => 'rejected',
            'rejection_reason' => $reasonClean,
            'responded_at' => now(),
        ]);

        // Comprobar si el evento tiene otros presupuestos activos (pendientes o aceptados)
        $remainingQuotes = $this->event->quotes()
            ->where('id', '!=', $this->quote->id)
            ->get();

        $hasAcceptedQuote = $remainingQuotes->contains(fn ($q) => $q->status === 'accepted');
        $activePendingQuotes = $remainingQuotes->filter(fn ($q) => !in_array($q->status, ['rejected', 'cancelled'], true));
        $hasOtherActiveQuotes = $hasAcceptedQuote || $activePendingQuotes->isNotEmpty();

        if ($hasAcceptedQuote) {
            $newEventStatus = in_array($this->event->status, ['completed'], true) ? $this->event->status : 'confirmed';
        } elseif ($activePendingQuotes->isNotEmpty()) {
            // Si aún quedan otros presupuestos pendientes, mantener el evento ACTIVO
            $newEventStatus = in_array($this->event->status, ['rejected', 'cancelled'], true)
                ? 'quote_sent'
                : $this->event->status;
        } else {
            // Solo si TODOS los presupuestos han sido rechazados, el evento pasa a rechazado
            $newEventStatus = 'rejected';
        }

        $logLine = '[' . now()->format('d/m/Y H:i') . "] ❌ PRESUPUESTO #{$this->quote->id} RECHAZADO ONLINE por el cliente ({$amountFormatted} €). Motivo indicado: {$reasonClean}"
            . ($hasOtherActiveQuotes ? ' (El evento continúa ACTIVO porque existen otras propuestas vigentes).' : '');

        $updateEvent = [
            'status' => $newEventStatus,
            'notes' => ($this->event->notes ? $this->event->notes . "\n" : '') . $logLine,
        ];
        if (Schema::hasColumn('events', 'no_response_at')) {
            $updateEvent['no_response_at'] = null;
        }

        try {
            $this->event->update($updateEvent);
        } catch (\Throwable $e) {
            $this->event->update(['status' => $newEventStatus]);
        }

        $this->event->refresh();
        $this->event->load('quotes.items');
        $this->quote->refresh();
        $this->actionMode = 'view';

        $clientName = $this->event->client ? $this->event->client->name : 'Cliente';
        $clientPhone = $this->event->client ? ($this->event->client->phone ?? '') : ($this->event->partner_phone ?? '');
        $clientEmail = $this->event->client_contact_email ?? '';
        $eventDate = $this->event->event_date ? \Carbon\Carbon::parse($this->event->event_date)->format('d/m/Y') : 'Sin fecha';
        $eventUrl = route('admin.events.show', $this->event->id);
        $eventStatusLabel = $hasOtherActiveQuotes
            ? 'Sigue Activo ✅ (Tiene otras propuestas vigentes)'
            : 'Rechazado ❌ (Sin más propuestas activas)';

        // Notificación interna en tiempo real
        try {
            AppNotificationService::push(
                'quote_request',
                "❌ Propuesta #{$this->quote->id} Descartada ({$this->event->name})",
                "{$clientName} ha rechazado la Propuesta #{$this->quote->id}. Motivo: {$reasonClean}" . ($hasOtherActiveQuotes ? ' · Evento sigue activo.' : ''),
                $eventUrl,
                $this->event->id,
                $this->event->brand_clean
            );
        } catch (\Throwable $e) {
        }

        $effectiveBrand = (($this->quote && $this->quote->brand_clean === 'javnx') || $this->event->brand_clean === 'javnx' || Setting::getDetectedBrand() === 'javnx')
            ? 'javnx'
            : $this->event->brand_clean;

        // Notificación por Email a todos los Administradores (y DJ asignado)
        try {
            $djEmail = ($this->event->dj && !empty($this->event->dj->email)) ? $this->event->dj->email : null;
            AppNotificationService::sendAlertEmailToAdmins(
                "❌ Propuesta #{$this->quote->id} Rechazada Online — {$this->event->name} ({$amountFormatted} €)",
                "❌ El cliente ha RECHAZADO la Propuesta #{$this->quote->id}",
                [
                    'Evento' => $this->event->name,
                    'Fecha del Evento' => $eventDate,
                    'Lugar / Espacio' => $this->event->location ?: 'Por definir',
                    'Cliente' => $clientName,
                    'Teléfono' => $clientPhone ?: 'No indicado',
                    'Email' => $clientEmail ?: 'No indicado',
                    'Propuesta Rechazada' => "Propuesta #{$this->quote->id} — {$amountFormatted} € (" . $this->quote->tax_label . ")",
                    'Motivo Indicado por el Cliente' => $reasonClean,
                    'Estado del Evento' => $eventStatusLabel,
                ],
                $eventUrl,
                'Abrir Ficha del Evento para Seguimiento / Ajuste',
                $effectiveBrand,
                '#e11d48',
                $djEmail
            );
        } catch (\Throwable $e) {
        }

        // Alerta opcional por WhatsApp a administradores
        try {
            $waAlert = "❌ *PROPUESTA #{$this->quote->id} RECHAZADA ONLINE*\n\n"
                . "👤 *Cliente:* {$clientName}\n"
                . "📅 *Evento:* {$this->event->name} ({$eventDate})\n"
                . "💰 *Propuesta #{$this->quote->id}:* {$amountFormatted} €\n"
                . "📝 *Motivo indicado:* {$reasonClean}\n\n"
                . "📌 *Estado del evento:* {$eventStatusLabel}\n"
                . "🔗 *Ficha:* {$eventUrl}";
            EvolutionWhatsAppService::notifyAdmins($waAlert, $effectiveBrand);
        } catch (\Throwable $e) {
        }

        session()->flash('quote_response_rejected', 'Hemos registrado tu respuesta y el motivo indicado. ¡Muchas gracias por tu tiempo!');
    }

    public function render()
    {
        $brandKey = $this->quote ? $this->quote->brand_clean : ($this->event ? $this->event->brand_clean : 'nunez_and_son');
        $brand = Setting::getBrandInfo($brandKey);

        return view('livewire.guest.quote-response', [
            'brand' => $brand,
            'companyIban' => Setting::get('company_iban', ''),
            'companyBizum' => Setting::get('company_bizum', ''),
        ])->layout('components.layouts.wide-guest');
    }
}
