<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Event;
use App\Models\EventMusicRequest;
use App\Services\MusicSearchService;

class DjBoothMode extends Component
{
    public $event;
    public $token;
    
    // Tabs: 'escaleta', 'custom', 'playlists', 'requests', 'guest_live', 'blacklist', 'notes', 'checklist', 'documents'
    public $activeTab = 'escaleta';
    
    // View mode: 'pads' or 'list'
    public $viewMode = 'pads';

    // Phase / Moment category filter: 'all', 'coctel', 'banquete', 'baile', 'ceremonia', 'guest'
    public $selectedPhase = 'all';
    public $showChangeMomentModal = false;

    // Grouping mode: 'grouped' (organized by moments with headers) or 'flat' (continuous sampler)
    public $groupingMode = 'grouped';
    
    // Pad pagination & search
    public $padPage = 1;
    public $perPage = 12;
    public $search = '';

    public $currentlyPlayingId = null;

    // Quick add modal
    public $showAddModal = false;
    public $new_title = '';
    public $new_artist = '';
    public $new_category = 'baile';
    public $new_moment = 'Baile & Fiesta';
    public $new_requested_by = 'DJ Cabina';
    public $new_notes = '';
    public $new_spotify_url = '';
    public $new_apple_music_url = '';
    public $new_audio_file = '';

    // Live Universal Music Catalog Search
    public $musicSearchQuery = '';
    public $musicSearchResults = [];

    // Escaleta state
    public $showAddMomentModal = false;
    public $new_moment_name = '';
    public $new_moment_time = '';
    public $new_moment_song_title = '';
    public $new_moment_artist = '';
    public $new_moment_notes = '';

    // Notes tab
    public $dj_notes = '';

    // Checklist state
    public $checklist = [
        ['text' => 'Comprobar suministro eléctrico y tomas de corriente en cabina', 'done' => true],
        ['text' => 'Montaje de altavoces / PA principal y monitores de cabina', 'done' => true],
        ['text' => 'Comprobar puente de luces / focos LED y efectos DMX', 'done' => true],
        ['text' => 'Verificar micrófonos inalámbricos (pilas nuevas y sin interferencias)', 'done' => true],
        ['text' => 'Repasar escaleta y momentos clave con el maitre / organizador', 'done' => false],
        ['text' => 'Probar audio de entrada a comedor, corte de tarta y baile nupcial', 'done' => false],
        ['text' => 'Código QR de peticiones colocado en mesas o barra', 'done' => false],
        ['text' => 'Conexión a internet / datos de respaldo para peticiones en vivo', 'done' => false],
    ];

    public function mount($event = null, $token = null)
    {
        if ($token) {
            $this->token = $token;
            $this->event = Event::with(['client', 'dj', 'assistant', 'musicRequests', 'contracts', 'quotes', 'invoices'])->where('token', $token)->firstOrFail();
        } else {
            $this->event = $event instanceof Event ? $event : Event::findOrFail($event);
            $this->event->load(['client', 'dj', 'assistant', 'musicRequests', 'contracts', 'quotes', 'invoices']);
        }

        $this->dj_notes = $this->event->notes ?: "Espacio: Salón 1, Discoteca 1\nServicios: MOMENTOS, DISCOTECA\nDuración baile: 4 horas (00:30 a 04:30 aprox)";

        // Find if any is already playing
        $playing = $this->event->musicRequests->where('status', 'playing')->first();
        if ($playing) {
            $this->currentlyPlayingId = $playing->id;
        }
    }

    public function updatedSearch()
    {
        $this->padPage = 1;
    }

    public function updatedMusicSearchQuery()
    {
        if (strlen(trim($this->musicSearchQuery)) >= 2) {
            $this->musicSearchResults = MusicSearchService::search($this->musicSearchQuery, 6);
        } else {
            $this->musicSearchResults = [];
        }
    }

    public function selectTrackFromSearch($track)
    {
        $this->new_title = $track['title'] ?? '';
        $this->new_artist = $track['artist'] ?? '';
        $this->new_spotify_url = $track['spotify_url'] ?? '';
        $this->new_apple_music_url = $track['apple_music_url'] ?? '';
        $this->new_audio_file = $track['preview_url'] ?? '';
        $this->musicSearchResults = [];
        $this->musicSearchQuery = '';
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->padPage = 1;
        $this->search = '';
    }

    public function setPhase($phase)
    {
        $this->selectedPhase = $phase;
        $this->padPage = 1;
        $this->showChangeMomentModal = false;
    }

    public function openAddModalForPhase($phase = null)
    {
        $target = $phase ?: $this->selectedPhase;
        if ($target === 'coctel') {
            $this->new_category = 'coctel';
            $this->new_moment = 'Cóctel / Bienvenida';
        } elseif ($target === 'banquete') {
            $this->new_category = 'banquete';
            $this->new_moment = 'Banquete';
        } elseif ($target === 'baile') {
            $this->new_category = 'baile';
            $this->new_moment = 'Baile & Fiesta';
        } elseif ($target === 'ceremonia') {
            $this->new_category = 'ceremonia';
            $this->new_moment = 'Ceremonia';
        } else {
            $this->new_category = 'baile';
            $this->new_moment = 'Pista Libre';
        }
        $this->showAddModal = true;
    }

    public function setGroupingMode($mode)
    {
        $this->groupingMode = $mode;
        $this->viewMode = 'pads';
        $this->padPage = 1;
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->padPage = 1;
    }

    public function nextPage($totalPages)
    {
        if ($this->padPage < $totalPages) {
            $this->padPage++;
        }
    }

    public function prevPage()
    {
        if ($this->padPage > 1) {
            $this->padPage--;
        }
    }

    public function togglePlay($requestId)
    {
        $req = EventMusicRequest::where('event_id', $this->event->id)->find($requestId);
        if (!$req) return;

        if ($req->status === 'playing') {
            $req->update(['status' => 'paused']);
            $this->currentlyPlayingId = null;
        } elseif ($req->status === 'paused') {
            $req->update(['status' => 'played']);
            $this->currentlyPlayingId = null;
        } else {
            EventMusicRequest::where('event_id', $this->event->id)
                ->where('status', 'playing')
                ->update(['status' => 'played']);

            $req->update(['status' => 'playing']);
            $this->currentlyPlayingId = $req->id;
        }

        $this->event->load('musicRequests');
    }

    public function setStatus($requestId, $status)
    {
        $req = EventMusicRequest::where('event_id', $this->event->id)->find($requestId);
        if ($req) {
            $req->update(['status' => $status]);
            if ($status === 'playing') {
                $this->currentlyPlayingId = $req->id;
            } elseif ($this->currentlyPlayingId === $req->id) {
                $this->currentlyPlayingId = null;
            }
            $this->event->load('musicRequests');
        }
    }

    public function deleteRequest($requestId)
    {
        $req = EventMusicRequest::where('event_id', $this->event->id)->find($requestId);
        if ($req) {
            $req->delete();
            $this->event->load('musicRequests');
        }
    }

    public function addRequest()
    {
        $this->validate([
            'new_title' => 'required|string|max:255',
            'new_artist' => 'nullable|string|max:255',
        ]);

        $maxOrder = $this->event->musicRequests()->max('order') ?: 0;

        $this->event->musicRequests()->create([
            'category' => $this->new_category,
            'moment' => $this->new_moment,
            'title' => $this->new_title,
            'artist' => $this->new_artist,
            'requested_by' => $this->new_requested_by,
            'notes' => $this->new_notes,
            'spotify_url' => $this->new_spotify_url,
            'apple_music_url' => $this->new_apple_music_url,
            'audio_file' => $this->new_audio_file,
            'order' => $maxOrder + 1,
            'status' => 'pending',
        ]);

        $this->reset(['new_title', 'new_artist', 'new_notes', 'new_spotify_url', 'new_apple_music_url', 'new_audio_file', 'musicSearchQuery', 'musicSearchResults', 'showAddModal']);
        $this->event->load('musicRequests');
        session()->flash('booth_message', 'Canción añadida correctamente.');
    }

    public function generateEscaleta()
    {
        $priorityMoments = [
            'entrada a comedor' => 10,
            'entrada comedor' => 10,
            'en sorbete' => 20,
            'sorbete' => 20,
            'regalo' => 30,
            'ramo' => 40,
            'tarta' => 50,
            'corte tarta' => 50,
            'audio sobrino' => 60,
            'baile' => 70,
            'baile nupcial' => 70,
            'baile novia con padre' => 75,
        ];

        $requests = $this->event->musicRequests;
        $order = 1;

        $moments = $requests->filter(fn($r) => !empty($r->moment) && $r->moment !== 'Otras peticiones (pista libre)')->sortBy(function($r) use ($priorityMoments) {
            $m = strtolower(trim($r->moment));
            foreach ($priorityMoments as $key => $weight) {
                if (str_contains($m, $key)) {
                    return $weight;
                }
            }
            return 35;
        });

        foreach ($moments as $m) {
            $m->update(['order' => $order++]);
        }

        $party = $requests->filter(fn($r) => empty($r->moment) || $r->moment === 'Otras peticiones (pista libre)');
        foreach ($party as $p) {
            $p->update(['order' => $order++]);
        }

        $this->event->load('musicRequests');
        session()->flash('booth_message', '✨ Escaleta cronológica generada con éxito.');
    }

    public function moveMomentUp($requestId)
    {
        $current = EventMusicRequest::where('event_id', $this->event->id)->find($requestId);
        if (!$current) return;

        $previous = EventMusicRequest::where('event_id', $this->event->id)
            ->where('order', '<', $current->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previous) {
            $temp = $current->order;
            $current->update(['order' => $previous->order]);
            $previous->update(['order' => $temp]);
            $this->event->load('musicRequests');
        }
    }

    public function moveMomentDown($requestId)
    {
        $current = EventMusicRequest::where('event_id', $this->event->id)->find($requestId);
        if (!$current) return;

        $next = EventMusicRequest::where('event_id', $this->event->id)
            ->where('order', '>', $current->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($next) {
            $temp = $current->order;
            $current->update(['order' => $next->order]);
            $next->update(['order' => $temp]);
            $this->event->load('musicRequests');
        }
    }

    public function addMoment()
    {
        $this->validate([
            'new_moment_name' => 'required|string|max:255',
            'new_moment_song_title' => 'required|string|max:255',
        ]);

        $maxOrder = $this->event->musicRequests()->max('order') ?: 0;

        $this->event->musicRequests()->create([
            'category' => 'banquete',
            'moment' => $this->new_moment_name,
            'title' => $this->new_moment_song_title,
            'artist' => $this->new_moment_artist,
            'cue_time' => $this->new_moment_time,
            'notes' => $this->new_moment_notes,
            'requested_by' => 'DJ Cabina',
            'order' => $maxOrder + 1,
            'status' => 'pending',
        ]);

        $this->reset(['new_moment_name', 'new_moment_time', 'new_moment_song_title', 'new_moment_artist', 'new_moment_notes', 'showAddMomentModal']);
        $this->event->load('musicRequests');
        session()->flash('booth_message', 'Momento añadido a la escaleta.');
    }

    public function saveNotes()
    {
        $this->event->update(['notes' => $this->dj_notes]);
        session()->flash('booth_message', 'Notas del DJ guardadas correctamente.');
    }

    public function toggleChecklist($index)
    {
        if (isset($this->checklist[$index])) {
            $this->checklist[$index]['done'] = !$this->checklist[$index]['done'];
        }
    }

    private function matchItemPhase($item, string $phase): bool
    {
        if ($phase === 'all') return true;
        if ($phase === 'guest') return (bool)$item->is_guest_request;

        $cat = strtolower($item->category ?? '');
        $mom = strtolower($item->moment ?? '');

        if ($phase === 'coctel') {
            return $cat === 'coctel' || str_contains($mom, 'coctel') || str_contains($mom, 'cóctel') || str_contains($mom, 'cocktail');
        }

        if ($phase === 'banquete') {
            return $cat === 'banquete' 
                || str_contains($mom, 'comedor') 
                || str_contains($mom, 'sorbete') 
                || str_contains($mom, 'regalo') 
                || str_contains($mom, 'ramo') 
                || str_contains($mom, 'tarta') 
                || str_contains($mom, 'entrega')
                || str_contains($mom, 'banquete');
        }

        if ($phase === 'baile') {
            return $cat === 'baile' 
                || str_contains($mom, 'baile') 
                || str_contains($mom, 'fiesta') 
                || str_contains($mom, 'loca') 
                || str_contains($mom, 'peticion')
                || str_contains($mom, 'petición')
                || str_contains($mom, 'barra libre')
                || str_contains($mom, 'libre');
        }

        if ($phase === 'ceremonia') {
            return $cat === 'ceremonia' 
                || str_contains($mom, 'ceremonia') 
                || str_contains($mom, 'anillo') 
                || str_contains($mom, 'lectura')
                || str_contains($mom, 'salida');
        }

        return false;
    }

    public function render()
    {
        $allRequests = $this->event->musicRequests;
        $blacklist = $allRequests->where('category', 'lista_negra');
        $nonBlacklist = $allRequests->where('category', '!=', 'lista_negra');

        // Calculate counts per phase for quick filter badges
        $phaseCounts = [
            'all' => $nonBlacklist->count(),
            'coctel' => $nonBlacklist->filter(fn($i) => $this->matchItemPhase($i, 'coctel'))->count(),
            'banquete' => $nonBlacklist->filter(fn($i) => $this->matchItemPhase($i, 'banquete'))->count(),
            'baile' => $nonBlacklist->filter(fn($i) => $this->matchItemPhase($i, 'baile'))->count(),
            'ceremonia' => $nonBlacklist->filter(fn($i) => $this->matchItemPhase($i, 'ceremonia'))->count(),
            'guest' => $allRequests->where('is_guest_request', true)->count(),
        ];

        $query = $this->event->musicRequests();

        if (in_array($this->activeTab, ['tracks', 'custom', 'playlists', 'requests'])) {
            // Repertorio del evento filtrado por fase
            $query = $query->where('category', '!=', 'lista_negra');
        } elseif ($this->activeTab === 'guest_live') {
            // Peticiones en Vivo (Invitados por QR)
            $query = $query->where('is_guest_request', true);
        } elseif ($this->activeTab === 'blacklist') {
            // Lista Negra
            $query = $query->where('category', 'lista_negra');
        } elseif ($this->activeTab === 'escaleta') {
            // Escaleta
            $query = $query->where('category', '!=', 'lista_negra');
        }

        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query = $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', $searchTerm)
                  ->orWhere('artist', 'like', $searchTerm)
                  ->orWhere('moment', 'like', $searchTerm)
                  ->orWhere('requested_by', 'like', $searchTerm);
            });
        }

        // Ordering: playing first, paused second, pending third, played fourth
        $rawCollection = $query->orderByRaw("CASE WHEN status = 'playing' THEN 1 WHEN status = 'paused' THEN 2 WHEN status = 'pending' THEN 3 ELSE 4 END")
                               ->orderBy('order', 'asc')
                               ->orderBy('likes', 'desc')
                               ->get();

        // Apply Phase Filter in PHP if in Repertorio tab
        if (in_array($this->activeTab, ['tracks', 'custom', 'playlists', 'requests']) && $this->selectedPhase !== 'all') {
            $filteredCollection = $rawCollection->filter(fn($i) => $this->matchItemPhase($i, $this->selectedPhase));
        } else {
            $filteredCollection = $rawCollection;
        }

        $totalItems = $filteredCollection->count();
        $totalPages = max(1, (int)ceil($totalItems / $this->perPage));

        // Slice for pad pagination
        $paginatedPads = $filteredCollection->slice(($this->padPage - 1) * $this->perPage, $this->perPage);

        // Grouped by moment for Grouped Mode and Escaleta
        $groupedPadsByMoment = $filteredCollection->groupBy(function($item) {
            return $item->moment ?: 'Otras Canciones (Pista Libre)';
        });

        $groupedByMoment = $allRequests->where('category', '!=', 'lista_negra')->groupBy(function($item) {
            return $item->moment ?: 'Otras peticiones (pista libre)';
        });

        $escaletaItems = $allRequests->where('category', '!=', 'lista_negra')->sortBy('order');

        $lastPlayed = $allRequests->where('status', 'played')->sortByDesc('updated_at')->first();
        $playedCount = $allRequests->where('status', 'played')->count();
        $totalCount = $nonBlacklist->count();
        $progressPct = $totalCount > 0 ? round(($playedCount / $totalCount) * 100) : 0;

        return view('livewire.admin.dj-booth-mode', [
            'filteredRequests' => $filteredCollection,
            'paginatedPads' => $paginatedPads,
            'groupedPadsByMoment' => $groupedPadsByMoment,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'blacklist' => $blacklist,
            'totalCount' => $totalCount,
            'playedCount' => $playedCount,
            'progressPct' => $progressPct,
            'lastPlayed' => $lastPlayed,
            'groupedByMoment' => $groupedByMoment,
            'escaletaItems' => $escaletaItems,
            'phaseCounts' => $phaseCounts,
            'guestRequestsCount' => $phaseCounts['guest'],
        ])->layout('components.layouts.wide-guest');
    }
}
