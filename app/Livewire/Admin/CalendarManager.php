<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;

class CalendarManager extends Component
{
    public $year;
    public $month;
    public $monthName;

    // Filtros
    public $filter_status = '';
    public $filter_dj = '';

    // Modal Crear Evento Rápido
    public $showCreateModal = false;
    public $new_name = '';
    public $new_event_date = '';
    public $new_location = '';
    public $new_client_id = '';
    public $new_dj_id = '';
    public $new_assistant_id = '';
    public $new_status = 'draft';

    public function mount()
    {
        $today = Carbon::today();
        $this->year = $today->year;
        $this->month = $today->month;
    }

    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function goToToday()
    {
        $today = Carbon::today();
        $this->year = $today->year;
        $this->month = $today->month;
    }

    public function openCreateModal($dateStr = null)
    {
        $this->resetCreateForm();
        if ($dateStr) {
            $this->new_event_date = $dateStr;
        } else {
            $this->new_event_date = Carbon::createFromDate($this->year, $this->month, 1)->format('Y-m-d');
        }
        $this->showCreateModal = true;
    }

    public function createEvent()
    {
        $this->validate([
            'new_name' => 'required|string|max:255',
            'new_event_date' => 'required|date',
            'new_location' => 'required|string|max:255',
            'new_client_id' => 'nullable|exists:users,id',
            'new_dj_id' => 'nullable|exists:users,id',
            'new_assistant_id' => 'nullable|exists:users,id',
            'new_status' => 'required|in:draft,confirmed,completed,cancelled',
        ]);

        $event = Event::create([
            'name' => $this->new_name,
            'event_date' => $this->new_event_date,
            'location' => $this->new_location,
            'client_id' => $this->new_client_id ?: null,
            'dj_id' => $this->new_dj_id ?: null,
            'assistant_id' => $this->new_assistant_id ?: null,
            'status' => $this->new_status,
        ]);

        $this->showCreateModal = false;
        $this->resetCreateForm();
        session()->flash('message', 'Evento creado exitosamente en el calendario.');
    }

    public function resetCreateForm()
    {
        $this->new_name = '';
        $this->new_event_date = '';
        $this->new_location = '';
        $this->new_client_id = '';
        $this->new_dj_id = '';
        $this->new_assistant_id = '';
        $this->new_status = 'draft';
    }

    public function render()
    {
        $firstDayOfMonth = Carbon::createFromDate($this->year, $this->month, 1);
        $this->monthName = ucfirst($firstDayOfMonth->locale('es')->monthName) . ' ' . $this->year;

        // Rango de fechas visibles (incluyendo días antes del lunes y después del domingo)
        $startCalendar = $firstDayOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endCalendar = $firstDayOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        // Consultar eventos del rango
        $query = Event::with(['client', 'dj', 'assistant'])
            ->whereBetween('event_date', [$startCalendar->format('Y-m-d'), $endCalendar->format('Y-m-d')]);

        if ($this->filter_status) {
            $query->where('status', $this->filter_status);
        }

        if ($this->filter_dj) {
            $query->where(function ($q) {
                $q->where('dj_id', $this->filter_dj)
                  ->orWhere('assistant_id', $this->filter_dj);
            });
        }

        $events = $query->get()->groupBy(function ($event) {
            return $event->event_date->format('Y-m-d');
        });

        // Construir semanas
        $weeks = [];
        $currentDate = $startCalendar->copy();

        while ($currentDate <= $endCalendar) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateStr = $currentDate->format('Y-m-d');
                $week[] = [
                    'date' => $dateStr,
                    'day' => $currentDate->day,
                    'isCurrentMonth' => $currentDate->month === (int)$this->month,
                    'isToday' => $currentDate->isToday(),
                    'isWeekend' => $currentDate->isWeekend(),
                    'events' => $events->get($dateStr, collect()),
                ];
                $currentDate->addDay();
            }
            $weeks[] = $week;
        }

        $clients = User::where('role', 'client')->orderBy('name')->get();
        $djs = User::whereIn('role', ['dj', 'admin'])->orderBy('name')->get();
        $assistants = User::whereIn('role', ['assistant', 'dj', 'admin'])->orderBy('name')->get();

        return view('livewire.admin.calendar-manager', [
            'weeks' => $weeks,
            'clients' => $clients,
            'djs' => $djs,
            'assistants' => $assistants,
        ])->layout('components.layouts.app', ['header' => 'Calendario de Eventos']);
    }
}
