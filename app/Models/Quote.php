<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'brand',
        'amount',
        'status',
        'rejection_reason',
        'client_notes',
        'responded_at',
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
        'responded_at' => 'datetime',
    ];

    protected static bool $schemaChecked = false;

    protected static function booted(): void
    {
        static::EnsureQuoteResponseColumns();
    }

    public static function EnsureQuoteResponseColumns(): void
    {
        if (static::$schemaChecked) {
            return;
        }
        static::$schemaChecked = true;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('quotes')) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('quotes', 'rejection_reason')) {
                    \Illuminate\Support\Facades\Schema::table('quotes', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->text('rejection_reason')->nullable()->after('status');
                        $table->text('client_notes')->nullable()->after('rejection_reason');
                        $table->timestamp('responded_at')->nullable()->after('client_notes');
                    });
                }
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `quotes` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
            }
        } catch (\Throwable $e) {
            // Silencioso si el motor es SQLite o ya está aplicado
        }
    }

    public function getPublicUrlAttribute(): string
    {
        if (!$this->event || empty($this->event->token)) {
            return url('/presupuesto');
        }
        return route('guest.quote.view', [
            'token' => $this->event->token,
            'quote' => $this->id,
        ]);
    }

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
        // 1. Si está explícitamente configurado como 'none' (sin señal)
        if ($this->deposit_type === 'none') {
            return 0.0;
        }

        // 2. Si se definió un importe fijo explícito (incluso si es 0)
        if ($this->attributes['deposit_amount'] !== null) {
            $amt = (float)$this->attributes['deposit_amount'];
            if ($amt <= 0.0 && ($this->deposit_type === 'fixed' || $this->deposit_type === 'none')) {
                return 0.0;
            }
            if ($amt > 0) {
                return min($amt, (float)$this->amount);
            }
        }

        // 3. Si se definió un porcentaje explícito (incluso si es 0%)
        if ($this->attributes['deposit_percentage'] !== null) {
            $pct = (float)$this->attributes['deposit_percentage'];
            if ($pct <= 0.0 && ($this->deposit_type === 'percentage' || $this->deposit_type === 'none')) {
                return 0.0;
            }
            if ($pct > 0) {
                return round(((float)$this->amount * $pct) / 100, 2);
            }
        }

        // 4. Si el evento es de tipo fiesta y no se indicó señal, por defecto es 0 €
        if ($this->event && $this->event->event_type_clean === 'fiesta') {
            return 0.0;
        }

        // 5. Fallback a Ajustes generales
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
     * Devuelve la etiqueta descriptiva de la señal (ej: "40% (280,00 €)", "200,00 €" o "Sin señal (0,00 €)")
     */
    public function getSignalLabelAttribute(): string
    {
        if ($this->deposit_type === 'none' || $this->signal_amount <= 0) {
            return 'Sin señal (0,00 €)';
        }

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

    public function getBrandCleanAttribute(): string
    {
        if (!empty($this->attributes['brand'])) {
            return $this->attributes['brand'];
        }
        if ($this->event && !empty($this->event->brand_clean)) {
            return $this->event->brand_clean;
        }
        if (!app()->runningInConsole() && request() && str_contains(strtolower(request()->getHost()), 'javnx')) {
            return 'javnx';
        }
        if (!app()->runningInConsole() && request() && str_contains(strtolower(request()->getHost()), 'leugim')) {
            return 'mago_leugim';
        }
        return 'nunez_and_son';
    }

    public function getBrandInfoAttribute(): array
    {
        return Setting::getBrandInfo($this->brand_clean);
    }

    /**
     * Días de validez garantizada de esta propuesta comercial
     */
    public function getValidityDaysAttribute(): int
    {
        return (int)Setting::get('quote_validity_days', 15);
    }

    /**
     * Fecha exacta de caducidad de la oferta
     */
    public function getExpiresAtAttribute(): \Carbon\Carbon
    {
        $baseDate = $this->created_at ? $this->created_at->copy() : now();
        return $baseDate->addDays($this->validity_days);
    }

    /**
     * Indica si la propuesta ya ha caducado
     */
    public function getIsExpiredAttribute(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    /**
     * Fecha de caducidad en formato legible (ej: 28 de octubre de 2026)
     */
    public function getExpiresAtFormattedAttribute(): string
    {
        return $this->expires_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    }

    /**
     * Fecha de emisión en formato legible (ej: 13 de octubre de 2026)
     */
    public function getCreatedAtFormattedAttribute(): string
    {
        $date = $this->created_at ?: now();
        return $date->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    }
}
