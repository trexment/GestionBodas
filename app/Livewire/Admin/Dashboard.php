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
        // Métricas financieras
        $this->totalBilled = Invoice::sum('total');
        $this->totalPending = Invoice::where('status', 'unpaid')->sum('total');
        
        // Usuarios y Personal
        $this->activeClientsCount = User::where('role', 'client')->count();
        $this->djsCount = User::where('role', 'dj')->count();
        $this->assistantsCount = User::where('role', 'assistant')->count();
        $this->totalEventsCount = Event::count();
        $this->equipmentCount = Equipment::sum('quantity') ?: Equipment::count();
        
        // Eventos a 30 días o total
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        $this->upcomingEventsCount = Event::where('event_date', '>=', Carbon::today()->subDays(30))
                                          ->count();

        // Cuestionarios pendientes de rellenar
        $this->pendingDossiersCount = Event::where('is_dossier_completed', false)->count();

        // Listas rápidas (prioriza próximos, si no hay muestra los más recientes)
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
