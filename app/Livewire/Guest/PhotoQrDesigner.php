<?php

namespace App\Livewire\Guest;

use App\Models\Event;
use App\Services\FotosEventosQrService;
use App\Services\QrPosterPdfService;
use Livewire\Component;

/**
 * Componente Livewire público para diseñar, personalizar y descargar los carteles PDF del álbum de fotos QR del evento.
 */
class PhotoQrDesigner extends Component
{
    public string $token;
    public Event $event;

    public string $layout = 'a4';
    public string $style = 'elegante';
    public string $names = '';
    public string $initials = '';
    public bool $show_initials = true;
    public string $kicker = '';
    public string $footer = '';
    public string $preset = 'directa';
    public string $headline = '';
    public string $subtitle = '';
    public bool $show_url = true;

    public bool $has_saved_custom = false;
    public ?string $statusMessage = null;

    /**
     * Inicializa el diseñador de carteles QR localizando el evento, sincronizando su álbum si es necesario y cargando la configuración.
     */
    public function mount(string $token): void
    {
        $this->token = $token;
        $found = Event::findByTokenOrPrettySlug($token);
        if (!$found) {
            abort(404);
        }
        $this->event = $found;

        $albumInfo = FotosEventosQrService::getEventAlbumInfo($this->event);
        if ((!$albumInfo || empty($albumInfo['guest_url'])) && FotosEventosQrService::isConfigured()) {
            $syncRes = FotosEventosQrService::createOrSyncEventAlbum($this->event);
            if (!empty($syncRes['success'])) {
                $this->event->refresh();
            }
        }

        $defaults = QrPosterPdfService::defaultsForEvent($this->event);
        $this->hydrateFromDefaults($defaults);
    }

    /**
     * @param array<string, mixed> $defaults
     */
    protected function hydrateFromDefaults(array $defaults): void
    {
        $this->layout = (string) ($defaults['layout'] ?? 'a4');
        $this->style = (string) ($defaults['style'] ?? 'elegante');
        $this->names = (string) ($defaults['names'] ?? '');
        $this->initials = (string) ($defaults['initials'] ?? '');
        $this->show_initials = (bool) ($defaults['show_initials'] ?? true);
        $this->kicker = (string) ($defaults['kicker'] ?? '');
        $this->footer = (string) ($defaults['footer'] ?? '');
        $this->preset = (string) ($defaults['preset'] ?? 'directa');
        $this->headline = (string) ($defaults['headline'] ?? '');
        $this->subtitle = (string) ($defaults['subtitle'] ?? '');
        $this->show_url = (bool) ($defaults['show_url'] ?? true);
        $this->has_saved_custom = (bool) ($defaults['has_saved_custom'] ?? false);
    }

    /**
     * Actualiza el titular y subtítulo cuando el cliente selecciona una frase predefinida.
     */
    public function updatedPreset(string $value): void
    {
        $presets = QrPosterPdfService::presetPhrases();
        if (isset($presets[$value])) {
            $this->headline = $presets[$value]['headline'];
            $this->subtitle = $presets[$value]['subtitle'];
        }
        $this->statusMessage = null;
    }

    /**
     * Cambia el selector de frase a personalizado si el cliente edita manualmente el titular.
     */
    public function updatedHeadline(): void
    {
        $presets = QrPosterPdfService::presetPhrases();
        if ($this->preset !== 'custom' && isset($presets[$this->preset])) {
            if (trim($this->headline) !== $presets[$this->preset]['headline']) {
                $this->preset = 'custom';
            }
        }
        $this->statusMessage = null;
    }

    /**
     * Cambia el selector de frase a personalizado si el cliente edita manualmente el subtítulo.
     */
    public function updatedSubtitle(): void
    {
        $presets = QrPosterPdfService::presetPhrases();
        if ($this->preset !== 'custom' && isset($presets[$this->preset])) {
            if (trim($this->subtitle) !== $presets[$this->preset]['subtitle']) {
                $this->preset = 'custom';
            }
        }
        $this->statusMessage = null;
    }

    /**
     * Selecciona el formato de disposición de página (A4, 2xA5 o 4xA6) para el cartel QR.
     */
    public function selectLayout(string $layout): void
    {
        if (isset(QrPosterPdfService::layouts()[$layout])) {
            $this->layout = $layout;
            $this->statusMessage = null;
        }
    }

    /**
     * Selecciona el estilo visual y paleta cromática del cartel QR.
     */
    public function selectStyle(string $style): void
    {
        if (isset(QrPosterPdfService::visualStyles()[$style])) {
            $this->style = $style;
            $this->statusMessage = null;
        }
    }

    /**
     * Guarda en la configuración del evento el diseño personalizado actual del cartel QR.
     */
    public function saveDesign(): void
    {
        $saved = QrPosterPdfService::saveCustomizationForEvent($this->event, $this->currentPayload(), 'client');
        $this->hydrateFromDefaults($saved);
        $this->statusMessage = '¡Vuestro diseño ha quedado guardado! Podéis descargarlo en PDF ahora o volver cuando queráis.';
    }

    /**
     * Restaura los textos y opciones de diseño del cartel QR a los valores sugeridos por defecto.
     */
    public function resetDefaults(): void
    {
        $defaults = QrPosterPdfService::resetCustomizationForEvent($this->event);
        $this->hydrateFromDefaults($defaults);
        $this->statusMessage = 'Se han restaurado los textos y el diseño original sugerido.';
    }

    /**
     * @return array<string, mixed>
     */
    protected function currentPayload(): array
    {
        return [
            'layout' => $this->layout,
            'style' => $this->style,
            'names' => $this->names,
            'initials' => $this->initials,
            'show_initials' => $this->show_initials,
            'kicker' => $this->kicker,
            'footer' => $this->footer,
            'preset' => $this->preset,
            'headline' => $this->headline,
            'subtitle' => $this->subtitle,
            'show_url' => $this->show_url,
        ];
    }

    /**
     * Construye la URL hacia la ruta de generación del cartel PDF para previsualización o descarga directa.
     */
    protected function buildPdfRoute(bool $download): string
    {
        return route('pdf.photo-qr-poster', [
            'event' => $this->event->id,
            'token' => $this->event->token,
            'layout' => $this->layout,
            'style' => $this->style,
            'names' => $this->names,
            'initials' => $this->initials,
            'show_initials' => $this->show_initials ? '1' : '0',
            'kicker' => $this->kicker,
            'preset' => $this->preset,
            'headline' => $this->headline,
            'subtitle' => $this->subtitle,
            'footer' => $this->footer,
            'show_url' => $this->show_url ? '1' : '0',
            'save' => '1',
            'download' => $download ? '1' : '0',
        ]);
    }

    /**
     * Renderiza la vista interactiva del diseñador de carteles QR con su previsualización SVG.
     */
    public function render()
    {
        $albumInfo = FotosEventosQrService::getEventAlbumInfo($this->event);
        $guestUrl = (string) ($albumInfo['guest_url'] ?? '');
        $shortDisplayUrl = $guestUrl !== '' ? (string) preg_replace('#^https?://#i', '', $guestUrl) : '';

        $styles = QrPosterPdfService::visualStyles();
        $presets = QrPosterPdfService::presetPhrases();
        $theme = $styles[$this->style] ?? $styles['elegante'];

        $qrSvg = $guestUrl !== ''
            ? QrPosterPdfService::toStylizedSvg($guestUrl, [
                'initials' => $this->show_initials ? $this->initials : '',
                'style' => $this->style,
            ])
            : '';

        return view('livewire.guest.photo-qr-designer', [
            'albumInfo' => $albumInfo,
            'guestUrl' => $guestUrl,
            'shortDisplayUrl' => $shortDisplayUrl,
            'styles' => $styles,
            'presets' => $presets,
            'theme' => $theme,
            'qrSvg' => $qrSvg,
            'downloadPdfUrl' => $this->buildPdfRoute(true),
            'previewPdfUrl' => $this->buildPdfRoute(false),
        ])->layout('components.layouts.guest');
    }
}
