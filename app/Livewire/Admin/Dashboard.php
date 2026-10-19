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

    public $funnelMetrics = [
        'total_leads' => 0,
        'draft_count' => 0,
        'no_response_count' => 0,
        'confirmed_count' => 0,
        'conversion_rate' => 0,
        'this_month_leads' => 0,
        'this_month_confirmed' => 0,
    ];

    public $season2027 = [
        'confirmed_count' => 0,
        'pipeline_count' => 0,
        'target_events' => 25,
        'occupancy_pct' => 0,
        'confirmed_revenue' => 0,
        'pipeline_revenue' => 0,
    ];

    public $season2028 = [
        'confirmed_count' => 0,
        'pipeline_count' => 0,
        'target_events' => 25,
        'occupancy_pct' => 0,
        'confirmed_revenue' => 0,
        'pipeline_revenue' => 0,
    ];

    public function mount()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';

        if ($isAdmin) {
            (new \App\Services\ImapLeadFetcherService())->autoSyncIfDue(120);
        }

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

            // Embudo de Ventas & Conversión (Metodología Madrigal)
            $allEvents = Event::with('quotes')->get();
            $totalLeads = $allEvents->count();
            $draftCount = $allEvents->where('status', 'draft')->count();
            $noRespCount = $allEvents->where('status', 'no_response')->count();
            $confirmedCount = $allEvents->where('status', 'confirmed')->count();
            $convRate = $totalLeads > 0 ? round(($confirmedCount / $totalLeads) * 100, 1) : 0;

            $startOfMonth = Carbon::now()->startOfMonth();
            $thisMonthLeads = Event::where('created_at', '>=', $startOfMonth)->count();
            $thisMonthConfirmed = Event::where('status', 'confirmed')
                ->where(function($q) use ($startOfMonth) {
                    $q->where('updated_at', '>=', $startOfMonth)
                      ->orWhere('created_at', '>=', $startOfMonth);
                })->count();

            $this->funnelMetrics = [
                'total_leads' => $totalLeads,
                'draft_count' => $draftCount,
                'no_response_count' => $noRespCount,
                'confirmed_count' => $confirmedCount,
                'conversion_rate' => $convRate,
                'this_month_leads' => $thisMonthLeads,
                'this_month_confirmed' => $thisMonthConfirmed,
            ];

            // Temporada 2027
            $events2027 = $allEvents->filter(fn($ev) => $ev->event_date && Carbon::parse($ev->event_date)->year === 2027);
            $confirmed2027 = $events2027->where('status', 'confirmed');
            $pipeline2027 = $events2027->whereIn('status', ['draft', 'no_response']);
            $revConf2027 = $confirmed2027->sum(function($ev) {
                $q = $ev->quotes->last();
                return $q ? (float)$q->amount : 0;
            });
            $revPipe2027 = $pipeline2027->sum(function($ev) {
                $q = $ev->quotes->last();
                return $q ? (float)$q->amount : 0;
            });
            $target2027 = (int) \App\Models\Setting::get('season_target_2027', 25);
            $this->season2027 = [
                'confirmed_count' => $confirmed2027->count(),
                'pipeline_count' => $pipeline2027->count(),
                'target_events' => $target2027,
                'occupancy_pct' => min(100, (int) round(($confirmed2027->count() / max(1, $target2027)) * 100)),
                'confirmed_revenue' => $revConf2027,
                'pipeline_revenue' => $revPipe2027,
            ];

            // Temporada 2028
            $events2028 = $allEvents->filter(fn($ev) => $ev->event_date && Carbon::parse($ev->event_date)->year === 2028);
            $confirmed2028 = $events2028->where('status', 'confirmed');
            $pipeline2028 = $events2028->whereIn('status', ['draft', 'no_response']);
            $revConf2028 = $confirmed2028->sum(function($ev) {
                $q = $ev->quotes->last();
                return $q ? (float)$q->amount : 0;
            });
            $revPipe2028 = $pipeline2028->sum(function($ev) {
                $q = $ev->quotes->last();
                return $q ? (float)$q->amount : 0;
            });
            $target2028 = (int) \App\Models\Setting::get('season_target_2028', 25);
            $this->season2028 = [
                'confirmed_count' => $confirmed2028->count(),
                'pipeline_count' => $pipeline2028->count(),
                'target_events' => $target2028,
                'occupancy_pct' => min(100, (int) round(($confirmed2028->count() / max(1, $target2028)) * 100)),
                'confirmed_revenue' => $revConf2028,
                'pipeline_revenue' => $revPipe2028,
            ];

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
    public $confirmingFollowupDays = 0;
    public $confirmingFollowupSelectedKey = 'dia3_dudas';
    public $confirmingFollowupOptions = [];
    public $confirmingFollowupUrl = '';
    public $confirmingFollowupText = '';
    public $confirmingFollowupEmail = '';
    public $confirmingFollowupSubject = '';
    public $confirmingFollowupMailtoUrl = '';
    public $confirmingFollowupHasPhone = false;

    public function promptFollowup(int $eventId, ?string $templateKey = null)
    {
        $event = Event::with('client')->find($eventId);
        if (!$event) return;

        $this->confirmingFollowupEventId = $event->id;
        $this->confirmingFollowupEventName = $event->name;
        $this->confirmingFollowupClientName = $event->client ? $event->client->name : ($event->partner_name ?: 'Cliente');
        $this->confirmingFollowupDays = $event->days_without_response;
        $this->confirmingFollowupEmail = $event->client_contact_email ?: '';
        $this->confirmingFollowupHasPhone = !empty($event->client_contact_phone);

        $this->confirmingFollowupOptions = \App\Services\WhatsAppTemplateService::getFollowupOptions($event);
        $key = $templateKey ?: \App\Services\WhatsAppTemplateService::getRecommendedFollowupKey($event);
        $this->selectFollowupTemplate($key);
    }

    public function selectFollowupTemplate(string $key)
    {
        $this->confirmingFollowupSelectedKey = $key;
        if (isset($this->confirmingFollowupOptions[$key])) {
            $this->confirmingFollowupUrl = $this->confirmingFollowupOptions[$key]['url'] ?? '';
            $this->confirmingFollowupText = $this->confirmingFollowupOptions[$key]['text'] ?? '';
            $this->confirmingFollowupSubject = $this->confirmingFollowupOptions[$key]['subject'] ?? ('Seguimiento · ' . $this->confirmingFollowupEventName);
            $this->confirmingFollowupMailtoUrl = $this->confirmingFollowupOptions[$key]['mailto_url'] ?? '';
        }
    }

    public function sendDirectFollowupEmail()
    {
        if (!$this->confirmingFollowupEventId) return;

        $event = Event::with('client')->find($this->confirmingFollowupEventId);
        if (!$event) return;

        $toEmail = trim((string) ($this->confirmingFollowupEmail ?: $event->client_contact_email));
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            session()->flash('error', 'Introduce un email válido del cliente para enviar el seguimiento por correo.');
            return;
        }

        $templateName = $this->confirmingFollowupOptions[$this->confirmingFollowupSelectedKey]['title'] ?? 'Seguimiento Madrigal';
        $subject = trim((string) $this->confirmingFollowupSubject) ?: \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($event, $this->confirmingFollowupSelectedKey);

        $res = \App\Services\WhatsAppTemplateService::sendAndLogClientEmail(
            $event,
            $toEmail,
            $subject,
            (string) $this->confirmingFollowupText,
            $this->confirmingFollowupSelectedKey,
            $templateName
        );

        $this->confirmingFollowupEventId = null;
        $this->mount();

        if ($res['success']) {
            session()->flash('message', "📧 ¡Seguimiento enviado por Email a {$toEmail} para {$event->name}! Registrado en la conversación del evento y contador a 0.");
        } else {
            $this->dispatch('open-whatsapp-link', url: $res['mailto_url']);
            session()->flash('message', "📧 Seguimiento registrado en el evento ({$templateName}) y abierto en tu gestor de correo para {$toEmail}.");
        }
    }

    public function confirmFollowupSent()
    {
        if (!$this->confirmingFollowupEventId) return;

        $event = Event::find($this->confirmingFollowupEventId);
        if ($event) {
            $templateName = $this->confirmingFollowupOptions[$this->confirmingFollowupSelectedKey]['title'] ?? 'Seguimiento Madrigal';
            $newEntry = "[" . now()->format('d/m/Y H:i') . "] 💬 Seguimiento enviado por " . auth()->user()->name . " ({$templateName}). Contador reseteado a 0.";
            $event->update([
                'no_response_at' => now(),
                'notes' => Event::prependNote($event->notes, $newEntry),
            ]);
            session()->flash('message', "¡Seguimiento registrado para {$event->name}! ({$templateName}). Contador reseteado.");
        }

        $this->confirmingFollowupEventId = null;
        $this->mount();
    }

    public function markAsResponded(string $newStatus = 'draft')
    {
        if (!$this->confirmingFollowupEventId) return;

        $event = Event::find($this->confirmingFollowupEventId);
        if ($event) {
            $newEntry = "[" . now()->format('d/m/Y H:i') . "] 🎉 El cliente ha respondido al seguimiento. Estado cambiado a " . ucfirst($newStatus) . " por " . auth()->user()->name . ".";
            $event->update([
                'status' => in_array($newStatus, ['draft', 'confirmed']) ? $newStatus : 'draft',
                'no_response_at' => null,
                'notes' => Event::prependNote($event->notes, $newEntry),
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
