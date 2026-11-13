<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa un equipo o elemento de inventario técnico (sonido, iluminación, efectos, etc.).
 */
class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'brand_model',
        'quantity',
        'status',
        'is_dmx',
        'dmx_mode',
        'dmx_address',
        'notes',
    ];

    /**
     * Relación muchos a muchos con los eventos en los que está asignado este equipamiento.
     */
    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_equipment')
                    ->withPivot('id', 'quantity', 'notes')
                    ->withTimestamps();
    }
}
