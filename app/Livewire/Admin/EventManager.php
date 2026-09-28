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
    public $event_date;
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
        'event_date' => 'required|date',
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
        if (auth()->user()->role !== 'admin') {
            session()->flash('error', 'Solo los administradores pueden crear nuevos eventos.');
            return;
        }
        $this->resetValidation();
        $this->reset(['name', 'event_date', 'location', 'client_id', 'dj_id', 'assistant_id', 'notes']);
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

        Event::create([
            'name' => $this->name,
            'event_date' => $this->event_date,
            'location' => $this->location,
            'client_id' => $this->client_id ?: null,
            'dj_id' => $this->dj_id ?: auth()->id(),
            'assistant_id' => $this->assistant_id ?: null,
            'status' => 'draft',
            'notes' => $this->notes,
        ]);

        $this->closeCreateModal();
        $this->loadEvents();
        
        session()->flash('message', 'Evento creado exitosamente con DJ y Asistente asignados.');
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
