<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Event;
use App\Models\User;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Livewire\Traits\HandlesDniValidation;

class EventManager extends Component
{
    use HandlesDniValidation;
    public $events;
    
    // Properties for creating a new event
    public $name;
    public $brand = 'nunez_and_son'; // nunez_and_son, javnx, mago_leugim
    public $event_type = 'boda'; // boda, video, empresa, cumpleanos, comunion, otro
    public $agreed_price = 250;
    public $initial_status = 'confirmed';
    public $new_client_name = '';
    public $new_client_phone = '';
    public $event_date;
    public $setup_date;
    public $start_time;
    public $dance_start_time;
    public $dance_duration_hours = 4.0;
    public $max_end_time;
    public $location;
    public $client_id;
    public $partner_name;
    public $partner_phone;
    public $partner_email;
    public $partner_dni;
    public $dj_id;
    public $assistant_id;
    public $assistant_ids = [];
    public $notes;
    
    public $editing_event_id = null;
    public $ceremony_time;
    public $cocktail_time;
    public $banquet_time;
    
    public $clients = [];
    public $djs = [];
    public $assistants = [];

    public $showCreateModal = false;
    public $status_filter = 'all'; // all, no_response, draft, confirmed, completed

    public function setStatusFilter($status)
    {
        $this->status_filter = $status;
    }

    protected $rules = [
        'name' => 'required|string|max:255',
        'brand' => 'nullable|string|in:nunez_and_son,javnx,mago_leugim',
        'event_type' => 'required|string|in:boda,video,empresa,cumpleanos,comunion,otro',
        'agreed_price' => 'nullable|numeric|min:0',
        'initial_status' => 'nullable|string|in:draft,confirmed',
        'new_client_name' => 'nullable|string|max:255',
        'new_client_phone' => 'nullable|string|max:50',
        'event_date' => 'required|date',
        'setup_date' => 'nullable|date',
        'start_time' => 'nullable|string|max:20',
        'ceremony_time' => 'nullable|string|max:20',
        'cocktail_time' => 'nullable|string|max:20',
        'banquet_time' => 'nullable|string|max:20',
        'dance_start_time' => 'nullable|string|max:20',
        'dance_duration_hours' => 'nullable|numeric|min:0.5|max:24',
        'max_end_time' => 'nullable|string|max:20',
        'location' => 'required|string|max:255',
        'client_id' => 'nullable|exists:users,id',
        'partner_name' => 'nullable|string|max:255',
        'partner_phone' => 'nullable|string|max:50',
        'partner_email' => 'nullable|email|max:255',
        'partner_dni' => 'nullable|string|max:50',
        'dj_id' => 'nullable|exists:users,id',
        'assistant_id' => 'nullable|exists:users,id',
        'assistant_ids' => 'nullable|array',
        'assistant_ids.*' => 'exists:users,id',
    ];

    public function mount()
    {
        if (auth()->check() && in_array(auth()->user()->role, ['admin', 'dj'])) {
            $syncRes = (new \App\Services\ImapLeadFetcherService())->autoSyncIfDue(120);
            if ($syncRes && ($syncRes['total_leads_created'] ?? 0) > 0) {
                session()->flash('message', "📩 ¡{$syncRes['total_leads_created']} nueva(s) solicitud(es) de evento importada(s) automáticamente desde el correo!");
            }
        }
        $this->loadEvents();
        $this->loadStaff();
    }

    public function syncEmailLeads()
    {
        try {
            \Illuminate\Support\Facades\Cache::forget('imap_auto_sync_last_run');
            $res = (new \App\Services\ImapLeadFetcherService())->fetchAllActiveMailboxes();
            $created = $res['total_leads_created'] ?? 0;
            $scanned = $res['total_scanned'] ?? 0;

            $this->loadEvents();
            $this->loadStaff();

            if ($created > 0) {
                session()->flash('message', "✅ ¡Sincronización completada! Se han importado {$created} nuevo(s) evento(s) de {$scanned} correos recientes revisados.");
            } else {
                session()->flash('message', "📬 Buzones al día ({$scanned} correos recientes verificados, 0 solicitudes nuevas pendientes).");
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Error al sincronizar buzones IMAP: ' . $e->getMessage());
        }
    }

    public function loadStaff()
    {
        $this->clients = User::where('role', 'client')->orderBy('name')->get();
        $this->djs = User::whereIn('role', ['dj', 'admin'])->orderBy('name')->get();
        $this->assistants = User::whereIn('role', ['assistant', 'dj', 'admin'])->orderBy('name')->get();
    }

    public function loadEvents()
    {
        $user = auth()->user();
        $relations = ['client', 'dj', 'assistant'];
        if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
            $relations[] = 'assistants';
        }

        if ($user->role === 'admin') {
            $this->events = Event::with($relations)->orderBy('event_date', 'asc')->get();
        } else {
            $this->events = Event::with($relations)
                ->where(function ($q) use ($user) {
                    $q->where('dj_id', $user->id)
                      ->orWhere('assistant_id', $user->id);
                    if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                        $q->orWhereHas('assistants', fn($sq) => $sq->where('users.id', $user->id));
                    }
                })
                ->orderBy('event_date', 'asc')
                ->get();
        }
    }

    public function openCreateModal()
    {
        if (!in_array(auth()->user()->role, ['admin', 'dj'])) {
            session()->flash('error', 'Solo los administradores y DJs autorizados pueden crear nuevos eventos.');
            return;
        }
        $this->resetValidation();
        $this->editing_event_id = null;
        $this->reset(['name', 'brand', 'event_type', 'event_date', 'setup_date', 'start_time', 'ceremony_time', 'cocktail_time', 'banquet_time', 'dance_start_time', 'dance_duration_hours', 'max_end_time', 'location', 'client_id', 'partner_name', 'partner_phone', 'partner_email', 'partner_dni', 'dj_id', 'assistant_id', 'assistant_ids', 'notes', 'new_client_name', 'new_client_phone']);
        $this->brand = \App\Models\Setting::getDetectedBrand();
        $this->event_type = 'boda';
        $this->agreed_price = 250;
        $this->initial_status = 'confirmed';
        $this->dance_duration_hours = 4.0;
        $this->dj_id = auth()->id(); // default current logged admin/dj
        $this->assistant_ids = [];
        $this->loadStaff();
        $this->showCreateModal = true;
    }

    public function updatedBrand($value)
    {
        if ($this->event_type === 'video' && !\App\Models\Setting::isVideoServiceEnabled($value)) {
            $this->event_type = 'boda';
        }
    }

    public function openEditModal($eventId)
    {
        if (!in_array(auth()->user()->role, ['admin', 'dj'])) {
            session()->flash('error', 'Solo los administradores y DJs autorizados pueden editar eventos.');
            return;
        }
        $this->resetValidation();
        $event = Event::findOrFail($eventId);
        $this->editing_event_id = $event->id;
        $this->name = $event->name;
        $this->brand = $event->brand ?: 'nunez_and_son';
        $this->event_type = $event->event_type ?: 'boda';
        $this->event_date = $event->event_date ? $event->event_date->format('Y-m-d') : null;
        $this->setup_date = $event->setup_date ? \Carbon\Carbon::parse($event->setup_date)->format('Y-m-d') : null;
        $this->start_time = $event->start_time;
        $this->ceremony_time = $event->ceremony_time;
        $this->cocktail_time = $event->cocktail_time;
        $this->banquet_time = $event->banquet_time;
        $this->dance_start_time = $event->dance_start_time;
        $this->dance_duration_hours = $event->dance_duration_hours ?: 4.0;
        $this->max_end_time = $event->max_end_time;
        $this->location = $event->location;
        $this->client_id = $event->client_id;
        $this->partner_name = $event->partner_name;
        $this->partner_phone = $event->partner_phone;
        $this->partner_email = $event->partner_email;
        $this->partner_dni = $event->partner_dni;
        if (!empty($this->partner_dni)) {
            $this->validateAndCompleteDni('partner_dni', $this->partner_dni);
        } else {
            $this->dniValidations['partner_dni'] = null;
        }
        $this->dj_id = $event->dj_id;
        $this->assistant_id = $event->assistant_id;
        $this->assistant_ids = $event->all_assistants->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->notes = $event->notes;
        $this->loadStaff();
        $this->showCreateModal = true;
    }

    public function updatedPartnerDni($value)
    {
        $this->validateAndCompleteDni('partner_dni', $value);
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->editing_event_id = null;
    }

    public function saveEvent()
    {
        $this->validate();

        try {
            // Si las columnas o tablas aún no existen, intentamos aplicar migraciones automáticamente
            if (!\Illuminate\Support\Facades\Schema::hasColumn('events', 'partner_name') || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'setup_date') || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'dance_start_time') || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'max_end_time') || !\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                try {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                } catch (\Throwable $migEx) {
                    // Ignorar si no se puede ejecutar artisan en este contexto
                }
            }

            // Si se introdujo un nuevo contacto directo y no se seleccionó cliente previo
            if (empty($this->client_id) && !empty(trim((string)$this->new_client_name))) {
                $cleanName = trim((string)$this->new_client_name);
                $cleanPhone = trim((string)$this->new_client_phone);
                $existingClient = null;
                if (!empty($cleanPhone)) {
                    $existingClient = User::where('role', 'client')->where('phone', $cleanPhone)->first();
                }
                if (!$existingClient) {
                    $existingClient = User::where('role', 'client')->where('name', $cleanName)->first();
                }

                if (!$existingClient) {
                    $slug = \Illuminate\Support\Str::slug($cleanName, '_');
                    $email = 'contacto_' . ($slug ?: 'colaborador') . '_' . time() . '@colaborador.local';
                    $existingClient = User::create([
                        'name' => $cleanName,
                        'email' => $email,
                        'phone' => $cleanPhone ?: null,
                        'role' => 'client',
                        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                    ]);
                }
                $this->client_id = $existingClient->id;
            }

            $firstAssistantId = !empty($this->assistant_ids) ? (int)$this->assistant_ids[0] : ($this->assistant_id ?: null);

            $eventData = [
                'name' => $this->name,
                'brand' => $this->brand ?: 'nunez_and_son',
                'event_type' => $this->event_type ?: 'boda',
                'event_date' => $this->event_date,
                'location' => $this->location,
                'client_id' => $this->client_id ?: null,
                'partner_name' => $this->partner_name ?: null,
                'partner_phone' => $this->partner_phone ?: null,
                'partner_email' => $this->partner_email ?: null,
                'partner_dni' => $this->partner_dni ?: null,
                'dj_id' => ($this->event_type === 'video') ? auth()->id() : ($this->dj_id ?: auth()->id()),
                'assistant_id' => ($this->event_type === 'video') ? null : $firstAssistantId,
                'notes' => $this->notes,
            ];

            $scheduleFields = [
                'setup_date' => $this->setup_date ?: null,
                'start_time' => $this->start_time ?: null,
                'ceremony_time' => $this->ceremony_time ?: null,
                'cocktail_time' => $this->cocktail_time ?: null,
                'banquet_time' => $this->banquet_time ?: null,
                'dance_start_time' => $this->dance_start_time ?: null,
                'dance_duration_hours' => $this->dance_duration_hours ? (float)$this->dance_duration_hours : null,
                'max_end_time' => $this->max_end_time ?: null,
            ];

            foreach ($scheduleFields as $col => $val) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('events', $col)) {
                    $eventData[$col] = $val;
                }
            }

            if ($this->editing_event_id) {
                $event = Event::findOrFail($this->editing_event_id);
                $event->update($eventData);
                if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                    $event->assistants()->sync($this->assistant_ids ?: []);
                }
                $msg = '¡Evento "' . $this->name . '" actualizado exitosamente!';
            } else {
                $eventStatus = ($this->event_type === 'video') ? ($this->initial_status ?: 'confirmed') : 'draft';
                $eventData['status'] = $eventStatus;
                $event = Event::create($eventData);
                if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                    $event->assistants()->sync(($this->event_type === 'video') ? [] : ($this->assistant_ids ?: []));
                }

                // Generar automáticamente presupuesto de videógrafo si es tipo video y tiene precio acordado
                if ($this->event_type === 'video' && (float)$this->agreed_price > 0) {
                    $quote = Quote::create([
                        'event_id' => $event->id,
                        'brand' => $event->brand ?: 'nunez_and_son',
                        'amount' => (float)$this->agreed_price,
                        'status' => 'accepted',
                        'tax_type' => 'included',
                        'tax_rate' => 21.00,
                    ]);

                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'service_name' => 'Servicio de Videógrafo / Cobertura de Vídeo',
                        'description' => 'Servicio audiovisual de grabación/vídeo para evento/boda acordado con colaborador.',
                        'price' => (float)$this->agreed_price,
                        'quantity' => 1,
                    ]);
                }

                $msg = ($this->event_type === 'video')
                    ? '¡Evento de Videógrafo registrado y fecha bloqueada (' . number_format((float)$this->agreed_price, 2, ',', '.') . ' €) exitosamente!'
                    : '¡Evento creado exitosamente con personal y equipo asignados!';
            }

            $this->closeCreateModal();
            $this->loadEvents();
            
            session()->flash('message', $msg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error saving event: ' . $e->getMessage());
            session()->flash('error', 'Error al guardar el evento: ' . $e->getMessage());
        }
    }

    public function deleteEvent($eventId)
    {
        if (auth()->user()->role !== 'admin') {
            session()->flash('error', 'Solo los administradores pueden eliminar eventos.');
            return;
        }

        $event = Event::with('client')->findOrFail($eventId);
        $name = $event->name;

        // Registrar huella del evento eliminado para que el sincronizador IMAP jamás vuelva a importarlo
        try {
            \App\Services\ImapLeadFetcherService::markEventAsDeleted($event);
        } catch (\Throwable $e) {}

        // Cascade delete relations
        foreach ($event->quotes as $quote) {
            $quote->items()->delete();
            $quote->delete();
        }
        $event->invoices()->delete();
        $event->contracts()->delete();
        $event->dossiers()->delete();
        $event->playlists()->delete();
        $event->musicRequests()->delete();
        $event->equipment()->detach();
        $event->delete();

        $this->loadEvents();
        session()->flash('message', "🗑️ El evento '{$name}' ha sido eliminado permanentemente (y bloqueado para no reimportarse por email).");
    }

    public $confirmingFollowupEventId = null;
    public $confirmingFollowupEventName = '';
    public $confirmingFollowupClientName = '';
    public $confirmingFollowupDays = 0;
    public $confirmingFollowupSelectedKey = 'dia3_dudas';
    public $confirmingFollowupOptions = [];
    public $confirmingFollowupUrl = '';
    public $confirmingFollowupText = '';

    public function promptFollowup(int $eventId, ?string $templateKey = null)
    {
        $event = Event::with('client')->find($eventId);
        if (!$event) return;

        $this->confirmingFollowupEventId = $event->id;
        $this->confirmingFollowupEventName = $event->name;
        $this->confirmingFollowupClientName = $event->client ? $event->client->name : 'Cliente';
        $this->confirmingFollowupDays = $event->days_without_response;

        $this->confirmingFollowupOptions = \App\Services\WhatsAppTemplateService::getFollowupOptions($event);
        $key = $templateKey ?: \App\Services\WhatsAppTemplateService::getRecommendedFollowupKey($event);
        $this->selectFollowupTemplate($key);
    }

    public function selectFollowupTemplate(string $key)
    {
        $this->confirmingFollowupSelectedKey = $key;
        if (isset($this->confirmingFollowupOptions[$key])) {
            $this->confirmingFollowupUrl = $this->confirmingFollowupOptions[$key]['url'];
            $this->confirmingFollowupText = $this->confirmingFollowupOptions[$key]['text'];
        }
    }

    public function confirmFollowupSent()
    {
        if (!$this->confirmingFollowupEventId) return;

        $event = Event::find($this->confirmingFollowupEventId);
        if ($event) {
            $templateName = $this->confirmingFollowupOptions[$this->confirmingFollowupSelectedKey]['title'] ?? 'Seguimiento Madrigal';
            $event->update([
                'no_response_at' => now(),
                'notes' => ($event->notes ? $event->notes . "\n" : "") . "[" . now()->format('d/m/Y H:i') . "] 💬 WhatsApp enviado por " . auth()->user()->name . " ({$templateName}). Contador reseteado a 0.",
            ]);
            session()->flash('message', "¡Seguimiento registrado para {$event->name}! ({$templateName}). Contador reseteado.");
        }

        $this->confirmingFollowupEventId = null;
        $this->loadEvents();
    }

    public function markAsResponded(string $newStatus = 'draft')
    {
        if (!$this->confirmingFollowupEventId) return;

        $event = Event::find($this->confirmingFollowupEventId);
        if ($event) {
            $event->update([
                'status' => in_array($newStatus, ['draft', 'confirmed']) ? $newStatus : 'draft',
                'no_response_at' => null,
                'notes' => ($event->notes ? $event->notes . "\n" : "") . "[" . now()->format('d/m/Y H:i') . "] 🎉 El cliente ha respondido al seguimiento. Estado cambiado a " . ucfirst($newStatus) . " por " . auth()->user()->name . ".",
            ]);
            session()->flash('message', "¡Genial! {$event->name} se ha marcado como " . ($newStatus === 'confirmed' ? 'Confirmado' : 'Borrador') . ".");
        }

        $this->confirmingFollowupEventId = null;
        $this->loadEvents();
    }

    public function cancelFollowup()
    {
        $this->confirmingFollowupEventId = null;
    }

    public function render()
    {
        $user = auth()->user();
        $relations = ['client', 'dj', 'assistant'];
        if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
            $relations[] = 'assistants';
        }

        $query = Event::with($relations);

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('dj_id', $user->id)
                  ->orWhere('assistant_id', $user->id);
                if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                    $q->orWhereHas('assistants', fn($sq) => $sq->where('users.id', $user->id));
                }
            });
        }

        $allEvents = $query->orderBy('event_date', 'asc')->get();

        $statusCounts = [
            'all' => $allEvents->count(),
            'no_response' => $allEvents->where('status', 'no_response')->count(),
            'draft' => $allEvents->where('status', 'draft')->count(),
            'confirmed' => $allEvents->where('status', 'confirmed')->count(),
            'completed' => $allEvents->where('status', 'completed')->count(),
            'rejected' => $allEvents->where('status', 'rejected')->count(),
        ];

        $urgentNoResponseCount = $allEvents->where('status', 'no_response')->filter(function($ev) {
            return $ev->days_without_response >= 2;
        })->count();

        $filteredEvents = $allEvents;
        if ($this->status_filter !== 'all') {
            $filteredEvents = $allEvents->where('status', $this->status_filter);
        }

        return view('livewire.admin.event-manager', [
            'events' => $filteredEvents,
            'statusCounts' => $statusCounts,
            'urgentNoResponseCount' => $urgentNoResponseCount,
        ])->layout('components.layouts.app', ['header' => 'Gestión de Eventos']);
    }
}
