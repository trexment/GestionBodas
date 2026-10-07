<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\Setting;
use App\Services\ContractTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ContractSignedClientCopyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contract;
    public $event;
    public $brand;
    public $renderedData;

    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
        $this->event = $contract->event;
        $brandKey = $this->event ? ($this->event->brand_clean ?? 'nunez_and_son') : 'nunez_and_son';
        $this->brand = Setting::getBrandInfo($brandKey);
    }

    public function build()
    {
        $this->contract->load(['event.client', 'event.quotes.items']);
        $this->renderedData = ContractTemplateService::renderContract($this->contract);
        $this->renderedData['company_logo'] = Setting::get('company_logo');

        $brandName = $this->brand['name'] ?? Setting::getCompanyName('Núñez and Son');
        $eventName = $this->event ? $this->event->name : 'Evento';
        $filename = 'Contrato_Firmado_' . Str::slug($eventName) . '.pdf';

        $mail = $this->subject("📄 Copia de tu Contrato Firmado: {$eventName} - {$brandName}")
                     ->view('emails.contract-signed-client-copy');

        try {
            $pdf = Pdf::loadView('pdf.contract', $this->renderedData);
            $mail->attachData($pdf->output(), $filename, [
                'mime' => 'application/pdf',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error adjuntando PDF a email de cliente: ' . $e->getMessage());
        }

        return $mail;
    }
}
