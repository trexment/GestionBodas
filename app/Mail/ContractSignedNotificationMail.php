<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractSignedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contract;
    public $event;
    public $brand;

    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
        $this->event = $contract->event;
        $brandKey = $this->event ? ($this->event->brand_clean ?? 'nunez_and_son') : 'nunez_and_son';
        $this->brand = Setting::getBrandInfo($brandKey);
    }

    public function build()
    {
        $brandName = $this->brand['name'] ?? 'Eventos Musicales';
        $clientName = $this->contract->client_name_signed ?: 'El cliente';
        $eventName = $this->event ? $this->event->name : 'Evento';

        return $this->subject("✍️ ¡Contrato Firmado!: {$eventName} - {$clientName}")
                    ->view('emails.contract-signed-notification');
    }
}
