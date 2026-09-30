<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $companyName;
    public $timestamp;

    public function __construct()
    {
        $this->companyName = Setting::get('company_name', config('app.name', 'Eventos Musicales'));
        $this->timestamp = now()->format('d/m/Y H:i:s');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Prueba de Configuración de Correo - ' . $this->companyName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test-smtp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
