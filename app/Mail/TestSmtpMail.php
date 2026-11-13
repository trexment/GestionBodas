<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;

/**
 * Correo de prueba para verificar el funcionamiento de la configuración SMTP del sistema.
 */
class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $companyName;
    public $timestamp;

    /**
     * Inicializa el correo de prueba con el nombre de la empresa y la marca de tiempo actual.
     */
    public function __construct()
    {
        $this->companyName = Setting::get('company_name', config('app.name', 'Eventos Musicales'));
        $this->timestamp = now()->format('d/m/Y H:i:s');
    }

    /**
     * Define el sobre del correo de prueba de configuración SMTP.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Prueba de Configuración de Correo - ' . $this->companyName,
        );
    }

    /**
     * Define la vista Blade que renderiza el correo de prueba SMTP.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.test-smtp',
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
