<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Contract;
use App\Models\Event;
use App\Models\Setting;
use App\Services\ContractTemplateService;
use App\Services\GeneralTariffDossierService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    /**
     * Verify that the requester is an authorized staff member or possesses a valid event/contract token.
     */
    protected function authorizePdfAccess(?Event $event = null, ?string $contractToken = null): void
    {
        // 1. Authenticated staff (admin, dj, assistant) or client owner
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->role === 'admin') {
                return;
            }
            if ($event) {
                $isAssignedAssistant = false;
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                        $isAssignedAssistant = $event->assistants()->where('users.id', $user->id)->exists();
                    }
                } catch (\Throwable $e) {}

                if ($event->dj_id == $user->id || $event->assistant_id == $user->id || $isAssignedAssistant || $event->client_id == $user->id) {
                    return;
                }
            }
        }

        // 2. Secret Token Authentication (client portal token, contract token or Live DJ Mode staff token).
        // The guest QR token (guest_token) is intentionally NOT accepted here.
        $providedToken = (string) (request()->query('token') ?: request()->header('X-Live-Token') ?: session('live_event_token'));
        if ($providedToken !== '') {
            if ($contractToken && hash_equals((string) $contractToken, $providedToken)) {
                return;
            }
            if ($event && $event->matchesClientOrStaffToken($providedToken)) {
                return;
            }
            if ($event && $event->contracts()->where('token', $providedToken)->exists()) {
                return;
            }
        }

        abort(403, 'Acceso denegado: Se requiere autenticación o token de acceso válido.');
    }

    public function downloadInvoice(Invoice $invoice)
    {
        $this->authorizePdfAccess($invoice->event);

        $invoice->load(['event.client', 'quote.items']);

        $brandKey = $invoice->event ? ($invoice->event->brand_clean ?? null) : null;
        $brand = Setting::getBrandInfo($brandKey);

        $company = [
            'name' => $brand['name'] ?: Setting::getCompanyName('Núñez and Son'),
            'subtitle' => $brand['subtitle'] ?: Setting::get('company_subtitle', 'Sound in Motion'),
            'cif' => Setting::get('company_cif', '78902362B'),
            'phone' => $brand['phone'] ?: Setting::get('company_phone', '+34 622 62 47 90'),
            'phone_2' => $brand['phone_2'] ?: Setting::get('company_phone_2', ''),
            'email' => $brand['email'] ?: Setting::get('company_email', 'info@nunezandson.com'),
            'website' => $brand['website'] ?: Setting::get('company_website', 'nunezandson.com'),
            'address' => Setting::get('company_address', 'Calle Principal s/n'),
            'city' => Setting::get('company_city', 'Logroño'),
            'iban' => Setting::get('company_iban', 'ES39 3035 0241 13 2411043571'),
            'bizum' => Setting::get('company_bizum', '622634790'),
            'logo' => $brand['logo_path'] ?: Setting::getLogoPathForPdf(),
        ];

        $data = [
            'invoice' => $invoice,
            'event' => $invoice->event,
            'client' => $invoice->event ? $invoice->event->client : null,
            'quote' => $invoice->quote,
            'company' => $company,
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);
        $filenamePrefix = $invoice->isReceipt() ? 'recibo_' : 'factura_';
        return $pdf->download($filenamePrefix . \Illuminate\Support\Str::slug($invoice->invoice_number) . '.pdf');
    }

    public function downloadQuote(Quote $quote)
    {
        $this->authorizePdfAccess($quote->event);

        $quote->load(['event.client', 'items']);
        \App\Models\Quote::repairCorruptedPackDiscounts($quote);

        $isSinglePage = request()->boolean('single') || request('mode') === 'single';

        $data = [
            'quote' => $quote,
            'event' => $quote->event,
            'client' => $quote->event ? $quote->event->client : null,
            'isSinglePage' => $isSinglePage,
        ];

        $eventName = $quote->event ? $quote->event->name : 'Evento';
        $safeName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $eventName);
        $safeName = trim(preg_replace('/\s+/', ' ', $safeName));
        $prefix = $isSinglePage ? 'Presupuesto_1Pag_' : 'Dossier_Presupuesto_';
        $filename = $prefix . ($safeName ?: 'Evento_' . $quote->id) . '.pdf';

        $pdf = Pdf::loadView('pdf.quote', $data);
        return $pdf->download($filename);
    }
    
    public function downloadContract(Contract $contract)
    {
        $this->authorizePdfAccess($contract->event, $contract->token);

        $contract->load('event.client', 'event.quotes');
        
        $renderedData = ContractTemplateService::renderContract($contract);
        $renderedData['company_logo'] = Setting::get('company_logo');

        $eventName = $contract->event ? $contract->event->name : 'Evento';
        $safeName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $eventName);
        $safeName = trim(preg_replace('/\s+/', ' ', $safeName));
        $filename = 'Contrato ' . ($safeName ?: 'Evento_' . $contract->id) . '.pdf';

        $pdf = Pdf::loadView('pdf.contract', $renderedData);
        return $pdf->download($filename);
    }

    public function downloadPackingList(Event $event)
    {
        $this->authorizePdfAccess($event);

        $event->load(['client', 'dj', 'assistant', 'equipment']);

        $equipmentGrouped = $event->equipment->groupBy('category');

        $data = [
            'event' => $event,
            'client' => $event->client,
            'dj' => $event->dj,
            'assistant' => $event->assistant,
            'equipmentGrouped' => $equipmentGrouped,
        ];

        $pdf = Pdf::loadView('pdf.packing-list', $data);
        return $pdf->download('hoja_de_carga_' . \Illuminate\Support\Str::slug($event->name) . '.pdf');
    }

    public function downloadMusicEscaleta(Event $event)
    {
        $this->authorizePdfAccess($event);

        $event->load(['client', 'dj', 'assistant', 'musicRequests']);

        $requestsGrouped = $event->musicRequests->groupBy('category');

        $data = [
            'event' => $event,
            'client' => $event->client,
            'dj' => $event->dj,
            'assistant' => $event->assistant,
            'requestsGrouped' => $requestsGrouped,
        ];

        $pdf = Pdf::loadView('pdf.music-escaleta', $data);
        return $pdf->download('escaleta_musical_' . \Illuminate\Support\Str::slug($event->name) . '.pdf');
    }

    public function downloadDjHistory(Event $event)
    {
        $this->authorizePdfAccess($event);

        $event->load(['client', 'dj', 'djHistories.matchedRequest']);

        $data = [
            'event' => $event,
            'client' => $event->client,
            'dj' => $event->dj,
            'histories' => $event->djHistories,
        ];

        $pdf = Pdf::loadView('pdf.dj-session-history', $data);
        return $pdf->download('tracklist_sesion_' . \Illuminate\Support\Str::slug($event->name) . '.pdf');
    }

    /**
     * Descarga o muestra en línea el Dossier General de Tarifas (8 a 10 páginas, igual para todas las parejas).
     * Distinto del presupuesto personalizado por boda.
     */
    public function downloadTariffDossier(Request $request, ?string $brand = null)
    {
        $config = GeneralTariffDossierService::getConfig();

        $requestedBrand = $brand ?: $request->query('brand');
        if ($requestedBrand && in_array($requestedBrand, ['nunez_and_son', 'javnx'], true)) {
            $config['brand'] = $requestedBrand;
        }
        if ($request->has('fair')) {
            $config['fair_mode']['enabled'] = $request->boolean('fair');
        }

        // Si un administrador solicita la generación desde el panel y no se pasa ?force=1,
        // verificamos el checklist obligatorio antes de exportar.
        if (auth()->check() && auth()->user()->role === 'admin' && !$request->boolean('force')) {
            $validation = GeneralTariffDossierService::validateConfig($config);
            if (empty($validation['can_export'])) {
                return redirect()
                    ->route('admin.tariff-dossier')
                    ->with('error', '⚠️ No se puede generar el PDF hasta corregir los puntos pendientes del checklist.');
            }
        }

        $plan = GeneralTariffDossierService::buildPagesPlan($config);
        $brandInfo = Setting::getBrandInfo($config['brand'] ?? 'nunez_and_son');

        $year = preg_replace('/[^0-9A-Za-z_-]/', '', (string) ($config['tariff_year'] ?? '2027')) ?: '2027';
        $brandSlug = ($config['brand'] ?? 'nunez_and_son') === 'javnx' ? 'JAVNX_DJ' : 'Nunez_and_Son';
        $filename = "Dossier_Tarifas_{$year}_{$brandSlug}.pdf";

        $pdf = Pdf::loadView('pdf.tariff-dossier', [
            'config' => $config,
            'plan' => $plan,
            'brandInfo' => $brandInfo,
        ])->setPaper('a4', 'portrait');

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Vista de presentación a pantalla completa ("Compartir en videollamada") del Dossier General de Tarifas.
     */
    public function viewTariffDossierPresentation(Request $request, ?string $brand = null)
    {
        $config = GeneralTariffDossierService::getConfig();

        $requestedBrand = $brand ?: $request->query('brand');
        if ($requestedBrand && in_array($requestedBrand, ['nunez_and_son', 'javnx'], true)) {
            $config['brand'] = $requestedBrand;
        }
        if ($request->has('fair')) {
            $config['fair_mode']['enabled'] = $request->boolean('fair');
        }

        $plan = GeneralTariffDossierService::buildPagesPlan($config);
        $brandInfo = Setting::getBrandInfo($config['brand'] ?? 'nunez_and_son');

        return view('pdf.tariff-dossier-presentation', [
            'config' => $config,
            'plan' => $plan,
            'brandInfo' => $brandInfo,
        ]);
    }

    /**
     * Pantalla visual independiente a ventana completa para TV / Monitor de Stand (ej. 21" en Feria de Novios),
     * con los códigos QR de cada servicio/acción listos para escanear desde el móvil.
     */
    public function viewStandTvScreen(Request $request, ?string $brand = null)
    {
        $config = GeneralTariffDossierService::getConfig();

        $requestedBrand = $brand ?: $request->query('brand');
        if ($requestedBrand && in_array($requestedBrand, ['nunez_and_son', 'javnx'], true)) {
            $config['brand'] = $requestedBrand;
        }
        if ($request->has('fair')) {
            $config['fair_mode']['enabled'] = $request->boolean('fair');
        }

        $activeBrand = $config['brand'] ?? 'nunez_and_son';
        $brandInfo = Setting::getBrandInfo($activeBrand);
        $fairData = $config['fair_mode'] ?? GeneralTariffDossierService::getDefaultFairModeConfig();
        $fairEnabled = !empty($fairData['enabled']);
        $fairName = trim((string) ($fairData['fair_name'] ?? 'Feria de Novios')) ?: 'Feria de Novios';

        $packsComputed = [];
        $maxDiscount = 0;
        foreach (($config['packs'] ?? []) as $idx => $pack) {
            $calc = GeneralTariffDossierService::computePackFairPricing($pack, (int) $idx, $config, $fairEnabled);
            if ($calc['discount_amount'] > $maxDiscount) {
                $maxDiscount = $calc['discount_amount'];
            }
            $packsComputed[] = array_merge($pack, [
                'pricing' => $calc,
            ]);
        }

        $phoneMiguelDisplay = trim((string) ($config['phone_miguel'] ?? '+34 674 37 89 93')) ?: '+34 674 37 89 93';
        $phoneFranDisplay = Setting::fixLegacyTypoPhone(trim((string) ($config['phone_fran'] ?? '+34 622 63 47 90')) ?: '+34 622 63 47 90');

        $rawMiguel = preg_replace('/\D+/', '', $phoneMiguelDisplay) ?: '34674378993';
        if (strlen($rawMiguel) === 9) {
            $rawMiguel = '34' . $rawMiguel;
        }

        $rawFran = preg_replace('/\D+/', '', $phoneFranDisplay) ?: '34622634790';
        if (strlen($rawFran) === 9) {
            $rawFran = '34' . $rawFran;
        }

        $defaultWaContact = ($activeBrand === 'javnx') ? 'fran' : ($request->query('wa', $fairData['whatsapp_contact'] ?? 'miguel'));
        if (!in_array($defaultWaContact, ['miguel', 'fran'], true)) {
            $defaultWaContact = 'miguel';
        }

        $waText = "¡Hola! Os estamos viendo en el stand de {$fairName} y nos gustaría recibir información y consultar disponibilidad para nuestra boda.";
        $waUrlMiguel = "https://wa.me/{$rawMiguel}?text=" . rawurlencode($waText);
        $waUrlFran = "https://wa.me/{$rawFran}?text=" . rawurlencode($waText);

        $activeWaUrl = $defaultWaContact === 'fran' ? $waUrlFran : $waUrlMiguel;
        $activeWaPhoneDisplay = $defaultWaContact === 'fran' ? $phoneFranDisplay : $phoneMiguelDisplay;
        $activeWaLabel = $defaultWaContact === 'fran' ? "Fran · {$phoneFranDisplay}" : "Miguel · {$phoneMiguelDisplay}";

        $pdfUrl = route('public.tariff-dossier.pdf', ['brand' => $activeBrand]);
        $quoteUrl = route('guest.quote');
        $viewerUrl = route('public.tariff-dossier.presentation', ['brand' => $activeBrand]);

        $svgQrPdf = QrPosterPdfService::toStylizedSvg($pdfUrl, ['style' => 'clasico', 'initials' => '']);
        $svgQrQuote = QrPosterPdfService::toStylizedSvg($quoteUrl, ['style' => 'clasico', 'initials' => '']);
        $svgQrWaMiguel = QrPosterPdfService::toStylizedSvg($waUrlMiguel, ['style' => 'clasico', 'initials' => '']);
        $svgQrWaFran = QrPosterPdfService::toStylizedSvg($waUrlFran, ['style' => 'clasico', 'initials' => '']);
        $svgQrViewer = QrPosterPdfService::toStylizedSvg($viewerUrl, ['style' => 'clasico', 'initials' => '']);

        $qrItems = [
            [
                'key' => 'dossier_pdf',
                'step' => 'OPCIÓN 1 · DESCARGA DIRECTA',
                'icon' => '📕',
                'title' => 'Dossier de Bodas ' . ($config['tariff_year'] ?? '2027') . ' (PDF)',
                'subtitle' => $fairEnabled
                    ? 'Descarga al instante nuestro dossier completo con los Packs, equipos y el Descuento Especial de Feria incluido.'
                    : 'Descarga en tu móvil el PDF completo con nuestros Packs de DJ, sonido, iluminación, efectos y tarifas.',
                'badge' => '📥 Descarga PDF inmediata en tu móvil',
                'accent' => 'amber',
                'url' => $pdfUrl,
                'svg' => $svgQrPdf,
            ],
            [
                'key' => 'quote_calculator',
                'step' => 'OPCIÓN 2 · A MEDIDA',
                'icon' => '✨',
                'title' => 'Configura Vuestra Boda Online',
                'subtitle' => 'Marca qué momentos queréis cubrir (Ceremonia, Cóctel, Banquete, Fiesta DJ, Fotomatón, 360º o Mesa Dulce) en 1 minuto.',
                'badge' => $fairEnabled ? '🎪 Aplica Promo Feria automáticamente' : '💍 Solicitud rápida en 1 minuto',
                'accent' => 'emerald',
                'url' => $quoteUrl,
                'svg' => $svgQrQuote,
            ],
            [
                'key' => 'whatsapp_direct',
                'step' => 'OPCIÓN 3 · CONTACTO DIRECTO',
                'icon' => '💬',
                'title' => 'Háblanos por WhatsApp',
                'subtitle' => 'Abre conversación directa con nosotros para consultar si vuestra fecha está libre y guardar nuestro contacto.',
                'badge' => '📲 WhatsApp directo · ' . $activeWaLabel,
                'accent' => 'green',
                'url' => $activeWaUrl,
                'svg' => $defaultWaContact === 'fran' ? $svgQrWaFran : $svgQrWaMiguel,
            ],
            [
                'key' => 'interactive_viewer',
                'step' => 'OPCIÓN 4 · VISOR INTERACTIVO',
                'icon' => '🖥️',
                'title' => 'Ver Dossier Online (Sin Descargar)',
                'subtitle' => 'Hojea las páginas del dossier directamente en tu navegador con fotografías reales y opiniones de parejas.',
                'badge' => '🌐 Visor Web Interactivo',
                'accent' => 'indigo',
                'url' => $viewerUrl,
                'svg' => $svgQrViewer,
            ],
        ];

        return view('pdf.stand-tv-screen', [
            'config' => $config,
            'brandInfo' => $brandInfo,
            'fairData' => $fairData,
            'fairEnabled' => $fairEnabled,
            'fairName' => $fairName,
            'packsComputed' => $packsComputed,
            'maxDiscount' => $maxDiscount,
            'qrItems' => $qrItems,
            'waProfiles' => [
                'default' => $defaultWaContact,
                'miguel' => [
                    'label' => "Miguel · {$phoneMiguelDisplay}",
                    'phone' => $phoneMiguelDisplay,
                    'url' => $waUrlMiguel,
                    'svg' => $svgQrWaMiguel,
                ],
                'fran' => [
                    'label' => "Fran · {$phoneFranDisplay}",
                    'phone' => $phoneFranDisplay,
                    'url' => $waUrlFran,
                    'svg' => $svgQrWaFran,
                ],
            ],
        ]);
    }

    /**
     * Genera y descarga el Cartel / Tarjetas de Mesa en PDF vectorial para el Álbum de Fotos QR del evento,
     * con iniciales de los novios en el centro del QR, frases llamativas y formatos 1xA4, 2xA5 o 4xA6.
     */
    public function downloadPhotoQrPoster(Request $request, Event $event)
    {
        $this->authorizePdfAccess($event);

        $albumInfo = \App\Services\FotosEventosQrService::getEventAlbumInfo($event);
        if ((!$albumInfo || empty($albumInfo['guest_url'])) && \App\Services\FotosEventosQrService::isConfigured()) {
            $syncRes = \App\Services\FotosEventosQrService::createOrSyncEventAlbum($event, [
                'share_drive_album' => $request->boolean('share_drive_album'),
                'allow_videos' => $request->boolean('allow_videos'),
            ]);
            if (!empty($syncRes['success'])) {
                $event->refresh();
                $albumInfo = \App\Services\FotosEventosQrService::getEventAlbumInfo($event);
            }
        }
        if (!$albumInfo || empty($albumInfo['guest_url'])) {
            abort(404, 'No se pudo activar el Álbum de Fotos QR. Verifica que la API Key esté guardada en Ajustes → Música & Fotos QR.');
        }

        $defaults = \App\Services\QrPosterPdfService::defaultsForEvent($event);
        $presets = \App\Services\QrPosterPdfService::presetPhrases();
        $presetKey = (string) $request->query('preset', $defaults['preset']);
        if ($presetKey !== 'custom' && !isset($presets[$presetKey])) {
            $presetKey = 'custom';
        }
        $selectedPreset = $presets[$presetKey] ?? null;

        $layout = (string) $request->query('layout', $defaults['layout']);
        if (!isset(\App\Services\QrPosterPdfService::layouts()[$layout])) {
            $layout = 'a4';
        }

        $style = (string) $request->query('style', $defaults['style']);
        if (!isset(\App\Services\QrPosterPdfService::visualStyles()[$style])) {
            $style = 'elegante';
        }

        $names = $request->has('names') ? trim((string) $request->query('names')) : $defaults['names'];
        if ($names === '') {
            $names = $defaults['names'];
        }

        $initials = $request->has('initials') ? trim((string) $request->query('initials')) : $defaults['initials'];
        $showInitials = $request->has('show_initials')
            ? ($request->query('show_initials') !== '0')
            : (bool) $defaults['show_initials'];
        $kicker = $request->has('kicker') ? trim((string) $request->query('kicker')) : $defaults['kicker'];

        if ($request->has('headline') && trim((string) $request->query('headline')) !== '') {
            $headline = trim((string) $request->query('headline'));
        } elseif ($request->has('preset') && $selectedPreset) {
            $headline = $selectedPreset['headline'];
        } else {
            $headline = $defaults['headline'];
        }

        if ($request->has('subtitle')) {
            $subtitle = trim((string) $request->query('subtitle'));
        } elseif ($request->has('preset') && $selectedPreset) {
            $subtitle = $selectedPreset['subtitle'];
        } else {
            $subtitle = $defaults['subtitle'];
        }

        $footer = $request->has('footer') ? trim((string) $request->query('footer')) : $defaults['footer'];
        $showUrl = $request->has('show_url')
            ? ($request->query('show_url') !== '0')
            : (bool) $defaults['show_url'];

        if ($request->boolean('save')) {
            \App\Services\QrPosterPdfService::saveCustomizationForEvent($event, [
                'layout' => $layout,
                'style' => $style,
                'names' => $names,
                'initials' => $initials,
                'show_initials' => $showInitials,
                'kicker' => $kicker,
                'footer' => $footer,
                'preset' => $presetKey,
                'headline' => $headline,
                'subtitle' => $subtitle,
                'show_url' => $showUrl,
            ], auth()->check() ? 'admin' : 'client');
        }

        $pdfBytes = \App\Services\QrPosterPdfService::generatePdf($albumInfo['guest_url'], [
            'layout' => $layout,
            'style' => $style,
            'kicker' => $kicker,
            'names' => $names,
            'initials' => $initials,
            'show_initials' => $showInitials,
            'headline' => $headline,
            'subtitle' => $subtitle,
            'footer' => $footer,
            'show_url' => $showUrl,
        ]);

        $safeSlug = \Illuminate\Support\Str::slug($names !== '' ? $names : $event->name) ?: ('evento-' . $event->id);
        $filename = 'Cartel-QR-Fotos-' . $safeSlug . '-' . strtoupper($layout) . '.pdf';
        $disposition = $request->query('download', '1') === '0' ? 'inline' : 'attachment';

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Content-Length' => strlen($pdfBytes),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}
