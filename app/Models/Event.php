<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Modelo principal que representa un evento musical o boda y concentra su operativa, horarios, finanzas y equipo.
 */
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
        'no_response_at',
        'cancellation_reason',
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
        'photo_qr_slug',
        'photo_qr_url',
        'photo_qr_drive_url',
        'photo_qr_poster_url',
        'photo_qr_created_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'setup_date' => 'date',
        'no_response_at' => 'datetime',
        'dance_duration_hours' => 'decimal:1',
        'deposit_paid' => 'boolean',
        'deposit_paid_amount' => 'decimal:2',
        'deposit_paid_at' => 'date',
        'photo_qr_created_at' => 'datetime',
    ];

    protected static bool $tokensSchemaChecked = false;
    protected static bool $hasGuestTokenColumn = false;
    protected static bool $hasStaffTokenColumn = false;

    /**
     * Comprueba y asegura que las columnas guest_token y staff_token existan en la tabla events.
     */
    protected static function ensureTokenColumnsExist(): void
    {
        if (static::$tokensSchemaChecked) {
            return;
        }
        static::$tokensSchemaChecked = true;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('events')) {
                static::$hasGuestTokenColumn = \Illuminate\Support\Facades\Schema::hasColumn('events', 'guest_token');
                static::$hasStaffTokenColumn = \Illuminate\Support\Facades\Schema::hasColumn('events', 'staff_token');

                if (!static::$hasGuestTokenColumn || !static::$hasStaffTokenColumn) {
                    \Illuminate\Support\Facades\Schema::table('events', function (\Illuminate\Database\Schema\Blueprint $table) {
                        if (!static::$hasGuestTokenColumn) {
                            $table->string('guest_token', 64)->nullable()->after('token');
                        }
                        if (!static::$hasStaffTokenColumn) {
                            $table->string('staff_token', 64)->nullable()->after('token');
                        }
                    });
                    static::$hasGuestTokenColumn = true;
                    static::$hasStaffTokenColumn = true;
                }
            }
        } catch (\Throwable $e) {
            // Ignorar si no se puede alterar esquema en caliente
        }
    }

    /**
     * Registra el evento de creación del modelo para inicializar los tokens de cliente, invitados y personal.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($event) {
            static::ensureTokenColumnsExist();

            if (empty($event->token)) {
                $event->token = Str::random(32);
            }
            // Tokens separados: invitados (QR peticiones) y personal (Modo Cabina)
            if (static::$hasGuestTokenColumn && empty($event->attributes['guest_token'] ?? null)) {
                $event->attributes['guest_token'] = Str::random(32);
            }
            if (static::$hasStaffTokenColumn && empty($event->attributes['staff_token'] ?? null)) {
                $event->attributes['staff_token'] = Str::random(40);
            }
        });
    }

    /**
     * Obtiene el token de acceso principal del evento, generándolo si estuviera vacío.
     */
    public function getTokenAttribute($value)
    {
        return $this->ensureTokenValue('token', $value, 32);
    }

    /**
     * Slug legible y seguro para las URLs públicas del cliente
     * (ej: "boda-javi-y-cristina-ucnyeo" en lugar de un token de 32 caracteres).
     */
    public function getPrettyTokenAttribute(): string
    {
        $rawToken = (string) $this->token;
        $shortCode = strtolower(substr($rawToken, 0, 6));
        $cleanName = str_replace(['&', '+'], ' y ', (string) ($this->name ?: 'evento'));
        $slug = Str::slug($cleanName);
        if ($slug === '') {
            $slug = 'evento';
        }
        $slug = rtrim(substr($slug, 0, 48), '-');

        return "{$slug}-{$shortCode}";
    }

    /**
     * Localiza un evento tanto por su token tradicional de 32 caracteres como por su slug bonito
     * ("boda-javi-y-cristina-ucnyeo" o "boda-javi-y-cristina-ucnyeo-29").
     */
    public static function findByTokenOrPrettySlug(string $tokenOrSlug, array $with = [], ?int &$extractedQuoteId = null): ?self
    {
        $tokenOrSlug = trim($tokenOrSlug);
        if ($tokenOrSlug === '') {
            return null;
        }

        $query = !empty($with) ? static::with($with) : static::query();
        $exact = (clone $query)->where('token', $tokenOrSlug)->first();
        if ($exact) {
            return $exact;
        }

        $workSlug = $tokenOrSlug;
        if (preg_match('/^(.+-[a-z0-9]{6})-(\d+)$/i', $workSlug, $qm)) {
            $workSlug = $qm[1];
            $extractedQuoteId = (int) $qm[2];
        }

        if (preg_match('/-([a-z0-9]{6})$/i', $workSlug, $m)) {
            $shortCode = strtolower($m[1]);
            $candidates = (clone $query)
                ->whereRaw('LOWER(LEFT(token, 6)) = ?', [$shortCode])
                ->get();

            if ($candidates->count() === 1) {
                return $candidates->first();
            }

            foreach ($candidates as $candidate) {
                if (strcasecmp($candidate->pretty_token, $workSlug) === 0) {
                    return $candidate;
                }
            }

            return $candidates->first();
        }

        return null;
    }

    /**
     * Token público de invitados: solo da acceso al portal de peticiones en vivo (QR).
     */
    public function getGuestTokenAttribute($value)
    {
        return $this->ensureTokenValue('guest_token', $value, 32);
    }

    /**
     * Token del personal: da acceso al Modo Cabina DJ (/live) sin iniciar sesión.
     */
    public function getStaffTokenAttribute($value)
    {
        return $this->ensureTokenValue('staff_token', $value, 40);
    }

    /**
     * Asegura que la columna de token indicada tenga un valor aleatorio válido y lo persiste silenciosamente si falta.
     */
    protected function ensureTokenValue(string $column, $value, int $length): string
    {
        if (empty($value)) {
            $newToken = Str::random($length);
            if ($this->exists) {
                $this->attributes[$column] = $newToken;
                $this->saveQuietly();
            }
            return $newToken;
        }
        return $value;
    }

    /**
     * Comprueba si el token recibido da acceso de cliente o de personal al evento.
     */
    public function matchesClientOrStaffToken(?string $provided): bool
    {
        if (empty($provided)) {
            return false;
        }
        return hash_equals((string) $this->token, $provided)
            || hash_equals((string) $this->staff_token, $provided);
    }

    /**
     * Relación con el usuario cliente titular del evento.
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Relación con el DJ titular asignado al evento.
     */
    public function dj()
    {
        return $this->belongsTo(User::class, 'dj_id');
    }

    /**
     * Relación con el asistente técnico principal asignado al evento.
     */
    public function assistant()
    {
        return $this->belongsTo(User::class, 'assistant_id');
    }

    /**
     * Relación muchos a muchos con todos los asistentes técnicos asignados al evento.
     */
    public function assistants()
    {
        return $this->belongsToMany(User::class, 'event_assistants', 'event_id', 'user_id')->withTimestamps();
    }

    /**
     * Relación con los presupuestos emitidos para el evento.
     */
    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Relación con los contratos generados para el evento.
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Relación con las facturas y recibos emitidos para el evento.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Relación con los mensajes de WhatsApp registrados en el historial del evento.
     */
    public function whatsappMessages()
    {
        return $this->hasMany(WhatsAppMessage::class)->orderBy('created_at', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Relación con los dossiers asociados al evento.
     */
    public function dossiers()
    {
        return $this->hasMany(Dossier::class);
    }

    /**
     * Relación con las listas de reproducción (playlists) del evento.
     */
    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }

    /**
     * Relación muchos a muchos con el equipamiento técnico asignado en la hoja de carga del evento.
     */
    public function equipment()
    {
        return $this->belongsToMany(Equipment::class, 'event_equipment')
                    ->withPivot('id', 'quantity', 'notes')
                    ->withTimestamps();
    }

    /**
     * Relación con las peticiones musicales y canciones de la escaleta del evento.
     */
    public function musicRequests()
    {
        return $this->hasMany(EventMusicRequest::class)->orderBy('order')->orderBy('id');
    }

    /**
     * Relación con las reuniones programadas o realizadas con los clientes del evento.
     */
    public function meetings()
    {
        return $this->hasMany(ClientMeeting::class)->orderBy('meeting_date', 'asc');
    }

    /**
     * Relación con el historial de pistas reproducidas en la sesión DJ del evento.
     */
    public function djHistories()
    {
        return $this->hasMany(EventDjHistory::class)->orderBy('order', 'asc');
    }

    /**
     * Relación con las reservas y pagos a proveedores externos (fotomatón, plataforma 360º, etc.) del evento.
     */
    public function providerBookings()
    {
        EventProviderBooking::ensureTableExists();
        return $this->hasMany(EventProviderBooking::class)->orderBy('id', 'asc');
    }

    /**
     * Detecta si el evento incluye Fotomatón, Plataforma 360º u otros servicios de proveedor externo
     * y devuelve el estado de la reserva y pagos al proveedor (Señal + Liquidación final).
     */
    public function getExternalProviderSummaryAttribute(): array
    {
        EventProviderBooking::ensureTableExists();

        $bookings = collect();
        try {
            $bookings = $this->relationLoaded('providerBookings')
                ? $this->providerBookings
                : $this->providerBookings()->get();
        } catch (\Throwable $e) {
            $bookings = collect();
        }

        $quotes = $this->relationLoaded('quotes') ? $this->quotes : $this->quotes()->with('items')->get();

        // Ordenar presupuestos priorizando: 1º aceptados, 2º enviados/pendientes, 3º rechazados (más recientes primero)
        $sortedQuotes = $quotes->sortByDesc(function ($q) {
            $statusWeight = match ($q->status) {
                'accepted' => 30,
                'sent', 'pending' => 20,
                'rejected' => 0,
                default => 10,
            };
            return ($statusWeight * 1000000) + (int) $q->id;
        });

        // Sugerir proveedor según ubicación (Vitoria/Álava vs Logroño/La Rioja)
        $locLower = mb_strtolower((string) ($this->location ?? ''));
        $suggestedProviderKey = (str_contains($locLower, 'vitoria') || str_contains($locLower, 'álava') || str_contains($locLower, 'alava') || str_contains($locLower, 'bilbao'))
            ? 'vitoria'
            : 'logrono';
        $providersCatalog = Setting::getPhotoboothProviders();
        $pbConfig = $providersCatalog[$suggestedProviderKey] ?? ($providersCatalog['logrono'] ?? []);
        $suggestedProviderName = $pbConfig['name'] ?? ($suggestedProviderKey === 'vitoria' ? 'Vitoria (FotomatonCheese)' : 'Logroño');

        $detectedNames = [];
        $detectedWithPrices = [];
        $detectedClientPrice = 0.0;
        $hasPhotobooth = false;
        $has360 = false;

        foreach ($sortedQuotes as $q) {
            $items = $q->relationLoaded('items') ? $q->items : $q->items()->get();
            $qNames = [];
            $qWithPrices = [];
            $qPrice = 0.0;
            $qHasPb = false;
            $qHas360 = false;

            foreach ($items as $item) {
                $name = trim((string) ($item->service_name ?? ''));
                $desc = trim((string) ($item->description ?? ''));
                $combined = mb_strtolower($name . ' ' . $desc);

                // Ignorar el Álbum QR de fotos de invitados
                if (str_contains($combined, 'álbum de fotos qr') || str_contains($combined, 'album de fotos qr') || str_contains($combined, 'carteles pdf')) {
                    continue;
                }

                $isPb = str_contains($combined, 'fotomaton') || str_contains($combined, 'fotomatón') || str_contains($combined, 'photobooth');
                $is360 = str_contains($combined, '360') || str_contains($combined, 'plataforma');
                $isDuo = str_contains($combined, 'pack dúo') || str_contains($combined, 'pack duo') || ($isPb && $is360);
                $isSweet = str_contains($combined, 'fuente de chocolate') || str_contains($combined, 'mesa de chuches') || str_contains($combined, 'solo chuches') || str_contains($combined, 'candy bar') || str_contains($combined, 'carrito de gofres');

                if ($isPb || $is360 || $isDuo || $isSweet) {
                    $qty = max(1, (int) ($item->quantity ?? 1));
                    // En QuoteItem la columna de precio es 'price' (unitario); soportamos también total_price como fallback
                    $unitPrice = (float) ($item->price ?? $item->total_price ?? 0);
                    $lineTotal = round($unitPrice * $qty, 2);

                    if ($lineTotal < 0) {
                        // Línea de descuento asociada a este servicio
                        $qPrice += $lineTotal;
                        continue;
                    }

                    // Si la línea tenía precio 0 (ej. "A consultar"), estimar según tarifas del proveedor configurado
                    if ($lineTotal <= 0) {
                        $is3h = str_contains($combined, '3 hora') || str_contains($combined, '3h');
                        $is2h = str_contains($combined, '2 hora') || str_contains($combined, '2h');
                        if ($isDuo) {
                            $lineTotal = (float) ($is2h ? ($pbConfig['pack_duo_2h'] ?? 690) : ($pbConfig['pack_duo_3h'] ?? 850));
                        } elseif ($isPb) {
                            $lineTotal = (float) ($is3h ? ($pbConfig['photobooth_3h'] ?? 480) : ($pbConfig['photobooth_2h'] ?? 430));
                        } elseif ($is360) {
                            $lineTotal = (float) ($is3h ? ($pbConfig['video_360_3h'] ?? 490) : ($pbConfig['video_360_2h'] ?? 430));
                        }
                    }

                    $qNames[] = $name;
                    $qWithPrices[] = $name . ($lineTotal > 0 ? ' (' . number_format($lineTotal, 2, ',', '.') . ' €)' : '');
                    $qPrice += $lineTotal;

                    if ($isPb || $isDuo) {
                        $qHasPb = true;
                    }
                    if ($is360 || $isDuo) {
                        $qHas360 = true;
                    }
                }
            }

            if (!empty($qNames)) {
                $detectedNames = $qNames;
                $detectedWithPrices = $qWithPrices;
                $detectedClientPrice = $qPrice;
                $hasPhotobooth = $qHasPb;
                $has360 = $qHas360;
                break;
            }
        }

        // Si no estaba en líneas de presupuesto pero se menciona en notas de solicitud online (/presupuesto)
        if (empty($detectedNames) && !empty($this->notes)) {
            $notesLower = mb_strtolower((string) $this->notes);
            $isDuoNotes = str_contains($notesLower, 'pack dúo') || str_contains($notesLower, 'pack duo');
            if ($isDuoNotes || str_contains($notesLower, 'fotomaton') || str_contains($notesLower, 'fotomatón')) {
                $hasPhotobooth = true;
            }
            if ($isDuoNotes || str_contains($notesLower, 'plataforma 360') || str_contains($notesLower, 'video 360') || str_contains($notesLower, 'vídeo 360')) {
                $has360 = true;
            }
            $is2hNotes = str_contains($notesLower, '2 hora') || str_contains($notesLower, '2h');
            $is3hNotes = str_contains($notesLower, '3 hora') || str_contains($notesLower, '3h');
            if ($hasPhotobooth && $has360) {
                $lbl = 'Pack Dúo: Fotomatón + Plataforma 360º' . ($is2hNotes ? ' (2 Horas)' : ($is3hNotes ? ' (3 Horas)' : ''));
                $detectedNames[] = $lbl;
                $detectedClientPrice = (float) ($is2hNotes ? ($pbConfig['pack_duo_2h'] ?? 690) : ($pbConfig['pack_duo_3h'] ?? 850));
                $detectedWithPrices[] = $lbl . ' (' . number_format($detectedClientPrice, 2, ',', '.') . ' €)';
            } elseif ($hasPhotobooth) {
                $lbl = 'Fotomatón' . ($is3hNotes ? ' (3 Horas)' : ($is2hNotes ? ' (2 Horas)' : ''));
                $detectedNames[] = $lbl;
                $detectedClientPrice = (float) ($is3hNotes ? ($pbConfig['photobooth_3h'] ?? 480) : ($pbConfig['photobooth_2h'] ?? 430));
                $detectedWithPrices[] = $lbl . ' (' . number_format($detectedClientPrice, 2, ',', '.') . ' €)';
            } elseif ($has360) {
                $lbl = 'Plataforma 360º' . ($is3hNotes ? ' (3 Horas)' : ($is2hNotes ? ' (2 Horas)' : ''));
                $detectedNames[] = $lbl;
                $detectedClientPrice = (float) ($is3hNotes ? ($pbConfig['video_360_3h'] ?? 490) : ($pbConfig['video_360_2h'] ?? 430));
                $detectedWithPrices[] = $lbl . ' (' . number_format($detectedClientPrice, 2, ',', '.') . ' €)';
            }
        }

        $detectedServiceText = implode(' + ', array_unique(array_filter($detectedNames)));
        $detectedServicesWithPricesText = implode(' + ', array_unique(array_filter($detectedWithPrices)));
        $suggestedDeposit = ($hasPhotobooth && $has360) ? 200.0 : (($hasPhotobooth || $has360 || !empty($detectedNames)) ? 100.0 : 100.0);

        $primaryBooking = $bookings->first();

        $serviceLabel = ($primaryBooking && !empty($primaryBooking->service_description) && $primaryBooking->service_description !== 'Fotomatón / Plataforma 360º')
            ? $primaryBooking->service_description
            : ($detectedServiceText ?: ($primaryBooking?->service_description ?: 'Fotomatón / Plataforma 360º'));
        $providerName = $primaryBooking?->provider_name ?: $suggestedProviderName;
        $providerKey = $primaryBooking?->provider_key ?: $suggestedProviderKey;

        // El precio pasado al cliente (client_price) se detecta siempre automáticamente del presupuesto si existe (> 0)
        $detectedClientPrice = max(0.0, round($detectedClientPrice, 2));
        $clientPrice = $detectedClientPrice > 0
            ? $detectedClientPrice
            : (($primaryBooking && (float) $primaryBooking->client_price > 0) ? (float) $primaryBooking->client_price : 0.0);

        // El coste de proveedor usa el guardado si es > 0, o por defecto el precio detectado en el presupuesto del cliente
        $providerTotalCost = ($primaryBooking && (float) $primaryBooking->provider_total_cost > 0)
            ? (float) $primaryBooking->provider_total_cost
            : $clientPrice;

        $depositPaid = $primaryBooking ? (bool) $primaryBooking->deposit_paid && (float) $primaryBooking->deposit_amount > 0 : false;
        $depositAmount = $depositPaid ? (float) $primaryBooking->deposit_amount : 0.0;
        $depositDate = $primaryBooking?->deposit_paid_at;
        $depositMethod = $primaryBooking?->deposit_payment_method;
        $depositMethodLabel = EventProviderBooking::formatMethodLabel($depositMethod);
        $depositMethodIcon = EventProviderBooking::formatMethodIcon($depositMethod);

        $finalPaid = $primaryBooking ? (bool) $primaryBooking->final_paid && (float) $primaryBooking->final_amount > 0 : false;
        $finalAmount = $finalPaid ? (float) $primaryBooking->final_amount : 0.0;
        $finalDate = $primaryBooking?->final_paid_at;
        $finalMethod = $primaryBooking?->final_payment_method;
        $finalMethodLabel = EventProviderBooking::formatMethodLabel($finalMethod);
        $finalMethodIcon = EventProviderBooking::formatMethodIcon($finalMethod);

        $totalPaidToProvider = round($depositAmount + $finalAmount, 2);
        $effectiveCost = max($providerTotalCost, $totalPaidToProvider);
        $pendingToProvider = max(0.0, round($effectiveCost - $totalPaidToProvider, 2));

        if ($totalPaidToProvider > 0 && ($pendingToProvider <= 0.05 && ($effectiveCost > 0 || $finalPaid))) {
            $status = 'paid';
            $statusLabel = '✅ Todo Pagado al Proveedor';
        } elseif ($totalPaidToProvider > 0) {
            $status = 'deposit_paid';
            $statusLabel = '🟢 Señal Pagada (Falta Resto)';
        } else {
            $status = 'unpaid';
            $statusLabel = '⏳ Pendiente de Pagar Señal';
        }

        return [
            'has_external_service' => $bookings->isNotEmpty() || $detectedServiceText !== '',
            'has_saved_booking' => $primaryBooking !== null,
            'booking_id' => $primaryBooking?->id,
            'booking' => $primaryBooking,
            'provider_key' => $providerKey,
            'provider_name' => $providerName,
            'service_description' => $serviceLabel,
            'detected_services' => $detectedServiceText,
            'detected_services_with_prices' => $detectedServicesWithPricesText,
            'detected_client_price' => $detectedClientPrice,
            'client_price' => $clientPrice,
            'provider_total_cost' => $effectiveCost,
            'suggested_deposit' => $suggestedDeposit,
            'deposit_paid' => $depositPaid,
            'deposit_amount' => $depositAmount,
            'deposit_date' => $depositDate,
            'deposit_method' => $depositMethod,
            'deposit_method_label' => $depositMethodLabel,
            'deposit_method_icon' => $depositMethodIcon,
            'final_paid' => $finalPaid,
            'final_amount' => $finalAmount,
            'final_date' => $finalDate,
            'final_method' => $finalMethod,
            'final_method_label' => $finalMethodLabel,
            'final_method_icon' => $finalMethodIcon,
            'total_paid' => $totalPaidToProvider,
            'pending_amount' => $pendingToProvider,
            'paid_percentage' => $effectiveCost > 0 ? min(100, (int) round(($totalPaidToProvider / $effectiveCost) * 100)) : ($totalPaidToProvider > 0 ? 100 : 0),
            'status' => $status,
            'status_label' => $statusLabel,
            'notes' => $primaryBooking?->notes ?? '',
        ];
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
            'on_hold' => 'En Espera',
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
            'on_hold' => 'bg-cyan-100 text-cyan-800 border-cyan-300 dark:bg-cyan-950/60 dark:text-cyan-300',
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
            'on_hold' => '⏸️',
            'confirmed' => '🟢',
            'completed' => '🔵',
            'rejected' => '❌',
            'cancelled' => '🔴',
            default => '📌',
        };
    }

    /**
     * Días transcurridos en estado "Sin Respuesta"
     */
    public function getDaysWithoutResponseAttribute(): int
    {
        if (($this->attributes['status'] ?? '') !== 'no_response') {
            return 0;
        }
        $date = !empty($this->attributes['no_response_at']) 
            ? \Carbon\Carbon::parse($this->attributes['no_response_at']) 
            : ($this->updated_at ?? now());
        return (int) $date->diffInDays(now());
    }

    /**
     * Días transcurridos en estado "En Espera / En Pausa" (on_hold)
     */
    public function getDaysOnHoldAttribute(): int
    {
        if (($this->attributes['status'] ?? '') !== 'on_hold') {
            return 0;
        }
        $date = !empty($this->attributes['no_response_at'])
            ? \Carbon\Carbon::parse($this->attributes['no_response_at'])
            : ($this->updated_at ?? now());
        return (int) $date->diffInDays(now());
    }

    /**
     * Etiqueta humana del tiempo en pausa ("En Espera")
     */
    public function getOnHoldTimeLabelAttribute(): string
    {
        $days = $this->days_on_hold;
        if ($days <= 0) {
            return 'En pausa hoy';
        }
        if ($days === 1) {
            return '1 día en pausa';
        }
        return "{$days} días en pausa";
    }

    /**
     * Indica si lleva 30 días o más en espera (vencimiento del plazo del presupuesto)
     */
    public function getIsOnHoldExpiredAttribute(): bool
    {
        return ($this->attributes['status'] ?? '') === 'on_hold' && $this->days_on_hold >= 30;
    }

    /**
     * Enlace directo de WhatsApp con el aviso de fin de plazo de presupuesto (+30 días en pausa)
     */
    public function getOnHoldExpiredWhatsappUrlAttribute(): string
    {
        if (empty($this->client_contact_phone)) {
            return '';
        }
        $data = \App\Services\WhatsAppTemplateService::followupReminder($this, 'fin_plazo_presupuesto');
        return $data['url'] ?? '';
    }

    /**
     * Nivel de urgencia del recordatorio de seguimiento
     */
    public function getNoResponseUrgencyLevelAttribute(): string
    {
        $days = $this->days_without_response;
        if ($days >= 4) {
            return 'critical'; // Rojo / Requiere reactivación urgente
        }
        if ($days >= 2) {
            return 'warning';  // Amarillo / Atención
        }
        return 'normal';       // Púrpura / Reciente
    }

    /**
     * Etiqueta humana del tiempo en "Sin Respuesta"
     */
    public function getNoResponseTimeLabelAttribute(): string
    {
        $days = $this->days_without_response;
        if ($days <= 0) {
            return 'Puesto hoy';
        }
        if ($days === 1) {
            return '1 día sin respuesta';
        }
        return "{$days} días sin respuesta";
    }

    /**
     * Teléfono de contacto efectivo del cliente o pareja
     */
    public function getClientContactPhoneAttribute(): ?string
    {
        $phone = $this->client ? $this->client->phone : null;
        if (empty($phone)) {
            $phone = $this->partner_phone ?? null;
        }
        return !empty(trim((string)$phone)) ? trim((string)$phone) : null;
    }

    /**
     * Email de contacto real del cliente o pareja (excluyendo emails ficticios autogenerados)
     */
    public function getClientContactEmailAttribute(): ?string
    {
        $candidates = [
            $this->client ? $this->client->email : null,
            $this->partner_email ?? null,
        ];

        foreach ($candidates as $email) {
            $clean = strtolower(trim((string)$email));
            if (
                !empty($clean) &&
                filter_var($clean, FILTER_VALIDATE_EMAIL) &&
                !str_ends_with($clean, '@eventosmusicales.local') &&
                !str_starts_with($clean, 'sin-email@') &&
                !str_starts_with($clean, 'cliente_') &&
                !str_starts_with($clean, 'lead_')
            ) {
                return $clean;
            }
        }

        // Fallback: buscar si en las notas del evento figura un email del lead
        if (!empty($this->notes) && preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', (string)$this->notes, $m)) {
            $found = strtolower(trim($m[0]));
            if (
                !str_ends_with($found, '@eventosmusicales.local') &&
                !str_contains($found, 'bodas.net') &&
                !str_contains($found, 'madrigalmarketing') &&
                !str_contains($found, 'nunezandson') &&
                !str_contains($found, 'javnx')
            ) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Enlace directo de WhatsApp con la plantilla recomendada según los días transcurridos (Madrigal Funnel)
     */
    public function getNoResponseWhatsappUrlAttribute(): string
    {
        if (empty($this->client_contact_phone)) {
            return '';
        }
        $data = \App\Services\WhatsAppTemplateService::followupReminder($this);
        return $data['url'] ?? '';
    }

    /**
     * Enlace mailto: directo con la plantilla recomendada para seguimiento por Email
     */
    public function getNoResponseMailtoUrlAttribute(): string
    {
        $data = \App\Services\WhatsAppTemplateService::followupReminder($this);
        return $data['mailto_url'] ?? '';
    }

    /**
     * Objeto completo con la plantilla recomendada actual
     */
    public function getRecommendedFollowupAttribute(): array
    {
        return \App\Services\WhatsAppTemplateService::followupReminder($this);
    }

    /**
     * Todas las plantillas de seguimiento disponibles para este evento
     */
    public function getFollowupOptionsAttribute(): array
    {
        return \App\Services\WhatsAppTemplateService::getFollowupOptions($this);
    }

    /**
     * Alias retrocompatible para deposit_paid_amount
     */
    public function getDepositAmountAttribute(): float
    {
        return (float) ($this->attributes['deposit_paid_amount'] ?? 0);
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
     * Desglose financiero unificado del evento (Presupuesto, Señal, Liquidación, Facturas, Métodos y Fechas de pago)
     */
    public function getFinancialBreakdownAttribute(): array
    {
        $quotes = $this->relationLoaded('quotes') ? $this->quotes : $this->quotes()->get();
        $invoices = ($this->relationLoaded('invoices') ? $this->invoices : $this->invoices()->get())
            ->where('status', '!=', 'cancelled')
            ->values();

        $activeQuote = $quotes->where('status', 'accepted')->sortByDesc('id')->first()
            ?? $quotes->where('status', '!=', 'rejected')->sortByDesc('id')->first()
            ?? $quotes->sortByDesc('id')->first();

        $quoteTotal = 0.0;
        $signalExpected = 0.0;
        if ($activeQuote) {
            if ($activeQuote->tax_type === 'excluded') {
                $quoteTotal = round((float) $activeQuote->amount + (float) $activeQuote->computed_tax, 2);
            } else {
                $quoteTotal = (float) $activeQuote->amount;
            }
            $signalExpected = (float) $activeQuote->signal_amount;
        } elseif ($invoices->count() > 0) {
            $quoteTotal = (float) $invoices->sum('total');
        }

        $hasDepositField = (bool) ($this->deposit_paid ?? false) && (float) ($this->deposit_paid_amount ?? 0) > 0;
        $depositFieldAmount = $hasDepositField ? (float) $this->deposit_paid_amount : 0.0;
        $depositFieldMethod = $this->deposit_payment_method ?: null;
        $depositFieldDate = !empty($this->deposit_paid_at) ? \Carbon\Carbon::parse($this->deposit_paid_at) : null;

        // Identificar si alguna factura/recibo corresponde a la señal de reserva
        $signalInvoice = $invoices->first(function ($inv) {
            $n = mb_strtolower((string) ($inv->notes ?? ''));
            return str_contains($n, 'señal') || str_contains($n, 'senal') || str_contains($n, 'reserva');
        });

        if (!$signalInvoice && $hasDepositField && $invoices->count() === 1) {
            $onlyInv = $invoices->first();
            if (abs((float) $onlyInv->total - $depositFieldAmount) < 0.05) {
                $signalInvoice = $onlyInv;
            }
        }

        $depositPaid = $hasDepositField || ($signalInvoice && $signalInvoice->status === 'paid');
        $depositAmount = $hasDepositField
            ? $depositFieldAmount
            : (($signalInvoice && $signalInvoice->status === 'paid') ? (float) $signalInvoice->total : 0.0);
        $depositDate = $depositFieldDate ?: ($signalInvoice?->issue_date ? \Carbon\Carbon::parse($signalInvoice->issue_date) : null);
        $depositMethod = $depositFieldMethod ?: ($signalInvoice?->effective_payment_method ?? null);

        $depositMethodLabel = match ($depositMethod) {
            'bizum' => 'Bizum',
            'transfer' => 'Transferencia Bancaria',
            'cash' => 'Efectivo',
            'card' => 'Tarjeta / TPV',
            'other' => 'Otro',
            default => $depositMethod ? ucfirst($depositMethod) : 'No especificado',
        };
        $depositMethodIcon = match ($depositMethod) {
            'bizum' => '📱',
            'transfer' => '🏦',
            'cash' => '💵',
            'card' => '💳',
            default => '💶',
        };

        // Resto de documentos pagados (liquidación final o facturas adicionales)
        $otherPaidInvoices = $invoices->filter(function ($inv) use ($signalInvoice) {
            if ($inv->status !== 'paid') {
                return false;
            }
            if ($signalInvoice && $inv->id === $signalInvoice->id) {
                return false;
            }
            return true;
        })->values();

        $sumOtherPaid = (float) $otherPaidInvoices->sum('total');

        if ($signalInvoice && $signalInvoice->status === 'paid') {
            $finalPaidAmount = $sumOtherPaid;
            $totalPaid = $depositAmount + $finalPaidAmount;
        } elseif ($depositPaid && $depositAmount > 0) {
            if ($otherPaidInvoices->isEmpty()) {
                $finalPaidAmount = 0.0;
                $totalPaid = $depositAmount;
            } else {
                $hasExplicitFinalNote = $otherPaidInvoices->contains(function ($inv) {
                    $n = mb_strtolower((string) ($inv->notes ?? ''));
                    return str_contains($n, 'liquidación') || str_contains($n, 'liquidacion') || str_contains($n, 'final') || str_contains($n, 'resto');
                });

                if (!$hasExplicitFinalNote && $quoteTotal > 0 && $sumOtherPaid >= ($quoteTotal - 0.05)) {
                    $totalPaid = max($depositAmount, $sumOtherPaid);
                    $finalPaidAmount = max(0.0, $totalPaid - $depositAmount);
                } else {
                    $finalPaidAmount = $sumOtherPaid;
                    $totalPaid = $depositAmount + $finalPaidAmount;
                }
            }
        } else {
            $finalPaidAmount = $sumOtherPaid;
            $totalPaid = $sumOtherPaid;
        }

        $lastFinalInv = $otherPaidInvoices->sortByDesc('issue_date')->first();
        $finalPaidDate = $lastFinalInv?->issue_date ? \Carbon\Carbon::parse($lastFinalInv->issue_date) : null;
        $finalMethod = $lastFinalInv?->effective_payment_method ?? null;
        $finalMethodLabel = match ($finalMethod) {
            'bizum' => 'Bizum',
            'transfer' => 'Transferencia Bancaria',
            'cash' => 'Efectivo',
            'card' => 'Tarjeta / TPV',
            'other' => 'Otro',
            default => $finalMethod ? ucfirst($finalMethod) : null,
        };
        $finalMethodIcon = match ($finalMethod) {
            'bizum' => '📱',
            'transfer' => '🏦',
            'cash' => '💵',
            'card' => '💳',
            default => '💶',
        };

        $effectiveQuoteTotal = max($quoteTotal, $totalPaid);
        $pendingAmount = max(0.0, round($effectiveQuoteTotal - $totalPaid, 2));

        if ($totalPaid > 0 && ($pendingAmount <= 0.05 || $this->status === 'completed' && $effectiveQuoteTotal <= 0)) {
            $paymentStatus = 'paid';
            $paymentStatusLabel = 'Pagado 100%';
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'partial';
            $paymentStatusLabel = $depositPaid ? 'Señal Pagada' : 'Pago Parcial';
        } elseif ($effectiveQuoteTotal > 0) {
            $paymentStatus = 'unpaid';
            $paymentStatusLabel = 'Pendiente';
        } else {
            $paymentStatus = 'no_quote';
            $paymentStatusLabel = 'Sin Presupuesto';
        }

        $lastPaymentDate = $finalPaidDate ?: $depositDate;
        if ($depositDate && $finalPaidDate && $depositDate->gt($finalPaidDate)) {
            $lastPaymentDate = $depositDate;
        }

        // Lista de pagos individuales de este evento (para el Libro de Ingresos)
        $paymentsList = [];
        if ($depositPaid && $depositAmount > 0) {
            $paymentsList[] = [
                'event_id' => $this->id,
                'invoice_id' => $signalInvoice?->id,
                'document_number' => $signalInvoice?->invoice_number ?? ('SEÑAL-EV' . str_pad((string) $this->id, 3, '0', STR_PAD_LEFT)),
                'document_type' => $signalInvoice ? ($signalInvoice->isReceipt() ? 'recibo' : 'factura') : 'senal',
                'concept' => 'Señal de Reserva',
                'base_amount' => $signalInvoice ? (float) $signalInvoice->amount : $depositAmount,
                'tax_amount' => $signalInvoice ? (float) $signalInvoice->tax : 0.0,
                'tax_rate' => $signalInvoice ? (float) $signalInvoice->tax_rate : 0.0,
                'total' => $depositAmount,
                'date' => $depositDate,
                'method' => $depositMethod ?: 'other',
                'method_label' => $depositMethodLabel,
                'method_icon' => $depositMethodIcon,
                'status' => 'paid',
                'notes' => $this->deposit_notes ?: ($signalInvoice?->notes ?? 'Señal de reserva registrada en el evento'),
            ];
        }

        foreach ($invoices as $inv) {
            if ($signalInvoice && $inv->id === $signalInvoice->id && $depositPaid) {
                continue; // Ya incluido arriba como señal con todos sus metadatos
            }
            $pm = $inv->effective_payment_method ?: ($this->deposit_payment_method ?: 'other');
            $paymentsList[] = [
                'event_id' => $this->id,
                'invoice_id' => $inv->id,
                'document_number' => $inv->invoice_number,
                'document_type' => $inv->isReceipt() ? 'recibo' : 'factura',
                'concept' => $inv->payment_concept_label,
                'base_amount' => (float) $inv->amount,
                'tax_amount' => (float) $inv->tax,
                'tax_rate' => (float) $inv->tax_rate,
                'total' => (float) $inv->total,
                'date' => $inv->issue_date ? \Carbon\Carbon::parse($inv->issue_date) : null,
                'method' => $pm,
                'method_label' => $inv->payment_method_label,
                'method_icon' => $inv->payment_method_icon,
                'status' => $inv->status,
                'notes' => $inv->notes,
            ];
        }

        // Totales por método de pago en este evento (solo cobrados)
        $byMethod = [
            'bizum' => 0.0,
            'transfer' => 0.0,
            'cash' => 0.0,
            'card' => 0.0,
            'other' => 0.0,
        ];
        foreach ($paymentsList as $p) {
            if (($p['status'] ?? '') === 'paid') {
                $mKey = in_array($p['method'], ['bizum', 'transfer', 'cash', 'card'], true) ? $p['method'] : 'other';
                $byMethod[$mKey] += (float) $p['total'];
            }
        }

        return [
            'active_quote' => $activeQuote,
            'quote_total' => $effectiveQuoteTotal,
            'signal_expected' => $signalExpected,
            'deposit_paid' => $depositPaid,
            'deposit_amount' => $depositAmount,
            'deposit_date' => $depositDate,
            'deposit_method' => $depositMethod,
            'deposit_method_label' => $depositMethodLabel,
            'deposit_method_icon' => $depositMethodIcon,
            'signal_invoice' => $signalInvoice,
            'final_paid_amount' => $finalPaidAmount,
            'final_paid_date' => $finalPaidDate,
            'final_method' => $finalMethod,
            'final_method_label' => $finalMethodLabel,
            'final_method_icon' => $finalMethodIcon,
            'total_paid' => round($totalPaid, 2),
            'pending_amount' => $pendingAmount,
            'paid_percentage' => $effectiveQuoteTotal > 0 ? min(100, (int) round(($totalPaid / $effectiveQuoteTotal) * 100)) : ($totalPaid > 0 ? 100 : 0),
            'payment_status' => $paymentStatus,
            'payment_status_label' => $paymentStatusLabel,
            'last_payment_date' => $lastPaymentDate,
            'by_method' => $byMethod,
            'payments_list' => $paymentsList,
            'invoices_count' => $invoices->count(),
        ];
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
        if (str_contains($nameLower, 'video') || str_contains($nameLower, 'vídeo') || str_contains($nameLower, 'grabac')) return 'video';
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
     * Determina si es un servicio de vídeo / videógrafo
     */
    public function getIsVideoAttribute(): bool
    {
        return $this->event_type_clean === 'video';
    }

    /**
     * Etiqueta legible del tipo de evento
     */
    public function getEventTypeLabelAttribute(): string
    {
        return match($this->event_type_clean) {
            'video' => '📹 Vídeo / Videógrafo',
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
            'video' => '📹',
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

    /**
     * Devuelve el nombre comercial legible de la marca asignada al evento.
     */
    public function getBrandLabelAttribute(): string
    {
        if ($this->brand_clean === 'javnx') return 'JAVNX DJ';
        if ($this->brand_clean === 'mago_leugim') return 'Mago Leugim';
        return 'Núñez and Son';
    }

    /**
     * Devuelve el icono representativo de la marca comercial asignada al evento.
     */
    public function getBrandIconAttribute(): string
    {
        if ($this->brand_clean === 'javnx') return '🎧';
        if ($this->brand_clean === 'mago_leugim') return '🎩';
        return '👑';
    }

    /**
     * Devuelve la configuración e información completa de la marca asignada al evento.
     */
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
                    
                    // Pack Esencial / Básico (4h), Pack Signature / Medio (5h), Pack Deluxe Show / Premium (6h)
                    if (str_contains($name, 'pack') || str_contains($desc, 'pack')) {
                        if (str_contains($name, 'esencial') || str_contains($name, 'clásico') || str_contains($name, 'clasico') || str_contains($name, 'básico') || str_contains($name, 'basico')) return (float)\App\Models\Setting::get('pack_basic_hours', 4);
                        if (str_contains($name, 'signature') || str_contains($name, 'medio')) return (float)\App\Models\Setting::get('pack_medium_hours', 5);
                        if (str_contains($name, 'deluxe') || str_contains($name, 'festival') || str_contains($name, 'premium')) return (float)\App\Models\Setting::get('pack_premium_hours', 6);
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
     * Hora de inicio de la actuación musical o fase principal del evento para el cliente.
     * Prioriza la primera fase contratada (Ceremonia, Cóctel, Banquete o Baile).
     * Solo si no hay ninguna fase definida, hace fallback a la hora general/montaje (start_time).
     */
    public function getEffectiveStartTimeAttribute(): string
    {
        // 1. Tomar la primera fase musical del evento (Ceremonia, Cóctel, Banquete o Baile)
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

        // 2. Si no hay fases musicales desglosadas, fallback a la hora general / montaje
        if (!empty($this->start_time)) {
            return substr(trim((string)$this->start_time), 0, 5);
        }

        return 'Por determinar';
    }

    /**
     * Hora estimada de llegada y montaje técnico del equipamiento (start_time).
     */
    public function getSetupTimeLabelAttribute(): ?string
    {
        if (empty($this->start_time)) {
            return null;
        }
        return substr(trim((string)$this->start_time), 0, 5) . ' h';
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
     * Normaliza una hora extraída de texto libre a formato 24h (HH:MM) según la fase del evento.
     */
    protected static function normalizeParsedTime(int $hour, int $minute, string $phase, ?string $referenceDayStart = null): ?string
    {
        if ($hour < 0 || $hour > 24 || $minute < 0 || $minute > 59) {
            return null;
        }
        if ($hour === 24) {
            $hour = 0;
        }

        switch ($phase) {
            case 'ceremony':
                // En España las ceremonias a la "1:00" o "6:30" se refieren a 13:00 o 18:30
                if ($hour >= 1 && $hour <= 9) {
                    $hour += 12;
                }
                break;

            case 'cocktail':
                // Cóctel nunca es de madrugada (1:00-10:59 -> 13:00-22:59)
                if ($hour >= 1 && $hour <= 10) {
                    $hour += 12;
                }
                break;

            case 'banquet':
                // Comida o cena nunca es entre la 1:00 AM y las 11:59 AM -> 13:00-23:59
                if ($hour >= 1 && $hour <= 11) {
                    $hour += 12;
                }
                break;

            case 'dance_start':
                // Si ponen "baile a las 7:00" o "baile 11:30", entre 5 y 11 es tarde/noche (17:00-23:59)
                if ($hour >= 5 && $hour <= 11) {
                    $hour += 12;
                }
                break;

            case 'max_end':
            case 'dance_end':
                // Si el baile/evento es de tarde y ponen "finalizar a las 11:30" o "hasta las 10:30", 9..11 es 21:00..23:59
                if ($hour >= 9 && $hour <= 11) {
                    $hour += 12;
                }
                break;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    /**
     * Extrae automáticamente los horarios de Ceremonia, Cóctel, Banquete, Baile, Fin y Montaje
     * desde texto libre escrito por el cliente (en /presupuesto, notas o email/WhatsApp).
     */
    public static function extractScheduleFromText(?string $rawText, ?float $defaultDjHours = null): array
    {
        $result = [
            'ceremony_time' => null,
            'cocktail_time' => null,
            'banquet_time' => null,
            'dance_start_time' => null,
            'dance_duration_hours' => $defaultDjHours && $defaultDjHours > 0 ? (float) $defaultDjHours : null,
            'dance_end_time' => null,
            'max_end_time' => null,
            'start_time' => null,
        ];

        $text = trim((string) $rawText);
        if ($text === '') {
            return $result;
        }

        // Ignorar marcas de fecha+hora de cabeceras como "[10/11/2026 22:40]" o "11/11/2026 09:00"
        $clean = preg_replace('/\[\s*\d{1,2}\/\d{1,2}\/\d{2,4}\s+\d{1,2}:\d{2}\s*\]/u', ' ', $text);
        $clean = preg_replace('/\b\d{1,2}\/\d{1,2}\/\d{2,4}\s+\d{1,2}:\d{2}\b/u', ' ', $clean);
        // Ignorar "360" o "360º" para que nunca se confunda con una hora
        $clean = preg_replace('/\b360\s*[º°]?\b/u', ' ', $clean);

        $timePattern = '(?:(?P<h>\d{1,2})\s*[:\.]\s*(?P<m>\d{2})\s*(?:h|hs|hrs)?|(?P<h2>\d{1,2})\s*(?:h|hs|hrs)\b)';

        $phaseKeywords = [
            'ceremony'    => '(?:ceremonia(?:\s+civil|\s+religiosa)?|enlace|iglesia|ayuntamiento|misa)',
            'cocktail'    => '(?:c[oó]ctel(?:\s+de\s+bienvenida)?|cocktail|aperitivo|vino\s+espa[nñ]ol|copa\s+de\s+bienvenida)',
            'banquet'     => '(?:banquete|comida|almuerzo|cena|convite|entrada\s+sal[oó]n)',
            'dance_start' => '(?:baile(?:\s+inicial|\s+nupcial)?|barra\s+libre|fiesta|discom[oó]vil|inicio\s+(?:del\s+)?baile|empieza\s+(?:el\s+)?baile)',
            'max_end'     => '(?:finalizar(?:á|a|emos)?|finaliza|fin\s+de\s+fiesta|fin\s+del\s+evento|terminar(?:á|a|emos)?|termina|acabar(?:á|a|emos)?|acaba|hasta\s+las|hora\s+l[ií]mite|cierre)',
            'setup'       => '(?:montaje|hora\s+de\s+llegada|llegada\s+montaje)',
        ];

        $allPhaseWordsUnion = implode('|', $phaseKeywords);

        // 0. Caso especial: rango directo de baile "baile de 19:00 a 23:30" o "barra libre 23:30 - 03:30"
        $rangeRegex = '/\b' . $phaseKeywords['dance_start'] . '\b[^,\n;]{0,25}?\b(?P<h1>\d{1,2})[:\.](?P<m1>\d{2})\s*(?:h|hs)?\s*(?:a|hasta|-|–)\s*(?:las\s*)?(?P<h2>\d{1,2})[:\.](?P<m2>\d{2})/iu';
        if (preg_match($rangeRegex, $clean, $rm)) {
            $result['dance_start_time'] = self::normalizeParsedTime((int) $rm['h1'], (int) $rm['m1'], 'dance_start');
            $result['max_end_time'] = self::normalizeParsedTime((int) $rm['h2'], (int) $rm['m2'], 'max_end', $result['dance_start_time']);
        }

        // 1. Buscar patrón: PALABRA_CLAVE ... HORA (sin cruzar otra palabra clave, ni 'y/luego', ni coma/salto de línea)
        // Ej: "La ceremonia será a las 13:00", "cóctel 14:30", "banquete 16:00", "para finalizar a las 11:30"
        foreach ($phaseKeywords as $phase => $kwRegex) {
            $field = $phase === 'setup' ? 'start_time' : ($phase === 'max_end' ? 'max_end_time' : ($phase === 'dance_start' ? 'dance_start_time' : "{$phase}_time"));
            if (!empty($result[$field])) {
                continue;
            }

            $patternAfter = '/\b' . $kwRegex . '\b(?:(?!\b(?:' . $allPhaseWordsUnion . '|y|luego|despu[eé]s)\b)[^,\n;\d]){0,40}?' . $timePattern . '/iu';
            if (preg_match($patternAfter, $clean, $m)) {
                $h = isset($m['h']) && $m['h'] !== '' ? (int) $m['h'] : (int) ($m['h2'] ?? -1);
                $min = isset($m['m']) && $m['m'] !== '' ? (int) $m['m'] : 0;
                $result[$field] = self::normalizeParsedTime($h, $min, $phase, $result['dance_start_time']);
            }
        }

        // 2. Buscar patrón inverso: HORA ... PALABRA_CLAVE en el mismo fragmento (sin cruzar coma ni otra hora)
        // Ej: ",19:00 baile inicial", "a las 13:00 ceremonia", "14:30 cóctel"
        foreach ($phaseKeywords as $phase => $kwRegex) {
            $field = $phase === 'setup' ? 'start_time' : ($phase === 'max_end' ? 'max_end_time' : ($phase === 'dance_start' ? 'dance_start_time' : "{$phase}_time"));
            if (!empty($result[$field])) {
                continue;
            }

            $patternBefore = '/' . $timePattern . '\s*(?:h|hs|hrs)?\s*(?:-\s*)?(?:el\s+|la\s+|inicio\s+de\s+)?\b' . $kwRegex . '\b/iu';
            if (preg_match($patternBefore, $clean, $m)) {
                $h = isset($m['h']) && $m['h'] !== '' ? (int) $m['h'] : (int) ($m['h2'] ?? -1);
                $min = isset($m['m']) && $m['m'] !== '' ? (int) $m['m'] : 0;
                $result[$field] = self::normalizeParsedTime($h, $min, $phase, $result['dance_start_time']);
            }
        }

        // 3. Re-normalizar max_end_time si ahora ya conocemos dance_start_time o ceremony_time
        if (!empty($result['max_end_time'])) {
            [$eh, $em] = array_map('intval', explode(':', $result['max_end_time']));
            if ($eh >= 9 && $eh <= 11) {
                $result['max_end_time'] = sprintf('%02d:%02d', $eh + 12, $em);
            }
        }

        // 4. Calcular fin previsto del baile (dance_end_time)
        if (!empty($result['dance_start_time'])) {
            $hours = $result['dance_duration_hours'];
            if (!empty($hours) && $hours > 0) {
                try {
                    $startObj = \Carbon\Carbon::createFromFormat('H:i', $result['dance_start_time']);
                    $result['dance_end_time'] = $startObj->addMinutes((int) round($hours * 60))->format('H:i');
                } catch (\Throwable $e) {}
            } elseif (!empty($result['max_end_time'])) {
                $result['dance_end_time'] = $result['max_end_time'];
                try {
                    $startObj = \Carbon\Carbon::createFromFormat('H:i', $result['dance_start_time']);
                    $endObj = \Carbon\Carbon::createFromFormat('H:i', $result['max_end_time']);
                    if ($endObj->lessThanOrEqualTo($startObj)) {
                        $endObj->addDay();
                    }
                    $diffHours = round($startObj->diffInMinutes($endObj) / 60, 1);
                    if ($diffHours >= 0.5 && $diffHours <= 12) {
                        $result['dance_duration_hours'] = $diffHours;
                    }
                } catch (\Throwable $e) {}
            }
        }

        return $result;
    }

    /**
     * Completa automáticamente los campos de horario vacíos del evento a partir de schedule_notes / notes.
     */
    public function syncParsedScheduleFromNotes(bool $saveIfChanged = true, bool $overwriteExisting = false): array
    {
        $sources = [];
        if (!empty($this->schedule_notes)) {
            $sources[] = (string) $this->schedule_notes;
        }
        if (!empty($this->notes)) {
            // Extraer especialmente el bloque de NOTAS Y EXPLICACIÓN DEL CLIENTE o Horario de las notas generales
            $notesStr = (string) $this->notes;
            if (preg_match('/NOTAS Y EXPLICACIÓN DEL CLIENTE:\s*(.+?)(?:\n\n|\nServicios Solicitados:|$)/su', $notesStr, $nm)) {
                $sources[] = trim($nm[1]);
            }
            if (preg_match('/Horario\s*\/\s*Tramo del Evento:\s*(.+)/iu', $notesStr, $hm)) {
                $sources[] = trim($hm[1]);
            }
            $sources[] = $notesStr;
        }

        $combinedText = trim(implode("\n", array_unique(array_filter($sources))));
        if ($combinedText === '') {
            return [];
        }

        $suggestedHours = $this->dance_duration_hours ?: $this->suggested_dance_hours;
        $parsed = self::extractScheduleFromText($combinedText, $suggestedHours ? (float) $suggestedHours : null);

        $updates = [];
        $timeFields = [
            'ceremony_time',
            'cocktail_time',
            'banquet_time',
            'dance_start_time',
            'dance_duration_hours',
            'dance_end_time',
            'max_end_time',
            'start_time',
        ];

        foreach ($timeFields as $field) {
            $currentVal = $this->getAttribute($field);
            $newVal = $parsed[$field] ?? null;

            if ($newVal !== null && ($overwriteExisting || empty($currentVal))) {
                $this->setAttribute($field, $newVal);
                $updates[$field] = $newVal;
            }
        }

        // Si ya tenía dance_start_time (o se acaba de detectar) y aún no tenía dance_end_time, calcularlo
        if (!empty($this->dance_start_time) && empty($this->dance_end_time)) {
            $calcEnd = $this->calculated_dance_end_time ?: ($this->max_end_time ?: null);
            if (!empty($calcEnd)) {
                $this->dance_end_time = $calcEnd;
                $updates['dance_end_time'] = $calcEnd;
            }
        }

        if ($saveIfChanged && !empty($updates) && $this->exists) {
            try {
                $validUpdates = [];
                foreach ($updates as $col => $val) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('events', $col)) {
                        $validUpdates[$col] = $val;
                    }
                }
                if (!empty($validUpdates)) {
                    $this->update($validUpdates);
                }
            } catch (\Throwable $e) {}
        }

        return $updates;
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
            'video' => [
                'Preparativos / Casas',
                'Ceremonia',
                'Sesión de Pareja / Exteriores',
                'Cóctel',
                'Entrada al Banquete',
                'Momentos Especiales / Entregas',
                'Corte de Tarta',
                'Baile Nupcial',
                'Fiesta / Barra Libre',
            ],
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

    /**
     * Devuelve el catálogo de categorías o fases musicales según el tipo de evento indicado.
     */
    public static function getCategoriesForEventType(?string $eventType = 'boda'): array
    {
        $type = mb_strtolower(trim((string)$eventType));

        if ($type === 'video') {
            return [
                'ceremonia' => '💍 Ceremonia / Preparativos',
                'coctel' => '🍸 Cóctel / Exteriores',
                'banquete' => '🍽️ Banquete / Momentos',
                'baile' => '💃 Baile / Fiesta',
                'lista_negra' => '🚫 Prohibiciones / No Grabar',
            ];
        } elseif ($type === 'boda') {
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

    /**
     * Inserta una nueva nota ARRIBA DEL TODO para que lo más reciente sea siempre lo primero en leerse
     */
    public static function prependNote(?string $existingNotes, string $newEntry): string
    {
        $newEntry = trim($newEntry);
        $existing = trim((string) $existingNotes);

        if ($existing === '') {
            return $newEntry;
        }

        return $newEntry . "\n\n" . $existing;
    }

    /**
     * Devuelve el motivo de cancelación o rechazo (desde la columna dedicada o extrayéndolo de las notas)
     */
    public function getCancellationReasonDisplayAttribute(): ?string
    {
        if (!empty($this->attributes['cancellation_reason'] ?? null)) {
            return trim($this->attributes['cancellation_reason']);
        }

        if (!empty($this->notes)) {
            if (preg_match('/(?:EVENTO RECHAZADO|EVENTO CANCELADO)[^\n]*?Motivo:\s*(.+)$/mi', $this->notes, $m)) {
                return trim($m[1]);
            }
        }

        return null;
    }

    /**
     * Clave en Setting donde se guardan las notas importantes / tareas de preparación y compra de este evento.
     */
    public function importantItemsSettingKey(): string
    {
        return 'event_important_items_' . $this->id;
    }

    /**
     * Devuelve la lista de notas importantes / compras / preparación del evento.
     *
     * @return array<int, array{id:string, text:string, category:string, is_important:bool, is_done:bool, include_in_dossier:bool, source:?string, created_at:string}>
     */
    public function getImportantItemsAttribute(): array
    {
        if (!$this->exists) {
            return [];
        }
        $raw = Setting::get($this->importantItemsSettingKey());
        if (empty($raw)) {
            return [];
        }
        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $row) {
            if (!is_array($row) || empty(trim((string) ($row['text'] ?? '')))) {
                continue;
            }
            $items[] = [
                'id' => (string) ($row['id'] ?? Str::random(10)),
                'text' => trim((string) $row['text']),
                'category' => (string) ($row['category'] ?? 'compra'),
                'is_important' => (bool) ($row['is_important'] ?? true),
                'is_done' => (bool) ($row['is_done'] ?? false),
                'include_in_dossier' => (bool) ($row['include_in_dossier'] ?? true),
                'source' => !empty($row['source']) ? (string) $row['source'] : null,
                'created_at' => (string) ($row['created_at'] ?? now()->format('d/m/Y H:i')),
            ];
        }

        return $items;
    }

    /**
     * Guarda la lista de notas importantes / compras / preparación del evento.
     */
    public function saveImportantItems(array $items): void
    {
        if (!$this->exists) {
            return;
        }
        Setting::set($this->importantItemsSettingKey(), json_encode(array_values($items), JSON_UNESCAPED_UNICODE));
    }

    /**
     * Devuelve las notas importantes que deben mostrarse en el Dossier Técnico y en la Hoja de Carga PDF,
     * incluyendo también el material asignado que tenga la marca [IMPORTANTE] en sus observaciones.
     */
    public function getDossierAndPackingImportantAlertsAttribute(): array
    {
        $alerts = [];

        foreach ($this->important_items as $item) {
            if (!empty($item['include_in_dossier']) || !empty($item['is_important']) || in_array($item['category'] ?? '', ['compra', 'material', 'logistica'], true)) {
                $catLabel = match ($item['category'] ?? 'compra') {
                    'compra' => '🛒 Comprar / Preparar',
                    'material' => '📦 Material / Carga',
                    'logistica' => '🏰 Finca / Logística',
                    default => '📞 Acordado con novios',
                };
                $alerts[] = [
                    'id' => $item['id'],
                    'type' => 'note',
                    'category' => $item['category'] ?? 'compra',
                    'category_label' => $catLabel,
                    'text' => $item['text'],
                    'is_done' => !empty($item['is_done']),
                    'source' => $item['source'] ?? null,
                ];
            }
        }

        try {
            $equipments = $this->relationLoaded('equipment') ? $this->equipment : $this->equipment()->get();
            foreach ($equipments as $eq) {
                $note = trim((string) ($eq->pivot->notes ?? ''));
                if (str_contains(mb_strtoupper($note), '[IMPORTANTE]') || str_starts_with($note, '⚠️')) {
                    $cleanNote = trim(str_ireplace(['[IMPORTANTE]', '⚠️'], '', $note));
                    $alerts[] = [
                        'id' => 'eq_' . ($eq->pivot->id ?? $eq->id),
                        'type' => 'equipment',
                        'category' => 'material',
                        'category_label' => '📦 Material Crítico',
                        'text' => $eq->name . ' (' . ($eq->pivot->quantity ?? 1) . ' uds)' . ($cleanNote !== '' ? ' — ' . $cleanNote : ''),
                        'is_done' => false,
                        'source' => 'Hoja de Carga',
                    ];
                }
            }
        } catch (\Throwable $e) {}

        return $alerts;
    }

    /**
     * Extrae de las notas generales del evento únicamente las notas manuales o la solicitud original del cliente,
     * ocultando el ruido de registros automáticos de envíos de WhatsApp ("[dd/mm/yyyy HH:mm] 💬 WhatsApp...").
     */
    public function getCleanClientRequestNotesAttribute(): string
    {
        $raw = trim((string) ($this->notes ?? ''));
        if ($raw === '') {
            return '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $kept = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if (!empty($kept) && end($kept) !== '') {
                    $kept[] = '';
                }
                continue;
            }
            // Omitir líneas automáticas de envíos de plantillas o reseteos de contador
            if (preg_match('/^\[\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}\]\s*💬\s*WhatsApp\s*\(/u', $trimmed)) {
                continue;
            }
            $kept[] = $line;
        }

        return trim(implode("\n", $kept));
    }

    /**
     * Comprueba si las notificaciones/alertas de conversación están silenciadas para un ID de evento concreto.
     */
    public static function isEventMuted(?int $eventId): bool
    {
        if (!$eventId || $eventId <= 0) {
            return false;
        }
        try {
            return (string) Setting::get('event_notifications_muted_' . $eventId, '0') === '1';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Atributo dinámico: $event->notifications_muted
     */
    public function getNotificationsMutedAttribute(): bool
    {
        return self::isEventMuted($this->id ? (int) $this->id : null);
    }

    /**
     * Activa o desactiva el silencio de alertas/notificaciones para este evento concreto.
     */
    public function setNotificationsMuted(bool $muted): void
    {
        if (!$this->id) {
            return;
        }
        Setting::set('event_notifications_muted_' . $this->id, $muted ? '1' : '0');
    }
}


