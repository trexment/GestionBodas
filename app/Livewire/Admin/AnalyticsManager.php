<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\EventMusicRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsManager extends Component
{
    public $selectedYear;

    public function mount()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Acceso restringido a administradores.');
        }

        $this->selectedYear = (int) date('Y');
    }

    public function render()
    {
        // 1. Facturación total y por meses
        $invoices = Invoice::whereYear('issue_date', $this->selectedYear)->get();
        $totalBilledYear = $invoices->sum('total');
        $paidYear = $invoices->where('status', 'paid')->sum('total');
        $pendingYear = $invoices->where('status', 'unpaid')->sum('total');

        $monthlyBilled = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthName = Carbon::create($this->selectedYear, $m, 1)->translatedFormat('M');
            $monthlyBilled[$monthName] = Invoice::whereYear('issue_date', $this->selectedYear)
                ->whereMonth('issue_date', $m)
                ->sum('total');
        }

        // 2. Eventos por estado
        $eventsByStatus = Event::whereYear('event_date', $this->selectedYear)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // 3. Top Canciones más solicitadas por categorías
        $topEntrance = EventMusicRequest::where('category', 'banquete')
            ->where('moment', 'Entrada Comedor')
            ->select('title', 'artist', DB::raw('count(*) as total'))
            ->groupBy('title', 'artist')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get();

        $topDance = EventMusicRequest::where('category', 'baile')
            ->where('moment', 'Baile Nupcial')
            ->select('title', 'artist', DB::raw('count(*) as total'))
            ->groupBy('title', 'artist')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get();

        $topCake = EventMusicRequest::where('category', 'banquete')
            ->where('moment', 'Corte de Tarta')
            ->select('title', 'artist', DB::raw('count(*) as total'))
            ->groupBy('title', 'artist')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get();

        $topParty = EventMusicRequest::where('category', 'baile')
            ->where('moment', '!=', 'Baile Nupcial')
            ->select('title', 'artist', DB::raw('count(*) as total'))
            ->groupBy('title', 'artist')
            ->orderBy('total', 'desc')
            ->take(8)
            ->get();

        $topBlacklist = EventMusicRequest::where('category', 'lista_negra')
            ->select('title', 'artist', DB::raw('count(*) as total'))
            ->groupBy('title', 'artist')
            ->orderBy('total', 'desc')
            ->take(6)
            ->get();

        // 4. Ranking de Personal (DJs y Asistentes)
        $djStats = User::where('role', 'dj')
            ->withCount(['eventsAsDj' => function ($q) {
                $q->whereYear('event_date', $this->selectedYear);
            }])
            ->orderBy('events_as_dj_count', 'desc')
            ->get();

        $assistantStats = User::where('role', 'assistant')
            ->withCount(['eventsAsAssistant' => function ($q) {
                $q->whereYear('event_date', $this->selectedYear);
            }])
            ->orderBy('events_as_assistant_count', 'desc')
            ->get();

        return view('livewire.admin.analytics-manager', [
            'totalBilledYear' => $totalBilledYear,
            'paidYear' => $paidYear,
            'pendingYear' => $pendingYear,
            'monthlyBilled' => $monthlyBilled,
            'eventsByStatus' => $eventsByStatus,
            'topEntrance' => $topEntrance,
            'topDance' => $topDance,
            'topCake' => $topCake,
            'topParty' => $topParty,
            'topBlacklist' => $topBlacklist,
            'djStats' => $djStats,
            'assistantStats' => $assistantStats,
            'totalEventsYear' => Event::whereYear('event_date', $this->selectedYear)->count(),
        ])->layout('components.layouts.app', ['header' => 'Estadísticas y Repertorio']);
    }
}
