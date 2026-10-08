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
        'max_end_time',
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
        'partner_name',
        'partner_phone',
        'partner_email',
        'partner_dni',
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

    public function assistants()
    {
        return $this->belongsToMany(User::class, 'event_assistants', 'event_id', 'user_id')->withTimestamps();
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

    public function meetings()
    {
        return $this->hasMany(ClientMeeting::class)->orderBy('meeting_date', 'asc');
    }

    public function djHistories()
    {
        return $this->hasMany(EventDjHistory::class)->orderBy('order', 'asc');
    }

    /**
     * Etiqueta legible del estado del evento
     */
    public function getStatusLabelAttribute(): string
    {
        $st = $this->attributes['status'] ?? 'draft';
        return match($st) {
            'draft' => 'Borrador / Pendiente',
            'no_response' => 'Sin Respuesta',
            'confirmed' => 'Confirmado',
            'completed' => 'Completado',
            'rejected' => 'Rechazado',
            'cancelled' => 'Cancelado',
            default => ucfirst($st),
        };
    }

    /**
     * Clases CSS para el badge de estado
     */
    public function getStatusBadgeClassAttribute(): string
    {
        $st = $this->attributes['status'] ?? 'draft';
        return match($st) {
            'draft' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300',
            'no_response' => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950/60 dark:text-purple-300',
            'confirmed' => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300',
            'completed' => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/60 dark:text-blue-300',
            'rejected' => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300',
            'cancelled' => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300',
            default => 'bg-gray-100 text-gray-800 border-gray-300',
        };
    }

    /**
     * Icono representativo del estado
     */
    public function getStatusIconAttribute(): string
    {
        $st = $this->attributes['status'] ?? 'draft';
        return match($st) {
            'draft' => '📝',
            'no_response' => '⏳',
            'confirmed' => '🟢',
            'completed' => '🔵',
            'rejected' => '❌',
            'cancelled' => '🔴',
            default => '📌',
        };
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
        if (!app()->runningInConsole() && request() && str_contains(strtolower(request()->getHost()), 'leugim')) {
            return 'mago_leugim';
        }
        return 'nunez_and_son';
    }

    public function getBrandLabelAttribute(): string
    {
        if ($this->brand_clean === 'javnx') return 'JAVNX DJ';
        if ($this->brand_clean === 'mago_leugim') return 'Mago Leugim';
        return 'Núñez and Son';
    }

    public function getBrandIconAttribute(): string
    {
        if ($this->brand_clean === 'javnx') return '🎧';
        if ($this->brand_clean === 'mago_leugim') return '🎩';
        return '👑';
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
        $maxEnd = !empty($this->attributes['max_end_time']) ? (substr((string)$this->attributes['max_end_time'], 0, 5) . ' h') : null;
        $hours = !empty($this->attributes['dance_duration_hours']) 
            ? (rtrim(rtrim(number_format((float)$this->attributes['dance_duration_hours'], 1, ',', '.'), '0'), ',') . 'h') 
            : null;

        if ($end && $maxEnd && $end !== $maxEnd) {
            return "De {$start} a {$end} ({$hours} aprox., máx. {$maxEnd})";
        } elseif ($end && $hours) {
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
     * Hora de inicio del servicio/trabajo para el evento.
     * Si el montaje es el mismo día y tiene hora especificada, toma la hora de montaje.
     * Si el montaje es otro día (o no se especifica hora de montaje), toma la primera fase cronológica (Ceremonia, Cóctel, Banquete o Baile).
     */
    public function getEffectiveStartTimeAttribute(): string
    {
        // 1. Si hay fecha de montaje y fecha de evento, comprobar si es el mismo día
        $isSameDaySetup = true;
        if (!empty($this->setup_date) && !empty($this->event_date)) {
            $isSameDaySetup = \Carbon\Carbon::parse($this->setup_date)->isSameDay(\Carbon\Carbon::parse($this->event_date));
        }

        // Si el montaje es el mismo día y se especificó hora de montaje, el trabajo en el recinto arranca con el montaje
        if ($isSameDaySetup && !empty($this->start_time)) {
            return substr(trim((string)$this->start_time), 0, 5);
        }

        // 2. Si el montaje es en otra fecha (víspera) o no hay hora de montaje: tomar la primera fase del evento
        if (!empty($this->ceremony_time)) {
            return substr(trim((string)$this->ceremony_time), 0, 5);
        }
        if (!empty($this->cocktail_time)) {
            return substr(trim((string)$this->cocktail_time), 0, 5);
        }
        if (!empty($this->banquet_time)) {
            return substr(trim((string)$this->banquet_time), 0, 5);
        }
        if (!empty($this->dance_start_time)) {
            return substr(trim((string)$this->dance_start_time), 0, 5);
        }

        // Fallback si hay start_time general
        if (!empty($this->start_time)) {
            return substr(trim((string)$this->start_time), 0, 5);
        }

        return 'Por determinar';
    }

    /**
     * Hora de fin estimada del servicio musical contratado.
     * Toma el fin del baile, o lo calcula a partir del inicio y duración del baile o presupuesto.
     */
    public function getEffectiveEndTimeAttribute(): string
    {
        $baseEnd = null;

        // 1. Hora de fin explícita del baile
        if (!empty($this->dance_end_time)) {
            $baseEnd = substr(trim((string)$this->dance_end_time), 0, 5);
        } elseif (!empty($this->calculated_dance_end_time)) {
            $baseEnd = substr(trim((string)$this->calculated_dance_end_time), 0, 5);
        } elseif (!empty($this->dance_start_time)) {
            try {
                $start = \Carbon\Carbon::createFromFormat('H:i', substr(trim((string)$this->dance_start_time), 0, 5));
                $hours = $this->suggested_dance_hours ?: 4.0;
                $baseEnd = $start->addMinutes((int)($hours * 60))->format('H:i');
            } catch (\Throwable $e) {}
        }

        if (!$baseEnd) {
            $startStr = $this->effective_start_time;
            if (!empty($startStr) && $startStr !== 'Por determinar' && preg_match('/^\d{1,2}:\d{2}$/', $startStr)) {
                try {
                    $start = \Carbon\Carbon::createFromFormat('H:i', $startStr);
                    $hours = $this->suggested_dance_hours ?: 4.0;
                    $baseEnd = $start->addMinutes((int)($hours * 60))->format('H:i');
                } catch (\Throwable $e) {}
            }
        }

        $maxEnd = !empty($this->attributes['max_end_time']) ? substr(trim((string)$this->attributes['max_end_time']), 0, 5) : null;

        if ($baseEnd && $maxEnd && $baseEnd !== $maxEnd) {
            return "{$baseEnd} (límite máx. {$maxEnd})";
        } elseif ($baseEnd) {
            return $baseEnd;
        } elseif ($maxEnd) {
            return "Hasta las {$maxEnd} (límite máx.)";
        }

        return 'Fin de fiesta / Según desarrollo';
    }

    /**
     * Resumen completo de todas las fases del evento con sus horarios
     */
    public function getScheduleBreakdownAttribute(): string
    {
        $parts = [];
        if (!empty($this->ceremony_time)) {
            $parts[] = "Ceremonia: " . substr(trim((string)$this->ceremony_time), 0, 5) . "h";
        }
        if (!empty($this->cocktail_time)) {
            $parts[] = "Cóctel: " . substr(trim((string)$this->cocktail_time), 0, 5) . "h";
        }
        if (!empty($this->banquet_time)) {
            $parts[] = "Banquete: " . substr(trim((string)$this->banquet_time), 0, 5) . "h";
        }
        if (!empty($this->dance_start_time)) {
            $danceEnd = $this->effective_end_time;
            $parts[] = "Baile / Barra Libre: " . substr(trim((string)$this->dance_start_time), 0, 5) . "h a " . $danceEnd . "h";
        }
        return !empty($parts) ? implode(' • ', $parts) : ($this->effective_start_time . ' a ' . $this->effective_end_time);
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

    /**
     * Obtiene la lista completa de asistentes (soporta relación N:M y fallback a assistant_id)
     */
    public function getAllAssistantsAttribute()
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                $list = $this->assistants;
                if ($list && $list->isNotEmpty()) {
                    return $list;
                }
            }
        } catch (\Throwable $e) {}

        if ($this->assistant) {
            return collect([$this->assistant]);
        }
        return collect([]);
    }

    /**
     * Número total de integrantes del equipo técnico (DJ + Asistentes)
     */
    public function getStaffCountAttribute(): int
    {
        $djCount = $this->dj_id || $this->dj ? 1 : 1; // Mínimo 1 DJ por defecto
        $astCount = $this->all_assistants->count();
        return max(1, $djCount + $astCount);
    }

    /**
     * Desglose detallado del equipo técnico asignado
     */
    public function getStaffBreakdownTextAttribute(): string
    {
        $djName = $this->dj ? $this->dj->name : 'DJ Titular';
        $assistants = $this->all_assistants;
        $astCount = $assistants->count();

        if ($astCount === 0) {
            return "1 persona (1 DJ: {$djName})";
        }

        $astNames = $assistants->pluck('name')->filter()->implode(', ');
        $astLabel = $astCount === 1 ? '1 Asistente' : "{$astCount} Asistentes";
        $total = 1 + $astCount;

        return "{$total} personas (1 DJ: {$djName} y {$astLabel}" . ($astNames ? ": {$astNames}" : "") . ")";
    }

    /**
     * Listado resumido de nombres de los técnicos
     */
    public function getStaffNamesTextAttribute(): string
    {
        $names = [];
        if ($this->dj) {
            $names[] = $this->dj->name . ' (DJ)';
        }
        foreach ($this->all_assistants as $ast) {
            $names[] = $ast->name . ' (Asistente)';
        }
        return !empty($names) ? implode(', ', $names) : 'Personal asignado';
    }

    /**
     * Determina si el evento requiere manutención / menú de staff (cóctel, banquete o larga duración)
     */
    public function getRequiresStaffMealAttribute(): bool
    {
        if (!empty($this->cocktail_time) || !empty($this->banquet_time) || !empty($this->ceremony_time)) {
            return true;
        }

        // Si la hora de inicio es anterior a las 20:30 o cubre tarde/noche completa
        $start = $this->effective_start_time;
        if (!empty($start) && $start !== 'Por determinar' && preg_match('/^\d{1,2}:\d{2}$/', $start)) {
            $hour = (int)explode(':', $start)[0];
            if ($hour < 21) {
                return true;
            }
        }

        try {
            $quote = $this->quotes()->latest()->first();
            if ($quote && $quote->items) {
                foreach ($quote->items as $item) {
                    $text = mb_strtolower(($item->service_name ?? '') . ' ' . ($item->description ?? ''));
                    if (str_contains($text, 'coctel') || str_contains($text, 'cóctel') || 
                        str_contains($text, 'banquet') || str_contains($text, 'comida') || 
                        str_contains($text, 'cena') || str_contains($text, 'ceremon') ||
                        str_contains($text, 'completo') || str_contains($text, 'dia')) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {}

        return false;
    }

    /**
     * Redacción de la cláusula de manutención / menú de personal para el contrato
     */
    public function getStaffMealClauseTextAttribute(): string
    {
        $count = $this->staff_count;
        $countStr = $count === 1 ? '1 persona' : "{$count} personas";
        $breakdown = $this->staff_breakdown_text;

        if ($this->requires_staff_meal) {
            return "Al prestarse servicios durante las fases de cóctel y/o banquete (o servicios continuados de larga duración), EL CLIENTE se compromete a facilitar la manutención / menú de personal (plato de comida caliente y bebida) para los integrantes del equipo técnico desplazados ({$breakdown}).";
        }

        return "En caso de que los servicios contratados se amplíen o cubran las fases de cóctel y/o banquete (o superen las 4 horas de servicio in situ), EL CLIENTE facilitará la correspondiente manutención / menú de personal para los integrantes del equipo ({$countStr}).";
    }

    /**
     * Limpia el teléfono de la pareja para enlace de WhatsApp / llamada
     */
    public function getPartnerCleanPhoneAttribute(): ?string
    {
        if (empty($this->partner_phone)) {
            return null;
        }
        $phone = preg_replace('/[^0-9]/', '', $this->partner_phone);
        if (strlen($phone) === 9 && in_array(substr($phone, 0, 1), ['6', '7'])) {
            $phone = '34' . $phone;
        }
        return $phone;
    }

    /**
     * Devuelve los nombres combinados de la pareja / novios
     */
    public function getCoupleNamesAttribute(): string
    {
        $clientName = $this->client?->name;
        if (!empty($this->partner_name)) {
            return $clientName ? "{$clientName} y {$this->partner_name}" : $this->partner_name;
        }
        return $clientName ?: ($this->name ?: 'Cliente');
    }
}

