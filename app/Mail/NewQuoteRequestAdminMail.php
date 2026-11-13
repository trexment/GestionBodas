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

/**
 * Correo de aviso enviado a los administradores cuando un cliente solicita un presupuesto online.
 */
class NewQuoteRequestAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public Quote $quote;
    public array $requestedServices;
    public array $typeConfig;
    public string $companyName;
    public string $adminUrl;
    public string $clientNotes;
    public string $eventSchedule;
    public bool $needsPriorSetup;

    /**
     * Inicializa el correo con el evento, el presupuesto generado y los detalles solicitados por el cliente.
     */
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
        $brandInfo = Setting::getBrandInfo($brandKey);
        $this->companyName = $brandInfo['name'] ?: Setting::get('company_name', config('app.name', 'Eventos Musicales'));
        $this->adminUrl = route('admin.events.show', $event->id);
    }

    /**
     * Define el sobre del correo con el asunto de la nueva solicitud de presupuesto.
     */
    public function envelope(): Envelope
    {
        $clientName = $this->event->client ? $this->event->client->name : 'Nuevo Cliente';
        $eventDate = $this->event->event_date ? \Carbon\Carbon::parse($this->event->event_date)->format('d/m/Y') : 'Por definir';
        $typeName = $this->typeConfig['name'] ?? 'Evento';

        return new Envelope(
            subject: "📩 ¡Nueva Solicitud de Presupuesto! ({$typeName}) - {$clientName} ({$eventDate})",
        );
    }

    /**
     * Define la vista del contenido del correo para los administradores.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-quote-request-admin',
        );
    }

    /**
     * Devuelve la lista de archivos adjuntos del correo.
     */
    public function attachments(): array
    {
        return [];
    }
}
