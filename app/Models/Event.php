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
        'event_type',
        'brand',
        'event_date',
        'setup_date',
        'start_time',
        'dance_start_time',
        'dance_duration_hours',
        'dance_end_time',
        'ceremony_time',
        'cocktail_time',
        'banquet_time',
        'schedule_notes',
        'location',
        'venue_contact_name',
        'venue_contact_phone',
        'venue_notes',
        'status',
        'deposit_paid',
        'deposit_paid_amount',
        'deposit_payment_method',
        'deposit_paid_at',
        'deposit_notes',
        'token',
        'is_dossier_completed',
        'client_id',
        'dj_id',
        'assistant_id',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
        'setup_date' => 'date',
        'dance_duration_hours' => 'decimal:1',
        'deposit_paid' => 'boolean',
        'deposit_paid_amount' => 'decimal:2',
        'deposit_paid_at' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($event) {
            if (empty($event->token)) {
                $event->token = Str::random(32);
            }
        });
    }

    public function getTokenAttribute($value)
    {
        if (empty($value)) {
            $newToken = Str::random(32);
            if ($this->exists) {
                $this->attributes['token'] = $newToken;
                $this->saveQuietly();
            }
            return $newToken;
        }
        return $value;
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

    /**
     * Etiqueta legible del método de pago de la señal
     */
    public function getDepositMethodLabelAttribute(): string
    {
        $pm = $this->attributes['deposit_payment_method'] ?? null;
        return match($pm) {
            'bizum' => 'Bizum',
            'transfer' => 'Transferencia Bancaria',
            'cash' => 'Efectivo',
            'card' => 'Tarjeta / TPV',
            'other' => 'Otro',
            default => $pm ? ucfirst($pm) : 'No especificado',
        };
    }

    /**
     * Icono según método de pago
     */
    public function getDepositMethodIconAttribute(): string
    {
        $pm = $this->attributes['deposit_payment_method'] ?? null;
        return match($pm) {
            'bizum' => '📱',
            'transfer' => '🏦',
            'cash' => '💵',
            'card' => '💳',
            default => '💶',
        };
    }

    /**
     * Tipo de evento limpio / normalizado
     */
    public function getEventTypeCleanAttribute(): string
    {
        $type = $this->attributes['event_type'] ?? null;
        if (!empty($type)) {
            return $type;
        }
        $nameLower = mb_strtolower($this->name ?? '');
        if (str_contains($nameLower, 'empresa') || str_contains($nameLower, 'corporativ')) return 'empresa';
        if (str_contains($nameLower, 'cumplea') || str_contains($nameLower, 'cumple') || str_contains($nameLower, 'aniversario')) return 'cumpleanos';
        if (str_contains($nameLower, 'comunion') || str_contains($nameLower, 'comunión')) return 'comunion';
        if (str_contains($nameLower, 'fiesta') || str_contains($nameLower, 'privad') || str_contains($nameLower, 'despedida')) return 'otro';
        return 'boda';
    }

    /**
     * Determina si es una boda
     */
    public function getIsWeddingAttribute(): bool
    {
        return $this->event_type_clean === 'boda';
    }

    /**
     * Etiqueta legible del tipo de evento
     */
    public function getEventTypeLabelAttribute(): string
    {
        return match($this->event_type_clean) {
            'empresa' => 'Evento de Empresa / Corporativo',
            'cumpleanos' => 'Cumpleaños / Aniversario',
            'comunion' => 'Comunión',
            'otro' => 'Fiesta Privada / Evento',
            default => 'Boda / Enlace',
        };
    }

    /**
     * Icono del tipo de evento
     */
    public function getEventTypeIconAttribute(): string
    {
        return match($this->event_type_clean) {
            'empresa' => '🏢',
            'cumpleanos' => '🎂',
            'comunion' => '🕊️',
            'otro' => '🎉',
            default => '💍',
        };
    }

    /**
     * Marca comercial activa del evento
     */
    public function getBrandCleanAttribute(): string
    {
        $b = $this->attributes['brand'] ?? null;
        if (!empty($b)) {
            return $b;
        }
        if (!app()->runningInConsole() && request() && str_contains(strtolower(request()->getHost()), 'javnx')) {
            return 'javnx';
        }
        return 'nunez_and_son';
    }

    public function getBrandLabelAttribute(): string
    {
        return $this->brand_clean === 'javnx' ? 'JAVNX DJ' : 'Núñez and Son';
    }

    public function getBrandIconAttribute(): string
    {
        return $this->brand_clean === 'javnx' ? '🎧' : '👑';
    }

    public function getBrandInfoAttribute(): array
    {
        return Setting::getBrandInfo($this->brand_clean);
    }

    /**
     * Calcula la hora de finalización del baile sumando las horas de duración
     */
    public function getCalculatedDanceEndTimeAttribute(): ?string
    {
        $explicitEndTime = $this->attributes['dance_end_time'] ?? null;
        if (!empty($explicitEndTime)) {
            return (string)$explicitEndTime;
        }

        $danceStart = $this->attributes['dance_start_time'] ?? null;
        $danceDuration = $this->attributes['dance_duration_hours'] ?? null;

        if (!empty($danceStart) && !empty($danceDuration)) {
            try {
                $timeClean = substr(trim((string)$danceStart), 0, 5);
                if (preg_match('/^\d{1,2}:\d{2}$/', $timeClean)) {
                    $start = \Carbon\Carbon::createFromFormat('H:i', $timeClean);
                    $minutes = (int)((float)$danceDuration * 60);
                    return $start->addMinutes($minutes)->format('H:i');
                }
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Extrae las horas de baile sugeridas a partir del presupuesto / contrato
     */
    public function getSuggestedDanceHoursAttribute(): ?float
    {
        $duration = $this->attributes['dance_duration_hours'] ?? null;
        if (!empty($duration)) {
            return (float)$duration;
        }

        // Buscar en los items del presupuesto
        try {
            $quote = $this->quotes()->latest()->first();
            if ($quote && $quote->items) {
                foreach ($quote->items as $item) {
                    $name = mb_strtolower($item->service_name ?? '');
                    $desc = mb_strtolower($item->description ?? '');
                    
                    // Pack Básico (4h), Pack Medio (5h), Pack Premium (6h)
                    if (str_contains($name, 'pack') || str_contains($desc, 'pack')) {
                        if (str_contains($name, 'básico') || str_contains($name, 'basico')) return 4.0;
                        if (str_contains($name, 'medio')) return 5.0;
                        if (str_contains($name, 'premium')) return 6.0;
                    }

                    // Horas de DJ
                    if (str_contains($name, 'hora') && str_contains($name, 'dj')) {
                        if ($item->quantity > 0) return (float)$item->quantity;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently fallback
        }

        return 4.0; // Valor por defecto habitual
    }

    /**
     * Etiqueta legible del horario del baile
     */
    public function getDanceScheduleLabelAttribute(): string
    {
        $danceStart = $this->attributes['dance_start_time'] ?? null;
        if (empty($danceStart)) {
            return 'Horario no definido';
        }

        $start = substr((string)$danceStart, 0, 5) . ' h';
        $end = $this->calculated_dance_end_time ? (substr((string)$this->calculated_dance_end_time, 0, 5) . ' h') : null;
        $hours = !empty($this->attributes['dance_duration_hours']) 
            ? (rtrim(rtrim(number_format((float)$this->attributes['dance_duration_hours'], 1, ',', '.'), '0'), ',') . 'h') 
            : null;

        if ($end && $hours) {
            return "De {$start} a {$end} ({$hours})";
        } elseif ($end) {
            return "De {$start} a {$end}";
        } elseif ($hours) {
            return "Desde las {$start} ({$hours})";
        }

        return "Desde las {$start}";
    }

    /**
     * Etiqueta legible de la fecha y hora de montaje
     */
    public function getSetupScheduleLabelAttribute(): string
    {
        $setupDate = $this->setup_date;
        $startTime = $this->start_time;

        if (empty($setupDate) && empty($startTime)) {
            return 'No especificado';
        }

        $timeStr = !empty($startTime) ? (substr((string)$startTime, 0, 5) . ' h') : null;
        
        if (!empty($setupDate)) {
            $formattedDate = \Carbon\Carbon::parse($setupDate)->translatedFormat('l d/m/Y');
            $shortDate = \Carbon\Carbon::parse($setupDate)->format('d/m/Y');
            
            if ($this->event_date && $setupDate->isSameDay($this->event_date)) {
                return $timeStr ? "Mismo día ({$shortDate}) a las {$timeStr}" : "Mismo día ({$shortDate})";
            } elseif ($this->event_date && $setupDate->isDayBefore($this->event_date)) {
                return $timeStr ? "Víspera ({$shortDate}) a las {$timeStr}" : "Víspera ({$shortDate})";
            } else {
                return $timeStr ? "{$shortDate} a las {$timeStr}" : "Fecha: {$shortDate}";
            }
        }

        return $timeStr ? "A las {$timeStr}" : 'No especificado';
    }

    /**
     * Devuelve la lista de momentos musicales sugeridos según el tipo de evento
     */
    public function getSuggestedMomentsAttribute(): array
    {
        return self::getMomentsForEventType($this->event_type_clean);
    }

    /**
     * Momentos estándar organizados por tipo de evento
     */
    public static function getMomentsForEventType(?string $eventType = 'boda'): array
    {
        $type = mb_strtolower(trim((string)$eventType));

        return match ($type) {
            'boda' => [
                'Baile / Fiesta',
                'Baile Nupcial',
                'Apertura Baile',
                'Hora Loca',
                'Entrada Novios',
                'Entrada Comedor',
                'Corte de Tarta',
                'Entrega de Ramo',
                'Regalos Padres',
                'Regalo Amigos',
                'Fin de Fiesta / Cierre',
                'Ceremonia - Entrada Novio',
                'Ceremonia - Entrada Novia',
                'Ceremonia - Anillos / Arras',
                'Ceremonia - Salida',
                'Música Ambiente Cóctel',
                'Música Fondo Banquete',
                'Prohibida / Lista Negra',
            ],
            'empresa' => [
                'Baile / Fiesta',
                'Recepción / Bienvenida',
                'Música Ambiente / Networking',
                'Entrada Ponentes / Directiva',
                'Entrega de Premios / Reconocimientos',
                'Fondo Almuerzo / Cena',
                'Apertura de Pista / Barra Libre',
                'Momento Sorpresa / Brindis',
                'Fin de Evento / Cierre',
                'Prohibida / Lista Negra',
            ],
            'cumpleanos' => [
                'Baile / Fiesta',
                'Llegada / Sorpresa',
                'Música Ambiente / Cóctel',
                'Momento Tarta / Cumpleaños Feliz',
                'Entrega de Regalo Especial',
                'Apertura de Pista',
                'Hora Loca',
                'Cierre / Fin de Fiesta',
                'Prohibida / Lista Negra',
            ],
            'comunion' => [
                'Baile / Fiesta',
                'Entrada del Comulgante',
                'Música Ambiente Comida',
                'Momento Tarta',
                'Entrega de Regalos / Recuerdos',
                'Animación / Juegos',
                'Fin de Fiesta',
                'Prohibida / Lista Negra',
            ],
            default => [
                'Baile / Fiesta',
                'Recepción / Bienvenida',
                'Música Ambiente',
                'Momento Especial',
                'Apertura de Pista',
                'Hora Loca',
                'Fin de Fiesta / Cierre',
                'Prohibida / Lista Negra',
            ],
        };
    }

    /**
     * Categorías / Fases contextuales según tipo de evento
     */
    public function getSuggestedCategoriesAttribute(): array
    {
        return self::getCategoriesForEventType($this->event_type_clean);
    }

    public static function getCategoriesForEventType(?string $eventType = 'boda'): array
    {
        $type = mb_strtolower(trim((string)$eventType));

        if ($type === 'boda') {
            return [
                'ceremonia' => '💍 Ceremonia',
                'coctel' => '🍸 Cóctel',
                'banquete' => '🍽️ Banquete / Regalos',
                'baile' => '💃 Baile / Fiesta',
                'lista_negra' => '🚫 Lista Negra',
            ];
        } elseif ($type === 'empresa') {
            return [
                'ceremonia' => '🤝 Recepción / Bienvenida',
                'coctel' => '🍸 Networking / Cóctel',
                'banquete' => '🍽️ Cena / Ponencias',
                'baile' => '💃 Baile / Fiesta Empresa',
                'lista_negra' => '🚫 Lista Negra',
            ];
        } elseif ($type === 'cumpleanos') {
            return [
                'ceremonia' => '🎉 Bienvenida / Llegada',
                'coctel' => '🍸 Cóctel / Picoteo',
                'banquete' => '🎂 Comida / Tarta',
                'baile' => '💃 Baile / Fiesta',
                'lista_negra' => '🚫 Lista Negra',
            ];
        } elseif ($type === 'comunion') {
            return [
                'ceremonia' => '🕊️ Llegada Comulgante',
                'coctel' => '🍸 Aperitivo',
                'banquete' => '🍽️ Banquete / Detalles',
                'baile' => '💃 Juegos / Baile',
                'lista_negra' => '🚫 Lista Negra',
            ];
        }

        return [
            'ceremonia' => '🚪 Recepción / Entrada',
            'coctel' => '🍸 Aperitivo / Ambiente',
            'banquete' => '🍽️ Comida / Actos',
            'baile' => '💃 Baile / Fiesta',
            'lista_negra' => '🚫 Lista Negra',
        ];
    }
}

