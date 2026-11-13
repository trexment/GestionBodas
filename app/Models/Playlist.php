<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa una lista de reproducción musical asociada a un evento.
 */
class Playlist extends Model
{
    use HasFactory;
    protected $fillable = ['event_id', 'name'];
    
    /**
     * Relación con el evento al que pertenece la lista de reproducción.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relación muchos a muchos con las pistas musicales incluidas en la lista de reproducción.
     */
    public function tracks()
    {
        return $this->belongsToMany(Track::class)->withPivot('order')->orderBy('pivot_order')->withTimestamps();
    }
}
