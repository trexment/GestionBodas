<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'dni',
        'address',
        'postal_code',
        'city',
        'province',
        'calendar_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function eventsAsDj()
    {
        return $this->hasMany(Event::class, 'dj_id');
    }

    public function eventsAsAssistant()
    {
        return $this->hasMany(Event::class, 'assistant_id');
    }

    public function eventsAsClient()
    {
        return $this->hasMany(Event::class, 'client_id');
    }

    public function getCalendarToken(): string
    {
        if (empty($this->calendar_token)) {
            $this->calendar_token = Str::random(32);
            $this->saveQuietly();
        }
        return $this->calendar_token;
    }
}
