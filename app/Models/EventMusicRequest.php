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
                $needsYoutube = empty($request->youtube_url) || str_contains($request->youtube_url, 'youtube.com/results');

                if ($needsSpotify || $needsApple || $needsYoutube) {
                    $meta = \App\Services\MusicSearchService::resolveTrackMetadata($title, $artist);
                    
                    if ($needsSpotify && !empty($meta['spotify_url'])) {
                        $request->spotify_url = $meta['spotify_url'];
                    }
                    if ($needsApple && !empty($meta['apple_music_url'])) {
                        $request->apple_music_url = $meta['apple_music_url'];
                    }
                    if ($needsYoutube && !empty($meta['youtube_url'])) {
                        $request->youtube_url = $meta['youtube_url'];
                    }
                    if (empty($request->audio_file) && !empty($meta['preview_url'])) {
                        $request->audio_file = $meta['preview_url'];
                    }
                }
            }
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
