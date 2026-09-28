<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'amount',
        'status',
        'pdf_path',
        'deposit_type',
        'deposit_percentage',
        'deposit_amount',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * Devuelve el importe de la señal de reserva calculada
     */
    public function getSignalAmountAttribute(): float
    {
        if ($this->attributes['deposit_amount'] !== null && (float)$this->attributes['deposit_amount'] > 0) {
            return min((float)$this->attributes['deposit_amount'], (float)$this->amount);
        }

        if ($this->attributes['deposit_percentage'] !== null && (float)$this->attributes['deposit_percentage'] > 0) {
            return round(((float)$this->amount * (float)$this->attributes['deposit_percentage']) / 100, 2);
        }

        // Si no está definido en el registro, consultar Ajustes
        $type = Setting::get('deposit_type', 'percentage');
        if ($type === 'fixed') {
            $fixed = (float)Setting::get('deposit_fixed_amount', 200);
            return min($fixed, (float)$this->amount);
        }

        $pct = (float)Setting::get('deposit_percentage', 40);
        return round(((float)$this->amount * $pct) / 100, 2);
    }

    /**
     * Devuelve el importe restante a pagar el día del evento
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float)$this->amount - $this->signal_amount);
    }

    /**
     * Devuelve la etiqueta descriptiva de la señal (ej: "40% (280,00 €)" o "200,00 € (Fijo)")
     */
    public function getSignalLabelAttribute(): string
    {
        $type = $this->deposit_type ?: Setting::get('deposit_type', 'percentage');
        if ($type === 'fixed') {
            return number_format($this->signal_amount, 2, ',', '.') . ' €';
        }

        $pct = $this->deposit_percentage !== null ? (float)$this->deposit_percentage : (float)Setting::get('deposit_percentage', 40);
        return rtrim(rtrim(number_format($pct, 2, ',', '.'), '0'), ',') . '% (' . number_format($this->signal_amount, 2, ',', '.') . ' €)';
    }
}
