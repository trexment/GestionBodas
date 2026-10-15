<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

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

    public function envelope(): Envelope
    {
        $clientName = $this->event->client ? $this->event->client->name : ($this->leadData['client_name'] ?? 'Nuevo Cliente');
        $eventDate = $this->event->event_date ? $this->event->event_date->format('d/m/Y') : 'Fecha por definir';
        $brandName = $this->brandInfo['name'] ?? 'General';

        return new Envelope(
            subject: "🔔 ¡Nuevo Lead / Contacto Web recibido! [{$brandName}] - {$clientName} ({$eventDate})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-lead-imported-admin',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
