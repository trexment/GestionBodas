<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Event;
use App\Models\User;

class EventManager extends Component
{
    public $events;
    
    // Properties for creating a new event
    public $name;
    public $brand = 'nunez_and_son'; // nunez_and_son, javnx, mago_leugim
    public $event_type = 'boda'; // boda, empresa, cumpleanos, comunion, otro
    public $event_date;
    public $setup_date;
    public $start_time;
    public $dance_start_time;
    public $dance_duration_hours = 4.0;
    public $max_end_time;
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

    protected $rules = [
        'name' => 'required|string|max:255',
        'brand' => 'nullable|string|in:nunez_and_son,javnx,mago_leugim',
        'event_type' => 'required|string|in:boda,empresa,cumpleanos,comunion,otro',
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
        $this->loadEvents();
        $this->loadStaff();
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
        $this->reset(['name', 'brand', 'event_type', 'event_date', 'setup_date', 'start_time', 'ceremony_time', 'cocktail_time', 'banquet_time', 'dance_start_time', 'dance_duration_hours', 'max_end_time', 'location', 'client_id', 'partner_name', 'partner_phone', 'partner_email', 'partner_dni', 'dj_id', 'assistant_id', 'assistant_ids', 'notes']);
        $this->brand = \App\Models\Setting::getDetectedBrand();
        $this->event_type = 'boda';
        $this->dance_duration_hours = 4.0;
        $this->dj_id = auth()->id(); // default current logged admin/dj
        $this->assistant_ids = [];
        $this->loadStaff();
        $this->showCreateModal = true;
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
        $this->dj_id = $event->dj_id;
        $this->assistant_id = $event->assistant_id;
        $this->assistant_ids = $event->all_assistants->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->notes = $event->notes;
        $this->loadStaff();
        $this->showCreateModal = true;
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
                'dj_id' => $this->dj_id ?: auth()->id(),
                'assistant_id' => $firstAssistantId,
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
                $eventData['status'] = 'draft';
                $event = Event::create($eventData);
                if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                    $event->assistants()->sync($this->assistant_ids ?: []);
                }
                $msg = '¡Evento creado exitosamente con personal y equipo asignados!';
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

        $event = Event::findOrFail($eventId);
        $name = $event->name;

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
        session()->flash('message', "🗑️ El evento '{$name}' ha sido eliminado permanentemente.");
    }

    public function render()
    {
        $user = auth()->user();
        $query = Event::with(['client', 'dj', 'assistant']);

        if ($user->role === 'admin') {
            $events = $query->orderBy('event_date', 'asc')->get();
        } else {
            $events = $query->where(function ($q) use ($user) {
                $q->where('dj_id', $user->id)
                  ->orWhere('assistant_id', $user->id);
            })->orderBy('event_date', 'asc')->get();
        }

        return view('livewire.admin.event-manager', [
            'events' => $events,
        ])->layout('components.layouts.app', ['header' => 'Gestión de Eventos']);
    }
}
