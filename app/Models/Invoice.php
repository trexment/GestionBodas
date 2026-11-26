<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa una factura oficial o un recibo de pago vinculado a un evento.
 */
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'quote_id',
        'invoice_number',
        'type', // 'factura' o 'recibo'
        'amount',
        'tax',
        'tax_rate',
        'total',
        'issue_date',
        'status', // 'unpaid', 'paid', 'cancelled'
        'pdf_path',
        'notes',
        'payment_method',
        'collected_by',
        'transferred_to_joint',
        'transferred_at',
    ];

    protected static bool $schemaChecked = false;

    /**
     * Verifica y añade la columna collected_by si no existe en la tabla invoices.
     */
    public static function ensureColumnsExist(): void
    {
        if (static::$schemaChecked) {
            return;
        }
        static::$schemaChecked = true;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('invoices')) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'collected_by')) {
                    try {
                        \Illuminate\Support\Facades\Schema::table('invoices', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->string('collected_by', 100)->nullable()->default('Fran')->after('payment_method');
                        });
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `invoices` ADD `collected_by` VARCHAR(100) NULL DEFAULT 'Fran' AFTER `payment_method`");
                    }
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'transferred_to_joint')) {
                    try {
                        \Illuminate\Support\Facades\Schema::table('invoices', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->boolean('transferred_to_joint')->default(false)->after('collected_by');
                            $table->date('transferred_at')->nullable()->after('transferred_to_joint');
                        });
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `invoices` ADD `transferred_to_joint` TINYINT(1) NOT NULL DEFAULT 0 AFTER `collected_by`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `invoices` ADD `transferred_at` DATE NULL AFTER `transferred_to_joint`");
                    }
                }
            }
        } catch (\Throwable $e) {}
    }

    protected static function boot()
    {
        parent::boot();
        static::ensureColumnsExist();
    }

    protected $casts = [
        'issue_date' => 'date',
        'transferred_at' => 'date',
        'transferred_to_joint' => 'boolean',
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Relación con el evento al que corresponde la factura o recibo.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relación con el presupuesto del que procede la factura o recibo.
     */
    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Comprueba si este documento es un Recibo
     */
    public function isReceipt(): bool
    {
        return ($this->type === 'recibo') || ((float)$this->tax === 0.0 && str_starts_with($this->invoice_number, 'REC'));
    }

    /**
     * Comprueba si este documento es una Factura
     */
    public function isInvoice(): bool
    {
        return !$this->isReceipt();
    }

    /**
     * Etiqueta del tipo de documento
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return $this->isReceipt() ? 'Recibo' : 'Factura';
    }

    /**
     * Método de pago efectivo (explícito, extraído de las notas o heredado del evento)
     */
    public function getEffectivePaymentMethodAttribute(): ?string
    {
        $pm = strtolower(trim((string) ($this->attributes['payment_method'] ?? '')));
        if (in_array($pm, ['bizum', 'transfer', 'cash', 'card', 'other'], true)) {
            return $pm;
        }

        $notesLower = mb_strtolower((string) ($this->notes ?? ''));
        if (str_contains($notesLower, 'bizum')) {
            return 'bizum';
        }
        if (str_contains($notesLower, 'transferencia') || str_contains($notesLower, 'banco') || str_contains($notesLower, 'iban')) {
            return 'transfer';
        }
        if (str_contains($notesLower, 'efectivo') || str_contains($notesLower, 'metálico') || str_contains($notesLower, 'metalico')) {
            return 'cash';
        }
        if (str_contains($notesLower, 'tarjeta') || str_contains($notesLower, 'tpv')) {
            return 'card';
        }

        if ($this->relationLoaded('event') && $this->event && !empty($this->event->deposit_payment_method)) {
            if (str_contains($notesLower, 'señal') || str_contains($notesLower, 'senal') || str_contains($notesLower, 'reserva')) {
                return $this->event->deposit_payment_method;
            }
        }

        return $pm !== '' ? $pm : null;
    }

    /**
     * Etiqueta legible del método de pago
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        $pm = $this->effective_payment_method;
        return match ($pm) {
            'bizum' => 'Bizum',
            'transfer' => 'Transferencia Bancaria',
            'cash' => 'Efectivo',
            'card' => 'Tarjeta / TPV',
            'other' => 'Otro',
            default => $pm ? ucfirst($pm) : 'No especificado',
        };
    }

    /**
     * Icono del método de pago
     */
    public function getPaymentMethodIconAttribute(): string
    {
        return match ($this->effective_payment_method) {
            'bizum' => '📱',
            'transfer' => '🏦',
            'cash' => '💵',
            'card' => '💳',
            default => '💶',
        };
    }

    /**
     * Concepto resumido del cobro / documento
     */
    public function getPaymentConceptLabelAttribute(): string
    {
        $notesLower = mb_strtolower((string) ($this->notes ?? ''));
        if (str_contains($notesLower, 'señal') || str_contains($notesLower, 'senal') || str_contains($notesLower, 'reserva')) {
            return 'Señal de Reserva';
        }
        if (str_contains($notesLower, 'liquidación') || str_contains($notesLower, 'liquidacion') || str_contains($notesLower, 'final')) {
            return 'Liquidación Final';
        }
        return $this->isReceipt() ? 'Recibo de Pago' : 'Factura Oficial';
    }

    /**
     * Generador automático del siguiente número correlativo de Factura o Recibo
     * Ej: FAC-2026-001 o REC-2026-001
     */
    public static function nextNumber(string $type = 'factura'): string
    {
        $year = date('Y');
        $prefix = ($type === 'recibo') ? 'REC-' . $year . '-' : 'FAC-' . $year . '-';

        // Buscar el último emitido de ese año con ese prefijo
        $last = self::where('invoice_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $lastNumberPart = substr($last->invoice_number, strlen($prefix));
            $nextSeq = (int)$lastNumberPart + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
    }
}
