<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventMusicRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'category',
        'moment',
        'title',
        'artist',
        'requested_by',
        'notes',
        'youtube_url',
        'spotify_url',
        'apple_music_url',
        'audio_file',
        'cue_time',
        'status',
        'order',
        'likes',
        'is_guest_request',
        'guest_name',
        'guest_note',
    ];

    protected $casts = [
        'is_guest_request' => 'boolean',
        'likes' => 'integer',
        'order' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function (EventMusicRequest $request) {
            $title = trim($request->title ?? '');
            $artist = trim($request->artist ?? '');

            if (!empty($title)) {
                $needsSpotify = empty($request->spotify_url) || str_contains($request->spotify_url, 'open.spotify.com/search/');
                $needsApple = empty($request->apple_music_url) || str_contains($request->apple_music_url, 'music.apple.com/es/search');

                if ($needsSpotify || $needsApple) {
                    $meta = \App\Services\MusicSearchService::resolveTrackMetadata($title, $artist);
                    
                    if ($needsSpotify && !empty($meta['spotify_url'])) {
                        $request->spotify_url = $meta['spotify_url'];
                    }
                    if ($needsApple && !empty($meta['apple_music_url'])) {
                        $request->apple_music_url = $meta['apple_music_url'];
                    }
                    if (empty($request->audio_file) && !empty($meta['preview_url'])) {
                        $request->audio_file = $meta['preview_url'];
                    }
                }
            }
        });
    }

    public function getAudioUrlAttribute(): ?string
    {
        if (empty($this->audio_file)) {
            return null;
        }

        $file = trim($this->audio_file);

        if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
            if (str_contains($file, 'drive.google.com')) {
                if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $file, $m) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $file, $m)) {
                    return url('/api/drive-stream/' . $m[1]);
                }
            }
            if (str_contains($file, 'dropbox.com')) {
                if (str_contains($file, '?dl=0')) {
                    return str_replace('?dl=0', '?raw=1', $file);
                }
                if (!str_contains($file, 'raw=1')) {
                    return $file . (str_contains($file, '?') ? '&raw=1' : '?raw=1');
                }
            }
            return $file;
        }

        return asset('storage/' . $file);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
