<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo que gestiona las reservas, costes y pagos (señal y liquidación) a proveedores externos por evento.
 */
class EventProviderBooking extends Model
{
    use HasFactory;

    protected $table = 'event_provider_bookings';

    protected $fillable = [
        'event_id',
        'provider_key',
        'provider_name',
        'service_description',
        'client_price',
        'provider_total_cost',
        'deposit_paid',
        'deposit_amount',
        'deposit_paid_at',
        'deposit_payment_method',
        'final_paid',
        'final_amount',
        'final_paid_at',
        'final_payment_method',
        'paid_by',
        'is_reimbursed',
        'reimbursed_at',
        'has_refund',
        'refund_amount',
        'refund_recipient',
        'refund_fran_amount',
        'refund_miguel_amount',
        'management_fee',
        'social_security_fee',
        'tax_fee',
        'registration_fee',
        'settlement_number',
        'invoice_number',
        'notes',
    ];

    protected $casts = [
        'client_price' => 'decimal:2',
        'provider_total_cost' => 'decimal:2',
        'deposit_paid' => 'boolean',
        'deposit_amount' => 'decimal:2',
        'deposit_paid_at' => 'date',
        'final_paid' => 'boolean',
        'final_amount' => 'decimal:2',
        'final_paid_at' => 'date',
        'is_reimbursed' => 'boolean',
        'reimbursed_at' => 'date',
        'has_refund' => 'boolean',
        'refund_amount' => 'decimal:2',
        'refund_fran_amount' => 'decimal:2',
        'refund_miguel_amount' => 'decimal:2',
        'management_fee' => 'decimal:2',
        'social_security_fee' => 'decimal:2',
        'tax_fee' => 'decimal:2',
        'registration_fee' => 'decimal:2',
    ];

    protected static bool $schemaChecked = false;

    /**
     * Verifica y crea automáticamente la tabla event_provider_bookings si aún no existe en la base de datos.
     */
    public static function ensureTableExists(): void
    {
        if (static::$schemaChecked) {
            return;
        }
        static::$schemaChecked = true;

        try {
            if (!Schema::hasTable('event_provider_bookings')) {
                Schema::create('event_provider_bookings', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                    $table->string('provider_key', 50)->nullable();
                    $table->string('provider_name', 150)->nullable();
                    $table->string('service_description', 255)->nullable();
                    $table->decimal('client_price', 10, 2)->default(0);
                    $table->decimal('provider_total_cost', 10, 2)->default(0);
                    $table->boolean('deposit_paid')->default(false);
                    $table->decimal('deposit_amount', 10, 2)->default(0);
                    $table->date('deposit_paid_at')->nullable();
                    $table->string('deposit_payment_method', 50)->nullable();
                    $table->boolean('final_paid')->default(false);
                    $table->decimal('final_amount', 10, 2)->default(0);
                    $table->date('final_paid_at')->nullable();
                    $table->string('final_payment_method', 50)->nullable();
                    $table->string('paid_by', 100)->nullable()->default('Fran');
                    $table->text('notes')->nullable();
                    $table->timestamps();
                });
            } else {
                if (!Schema::hasColumn('event_provider_bookings', 'paid_by')) {
                    try {
                        Schema::table('event_provider_bookings', function (Blueprint $table) {
                            $table->string('paid_by', 100)->nullable()->default('Fran')->after('final_payment_method');
                        });
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `paid_by` VARCHAR(100) NULL DEFAULT 'Fran' AFTER `final_payment_method`");
                    }
                }
                if (!Schema::hasColumn('event_provider_bookings', 'has_refund')) {
                    try {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `has_refund` TINYINT(1) NOT NULL DEFAULT 0 AFTER `paid_by`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `has_refund`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `refund_recipient` VARCHAR(50) NOT NULL DEFAULT '50_50' AFTER `refund_amount`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `refund_fran_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `refund_recipient`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `refund_miguel_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `refund_fran_amount`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `management_fee` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `refund_miguel_amount`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `social_security_fee` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `management_fee`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `tax_fee` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `social_security_fee`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `registration_fee` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `tax_fee`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `settlement_number` VARCHAR(100) NULL AFTER `registration_fee`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `invoice_number` VARCHAR(100) NULL AFTER `settlement_number`");
                    } catch (\Throwable $ex) {
                        // Silencioso
                    }
                }
                if (!Schema::hasColumn('event_provider_bookings', 'is_reimbursed')) {
                    try {
                        Schema::table('event_provider_bookings', function (Blueprint $table) {
                            $table->boolean('is_reimbursed')->default(false)->after('paid_by');
                            $table->date('reimbursed_at')->nullable()->after('is_reimbursed');
                        });
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `is_reimbursed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `paid_by`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `reimbursed_at` DATE NULL AFTER `is_reimbursed`");
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silencioso si no hay permisos de DDL en este instante
        }
    }

    protected static function boot()
    {
        parent::boot();
        static::ensureTableExists();
    }

    /**
     * Relación con el evento al que corresponde la reserva del proveedor.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Formatea el código de método de pago en una etiqueta legible en español.
     */
    public static function formatMethodLabel(?string $method): string
    {
        return match ($method) {
            'bizum' => 'Bizum',
            'transfer' => 'Transferencia Bancaria',
            'cash' => 'Efectivo',
            'card' => 'Tarjeta / TPV',
            'other' => 'Otro',
            default => $method ? ucfirst($method) : 'No especificado',
        };
    }

    /**
     * Devuelve el icono asociado al método de pago indicado.
     */
    public static function formatMethodIcon(?string $method): string
    {
        return match ($method) {
            'bizum' => '📱',
            'transfer' => '🏦',
            'cash' => '💵',
            'card' => '💳',
            default => '💶',
        };
    }

    /**
     * Devuelve la etiqueta legible del método de pago de la señal al proveedor.
     */
    public function getDepositMethodLabelAttribute(): string
    {
        return self::formatMethodLabel($this->deposit_payment_method);
    }

    /**
     * Devuelve el icono del método de pago de la señal al proveedor.
     */
    public function getDepositMethodIconAttribute(): string
    {
        return self::formatMethodIcon($this->deposit_payment_method);
    }

    /**
     * Devuelve la etiqueta legible del método de pago de la liquidación final al proveedor.
     */
    public function getFinalMethodLabelAttribute(): string
    {
        return self::formatMethodLabel($this->final_payment_method);
    }

    /**
     * Devuelve el icono del método de pago de la liquidación final al proveedor.
     */
    public function getFinalMethodIconAttribute(): string
    {
        return self::formatMethodIcon($this->final_payment_method);
    }

    /**
     * Calcula el importe total abonado al proveedor sumando señal y liquidación final.
     */
    public function getTotalPaidAttribute(): float
    {
        $dep = ($this->deposit_paid && (float) $this->deposit_amount > 0) ? (float) $this->deposit_amount : 0.0;
        $fin = ($this->final_paid && (float) $this->final_amount > 0) ? (float) $this->final_amount : 0.0;
        return round($dep + $fin, 2);
    }

    /**
     * Calcula el importe pendiente de pago al proveedor.
     */
    public function getPendingAmountAttribute(): float
    {
        $cost = max((float) $this->provider_total_cost, $this->total_paid);
        return max(0.0, round($cost - $this->total_paid, 2));
    }

    /**
     * Determina la clave de estado de pago al proveedor ('paid', 'deposit_paid' o 'unpaid').
     */
    public function getStatusKeyAttribute(): string
    {
        $totalPaid = $this->total_paid;
        $pending = $this->pending_amount;
        $cost = (float) $this->provider_total_cost;

        if ($totalPaid > 0 && ($pending <= 0.05 && ($cost > 0 || $this->final_paid))) {
            return 'paid'; // Liquidado 100% al proveedor
        }
        if ($totalPaid > 0) {
            return 'deposit_paid'; // Señal pagada, falta el resto
        }
        return 'unpaid'; // Pendiente de pagar señal al proveedor
    }

    /**
     * Devuelve la etiqueta descriptiva del estado de pago al proveedor.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_key) {
            'paid' => '✅ Todo Pagado al Proveedor',
            'deposit_paid' => '🟢 Señal Pagada (Falta Resto)',
            default => '⏳ Pendiente de Pagar Señal',
        };
    }

    /**
     * Coste neto real asumido por el evento tras descontar la devolución/liquidación de cooperativa (Artístamente).
     */
    public function getNetCostAttribute(): float
    {
        $cost = (float) $this->provider_total_cost;
        $refund = ($this->has_refund && (float) $this->refund_amount > 0) ? (float) $this->refund_amount : 0.0;
        return max(0.0, round($cost - $refund, 2));
    }

    /**
     * Importe de devolución de Artístamente ingresado a Fran.
     */
    public function getRefundFranCalculatedAttribute(): float
    {
        if (!$this->has_refund || (float) $this->refund_amount <= 0) {
            return 0.0;
        }
        return match ($this->refund_recipient) {
            'fran' => (float) $this->refund_amount,
            'miguel', 'ezequiel' => 0.0,
            'joint' => 0.0,
            'custom' => (float) $this->refund_fran_amount,
            default => round((float) $this->refund_amount / 2, 2), // 50_50
        };
    }

    /**
     * Importe de devolución de Artístamente ingresado a Miguel.
     */
    public function getRefundMiguelCalculatedAttribute(): float
    {
        if (!$this->has_refund || (float) $this->refund_amount <= 0) {
            return 0.0;
        }
        return match ($this->refund_recipient) {
            'miguel' => (float) $this->refund_amount,
            'fran', 'ezequiel' => 0.0,
            'joint' => 0.0,
            'custom' => (float) $this->refund_miguel_amount,
            default => round((float) $this->refund_amount - round((float) $this->refund_amount / 2, 2), 2), // resto de 50_50
        };
    }

    /**
     * Importe de devolución de Artístamente ingresado a Ezequiel.
     */
    public function getRefundEzequielCalculatedAttribute(): float
    {
        if (!$this->has_refund || (float) $this->refund_amount <= 0) {
            return 0.0;
        }
        return match ($this->refund_recipient) {
            'ezequiel' => (float) $this->refund_amount,
            default => 0.0,
        };
    }

    /**
     * Importe de devolución de Artístamente ingresado en Cuenta Conjunta.
     */
    public function getRefundJointCalculatedAttribute(): float
    {
        if (!$this->has_refund || (float) $this->refund_amount <= 0 || $this->refund_recipient !== 'joint') {
            return 0.0;
        }
        return (float) $this->refund_amount;
    }

    /**
     * Etiqueta amigable del destinatario de la devolución.
     */
    public function getRefundRecipientLabelAttribute(): string
    {
        return match ($this->refund_recipient) {
            'fran' => 'En banco de Fran (se divide en el arqueo)',
            'miguel' => 'En banco de Miguel (se divide en el arqueo)',
            'ezequiel' => 'En banco de Ezequiel (se divide en el arqueo)',
            'joint' => 'Cuenta Conjunta / Fondo Común',
            'custom' => 'Personalizado (' . number_format((float)$this->refund_fran_amount, 2, ',', '.') . '€ Fran / ' . number_format((float)$this->refund_miguel_amount, 2, ',', '.') . '€ Miguel)',
            default => 'Mitad a cada uno (50% Fran / 50% Miguel)',
        };
    }
}
