<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Services\EvolutionWhatsAppService;
use App\Services\GeneralTariffDossierService;
use App\Services\WhatsAppTemplateService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class TariffDossierManager extends Component
{
    use WithFileUploads;

    public array $config = [];
    public string $activeSection = 'all';

    // Subidas temporales de imágenes
    public $about_photo_upload = null;
    public $cover_custom_photo_upload = null;
    public array $gallery_uploads = [];

    // textos auxiliares para editar listas línea por línea cómodamente
    public string $about_facts_text = '';
    public array $pack_includes_text = ['', '', ''];

    // Modal de envío por WhatsApp
    public bool $showSendModal = false;
    public ?int $selected_event_id = null;
    public string $recipient_name = '';
    public string $recipient_phone = '';
    public string $sender_name = 'Fran';
    public string $whatsapp_preview_message = '';

    public function mount(): void
    {
        $this->config = GeneralTariffDossierService::getConfig();
        $this->syncTextHelpersFromConfig();

        if (auth()->check() && auth()->user()) {
            $uLower = mb_strtolower((auth()->user()->name ?? '') . ' ' . (auth()->user()->email ?? ''));
            $this->sender_name = str_contains($uLower, 'miguel') ? 'Miguel' : 'Fran';
        }
        $this->refreshWhatsAppPreview();
    }

    protected function syncTextHelpersFromConfig(): void
    {
        $this->about_facts_text = implode("\n", $this->config['about_facts'] ?? []);
        foreach ([0, 1, 2] as $i) {
            $lines = $this->config['packs'][$i]['includes'] ?? [];
            $this->pack_includes_text[$i] = is_array($lines) ? implode("\n", $lines) : (string) $lines;
        }
    }

    protected function syncConfigFromTextHelpers(): void
    {
        $this->config['about_facts'] = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $this->about_facts_text)),
            fn($l) => $l !== ''
        ));

        foreach ([0, 1, 2] as $i) {
            $raw = $this->pack_includes_text[$i] ?? '';
            $this->config['packs'][$i]['includes'] = array_values(array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', (string) $raw)),
                fn($l) => $l !== ''
            ));
            $this->config['packs'][$i]['featured'] = ($i === 1);
        }
    }

    public function updatedAboutPhotoUpload(): void
    {
        if ($this->about_photo_upload) {
            $path = GeneralTariffDossierService::processAndCompressImage($this->about_photo_upload, 'equipo');
            if ($path) {
                $this->config['about_photo'] = $path;
                $this->about_photo_upload = null;
                $this->save(false);
                session()->flash('dossier_saved', 'Foto de «Quiénes somos» convertida a .jpg, comprimida y guardada.');
            }
        }
    }

    public function updatedCoverCustomPhotoUpload(): void
    {
        if ($this->cover_custom_photo_upload) {
            $path = GeneralTariffDossierService::processAndCompressImage($this->cover_custom_photo_upload, 'portada');
            if ($path) {
                $this->config['cover_custom_photo'] = $path;
                $this->cover_custom_photo_upload = null;
                $this->save(false);
                session()->flash('dossier_saved', 'Foto de portada convertida a .jpg, comprimida y guardada.');
            }
        }
    }

    public function updatedGalleryUploads($value, $index): void
    {
        if (!empty($this->gallery_uploads[$index])) {
            $path = GeneralTariffDossierService::processAndCompressImage($this->gallery_uploads[$index], 'galeria_' . ($index + 1));
            if ($path) {
                $this->config['gallery_photos'][$index]['image_path'] = $path;
                $this->config['gallery_photos'][$index]['has_permission'] = true;
                unset($this->gallery_uploads[$index]);
                $this->save(false);
                session()->flash('dossier_saved', 'Foto #' . ($index + 1) . ' convertida a .jpg, comprimida y añadida a la galería.');
            }
        }
    }

    public function addGallerySlot(): void
    {
        if (count($this->config['gallery_photos'] ?? []) >= 12) {
            return;
        }
        $this->config['gallery_photos'][] = [
            'image_path' => null,
            'wedding_name' => '',
            'wedding_date' => '',
            'has_permission' => false,
            'caption' => '',
        ];
    }

    public function removeGallerySlot(int $index): void
    {
        if (isset($this->config['gallery_photos'][$index])) {
            array_splice($this->config['gallery_photos'], $index, 1);
            $this->save(false);
        }
    }

    public function clearCoverCustomPhoto(): void
    {
        $this->config['cover_custom_photo'] = null;
        $this->save(false);
    }

    public function clearAboutPhoto(): void
    {
        $this->config['about_photo'] = null;
        $this->save(false);
    }

    public function addWorkStep(): void
    {
        $this->config['work_steps'][] = [
            'title' => '',
            'description' => '',
            'timeframe' => '',
        ];
    }

    public function applySevenWorkStepsPreset(): void
    {
        $this->config['work_steps'] = GeneralTariffDossierService::getRecommendedSevenWorkSteps();
        $this->save(false);
        session()->flash('dossier_saved', '✅ Aplicada la estructura recomendada de 7 pasos (Consulta y disponibilidad → Propuesta → Reserva con señal en el paso 3).');
    }

    public function applyCompactWorkStepsPreset(): void
    {
        $this->config['work_steps'] = GeneralTariffDossierService::getRecommendedCompactWorkSteps();
        $this->save(false);
        session()->flash('dossier_saved', '✅ Aplicada la versión compacta uniendo «Consulta y propuesta» en el Paso 1.');
    }

    public function applyOrientativeLightingToPacks(): void
    {
        $this->syncConfigFromTextHelpers();
        $defaults = GeneralTariffDossierService::getDefaults();
        $this->config['packs_section_note'] = $defaults['packs_section_note'];

        if (isset($this->config['packs'][0]['includes']) && is_array($this->config['packs'][0]['includes'])) {
            foreach ($this->config['packs'][0]['includes'] as &$line) {
                if (str_contains((string) $line, 'focos LED') || str_contains((string) $line, 'Iluminación')) {
                    $line = 'Iluminación de pista con focos LED, barras y efectos dinámicos (montaje orientativo adaptado al espacio)';
                }
                if (str_contains((string) $line, 'altavoces') || str_contains((string) $line, 'Sonido profesional')) {
                    $line = 'Sonido profesional de alta definición dimensionado al salón o espacio (orientativo hasta 120 inv.)';
                }
            }
        }
        $this->syncTextHelpersFromConfig();
        $this->save(false);
        session()->flash('dossier_saved', '✅ Aplicada la descripción de iluminación y sonido orientativos según el espacio en los packs.');
    }

    public function removeWorkStep(int $index): void
    {
        if (count($this->config['work_steps']) <= 1) {
            return;
        }
        array_splice($this->config['work_steps'], $index, 1);
    }

    public function addExtra(): void
    {
        $this->config['extras'][] = [
            'name' => '',
            'description' => '',
            'price' => '',
        ];
    }

    public function syncExtrasFromStoredQuotes(?string $providerKey = null): void
    {
        $this->config['extras'] = GeneralTariffDossierService::getRecommendedExtras($providerKey);
        $this->config['extras_section_note'] = GeneralTariffDossierService::getDefaults()['extras_section_note'];
        $this->save(false);
        $zoneLabel = $providerKey === 'vitoria' ? 'Vitoria / Álava' : 'Logroño / La Rioja';
        session()->flash('dossier_saved', "✅ Precios y descripciones orientativas de Extras sincronizados desde los Ajustes de Presupuestos ({$zoneLabel}).");
    }

    public function removeExtra(int $index): void
    {
        if (isset($this->config['extras'][$index])) {
            array_splice($this->config['extras'], $index, 1);
        }
    }

    public function addReview(): void
    {
        if (count($this->config['reviews'] ?? []) >= 5) {
            return;
        }
        $this->config['reviews'][] = [
            'couple_name' => '',
            'location' => '',
            'event_date' => '',
            'text' => '',
            'has_permission' => true,
        ];
    }

    public function removeReview(int $index): void
    {
        if (isset($this->config['reviews'][$index])) {
            array_splice($this->config['reviews'], $index, 1);
        }
    }

    public function save(bool $flash = true): void
    {
        $this->syncConfigFromTextHelpers();
        GeneralTariffDossierService::saveConfig($this->config);
        $this->refreshWhatsAppPreview();

        if ($flash) {
            session()->flash('dossier_saved', '✅ Configuración del Dossier General de Tarifas guardada correctamente.');
        }
    }

    public function exportPdf()
    {
        $this->save(false);
        $report = GeneralTariffDossierService::validateConfig($this->config);

        if (!$report['can_export']) {
            session()->flash(
                'dossier_error',
                '⚠️ No se puede generar el PDF todavía. Revisa los puntos marcados en rojo en el checklist de validación.'
            );
            return;
        }

        return redirect()->route('admin.tariff-dossier.pdf', [
            'brand' => $this->config['brand'] ?? 'nunez_and_son',
        ]);
    }

    public function openSendModal(): void
    {
        $this->save(false);
        $this->refreshWhatsAppPreview();
        $this->showSendModal = true;
    }

    public function updatedSelectedEventId($val): void
    {
        if ($val) {
            $ev = Event::with('client')->find($val);
            if ($ev) {
                $this->recipient_name = WhatsAppTemplateService::cleanClientDisplayName(
                    $ev->client ? $ev->client->name : $ev->partner_name,
                    '',
                    $ev
                );
                $this->recipient_phone = $ev->client ? ($ev->client->phone ?? '') : ($ev->partner_phone ?? '');
            }
        }
        $this->refreshWhatsAppPreview();
    }

    public function updatedRecipientName(): void
    {
        $this->refreshWhatsAppPreview();
    }

    public function updatedSenderName(): void
    {
        $this->refreshWhatsAppPreview();
    }

    public function refreshWhatsAppPreview(): void
    {
        $this->whatsapp_preview_message = GeneralTariffDossierService::buildWhatsAppMessage(
            $this->recipient_name,
            $this->sender_name,
            $this->config,
            true
        );
    }

    public function sendViaWhatsAppWeb(): void
    {
        $url = WhatsAppTemplateService::buildUrl($this->recipient_phone, $this->whatsapp_preview_message);
        $this->dispatch('open-whatsapp-link', url: $url);
    }

    public function sendDirectEvolutionWhatsApp(): void
    {
        if (empty(trim($this->recipient_phone))) {
            session()->flash('wa_send_error', 'Indica un número de teléfono para enviar el dossier.');
            return;
        }

        $brand = strtolower($this->sender_name) === 'fran' ? 'fran' : 'miguel';
        $res = EvolutionWhatsAppService::sendTextMessage(
            $this->recipient_phone,
            $this->whatsapp_preview_message,
            $brand,
            $this->selected_event_id ? (int) $this->selected_event_id : null,
            auth()->id()
        );

        if (!empty($res['success'])) {
            session()->flash('wa_send_ok', '✅ Dossier de tarifas enviado por WhatsApp correctamente.');
        } else {
            session()->flash('wa_send_error', '⚠️ No se pudo enviar por API directa (' . ($res['error'] ?? 'Error') . '). Usa el botón de abrir en WhatsApp.');
        }
    }

    public function getValidationReportProperty(): array
    {
        $this->syncConfigFromTextHelpers();
        return GeneralTariffDossierService::validateConfig($this->config);
    }

    public function render()
    {
        $this->syncConfigFromTextHelpers();
        $report = GeneralTariffDossierService::validateConfig($this->config);

        $recentEvents = Event::with('client')
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->latest()
            ->take(40)
            ->get();

        return view('livewire.admin.tariff-dossier-manager', [
            'report' => $report,
            'recentEvents' => $recentEvents,
        ])->layout('components.layouts.app', [
            'header' => 'Dossier General de Tarifas',
        ]);
    }
}
