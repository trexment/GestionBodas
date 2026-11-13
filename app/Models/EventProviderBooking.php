<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
    ];

    protected static bool $schemaChecked = false;

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
                    $table->text('notes')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            // Silencioso si no hay permisos de DDL en este instante
        }
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

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

    public function getDepositMethodLabelAttribute(): string
    {
        return self::formatMethodLabel($this->deposit_payment_method);
    }

    public function getDepositMethodIconAttribute(): string
    {
        return self::formatMethodIcon($this->deposit_payment_method);
    }

    public function getFinalMethodLabelAttribute(): string
    {
        return self::formatMethodLabel($this->final_payment_method);
    }

    public function getFinalMethodIconAttribute(): string
    {
        return self::formatMethodIcon($this->final_payment_method);
    }

    public function getTotalPaidAttribute(): float
    {
        $dep = ($this->deposit_paid && (float) $this->deposit_amount > 0) ? (float) $this->deposit_amount : 0.0;
        $fin = ($this->final_paid && (float) $this->final_amount > 0) ? (float) $this->final_amount : 0.0;
        return round($dep + $fin, 2);
    }

    public function getPendingAmountAttribute(): float
    {
        $cost = max((float) $this->provider_total_cost, $this->total_paid);
        return max(0.0, round($cost - $this->total_paid, 2));
    }

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

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_key) {
            'paid' => '✅ Todo Pagado al Proveedor',
            'deposit_paid' => '🟢 Señal Pagada (Falta Resto)',
            default => '⏳ Pendiente de Pagar Señal',
        };
    }
}
