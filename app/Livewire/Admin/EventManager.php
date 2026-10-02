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
    public $location;
    public $client_id;
    public $dj_id;
    public $assistant_id;
    public $notes;
    
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
        'dance_start_time' => 'nullable|string|max:20',
        'dance_duration_hours' => 'nullable|numeric|min:0.5|max:24',
        'location' => 'required|string|max:255',
        'client_id' => 'nullable|exists:users,id',
        'dj_id' => 'nullable|exists:users,id',
        'assistant_id' => 'nullable|exists:users,id',
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
        if ($user->role === 'admin') {
            $this->events = Event::with(['client', 'dj', 'assistant'])->orderBy('event_date', 'asc')->get();
        } else {
            $this->events = Event::with(['client', 'dj', 'assistant'])
                ->where(function ($q) use ($user) {
                    $q->where('dj_id', $user->id)
                      ->orWhere('assistant_id', $user->id);
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
        $this->reset(['name', 'brand', 'event_type', 'event_date', 'setup_date', 'start_time', 'dance_start_time', 'dance_duration_hours', 'location', 'client_id', 'dj_id', 'assistant_id', 'notes']);
        $this->brand = \App\Models\Setting::getDetectedBrand();
        $this->event_type = 'boda';
        $this->dance_duration_hours = 4.0;
        $this->dj_id = auth()->id(); // default current logged admin/dj
        $this->loadStaff();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
    }

    public function saveEvent()
    {
        $this->validate();

        try {
            // Si las columnas aún no existen, intentamos aplicar migraciones automáticamente
            if (!\Illuminate\Support\Facades\Schema::hasColumn('events', 'setup_date') || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'dance_start_time')) {
                try {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                } catch (\Throwable $migEx) {
                    // Ignorar si no se puede ejecutar artisan en este contexto
                }
            }

            $createData = [
                'name' => $this->name,
                'brand' => $this->brand ?: 'nunez_and_son',
                'event_type' => $this->event_type ?: 'boda',
                'event_date' => $this->event_date,
                'location' => $this->location,
                'client_id' => $this->client_id ?: null,
                'dj_id' => $this->dj_id ?: auth()->id(),
                'assistant_id' => $this->assistant_id ?: null,
                'status' => 'draft',
                'notes' => $this->notes,
            ];

            $scheduleFields = [
                'setup_date' => $this->setup_date ?: null,
                'start_time' => $this->start_time ?: null,
                'dance_start_time' => $this->dance_start_time ?: null,
                'dance_duration_hours' => $this->dance_duration_hours ? (float)$this->dance_duration_hours : null,
            ];

            foreach ($scheduleFields as $col => $val) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('events', $col)) {
                    $createData[$col] = $val;
                }
            }

            Event::create($createData);

            $this->closeCreateModal();
            $this->loadEvents();
            
            session()->flash('message', 'Evento creado exitosamente con DJ y Asistente asignados.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error creating event: ' . $e->getMessage());
            session()->flash('error', 'Error al crear el evento: ' . $e->getMessage() . '. Si faltan columnas, ejecuta "php artisan migrate".');
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
