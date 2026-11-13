<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Modelo que representa a un usuario del sistema (administrador, DJ, asistente o cliente).
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
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

    /**
     * Define las conversiones de tipos para los atributos del usuario.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relación con los eventos en los que el usuario actúa como DJ titular.
     */
    public function eventsAsDj()
    {
        return $this->hasMany(Event::class, 'dj_id');
    }

    /**
     * Relación con los eventos en los que el usuario actúa como asistente técnico.
     */
    public function eventsAsAssistant()
    {
        return $this->hasMany(Event::class, 'assistant_id');
    }

    /**
     * Relación con los eventos en los que el usuario figura como cliente titular.
     */
    public function eventsAsClient()
    {
        return $this->hasMany(Event::class, 'client_id');
    }

    /**
     * Alias de relación con los eventos asociados al usuario como cliente.
     */
    public function clientEvents()
    {
        return $this->hasMany(Event::class, 'client_id');
    }

    /**
     * Obtiene el token único de suscripción al calendario iCal del usuario, generándolo si no existe.
     */
    public function getCalendarToken(): string
    {
        if (empty($this->calendar_token)) {
            $this->calendar_token = Str::random(32);
            $this->saveQuietly();
        }
        return $this->calendar_token;
    }
}
