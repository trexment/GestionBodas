<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'artist', 'genre', 'bpm', 'file_path'];
    
    public function playlists()
    {
        return $this->belongsToMany(Playlist::class)->withPivot('order')->withTimestamps();
    }
}
