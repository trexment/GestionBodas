<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo que gestiona las compras de material, equipamiento técnico y gastos deducibles de los eventos.
 */
class MaterialExpense extends Model
{
    use HasFactory;

    protected $table = 'material_expenses';

    protected $fillable = [
        'event_id',
        'season',
        'concept',
        'category',
        'amount',
        'purchase_date',
        'paid_by',
        'is_reimbursed',
        'reimbursed_at',
        'payment_method',
        'supplier',
        'invoice_number',
        'receipt_file',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'purchase_date' => 'date',
        'is_reimbursed' => 'boolean',
        'reimbursed_at' => 'date',
    ];

    protected static bool $schemaChecked = false;

    /**
     * Verifica y crea automáticamente la tabla material_expenses si aún no existe en la base de datos.
     */
    public static function ensureTableExists(): void
    {
        if (static::$schemaChecked) {
            return;
        }
        static::$schemaChecked = true;

        try {
            if (!Schema::hasTable('material_expenses')) {
                Schema::create('material_expenses', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
                    $table->string('season', 10)->nullable()->index();
                    $table->string('concept', 255);
                    $table->string('category', 50)->default('general');
                    $table->decimal('amount', 10, 2)->default(0);
                    $table->date('purchase_date');
                    $table->string('paid_by', 100)->default('Fran');
                    $table->boolean('is_reimbursed')->default(false);
                    $table->date('reimbursed_at')->nullable();
                    $table->string('payment_method', 50)->default('card');
                    $table->string('supplier', 150)->nullable();
                    $table->string('invoice_number', 100)->nullable();
                    $table->string('receipt_file', 255)->nullable();
                    $table->text('notes')->nullable();
                    $table->timestamps();
                });
            } else {
                if (!Schema::hasColumn('material_expenses', 'is_reimbursed')) {
                    try {
                        Schema::table('material_expenses', function (Blueprint $table) {
                            $table->boolean('is_reimbursed')->default(false)->after('paid_by');
                            $table->date('reimbursed_at')->nullable()->after('is_reimbursed');
                        });
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `material_expenses` ADD `is_reimbursed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `paid_by`");
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `material_expenses` ADD `reimbursed_at` DATE NULL AFTER `is_reimbursed`");
                    }
                }

                // Limpieza de duplicado idéntico accidental en Larriba si existe
                try {
                    $dups = \Illuminate\Support\Facades\DB::table('material_expenses')
                        ->where('amount', 450.00)
                        ->where(function($q) {
                            $q->where('concept', 'like', '%Larriba%')
                              ->orWhere('supplier', 'like', '%Marketing%')
                              ->orWhere('notes', 'like', '%marketing%');
                        })
                        ->orderBy('id', 'desc')
                        ->get();
                    if ($dups->count() > 1) {
                        \Illuminate\Support\Facades\DB::table('material_expenses')->where('id', $dups->first()->id)->delete();
                    }
                } catch (\Throwable $ex) {
                    // Silencioso
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
     * Relación con el evento al que está asignado el gasto (si es específico de un evento).
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Devuelve las categorías disponibles para gastos de material.
     */
    public static function getCategories(): array
    {
        return [
            'cena_empresa' => ['label' => 'Cena / Comida de Empresa (Navidad, etc.)', 'icon' => '🥂'],
            'team_dietas' => ['label' => 'Dietas, Comidas y Celebraciones de Equipo', 'icon' => '🍽️'],
            'gastos_empresa' => ['label' => 'Gastos Generales de Empresa / Fondo Común', 'icon' => '🏢'],
            'sonido' => ['label' => 'Sonido & Audio', 'icon' => '🔊'],
            'sound_lighting' => ['label' => 'Sonido e Iluminación', 'icon' => '🔊'],
            'iluminacion' => ['label' => 'Iluminación & Efectos', 'icon' => '💡'],
            'fotomaton_360' => ['label' => 'Fotomatón & Plataforma 360º', 'icon' => '📸'],
            'photobooth_360' => ['label' => 'Fotomatón & Plataforma 360º', 'icon' => '📸'],
            'cables_conectores' => ['label' => 'Cables & Conectores', 'icon' => '🔌'],
            'it_cables' => ['label' => 'Informática & Cableado', 'icon' => '🔌'],
            'fuel_transport' => ['label' => 'Combustible & Transporte', 'icon' => '⛽'],
            'atrezo' => ['label' => 'Atrezo & Fungibles', 'icon' => '🎭'],
            'consumables' => ['label' => 'Consumibles & Atrezzo', 'icon' => '📦'],
            'mantenimiento' => ['label' => 'Mantenimiento & Reparación', 'icon' => '🔧'],
            'general' => ['label' => 'Material General', 'icon' => '🛠️'],
            'otros' => ['label' => 'Otros Gastos', 'icon' => '🏷️'],
        ];
    }

    /**
     * Devuelve la etiqueta y el icono de la categoría de este gasto.
     */
    public function getCategoryInfoAttribute(): array
    {
        $cats = self::getCategories();
        return $cats[$this->category] ?? ['label' => ucfirst($this->category ?: 'General'), 'icon' => '📦'];
    }

    /**
     * Devuelve solo la etiqueta legible de la categoría.
     */
    public function getCategoryLabelAttribute(): string
    {
        return $this->category_info['label'] ?? ucfirst($this->category ?: 'General');
    }

    /**
     * Devuelve solo el icono de la categoría.
     */
    public function getCategoryIconAttribute(): string
    {
        return $this->category_info['icon'] ?? '📦';
    }

    /**
     * Devuelve la etiqueta legible del método de pago.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'card' => 'Tarjeta / TPV',
            'transfer' => 'Transferencia Bancaria',
            'bizum' => 'Bizum',
            'cash' => 'Efectivo',
            default => 'Otro',
        };
    }

    /**
     * Devuelve el icono asociado al método de pago.
     */
    public function getPaymentMethodIconAttribute(): string
    {
        return match ($this->payment_method) {
            'card' => '💳',
            'transfer' => '🏦',
            'bizum' => '📱',
            'cash' => '💵',
            default => '📝',
        };
    }
}
