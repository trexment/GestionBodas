<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

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
