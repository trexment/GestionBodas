<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Services\EvolutionWhatsAppService;
use App\Services\GeneralTariffDossierService;
use App\Services\WhatsAppTemplateService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Componente Livewire para la edición, previsualización, exportación en PDF y envío por WhatsApp del Dossier General de Tarifas (incluyendo el Modo Feria de Novios).
 */
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
    public $selected_event_id = '';
    public string $recipient_name = '';
    public string $recipient_phone = '';
    public string $sender_name = 'Fran';
    public string $whatsapp_preview_message = '';

    // Modo Feria de Novios / Mostrador Stand y QR Instantáneo
    public bool $showFairPanel = false;
    public bool $showFullscreenQrModal = false;
    public string $fullscreenQrType = 'pdf'; // 'pdf' | 'presentation' | 'quote'
    public bool $save_as_fair_lead = false;
    public string $fair_lead_date = '';
    public string $fair_lead_location = '';

    /**
     * Inicializa el componente cargando la configuración del dossier de tarifas, sincronizando campos auxiliares de texto y preparando la vista previa de WhatsApp.
     *
     * @return void
     */
    public function mount(): void
    {
        $this->config = GeneralTariffDossierService::getConfig();
        $this->syncTextHelpersFromConfig();

        if (request()->boolean('fair_panel') || !empty($this->config['fair_mode']['enabled'])) {
            $this->showFairPanel = true;
        }

        if (auth()->check() && auth()->user()) {
            $uLower = mb_strtolower((auth()->user()->name ?? '') . ' ' . (auth()->user()->email ?? ''));
            $this->sender_name = str_contains($uLower, 'miguel') ? 'Miguel' : 'Fran';
        }
        $this->refreshWhatsAppPreview();
    }

    /**
     * Convierte los arreglos de datos de la configuración en cadenas de texto multilínea para su edición cómoda en formularios.
     *
     * @return void
     */
    protected function syncTextHelpersFromConfig(): void
    {
        $this->about_facts_text = implode("\n", $this->config['about_facts'] ?? []);
        foreach ([0, 1, 2] as $i) {
            $lines = $this->config['packs'][$i]['includes'] ?? [];
            $this->pack_includes_text[$i] = is_array($lines) ? implode("\n", $lines) : (string) $lines;
        }
    }

    /**
     * Sincroniza los textos multilínea editados por el usuario de vuelta al arreglo estructurado de configuración del dossier.
     *
     * @return void
     */
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

    /**
     * Procesa, comprime y guarda automáticamente la imagen subida para la sección «Quiénes somos».
     *
     * @return void
     */
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

    /**
     * Procesa, comprime y guarda automáticamente la imagen personalizada subida para la portada del dossier.
     *
     * @return void
     */
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

    /**
     * Procesa, comprime y asigna una nueva fotografía subida a un espacio específico de la galería del dossier.
     *
     * @param mixed $value Archivo subido.
     * @param int|string $index Índice del elemento dentro de la galería.
     * @return void
     */
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

    /**
     * Añade un nuevo espacio vacío para fotografía en la galería del dossier (hasta un máximo de 12).
     *
     * @return void
     */
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

    /**
     * Elimina un espacio de fotografía de la galería según su índice y guarda la configuración.
     *
     * @param int $index Índice de la foto en la galería.
     * @return void
     */
    public function removeGallerySlot(int $index): void
    {
        if (isset($this->config['gallery_photos'][$index])) {
            array_splice($this->config['gallery_photos'], $index, 1);
            $this->save(false);
        }
    }

    /**
     * Elimina la fotografía personalizada de portada del dossier.
     *
     * @return void
     */
    public function clearCoverCustomPhoto(): void
    {
        $this->config['cover_custom_photo'] = null;
        $this->save(false);
    }

    /**
     * Elimina la fotografía de la sección «Quiénes somos» del dossier.
     *
     * @return void
     */
    public function clearAboutPhoto(): void
    {
        $this->config['about_photo'] = null;
        $this->save(false);
    }

    /**
     * Añade un nuevo paso vacío al listado de metodología de trabajo del dossier.
     *
     * @return void
     */
    public function addWorkStep(): void
    {
        $this->config['work_steps'][] = [
            'title' => '',
            'description' => '',
            'timeframe' => '',
        ];
    }

    /**
     * Aplica la plantilla recomendada de 7 pasos de trabajo al dossier y guarda los cambios.
     *
     * @return void
     */
    public function applySevenWorkStepsPreset(): void
    {
        $this->config['work_steps'] = GeneralTariffDossierService::getRecommendedSevenWorkSteps();
        $this->save(false);
        session()->flash('dossier_saved', '✅ Aplicada la estructura recomendada de 7 pasos (Consulta y disponibilidad → Propuesta → Reserva con señal en el paso 3).');
    }

    /**
     * Aplica la plantilla compacta de pasos de trabajo al dossier y guarda los cambios.
     *
     * @return void
     */
    public function applyCompactWorkStepsPreset(): void
    {
        $this->config['work_steps'] = GeneralTariffDossierService::getRecommendedCompactWorkSteps();
        $this->save(false);
        session()->flash('dossier_saved', '✅ Aplicada la versión compacta uniendo «Consulta y propuesta» en el Paso 1.');
    }

    /**
     * Actualiza las descripciones de los packs con textos orientativos de sonido e iluminación adaptados al espacio.
     *
     * @return void
     */
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

    /**
     * Elimina un paso de la metodología de trabajo según su índice, manteniendo al menos uno.
     *
     * @param int $index Índice del paso a eliminar.
     * @return void
     */
    public function removeWorkStep(int $index): void
    {
        if (count($this->config['work_steps']) <= 1) {
            return;
        }
        array_splice($this->config['work_steps'], $index, 1);
    }

    /**
     * Añade un nuevo servicio extra vacío a la lista de extras del dossier.
     *
     * @return void
     */
    public function addExtra(): void
    {
        $this->config['extras'][] = [
            'name' => '',
            'description' => '',
            'price' => '',
        ];
    }

    /**
     * Sincroniza la lista de servicios extras y sus precios a partir de los ajustes generales de presupuestos y del proveedor indicado.
     *
     * @param string|null $providerKey Clave del proveedor de zona ('logrono', 'vitoria', etc.).
     * @return void
     */
    public function syncExtrasFromStoredQuotes(?string $providerKey = null): void
    {
        $this->config['extras'] = GeneralTariffDossierService::getRecommendedExtras($providerKey);
        $this->config['extras_section_note'] = GeneralTariffDossierService::getDefaults()['extras_section_note'];
        $this->save(false);
        $zoneLabel = $providerKey === 'vitoria' ? 'Vitoria / Álava' : 'Logroño / La Rioja';
        session()->flash('dossier_saved', "✅ Precios y descripciones orientativas de Extras sincronizados desde los Ajustes de Presupuestos ({$zoneLabel}).");
    }

    /**
     * Elimina un servicio extra de la lista según su índice.
     *
     * @param int $index Índice del servicio extra a eliminar.
     * @return void
     */
    public function removeExtra(int $index): void
    {
        if (isset($this->config['extras'][$index])) {
            array_splice($this->config['extras'], $index, 1);
        }
    }

    /**
     * Añade una nueva reseña vacía de clientes al dossier (hasta un máximo de 5).
     *
     * @return void
     */
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

    /**
     * Elimina una reseña de clientes del dossier según su índice.
     *
     * @param int $index Índice de la reseña a eliminar.
     * @return void
     */
    public function removeReview(int $index): void
    {
        if (isset($this->config['reviews'][$index])) {
            array_splice($this->config['reviews'], $index, 1);
        }
    }

    /**
     * Sincroniza los textos auxiliares y guarda la configuración completa del Dossier General de Tarifas.
     *
     * @param bool $flash Indica si debe mostrarse un mensaje de confirmación en sesión.
     * @return void
     */
    public function save(bool $flash = true): void
    {
        $this->syncConfigFromTextHelpers();
        GeneralTariffDossierService::saveConfig($this->config);
        $this->refreshWhatsAppPreview();

        if ($flash) {
            session()->flash('dossier_saved', '✅ Configuración del Dossier General de Tarifas guardada correctamente.');
        }
    }

    /**
     * Valida la configuración del dossier y redirige a la ruta de generación y descarga del PDF si cumple todos los requisitos.
     *
     * @return mixed
     */
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

    /**
     * Guarda los cambios actuales, actualiza el mensaje de vista previa y abre el modal de envío por WhatsApp.
     *
     * @return void
     */
    public function openSendModal(): void
    {
        $this->save(false);
        $this->refreshWhatsAppPreview();
        $this->showSendModal = true;
    }

    /**
     * Activa o desactiva el Modo Feria de Novios en la configuración del dossier y guarda el estado.
     *
     * @return void
     */
    public function toggleFairMode(): void
    {
        $current = !empty($this->config['fair_mode']['enabled']);
        $this->config['fair_mode']['enabled'] = !$current;
        $this->showFairPanel = true;
        $this->save(false);

        if ($this->config['fair_mode']['enabled']) {
            session()->flash('dossier_saved', '🎪 ¡Modo Feria de Novios ACTIVADO! Tus precios base siguen intactos; ahora el Dossier y los QR muestran el precio anterior tachado y el Descuento Especial de Feria.');
        } else {
            session()->flash('dossier_saved', '🔒 Modo Feria de Novios DESACTIVADO. El Dossier y las tarifas han vuelto al instante a tus precios normales.');
        }
    }

    /**
     * Calcula y aplica precios promocionales simulados para el Modo Feria sin alterar los precios base de los packs.
     *
     * @param string $preset Tipo de cálculo promocional ('fixed_equal', 'percent_15_equal', 'fixed_minus_20').
     * @return void
     */
    public function applyFairPricingPreset(string $preset = 'fixed_equal'): void
    {
        $this->syncConfigFromTextHelpers();
        $pricesIncludeVat = (bool) ($this->config['prices_include_vat'] ?? true);
        $vatRate = (float) ($this->config['vat_rate'] ?? 21);

        foreach ([0, 1, 2] as $idx) {
            $normal = GeneralTariffDossierService::computeFinalPriceWithVat(
                $this->config['packs'][$idx]['price_input'] ?? 0,
                $pricesIncludeVat,
                $vatRate
            );
            $defaultAdd = $idx === 0 ? 100 : ($idx === 1 ? 150 : 200);

            if ($preset === 'percent_15_equal') {
                $inflated = (int) (round(($normal * 1.15) / 10) * 10);
                if ($inflated <= $normal) {
                    $inflated = (int) $normal + $defaultAdd;
                }
                $final = (int) round($normal);
            } elseif ($preset === 'fixed_minus_20') {
                $inflated = (int) round($normal + $defaultAdd);
                $final = (int) max(100, round($normal - 20));
            } else {
                // 'fixed_equal': sube +100/+150/+200 y el precio final con descuento queda exactamente en tu precio real
                $inflated = (int) round($normal + $defaultAdd);
                $final = (int) round($normal);
            }

            $this->config['fair_mode']['packs_pricing'][$idx]['inflated_price'] = $inflated;
            $this->config['fair_mode']['packs_pricing'][$idx]['fair_final_price'] = $final;
        }

        $this->save(false);
        session()->flash('dossier_saved', '✅ Precios simulados de Feria calculados sin modificar tus precios reales.');
    }

    /**
     * Abre el modal a pantalla completa con el código QR del tipo indicado para mostrar en el stand de feria.
     *
     * @param string $type Tipo de enlace QR ('pdf', 'presentation' o 'quote').
     * @return void
     */
    public function openFullscreenQr(string $type = 'pdf'): void
    {
        $this->fullscreenQrType = in_array($type, ['pdf', 'presentation', 'quote'], true) ? $type : 'pdf';
        $this->showFullscreenQrModal = true;
    }

    /**
     * Crea opcionalmente un cliente y evento de seguimiento cuando se envía el dossier a una pareja atendida en feria.
     *
     * @return int|null Identificador del evento existente o recién creado.
     */
    protected function maybeCreateFairLeadEvent(): ?int
    {
        if (!$this->save_as_fair_lead || !empty($this->selected_event_id)) {
            return !empty($this->selected_event_id) ? (int) $this->selected_event_id : null;
        }

        $name = trim($this->recipient_name);
        $phone = trim($this->recipient_phone);
        if ($name === '' && $phone === '') {
            return null;
        }

        try {
            $cleanPhone = WhatsAppTemplateService::cleanPhone($phone);
            $client = null;
            if ($cleanPhone !== '') {
                $last9 = substr($cleanPhone, -9);
                $client = \App\Models\User::where('role', 'client')
                    ->where('phone', 'like', '%' . $last9 . '%')
                    ->first();
            }

            if (!$client) {
                $client = \App\Models\User::create([
                    'name' => $name !== '' ? $name : ('Novios Feria ' . substr($cleanPhone, -4)),
                    'email' => 'feria_' . uniqid() . '@pendiente.local',
                    'phone' => $phone ?: null,
                    'password' => bcrypt(\Illuminate\Support\Str::random(12)),
                    'role' => 'client',
                ]);
            }

            $fairName = trim((string) ($this->config['fair_mode']['fair_name'] ?? 'Feria de Novios')) ?: 'Feria de Novios';
            $eventDate = !empty($this->fair_lead_date) ? $this->fair_lead_date : now()->addMonths(8)->format('Y-m-d');

            $ev = Event::create([
                'name' => 'Boda ' . ($name !== '' ? $name : $client->name),
                'event_type' => 'boda',
                'brand' => $this->config['brand'] ?? 'nunez_and_son',
                'event_date' => $eventDate,
                'location' => !empty(trim($this->fair_lead_location)) ? trim($this->fair_lead_location) : 'Por definir (' . $fairName . ')',
                'status' => 'pending',
                'client_id' => $client->id,
                'notes' => '[' . now()->format('d/m/Y H:i') . '] 🎪 Pareja atendida en stand de ' . $fairName . '. Dossier enviado por WhatsApp.',
            ]);

            return $ev->id;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Autocompleta el nombre y teléfono del destinatario cuando se selecciona un evento existente en el modal de envío.
     *
     * @param mixed $val Identificador del evento seleccionado.
     * @return void
     */
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

    /**
     * Actualiza la vista previa del mensaje de WhatsApp cuando cambia el nombre del destinatario.
     *
     * @return void
     */
    public function updatedRecipientName(): void
    {
        $this->refreshWhatsAppPreview();
    }

    /**
     * Actualiza la vista previa del mensaje de WhatsApp cuando cambia el nombre del remitente.
     *
     * @return void
     */
    public function updatedSenderName(): void
    {
        $this->refreshWhatsAppPreview();
    }

    /**
     * Regenera el texto del mensaje de WhatsApp para enviar el dossier con los enlaces e información actuales.
     *
     * @return void
     */
    public function refreshWhatsAppPreview(): void
    {
        $this->whatsapp_preview_message = GeneralTariffDossierService::buildWhatsAppMessage(
            $this->recipient_name,
            $this->sender_name,
            $this->config,
            true
        );
    }

    /**
     * Registra opcionalmente el lead de feria y abre WhatsApp Web o la aplicación con el mensaje del dossier preparado.
     *
     * @return void
     */
    public function sendViaWhatsAppWeb(): void
    {
        $this->maybeCreateFairLeadEvent();
        $url = WhatsAppTemplateService::buildUrl($this->recipient_phone, $this->whatsapp_preview_message);
        $this->dispatch('open-whatsapp-link', url: $url);
    }

    /**
     * Envía el mensaje con el dossier directamente a través del Gateway de WhatsApp (Evolution API) y registra el lead si procede.
     *
     * @return void
     */
    public function sendDirectEvolutionWhatsApp(): void
    {
        if (empty(trim($this->recipient_phone))) {
            session()->flash('wa_send_error', 'Indica un número de teléfono para enviar el dossier.');
            return;
        }

        try {
            $brand = mb_strtolower(trim($this->sender_name)) === 'fran' ? 'fran' : 'miguel';
            $eventId = $this->maybeCreateFairLeadEvent();

            $res = EvolutionWhatsAppService::sendMessage(
                $this->recipient_phone,
                $this->whatsapp_preview_message,
                $brand,
                $eventId,
                auth()->check() ? auth()->id() : null
            );

            $leadSuffix = ($this->save_as_fair_lead && $eventId) ? ' (y pareja guardada en Eventos para seguimiento).' : '.';

            if (!empty($res['success'])) {
                if (($res['mode'] ?? '') === 'manual' && !empty($res['url'])) {
                    $this->dispatch('open-whatsapp-link', url: $res['url']);
                    session()->flash('wa_send_ok', '📲 Gateway directo no activo: abriendo WhatsApp con el mensaje preparado' . $leadSuffix);
                } else {
                    session()->flash('wa_send_ok', '✅ Dossier de tarifas enviado por WhatsApp correctamente desde el número de ' . $this->sender_name . $leadSuffix);
                }
            } else {
                $errDetail = $res['message'] ?? ($res['error'] ?? 'Error de conexión con el Gateway');
                session()->flash('wa_send_error', '⚠️ No se pudo enviar por API directa (' . $errDetail . '). Puedes pulsar «📲 Abrir en WhatsApp».');
            }
        } catch (\Throwable $e) {
            session()->flash('wa_send_error', '⚠️ Error al enviar por Gateway (' . $e->getMessage() . '). Usa el botón «📲 Abrir en WhatsApp».');
        }
    }

    /**
     * Obtiene el informe de validación de los requisitos del dossier antes de su exportación.
     *
     * @return array<string, mixed>
     */
    public function getValidationReportProperty(): array
    {
        $this->syncConfigFromTextHelpers();
        return GeneralTariffDossierService::validateConfig($this->config);
    }

    /**
     * Valida el estado actual del dossier y renderiza la vista de administración del Dossier General de Tarifas.
     *
     * @return \Illuminate\Contracts\View\View
     */
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
