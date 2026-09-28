<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;
    protected $fillable = ['event_id', 'name'];
    
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function tracks()
    {
        return $this->belongsToMany(Track::class)->withPivot('order')->orderBy('pivot_order')->withTimestamps();
    }
}
