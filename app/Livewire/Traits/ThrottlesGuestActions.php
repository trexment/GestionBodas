<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Límite de peticiones para acciones de portales públicos.
 * Las acciones Livewire se envían a /livewire/update, así que el throttle de la ruta GET no las cubre.
 */
trait ThrottlesGuestActions
{
    /**
     * Registra un intento y devuelve los segundos de espera si se ha superado el límite (null si se permite).
     */
    protected function throttleGuestAction(string $action, int $maxAttempts = 5, int $decaySeconds = 600): ?int
    {
        $key = 'guest_action_' . $action . '_' . static::class . '_' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return RateLimiter::availableIn($key);
        }

        RateLimiter::hit($key, $decaySeconds);
        return null;
    }
}
