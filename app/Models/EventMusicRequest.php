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
                if (empty($request->spotify_url) || empty($request->apple_music_url) || empty($request->youtube_url)) {
                    $meta = \App\Services\MusicSearchService::resolveTrackMetadata($title, $artist);
                    
                    if (empty($request->spotify_url) && !empty($meta['spotify_url'])) {
                        $request->spotify_url = $meta['spotify_url'];
                    }
                    if (empty($request->apple_music_url) && !empty($meta['apple_music_url'])) {
                        $request->apple_music_url = $meta['apple_music_url'];
                    }
                    if (empty($request->youtube_url) && !empty($meta['youtube_url'])) {
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
