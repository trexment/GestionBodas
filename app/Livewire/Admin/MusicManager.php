<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Track;
use App\Models\Playlist;
use App\Models\Event;
use App\Models\Setting;
use App\Services\CloudMusicStorageService;
use App\Services\MusicSearchService;

class MusicManager extends Component
{
    use WithPagination;

    // Active View
    public $activeTab = 'library';

    // Top Charts & Trends
    public $topChartType = 'spain_top50';
    public $selectedEventForTopTrack = '';
    public $selectedCategoryForTopTrack = 'baile';
    public $selectedMomentForTopTrack = 'Baile / Fiesta';

    // Filters for Library
    public $search = '';
    public $sourceFilter = 'all'; // 'all', 'google_drive', 'local'
    public $folderFilter = 'all'; // 'all' or specific folder name
    
    // Playlists & Events
    public $playlists = [];
    public $events = [];

    // Form for Track
    public $track_title = '';
    public $track_artist = '';
    public $track_genre = '';
    public $track_bpm = '';
    public $track_file_path = '';
    
    // Form for Playlist
    public $playlist_name = '';
    public $playlist_event_id = '';

    // Manage Playlist
    public $activePlaylist = null;
    public $searchTrack = '';
    public $searchResults = [];

    // Drive Sync
    public $isSyncing = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'sourceFilter' => ['except' => 'all'],
        'folderFilter' => ['except' => 'all'],
        'topChartType' => ['except' => 'spain_top50'],
    ];

    public function setTopChartType($type)
    {
        $this->topChartType = $type;
    }

    public function addTopTrackToLibrary($title, $artist, $coverUrl = '', $spotifyUrl = '', $appleUrl = '', $youtubeUrl = '', $previewUrl = '')
    {
        // Check if track already exists
        $exists = Track::where('title', $title)->where('artist', $artist)->first();
        if ($exists) {
            session()->flash('top_message', "ℹ️ '{$title}' ya está en tu catálogo musical.");
            return;
        }

        Track::create([
            'title' => $title,
            'artist' => $artist,
            'genre' => 'Top Éxito',
            'file_path' => $previewUrl ?: null,
            'source' => 'spotify',
            'spotify_url' => $spotifyUrl,
            'apple_music_url' => $appleUrl,
            'youtube_url' => $youtubeUrl,
        ]);

        session()->flash('top_message', "✅ '{$title}' de {$artist} añadida a tu catálogo musical.");
    }

    public function addTopTrackToEvent($title, $artist, $eventId, $spotifyUrl = '', $appleUrl = '', $youtubeUrl = '', $previewUrl = '')
    {
        if (empty($eventId)) {
            session()->flash('top_error', 'Selecciona un evento antes de añadir la canción.');
            return;
        }

        $event = Event::find($eventId);
        if (!$event) {
            session()->flash('top_error', 'Evento no encontrado.');
            return;
        }

        $maxOrder = \App\Models\EventMusicRequest::where('event_id', $eventId)->max('order') ?: 0;

        $event->musicRequests()->create([
            'category' => $this->selectedCategoryForTopTrack ?: 'baile',
            'moment' => $this->selectedMomentForTopTrack ?: 'Baile / Fiesta',
            'title' => $title,
            'artist' => $artist,
            'requested_by' => 'DJ (Top Hits)',
            'spotify_url' => $spotifyUrl,
            'apple_music_url' => $appleUrl,
            'youtube_url' => $youtubeUrl,
            'audio_file' => $previewUrl ?: null,
            'status' => 'pending',
            'order' => $maxOrder + 1,
        ]);

        session()->flash('top_message', "✨ '{$title}' añadida a la escaleta del evento '{$event->name}'.");
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSourceFilter()
    {
        $this->resetPage();
    }

    public function updatingFolderFilter()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->loadPlaylistsAndEvents();
    }

    public function loadPlaylistsAndEvents()
    {
        $this->playlists = Playlist::with('event')->orderBy('name', 'asc')->get();
        $this->events = Event::orderBy('event_date', 'desc')->get();
    }

    public function syncDrive()
    {
        $folderUrlOrId = Setting::get('google_drive_library_folder', '');
        $apiKey = Setting::get('google_drive_api_key', '');

        if (empty($folderUrlOrId)) {
            session()->flash('sync_error', 'Primero configura el enlace o ID de tu carpeta de Google Drive en Ajustes > Integraciones Musicales > Almacenamiento.');
            return;
        }

        $result = CloudMusicStorageService::syncMasterLibraryFromGoogleDrive($folderUrlOrId, $apiKey);

        if ($result['success']) {
            session()->flash('sync_success', $result['message']);
        } else {
            session()->flash('sync_error', $result['message']);
        }

        $this->resetPage();
    }

    public function saveTrack()
    {
        $this->validate([
            'track_title' => 'required|string|max:255',
            'track_artist' => 'nullable|string|max:255',
            'track_genre' => 'nullable|string|max:255',
            'track_bpm' => 'nullable|integer',
            'track_file_path' => 'nullable|string|max:500',
        ]);

        // Auto-resolve Spotify and streaming URLs if not provided
        $meta = MusicSearchService::resolveTrackMetadata($this->track_title, $this->track_artist);

        Track::create([
            'title' => $this->track_title,
            'artist' => $this->track_artist,
            'genre' => $this->track_genre,
            'bpm' => $this->track_bpm,
            'file_path' => $this->track_file_path ?: ($meta['preview_url'] ?? null),
            'source' => $this->track_file_path ? (str_contains($this->track_file_path, 'drive.google.com') ? 'google_drive' : 'local') : 'local',
            'spotify_url' => $meta['spotify_url'] ?? null,
            'apple_music_url' => $meta['apple_music_url'] ?? null,
            'youtube_url' => $meta['youtube_url'] ?? null,
        ]);

        $this->reset(['track_title', 'track_artist', 'track_genre', 'track_bpm', 'track_file_path']);
        $this->resetPage();
        session()->flash('track_message', 'Canción añadida al catálogo con enlaces sincronizados.');
    }

    public function deleteTrack($id)
    {
        Track::destroy($id);
        session()->flash('track_message', 'Canción eliminada del catálogo.');
    }

    public function savePlaylist()
    {
        $this->validate([
            'playlist_name' => 'required|string|max:255',
            'playlist_event_id' => 'nullable|exists:events,id',
        ]);

        Playlist::create([
            'name' => $this->playlist_name,
            'event_id' => $this->playlist_event_id ?: null,
        ]);

        $this->reset(['playlist_name', 'playlist_event_id']);
        $this->loadPlaylistsAndEvents();
        session()->flash('playlist_message', 'Playlist creada exitosamente.');
    }

    public function managePlaylist($id)
    {
        $this->activePlaylist = Playlist::with('tracks')->find($id);
        $this->activeTab = 'manage_playlist';
    }

    public function searchTracksToAdd()
    {
        if (strlen($this->searchTrack) > 1) {
            $this->searchResults = Track::where(function($q) {
                $q->where('title', 'like', '%' . $this->searchTrack . '%')
                  ->orWhere('artist', 'like', '%' . $this->searchTrack . '%');
            })->limit(15)->get();
        } else {
            $this->searchResults = [];
        }
    }

    public function addTrackToPlaylist($trackId)
    {
        if ($this->activePlaylist && !$this->activePlaylist->tracks->contains($trackId)) {
            $this->activePlaylist->tracks()->attach($trackId);
            $this->activePlaylist->load('tracks');
        }
        $this->searchTrack = '';
        $this->searchResults = [];
    }

    public function removeTrackFromPlaylist($trackId)
    {
        if ($this->activePlaylist) {
            $this->activePlaylist->tracks()->detach($trackId);
            $this->activePlaylist->load('tracks');
        }
    }

    public function backToPlaylists()
    {
        $this->activePlaylist = null;
        $this->activeTab = 'playlists';
        $this->loadPlaylistsAndEvents();
    }

    public function render()
    {
        $query = Track::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('artist', 'like', '%' . $this->search . '%')
                  ->orWhere('genre', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->sourceFilter === 'google_drive') {
            $query->where('source', 'google_drive');
        } elseif ($this->sourceFilter === 'local') {
            $query->where('source', '!=', 'google_drive');
        }

        if (!empty($this->folderFilter) && $this->folderFilter !== 'all') {
            $query->where('cloud_folder', $this->folderFilter);
        }

        $tracks = $query->orderBy('cloud_folder', 'asc')->orderBy('title', 'asc')->paginate(25);

        $totalTracks = Track::count();
        $driveCount = Track::where('source', 'google_drive')->count();
        $driveFolderConfigured = !empty(Setting::get('google_drive_library_folder'));

        // Obtener carpetas disponibles con conteo de canciones
        $availableFolders = Track::where('source', 'google_drive')
            ->whereNotNull('cloud_folder')
            ->where('cloud_folder', '!=', '')
            ->where('cloud_folder', '!=', 'Raíz')
            ->select('cloud_folder', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('cloud_folder')
            ->orderBy('cloud_folder', 'asc')
            ->get();

        $topTracks = [];
        if ($this->activeTab === 'top_charts') {
            $topTracks = MusicSearchService::getTopCharts($this->topChartType, 50);
        }

        return view('livewire.admin.music-manager', [
            'tracks' => $tracks,
            'totalTracks' => $totalTracks,
            'driveCount' => $driveCount,
            'driveFolderConfigured' => $driveFolderConfigured,
            'availableFolders' => $availableFolders,
            'topTracks' => $topTracks,
        ])->layout('components.layouts.app', ['header' => 'Gestor Musical']);
    }
}
