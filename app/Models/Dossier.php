<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa el dossier informativo o técnico asociado a un evento.
 */
class Dossier extends Model
{
    use HasFactory;
    protected $fillable = ['event_id', 'content', 'pdf_path'];

    /**
     * Relación con el evento al que pertenece el dossier.
     */
    public function event() { return $this->belongsTo(Event::class); }
}
