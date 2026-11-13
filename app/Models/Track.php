<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa una pista de audio o canción de la biblioteca musical.
 */
class Track extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'artist',
        'genre',
        'bpm',
        'file_path',
        'cloud_id',
        'cloud_folder',
        'source',
        'spotify_url',
        'apple_music_url',
        'tidal_url',
        'youtube_url',
    ];

    /**
     * Obtiene la URL de reproducción o streaming del archivo de audio de la pista.
     */
    public function getAudioUrlAttribute(): ?string
    {
        if (!empty($this->cloud_id) && $this->source === 'google_drive') {
            return url('/api/drive-stream/' . $this->cloud_id);
        }

        if (empty($this->file_path)) {
            return null;
        }

        $file = trim($this->file_path);

        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
            if (str_contains($file, 'drive.google.com')) {
                if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $file, $m) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $file, $m)) {
                    return url('/api/drive-stream/' . $m[1]);
                }
            }
            return $file;
        }

        return asset('storage/' . $file);
    }
    
    /**
     * Relación muchos a muchos con las listas de reproducción en las que se incluye esta pista.
     */
    public function playlists()
    {
        return $this->belongsToMany(Playlist::class)->withPivot('order')->withTimestamps();
    }
}
