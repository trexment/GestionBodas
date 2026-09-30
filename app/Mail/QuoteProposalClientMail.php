<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use App\Models\Event;
use App\Models\Quote;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class QuoteProposalClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public Quote $quote;
    public string $companyName;
    public string $companyPhone;
    public string $companyEmail;
    public string $portalUrl;

    public function __construct(Event $event, Quote $quote)
    {
        $this->event = $event;
        $this->quote = $quote->load('items');
        $this->companyName = Setting::get('company_name', config('app.name', 'Eventos Musicales'));
        $this->companyPhone = Setting::get('company_phone', '');
        $this->companyEmail = Setting::get('company_email', '');
        $this->portalUrl = route('guest.form', $event->token);
    }

    public function envelope(): Envelope
    {
        $eventDate = \Carbon\Carbon::parse($this->event->event_date)->format('d/m/Y');

        return new Envelope(
            subject: "📑 Propuesta de Presupuesto para tu Evento ({$eventDate}) - {$this->companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-proposal-client',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        try {
            // Generate PDF on the fly and attach
            $pdf = Pdf::loadView('pdf.quote', [
                'quote' => $this->quote,
                'event' => $this->event,
                'client' => $this->event->client,
                'settings' => Setting::all()->pluck('value', 'key')->toArray(),
            ]);

            $fileName = 'Presupuesto_' . $this->quote->id . '_' . \Illuminate\Support\Str::slug($this->event->name) . '.pdf';

            $attachments[] = Attachment::fromData(fn () => $pdf->output(), $fileName)
                ->withMime('application/pdf');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Could not generate PDF attachment for quote email: ' . $e->getMessage());
        }

        return $attachments;
    }
}
