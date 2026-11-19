<?php

namespace App\Livewire\Guest;

use App\Models\Event;
use App\Services\FotosEventosQrService;
use App\Services\QrPosterPdfService;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Modo Pantalla Completa / Tablet para el Álbum de Fotos QR.
 * Diseñado específicamente para tablets (Lenovo Tab 12, iPad) o pantallas de TV en eventos,
 * permitiendo proyectar el código QR en grande con contador en vivo y WakeLock anti-apagado.
 */
class PhotoQrScreen extends Component
{
    public ?string $token = null;
    public Event $event;

    public string $theme = 'dark'; // 'dark' | 'light' | 'gold'
    public string $style = 'elegante';
    public bool $kioskMode = false;
    public ?int $photosCount = null;
    public bool $isActive = true;

    public string $names = '';
    public string $initials = '';
    public string $dateFormatted = '';
    public string $location = '';
    public string $headline = '';
    public string $subtitle = '';
    public string $guestUrl = '';
    public ?string $driveUrl = null;

    /**
     * Inicializa la pantalla para un evento a través de su token público o ID.
     */
    public function mount(?string $token = null, ?Event $event = null): void
    {
        if ($event instanceof Event && $event->exists) {
            $this->event = $event;
            $this->token = (string) $event->token;
        } else {
            $this->token = (string) $token;
            $found = Event::findByTokenOrPrettySlug($this->token);
            if (!$found) {
                abort(404, 'Evento no encontrado');
            }
            $this->event = $found;
        }

        $this->loadEventData();
    }

    /**
     * Carga y sincroniza la información del álbum y textos personalizados.
     */
    public function loadEventData(): void
    {
        // 1. Sincronizar / obtener datos del álbum QR
        $albumInfo = FotosEventosQrService::getEventAlbumInfo($this->event);
        if ((!$albumInfo || empty($albumInfo['guest_url'])) && FotosEventosQrService::isConfigured()) {
            $syncRes = FotosEventosQrService::createOrSyncEventAlbum($this->event);
            if (!empty($syncRes['success'])) {
                $this->event->refresh();
                $albumInfo = FotosEventosQrService::getEventAlbumInfo($this->event);
            }
        }

        $this->guestUrl = (string) ($albumInfo['guest_url'] ?? '');
        $this->driveUrl = $albumInfo['drive_url'] ?? null;
        $this->isActive = (bool) ($albumInfo['is_active'] ?? true);

        // Fallback si no hay álbum configurado aún: portal del invitado
        if ($this->guestUrl === '') {
            $this->guestUrl = route('guest.form', ['token' => $this->event->token]);
        }

        // 2. Nombres, iniciales y textos del evento
        $defaults = QrPosterPdfService::defaultsForEvent($this->event);
        $this->style = (string) ($defaults['style'] ?? 'elegante');
        $this->names = (string) ($defaults['names'] ?? $this->event->name);
        $this->initials = (string) ($defaults['initials'] ?? '');
        $this->headline = (string) ($defaults['headline'] ?? '¡Sube tus fotos de hoy al álbum de los novios!');
        $this->subtitle = (string) ($defaults['subtitle'] ?? 'Escanea con tu cámara y comparte tus mejores momentos en calidad original.');

        if (!empty($this->event->event_date)) {
            $cDate = Carbon::parse($this->event->event_date);
            $this->dateFormatted = ucfirst($cDate->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY'));
        }
        $this->location = (string) ($this->event->location ?? '');

        // 3. Consultar estadísticas de fotos
        $this->refreshPhotoCount();
    }

    /**
     * Consulta el número de fotos actualizadas en Google Drive (polling ligero).
     */
    public function refreshPhotoCount(): void
    {
        if (FotosEventosQrService::isConfigured()) {
            $res = FotosEventosQrService::fetchAlbumStatus($this->event);
            if (!empty($res['success']) && isset($res['event']['photos_count'])) {
                $this->photosCount = (int) $res['event']['photos_count'];
                $this->isActive = (bool) ($res['event']['is_active'] ?? true);
            }
        }
    }

    /**
     * Alterna el tema visual de la pantalla.
     */
    public function setTheme(string $theme): void
    {
        if (in_array($theme, ['dark', 'light', 'gold'], true)) {
            $this->theme = $theme;
        }
    }

    /**
     * Alterna el modo kiosko (oculta barras de control para dejar solo la presentación).
     */
    public function toggleKiosk(): void
    {
        $this->kioskMode = !$this->kioskMode;
    }

    /**
     * Renderiza la vista de pantalla completa.
     */
    public function render()
    {
        $qrSvg = QrPosterPdfService::toStylizedSvg($this->guestUrl, [
            'initials' => $this->initials,
            'style' => $this->style,
        ]);

        $shortDisplayUrl = preg_replace('#^https?://#i', '', $this->guestUrl);

        return view('livewire.guest.photo-qr-screen', [
            'qrSvg' => $qrSvg,
            'shortDisplayUrl' => $shortDisplayUrl,
        ])->layout('components.layouts.wide-guest');
    }
}
