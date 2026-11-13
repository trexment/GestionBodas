<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo de alerta enviado a los administradores cuando se importa o recibe un nuevo lead web.
 */
class NewLeadImportedAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public array $leadData;
    public string $brandKey;
    public array $brandInfo;
    public string $companyName;
    public string $adminUrl;
    public ?string $whatsappUrl;

    /**
     * Inicializa el correo con el evento creado, los datos del lead y la marca detectada.
     */
    public function __construct(Event $event, array $leadData, string $brandKey)
    {
        $this->event = $event;
        $this->leadData = $leadData;
        $this->brandKey = $brandKey;
        $this->brandInfo = Setting::getBrandInfo($brandKey);
        $this->companyName = $this->brandInfo['name'] ?? Setting::get('company_name', config('app.name', 'Eventos Musicales'));
        $this->adminUrl = route('admin.events.show', $event->id);

        $phone = $event->client ? $event->client->phone : ($leadData['client_phone'] ?? null);
        $cleanPhone = $phone ? preg_replace('/[^\d]/', '', $phone) : null;
        if ($cleanPhone && strlen($cleanPhone) === 9) {
            $cleanPhone = '34' . $cleanPhone;
        }
        $this->whatsappUrl = $cleanPhone ? "https://wa.me/{$cleanPhone}" : null;
    }

    /**
     * Define el sobre del correo con el asunto personalizado del lead.
     */
    public function envelope(): Envelope
    {
        $clientName = $this->event->client ? $this->event->client->name : ($this->leadData['client_name'] ?? 'Nuevo Cliente');
        $eventDate = $this->event->event_date ? $this->event->event_date->format('d/m/Y') : 'Fecha por definir';
        $brandName = $this->brandInfo['name'] ?? 'General';

        return new Envelope(
            subject: "🔔 ¡Nuevo Lead / Contacto Web recibido! [{$brandName}] - {$clientName} ({$eventDate})",
        );
    }

    /**
     * Define la vista Blade que renderiza el cuerpo del correo.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-lead-imported-admin',
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
