<?php

namespace App\Livewire\Guest;

use Livewire\Component;
use App\Models\Event;
use App\Models\EventMusicRequest;

class LiveRequests extends Component
{
    public $token;
    public $event;

    public $song_title = '';
    public $song_artist = '';
    public $guest_name = '';
    public $guest_note = '';

    public $searchQuery = '';
    public $blacklistWarning = '';
    public $successMessage = '';

    public function mount($token)
    {
        $this->token = $token;
        $this->event = Event::where('token', $token)->firstOrFail();
    }

    public function selectSong($title, $artist = '')
    {
        $this->song_title = $title;
        $this->song_artist = $artist;
        $this->searchQuery = ($artist ? $artist . ' - ' : '') . $title;
        $this->blacklistWarning = '';
    }

    public function checkBlacklist($title, $artist): bool
    {
        $blacklist = $this->event->musicRequests()->where('category', 'lista_negra')->get();

        $textToSearch = strtolower($title . ' ' . $artist);

        foreach ($blacklist as $item) {
            $itemTitle = strtolower(trim($item->title));
            $itemArtist = strtolower(trim($item->artist ?? ''));

            if (!empty($itemTitle) && str_contains($textToSearch, $itemTitle)) {
                return true;
            }
            if (!empty($itemArtist) && str_contains($textToSearch, $itemArtist)) {
                return true;
            }
        }

        return false;
    }

    public function sendRequest()
    {
        $this->validate([
            'song_title' => 'required|string|max:255',
            'guest_name' => 'nullable|string|max:100',
            'guest_note' => 'nullable|string|max:255',
        ], [
            'song_title.required' => 'Por favor, busca o escribe el título de la canción.',
        ]);

        if ($this->checkBlacklist($this->song_title, $this->song_artist)) {
            $this->blacklistWarning = '🚫 ¡Ups! Esta canción o artista está en la Lista Negra de música prohibida por los novios/anfitriones. ¡Prueba con otro temazo!';
            return;
        }

        $maxOrder = $this->event->musicRequests()->max('order') ?: 0;

        $this->event->musicRequests()->create([
            'category' => 'baile',
            'moment' => 'Petición Invitados',
            'title' => $this->song_title,
            'artist' => $this->song_artist,
            'is_guest_request' => true,
            'guest_name' => $this->guest_name ?: 'Invitado',
            'guest_note' => $this->guest_note,
            'requested_by' => 'Invitado: ' . ($this->guest_name ?: 'Anónimo'),
            'likes' => 1,
            'order' => $maxOrder + 1,
            'status' => 'pending',
        ]);

        $this->reset(['song_title', 'song_artist', 'guest_note', 'searchQuery', 'blacklistWarning']);
        $this->successMessage = '¡Petición enviada al DJ en cabina! Ya está en la lista de la fiesta.';
    }

    public function likeSong($requestId)
    {
        $req = EventMusicRequest::where('event_id', $this->event->id)->find($requestId);
        if ($req) {
            $req->increment('likes');
        }
    }

    public function render()
    {
        $guestRequests = $this->event->musicRequests()
            ->where('is_guest_request', true)
            ->orderBy('likes', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.guest.live-requests', [
            'guestRequests' => $guestRequests,
        ])->layout('components.layouts.wide-guest');
    }
}
