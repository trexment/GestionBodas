<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Modelo que representa una reunión o cita (presencial, videollamada o telefónica) con el cliente de un evento.
 */
class ClientMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'title',
        'meeting_type',
        'meeting_date',
        'duration_minutes',
        'location_type',
        'location',
        'video_call_url',
        'status',
        'summary',
        'notes',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    /**
     * Relación con el evento al que pertenece la reunión.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relación con el miembro del equipo responsable de la reunión.
     */
    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Devuelve la etiqueta descriptiva del tipo de reunión.
     */
    public function getMeetingTypeLabelAttribute(): string
    {
        return match ($this->meeting_type) {
            'primera_toma' => 'Primera Toma de Contacto',
            'visita_tecnica' => 'Visita Técnica Finca / Salón',
            'escaleta_musica' => 'Reunión Escaleta Musical',
            'magia_guion' => 'Reunión Guion & Magia',
            default => 'Reunión General',
        };
    }

    /**
     * Devuelve el icono representativo del tipo de reunión.
     */
    public function getMeetingTypeIconAttribute(): string
    {
        return match ($this->meeting_type) {
            'primera_toma' => '☕',
            'visita_tecnica' => '🏰',
            'escaleta_musica' => '🎵',
            'magia_guion' => '🎩',
            default => '📅',
        };
    }

    /**
     * Devuelve la etiqueta legible de la modalidad de ubicación de la reunión.
     */
    public function getLocationTypeLabelAttribute(): string
    {
        return match ($this->location_type) {
            'video_call' => 'Videollamada',
            'phone' => 'Llamada Telefónica',
            default => 'Presencial',
        };
    }

    /**
     * Devuelve el icono representativo de la modalidad de ubicación de la reunión.
     */
    public function getLocationTypeIconAttribute(): string
    {
        return match ($this->location_type) {
            'video_call' => '💻',
            'phone' => '📱',
            default => '📍',
        };
    }

    /**
     * Devuelve la etiqueta legible del estado de la reunión.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'Realizada',
            'cancelled' => 'Cancelada',
            default => 'Programada',
        };
    }

    /**
     * Devuelve las clases CSS para el distintivo visual del estado de la reunión.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800',
            default => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        };
    }
}
