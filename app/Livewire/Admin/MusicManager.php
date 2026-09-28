<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Track;
use App\Models\Playlist;
use App\Models\Event;

class MusicManager extends Component
{
    // Active View
    public $activeTab = 'library';

    // Library Data
    public $tracks = [];
    public $playlists = [];
    
    // Form for Track
    public $track_title, $track_artist, $track_genre, $track_bpm;
    
    // Form for Playlist
    public $playlist_name, $playlist_event_id;
    public $events = [];

    // Manage Playlist
    public $activePlaylist = null;
    public $searchTrack = '';
    public $searchResults = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->tracks = Track::orderBy('title', 'asc')->get();
        $this->playlists = Playlist::with('event')->orderBy('name', 'asc')->get();
        $this->events = Event::all();
    }

    public function saveTrack()
    {
        $this->validate([
            'track_title' => 'required|string|max:255',
            'track_artist' => 'nullable|string|max:255',
            'track_genre' => 'nullable|string|max:255',
            'track_bpm' => 'nullable|integer',
        ]);

        Track::create([
            'title' => $this->track_title,
            'artist' => $this->track_artist,
            'genre' => $this->track_genre,
            'bpm' => $this->track_bpm,
        ]);

        $this->reset(['track_title', 'track_artist', 'track_genre', 'track_bpm']);
        $this->loadData();
        session()->flash('track_message', 'Canción añadida a la biblioteca.');
    }

    public function deleteTrack($id)
    {
        Track::destroy($id);
        $this->loadData();
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
        $this->loadData();
        session()->flash('playlist_message', 'Playlist creada exitosamente.');
    }

    public function managePlaylist($id)
    {
        $this->activePlaylist = Playlist::with('tracks')->find($id);
        $this->activeTab = 'manage_playlist';
    }

    public function searchTracksToAdd()
    {
        if (strlen($this->searchTrack) > 2) {
            $this->searchResults = Track::where('title', 'like', '%' . $this->searchTrack . '%')
                ->orWhere('artist', 'like', '%' . $this->searchTrack . '%')
                ->get();
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
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.music-manager')->layout('components.layouts.app', ['header' => 'Gestor Musical']);
    }
}
