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
        'tax_type',
        'tax_rate',
        'subtotal_amount',
        'tax_amount',
        'payment_methods',
    ];

    protected $casts = [
        'payment_methods' => 'array',
        'tax_rate' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
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

    /**
     * Métodos de pago permitidos en esta propuesta
     */
    public function getActivePaymentMethodsAttribute(): array
    {
        if (is_array($this->payment_methods) && !empty($this->payment_methods)) {
            return $this->payment_methods;
        }

        // Por defecto: transferencia y bizum
        return ['transfer', 'bizum'];
    }

    /**
     * Etiqueta del régimen de IVA
     */
    public function getTaxLabelAttribute(): string
    {
        $type = $this->tax_type ?: 'included';
        $rate = $this->tax_rate ?: 21.00;

        return match ($type) {
            'included' => 'IVA incluido (' . rtrim(rtrim(number_format($rate, 2, ',', '.'), '0'), ',') . '%)',
            'excluded' => '+ ' . rtrim(rtrim(number_format($rate, 2, ',', '.'), '0'), ',') . '% IVA',
            'none' => 'Exento / Sin IVA',
            default => 'IVA incluido (' . rtrim(rtrim(number_format($rate, 2, ',', '.'), '0'), ',') . '%)',
        };
    }

    /**
     * Base imponible calculada
     */
    public function getComputedSubtotalAttribute(): float
    {
        if ($this->subtotal_amount !== null && (float)$this->subtotal_amount > 0) {
            return (float)$this->subtotal_amount;
        }

        $type = $this->tax_type ?: 'included';
        $rate = (float)($this->tax_rate ?: 21.00);

        if ($type === 'included') {
            return round((float)$this->amount / (1 + ($rate / 100)), 2);
        }

        return (float)$this->amount;
    }

    /**
     * Cuota de IVA calculada
     */
    public function getComputedTaxAttribute(): float
    {
        if ($this->tax_amount !== null) {
            return (float)$this->tax_amount;
        }

        $type = $this->tax_type ?: 'included';
        $rate = (float)($this->tax_rate ?: 21.00);

        if ($type === 'included') {
            return round((float)$this->amount - $this->computed_subtotal, 2);
        }

        if ($type === 'excluded') {
            return round(((float)$this->amount * $rate) / 100, 2);
        }

        return 0.00;
    }
}
