<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Event;
use App\Models\Quote;
use App\Models\Setting;

class NewQuoteRequestAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public Quote $quote;
    public array $requestedServices;
    public array $typeConfig;
    public string $companyName;
    public string $adminUrl;

    public function __construct(Event $event, Quote $quote, array $requestedServices, array $typeConfig)
    {
        $this->event = $event;
        $this->quote = $quote;
        $this->requestedServices = $requestedServices;
        $this->typeConfig = $typeConfig;
        $this->companyName = Setting::get('company_name', config('app.name', 'Eventos Musicales'));
        $this->adminUrl = route('admin.events.show', $event->id);
    }

    public function envelope(): Envelope
    {
        $clientName = $this->event->client ? $this->event->client->name : 'Nuevo Cliente';
        $eventDate = \Carbon\Carbon::parse($this->event->event_date)->format('d/m/Y');
        $typeName = $this->typeConfig['name'] ?? 'Evento';

        return new Envelope(
            subject: "📩 ¡Nueva Solicitud de Presupuesto! ({$typeName}) - {$clientName} ({$eventDate})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-quote-request-admin',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
