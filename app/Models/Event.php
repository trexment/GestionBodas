<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'event_date',
        'location',
        'venue_contact_name',
        'venue_contact_phone',
        'venue_notes',
        'status',
        'token',
        'is_dossier_completed',
        'client_id',
        'dj_id',
        'assistant_id',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($event) {
            $event->token = Str::random(32);
        });
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function dj()
    {
        return $this->belongsTo(User::class, 'dj_id');
    }

    public function assistant()
    {
        return $this->belongsTo(User::class, 'assistant_id');
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function dossiers()
    {
        return $this->hasMany(Dossier::class);
    }

    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }

    public function equipment()
    {
        return $this->belongsToMany(Equipment::class, 'event_equipment')
                    ->withPivot('id', 'quantity', 'notes')
                    ->withTimestamps();
    }

    public function musicRequests()
    {
        return $this->hasMany(EventMusicRequest::class)->orderBy('order')->orderBy('id');
    }
}
