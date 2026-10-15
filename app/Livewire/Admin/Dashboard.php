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
    public $noResponseCount = 0;
    public $urgentNoResponseCount = 0;
    
    public $upcomingEvents = [];
    public $recentInvoices = [];
    public $noResponseEvents = [];

    public function mount()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';

        // Eventos Sin Respuesta (seguimiento)
        $noResponseQuery = Event::with('client')->where('status', 'no_response');
        if (!$isAdmin) {
            $noResponseQuery->where(function ($q) use ($user) {
                $q->where('dj_id', $user->id)->orWhere('assistant_id', $user->id);
            });
        }
        $allNoResponse = $noResponseQuery->orderBy('updated_at', 'asc')->get();
        $this->noResponseCount = $allNoResponse->count();
        $this->urgentNoResponseCount = $allNoResponse->filter(fn($ev) => $ev->days_without_response >= 2)->count();
        $this->noResponseEvents = $allNoResponse->take(4);

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
    
    public $confirmingFollowupEventId = null;
    public $confirmingFollowupEventName = '';
    public $confirmingFollowupClientName = '';

    public function promptFollowup(int $eventId)
    {
        $event = Event::with('client')->find($eventId);
        if (!$event) return;

        $this->confirmingFollowupEventId = $event->id;
        $this->confirmingFollowupEventName = $event->name;
        $this->confirmingFollowupClientName = $event->client ? $event->client->name : 'Cliente';
    }

    public function confirmFollowupSent()
    {
        if (!$this->confirmingFollowupEventId) return;

        $event = Event::find($this->confirmingFollowupEventId);
        if ($event) {
            $event->update([
                'no_response_at' => now(),
                'notes' => ($event->notes ? $event->notes . "\n" : "") . "[" . now()->format('d/m/Y H:i') . "] 💬 Seguimiento de WhatsApp enviado por " . auth()->user()->name . ". Contador de días reseteado.",
            ]);
            session()->flash('message', "¡Seguimiento registrado para {$event->name}! El contador de días se ha reseteado.");
        }

        $this->confirmingFollowupEventId = null;
        $this->mount();
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
        $this->mount();
    }

    public function cancelFollowup()
    {
        $this->confirmingFollowupEventId = null;
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
