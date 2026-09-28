<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_equipment')
                    ->withPivot('id', 'quantity', 'notes')
                    ->withTimestamps();
    }
}
