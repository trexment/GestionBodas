<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa una pista reproducida en el historial de sesión DJ de un evento.
 */
class EventDjHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'session_name',
        'order',
        'title',
        'artist',
        'played_at_time',
        'bpm',
        'key',
        'duration',
        'genre',
        'source_software',
        'matched_request_id',
    ];

    protected $casts = [
        'order' => 'integer',
        'bpm' => 'decimal:1',
    ];

    /**
     * Relación con el evento al que pertenece este registro del historial DJ.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relación con la petición musical coincidente del evento, si existe.
     */
    public function matchedRequest()
    {
        return $this->belongsTo(EventMusicRequest::class, 'matched_request_id');
    }

    /**
     * Nombre formateado "Artista - Título" o "Título"
     */
    public function getFormattedTrackAttribute(): string
    {
        if (!empty($this->artist)) {
            return "{$this->artist} - {$this->title}";
        }
        return $this->title;
    }

    /**
     * Icono según software de origen
     */
    public function getSourceIconAttribute(): string
    {
        return match($this->source_software) {
            'engine_dj' => '🎧 Denon Engine DJ',
            'rekordbox' => '💿 Pioneer Rekordbox',
            'serato' => '🎛️ Serato DJ',
            'traktor' => '🎹 NI Traktor',
            'm3u' => '🎵 Playlist M3U',
            default => '📄 CSV / Historial',
        };
    }
}
