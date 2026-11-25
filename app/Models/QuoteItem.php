<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa un concepto o línea de servicio dentro de un presupuesto.
 */
class QuoteItem extends Model
{
    use HasFactory;

    protected static bool $schemaChecked = false;
    protected static bool $supportsTextDescription = false;

    protected $fillable = [
        'quote_id',
        'service_name',
        'description',
        'price',
        'quantity',
        'item_status',
        'item_type',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Registra el evento de guardado para asegurar compatibilidad de longitud en las columnas de texto.
     */
    protected static function booted(): void
    {
        static::saving(function (QuoteItem $item) {
            if (!self::$schemaChecked) {
                self::$schemaChecked = true;
                try {
                    $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
                    if (in_array($driver, ['mysql', 'mariadb'])) {
                        \Illuminate\Support\Facades\DB::statement('ALTER TABLE quote_items MODIFY description TEXT NULL');
                        self::$supportsTextDescription = true;
                    } elseif ($driver === 'sqlite') {
                        self::$supportsTextDescription = true;
                    }
                } catch (\Throwable $e) {
                    self::$supportsTextDescription = false;
                }
            }

            if (!self::$supportsTextDescription && !empty($item->description) && mb_strlen($item->description) > 250) {
                $item->description = mb_substr($item->description, 0, 247) . '...';
            }

            if (!empty($item->service_name) && mb_strlen($item->service_name) > 250) {
                $item->service_name = mb_substr($item->service_name, 0, 250);
            }
        });
    }

    /**
     * Relación con el presupuesto al que pertenece esta línea de concepto.
     */
    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Indica si el concepto es un extra opcional no sumado al total obligatorio.
     */
    public function getIsOptionalAttribute(): bool
    {
        return ($this->item_status ?? 'normal') === 'optional';
    }

    /**
     * Indica si el concepto está incluido en la propuesta (0 €).
     */
    public function getIsIncludedAttribute(): bool
    {
        return ($this->item_status ?? 'normal') === 'included';
    }

    /**
     * Indica si el concepto es una partida contratada estándar.
     */
    public function getIsNormalAttribute(): bool
    {
        return ($this->item_status ?? 'normal') === 'normal';
    }
}
