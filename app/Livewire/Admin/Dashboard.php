<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Equipment;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $totalBilled = 0;
    public $totalPending = 0;
    public $upcomingEventsCount = 0;
    public $totalEventsCount = 0;
    public $activeClientsCount = 0;
    public $djsCount = 0;
    public $assistantsCount = 0;
    public $equipmentCount = 0;
    public $pendingDossiersCount = 0;
    
    public $upcomingEvents = [];
    public $recentInvoices = [];

    public function mount()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';

        if ($isAdmin) {
            // Métricas financieras globales
            $this->totalBilled = Invoice::sum('total');
            $this->totalPending = Invoice::where('status', 'unpaid')->sum('total');
            
            // Usuarios y Personal
            $this->activeClientsCount = User::where('role', 'client')->count();
            $this->djsCount = User::where('role', 'dj')->count();
            $this->assistantsCount = User::where('role', 'assistant')->count();
            $this->totalEventsCount = Event::count();
            $this->equipmentCount = Equipment::sum('quantity') ?: Equipment::count();
            
            // Eventos próximos
            $this->upcomingEventsCount = Event::where('event_date', '>=', Carbon::today()->subDays(30))->count();
            $this->pendingDossiersCount = Event::where('is_dossier_completed', false)->count();

            $futureEvents = Event::with(['client', 'dj', 'assistant'])
                                 ->where('event_date', '>=', Carbon::today())
                                 ->orderBy('event_date', 'asc')
                                 ->take(6)
                                 ->get();

            if ($futureEvents->isNotEmpty()) {
                $this->upcomingEvents = $futureEvents;
            } else {
                $this->upcomingEvents = Event::with(['client', 'dj', 'assistant'])
                                             ->orderBy('event_date', 'desc')
                                             ->take(6)
                                             ->get();
            }
                                        
            $this->recentInvoices = Invoice::with(['event.client'])
                                        ->orderBy('issue_date', 'desc')
                                        ->take(6)
                                        ->get();
        } else {
            // Métricas y eventos para el personal asignado (DJ / Asistente)
            $this->totalBilled = 0;
            $this->totalPending = 0;
            $this->activeClientsCount = 0;
            $this->djsCount = 0;
            $this->assistantsCount = 0;
            $this->equipmentCount = Equipment::sum('quantity') ?: Equipment::count();

            $assignedEventsQuery = Event::where(function ($q) use ($user) {
                $q->where('dj_id', $user->id)
                  ->orWhere('assistant_id', $user->id);
            });

            $this->totalEventsCount = (clone $assignedEventsQuery)->count();
            $this->upcomingEventsCount = (clone $assignedEventsQuery)->where('event_date', '>=', Carbon::today())->count();
            $this->pendingDossiersCount = (clone $assignedEventsQuery)->where('is_dossier_completed', false)->count();

            $futureEvents = (clone $assignedEventsQuery)
                ->with(['client', 'dj', 'assistant'])
                ->where('event_date', '>=', Carbon::today())
                ->orderBy('event_date', 'asc')
                ->take(6)
                ->get();

            if ($futureEvents->isNotEmpty()) {
                $this->upcomingEvents = $futureEvents;
            } else {
                $this->upcomingEvents = (clone $assignedEventsQuery)
                    ->with(['client', 'dj', 'assistant'])
                    ->orderBy('event_date', 'desc')
                    ->take(6)
                    ->get();
            }

            $this->recentInvoices = collect();
        }
    }
    
    public function markInvoiceAsPaid($id)
    {
        $invoice = Invoice::find($id);
        if ($invoice) {
            $invoice->update(['status' => 'paid']);
            $this->mount();
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard')->layout('components.layouts.app', ['header' => 'Panel de Control']);
    }
}
