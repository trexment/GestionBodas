<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'event_type',
        'event_date',
        'location',
        'venue_contact_name',
        'venue_contact_phone',
        'venue_notes',
        'status',
        'deposit_paid',
        'deposit_paid_amount',
        'deposit_payment_method',
        'deposit_paid_at',
        'deposit_notes',
        'token',
        'is_dossier_completed',
        'client_id',
        'dj_id',
        'assistant_id',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
        'deposit_paid' => 'boolean',
        'deposit_paid_amount' => 'decimal:2',
        'deposit_paid_at' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($event) {
            $event->token = Str::random(32);
        });
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function dj()
    {
        return $this->belongsTo(User::class, 'dj_id');
    }

    public function assistant()
    {
        return $this->belongsTo(User::class, 'assistant_id');
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function dossiers()
    {
        return $this->hasMany(Dossier::class);
    }

    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }

    public function equipment()
    {
        return $this->belongsToMany(Equipment::class, 'event_equipment')
                    ->withPivot('id', 'quantity', 'notes')
                    ->withTimestamps();
    }

    public function musicRequests()
    {
        return $this->hasMany(EventMusicRequest::class)->orderBy('order')->orderBy('id');
    }

    /**
     * Etiqueta legible del método de pago de la señal
     */
    public function getDepositMethodLabelAttribute(): string
    {
        $pm = $this->attributes['deposit_payment_method'] ?? null;
        return match($pm) {
            'bizum' => 'Bizum',
            'transfer' => 'Transferencia Bancaria',
            'cash' => 'Efectivo',
            'card' => 'Tarjeta / TPV',
            'other' => 'Otro',
            default => $pm ? ucfirst($pm) : 'No especificado',
        };
    }

    /**
     * Icono según método de pago
     */
    public function getDepositMethodIconAttribute(): string
    {
        $pm = $this->attributes['deposit_payment_method'] ?? null;
        return match($pm) {
            'bizum' => '📱',
            'transfer' => '🏦',
            'cash' => '💵',
            'card' => '💳',
            default => '💶',
        };
    }

    /**
     * Tipo de evento limpio / normalizado
     */
    public function getEventTypeCleanAttribute(): string
    {
        $type = $this->attributes['event_type'] ?? null;
        if (!empty($type)) {
            return $type;
        }
        $nameLower = mb_strtolower($this->name ?? '');
        if (str_contains($nameLower, 'empresa') || str_contains($nameLower, 'corporativ')) return 'empresa';
        if (str_contains($nameLower, 'cumplea') || str_contains($nameLower, 'cumple') || str_contains($nameLower, 'aniversario')) return 'cumpleanos';
        if (str_contains($nameLower, 'comunion') || str_contains($nameLower, 'comunión')) return 'comunion';
        if (str_contains($nameLower, 'fiesta') || str_contains($nameLower, 'privad') || str_contains($nameLower, 'despedida')) return 'otro';
        return 'boda';
    }

    /**
     * Determina si es una boda
     */
    public function getIsWeddingAttribute(): bool
    {
        return $this->event_type_clean === 'boda';
    }

    /**
     * Etiqueta legible del tipo de evento
     */
    public function getEventTypeLabelAttribute(): string
    {
        return match($this->event_type_clean) {
            'empresa' => 'Evento de Empresa / Corporativo',
            'cumpleanos' => 'Cumpleaños / Aniversario',
            'comunion' => 'Comunión',
            'otro' => 'Fiesta Privada / Evento',
            default => 'Boda / Enlace',
        };
    }

    /**
     * Icono del tipo de evento
     */
    public function getEventTypeIconAttribute(): string
    {
        return match($this->event_type_clean) {
            'empresa' => '🏢',
            'cumpleanos' => '🎂',
            'comunion' => '🕊️',
            'otro' => '🎉',
            default => '💍',
        };
    }
}
