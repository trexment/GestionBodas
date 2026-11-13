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

/**
 * Componente Livewire para la gestión de la biblioteca musical, sincronización con Google Drive, listas de reproducción y rankings Top Charts.
 */
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

    /**
     * Establece el tipo de listado de éxitos (Top Charts) activo.
     *
     * @param string $type Identificador del ranking musical.
     * @return void
     */
    public function setTopChartType($type)
    {
        $this->topChartType = $type;
    }

    /**
     * Añade una canción del ranking de éxitos a la biblioteca musical general según su posición en la lista.
     *
     * @param int $index Índice de la canción en el listado Top Charts.
     * @return void
     */
    public function addTopTrackByIndex(int $index)
    {
        $topTracks = MusicSearchService::getTopCharts($this->topChartType, 50);
        if (!isset($topTracks[$index])) {
            return;
        }
        $track = $topTracks[$index];
        $this->addTopTrackToLibrary(
            $track['title'] ?? '',
            $track['artist'] ?? '',
            $track['cover_url'] ?? '',
            $track['spotify_url'] ?? '',
            $track['apple_music_url'] ?? '',
            $track['youtube_url'] ?? '',
            $track['preview_url'] ?? ''
        );
    }

    /**
     * Añade una canción del ranking de éxitos directamente a la escaleta del evento seleccionado mediante su índice.
     *
     * @param int $index Índice de la canción en el listado Top Charts.
     * @return void
     */
    public function addTopTrackToEventByIndex(int $index)
    {
        if (empty($this->selectedEventForTopTrack)) {
            session()->flash('top_error', 'Selecciona un evento antes de añadir la canción.');
            return;
        }
        $topTracks = MusicSearchService::getTopCharts($this->topChartType, 50);
        if (!isset($topTracks[$index])) {
            return;
        }
        $track = $topTracks[$index];
        $this->addTopTrackToEvent(
            $track['title'] ?? '',
            $track['artist'] ?? '',
            $this->selectedEventForTopTrack,
            $track['spotify_url'] ?? '',
            $track['apple_music_url'] ?? '',
            $track['youtube_url'] ?? '',
            $track['preview_url'] ?? ''
        );
    }

    /**
     * Registra una canción procedente de Top Charts en el catálogo musical si no existe previamente.
     *
     * @param string $title Título de la canción.
     * @param string $artist Artista de la canción.
     * @param string $coverUrl URL de la portada.
     * @param string $spotifyUrl Enlace a Spotify.
     * @param string $appleUrl Enlace a Apple Music.
     * @param string $youtubeUrl Enlace a YouTube.
     * @param string $previewUrl URL de preescucha.
     * @return void
     */
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

    /**
     * Crea una petición musical en el evento indicado a partir de una canción de Top Charts.
     *
     * @param string $title Título de la canción.
     * @param string $artist Artista de la canción.
     * @param int|string $eventId Identificador del evento destino.
     * @param string $spotifyUrl Enlace a Spotify.
     * @param string $appleUrl Enlace a Apple Music.
     * @param string $youtubeUrl Enlace a YouTube.
     * @param string $previewUrl URL de preescucha.
     * @return void
     */
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
            'audio_file' => \App\Services\RemoteAudioUrlGuard::sanitizeInput($previewUrl) ?: null,
            'status' => 'pending',
            'order' => $maxOrder + 1,
        ]);

        session()->flash('top_message', "✨ '{$title}' añadida a la escaleta del evento '{$event->name}'.");
    }

    /**
     * Reinicia la paginación cuando se modifica el texto de búsqueda en la biblioteca.
     *
     * @return void
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Reinicia la paginación cuando se modifica el filtro de origen de las pistas.
     *
     * @return void
     */
    public function updatingSourceFilter()
    {
        $this->resetPage();
    }

    /**
     * Reinicia la paginación cuando se modifica el filtro de carpeta en la nube.
     *
     * @return void
     */
    public function updatingFolderFilter()
    {
        $this->resetPage();
    }

    /**
     * Inicializa el componente cargando las listas de reproducción y eventos disponibles.
     *
     * @return void
     */
    public function mount()
    {
        $this->loadPlaylistsAndEvents();
    }

    /**
     * Obtiene todas las playlists con su evento asociado y el listado de eventos ordenados por fecha.
     *
     * @return void
     */
    public function loadPlaylistsAndEvents()
    {
        $this->playlists = Playlist::with('event')->orderBy('name', 'asc')->get();
        $this->events = Event::orderBy('event_date', 'desc')->get();
    }

    /**
     * Sincroniza la biblioteca maestra de música con la carpeta configurada en Google Drive.
     *
     * @return void
     */
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

    /**
     * Valida y guarda una nueva pista musical en el catálogo resolviendo automáticamente sus enlaces de streaming.
     *
     * @return void
     */
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

    /**
     * Elimina una canción del catálogo musical por su identificador.
     *
     * @param int|string $id Identificador de la pista a eliminar.
     * @return void
     */
    public function deleteTrack($id)
    {
        Track::destroy($id);
        session()->flash('track_message', 'Canción eliminada del catálogo.');
    }

    /**
     * Valida y crea una nueva lista de reproducción, vinculándola opcionalmente a un evento.
     *
     * @return void
     */
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

    /**
     * Abre la vista de gestión de canciones de una playlist específica.
     *
     * @param int|string $id Identificador de la playlist.
     * @return void
     */
    public function managePlaylist($id)
    {
        $this->activePlaylist = Playlist::with('tracks')->find($id);
        $this->activeTab = 'manage_playlist';
    }

    /**
     * Busca pistas en el catálogo local para añadirlas a la playlist activa.
     *
     * @return void
     */
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

    /**
     * Asocia una pista musical a la playlist activa si aún no forma parte de ella.
     *
     * @param int|string $trackId Identificador de la pista musical.
     * @return void
     */
    public function addTrackToPlaylist($trackId)
    {
        if ($this->activePlaylist && !$this->activePlaylist->tracks->contains($trackId)) {
            $this->activePlaylist->tracks()->attach($trackId);
            $this->activePlaylist->load('tracks');
        }
        $this->searchTrack = '';
        $this->searchResults = [];
    }

    /**
     * Desvincula una pista musical de la playlist activa.
     *
     * @param int|string $trackId Identificador de la pista musical.
     * @return void
     */
    public function removeTrackFromPlaylist($trackId)
    {
        if ($this->activePlaylist) {
            $this->activePlaylist->tracks()->detach($trackId);
            $this->activePlaylist->load('tracks');
        }
    }

    /**
     * Regresa desde el editor de una playlist al listado general de listas de reproducción.
     *
     * @return void
     */
    public function backToPlaylists()
    {
        $this->activePlaylist = null;
        $this->activeTab = 'playlists';
        $this->loadPlaylistsAndEvents();
    }

    /**
     * Filtra y pagina las canciones del catálogo y renderiza la vista del gestor musical.
     *
     * @return \Illuminate\Contracts\View\View
     */
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
