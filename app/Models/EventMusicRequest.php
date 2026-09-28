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

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
