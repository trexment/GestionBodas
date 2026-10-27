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

class QuoteRequestConfirmationClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public Quote $quote;
    public array $requestedServices;
    public array $typeConfig;
    public array $brand;
    public string $companyName;
    public string $clientNotes;
    public string $eventSchedule;
    public bool $needsPriorSetup;

    public function __construct(
        Event $event,
        Quote $quote,
        array $requestedServices,
        array $typeConfig,
        string $clientNotes = '',
        string $eventSchedule = '',
        bool $needsPriorSetup = false
    ) {
        $this->event = $event;
        $this->quote = $quote;
        $this->requestedServices = $requestedServices;
        $this->typeConfig = $typeConfig;
        $this->clientNotes = $clientNotes;
        $this->eventSchedule = $eventSchedule;
        $this->needsPriorSetup = $needsPriorSetup;
        $brandKey = $event->brand_clean ?? 'nunez_and_son';
        $this->brand = Setting::getBrandInfo($brandKey);
        $this->companyName = $this->brand['name'] ?: Setting::get('company_name', config('app.name', 'Eventos Musicales'));
    }

    public function envelope(): Envelope
    {
        $typeName = $this->typeConfig['name'] ?? 'tu evento';

        return new Envelope(
            subject: "Solicitud de presupuesto recibida ({$typeName}) - {$this->companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-request-confirmation-client',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
