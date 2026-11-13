<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Correo de notificación enviado a los administradores cuando un cliente firma un contrato.
 */
class ContractSignedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contract;
    public $event;
    public $brand;

    /**
     * Inicializa la notificación con el contrato firmado y la información del evento.
     */
    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
        $this->event = $contract->event;
        $brandKey = $this->event ? ($this->event->brand_clean ?? 'nunez_and_son') : 'nunez_and_son';
        $this->brand = Setting::getBrandInfo($brandKey);
    }

    /**
     * Construye el correo electrónico de aviso de firma de contrato.
     */
    public function build()
    {
        $brandName = $this->brand['name'] ?? 'Eventos Musicales';
        $clientName = $this->contract->client_name_signed ?: 'El cliente';
        $eventName = $this->event ? $this->event->name : 'Evento';

        return $this->subject("✍️ ¡Contrato Firmado!: {$eventName} - {$clientName}")
                    ->view('emails.contract-signed-notification');
    }
}
