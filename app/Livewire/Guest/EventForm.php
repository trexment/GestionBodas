<?php

namespace App\Livewire\Guest;

use Livewire\Component;
use App\Models\Event;
use App\Models\EventMusicRequest;

class EventForm extends Component
{
    public $token;
    public $event;

    // Form fields
    public $ceremony_songs;
    public $cocktail_songs;
    public $entrance_song;
    public $cake_song;
    public $gifts_songs;
    public $dance_song;
    public $party_favs;
    public $blacklist;
    public $comments;

    public $submitted = false;
    public $hasExistingData = false;

    public function mount($token)
    {
        $this->token = $token;
        $this->event = Event::where('token', $token)->firstOrFail();
        $this->loadExistingData();
    }

    protected function loadExistingData()
    {
        $requests = $this->event->musicRequests()->orderBy('order', 'asc')->get();

        if ($requests->isNotEmpty()) {
            $this->hasExistingData = true;

            // 1. Entrada Salón
            $entrance = $requests->first(function ($r) {
                return $r->category === 'banquete' && $r->moment === 'Entrada Comedor';
            });
            if ($entrance) {
                $this->entrance_song = ($entrance->artist ? $entrance->artist . ' - ' : '') . $entrance->title;
            }

            // 2. Corte de Tarta
            $cake = $requests->first(function ($r) {
                return $r->category === 'banquete' && $r->moment === 'Corte de Tarta';
            });
            if ($cake) {
                $this->cake_song = ($cake->artist ? $cake->artist . ' - ' : '') . $cake->title;
            }

            // 3. Regalos / Sorpresas (otros momentos en banquete)
            $gifts = $requests->filter(function ($r) {
                return $r->category === 'banquete' && $r->moment !== 'Entrada Comedor' && $r->moment !== 'Corte de Tarta';
            });
            if ($gifts->isNotEmpty()) {
                $this->gifts_songs = $gifts->map(function ($r) {
                    $prefix = ($r->moment && $r->moment !== 'Regalo / Sorpresa' && $r->moment !== 'General') ? $r->moment . ': ' : '';
                    return $prefix . ($r->artist ? $r->artist . ' - ' : '') . $r->title;
                })->implode("\n");
            }

            // 4. Baile Nupcial
            $dance = $requests->first(function ($r) {
                return $r->category === 'baile' && $r->moment === 'Baile Nupcial';
            });
            if ($dance) {
                $this->dance_song = ($dance->artist ? $dance->artist . ' - ' : '') . $dance->title;
            }

            // 5. Ceremonia
            $ceremony = $requests->filter(function ($r) {
                return $r->category === 'ceremonia';
            });
            if ($ceremony->isNotEmpty()) {
                $this->ceremony_songs = $ceremony->map(function ($r) {
                    $prefix = ($r->moment && $r->moment !== 'Ceremonia' && $r->moment !== 'General') ? $r->moment . ': ' : '';
                    return $prefix . ($r->artist ? $r->artist . ' - ' : '') . $r->title;
                })->implode("\n");
            }

            // 6. Cóctel
            $cocktail = $requests->filter(function ($r) {
                return $r->category === 'coctel';
            });
            if ($cocktail->isNotEmpty()) {
                $this->cocktail_songs = $cocktail->map(function ($r) {
                    return ($r->artist ? $r->artist . ' - ' : '') . $r->title;
                })->implode("\n");
            }

            // 7. Temazos Fiesta
            $party = $requests->filter(function ($r) {
                return $r->category === 'baile' && $r->moment !== 'Baile Nupcial';
            });
            if ($party->isNotEmpty()) {
                $this->party_favs = $party->map(function ($r) {
                    return ($r->artist ? $r->artist . ' - ' : '') . $r->title;
                })->implode("\n");
            }

            // 8. Lista Negra
            $blacklist = $requests->filter(function ($r) {
                return $r->category === 'lista_negra';
            });
            if ($blacklist->isNotEmpty()) {
                $this->blacklist = $blacklist->map(function ($r) {
                    return ($r->artist ? $r->artist . ' - ' : '') . $r->title;
                })->implode("\n");
            }
        }

        // 9. Comentarios desde el dossier
        $dossier = $this->event->dossiers()->first();
        if ($dossier && preg_match('/\*\*Comentarios:\*\*\s*(.*?)(\n---|\n\*\*|$)/s', $dossier->content, $matches)) {
            $this->comments = trim($matches[1]);
        }
    }

    protected function parseSong($str)
    {
        $str = trim($str, " \t\n\r\0\x0B-*•");
        $artist = null;
        $title = $str;

        if (str_contains($str, ' - ')) {
            $parts = explode(' - ', $str, 2);
            $artist = trim($parts[0]);
            $title = trim($parts[1]);
        }

        return [
            'title' => $title,
            'artist' => $artist,
        ];
    }

    public function submitForm()
    {
        if ($this->event->is_dossier_completed) {
            return;
        }

        $this->validate([
            'ceremony_songs' => 'nullable|string',
            'cocktail_songs' => 'nullable|string',
            'entrance_song' => 'nullable|string',
            'cake_song' => 'nullable|string',
            'gifts_songs' => 'nullable|string',
            'dance_song' => 'nullable|string',
            'party_favs' => 'nullable|string',
            'blacklist' => 'nullable|string',
            'comments' => 'nullable|string',
        ]);

        // Eliminar las solicitudes previas del cliente para evitar duplicados al re-enviar
        $this->event->musicRequests()->where('requested_by', 'Novios')->delete();

        $maxOrder = EventMusicRequest::where('event_id', $this->event->id)->max('order') ?: 0;

        // 1. Entrada Salón
        if (!empty(trim($this->entrance_song))) {
            $maxOrder++;
            $parsed = $this->parseSong($this->entrance_song);
            $this->event->musicRequests()->create([
                'category' => 'banquete',
                'moment' => 'Entrada Comedor',
                'title' => $parsed['title'],
                'artist' => $parsed['artist'],
                'requested_by' => 'Novios',
                'order' => $maxOrder,
            ]);
        }

        // 2. Tarta
        if (!empty(trim($this->cake_song))) {
            $maxOrder++;
            $parsed = $this->parseSong($this->cake_song);
            $this->event->musicRequests()->create([
                'category' => 'banquete',
                'moment' => 'Corte de Tarta',
                'title' => $parsed['title'],
                'artist' => $parsed['artist'],
                'requested_by' => 'Novios',
                'order' => $maxOrder,
            ]);
        }

        // 3. Regalos / Sorpresas (Procesar línea a línea)
        if (!empty(trim($this->gifts_songs))) {
            $lines = explode("\n", str_replace("\r", "", $this->gifts_songs));
            foreach ($lines as $line) {
                $line = trim($line, " \t\n\r\0\x0B-*•");
                if (empty($line)) continue;

                $moment = 'Regalo / Sorpresa';
                $songPart = $line;
                $reqBy = 'Novios';

                if (str_contains($line, ':')) {
                    $parts = explode(':', $line, 2);
                    $moment = trim($parts[0]);
                    $songPart = trim($parts[1]);
                }

                $parsed = $this->parseSong($songPart);
                $maxOrder++;
                $this->event->musicRequests()->create([
                    'category' => 'banquete',
                    'moment' => $moment ?: 'Regalo / Sorpresa',
                    'title' => $parsed['title'],
                    'artist' => $parsed['artist'],
                    'requested_by' => $reqBy,
                    'order' => $maxOrder,
                ]);
            }
        }

        // 4. Baile Nupcial
        if (!empty(trim($this->dance_song))) {
            $maxOrder++;
            $parsed = $this->parseSong($this->dance_song);
            $this->event->musicRequests()->create([
                'category' => 'baile',
                'moment' => 'Baile Nupcial',
                'title' => $parsed['title'],
                'artist' => $parsed['artist'],
                'requested_by' => 'Novios',
                'order' => $maxOrder,
            ]);
        }

        // 5. Ceremonia
        if (!empty(trim($this->ceremony_songs))) {
            $lines = explode("\n", str_replace("\r", "", $this->ceremony_songs));
            foreach ($lines as $line) {
                $line = trim($line, " \t\n\r\0\x0B-*•");
                if (empty($line)) continue;

                $moment = 'Ceremonia';
                $songPart = $line;
                if (str_contains($line, ':')) {
                    $parts = explode(':', $line, 2);
                    $moment = trim($parts[0]);
                    $songPart = trim($parts[1]);
                }

                $parsed = $this->parseSong($songPart);
                $maxOrder++;
                $this->event->musicRequests()->create([
                    'category' => 'ceremonia',
                    'moment' => $moment ?: 'Ceremonia',
                    'title' => $parsed['title'],
                    'artist' => $parsed['artist'],
                    'requested_by' => 'Novios',
                    'order' => $maxOrder,
                ]);
            }
        }

        // 6. Cóctel
        if (!empty(trim($this->cocktail_songs))) {
            $lines = explode("\n", str_replace("\r", "", $this->cocktail_songs));
            foreach ($lines as $line) {
                $line = trim($line, " \t\n\r\0\x0B-*•");
                if (empty($line)) continue;

                $parsed = $this->parseSong($line);
                $maxOrder++;
                $this->event->musicRequests()->create([
                    'category' => 'coctel',
                    'moment' => 'Estilo Cóctel',
                    'title' => $parsed['title'],
                    'artist' => $parsed['artist'],
                    'requested_by' => 'Novios',
                    'order' => $maxOrder,
                ]);
            }
        }

        // 7. Temazos Fiesta
        if (!empty(trim($this->party_favs))) {
            $lines = explode("\n", str_replace("\r", "", $this->party_favs));
            foreach ($lines as $line) {
                $line = trim($line, " \t\n\r\0\x0B-*•");
                if (empty($line)) continue;

                $parsed = $this->parseSong($line);
                $maxOrder++;
                $this->event->musicRequests()->create([
                    'category' => 'baile',
                    'moment' => 'Temazo Fiesta',
                    'title' => $parsed['title'],
                    'artist' => $parsed['artist'],
                    'requested_by' => 'Novios',
                    'order' => $maxOrder,
                ]);
            }
        }

        // 8. Lista Negra
        if (!empty(trim($this->blacklist))) {
            $lines = explode("\n", str_replace("\r", "", $this->blacklist));
            foreach ($lines as $line) {
                $line = trim($line, " \t\n\r\0\x0B-*•");
                if (empty($line)) continue;

                $parsed = $this->parseSong($line);
                $maxOrder++;
                $this->event->musicRequests()->create([
                    'category' => 'lista_negra',
                    'moment' => 'Prohibida',
                    'title' => $parsed['title'],
                    'artist' => $parsed['artist'],
                    'requested_by' => 'Novios',
                    'order' => $maxOrder,
                ]);
            }
        }

        // 9. Actualizar Dossier General
        $dossierContent = "### Preferencias Musicales Enviadas por el Cliente\n\n";
        if ($this->ceremony_songs) $dossierContent .= "**Ceremonia:** " . $this->ceremony_songs . "\n";
        if ($this->cocktail_songs) $dossierContent .= "**Cóctel:** " . $this->cocktail_songs . "\n";
        if ($this->entrance_song) $dossierContent .= "**Entrada:** " . $this->entrance_song . "\n";
        if ($this->cake_song) $dossierContent .= "**Tarta:** " . $this->cake_song . "\n";
        if ($this->gifts_songs) $dossierContent .= "**Regalos / Entregas:** " . $this->gifts_songs . "\n";
        if ($this->dance_song) $dossierContent .= "**Baile Nupcial:** " . $this->dance_song . "\n";
        if ($this->party_favs) $dossierContent .= "**Temazos Fiesta:** " . $this->party_favs . "\n";
        if ($this->blacklist) $dossierContent .= "**Lista Negra:** " . $this->blacklist . "\n";
        if ($this->comments) $dossierContent .= "**Comentarios:** " . $this->comments . "\n";
        $dossierContent .= "---\n\n";

        $dossier = $this->event->dossiers()->first();
        if ($dossier) {
            $dossier->update([
                'content' => $dossierContent
            ]);
        } else {
            $this->event->dossiers()->create([
                'content' => $dossierContent
            ]);
        }

        $this->event->update(['is_dossier_completed' => true]);
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.guest.event-form')->layout('components.layouts.guest');
    }
}
