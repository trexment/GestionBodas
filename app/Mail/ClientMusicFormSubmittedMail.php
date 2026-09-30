<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientMusicFormSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $event;
    public $brand;
    public $songsSummary;

    public function __construct(Event $event, array $songsSummary = [])
    {
        $this->event = $event;
        $this->brand = Setting::getBrandInfo($event->brand_clean ?? 'nunez_and_son');
        $this->songsSummary = $songsSummary;
    }

    public function build()
    {
        $brandName = $this->brand['name'] ?? 'Eventos Musicales';
        $clientName = $this->event->client ? $this->event->client->name : 'El cliente';
        $dateStr = $this->event->event_date ? $this->event->event_date->format('d/m/Y') : 'Fecha por definir';

        return $this->subject("🎵 Cuestionario Musical Recibido: {$this->event->name} ({$dateStr})")
                    ->view('emails.client-music-form-submitted');
    }
}
