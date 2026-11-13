<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modelo que representa un mensaje de WhatsApp entrante o saliente vinculado a un evento y cliente.
 */
class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'event_id',
        'user_id',
        'phone',
        'sender_name',
        'direction',
        'message',
        'status',
        'external_id',
        'brand',
        'payload',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Relación con el evento al que pertenece el mensaje de WhatsApp.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relación con el usuario asociado al mensaje de WhatsApp.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Indica si el mensaje fue recibido desde el cliente (entrante).
     */
    public function getIsInboundAttribute(): bool
    {
        return $this->direction === 'inbound';
    }

    /**
     * Indica si el mensaje fue enviado por el equipo hacia el cliente (saliente).
     */
    public function getIsOutboundAttribute(): bool
    {
        return $this->direction === 'outbound';
    }

    /**
     * Devuelve la fecha y hora del mensaje formateada como dd/mm/YYYY HH:mm.
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d/m/Y H:i') : '';
    }

    /**
     * Devuelve únicamente la hora y minuto del mensaje en formato HH:mm.
     */
    public function getTimeShortAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('H:i') : '';
    }
}
