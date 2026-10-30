<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Event;
use App\Models\User;
use App\Models\Contract;
use App\Models\Equipment;
use App\Models\EventMusicRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Livewire\Traits\HandlesDniValidation;

class EventShow extends Component
{
    use WithFileUploads;
    use HandlesDniValidation;

    public Event $event;
    public $activeTab = 'dossier'; // dossier, music, equipment, quotes, contracts, invoices

    // Personal asignado (DJ y Asistentes)
    public $assigned_dj_id;
    public $assigned_assistant_id;
    public $assigned_assistant_ids = [];
    public $showStaffModal = false;

    // Contacto Finca / Bodega / Lugar
    public $venue_contact_name;
    public $venue_contact_phone;
    public $venue_notes;
    public $showVenueModal = false;

    // Edición General del Evento
    public $showEditEventModal = false;
    public $event_edit_name;
    public $event_edit_date;
    public $event_edit_location;
    public $event_edit_type;
    public $event_edit_brand;
    public $event_edit_status;
    public $event_edit_notes;

    // Horarios del Evento y Baile
    public $showScheduleModal = false;
    public $setup_date;
    public $dance_start_time;
    public $dance_duration_hours;
    public $dance_end_time;
    public $max_end_time;
    public $start_time;
    public $ceremony_time;
    public $cocktail_time;
    public $banquet_time;
    public $schedule_notes;

    // Edición de Cliente y Pareja
    public $showClientModal = false;
    public $selected_client_id = '';
    public $client_edit_name;
    public $client_edit_phone;
    public $client_edit_email;
    public $client_edit_username;
    public $client_edit_dni;
    public $client_edit_address;
    public $client_edit_postal_code;
    public $client_edit_city;
    public $client_edit_province;
    public $client_edit_password;
    public $partner_edit_name;
    public $partner_edit_phone;
    public $partner_edit_email;
    public $partner_edit_dni;

    // Facturas y Recibos
    public $invoice_type = 'factura'; // 'factura' | 'recibo'
    public $invoice_quote_id = null;
    public $invoice_number;
    public $invoice_amount;
    public $invoice_tax;
    public $invoice_tax_rate = 21.00;
    public $invoice_issue_date;
    public $invoice_status = 'unpaid';
    public $invoice_notes = '';

    // Notas de Evento / Acuerdos WhatsApp & Email
    public $event_notes;
    public $whatsapp_chat_input = '';
    public $whatsapp_sender_brand = null; // 'miguel', 'fran', 'ezequiel', 'mago_leugim'
    public $whatsapp_sync_phone = '';
    public $showWhatsAppImportBox = false;
    public $whatsapp_import_text = '';
    public $whatsapp_import_direction = 'inbound';
    public $whatsapp_import_channel = 'whatsapp'; // 'whatsapp' | 'email'
    public $whatsapp_import_date = '';
    public $email_chat_to = '';
    public $email_chat_subject = '';
    public $selected_pack_type = 'custom'; // basic, medium, premium, wedding_dj, wedding_full, custom
    public $photobooth_provider = 'logrono'; // 'logrono' | 'vitoria'

    // Configuración de señal específica para este presupuesto
    public $quote_deposit_type = 'percentage'; // 'percentage' | 'fixed'
    public $quote_deposit_percentage = 40;
    public $quote_deposit_fixed_amount = 200;
    public $editing_quote_id = null; // ID de la propuesta actualmente en edición
    public $editing_contract_id = null; // ID del contrato actualmente editando su nombre/código
    public $editing_contract_number = '';

    // Configuración de IVA y Métodos de Pago
    public $quote_tax_type = 'included'; // 'included' (IVA incluido), 'excluded' (Base + 21% IVA), 'none' (Sin IVA / Exento)
    public $quote_tax_rate = 21.00;
    public $quote_payment_methods = ['transfer', 'bizum', 'cash']; // transfer, bizum, cash, card
    public $quote_admin_notes = ''; // Notas / condiciones especiales visibles para el cliente en el presupuesto

    // Señal / Reserva cobrada
    public $showDepositModal = false;
    public $deposit_amount_input;
    public $deposit_method_input = 'bizum'; // bizum, transfer, cash, card, other
    public $deposit_date_input;
    public $deposit_notes_input = '';
    public $generate_receipt_checkbox = true;
    public $target_status = 'confirmed';

    // Liquidación / Cobro Final (al Completar Evento)
    public $showFinalPaymentModal = false;
    public $final_payment_amount;
    public $final_payment_method = 'cash'; // cash, bizum, transfer, card, other
    public $final_payment_date;
    public $final_payment_notes = '';
    public $generate_final_receipt_checkbox = true;

    // Presupuestos & Packs
    public $quote_services = [
        'pack_basic' => ['selected' => false, 'name' => 'Pack Esencial', 'price' => 600, 'hours' => 4, 'desc' => '4 horas de sesión DJ. Sonido profesional estéreo (2 altavoces de alta definición) + iluminación rítmica básica de pista (2 focos/efectos LED). Microfonía inalámbrica para discursos.'],
        'pack_medium' => ['selected' => false, 'name' => 'Pack Signature', 'price' => 950, 'hours' => 5, 'desc' => '5 horas de sesión DJ. Sonido reforzado con subgraves (impacto y pegada en pista). Cabina DJ retroiluminada + puente/barras de iluminación LED sincronizada y ambientación de pista. Microfonía inalámbrica.'],
        'pack_premium' => ['selected' => false, 'name' => 'Pack Deluxe Show', 'price' => 1350, 'hours' => 6, 'desc' => 'Cobertura completa (hasta 6h). Montaje de gran formato: sonido multizona/subgraves potentes + show de iluminación avanzada sincronizada. Incluye efecto especial de máquina de humo frío / chispas frías para momentos clave (apertura de baile).'],
        'ceremony' => ['selected' => false, 'name' => 'Ceremonia', 'quantity' => 1, 'price' => 180, 'desc' => 'Sonorización + Música ambiente + Micrófonos inalámbricos.'],
        'cocktail' => ['selected' => false, 'quantity' => 1, 'price' => 150, 'desc' => 'Música ambiente durante el cóctel.'],
        'restaurant' => ['selected' => false, 'quantity' => 1, 'price' => 150, 'desc' => 'Música ambiente + Sonorización de regalos y detalles en el banquete.'],
        'dj_custom' => ['selected' => false, 'quantity' => 2, 'price' => 150, 'desc' => 'Horas de DJ en directo.'],
        'extra_hours' => ['quantity' => 0, 'price' => 120, 'desc' => 'Horas adicionales de baile.'],
        'photo_ceremony' => ['selected' => false, 'quantity' => 1, 'price' => 250, 'desc' => 'Fotografía: Cobertura completa de la Ceremonia.'],
        'photo_restaurant' => ['selected' => false, 'quantity' => 1, 'price' => 250, 'desc' => 'Fotografía: Banquete y momentos emotivos con invitados.'],
        'photo_party' => ['selected' => false, 'quantity' => 1, 'price' => 300, 'desc' => 'Fotografía: Baile nupcial y fiesta.'],
        'photo_album' => ['selected' => false, 'quantity' => 1, 'price' => 150, 'desc' => 'Maquetación álbum digital profesional (sin imprimir).'],
        'photo_full_pack' => ['selected' => false, 'quantity' => 1, 'price' => 800, 'desc' => 'Fotografía Completa: Ceremonia, Restaurante, Fiesta, Álbum digital maquetado, fotos en alta calidad sin marcas de agua.'],
        'karaoke' => ['selected' => false, 'quantity' => 1, 'price' => 80, 'desc' => 'Karaoke interactivo para invitados.'],
        'photobooth' => ['selected' => false, 'hours' => 2, 'quantity' => 1, 'price_2h' => 350, 'price_3h' => 420, 'price' => 350, 'desc' => 'Cámara réflex profesional Canon 1200, impresora de alta calidad (imprime en 10s) y ordenador táctil con efecto espejo. Incluye fotos ilimitadas, 2 copias de cada foto (invitados + libro de firmas personalizado de imprenta con foto), formato 10x15 o tiras (5x15), marco personalizado (nombre, fecha y temática), atrezzo divertido, photocall a elegir (8 fondos), animador de FotomatonCheese, USB con todas las fotos y subida a redes sociales. (Requiere enchufe cerca y espacio de 2m x 2m).'],
        'video_360' => ['selected' => false, 'hours' => 2, 'quantity' => 1, 'price_2h' => 390, 'price_3h' => 490, 'price' => 390, 'desc' => 'Plataforma de 100cm (capacidad 4-5 personas por vídeo) con cámara GoPro. Incluye vídeos 360 ilimitados grabados con slow motion y boomerang (~20 seg), marco personalizado, canción a elegir, atrezzo divertido, bolsa de confeti, 1 o 2 animadores de FotomatonCheese y transporte incluido hasta 50 km. (Montaje 45 min aprox., requiere enchufe cerca y espacio de 2m x 2m).'],
        'pack_duo_360' => ['selected' => false, 'hours' => 3, 'quantity' => 1, 'price_2h' => 690, 'price_3h' => 850, 'price' => 850, 'desc' => 'Pack Dúo Todo Incluido (Fotomatón fotos impresas ilimitadas + Plataforma 360º vídeos slow motion/boomerang ilimitados): 2 copias por foto, libro de firmas personalizado de imprenta, USB, marcos y música personalizados, atrezzo completo, photocall, confeti, animadores FotomatonCheese y transporte incluido hasta 50 km.'],
        'chocolate_fountain' => ['selected' => false, 'fountains' => 1, 'hours' => 2, 'quantity' => 1, 'price_1_2h' => 420, 'price_1_3h' => 520, 'price_2_2h' => 490, 'price_2_3h' => 590, 'waffle_extra' => 2.90, 'price' => 420, 'consult' => false, 'desc' => 'Mesa dulce con fuente de chocolate a elegir (blanco, negro o con leche): donut de azúcar y gofres cortados, profiteroles de nata, fruta de temporada variada (🍓🍊🍍🫐) cortada a tamaño de bocado, galletas Chips Ahoy, palmeritas, filipinos blanco y negro, chocolatinas individuales cerradas (barritas Kinder, mini KitKat, Huesitos), chucherías variadas, flores de lenguas verdes y rosa envueltas individuales, arco de globos, decoración a elegir, palillos, servilletas y bolsa de basura. Montaje 1-2h antes y reposición a mitad del servicio. (Opción rincón de gofres con 3 biberones de siropes + nata spray: 2,90 €/ud).'],
        'candy_bar' => ['selected' => false, 'quantity' => 1, 'price' => 420, 'consult' => false, 'desc' => 'Mesa de Solo Chuches: Incluye decoración a elegir, gran variado de chuches (eligiendo las favoritas de los anfitriones) y globos decorativos. Se monta un par de horas antes y se recoge a la noche o al día siguiente.'],
        'waffle_cart' => ['selected' => false, 'hours' => 2, 'quantity' => 1, 'price_2h' => 420, 'price_3h' => 550, 'price' => 420, 'consult' => false, 'desc' => 'Carrito de Gofres hechos al momento: Incluye 4 siropes a elegir (Kinder, Lotus, Nutella, chocolate blanco, chocolate con leche, pistacho...), 4 ingredientes secos (galleta Lotus molida, Oreo, mini Lacasitos, mini nubes...), decoración del carrito con globos y banderín con nombre, servilletas, bandejas y cubiertos. (2h ~60 gofres / 3h ~100 gofres).'],
        'smoke_machine' => ['selected' => false, 'quantity' => 1, 'price' => 60, 'included' => false, 'desc' => 'Efecto de máquina de humo para ambientar la pista y realzar la iluminación.'],
        'cold_sparks' => ['selected' => false, 'quantity' => 2, 'price' => 60, 'included' => false, 'desc' => 'Efecto especial de fuego frío / chispas frías para momentos clave (apertura de baile / corte de tarta).'],
        'magic_kids' => ['selected' => false, 'name' => 'Espectáculo de Magia Infantil', 'quantity' => 1, 'price' => 250, 'desc' => 'Show participativo de magia infantil con humor, juegos visuales e ilusión adaptado a niños (aprox. 60 min). Incluye equipo de sonido de apoyo y microfonía.'],
        'magic_adults' => ['selected' => false, 'name' => 'Espectáculo de Magia para Adultos', 'quantity' => 1, 'price' => 300, 'desc' => 'Show de magia de salón/escenario, mentalismo y comedia elegante diseñado para público adulto (aprox. 60-75 min). Incluye sonido y microfonía inalámbrica.'],
        'magic_mixed' => ['selected' => false, 'name' => 'Espectáculo de Magia Mixto / Familiar (Infantil y Adultos)', 'quantity' => 1, 'price' => 300, 'desc' => 'Espectáculo dinámico de magia, humor y participación para todas las edades (niños y adultos), ideal para fiestas, carnaval, colegios, comuniones y celebraciones familiares.'],
        'magic_closeup' => ['selected' => false, 'name' => 'Magia de Cerca (Close-Up en Recepción / Cóctel)', 'quantity' => 1, 'price' => 250, 'desc' => 'Ilusionismo interactivo a pocos centímetros de los invitados por grupos o mesas durante recepción, cóctel o sobremesa.'],
    ];

    public $custom_extras = [
        ['name' => '', 'price' => 0, 'desc' => '', 'consult' => false]
    ];

    // Dossier form
    public $dossier_content;
    public $is_dossier_completed;

    // Material / Equipment form
    public $selected_equipment_id = '';
    public $equipment_quantity = 1;
    public $equipment_notes = '';

    // Music & Moments form
    public $music_active_category = 'all'; // all, ceremonia, coctel, banquete, baile, lista_negra
    public $showSongModal = false;
    public $editingSongId = null;
    public $req_category = 'banquete';
    public $req_moment = 'Entrada Comedor';
    public $req_title = '';
    public $req_artist = '';
    public $req_requested_by = '';
    public $req_notes = '';
    public $req_youtube_url = '';
    public $req_spotify_url = '';
    public $req_apple_music_url = '';
    public $req_tidal_url = '';
    public $req_audio_file = null;
    public $existing_audio_file = null;
    public $req_cue_time = '';
    public $req_status = 'pending';

    // Music Search Autocomplete
    public $musicSearchQuery = '';
    public $musicSearchResults = [];

    // Importador Carpeta Nube (Google Drive / OneDrive)
    public $showCloudImportModal = false;
    public $cloudFolderUrl = '';
    public $cloudImportCategory = 'banquete';
    public $cloudImportMoment = 'General';
    public $cloudImportStatus = null;
    public $cloudScannedFiles = [];

    // Importador Inteligente de Listas (PDF / WhatsApp / Texto)
    public $showImportModal = false;
    public $importRawText = '';
    public $importFile = null;
    public $parsedImportTracks = [];
    public $parsedImportNotes = [];
    public $importDefaultCategory = 'baile';
    public $importDefaultMoment = 'Baile & Fiesta';

    // Citas y Reuniones con Clientes
    public $showMeetingModal = false;
    public $editing_meeting_id = null;
    public $meeting_title = 'Reunión de Escaleta Musical';
    public $meeting_type = 'escaleta_musica';
    public $meeting_date;
    public $meeting_time = '18:00';
    public $meeting_duration_minutes = 45;
    public $meeting_location_type = 'in_person';
    public $meeting_location = '';
    public $meeting_video_call_url = '';
    public $meeting_staff_id = null;
    public $meeting_status = 'scheduled';
    public $meeting_summary = '';
    public $meeting_notes = '';

    // Historial de Sesión DJ Real (Engine DJ / Rekordbox / M3U / CSV)
    public $showDjHistoryModal = false;
    public $djHistoryFile = null;
    public $djHistoryRawText = '';
    public $djHistorySessionName = 'Sesión Baile & Fiesta';
    public $djHistorySyncRequests = true;
    public $djHistoryFilterQuery = '';

    // Álbum Colaborativo de Fotos QR (FotosEventosQR vía HTTP API)
    public bool $showPhotoQrModal = false;
    public bool $photoQrShareDriveAlbum = false;
    public bool $photoQrAllowVideos = false;
    public ?array $photoQrLiveStats = null;
    public int $photoQrPosterKey = 0;

    public function boot()
    {
        if ($this->event && auth()->check()) {
            $user = auth()->user();
            if ($user->role !== 'admin') {
                $isAssignedAssistant = false;
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                        $isAssignedAssistant = $this->event->assistants()->where('users.id', $user->id)->exists();
                    }
                } catch (\Throwable $e) {}

                if ($this->event->dj_id != $user->id && $this->event->assistant_id != $user->id && !$isAssignedAssistant) {
                    abort(403, 'Acceso denegado: No tienes permisos sobre este evento.');
                }
            }
        }
    }

    public function mount(Event $event)
    {
        $user = auth()->user();
        $isAssignedAssistant = false;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                $isAssignedAssistant = $event->assistants()->where('users.id', $user->id)->exists();
            }
        } catch (\Throwable $e) {}

        if (!\Illuminate\Support\Facades\Schema::hasTable('whatsapp_messages') || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'partner_name')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {}
        }

        if ($user->role !== 'admin' && $event->dj_id != $user->id && $event->assistant_id != $user->id && !$isAssignedAssistant) {
            abort(403, 'Acceso denegado: No estás asignado a este evento.');
        }

        $relations = ['client', 'dj', 'assistant', 'invoices', 'quotes.items', 'contracts', 'dossiers', 'equipment', 'musicRequests', 'meetings.staff', 'djHistories.matchedRequest'];
        if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
            $relations[] = 'assistants';
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('whatsapp_messages')) {
            $relations[] = 'whatsappMessages.user';
        }

        $this->event = $event->load($relations);

        try {
            if (\App\Services\EvolutionWhatsAppService::purgePollutedMessagesForEvent($this->event) > 0) {
                $this->event->refresh();
                $this->event->load($relations);
            }
        } catch (\Throwable $e) {}

        try {
            if (\App\Services\ImapLeadFetcherService::repairMisParsedLeadDates($this->event) > 0) {
                $this->event->refresh();
            }
        } catch (\Throwable $e) {}

        if (empty(trim((string)$this->event->venue_contact_phone)) && \App\Services\VenueLookupService::isSearchableLocation($this->event->location)) {
            try {
                $foundVenue = \App\Services\VenueLookupService::autoPopulateEventVenuePhone($this->event, false);
                if ($foundVenue) {
                    $this->event->refresh();
                }
            } catch (\Throwable $e) {}
        }

        $this->assigned_dj_id = $this->event->dj_id;
        $this->assigned_assistant_id = $this->event->assistant_id;
        $this->assigned_assistant_ids = $this->event->all_assistants->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->venue_contact_name = $this->event->venue_contact_name;
        $this->venue_contact_phone = $this->event->venue_contact_phone;
        $this->venue_notes = $this->event->venue_notes;
        $this->is_dossier_completed = $this->event->is_dossier_completed;
        $this->event_notes = $this->event->notes;
        $this->whatsapp_sync_phone = $this->event->client ? ($this->event->client->phone ?? '') : ($this->event->partner_phone ?? '');
        $this->email_chat_to = $this->event->client_contact_email ?? '';
        $this->email_chat_subject = \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, 'default');
        if (empty($this->whatsapp_sync_phone) && !empty($this->email_chat_to)) {
            $this->whatsapp_sync_phone = $this->email_chat_to;
            $this->whatsapp_import_channel = 'email';
        }
        $this->whatsapp_import_date = now()->format('Y-m-d\TH:i');

        // Si el evento entró por Email y aún no tiene su correo inicial en Conversación Registrada, registrarlo automáticamente
        if (!empty($this->event->notes) && str_contains((string)$this->event->notes, 'LEAD IMPORTADO VÍA EMAIL')) {
            try {
                if ($this->event->whatsappMessages->isEmpty()) {
                    (new \App\Services\ImapLeadFetcherService())->syncEmailHistoryForEvent($this->event, $this->email_chat_to);
                    $this->event->load('whatsappMessages.user');
                }
            } catch (\Throwable $e) {}
        }

        // Horarios
        $this->setup_date = $this->event->setup_date ? \Carbon\Carbon::parse($this->event->setup_date)->format('Y-m-d') : null;
        $this->dance_start_time = $this->event->dance_start_time;
        $this->dance_duration_hours = $this->event->dance_duration_hours ?: $this->event->suggested_dance_hours;
        $this->dance_end_time = $this->event->dance_end_time ?: $this->event->calculated_dance_end_time;
        $this->start_time = $this->event->start_time;
        $this->ceremony_time = $this->event->ceremony_time;
        $this->cocktail_time = $this->event->cocktail_time;
        $this->banquet_time = $this->event->banquet_time;
        $this->schedule_notes = $this->event->schedule_notes;
        
        $dossier = $this->event->dossiers()->first();
        if ($dossier) {
            $this->dossier_content = $dossier->content;
        }
        $this->loadSettingsPrices();

        // Si el evento ya tiene un presupuesto (por ejemplo, enviado por el cliente desde /presupuesto),
        // reparar posibles descuentos duplicados y cargar sus opciones en el configurador
        if ($this->event->quotes && $this->event->quotes->isNotEmpty()) {
            $repairedAnyQuote = false;
            foreach ($this->event->quotes as $evQuote) {
                if (\App\Models\Quote::repairCorruptedPackDiscounts($evQuote)) {
                    $repairedAnyQuote = true;
                }
            }
            if ($repairedAnyQuote) {
                $this->event->load('quotes.items');
            }
        }
        $latestQuote = $this->event->quotes ? $this->event->quotes->sortByDesc('id')->first() : null;
        if ($latestQuote) {
            try {
                $this->loadQuoteIntoCalculator($latestQuote->id, false);
            } catch (\Throwable $e) {}
        }

        $this->invoice_type = 'factura';
        $this->invoice_tax_rate = 21.00;
        $this->invoice_issue_date = now()->format('Y-m-d');
        $this->invoice_number = \App\Models\Invoice::nextNumber('factura');

        $requestedTab = request()->query('tab');
        if (in_array($requestedTab, ['dossier', 'music', 'equipment', 'meetings', 'quotes', 'contracts', 'invoices'], true)) {
            $this->activeTab = $requestedTab;
        }
    }

    public function loadSettingsPrices()
    {
        // Packs
        $storedBasic = \App\Models\Setting::get('pack_basic_name');
        if (empty($storedBasic) || $storedBasic === 'Pack Básico' || $storedBasic === 'Pack Basico') {
            \App\Models\Setting::set('pack_basic_name', 'Pack Esencial');
            \App\Models\Setting::set('pack_basic_price', 600);
            \App\Models\Setting::set('pack_basic_hours', 4);
            \App\Models\Setting::set('pack_basic_desc', '4 horas de sesión DJ. Sonido profesional estéreo (2 altavoces de alta definición) + iluminación rítmica básica de pista (2 focos/efectos LED). Microfonía inalámbrica para discursos.');
        }

        $storedMedium = \App\Models\Setting::get('pack_medium_name');
        if (empty($storedMedium) || $storedMedium === 'Pack Medio (Recomendado)' || $storedMedium === 'Pack Medio') {
            \App\Models\Setting::set('pack_medium_name', 'Pack Signature');
            \App\Models\Setting::set('pack_medium_price', 950);
            \App\Models\Setting::set('pack_medium_hours', 5);
            \App\Models\Setting::set('pack_medium_desc', '5 horas de sesión DJ. Sonido reforzado con subgraves (impacto y pegada en pista). Cabina DJ retroiluminada + puente/barras de iluminación LED sincronizada y ambientación de pista. Microfonía inalámbrica.');
        }

        $storedPremium = \App\Models\Setting::get('pack_premium_name');
        if (empty($storedPremium) || $storedPremium === 'Pack Premium') {
            \App\Models\Setting::set('pack_premium_name', 'Pack Deluxe Show');
            \App\Models\Setting::set('pack_premium_price', 1350);
            \App\Models\Setting::set('pack_premium_hours', 6);
            \App\Models\Setting::set('pack_premium_desc', 'Cobertura completa (hasta 6h). Montaje de gran formato: sonido multizona/subgraves potentes + show de iluminación avanzada sincronizada. Incluye efecto especial de máquina de humo frío / chispas frías para momentos clave (apertura de baile).');
        }

        $evType = $this->event ? $this->event->event_type_clean : 'boda';
        $getPrice = function (string $key, float $default) use ($evType): float {
            $byType = \App\Models\Setting::get("{$key}_{$evType}");
            if ($byType !== null && $byType !== '') {
                return (float)$byType;
            }
            return (float)\App\Models\Setting::get($key, $default);
        };

        $this->quote_services['pack_basic']['name'] = \App\Models\Setting::get('pack_basic_name', 'Pack Esencial');
        $this->quote_services['pack_basic']['price'] = $getPrice('pack_basic_price', 600);
        $this->quote_services['pack_basic']['hours'] = (int)\App\Models\Setting::get("pack_basic_hours_{$evType}", \App\Models\Setting::get('pack_basic_hours', 4));
        $this->quote_services['pack_basic']['desc'] = \App\Models\Setting::get("pack_basic_desc_{$evType}", \App\Models\Setting::get('pack_basic_desc', '4 horas de sesión DJ. Sonido profesional estéreo (2 altavoces de alta definición) + iluminación rítmica básica de pista (2 focos/efectos LED). Microfonía inalámbrica para discursos.'));

        $this->quote_services['pack_medium']['name'] = \App\Models\Setting::get('pack_medium_name', 'Pack Signature');
        $this->quote_services['pack_medium']['price'] = $getPrice('pack_medium_price', 950);
        $this->quote_services['pack_medium']['hours'] = (int)\App\Models\Setting::get("pack_medium_hours_{$evType}", \App\Models\Setting::get('pack_medium_hours', 5));
        $this->quote_services['pack_medium']['desc'] = \App\Models\Setting::get("pack_medium_desc_{$evType}", \App\Models\Setting::get('pack_medium_desc', '5 horas de sesión DJ. Sonido reforzado con subgraves (impacto y pegada en pista). Cabina DJ retroiluminada + puente/barras de iluminación LED sincronizada y ambientación de pista. Microfonía inalámbrica.'));

        $this->quote_services['pack_premium']['name'] = \App\Models\Setting::get('pack_premium_name', 'Pack Deluxe Show');
        $this->quote_services['pack_premium']['price'] = $getPrice('pack_premium_price', 1350);
        $this->quote_services['pack_premium']['hours'] = (int)\App\Models\Setting::get("pack_premium_hours_{$evType}", \App\Models\Setting::get('pack_premium_hours', 6));
        $this->quote_services['pack_premium']['desc'] = \App\Models\Setting::get("pack_premium_desc_{$evType}", \App\Models\Setting::get('pack_premium_desc', 'Cobertura completa (hasta 6h). Montaje de gran formato: sonido multizona/subgraves potentes + show de iluminación avanzada sincronizada. Incluye efecto especial de máquina de humo frío / chispas frías para momentos clave (apertura de baile).'));

        // Servicios individuales adaptados al tipo de evento
        $this->quote_services['ceremony']['price'] = $getPrice('price_ceremony', 180);
        $this->quote_services['cocktail']['price'] = $getPrice('price_cocktail', 150);
        $this->quote_services['restaurant']['price'] = $getPrice('price_restaurant', 150);
        $this->quote_services['dj_custom']['price'] = $getPrice('price_dj', 150);
        $this->quote_services['karaoke']['price'] = $getPrice('price_karaoke', 80);

        if ($evType === 'boda') {
            $defCerName = 'Ceremonia Civil / Religiosa';
            $defCerDesc = 'Sonorización + Música ambiente + Micrófonos inalámbricos.';
            $defCockName = 'Música en Cóctel';
            $defCockDesc = 'Ambientación musical durante el cóctel de bienvenida.';
            $defRestName = 'Regalos & Banquete';
            $defRestDesc = 'Música ambiente y sonorización de entradas, regalos y sorpresas en el banquete.';
            $defDjName = 'DJ Baile / Barra Libre (Horas sueltas)';
            $defDjDesc = 'Actuación DJ en directo con repertorio a medida (ampliable durante el evento).';
        } elseif ($evType === 'empresa') {
            $defCerName = 'Sonorización Presentación / Acto Corporativo';
            $defCerDesc = 'Equipo de sonido profesional, microfonía inalámbrica para ponencias y control técnico.';
            $defCockName = 'Ambientación Cóctel / Networking';
            $defCockDesc = 'Música ambiente elegante durante la recepción, networking o vino español.';
            $defRestName = 'Ambientación Comida / Cena de Gala & Premios';
            $defRestDesc = 'Sonorización ambiental y coordinación musical para entregas de premios o discursos.';
            $defDjName = 'Sesión DJ Fiesta Corporativa (Horas sueltas)';
            $defDjDesc = 'Actuación DJ en directo con equipo de sonido e iluminación profesional.';
        } elseif ($evType === 'comunion') {
            $defCerName = 'Recibimiento / Sonorización Especial';
            $defCerDesc = 'Equipo de sonido profesional, música ambiente y microfonía inalámbrica.';
            $defCockName = 'Música en Aperitivo / Cóctel';
            $defCockDesc = 'Ambientación musical durante el aperitivo de bienvenida.';
            $defRestName = 'Ambientación Comida & Entrega de Regalos';
            $defRestDesc = 'Música ambiente durante la comida y temas especiales para regalos y sorpresas.';
            $defDjName = 'DJ Fiesta & Animación (Horas sueltas)';
            $defDjDesc = 'Sesión DJ en directo con animación, sonido e iluminación de fiesta.';
        } elseif ($evType === 'cumpleanos') {
            $defCerName = 'Sonorización Sorpresas / Discursos / Recepción';
            $defCerDesc = 'Equipo de sonido profesional, música de apoyo y microfonía inalámbrica para dedicatorias.';
            $defCockName = 'Ambientación Recepción / Aperitivo';
            $defCockDesc = 'Música ambiente para la llegada de invitados y aperitivo.';
            $defRestName = 'Ambientación Comida / Cena & Sorpresas';
            $defRestDesc = 'Música ambiente y sonorización de momentos especiales, tarta y sorpresas.';
            $defDjName = 'Sesión DJ Fiesta / Baile (Horas sueltas)';
            $defDjDesc = 'Actuación DJ en directo con sonido e iluminación profesional a medida.';
        } else {
            $defCerName = 'Sonorización de Evento / Acto / Escenario';
            $defCerDesc = 'Instalación de equipo de sonido profesional, microfonía inalámbrica y asistencia técnica.';
            $defCockName = 'Ambientación Recepción / Aperitivo';
            $defCockDesc = 'Música ambiente durante la recepción o aperitivo del evento.';
            $defRestName = 'Ambientación Comida / Cena / Entregas';
            $defRestDesc = 'Sonorización ambiental para comida/cena y momentos especiales del evento.';
            $defDjName = 'Sesión DJ Fiesta / Espectáculo (Horas sueltas)';
            $defDjDesc = 'Actuación DJ en directo con sonido e iluminación profesional.';
        }

        $this->quote_services['ceremony']['name'] = \App\Models\Setting::get("service_ceremony_name_{$evType}", $defCerName);
        $this->quote_services['ceremony']['desc'] = \App\Models\Setting::get("service_ceremony_desc_{$evType}", $defCerDesc);
        $this->quote_services['cocktail']['name'] = \App\Models\Setting::get("service_cocktail_name_{$evType}", $defCockName);
        $this->quote_services['cocktail']['desc'] = \App\Models\Setting::get("service_cocktail_desc_{$evType}", $defCockDesc);
        $this->quote_services['restaurant']['name'] = \App\Models\Setting::get("service_restaurant_name_{$evType}", $defRestName);
        $this->quote_services['restaurant']['desc'] = \App\Models\Setting::get("service_restaurant_desc_{$evType}", $defRestDesc);
        $this->quote_services['dj_custom']['name'] = \App\Models\Setting::get("service_dj_name_{$evType}", $defDjName);
        $this->quote_services['dj_custom']['desc'] = \App\Models\Setting::get("service_dj_desc_{$evType}", $defDjDesc);

        // Espectáculos de Magia (Mago Leugim) por Bloques: Infantil, Adultos, Mixto/Familiar y Close-Up
        $this->quote_services['magic_kids']['price'] = $getPrice('price_magic_kids', 250);
        $this->quote_services['magic_kids']['name'] = \App\Models\Setting::get("service_magic_kids_name_{$evType}", \App\Models\Setting::get('service_magic_kids_name', 'Espectáculo de Magia Infantil'));
        $this->quote_services['magic_kids']['desc'] = \App\Models\Setting::get("service_magic_kids_desc_{$evType}", \App\Models\Setting::get('service_magic_kids_desc', 'Show participativo de magia infantil con humor, juegos visuales e ilusión adaptado a niños (aprox. 60 min). Incluye equipo de sonido de apoyo y microfonía.'));

        $this->quote_services['magic_adults']['price'] = $getPrice('price_magic_adults', 300);
        $this->quote_services['magic_adults']['name'] = \App\Models\Setting::get("service_magic_adults_name_{$evType}", \App\Models\Setting::get('service_magic_adults_name', 'Espectáculo de Magia para Adultos'));
        $this->quote_services['magic_adults']['desc'] = \App\Models\Setting::get("service_magic_adults_desc_{$evType}", \App\Models\Setting::get('service_magic_adults_desc', 'Show de magia de salón/escenario, mentalismo y comedia elegante diseñado para público adulto (aprox. 60-75 min). Incluye sonido y microfonía inalámbrica.'));

        $this->quote_services['magic_mixed']['price'] = $getPrice('price_magic_mixed', 300);
        $this->quote_services['magic_mixed']['name'] = \App\Models\Setting::get("service_magic_mixed_name_{$evType}", \App\Models\Setting::get('service_magic_mixed_name', 'Espectáculo de Magia Mixto / Familiar (Infantil y Adultos)'));
        $this->quote_services['magic_mixed']['desc'] = \App\Models\Setting::get("service_magic_mixed_desc_{$evType}", \App\Models\Setting::get('service_magic_mixed_desc', 'Espectáculo dinámico de magia, humor y participación para todas las edades (niños y adultos), ideal para fiestas, carnaval, colegios, comuniones y celebraciones familiares.'));

        $this->quote_services['magic_closeup']['price'] = $getPrice('price_magic_closeup', 250);
        $this->quote_services['magic_closeup']['name'] = \App\Models\Setting::get("service_magic_closeup_name_{$evType}", \App\Models\Setting::get('service_magic_closeup_name', 'Magia de Cerca (Close-Up en Recepción / Cóctel)'));
        $this->quote_services['magic_closeup']['desc'] = \App\Models\Setting::get("service_magic_closeup_desc_{$evType}", \App\Models\Setting::get('service_magic_closeup_desc', 'Ilusionismo interactivo a pocos centímetros de los invitados por grupos o mesas durante recepción, cóctel o sobremesa.'));

        // Si la marca comercial es Mago Leugim, adaptar textos de Música después de la Magia
        if ($this->event && $this->event->brand_clean === 'mago_leugim') {
            $this->quote_services['dj_custom']['name'] = \App\Models\Setting::get("service_dj_name_mago_{$evType}", 'Música y Fiesta después de la Magia (DJ / Discomóvil)');
            $this->quote_services['dj_custom']['desc'] = \App\Models\Setting::get("service_dj_desc_mago_{$evType}", 'Sesión musical, animación y baile al finalizar el espectáculo de magia con equipo de sonido e iluminación.');
            $this->quote_services['ceremony']['name'] = \App\Models\Setting::get("service_ceremony_name_mago_{$evType}", 'Sonorización Extra de Evento / Escenario');
            $this->quote_services['ceremony']['desc'] = \App\Models\Setting::get("service_ceremony_desc_mago_{$evType}", 'Equipo de sonido profesional, microfonía inalámbrica y asistencia técnica para el evento.');
        }

        $loc = mb_strtolower((string)($this->event->location ?? ''));
        if (str_contains($loc, 'vitoria') || str_contains($loc, 'álava') || str_contains($loc, 'alava') || str_contains($loc, 'gasteiz') || str_contains($loc, 'bilbao') || str_contains($loc, 'bizkaia') || str_contains($loc, 'gipuzkoa') || str_contains($loc, 'araba')) {
            $this->photobooth_provider = 'vitoria';
        } else {
            $this->photobooth_provider = \App\Models\Setting::get('default_photobooth_provider', 'logrono');
        }

        $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider);
        $defaultPbHours = (int)($pbConfig['default_hours'] ?? ($this->photobooth_provider === 'logrono' ? 3 : 2));
        $this->quote_services['photobooth']['price_2h'] = $pbConfig['photobooth_2h'];
        $this->quote_services['photobooth']['price_3h'] = $pbConfig['photobooth_3h'];
        $this->quote_services['photobooth']['extra_hour'] = $pbConfig['photobooth_extra_hour'] ?? 50;
        $this->quote_services['photobooth']['hours'] = $defaultPbHours;
        $this->quote_services['photobooth']['price'] = $defaultPbHours >= 3 ? $pbConfig['photobooth_3h'] : $pbConfig['photobooth_2h'];
        $this->quote_services['photobooth']['desc'] = $pbConfig['photobooth_desc'];

        $this->quote_services['video_360']['price_2h'] = $pbConfig['video_360_2h'];
        $this->quote_services['video_360']['price_3h'] = $pbConfig['video_360_3h'];
        $this->quote_services['video_360']['extra_hour'] = $pbConfig['video_360_extra_hour'] ?? 60;
        $this->quote_services['video_360']['hours'] = 2;
        $this->quote_services['video_360']['price'] = $pbConfig['video_360_2h'];
        $this->quote_services['video_360']['desc'] = $pbConfig['video_360_desc'];

        $this->quote_services['pack_duo_360']['price_2h'] = $pbConfig['pack_duo_2h'];
        $this->quote_services['pack_duo_360']['price_3h'] = $pbConfig['pack_duo_3h'];
        $this->quote_services['pack_duo_360']['hours'] = 3;
        $this->quote_services['pack_duo_360']['price'] = $pbConfig['pack_duo_3h'];
        $this->quote_services['pack_duo_360']['desc'] = $pbConfig['pack_duo_desc'];

        $this->quote_services['chocolate_fountain']['fountains'] = 1;
        $this->quote_services['chocolate_fountain']['hours'] = 2;
        $this->quote_services['chocolate_fountain']['price_1_2h'] = $pbConfig['chocolate_fountain_1_2h'] ?? 420;
        $this->quote_services['chocolate_fountain']['price_1_3h'] = $pbConfig['chocolate_fountain_1_3h'] ?? 520;
        $this->quote_services['chocolate_fountain']['price_2_2h'] = $pbConfig['chocolate_fountain_2_2h'] ?? 490;
        $this->quote_services['chocolate_fountain']['price_2_3h'] = $pbConfig['chocolate_fountain_2_3h'] ?? 590;
        $this->quote_services['chocolate_fountain']['waffle_extra'] = $pbConfig['chocolate_fountain_waffle_extra'] ?? 2.90;
        $this->quote_services['chocolate_fountain']['price'] = $pbConfig['chocolate_fountain_1_2h'] ?? $pbConfig['chocolate_fountain_price'] ?? 420;
        $this->quote_services['chocolate_fountain']['desc'] = $pbConfig['chocolate_fountain_desc'];

        $this->quote_services['candy_bar']['price'] = $pbConfig['candy_bar_price'] ?? 420;
        $this->quote_services['candy_bar']['desc'] = $pbConfig['candy_bar_desc'] ?? 'Mesa de Solo Chuches: Incluye decoración a elegir, gran variado de chuches (eligiendo las favoritas de los anfitriones) y globos decorativos. Se monta un par de horas antes y se recoge a la noche o al día siguiente.';

        $this->quote_services['waffle_cart']['hours'] = 2;
        $this->quote_services['waffle_cart']['price_2h'] = $pbConfig['waffle_cart_2h'] ?? 420;
        $this->quote_services['waffle_cart']['price_3h'] = $pbConfig['waffle_cart_3h'] ?? 550;
        $this->quote_services['waffle_cart']['price'] = $pbConfig['waffle_cart_2h'] ?? 420;
        $this->quote_services['waffle_cart']['desc'] = $pbConfig['waffle_cart_desc'] ?? 'Carrito de Gofres hechos al momento: Incluye 4 siropes a elegir (Kinder, Lotus, Nutella, chocolate blanco, chocolate con leche, pistacho...), 4 ingredientes secos (galleta Lotus molida, Oreo, mini Lacasitos, mini nubes...), decoración del carrito con globos y banderín con nombre, servilletas, bandejas y cubiertos. (2h ~60 gofres / 3h ~100 gofres).';

        $this->quote_services['smoke_machine']['price'] = (float)\App\Models\Setting::get('price_effects_smoke', 60);
        $this->quote_services['cold_sparks']['price'] = (float)\App\Models\Setting::get('price_effects_sparks', 60);
        $this->quote_services['cold_sparks']['quantity'] = (int)($this->quote_services['cold_sparks']['quantity'] ?? 2) ?: 2;
        $this->quote_services['extra_hours']['price'] = $getPrice('price_extra_hours', 120);

        // Fotografía
        $this->quote_services['photo_ceremony']['price'] = (float)\App\Models\Setting::get('price_photo_ceremony', 250);
        $this->quote_services['photo_restaurant']['price'] = (float)\App\Models\Setting::get('price_photo_restaurant', 250);
        $this->quote_services['photo_party']['price'] = (float)\App\Models\Setting::get('price_photo_party', 300);
        $this->quote_services['photo_album']['price'] = (float)\App\Models\Setting::get('price_photo_album', 150);
        $this->quote_services['photo_full_pack']['price'] = (float)\App\Models\Setting::get('price_photo_full_pack', 800);

        // Condiciones de Señal (automática y gradual del 25% al 50% según antelación hasta la fecha del evento)
        $gradualPct = \App\Models\Quote::calculateGradualDepositPercentage($this->event?->event_date);
        $savedDepTypeByEv = \App\Models\Setting::get("deposit_type_{$evType}");
        if ($this->event && in_array($evType, ['fiesta', 'otro'], true) && ($savedDepTypeByEv === null || $savedDepTypeByEv === '' || $savedDepTypeByEv === 'none')) {
            $this->quote_deposit_type = 'none';
            $this->quote_deposit_percentage = 0;
            $this->quote_deposit_fixed_amount = 0;
        } elseif ($savedDepTypeByEv === 'fixed') {
            $this->quote_deposit_type = 'fixed';
            $this->quote_deposit_percentage = $gradualPct;
            $this->quote_deposit_fixed_amount = (float)\App\Models\Setting::get("deposit_fixed_amount_{$evType}", \App\Models\Setting::get('deposit_fixed_amount', 200));
        } else {
            $this->quote_deposit_type = 'percentage';
            $this->quote_deposit_percentage = $gradualPct;
            $this->quote_deposit_fixed_amount = (float)\App\Models\Setting::get('deposit_fixed_amount', 200);
        }

        // Condiciones de IVA y Métodos de Pago
        $this->quote_tax_type = \App\Models\Setting::get('default_tax_type', 'included');
        $this->quote_tax_rate = (float)\App\Models\Setting::get('default_tax_rate', 21.00);
        $savedPm = \App\Models\Setting::get('default_payment_methods');
        if ($savedPm) {
            $decoded = json_decode($savedPm, true);
            if (is_array($decoded) && !empty($decoded)) {
                $this->quote_payment_methods = $decoded;
            }
        }

        $user = auth()->user();
        $userBrand = null;
        if ($user) {
            $uCheck = mb_strtolower(($user->name ?? '') . ' ' . ($user->email ?? ''));
            if (str_contains($uCheck, 'fran') || str_contains($uCheck, 'javnx')) {
                $userBrand = 'fran';
            } elseif (str_contains($uCheck, 'ezequiel')) {
                $userBrand = 'ezequiel';
            } elseif (str_contains($uCheck, 'miguel')) {
                $userBrand = 'miguel';
            }
        }
        $this->whatsapp_sender_brand = $userBrand ?? (($this->event->brand_clean === 'javnx') ? 'fran' : 'miguel');
    }

    public $showCancellationModal = false;
    public $cancellation_target_status = 'rejected';
    public $cancellation_reason_input = '';

    public function openCancellationModal(string $targetStatus = 'rejected')
    {
        $this->cancellation_target_status = in_array($targetStatus, ['rejected', 'cancelled']) ? $targetStatus : 'rejected';
        $this->cancellation_reason_input = $this->event->cancellation_reason_display ?? '';
        $this->showCancellationModal = true;
    }

    public function setPresetCancellationReason(string $reason)
    {
        $this->cancellation_reason_input = $reason;
    }

    public function closeCancellationModal()
    {
        $this->showCancellationModal = false;
    }

    public function confirmCancellationStatus()
    {
        $reason = trim((string)$this->cancellation_reason_input);
        if ($reason === '') {
            session()->flash('cancellation_error', 'Por favor, selecciona o escribe el motivo antes de confirmar.');
            return;
        }

        $status = in_array($this->cancellation_target_status, ['rejected', 'cancelled'])
            ? $this->cancellation_target_status
            : 'rejected';

        $labelUpper = $status === 'rejected' ? '❌ EVENTO RECHAZADO' : '🔴 EVENTO CANCELADO';
        $userName = auth()->user()?->name ?? 'Sistema';
        $noteEntry = "[" . now()->format('d/m/Y H:i') . "] {$labelUpper} por {$userName} — Motivo: {$reason}";

        $newNotes = \App\Models\Event::prependNote($this->event->notes, $noteEntry);

        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('events', 'cancellation_reason')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `events` ADD COLUMN `cancellation_reason` TEXT NULL AFTER `no_response_at`");
            }
        } catch (\Throwable $e) {}

        $updateData = [
            'status' => $status,
            'notes' => $newNotes,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('events', 'no_response_at')) {
            $updateData['no_response_at'] = null;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('events', 'cancellation_reason')) {
            $updateData['cancellation_reason'] = $reason;
        }

        $this->event->update($updateData);
        $this->event->refresh();
        $this->event_notes = $this->event->notes;
        $this->showCancellationModal = false;

        $statusText = $status === 'rejected' ? 'Rechazado' : 'Cancelado';
        session()->flash('message', "📌 Estado actualizado a {$statusText}. Motivo registrado: {$reason}");
    }

    public function changeStatus($status)
    {
        if ($status === 'confirmed') {
            $this->openDepositModal('confirmed');
            return;
        }

        if ($status === 'completed') {
            $this->openFinalPaymentModal();
            return;
        }

        if (in_array($status, ['rejected', 'cancelled'])) {
            $this->openCancellationModal($status);
            return;
        }

        $updateData = ['status' => $status];
        if (\Illuminate\Support\Facades\Schema::hasColumn('events', 'no_response_at')) {
            $updateData['no_response_at'] = in_array($status, ['no_response', 'on_hold'], true) ? now() : null;
        }
        if ($status === 'on_hold') {
            $userName = auth()->user()?->name ?? 'Sistema';
            $pauseNote = "[" . now()->format('d/m/Y H:i') . "] ⏸️ Evento puesto En Espera por {$userName} (el cliente indicó que se pondrá en contacto). Contador de días en pausa iniciado.";
            $updateData['notes'] = \App\Models\Event::prependNote($this->event->notes, $pauseNote);
        }

        try {
            $this->event->update($updateData);
        } catch (\Illuminate\Database\QueryException $e) {
            // Auto-heal if MySQL table still has the legacy ENUM column without 'no_response' / 'on_hold'
            if (str_contains($e->getMessage(), 'Data truncated') || str_contains($e->getMessage(), '1265')) {
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `events` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'draft'");
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('events', 'no_response_at')) {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `events` ADD COLUMN `no_response_at` TIMESTAMP NULL AFTER `status`");
                    }
                    if (in_array($status, ['no_response', 'on_hold'], true)) {
                        $updateData['no_response_at'] = now();
                    }
                    $this->event->update($updateData);
                } catch (\Throwable $innerEx) {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }

        $this->event->refresh();
        $this->event_notes = $this->event->notes;

        if ($status === 'no_response') {
            session()->flash('message', '⏳ Estado actualizado a: Sin Respuesta. Se ha activado el recordatorio de seguimiento.');
        } elseif ($status === 'on_hold') {
            session()->flash('message', '⏸️ Estado actualizado a: En Espera (En Pausa). Se mostrarán los días en pausa sin alerta de sin respuesta y te avisará si supera los 30 días de validez del presupuesto.');
        } else {
            session()->flash('message', 'Estado del evento actualizado a: ' . ($this->event->status_label ?? ucfirst($status)));
        }
    }

    public $showConfirmFollowupModal = false;
    public $confirmingFollowupSelectedKey = 'dia3_dudas';
    public $confirmingFollowupOptions = [];
    public $confirmingFollowupUrl = '';
    public $confirmingFollowupText = '';
    public $confirmingFollowupEmail = '';
    public $confirmingFollowupSubject = '';
    public $confirmingFollowupMailtoUrl = '';

    public function promptFollowupFromShow(?string $templateKey = null)
    {
        $this->confirmingFollowupEmail = $this->event->client_contact_email ?: $this->email_chat_to;
        $this->confirmingFollowupOptions = \App\Services\WhatsAppTemplateService::getFollowupOptions($this->event);
        $key = $templateKey ?: \App\Services\WhatsAppTemplateService::getRecommendedFollowupKey($this->event);
        $this->selectFollowupTemplateFromShow($key);
        $this->showConfirmFollowupModal = true;
    }

    public function selectFollowupTemplateFromShow(string $key)
    {
        $this->confirmingFollowupSelectedKey = $key;
        if (isset($this->confirmingFollowupOptions[$key])) {
            $opt = $this->confirmingFollowupOptions[$key];
            $this->confirmingFollowupUrl = $opt['url'] ?? '';
            $this->confirmingFollowupText = $opt['text'] ?? '';
            $this->confirmingFollowupSubject = $opt['subject'] ?? \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, $key);
            $this->confirmingFollowupMailtoUrl = \App\Services\WhatsAppTemplateService::buildMailtoUrl(
                $this->confirmingFollowupEmail,
                $this->confirmingFollowupSubject,
                $this->confirmingFollowupText
            );
        }
    }

    public function updatedConfirmingFollowupEmail($value)
    {
        $this->confirmingFollowupMailtoUrl = \App\Services\WhatsAppTemplateService::buildMailtoUrl(
            $value,
            $this->confirmingFollowupSubject,
            $this->confirmingFollowupText
        );
    }

    public function updatedConfirmingFollowupSubject($value)
    {
        $this->confirmingFollowupMailtoUrl = \App\Services\WhatsAppTemplateService::buildMailtoUrl(
            $this->confirmingFollowupEmail,
            $value,
            $this->confirmingFollowupText
        );
    }

    public function confirmFollowupSentFromShow()
    {
        $templateName = $this->confirmingFollowupOptions[$this->confirmingFollowupSelectedKey]['title'] ?? 'Seguimiento Madrigal';
        $newEntry = "[" . now()->format('d/m/Y H:i') . "] ✅ Seguimiento enviado por " . auth()->user()->name . " ({$templateName}). Contador reseteado a 0.";
        $this->event->update([
            'no_response_at' => now(),
            'notes' => \App\Models\Event::prependNote($this->event->notes, $newEntry),
        ]);
        $this->event->refresh();
        $this->event_notes = $this->event->notes;
        $this->showConfirmFollowupModal = false;
        session()->flash('message', "¡Seguimiento registrado! ({$templateName}). El contador se ha reseteado a 0.");
    }

    public function sendDirectFollowupFromShow()
    {
        $phone = $this->event->client ? $this->event->client->phone : $this->event->partner_phone;
        if (empty($phone)) {
            session()->flash('whatsapp_error', 'El cliente no tiene número de teléfono registrado.');
            return;
        }

        $text = $this->confirmingFollowupText;
        $templateName = $this->confirmingFollowupOptions[$this->confirmingFollowupSelectedKey]['title'] ?? 'Seguimiento Madrigal';
        if (empty($text)) {
            return;
        }

        $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;
        $senderLabel = $this->getSenderBrandLabel();

        $result = \App\Services\EvolutionWhatsAppService::sendMessage(
            $phone,
            $text,
            $senderBrand,
            $this->event->id,
            auth()->id()
        );

        $newEntry = "[" . now()->format('d/m/Y H:i') . "] 💬 WhatsApp ({$templateName}) enviado desde {$senderLabel} por " . auth()->user()->name . ". Contador reseteado a 0.";
        $this->event->update([
            'no_response_at' => now(),
            'notes' => \App\Models\Event::prependNote($this->event->notes, $newEntry),
        ]);
        $this->event->refresh();
        $this->event_notes = $this->event->notes;
        $this->event->load('whatsappMessages.user');
        $this->showConfirmFollowupModal = false;

        if ($result['mode'] === 'api' && $result['success']) {
            session()->flash('message', "✅ ¡Seguimiento ({$templateName}) enviado directamente desde el WhatsApp de {$senderLabel}!");
        } else {
            $this->dispatch('open-whatsapp-link', ['url' => $result['url']]);
            session()->flash('message', "💬 Abriendo WhatsApp con el mensaje de seguimiento ({$templateName})...");
        }
    }

    public function sendDirectEmailFromShow()
    {
        $toEmail = strtolower(trim((string)($this->confirmingFollowupEmail ?: $this->event->client_contact_email)));
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            session()->flash('error', 'Introduce un email válido del cliente para enviar el seguimiento.');
            return;
        }

        $text = $this->confirmingFollowupText;
        $templateName = $this->confirmingFollowupOptions[$this->confirmingFollowupSelectedKey]['title'] ?? 'Seguimiento Madrigal';
        $subject = trim((string)$this->confirmingFollowupSubject) ?: \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, $this->confirmingFollowupSelectedKey);

        if (empty($text)) {
            return;
        }

        // Si el cliente no tenía email guardado o tenía uno ficticio, guardar este email en su ficha
        if ($this->event->client && (empty($this->event->client->email) || str_ends_with($this->event->client->email, '@eventosmusicales.local'))) {
            try {
                $this->event->client->update(['email' => $toEmail]);
            } catch (\Throwable $e) {}
        }

        $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;
        $result = \App\Services\WhatsAppTemplateService::sendAndLogClientEmail(
            $this->event,
            $toEmail,
            $subject,
            $text,
            $senderBrand,
            auth()->id(),
            $templateName
        );

        $this->event->refresh();
        $this->event_notes = $this->event->notes;
        $this->email_chat_to = $toEmail;
        $this->event->load('whatsappMessages.user');
        $this->showConfirmFollowupModal = false;

        if ($result['mode'] === 'mailto_fallback' && !empty($result['mailto_url'])) {
            $this->dispatch('open-whatsapp-link', ['url' => $result['mailto_url']]);
        }

        session()->flash('message', $result['message']);
        session()->flash('whatsapp_success', $result['message']);
    }

    public function markAsRespondedFromShow(string $newStatus = 'draft')
    {
        $this->showConfirmFollowupModal = false;
        $this->changeStatus($newStatus);
    }

    public function cancelFollowupFromShow()
    {
        $this->showConfirmFollowupModal = false;
    }

    public function changeEventType($type)
    {
        if ($type === 'video' && !\App\Models\Setting::isVideoServiceEnabled($this->event->brand_clean)) {
            session()->flash('error', 'El servicio de vídeo no está habilitado para esta marca comercial.');
            return;
        }
        $this->event->update(['event_type' => $type]);
        $this->event->refresh();
        if (!$this->editing_quote_id) {
            $this->loadSettingsPrices();
        }
        session()->flash('message', "Tipo de evento actualizado a: {$this->event->event_type_label} (formularios y tarifas adaptados)");
    }

    public function changeBrand($brand)
    {
        $update = ['brand' => $brand];
        if ($this->event->event_type_clean === 'video' && !\App\Models\Setting::isVideoServiceEnabled($brand)) {
            $update['event_type'] = 'boda';
        }
        $this->event->update($update);
        $this->event->refresh();
        if (!$this->editing_quote_id) {
            $this->loadSettingsPrices();
        }
        $brandLabel = $brand === 'javnx' ? 'JAVNX DJ' : ($brand === 'mago_leugim' ? 'Mago Leugim' : 'Núñez and Son');
        session()->flash('message', "Marca comercial del evento cambiada a: {$brandLabel} (servicios y tarifas adaptados)");
    }

    public function deleteEvent()
    {
        if (auth()->user()->role !== 'admin') {
            session()->flash('error', 'Solo los administradores pueden eliminar eventos.');
            return;
        }

        $name = $this->event->name;
        $eventId = (int) $this->event->id;

        try {
            \App\Services\ImapLeadFetcherService::markEventAsDeleted($this->event);
            \App\Services\AppNotificationService::removeByEventId($eventId);
        } catch (\Throwable $e) {}

        // Cascade delete relations
        foreach ($this->event->quotes as $quote) {
            $quote->items()->delete();
            $quote->delete();
        }
        $this->event->invoices()->delete();
        $this->event->contracts()->delete();
        $this->event->dossiers()->delete();
        $this->event->playlists()->delete();
        $this->event->musicRequests()->delete();
        try {
            $this->event->whatsappMessages()->delete();
        } catch (\Throwable $e) {}
        $this->event->equipment()->detach();
        $this->event->delete();

        session()->flash('message', "🗑️ El evento '{$name}' ha sido eliminado permanentemente.");
        return redirect()->route('admin.events');
    }

    public function openDepositModal(string $targetStatus = 'confirmed')
    {
        $this->target_status = $targetStatus;

        // Si ya tenía señal cobrada guardada, precargar los valores existentes
        if ($this->event->deposit_paid_amount > 0) {
            $this->deposit_amount_input = (float)$this->event->deposit_paid_amount;
            $this->deposit_method_input = $this->event->deposit_payment_method ?: 'bizum';
            $this->deposit_date_input = $this->event->deposit_paid_at ? \Carbon\Carbon::parse($this->event->deposit_paid_at)->format('Y-m-d') : now()->format('Y-m-d');
            $this->deposit_notes_input = $this->event->deposit_notes ?: '';
        } else {
            // Sugerir la señal estipulada en la última propuesta
            $latestQuote = $this->event->quotes()->latest()->first();
            if ($latestQuote) {
                $this->deposit_amount_input = (float)$latestQuote->signal_amount;
            } else {
                if ($this->event && $this->event->event_type_clean === 'fiesta') {
                    $this->deposit_amount_input = 0;
                } else {
                    $depType = \App\Models\Setting::get('deposit_type', 'percentage');
                    if ($depType === 'fixed') {
                        $this->deposit_amount_input = (float)\App\Models\Setting::get('deposit_fixed_amount', 200);
                    } else {
                        $this->deposit_amount_input = 200;
                    }
                }
            }
            $this->deposit_method_input = 'bizum';
            $this->deposit_date_input = now()->format('Y-m-d');
            $this->deposit_notes_input = '';
        }

        $this->generate_receipt_checkbox = true;
        $this->showDepositModal = true;
    }

    public function closeDepositModal()
    {
        $this->showDepositModal = false;
    }

    public function saveDepositAndStatus()
    {
        $this->validate([
            'deposit_amount_input' => 'required|numeric|min:0',
            'deposit_method_input' => 'required|in:bizum,transfer,cash,card,other',
            'deposit_date_input' => 'required|date',
            'deposit_notes_input' => 'nullable|string|max:500',
        ], [
            'deposit_amount_input.required' => 'Debes indicar el importe cobrado de la señal.',
            'deposit_amount_input.numeric' => 'El importe debe ser un número válido.',
            'deposit_amount_input.min' => 'El importe no puede ser negativo.',
            'deposit_date_input.required' => 'Indica la fecha en que se recibió el pago.',
        ]);

        $amount = (float)$this->deposit_amount_input;
        $hasPaid = $amount > 0;

        $this->event->update([
            'status' => $this->target_status ?: 'confirmed',
            'deposit_paid' => $hasPaid,
            'deposit_paid_amount' => $amount,
            'deposit_payment_method' => $this->deposit_method_input,
            'deposit_paid_at' => $this->deposit_date_input,
            'deposit_notes' => $this->deposit_notes_input,
        ]);

        // Generar recibo de cobro automático si está marcado y el importe es > 0
        if ($this->generate_receipt_checkbox && $hasPaid) {
            $nextNum = \App\Models\Invoice::nextNumber('recibo');
            $methodLabel = match($this->deposit_method_input) {
                'bizum' => 'Bizum',
                'transfer' => 'Transferencia Bancaria',
                'cash' => 'Efectivo',
                'card' => 'Tarjeta / TPV',
                'other' => 'Otro método',
                default => ucfirst($this->deposit_method_input)
            };

            $latestQuote = $this->event->quotes()->latest()->first();

            $this->event->invoices()->create([
                'quote_id' => $latestQuote ? $latestQuote->id : null,
                'invoice_number' => $nextNum,
                'type' => 'recibo',
                'amount' => $amount,
                'tax' => 0.00,
                'tax_rate' => 0.00,
                'total' => $amount,
                'issue_date' => $this->deposit_date_input,
                'status' => 'paid',
                'notes' => "Señal de reserva cobrada vía {$methodLabel} el " . \Carbon\Carbon::parse($this->deposit_date_input)->format('d/m/Y') . ($this->deposit_notes_input ? " ({$this->deposit_notes_input})" : ""),
            ]);
            $this->event->load('invoices');
        }

        $this->showDepositModal = false;
        $this->event->refresh();

        session()->flash('message', "✅ Evento actualizado a '" . ucfirst($this->event->status) . "' y señal de " . number_format($amount, 2, ',', '.') . " € registrada correctamente.");
    }

    public function removeDeposit()
    {
        $this->event->update([
            'deposit_paid' => false,
            'deposit_paid_amount' => 0,
            'deposit_payment_method' => null,
            'deposit_paid_at' => null,
            'deposit_notes' => null,
        ]);

        $this->showDepositModal = false;
        $this->event->refresh();

        session()->flash('message', "ℹ️ Datos de la señal de reserva reseteados.");
    }

    public function openFinalPaymentModal()
    {
        $latestQuote = $this->event->quotes()->latest()->first();
        $quoteTotal = $latestQuote ? (float)$latestQuote->amount : 0;
        
        $alreadyPaid = (float)($this->event->deposit_paid_amount ?? 0);
        $invoicesPaid = (float)$this->event->invoices()->where('status', 'paid')->sum('total');
        $totalPaid = max($alreadyPaid, $invoicesPaid);
        $remaining = max(0, $quoteTotal - $totalPaid);

        $this->final_payment_amount = $remaining;
        $this->final_payment_method = 'cash';
        $this->final_payment_date = now()->format('Y-m-d');
        $this->final_payment_notes = '';
        $this->generate_final_receipt_checkbox = true;
        $this->showFinalPaymentModal = true;
    }

    public function closeFinalPaymentModal()
    {
        $this->showFinalPaymentModal = false;
    }

    public function saveFinalPaymentAndComplete()
    {
        $this->validate([
            'final_payment_amount' => 'required|numeric|min:0',
            'final_payment_method' => 'required|in:bizum,transfer,cash,card,other',
            'final_payment_date' => 'required|date',
            'final_payment_notes' => 'nullable|string|max:500',
        ], [
            'final_payment_amount.required' => 'Debes indicar el importe de la liquidación final.',
            'final_payment_amount.numeric' => 'El importe debe ser un número válido.',
            'final_payment_amount.min' => 'El importe no puede ser negativo.',
            'final_payment_date.required' => 'Indica la fecha en que se recibió el cobro.',
        ]);

        $amount = (float)$this->final_payment_amount;

        $this->event->update([
            'status' => 'completed',
        ]);

        if ($this->generate_final_receipt_checkbox && $amount > 0) {
            $nextNum = \App\Models\Invoice::nextNumber('recibo');
            $methodLabel = match($this->final_payment_method) {
                'bizum' => 'Bizum',
                'transfer' => 'Transferencia Bancaria',
                'cash' => 'Efectivo',
                'card' => 'Tarjeta / TPV',
                'other' => 'Otro método',
                default => ucfirst($this->final_payment_method)
            };

            $latestQuote = $this->event->quotes()->latest()->first();

            $this->event->invoices()->create([
                'quote_id' => $latestQuote ? $latestQuote->id : null,
                'invoice_number' => $nextNum,
                'type' => 'recibo',
                'amount' => $amount,
                'tax' => 0.00,
                'tax_rate' => 0.00,
                'total' => $amount,
                'issue_date' => $this->final_payment_date,
                'status' => 'paid',
                'notes' => "Liquidación final del evento cobrada vía {$methodLabel} el " . \Carbon\Carbon::parse($this->final_payment_date)->format('d/m/Y') . ($this->final_payment_notes ? " ({$this->final_payment_notes})" : ""),
            ]);
            $this->event->load('invoices');
        }

        $this->showFinalPaymentModal = false;
        $this->event->refresh();

        session()->flash('message', "🎉 Evento marcado como 'Completado' y cobro final de " . number_format($amount, 2, ',', '.') . " € registrado correctamente con recibo.");
    }

    public function openStaffModal()
    {
        $this->assigned_dj_id = $this->event->dj_id;
        $this->assigned_assistant_id = $this->event->assistant_id;
        $this->assigned_assistant_ids = $this->event->all_assistants->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->showStaffModal = true;
    }

    public function saveStaff()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $migEx) {}
        }

        $this->validate([
            'assigned_dj_id' => 'nullable|exists:users,id',
            'assigned_assistant_ids' => 'nullable|array',
            'assigned_assistant_ids.*' => 'exists:users,id',
        ]);

        $firstAssistantId = !empty($this->assigned_assistant_ids) ? (int)$this->assigned_assistant_ids[0] : null;

        $this->event->update([
            'dj_id' => $this->assigned_dj_id ?: null,
            'assistant_id' => $firstAssistantId,
        ]);

        if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
            $this->event->assistants()->sync($this->assigned_assistant_ids ?: []);
        }

        $relations = ['dj', 'assistant'];
        if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
            $relations[] = 'assistants';
        }
        $this->event->load($relations);
        $this->assigned_assistant_ids = $this->event->all_assistants->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->showStaffModal = false;
        session()->flash('message', 'Personal asignado (DJ y Asistentes) actualizado correctamente.');
    }

    public function openClientModal()
    {
        $this->resetValidation();
        $client = $this->event->client;
        if ($client) {
            $this->selected_client_id = (string)$client->id;
            $this->client_edit_name = $client->name;
            $this->client_edit_phone = $client->phone;
            $this->client_edit_email = str_ends_with($client->email, '@eventosmusicales.local') ? '' : $client->email;
            $this->client_edit_username = $client->username;
            $this->client_edit_dni = $client->dni ?? $client->nif;
            $this->client_edit_address = $client->address;
            $this->client_edit_postal_code = $client->postal_code;
            $this->client_edit_city = $client->city;
            $this->client_edit_province = $client->province;
            $this->client_edit_password = '';
        } else {
            $this->selected_client_id = '';
            $this->client_edit_name = '';
            $this->client_edit_phone = '';
            $this->client_edit_email = '';
            $this->client_edit_username = '';
            $this->client_edit_dni = '';
            $this->client_edit_address = '';
            $this->client_edit_postal_code = '';
            $this->client_edit_city = '';
            $this->client_edit_province = '';
            $this->client_edit_password = '';
        }

        $this->partner_edit_name = $this->event->partner_name;
        $this->partner_edit_phone = $this->event->partner_phone;
        $this->partner_edit_email = $this->event->partner_email;
        $this->partner_edit_dni = $this->event->partner_dni;

        if (!empty($this->client_edit_dni)) {
            $this->validateAndCompleteDni('client_edit_dni', $this->client_edit_dni);
        } else {
            $this->dniValidations['client_edit_dni'] = null;
        }

        if (!empty($this->partner_edit_dni)) {
            $this->validateAndCompleteDni('partner_edit_dni', $this->partner_edit_dni);
        } else {
            $this->dniValidations['partner_edit_dni'] = null;
        }

        $this->showClientModal = true;
    }

    public function updatedClientEditDni($value)
    {
        $this->validateAndCompleteDni('client_edit_dni', $value);
    }

    public function updatedPartnerEditDni($value)
    {
        $this->validateAndCompleteDni('partner_edit_dni', $value);
    }

    public function updatedSelectedClientId($value)
    {
        if (!empty($value)) {
            $c = User::where('role', 'client')->find($value);
            if ($c) {
                $this->client_edit_name = $c->name;
                $this->client_edit_phone = $c->phone;
                $this->client_edit_email = str_ends_with($c->email, '@eventosmusicales.local') ? '' : $c->email;
                $this->client_edit_username = $c->username;
                $this->client_edit_dni = $c->dni ?? $c->nif;
                $this->client_edit_address = $c->address;
                $this->client_edit_postal_code = $c->postal_code;
                $this->client_edit_city = $c->city;
                $this->client_edit_province = $c->province;
                $this->client_edit_password = '';

                if (!empty($this->client_edit_dni)) {
                    $this->validateAndCompleteDni('client_edit_dni', $this->client_edit_dni);
                } else {
                    $this->dniValidations['client_edit_dni'] = null;
                }
            }
        } else {
            $this->client_edit_name = '';
            $this->client_edit_phone = '';
            $this->client_edit_email = '';
            $this->client_edit_username = '';
            $this->client_edit_dni = '';
            $this->client_edit_address = '';
            $this->client_edit_postal_code = '';
            $this->client_edit_city = '';
            $this->client_edit_province = '';
            $this->client_edit_password = '';
            $this->dniValidations['client_edit_dni'] = null;
        }
    }

    public function updatedClientEditPostalCode($value)
    {
        $lookup = \App\Services\PostalCodeService::lookup($value);
        if (!empty($lookup['city']) && empty($this->client_edit_city)) {
            $this->client_edit_city = $lookup['city'];
        }
        if (!empty($lookup['province'])) {
            $this->client_edit_province = $lookup['province'];
        }
    }

    public function saveClientDetails()
    {
        // Auto-migrar si la columna partner_name no existe todavía
        if (!\Illuminate\Support\Facades\Schema::hasColumn('events', 'partner_name')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $migEx) {}
        }

        $partnerData = [
            'partner_name' => $this->partner_edit_name ?: null,
            'partner_phone' => $this->partner_edit_phone ?: null,
            'partner_email' => $this->partner_edit_email ?: null,
            'partner_dni' => $this->partner_edit_dni ?: null,
        ];

        if (!empty($this->selected_client_id)) {
            // Existing client selected or editing current client
            // Solo se pueden editar/vincular usuarios con rol cliente (evita tomar cuentas de staff/admin)
            $client = User::where('role', 'client')->findOrFail($this->selected_client_id);

            $this->validate([
                'client_edit_name' => 'required|string|max:255',
                'client_edit_phone' => 'nullable|string|max:50',
                'client_edit_email' => ['nullable', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($client->id)],
                'client_edit_username' => ['nullable', 'string', 'max:255', \Illuminate\Validation\Rule::unique('users', 'username')->ignore($client->id)],
                'client_edit_dni' => 'nullable|string|max:50',
                'client_edit_address' => 'nullable|string|max:255',
                'client_edit_postal_code' => 'nullable|string|max:10',
                'client_edit_city' => 'nullable|string|max:100',
                'client_edit_province' => 'nullable|string|max:100',
                'client_edit_password' => 'nullable|string|min:4',
                'partner_edit_name' => 'nullable|string|max:255',
                'partner_edit_phone' => 'nullable|string|max:50',
                'partner_edit_email' => 'nullable|email|max:255',
                'partner_edit_dni' => 'nullable|string|max:50',
            ], [
                'client_edit_name.required' => 'El nombre del cliente es obligatorio.',
                'client_edit_email.unique' => 'Este email ya está en uso por otro usuario.',
                'client_edit_username.unique' => 'Este nombre de usuario ya existe.',
                'client_edit_password.min' => 'La contraseña debe tener al menos 4 caracteres.',
            ]);

            $updateData = [
                'name' => $this->client_edit_name,
                'phone' => $this->client_edit_phone ?: null,
                'username' => $this->client_edit_username ?: null,
                'dni' => $this->client_edit_dni ?: null,
                'address' => $this->client_edit_address ?: null,
                'postal_code' => $this->client_edit_postal_code ?: null,
                'city' => $this->client_edit_city ?: null,
                'province' => $this->client_edit_province ?: null,
            ];

            if (!empty($this->client_edit_email)) {
                $updateData['email'] = $this->client_edit_email;
            }

            if (!empty($this->client_edit_password)) {
                $updateData['password'] = \Illuminate\Support\Facades\Hash::make($this->client_edit_password);
            }

            $client->update($updateData);

            $this->event->update(array_merge(['client_id' => $client->id], $partnerData));
            $this->event->load('client');
            $this->showClientModal = false;
            session()->flash('message', '¡Cliente y datos de la pareja actualizados correctamente!');
        } else {
            // Creating new client and assigning to event
            $this->validate([
                'client_edit_name' => 'required|string|max:255',
                'client_edit_phone' => 'nullable|string|max:50',
                'client_edit_email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
                'client_edit_username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
                'client_edit_dni' => 'nullable|string|max:50',
                'client_edit_address' => 'nullable|string|max:255',
                'client_edit_postal_code' => 'nullable|string|max:10',
                'client_edit_city' => 'nullable|string|max:100',
                'client_edit_province' => 'nullable|string|max:100',
                'client_edit_password' => 'nullable|string|min:4',
                'partner_edit_name' => 'nullable|string|max:255',
                'partner_edit_phone' => 'nullable|string|max:50',
                'partner_edit_email' => 'nullable|email|max:255',
                'partner_edit_dni' => 'nullable|string|max:50',
            ], [
                'client_edit_name.required' => 'El nombre del cliente es obligatorio.',
                'client_edit_email.unique' => 'Este email ya está en uso por otro usuario.',
                'client_edit_username.unique' => 'Este nombre de usuario ya existe.',
                'client_edit_password.min' => 'La contraseña debe tener al menos 4 caracteres.',
            ]);

            $email = !empty($this->client_edit_email) ? $this->client_edit_email : ('cliente_' . time() . '_' . rand(100, 999) . '@eventosmusicales.local');
            $password = !empty($this->client_edit_password) ? $this->client_edit_password : \Illuminate\Support\Str::random(8);

            $newClient = User::create([
                'name' => $this->client_edit_name,
                'email' => $email,
                'phone' => $this->client_edit_phone ?: null,
                'username' => $this->client_edit_username ?: null,
                'dni' => $this->client_edit_dni ?: null,
                'address' => $this->client_edit_address ?: null,
                'postal_code' => $this->client_edit_postal_code ?: null,
                'city' => $this->client_edit_city ?: null,
                'province' => $this->client_edit_province ?: null,
                'role' => 'client',
                'password' => \Illuminate\Support\Facades\Hash::make($password),
            ]);

            $this->event->update(array_merge(['client_id' => $newClient->id], $partnerData));
            $this->event->load('client');
            $this->showClientModal = false;
            session()->flash('message', '¡Nuevo cliente y datos de pareja guardados con éxito!');
        }
    }

    public function unlinkClient()
    {
        $this->event->update(['client_id' => null]);
        $this->event->load('client');
        $this->showClientModal = false;
        session()->flash('message', 'Cliente desvinculado del evento.');
    }

    public function saveDossier()
    {
        $this->event->update(['is_dossier_completed' => $this->is_dossier_completed]);

        $dossier = $this->event->dossiers()->first();
        if ($dossier) {
            $dossier->update(['content' => $this->dossier_content]);
        } else {
            $this->event->dossiers()->create(['content' => $this->dossier_content]);
        }
        session()->flash('dossier_message', 'Dossier guardado correctamente.');
    }

    public function toggleDossierStatus()
    {
        $this->is_dossier_completed = !$this->is_dossier_completed;
        $this->event->update(['is_dossier_completed' => $this->is_dossier_completed]);
        $statusStr = $this->is_dossier_completed ? '🔒 Cuestionario bloqueado/cerrado.' : '🔓 Cuestionario reabierto para que el cliente pueda editar sus canciones.';
        session()->flash('message', $statusStr);
    }

    // Material / Hoja de Carga Methods
    public function addEquipment()
    {
        $this->validate([
            'selected_equipment_id' => 'required|exists:equipment,id',
            'equipment_quantity' => 'required|integer|min:1',
            'equipment_notes' => 'nullable|string|max:255',
        ]);

        $existing = DB::table('event_equipment')
            ->where('event_id', $this->event->id)
            ->where('equipment_id', $this->selected_equipment_id)
            ->first();

        if ($existing) {
            DB::table('event_equipment')
                ->where('id', $existing->id)
                ->update([
                    'quantity' => $existing->quantity + $this->equipment_quantity,
                    'notes' => $this->equipment_notes ?: $existing->notes,
                    'updated_at' => now(),
                ]);
        } else {
            $this->event->equipment()->attach($this->selected_equipment_id, [
                'quantity' => $this->equipment_quantity,
                'notes' => $this->equipment_notes,
            ]);
        }

        $this->reset(['selected_equipment_id', 'equipment_quantity', 'equipment_notes']);
        $this->equipment_quantity = 1;
        $this->event->load('equipment');
        session()->flash('equipment_message', 'Material añadido al evento correctamente.');
    }

    public function updateEquipmentQuantity($pivotId, $amount)
    {
        $item = DB::table('event_equipment')->where('event_id', $this->event->id)->where('id', $pivotId)->first();
        if ($item) {
            $newQty = $item->quantity + $amount;
            if ($newQty > 0) {
                DB::table('event_equipment')->where('event_id', $this->event->id)->where('id', $pivotId)->update([
                    'quantity' => $newQty,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('event_equipment')->where('event_id', $this->event->id)->where('id', $pivotId)->delete();
            }
            $this->event->load('equipment');
        }
    }

    public function removeEquipment($pivotId)
    {
        DB::table('event_equipment')->where('event_id', $this->event->id)->where('id', $pivotId)->delete();
        $this->event->load('equipment');
        session()->flash('equipment_message', 'Material eliminado del evento.');
    }

    public function updatedMusicSearchQuery()
    {
        if (strlen(trim($this->musicSearchQuery)) >= 2) {
            $this->musicSearchResults = \App\Services\MusicSearchService::search($this->musicSearchQuery, 5);
        } else {
            $this->musicSearchResults = [];
        }
    }

    public function selectTrackFromSearch($track)
    {
        $this->req_title = $track['title'] ?? '';
        $this->req_artist = $track['artist'] ?? '';
        $this->req_spotify_url = $track['spotify_url'] ?? '';
        $this->req_apple_music_url = $track['apple_music_url'] ?? '';
        $this->req_tidal_url = $track['tidal_url'] ?? '';
        $this->req_youtube_url = $track['youtube_url'] ?? '';
        if (!empty($track['is_drive_library']) && !empty($track['preview_url'])) {
            $this->existing_audio_file = $track['preview_url'];
        } else {
            $this->existing_audio_file = null;
        }
        $this->musicSearchResults = [];
        $this->musicSearchQuery = '';
    }

    public function openSongModal($songId = null)
    {
        $this->resetValidation();
        $this->reset([
            'req_title', 'req_artist', 'req_requested_by', 'req_notes',
            'req_youtube_url', 'req_spotify_url', 'req_apple_music_url', 'req_tidal_url', 'req_audio_file', 'existing_audio_file', 'req_cue_time',
            'musicSearchQuery', 'musicSearchResults'
        ]);

        if ($songId) {
            $song = $this->event->musicRequests()->findOrFail($songId);
            $this->editingSongId = $song->id;
            $this->req_category = $song->category;
            $this->req_moment = $song->moment;
            $this->req_title = $song->title;
            $this->req_artist = $song->artist;
            $this->req_requested_by = $song->requested_by;
            $this->req_notes = $song->notes;
            $this->req_youtube_url = $song->youtube_url;
            $this->req_spotify_url = $song->spotify_url;
            $this->req_apple_music_url = $song->apple_music_url;
            $this->req_tidal_url = $song->tidal_url;
            $this->existing_audio_file = $song->audio_file;
            $this->req_cue_time = $song->cue_time;
            $this->req_status = $song->status;
        } else {
            $this->editingSongId = null;
            $this->req_category = $this->music_active_category !== 'all' ? $this->music_active_category : 'baile';
            $isWedding = $this->event ? ($this->event->event_type_clean === 'boda') : true;
            $this->req_moment = match($this->req_category) {
                'ceremonia' => $isWedding ? 'Entrada Novios' : 'Recepción / Bienvenida',
                'coctel' => 'Música Ambiente',
                'banquete' => $isWedding ? 'Entrada Comedor' : 'Fondo Almuerzo / Cena',
                'baile' => 'Baile / Fiesta',
                'lista_negra' => 'Prohibida / Lista Negra',
                default => 'Baile / Fiesta',
            };
            $this->req_status = 'pending';
        }

        $this->showSongModal = true;
    }

    public function removeExistingAudioFile()
    {
        $this->existing_audio_file = null;
        $this->req_audio_file = null;
        if ($this->editingSongId) {
            EventMusicRequest::where('event_id', $this->event->id)->where('id', $this->editingSongId)->update(['audio_file' => null]);
            $this->event->load('musicRequests');
        }
        session()->flash('music_modal_message', '🗑️ Archivo de audio eliminado. Ahora se reproducirá mediante Spotify / Apple Music / YouTube.');
    }

    public function updatedReqCategory($val)
    {
        if (!$this->editingSongId) {
            $isWedding = $this->event ? ($this->event->event_type_clean === 'boda') : true;
            $this->req_moment = match($val) {
                'ceremonia' => $isWedding ? 'Entrada Novios' : 'Recepción / Bienvenida',
                'coctel' => 'Música Ambiente',
                'banquete' => $isWedding ? 'Entrada Comedor' : 'Fondo Almuerzo / Cena',
                'baile' => 'Baile / Fiesta',
                'lista_negra' => 'Prohibida / Lista Negra',
                default => 'Baile / Fiesta',
            };
        }
    }

    public function autoFillTrackLinks()
    {
        if (empty($this->req_title)) {
            return;
        }

        $meta = \App\Services\MusicSearchService::resolveTrackMetadata($this->req_title, $this->req_artist ?? '');
        if (!empty($meta['spotify_url'])) {
            $this->req_spotify_url = $meta['spotify_url'];
        }
        if (!empty($meta['apple_music_url'])) {
            $this->req_apple_music_url = $meta['apple_music_url'];
        }
        if (!empty($meta['youtube_url'])) {
            $this->req_youtube_url = $meta['youtube_url'];
        }
        if (!empty($meta['is_drive_library']) && !empty($meta['preview_url'])) {
            $this->existing_audio_file = $meta['preview_url'];
        }

        session()->flash('music_modal_message', '✨ Enlaces de Spotify, Apple Music y YouTube completados.');
    }

    public function autoResolveAllMusicLinks()
    {
        $songs = $this->event->musicRequests;
        $updatedCount = 0;

        foreach ($songs as $song) {
            $needsSpotify = empty($song->spotify_url) || str_contains($song->spotify_url, 'open.spotify.com/search/');
            $needsApple = empty($song->apple_music_url) || str_contains($song->apple_music_url, 'music.apple.com/es/search');
            $needsYoutube = empty($song->youtube_url) || str_contains($song->youtube_url, 'youtube.com/results');
            $needsAudio = empty($song->audio_file);

            if ($needsSpotify || $needsApple || $needsYoutube || $needsAudio) {
                $meta = \App\Services\MusicSearchService::resolveTrackMetadata($song->title, $song->artist ?? '');
                
                $updates = [];
                if ($needsSpotify && !empty($meta['spotify_url'])) {
                    $updates['spotify_url'] = $meta['spotify_url'];
                }
                if ($needsApple && !empty($meta['apple_music_url'])) {
                    $updates['apple_music_url'] = $meta['apple_music_url'];
                }
                if ($needsYoutube && !empty($meta['youtube_url'])) {
                    $updates['youtube_url'] = $meta['youtube_url'];
                }
                if ($needsAudio && !empty($meta['preview_url'])) {
                    $updates['audio_file'] = $meta['preview_url'];
                }

                if (!empty($updates)) {
                    $song->update($updates);
                    $updatedCount++;
                }
            }
        }

        $this->event->load('musicRequests');
        session()->flash('music_message', "✨ Se han completado y actualizado automáticamente los audios y enlaces de {$updatedCount} canciones.");
    }

    public function autoResolveSongAudio($songId)
    {
        $song = $this->event->musicRequests()->findOrFail($songId);
        $meta = \App\Services\MusicSearchService::resolveTrackMetadata($song->title, $song->artist ?? '');
        $updates = [];
        if (!empty($meta['spotify_url']) && empty($song->spotify_url)) {
            $updates['spotify_url'] = $meta['spotify_url'];
        }
        if (!empty($meta['apple_music_url']) && empty($song->apple_music_url)) {
            $updates['apple_music_url'] = $meta['apple_music_url'];
        }
        if (!empty($meta['youtube_url']) && empty($song->youtube_url)) {
            $updates['youtube_url'] = $meta['youtube_url'];
        }
        if (!empty($meta['preview_url'])) {
            $updates['audio_file'] = $meta['preview_url'];
        }

        if (!empty($updates)) {
            $song->update($updates);
        }

        $this->event->load('musicRequests');
        session()->flash('music_message', "✨ Audio y enlaces localizados para '{$song->title}'.");
    }

    public function openCloudImportModal()
    {
        $this->cloudFolderUrl = '';
        $this->cloudImportCategory = $this->music_active_category !== 'all' ? $this->music_active_category : 'banquete';
        $this->cloudImportMoment = match($this->cloudImportCategory) {
            'ceremonia' => 'Entrada Novios',
            'coctel' => 'Música Ambiente',
            'banquete' => 'Entrada Comedor',
            'baile' => 'Baile Nupcial',
            'lista_negra' => 'Prohibida',
            default => 'General',
        };
        $this->cloudImportStatus = null;
        $this->cloudScannedFiles = [];
        $this->showCloudImportModal = true;
    }

    public function scanCloudFolder()
    {
        $this->validate([
            'cloudFolderUrl' => 'required|string',
        ], [
            'cloudFolderUrl.required' => 'Introduce un enlace o ID de carpeta válido.',
        ]);

        $res = \App\Services\CloudMusicStorageService::scanGoogleDriveFolder($this->cloudFolderUrl);
        if ($res['success']) {
            $this->cloudScannedFiles = $res['files'];
            $this->cloudImportStatus = [
                'success' => true,
                'message' => "✓ Se encontraron {$res['count']} archivos de audio en la carpeta."
            ];
        } else {
            $this->cloudScannedFiles = [];
            $this->cloudImportStatus = [
                'success' => false,
                'message' => $res['message']
            ];
        }
    }

    public function confirmImportCloudFolder()
    {
        if (empty($this->cloudScannedFiles)) {
            return;
        }

        $imported = \App\Services\CloudMusicStorageService::importFolderToEvent(
            $this->event->id,
            $this->cloudScannedFiles,
            $this->cloudImportCategory,
            $this->cloudImportMoment
        );

        $this->event->load('musicRequests');
        $this->showCloudImportModal = false;
        session()->flash('music_message', "📁 ¡Se importaron {$imported} canciones de la carpeta de la nube a '{$this->cloudImportMoment}'!");
    }

    public function saveSongRequest()
    {
        $this->validate([
            'req_title' => 'required|string|max:255',
            'req_category' => 'required|string|max:100',
            'req_moment' => 'required|string|max:255',
            'req_artist' => 'nullable|string|max:255',
            'req_requested_by' => 'nullable|string|max:255',
            'req_notes' => 'nullable|string',
            'req_youtube_url' => 'nullable|string|max:1000',
            'req_spotify_url' => 'nullable|string|max:1000',
            'req_apple_music_url' => 'nullable|string|max:1000',
            'req_tidal_url' => 'nullable|string|max:1000',
            'req_audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:25600', // 25MB max
            'req_cue_time' => 'nullable|string|max:50',
            'req_status' => 'nullable|string|max:50',
        ], [
            'req_title.required' => 'El título de la canción es obligatorio.',
            'req_moment.required' => 'El momento o fase es obligatorio.',
            'req_audio_file.mimes' => 'El archivo de audio debe ser MP3, WAV, OGG o M4A.',
            'req_audio_file.max' => 'El archivo de audio no debe superar los 25 MB.',
        ]);

        $audioPath = $this->existing_audio_file;
        if ($this->req_audio_file) {
            $audioPath = $this->req_audio_file->store('music_audios', 'public');
        }

        // Auto-resolver enlaces si alguno quedó en blanco
        if (empty($this->req_spotify_url) || empty($this->req_apple_music_url) || empty($this->req_tidal_url) || empty($this->req_youtube_url)) {
            $meta = \App\Services\MusicSearchService::resolveTrackMetadata($this->req_title, $this->req_artist ?? '');
            if (empty($this->req_spotify_url) && !empty($meta['spotify_url'])) {
                $this->req_spotify_url = $meta['spotify_url'];
            }
            if (empty($this->req_apple_music_url) && !empty($meta['apple_music_url'])) {
                $this->req_apple_music_url = $meta['apple_music_url'];
            }
            if (empty($this->req_tidal_url) && !empty($meta['tidal_url'])) {
                $this->req_tidal_url = $meta['tidal_url'];
            }
            if (empty($this->req_youtube_url) && !empty($meta['youtube_url'])) {
                $this->req_youtube_url = $meta['youtube_url'];
            }
        }

        $finalStatus = $this->req_status ?: 'pending';

        if ($this->editingSongId) {
            $song = $this->event->musicRequests()->findOrFail($this->editingSongId);
            $song->update([
                'category' => $this->req_category,
                'moment' => $this->req_moment,
                'title' => $this->req_title,
                'artist' => $this->req_artist,
                'requested_by' => $this->req_requested_by,
                'notes' => $this->req_notes,
                'youtube_url' => $this->req_youtube_url,
                'spotify_url' => $this->req_spotify_url,
                'apple_music_url' => $this->req_apple_music_url,
                'tidal_url' => $this->req_tidal_url,
                'audio_file' => $audioPath,
                'cue_time' => $this->req_cue_time,
                'status' => $finalStatus,
            ]);
            session()->flash('music_message', 'Canción/Momento actualizado correctamente.');
        } else {
            $maxOrder = EventMusicRequest::where('event_id', $this->event->id)->max('order') ?: 0;
            $this->event->musicRequests()->create([
                'category' => $this->req_category,
                'moment' => $this->req_moment,
                'title' => $this->req_title,
                'artist' => $this->req_artist,
                'requested_by' => $this->req_requested_by,
                'notes' => $this->req_notes,
                'youtube_url' => $this->req_youtube_url,
                'spotify_url' => $this->req_spotify_url,
                'apple_music_url' => $this->req_apple_music_url,
                'tidal_url' => $this->req_tidal_url,
                'audio_file' => $audioPath,
                'cue_time' => $this->req_cue_time,
                'status' => $finalStatus,
                'order' => $maxOrder + 1,
            ]);
            session()->flash('music_message', 'Canción/Momento añadido a la escaleta.');
        }

        $this->showSongModal = false;
        $this->reset([
            'req_title', 'req_artist', 'req_requested_by', 'req_notes',
            'req_youtube_url', 'req_spotify_url', 'req_apple_music_url', 'req_tidal_url',
            'req_audio_file', 'existing_audio_file', 'req_cue_time',
            'musicSearchQuery', 'musicSearchResults', 'editingSongId'
        ]);
        $this->event->load('musicRequests');
    }

    public function toggleSongStatus($songId)
    {
        $song = $this->event->musicRequests()->findOrFail($songId);
        $nextStatus = match($song->status) {
            'pending' => 'ready',
            'ready' => 'played',
            'played' => 'pending',
            default => 'pending',
        };
        $song->update(['status' => $nextStatus]);
        $this->event->load('musicRequests');
    }

    public function deleteSongRequest($songId)
    {
        $song = $this->event->musicRequests()->findOrFail($songId);
        if ($song->audio_file) {
            Storage::disk('public')->delete($song->audio_file);
        }
        $song->delete();
        $this->event->load('musicRequests');
        session()->flash('music_message', 'Canción eliminada de la escaleta.');
    }

    public function moveSongOrder($songId, $direction)
    {
        $song = $this->event->musicRequests()->findOrFail($songId);
        if ($direction === 'up') {
            $prev = EventMusicRequest::where('event_id', $this->event->id)
                ->where('order', '<', $song->order)
                ->orderBy('order', 'desc')
                ->first();
            if ($prev) {
                $temp = $song->order;
                $song->update(['order' => $prev->order]);
                $prev->update(['order' => $temp]);
            }
        } elseif ($direction === 'down') {
            $next = EventMusicRequest::where('event_id', $this->event->id)
                ->where('order', '>', $song->order)
                ->orderBy('order', 'asc')
                ->first();
            if ($next) {
                $temp = $song->order;
                $song->update(['order' => $next->order]);
                $next->update(['order' => $temp]);
            }
        }
        $this->event->load('musicRequests');
    }

    // ==================== FACTURAS Y RECIBOS ====================

    public function updatedInvoiceType($value)
    {
        $this->invoice_number = \App\Models\Invoice::nextNumber($value);
        if ($value === 'recibo') {
            $this->invoice_tax = 0.00;
            $this->invoice_tax_rate = 0.00;
        } else {
            $this->invoice_tax_rate = 21.00;
            if ((float)$this->invoice_amount > 0) {
                $this->invoice_tax = round((float)$this->invoice_amount * 0.21, 2);
            }
        }
    }

    public function updatedInvoiceAmount($value)
    {
        if ($this->invoice_type === 'factura') {
            $rate = (float)($this->invoice_tax_rate ?: 21.00);
            $this->invoice_tax = round((float)$value * ($rate / 100), 2);
        } else {
            $this->invoice_tax = 0.00;
        }
    }

    public function updatedInvoiceTaxRate($value)
    {
        if ($this->invoice_type === 'factura' && (float)$this->invoice_amount > 0) {
            $this->invoice_tax = round((float)$this->invoice_amount * ((float)$value / 100), 2);
        }
    }

    public function loadQuoteIntoInvoice(int $quoteId)
    {
        $quote = \App\Models\Quote::with('items')->find($quoteId);
        if (!$quote) return;

        $this->invoice_quote_id = $quote->id;
        $this->invoice_issue_date = now()->format('Y-m-d');

        if ($quote->tax_type === 'none') {
            $this->invoice_type = 'recibo';
            $this->invoice_amount = (float)$quote->amount;
            $this->invoice_tax = 0.00;
            $this->invoice_tax_rate = 0.00;
        } elseif ($quote->tax_type === 'included') {
            $this->invoice_type = 'factura';
            $this->invoice_tax_rate = (float)($quote->tax_rate ?: 21.00);
            $this->invoice_amount = $quote->computed_subtotal;
            $this->invoice_tax = $quote->computed_tax;
        } elseif ($quote->tax_type === 'excluded') {
            $this->invoice_type = 'factura';
            $this->invoice_tax_rate = (float)($quote->tax_rate ?: 21.00);
            $this->invoice_amount = (float)$quote->amount;
            $this->invoice_tax = $quote->computed_tax;
        } else {
            $this->invoice_type = 'factura';
            $this->invoice_tax_rate = 21.00;
            $this->invoice_amount = round((float)$quote->amount / 1.21, 2);
            $this->invoice_tax = round((float)$quote->amount - $this->invoice_amount, 2);
        }

        $this->invoice_number = \App\Models\Invoice::nextNumber($this->invoice_type);
        $this->invoice_notes = 'Servicios según Propuesta #' . $quote->id;
        $this->activeTab = 'invoices';
        session()->flash('invoice_message', "Propuesta #{$quote->id} cargada en el formulario de " . ($this->invoice_type === 'recibo' ? 'Recibo' : 'Factura') . ".");
    }

    public function convertQuoteToInvoice(int $quoteId, string $type = 'auto')
    {
        $quote = \App\Models\Quote::with('items')->find($quoteId);
        if (!$quote) {
            session()->flash('error_message', 'No se encontró la propuesta indicada.');
            return;
        }

        // Determinar si es Recibo o Factura
        if ($type === 'auto') {
            $type = ($quote->tax_type === 'none') ? 'recibo' : 'factura';
        }

        if ($type === 'recibo') {
            $amount = (float)$quote->amount;
            $tax = 0.00;
            $taxRate = 0.00;
            $total = $amount;
        } else {
            // Factura con IVA
            $taxRate = (float)($quote->tax_rate ?: 21.00);
            if ($quote->tax_type === 'included') {
                $amount = $quote->computed_subtotal;
                $tax = $quote->computed_tax;
                $total = (float)$quote->amount;
            } elseif ($quote->tax_type === 'excluded') {
                $amount = (float)$quote->amount;
                $tax = $quote->computed_tax;
                $total = $amount + $tax;
            } else {
                // Era 'none' pero se solicita explícitamente como Factura
                $amount = (float)$quote->amount;
                $tax = round($amount * ($taxRate / 100), 2);
                $total = $amount + $tax;
            }
        }

        $nextNum = \App\Models\Invoice::nextNumber($type);

        $invoice = $this->event->invoices()->create([
            'quote_id' => $quote->id,
            'invoice_number' => $nextNum,
            'type' => $type,
            'amount' => $amount,
            'tax' => $tax,
            'tax_rate' => $taxRate,
            'total' => $total,
            'issue_date' => now(),
            'status' => 'unpaid',
            'notes' => 'Generado a partir de la Propuesta #' . $quote->id,
        ]);

        $this->event->load('invoices');
        $this->activeTab = 'invoices';
        session()->flash('invoice_message', '¡' . ($type === 'recibo' ? 'Recibo' : 'Factura') . " {$nextNum} generado con éxito a partir de la Propuesta #{$quote->id}!");
    }

    public function createInvoice()
    {
        $this->validate([
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'invoice_type' => 'required|in:factura,recibo',
            'invoice_amount' => 'required|numeric|min:0',
            'invoice_tax' => 'required|numeric|min:0',
            'invoice_issue_date' => 'required|date',
        ], [
            'invoice_number.required' => 'El número de documento es obligatorio.',
            'invoice_number.unique' => 'Este número de factura/recibo ya ha sido utilizado.',
            'invoice_amount.required' => 'El importe base es obligatorio.',
            'invoice_issue_date.required' => 'La fecha de emisión es obligatoria.',
        ]);

        $total = (float)$this->invoice_amount + (float)$this->invoice_tax;

        $this->event->invoices()->create([
            'quote_id' => $this->invoice_quote_id,
            'invoice_number' => $this->invoice_number,
            'type' => $this->invoice_type,
            'amount' => (float)$this->invoice_amount,
            'tax' => (float)$this->invoice_tax,
            'tax_rate' => $this->invoice_type === 'recibo' ? 0.00 : (float)($this->invoice_tax_rate ?: 21.00),
            'total' => $total,
            'issue_date' => $this->invoice_issue_date,
            'status' => $this->invoice_status ?: 'unpaid',
            'notes' => $this->invoice_notes,
        ]);

        $this->reset(['invoice_amount', 'invoice_tax', 'invoice_quote_id', 'invoice_notes']);
        $this->invoice_type = 'factura';
        $this->invoice_tax_rate = 21.00;
        $this->invoice_issue_date = now()->format('Y-m-d');
        $this->invoice_number = \App\Models\Invoice::nextNumber('factura');

        $this->event->load('invoices');
        session()->flash('invoice_message', 'Documento emitido y guardado exitosamente.');
    }

    public function toggleInvoiceStatus(int $invoiceId)
    {
        $invoice = $this->event->invoices()->find($invoiceId);
        if ($invoice) {
            $newStatus = ($invoice->status === 'paid') ? 'unpaid' : 'paid';
            $invoice->update(['status' => $newStatus]);
            $this->event->load('invoices');
            session()->flash('invoice_message', 'Estado del documento ' . $invoice->invoice_number . ' actualizado a: ' . ($newStatus === 'paid' ? 'COBRADO / PAGADO' : 'PENDIENTE') . '.');
        }
    }

    public function deleteInvoice(int $invoiceId)
    {
        $invoice = $this->event->invoices()->find($invoiceId);
        if ($invoice) {
            $num = $invoice->invoice_number;
            $invoice->delete();
            $this->event->load('invoices');
            session()->flash('invoice_message', "Documento {$num} eliminado correctamente.");
        }
    }

    public function saveEventNotes()
    {
        $this->event->update(['notes' => $this->event_notes]);
        $this->event->refresh();
        session()->flash('notes_message', 'Notas del evento y acuerdos actualizados correctamente.');
    }

    public function applyWhatsappTextToNotes()
    {
        if (empty(trim($this->whatsapp_chat_input))) {
            return;
        }

        $timestamp = now()->format('d/m/Y H:i');
        $newEntry = "--- Acuerdos WhatsApp ({$timestamp}) ---\n" . trim($this->whatsapp_chat_input);
        
        $this->event_notes = \App\Models\Event::prependNote($this->event_notes, $newEntry);
        $this->event->update(['notes' => $this->event_notes]);
        $this->whatsapp_chat_input = '';
        $this->event->refresh();
        
        session()->flash('notes_message', 'Conversación / Acuerdos de WhatsApp añadidos al principio de las notas del evento.');
    }

    public $whatsapp_chat_message = '';
    public $showTemplateEditor = false;
    public $editingTemplateKey = 'primer_contacto_presupuesto';
    public $editingTemplateRawText = '';

    public function toggleTemplateEditor(?string $key = null)
    {
        if ($key) {
            $this->editingTemplateKey = $key;
            $this->showTemplateEditor = true;
        } else {
            $this->showTemplateEditor = !$this->showTemplateEditor;
        }

        if ($this->showTemplateEditor) {
            $this->editingTemplateRawText = \App\Services\WhatsAppTemplateService::getRawTemplateForEdit($this->editingTemplateKey);
        }
    }

    public function updatedEditingTemplateKey($value)
    {
        $this->editingTemplateRawText = \App\Services\WhatsAppTemplateService::getRawTemplateForEdit((string)$value);
    }

    public function saveTemplateCustomization()
    {
        if (empty(trim((string)$this->editingTemplateRawText))) {
            session()->flash('whatsapp_error', 'El texto de la plantilla no puede estar vacío.');
            return;
        }

        \App\Services\WhatsAppTemplateService::saveCustomTemplate($this->editingTemplateKey, (string)$this->editingTemplateRawText);
        $this->loadWhatsAppTemplate($this->editingTemplateKey);
        $this->showTemplateEditor = false;
        session()->flash('whatsapp_success', '✅ ¡Plantilla guardada! A partir de ahora se usará este texto en todos los eventos.');
    }

    public function resetTemplateToDefault()
    {
        \App\Services\WhatsAppTemplateService::resetCustomTemplate($this->editingTemplateKey);
        $this->editingTemplateRawText = \App\Services\WhatsAppTemplateService::getRawTemplateForEdit($this->editingTemplateKey);
        $this->loadWhatsAppTemplate($this->editingTemplateKey);
        session()->flash('whatsapp_success', '🔄 Plantilla restaurada a su texto original por defecto.');
    }

    public function loadWhatsAppTemplate(string $templateKey)
    {
        // Mapear alias posibles del panel rápido
        $keyMap = [
            'seguimiento_dia_3' => 'dia3_dudas',
            'seguimiento_dia_7' => 'dia7_escasez',
            'tecnica_del_no' => 'pregunta_no',
            'cierre_expediente' => 'psicologia_inversa',
            'objecion_precio' => 'presupuesto',
        ];
        $resolvedKey = $keyMap[$templateKey] ?? $templateKey;

        if ($this->showTemplateEditor && isset(\App\Services\WhatsAppTemplateService::getEditableTemplatesCatalog()[$resolvedKey])) {
            $this->editingTemplateKey = $resolvedKey;
            $this->editingTemplateRawText = \App\Services\WhatsAppTemplateService::getRawTemplateForEdit($resolvedKey);
        }

        $sender = $this->whatsapp_sender_brand ?: null;
        $options = \App\Services\WhatsAppTemplateService::getFollowupOptions($this->event, $sender);
        if (isset($options[$resolvedKey])) {
            $this->whatsapp_chat_message = $options[$resolvedKey]['text'];
            $this->email_chat_subject = $options[$resolvedKey]['subject'] ?? \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, $resolvedKey);
        } elseif ($resolvedKey === 'primer_contacto_presupuesto') {
            $this->whatsapp_chat_message = \App\Services\WhatsAppTemplateService::primeraTomaContacto($this->event, 'presupuesto_online', $sender);
            $this->email_chat_subject = \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, 'primer_contacto_presupuesto');
        } elseif ($resolvedKey === 'primer_contacto_madrigal') {
            $this->whatsapp_chat_message = \App\Services\WhatsAppTemplateService::primeraTomaContacto($this->event, 'filtro_finca', $sender);
            $this->email_chat_subject = \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, 'primer_contacto_madrigal');
        } elseif ($resolvedKey === 'cuestionario') {
            $this->whatsapp_chat_message = \App\Services\WhatsAppTemplateService::cuestionario($this->event, $sender);
            $this->email_chat_subject = \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, 'cuestionario');
        } elseif ($resolvedKey === 'contrato') {
            $this->whatsapp_chat_message = \App\Services\WhatsAppTemplateService::contrato($this->event, $sender);
            $this->email_chat_subject = \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, 'contrato');
        } elseif ($resolvedKey === 'llamada') {
            $this->whatsapp_chat_message = \App\Services\WhatsAppTemplateService::propuestaLlamada($this->event, $sender);
            $this->email_chat_subject = \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, 'llamada');
        } elseif ($resolvedKey === 'dossier') {
            $this->whatsapp_chat_message = \App\Services\WhatsAppTemplateService::envioDossierPresupuesto($this->event, $sender);
            $this->email_chat_subject = \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, 'dossier');
        } elseif ($resolvedKey === 'fotomaton_360') {
            $this->whatsapp_chat_message = \App\Services\WhatsAppTemplateService::infoFotomatonY360($this->event);
            $this->email_chat_subject = \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, 'fotomaton_360');
        }

        if (!empty($this->whatsapp_chat_message)) {
            $this->whatsapp_chat_message = \App\Services\WhatsAppTemplateService::applySenderSignatureToMessage(
                (string)$this->whatsapp_chat_message,
                $sender,
                $this->event
            );
        }

        if (empty($this->email_chat_to)) {
            $this->email_chat_to = $this->event->client_contact_email ?? '';
        }
    }

    public function setSenderBrand(string $brand): void
    {
        $this->whatsapp_sender_brand = $brand;
        $this->syncSenderSignatureInCurrentMessage();
    }

    public function updatedWhatsappSenderBrand($value): void
    {
        $this->syncSenderSignatureInCurrentMessage();
    }

    protected function syncSenderSignatureInCurrentMessage(): void
    {
        if (!empty(trim((string)$this->whatsapp_chat_message))) {
            $this->whatsapp_chat_message = \App\Services\WhatsAppTemplateService::applySenderSignatureToMessage(
                (string)$this->whatsapp_chat_message,
                $this->whatsapp_sender_brand ?: null,
                $this->event
            );
        }
    }

    public function getSenderBrandLabel(): string
    {
        return match ($this->whatsapp_sender_brand) {
            'fran' => '🎧 Fran (JAVNX DJ)',
            'ezequiel' => '👥 Ezequiel (Núñez & Son)',
            default => '👑 Miguel (Núñez & Son)',
        };
    }

    public function sendDirectWhatsAppMessage()
    {
        if (empty(trim($this->whatsapp_chat_message))) {
            session()->flash('whatsapp_error', 'Escribe un mensaje antes de enviar.');
            return;
        }

        $phone = $this->event->client ? $this->event->client->phone : $this->event->partner_phone;
        if (empty($phone)) {
            session()->flash('whatsapp_error', 'El cliente no tiene teléfono registrado. Puedes enviárselo justo al lado con el botón «📧 Enviar por Email».');
            return;
        }

        $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;
        $this->syncSenderSignatureInCurrentMessage();
        $senderLabel = $this->getSenderBrandLabel();
        $result = \App\Services\EvolutionWhatsAppService::sendMessage(
            $phone,
            $this->whatsapp_chat_message,
            $senderBrand,
            $this->event->id,
            auth()->id()
        );

        $this->event->update([
            'no_response_at' => now(),
        ]);
        $this->whatsapp_chat_message = '';
        $this->event->load('whatsappMessages.user');

        if ($result['mode'] === 'api' && $result['success']) {
            session()->flash('whatsapp_success', "✅ Mensaje enviado directamente desde el WhatsApp de {$senderLabel}.");
        } else {
            // Modo manual o fallback: despachar evento para abrir WhatsApp Web / App
            $this->dispatch('open-whatsapp-link', ['url' => $result['url']]);
            session()->flash('whatsapp_success', '💬 Abriendo WhatsApp con el mensaje preparado...');
        }
    }

    public function sendDirectEmailMessage()
    {
        if (empty(trim((string)$this->whatsapp_chat_message))) {
            session()->flash('whatsapp_error', 'Escribe un mensaje o selecciona una plantilla arriba antes de enviar por Email.');
            return;
        }

        $toEmail = strtolower(trim((string)($this->email_chat_to ?: $this->event->client_contact_email)));
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            session()->flash('whatsapp_error', 'Introduce el email del cliente en el campo «Para:» antes de enviar.');
            return;
        }

        // Si el cliente no tenía email real guardado, actualizar su ficha automáticamente
        if ($this->event->client && (empty($this->event->client->email) || str_ends_with($this->event->client->email, '@eventosmusicales.local'))) {
            try {
                $this->event->client->update(['email' => $toEmail]);
            } catch (\Throwable $e) {}
        }

        $subject = trim((string)$this->email_chat_subject) ?: \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($this->event, 'default');
        $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;
        $this->syncSenderSignatureInCurrentMessage();

        $result = \App\Services\WhatsAppTemplateService::sendAndLogClientEmail(
            $this->event,
            $toEmail,
            $subject,
            (string)$this->whatsapp_chat_message,
            $senderBrand,
            auth()->id()
        );

        $this->whatsapp_chat_message = '';
        $this->email_chat_to = $toEmail;
        $this->event->refresh();
        $this->event_notes = $this->event->notes;
        $this->event->load('whatsappMessages.user');

        if ($result['mode'] === 'mailto_fallback' && !empty($result['mailto_url'])) {
            $this->dispatch('open-whatsapp-link', ['url' => $result['mailto_url']]);
        }

        if (!empty($result['success'])) {
            session()->flash('whatsapp_success', $result['message']);
        } else {
            session()->flash('whatsapp_error', $result['message']);
        }
    }

    public function syncWhatsAppHistoryByPhone()
    {
        $query = trim((string)$this->whatsapp_sync_phone);
        if (empty($query)) {
            $query = $this->event->client ? ($this->event->client->phone ?? '') : ($this->event->partner_phone ?? '');
            if (empty($query)) {
                $query = $this->event->client_contact_email ?? '';
            }
            $this->whatsapp_sync_phone = $query;
        }

        if (empty($query)) {
            session()->flash('whatsapp_error', 'Introduce un número de teléfono o dirección de email para buscar la conversación.');
            return;
        }

        // Si ha introducido un Email (contiene @), sincronizar historial de correo IMAP y notas de lead
        if (str_contains($query, '@')) {
            $emailResult = (new \App\Services\ImapLeadFetcherService())->syncEmailHistoryForEvent($this->event, $query);
            $this->event->refresh();
            $this->event_notes = $this->event->notes;
            $this->event->load('whatsappMessages.user');
            if (!empty($emailResult['success'])) {
                session()->flash('whatsapp_success', $emailResult['message']);
            } else {
                session()->flash('whatsapp_success', $emailResult['message']);
            }
            return;
        }

        $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;
        $result = \App\Services\EvolutionWhatsAppService::syncConversationHistory(
            $this->event,
            $query,
            $senderBrand,
            auth()->id(),
            true
        );

        $this->event->refresh();
        $this->event_notes = $this->event->notes;
        $this->event->load('whatsappMessages.user');

        if (!empty($result['success'])) {
            session()->flash('whatsapp_success', $result['message']);
        } else {
            session()->flash('whatsapp_error', $result['message']);
        }
    }

    /**
     * Sincroniza silenciosamente en segundo plano al abrir la ficha del evento
     * para traer cualquier mensaje entrante reciente (incluyendo chats @lid de campañas Meta/Instagram).
     */
    public function autoSyncWhatsAppOnLoad()
    {
        try {
            if (!\App\Services\EvolutionWhatsAppService::isEnabled()) {
                return;
            }

            $cacheKey = 'wa_autosync_event_' . $this->event->id;
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return;
            }
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addSeconds(45));

            $phone = trim((string)($this->event->client ? ($this->event->client->phone ?? '') : ($this->event->partner_phone ?? '')));
            if (empty($phone)) {
                return;
            }

            $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;
            $result = \App\Services\EvolutionWhatsAppService::syncConversationHistory(
                $this->event,
                $phone,
                $senderBrand,
                auth()->id(),
                true
            );

            if (!empty($result['imported']) && $result['imported'] > 0) {
                $this->event->refresh();
                $this->event_notes = $this->event->notes;
                $this->event->load('whatsappMessages.user');
            }
        } catch (\Throwable $e) {
            // Fallo silencioso en auto-sync
        }
    }

    public function importWhatsAppConversationText()
    {
        if (empty(trim((string)$this->whatsapp_import_text))) {
            session()->flash('whatsapp_error', 'Pega o escribe el texto de la conversación o email que quieres añadir al historial.');
            return;
        }

        $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;

        // Si el canal seleccionado es Email, guardarlo como burbuja de Email en Conversación Registrada
        if ($this->whatsapp_import_channel === 'email') {
            $emailToUse = strtolower(trim((string)($this->email_chat_to ?: $this->event->client_contact_email ?: 'email')));
            $direction = $this->whatsapp_import_direction ?: 'inbound';
            $clientName = $this->event->client ? $this->event->client->name : ($this->event->partner_name ?: 'Cliente');
            $baseDate = now();
            if (!empty($this->whatsapp_import_date)) {
                try {
                    $baseDate = \Carbon\Carbon::parse($this->whatsapp_import_date);
                } catch (\Throwable $e) {}
            }

            $rawText = trim((string)$this->whatsapp_import_text);
            $msgText = str_starts_with($rawText, '📧') ? $rawText : ("📧 [Email]\n" . $rawText);

            \App\Models\WhatsAppMessage::create([
                'event_id' => $this->event->id,
                'user_id' => $direction === 'outbound' ? auth()->id() : ($this->event->client_id ?: null),
                'phone' => $emailToUse,
                'sender_name' => $direction === 'outbound' ? 'Nosotros (Email)' : $clientName,
                'direction' => $direction,
                'message' => $msgText,
                'status' => $direction === 'outbound' ? 'sent' : 'received',
                'brand' => $senderBrand,
                'payload' => [
                    'channel' => 'email',
                    'from' => $direction === 'inbound' ? $emailToUse : null,
                    'to' => $direction === 'outbound' ? $emailToUse : null,
                ],
                'created_at' => $baseDate,
                'updated_at' => $baseDate,
            ]);

            if ($direction === 'outbound') {
                $this->event->update(['no_response_at' => now()]);
            }

            $this->whatsapp_import_text = '';
            $this->showWhatsAppImportBox = false;
            $this->event->load('whatsappMessages.user');
            session()->flash('whatsapp_success', '✅ Correo añadido a la Conversación Registrada.');
            return;
        }

        $phoneToUse = trim((string)$this->whatsapp_sync_phone) ?: ($this->event->client ? ($this->event->client->phone ?? '') : ($this->event->partner_phone ?? ''));

        $count = \App\Services\EvolutionWhatsAppService::importPastedConversation(
            $this->event,
            (string)$this->whatsapp_import_text,
            $this->whatsapp_import_direction ?: 'inbound',
            $this->whatsapp_import_date ?: null,
            $phoneToUse,
            $senderBrand,
            auth()->id()
        );

        $this->whatsapp_import_text = '';
        $this->showWhatsAppImportBox = false;
        $this->event->load('whatsappMessages.user');

        if ($count > 0) {
            session()->flash('whatsapp_success', "✅ Se han añadido {$count} mensaje(s) a la Conversación Registrada.");
        } else {
            session()->flash('whatsapp_error', 'No se añadieron mensajes nuevos (puede que ya estuvieran registrados).');
        }
    }

    public function deleteWhatsAppMessage(int $messageId)
    {
        try {
            \App\Models\WhatsAppMessage::where('event_id', $this->event->id)
                ->where('id', $messageId)
                ->delete();
            $this->event->load('whatsappMessages.user');
        } catch (\Throwable $e) {}
    }

    public function sendDirectTemplate(string $templateKey, ?string $recipientType = 'client')
    {
        $phone = $this->event->client ? $this->event->client->phone : $this->event->partner_phone;
        if ($recipientType === 'dj') {
            $phone = $this->event->dj ? $this->event->dj->phone : null;
        } elseif ($recipientType === 'assistant') {
            $phone = $this->event->assistant ? $this->event->assistant->phone : null;
        }

        if (empty($phone)) {
            session()->flash('whatsapp_error', 'No hay teléfono registrado para el destinatario.');
            return;
        }

        $sender = $this->whatsapp_sender_brand ?: null;
        $options = \App\Services\WhatsAppTemplateService::getFollowupOptions($this->event, $sender);
        $text = null;
        $title = 'Mensaje';

        if ($templateKey === 'fotomaton_360') {
            $text = \App\Services\WhatsAppTemplateService::infoFotomatonY360($this->event, $this->photobooth_provider ?: 'logrono');
            $title = 'Info Fotomatón & Plataforma 360º';
        } elseif ($templateKey === 'mesa_dulce_gofres') {
            $text = \App\Services\WhatsAppTemplateService::infoMesaDulceYGofres($this->event, $this->photobooth_provider ?: 'vitoria');
            $title = 'Info Mesas Dulces, Chocolate & Gofres';
        } elseif (isset($options[$templateKey])) {
            $text = $options[$templateKey]['text'];
            $title = $options[$templateKey]['title'] ?? $templateKey;
        } else {
            switch ($templateKey) {
                case 'disponibilidad':
                    $text = \App\Services\WhatsAppTemplateService::primeraTomaContacto($this->event, 'disponibilidad', $sender);
                    $title = 'Disponibilidad Fecha';
                    break;
                case 'filtro_finca':
                    $text = \App\Services\WhatsAppTemplateService::primeraTomaContacto($this->event, 'filtro_finca', $sender);
                    $title = 'Filtro Finca';
                    break;
                case 'estandar':
                    $text = \App\Services\WhatsAppTemplateService::primeraTomaContacto($this->event, 'estandar', $sender);
                    $title = 'Contacto Inicial';
                    break;
                case 'conversacion_suave':
                    $text = \App\Services\WhatsAppTemplateService::seguimientoConversacion($this->event, 'suave');
                    $title = 'Seguimiento Conversación';
                    break;
                case 'propuesta_llamada':
                    $text = \App\Services\WhatsAppTemplateService::propuestaLlamada($this->event, $sender);
                    $title = 'Propuesta de Llamada';
                    break;
                case 'envio_dossier':
                    $text = \App\Services\WhatsAppTemplateService::envioDossierPresupuesto($this->event, $sender);
                    $title = 'Dossier y Presupuesto';
                    break;
                case 'post_presupuesto':
                    $text = \App\Services\WhatsAppTemplateService::seguimientoPostPresupuesto($this->event, $sender);
                    $title = 'Seguimiento Presupuesto';
                    break;
                case 'quote_propuesta':
                case 'resumen_presupuesto':
                    $text = \App\Services\WhatsAppTemplateService::resumenPresupuesto($this->event);
                    $title = 'Resumen de Presupuesto';
                    break;
                case 'signal':
                case 'senal':
                    $text = \App\Services\WhatsAppTemplateService::senalReserva($this->event);
                    $title = 'Señal de Reserva';
                    break;
                case 'tecnica_no':
                case 'pregunta_no':
                    $text = \App\Services\WhatsAppTemplateService::seguimientoPreguntaNo($this->event, $sender);
                    $title = 'Técnica del NO';
                    break;
                case 'seguimiento_no':
                case 'presupuesto_no':
                case 'followup_budget':
                    $text = \App\Services\WhatsAppTemplateService::seguimientoPresupuestoNo($this->event, $sender);
                    $title = 'Objeción Presupuesto';
                    break;
                case 'psicologia_inversa':
                    $text = \App\Services\WhatsAppTemplateService::seguimientoPsicologiaInversa($this->event, $sender);
                    $title = 'Psicología Inversa';
                    break;
                case 'fin_plazo_presupuesto':
                    $text = \App\Services\WhatsAppTemplateService::finPlazoPresupuesto($this->event, $sender);
                    $title = 'Fin de Plazo de Presupuesto (+30d)';
                    break;
                case 'cuestionario':
                    $text = \App\Services\WhatsAppTemplateService::cuestionario($this->event, $sender);
                    $title = 'Cuestionario Musical';
                    break;
                case 'contrato':
                    $text = \App\Services\WhatsAppTemplateService::contrato($this->event, $sender);
                    $title = 'Firma de Contrato';
                    break;
                case 'liquidacion':
                    $text = \App\Services\WhatsAppTemplateService::liquidacion($this->event);
                    $title = 'Liquidación Final';
                    break;
                case 'fotomaton_360':
                    $text = \App\Services\WhatsAppTemplateService::infoFotomatonY360($this->event, $this->photobooth_provider ?: 'logrono');
                    $title = 'Info Fotomatón & Plataforma 360º';
                    break;
                case 'mesa_dulce_gofres':
                    $text = \App\Services\WhatsAppTemplateService::infoMesaDulceYGofres($this->event, $this->photobooth_provider ?: 'vitoria');
                    $title = 'Info Mesas Dulces, Chocolate & Gofres';
                    break;
                case 'hoja_ruta':
                    $targetUser = $recipientType === 'assistant' ? $this->event->assistant : $this->event->dj;
                    $text = \App\Services\WhatsAppTemplateService::hojaRutaDj($this->event, $targetUser);
                    $title = $recipientType === 'assistant' ? 'Hoja de Ruta Asistente' : 'Hoja de Ruta DJ';
                    break;
                default:
                    $text = \App\Services\WhatsAppTemplateService::seguimientoPreguntaNo($this->event, $sender);
                    $title = 'Seguimiento';
                    break;
            }
        }

        if (!empty($text) && $recipientType === 'client') {
            $text = \App\Services\WhatsAppTemplateService::applySenderSignatureToMessage((string)$text, $sender, $this->event);
        }

        if (empty($text)) {
            session()->flash('whatsapp_error', 'No se pudo generar el texto de la plantilla.');
            return;
        }

        $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;
        $senderLabel = $this->getSenderBrandLabel();
        $result = \App\Services\EvolutionWhatsAppService::sendMessage(
            $phone,
            $text,
            $senderBrand,
            $this->event->id,
            auth()->id()
        );

        $newEntry = "[" . now()->format('d/m/Y H:i') . "] 💬 WhatsApp ({$title}) enviado desde {$senderLabel} por " . auth()->user()->name . ". Contador reseteado a 0.";
        $this->event->update([
            'no_response_at' => now(),
            'notes' => \App\Models\Event::prependNote($this->event->notes, $newEntry),
        ]);
        $this->event->refresh();
        $this->event_notes = $this->event->notes;
        $this->event->load('whatsappMessages');

        if ($result['mode'] === 'api' && $result['success']) {
            session()->flash('message', "✅ ¡WhatsApp ({$title}) enviado directamente desde el WhatsApp de {$senderLabel}!");
        } elseif ($result['mode'] === 'manual' && !empty($result['url'])) {
            $this->dispatch('open-whatsapp-link', ['url' => $result['url']]);
            session()->flash('message', "💬 Abriendo WhatsApp con {$title}...");
        } elseif ($result['mode'] === 'fallback_link') {
            session()->flash('whatsapp_error', 'El servicio de WhatsApp (' . $senderLabel . ') no está conectado. Revisa Ajustes o usa el enlace manual.');
        } else {
            session()->flash('whatsapp_error', 'Error al enviar por WhatsApp: ' . ($result['error'] ?? 'desconocido'));
        }
    }

    public function sendDirectQuoteWhatsApp(int $quoteId)
    {
        $phone = $this->event->client ? $this->event->client->phone : $this->event->partner_phone;
        if (empty($phone)) {
            session()->flash('whatsapp_error', 'No hay teléfono registrado para el cliente.');
            return;
        }

        $quote = $this->event->quotes()->find($quoteId);
        if (!$quote) {
            session()->flash('whatsapp_error', 'Presupuesto no encontrado.');
            return;
        }

        $text = \App\Services\WhatsAppTemplateService::resumenPresupuesto($this->event, $quote);
        $title = 'Presupuesto #' . $quote->id;

        $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;
        $senderLabel = $this->getSenderBrandLabel();
        $result = \App\Services\EvolutionWhatsAppService::sendMessage(
            $phone,
            $text,
            $senderBrand,
            $this->event->id,
            auth()->id()
        );

        $newEntry = "[" . now()->format('d/m/Y H:i') . "] 💬 WhatsApp ({$title}) enviado desde {$senderLabel} por " . auth()->user()->name . ". Contador reseteado a 0.";
        $this->event->update([
            'no_response_at' => now(),
            'notes' => \App\Models\Event::prependNote($this->event->notes, $newEntry),
        ]);
        $this->event->refresh();
        $this->event_notes = $this->event->notes;
        $this->event->load('whatsappMessages');

        if ($result['mode'] === 'api' && $result['success']) {
            $msg = "✅ ¡Presupuesto #{$quote->id} enviado al cliente desde el WhatsApp de {$senderLabel}!";
            session()->flash('message', $msg);
            session()->flash('quote_message', $msg);
        } elseif ($result['mode'] === 'manual' && !empty($result['url'])) {
            $this->dispatch('open-whatsapp-link', ['url' => $result['url']]);
            session()->flash('quote_message', "💬 Abriendo WhatsApp con la Propuesta #{$quote->id}...");
        } elseif ($result['mode'] === 'fallback_link') {
            session()->flash('whatsapp_error', 'El servicio de WhatsApp (' . $senderLabel . ') no está conectado. Usa el enlace manual.');
        } else {
            session()->flash('whatsapp_error', 'Error al enviar por WhatsApp: ' . ($result['error'] ?? 'desconocido'));
        }
    }

    public function sendDirectContractWhatsApp(int $contractId)
    {
        $phone = $this->event->client ? $this->event->client->phone : $this->event->partner_phone;
        if (empty($phone)) {
            session()->flash('whatsapp_error', 'No hay teléfono registrado para el cliente.');
            return;
        }

        $contract = $this->event->contracts()->find($contractId);
        if (!$contract) {
            session()->flash('whatsapp_error', 'Contrato no encontrado.');
            return;
        }

        $text = \App\Services\WhatsAppTemplateService::contrato($contract);
        $title = 'Firma de Contrato';

        $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;
        $senderLabel = $this->getSenderBrandLabel();
        $result = \App\Services\EvolutionWhatsAppService::sendMessage(
            $phone,
            $text,
            $senderBrand,
            $this->event->id,
            auth()->id()
        );

        $newEntry = "[" . now()->format('d/m/Y H:i') . "] 💬 WhatsApp ({$title}) enviado desde {$senderLabel} por " . auth()->user()->name . ". Contador reseteado a 0.";
        $this->event->update([
            'no_response_at' => now(),
            'notes' => \App\Models\Event::prependNote($this->event->notes, $newEntry),
        ]);
        $this->event->refresh();
        $this->event_notes = $this->event->notes;
        $this->event->load('whatsappMessages');

        if ($result['mode'] === 'api' && $result['success']) {
            $msg = "✅ ¡Enlace de firma de contrato ({$contract->contract_number}) enviado desde el WhatsApp de {$senderLabel}!";
            session()->flash('message', $msg);
            session()->flash('contract_message', $msg);
        } elseif ($result['mode'] === 'manual' && !empty($result['url'])) {
            $this->dispatch('open-whatsapp-link', ['url' => $result['url']]);
            session()->flash('contract_message', "💬 Abriendo WhatsApp con el contrato {$contract->contract_number}...");
        } elseif ($result['mode'] === 'fallback_link') {
            session()->flash('whatsapp_error', 'El servicio de WhatsApp (' . $senderLabel . ') no está conectado. Usa el enlace manual.');
        } else {
            session()->flash('whatsapp_error', 'Error al enviar por WhatsApp: ' . ($result['error'] ?? 'desconocido'));
        }
    }

    public function sendDirectInvoiceWhatsApp(int $invoiceId)
    {
        $phone = $this->event->client ? $this->event->client->phone : $this->event->partner_phone;
        if (empty($phone)) {
            session()->flash('whatsapp_error', 'No hay teléfono registrado para el cliente.');
            return;
        }

        $invoice = $this->event->invoices()->find($invoiceId);
        if (!$invoice) {
            session()->flash('whatsapp_error', 'Documento no encontrado.');
            return;
        }

        $text = \App\Services\WhatsAppTemplateService::facturaRecibo($this->event, $invoice);
        $docName = ($invoice->type === 'recibo' ? 'Recibo ' : 'Factura ') . $invoice->invoice_number;

        $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;
        $senderLabel = $this->getSenderBrandLabel();
        $result = \App\Services\EvolutionWhatsAppService::sendMessage(
            $phone,
            $text,
            $senderBrand,
            $this->event->id,
            auth()->id()
        );

        $newEntry = "[" . now()->format('d/m/Y H:i') . "] 💬 WhatsApp ({$docName}) enviado desde {$senderLabel} por " . auth()->user()->name . ".";
        $this->event->update([
            'no_response_at' => now(),
            'notes' => \App\Models\Event::prependNote($this->event->notes, $newEntry),
        ]);
        $this->event->refresh();
        $this->event_notes = $this->event->notes;
        $this->event->load('whatsappMessages');

        if ($result['mode'] === 'api' && $result['success']) {
            $msg = "✅ ¡{$docName} enviado al cliente desde el WhatsApp de {$senderLabel}!";
            session()->flash('message', $msg);
            session()->flash('invoice_message', $msg);
        } elseif ($result['mode'] === 'manual' && !empty($result['url'])) {
            $this->dispatch('open-whatsapp-link', ['url' => $result['url']]);
            session()->flash('invoice_message', "💬 Abriendo WhatsApp con {$docName}...");
        } elseif ($result['mode'] === 'fallback_link') {
            session()->flash('whatsapp_error', 'El servicio de WhatsApp (' . $senderLabel . ') no está conectado. Usa el enlace manual.');
        } else {
            session()->flash('whatsapp_error', 'Error al enviar por WhatsApp: ' . ($result['error'] ?? 'desconocido'));
        }
    }

    public function sendDirectMeetingWhatsApp(int $meetingId, string $type = 'confirm')
    {
        $phone = $this->event->client ? $this->event->client->phone : $this->event->partner_phone;
        if (empty($phone)) {
            session()->flash('whatsapp_error', 'No hay teléfono registrado para el cliente.');
            return;
        }

        $meeting = $this->event->meetings()->find($meetingId);
        if (!$meeting) {
            session()->flash('whatsapp_error', 'Cita no encontrada.');
            return;
        }

        $text = $type === 'reminder'
            ? \App\Services\WhatsAppTemplateService::meetingReminder($meeting)
            : \App\Services\WhatsAppTemplateService::meetingConfirmation($meeting);
        $title = ($type === 'reminder' ? 'Recordatorio Cita: ' : 'Confirmación Cita: ') . $meeting->title;

        $senderBrand = $this->whatsapp_sender_brand ?: $this->event->brand_clean;
        $senderLabel = $this->getSenderBrandLabel();
        $result = \App\Services\EvolutionWhatsAppService::sendMessage(
            $phone,
            $text,
            $senderBrand,
            $this->event->id,
            auth()->id()
        );

        $newEntry = "[" . now()->format('d/m/Y H:i') . "] 💬 WhatsApp ({$title}) enviado desde {$senderLabel} por " . auth()->user()->name . ".";
        $this->event->update([
            'no_response_at' => now(),
            'notes' => \App\Models\Event::prependNote($this->event->notes, $newEntry),
        ]);
        $this->event->refresh();
        $this->event_notes = $this->event->notes;
        $this->event->load('whatsappMessages');

        if ($result['mode'] === 'api' && $result['success']) {
            $msg = "✅ ¡{$title} enviado al cliente desde el WhatsApp de {$senderLabel}!";
            session()->flash('message', $msg);
            session()->flash('meeting_message', $msg);
        } elseif ($result['mode'] === 'manual' && !empty($result['url'])) {
            $this->dispatch('open-whatsapp-link', ['url' => $result['url']]);
            session()->flash('meeting_message', "💬 Abriendo WhatsApp con {$title}...");
        } elseif ($result['mode'] === 'fallback_link') {
            session()->flash('whatsapp_error', 'El servicio de WhatsApp (' . $senderLabel . ') no está conectado. Usa el enlace manual.');
        } else {
            session()->flash('whatsapp_error', 'Error al enviar por WhatsApp: ' . ($result['error'] ?? 'desconocido'));
        }
    }

    public function getWhatsappMessagesProperty()
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('whatsapp_messages')) {
                return collect();
            }
            return $this->event->relationLoaded('whatsappMessages')
                ? $this->event->whatsappMessages
                : $this->event->whatsappMessages()->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public function updatedQuoteServices($value, $key)
    {
        if (str_starts_with($key, 'pack_duo_360.selected')) {
            if ($value) {
                $this->quote_services['photobooth']['selected'] = false;
                $this->quote_services['video_360']['selected'] = false;
            }
            return;
        }

        if ($key === 'photobooth.selected' || $key === 'video_360.selected') {
            if ($value) {
                $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider ?: 'logrono');
                if (
                    !empty($this->quote_services['photobooth']['selected']) &&
                    !empty($this->quote_services['video_360']['selected']) &&
                    !empty($pbConfig['pack_duo_enabled'])
                ) {
                    $pbH = max(2, (int)($this->quote_services['photobooth']['hours'] ?? 2));
                    $v360H = max(2, (int)($this->quote_services['video_360']['hours'] ?? 2));
                    $this->setPackDuoHours(max($pbH, $v360H));
                    return;
                }
                if (!empty($this->quote_services['pack_duo_360']['selected'])) {
                    $this->quote_services['pack_duo_360']['selected'] = false;
                }
            }
            return;
        }

        if ($key === 'photobooth.hours') {
            $h = max(1, (int)$value);
            $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider ?: 'logrono');
            $extraRate = (float)($pbConfig['photobooth_extra_hour'] ?? 50);
            if ($h <= 2) {
                $this->quote_services['photobooth']['price'] = $pbConfig['photobooth_2h'];
            } elseif ($h === 3) {
                $this->quote_services['photobooth']['price'] = $pbConfig['photobooth_3h'];
            } else {
                $this->quote_services['photobooth']['price'] = $pbConfig['photobooth_3h'] + (($h - 3) * $extraRate);
            }
            return;
        }

        if ($key === 'photobooth.price') {
            $prov = $this->photobooth_provider ?: 'logrono';
            $pbConfig = \App\Models\Setting::getPhotobooth360Config($prov);
            $h = max(1, (int)($this->quote_services['photobooth']['hours'] ?? 2));
            $price = (float)$value;
            if ($h <= 2) {
                $this->quote_services['photobooth']['price_2h'] = $price;
                $pbConfig['photobooth_2h'] = $price;
            } elseif ($h === 3) {
                $this->quote_services['photobooth']['price_3h'] = $price;
                $pbConfig['photobooth_3h'] = $price;
            }
            \App\Models\Setting::saveSinglePhotoboothProvider($prov, $pbConfig);
            return;
        }

        if ($key === 'video_360.hours') {
            $h = max(1, (int)$value);
            $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider ?: 'logrono');
            $extraRate = (float)($pbConfig['video_360_extra_hour'] ?? 60);
            if ($h <= 2) {
                $this->quote_services['video_360']['price'] = $pbConfig['video_360_2h'];
            } elseif ($h === 3) {
                $this->quote_services['video_360']['price'] = $pbConfig['video_360_3h'];
            } else {
                $this->quote_services['video_360']['price'] = $pbConfig['video_360_3h'] + (($h - 3) * $extraRate);
            }
            return;
        }

        if ($key === 'video_360.price') {
            $prov = $this->photobooth_provider ?: 'logrono';
            $pbConfig = \App\Models\Setting::getPhotobooth360Config($prov);
            $h = max(1, (int)($this->quote_services['video_360']['hours'] ?? 2));
            $price = (float)$value;
            if ($h <= 2) {
                $this->quote_services['video_360']['price_2h'] = $price;
                $pbConfig['video_360_2h'] = $price;
            } elseif ($h === 3) {
                $this->quote_services['video_360']['price_3h'] = $price;
                $pbConfig['video_360_3h'] = $price;
            }
            \App\Models\Setting::saveSinglePhotoboothProvider($prov, $pbConfig);
            return;
        }

        if ($key === 'pack_duo_360.hours') {
            $h = max(1, (int)$value);
            $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider ?: 'logrono');
            $extraRate = (float)($pbConfig['photobooth_extra_hour'] ?? 50) + (float)($pbConfig['video_360_extra_hour'] ?? 60);
            if ($h <= 2) {
                $this->quote_services['pack_duo_360']['price'] = $pbConfig['pack_duo_2h'];
            } elseif ($h === 3) {
                $this->quote_services['pack_duo_360']['price'] = $pbConfig['pack_duo_3h'];
            } else {
                $this->quote_services['pack_duo_360']['price'] = $pbConfig['pack_duo_3h'] + (($h - 3) * $extraRate);
            }
            return;
        }

        if ($key === 'pack_duo_360.price') {
            $prov = $this->photobooth_provider ?: 'logrono';
            $pbConfig = \App\Models\Setting::getPhotobooth360Config($prov);
            $h = max(1, (int)($this->quote_services['pack_duo_360']['hours'] ?? 3));
            $price = (float)$value;
            if ($h <= 2) {
                $this->quote_services['pack_duo_360']['price_2h'] = $price;
                $pbConfig['pack_duo_2h'] = $price;
            } elseif ($h === 3) {
                $this->quote_services['pack_duo_360']['price_3h'] = $price;
                $pbConfig['pack_duo_3h'] = $price;
            }
            \App\Models\Setting::saveSinglePhotoboothProvider($prov, $pbConfig);
            return;
        }

        if ($key === 'chocolate_fountain.price') {
            $prov = $this->photobooth_provider ?: 'vitoria';
            $pbConfig = \App\Models\Setting::getPhotobooth360Config($prov);
            $chocF = (int)($this->quote_services['chocolate_fountain']['fountains'] ?? 1) >= 2 ? 2 : 1;
            $chocH = (int)($this->quote_services['chocolate_fountain']['hours'] ?? 2) >= 3 ? 3 : 2;
            $price = (float)$value;
            $this->quote_services['chocolate_fountain']["price_{$chocF}_{$chocH}h"] = $price;
            $pbConfig["chocolate_fountain_{$chocF}_{$chocH}h"] = $price;
            \App\Models\Setting::saveSinglePhotoboothProvider($prov, $pbConfig);
            return;
        }

        if ($key === 'candy_bar.price') {
            $prov = $this->photobooth_provider ?: 'vitoria';
            $pbConfig = \App\Models\Setting::getPhotobooth360Config($prov);
            $pbConfig['candy_bar_price'] = (float)$value;
            \App\Models\Setting::saveSinglePhotoboothProvider($prov, $pbConfig);
            return;
        }

        if ($key === 'waffle_cart.hours') {
            $h = max(2, (int)$value);
            $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider ?: 'vitoria');
            $this->quote_services['waffle_cart']['price'] = ($h <= 2) ? ($pbConfig['waffle_cart_2h'] ?? 420) : ($pbConfig['waffle_cart_3h'] ?? 550);
            return;
        }

        if ($key === 'waffle_cart.price') {
            $prov = $this->photobooth_provider ?: 'vitoria';
            $pbConfig = \App\Models\Setting::getPhotobooth360Config($prov);
            $waffleH = (int)($this->quote_services['waffle_cart']['hours'] ?? 2) >= 3 ? 3 : 2;
            $price = (float)$value;
            if ($waffleH === 3) {
                $this->quote_services['waffle_cart']['price_3h'] = $price;
                $pbConfig['waffle_cart_3h'] = $price;
            } else {
                $this->quote_services['waffle_cart']['price_2h'] = $price;
                $pbConfig['waffle_cart_2h'] = $price;
            }
            \App\Models\Setting::saveSinglePhotoboothProvider($prov, $pbConfig);
            return;
        }

        if (str_starts_with($key, 'pack_')) {
            $parts = explode('.', $key);
            $packName = $parts[0] ?? '';
            if ($value) {
                if ($packName === 'pack_basic') {
                    $this->quote_services['pack_medium']['selected'] = false;
                    $this->quote_services['pack_premium']['selected'] = false;
                    $this->selected_pack_type = 'basic';
                } elseif ($packName === 'pack_medium') {
                    $this->quote_services['pack_basic']['selected'] = false;
                    $this->quote_services['pack_premium']['selected'] = false;
                    $this->selected_pack_type = 'medium';
                } elseif ($packName === 'pack_premium') {
                    $this->quote_services['pack_basic']['selected'] = false;
                    $this->quote_services['pack_medium']['selected'] = false;
                    $this->selected_pack_type = 'premium';
                }
            } else {
                $this->selected_pack_type = 'custom';
            }
            $this->syncPackEffectsAdmin();
        }
    }

    public function setPhotoboothProvider(string $provider)
    {
        $providers = \App\Models\Setting::getPhotoboothProviders();
        $this->photobooth_provider = isset($providers[$provider]) ? $provider : (array_key_first($providers) ?: 'logrono');
        $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider);

        // Si algún servicio no lo ofrece este proveedor, desmarcarlo para que no sume al cambiar de proveedor
        if (empty($pbConfig['photobooth_enabled'])) {
            $this->quote_services['photobooth']['selected'] = false;
        }
        if (empty($pbConfig['video_360_enabled'])) {
            $this->quote_services['video_360']['selected'] = false;
        }
        if (empty($pbConfig['pack_duo_enabled'])) {
            $this->quote_services['pack_duo_360']['selected'] = false;
        }
        if (empty($pbConfig['chocolate_fountain_enabled'])) {
            $this->quote_services['chocolate_fountain']['selected'] = false;
        }
        if (empty($pbConfig['candy_bar_enabled'])) {
            $this->quote_services['candy_bar']['selected'] = false;
        }
        if (empty($pbConfig['waffle_cart_enabled'])) {
            $this->quote_services['waffle_cart']['selected'] = false;
        }

        $pbHours = max(1, (int)($this->quote_services['photobooth']['hours'] ?? 2));
        if ($this->photobooth_provider === 'logrono' && $pbHours < 3) {
            $pbHours = 3;
            $this->quote_services['photobooth']['hours'] = 3;
        }
        $pbExtra = (float)($pbConfig['photobooth_extra_hour'] ?? 50);
        $this->quote_services['photobooth']['price_2h'] = $pbConfig['photobooth_2h'];
        $this->quote_services['photobooth']['price_3h'] = $pbConfig['photobooth_3h'];
        $this->quote_services['photobooth']['extra_hour'] = $pbExtra;
        $this->quote_services['photobooth']['price'] = ($pbHours <= 2) ? $pbConfig['photobooth_2h'] : ($pbConfig['photobooth_3h'] + max(0, $pbHours - 3) * $pbExtra);
        $this->quote_services['photobooth']['desc'] = $pbConfig['photobooth_desc'];

        $v360Hours = max(1, (int)($this->quote_services['video_360']['hours'] ?? 2));
        $v360Extra = (float)($pbConfig['video_360_extra_hour'] ?? 60);
        $this->quote_services['video_360']['price_2h'] = $pbConfig['video_360_2h'];
        $this->quote_services['video_360']['price_3h'] = $pbConfig['video_360_3h'];
        $this->quote_services['video_360']['extra_hour'] = $v360Extra;
        $this->quote_services['video_360']['price'] = ($v360Hours <= 2) ? $pbConfig['video_360_2h'] : ($pbConfig['video_360_3h'] + max(0, $v360Hours - 3) * $v360Extra);
        $this->quote_services['video_360']['desc'] = $pbConfig['video_360_desc'];

        $duoHours = max(1, (int)($this->quote_services['pack_duo_360']['hours'] ?? 3));
        $duoExtra = $pbExtra + $v360Extra;
        $this->quote_services['pack_duo_360']['price_2h'] = $pbConfig['pack_duo_2h'];
        $this->quote_services['pack_duo_360']['price_3h'] = $pbConfig['pack_duo_3h'];
        $this->quote_services['pack_duo_360']['price'] = ($duoHours <= 2) ? $pbConfig['pack_duo_2h'] : ($pbConfig['pack_duo_3h'] + max(0, $duoHours - 3) * $duoExtra);
        $this->quote_services['pack_duo_360']['desc'] = $pbConfig['pack_duo_desc'];

        $chocFountains = max(1, (int)($this->quote_services['chocolate_fountain']['fountains'] ?? 1));
        $chocHours = max(2, (int)($this->quote_services['chocolate_fountain']['hours'] ?? 2));
        $this->quote_services['chocolate_fountain']['price_1_2h'] = $pbConfig['chocolate_fountain_1_2h'] ?? 420;
        $this->quote_services['chocolate_fountain']['price_1_3h'] = $pbConfig['chocolate_fountain_1_3h'] ?? 520;
        $this->quote_services['chocolate_fountain']['price_2_2h'] = $pbConfig['chocolate_fountain_2_2h'] ?? 490;
        $this->quote_services['chocolate_fountain']['price_2_3h'] = $pbConfig['chocolate_fountain_2_3h'] ?? 590;
        $chocKey = 'chocolate_fountain_' . ($chocFountains >= 2 ? '2' : '1') . '_' . ($chocHours >= 3 ? '3h' : '2h');
        $this->quote_services['chocolate_fountain']['price'] = $pbConfig[$chocKey] ?? 420;
        $this->quote_services['chocolate_fountain']['desc'] = $pbConfig['chocolate_fountain_desc'];

        $this->quote_services['candy_bar']['price'] = $pbConfig['candy_bar_price'] ?? 420;
        $this->quote_services['candy_bar']['desc'] = $pbConfig['candy_bar_desc'];

        $waffleHours = max(2, (int)($this->quote_services['waffle_cart']['hours'] ?? 2));
        $this->quote_services['waffle_cart']['price_2h'] = $pbConfig['waffle_cart_2h'] ?? 420;
        $this->quote_services['waffle_cart']['price_3h'] = $pbConfig['waffle_cart_3h'] ?? 550;
        $this->quote_services['waffle_cart']['price'] = ($waffleHours >= 3) ? ($pbConfig['waffle_cart_3h'] ?? 550) : ($pbConfig['waffle_cart_2h'] ?? 420);
        $this->quote_services['waffle_cart']['desc'] = $pbConfig['waffle_cart_desc'];
    }

    public function toggleProviderServiceFromQuote(string $serviceFlag): void
    {
        $providers = \App\Models\Setting::getPhotoboothProviders();
        $provKey = isset($providers[$this->photobooth_provider]) ? $this->photobooth_provider : (array_key_first($providers) ?: 'logrono');
        if (!isset($providers[$provKey])) {
            return;
        }
        $allowedFlags = [
            'photobooth_enabled' => 'photobooth',
            'video_360_enabled' => 'video_360',
            'pack_duo_enabled' => 'pack_duo_360',
            'chocolate_fountain_enabled' => 'chocolate_fountain',
            'candy_bar_enabled' => 'candy_bar',
            'waffle_cart_enabled' => 'waffle_cart',
        ];
        if (!isset($allowedFlags[$serviceFlag])) {
            return;
        }
        $newState = empty($providers[$provKey][$serviceFlag]);
        $providers[$provKey][$serviceFlag] = $newState;
        \App\Models\Setting::saveSinglePhotoboothProvider($provKey, $providers[$provKey]);

        if (!$newState) {
            $serviceKey = $allowedFlags[$serviceFlag];
            $this->quote_services[$serviceKey]['selected'] = false;
        }
    }

    public function setPhotoboothHours(int $hours)
    {
        $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider ?: 'logrono');
        $extraRate = (float)($pbConfig['photobooth_extra_hour'] ?? 50);
        $this->quote_services['photobooth']['selected'] = true;
        $this->quote_services['photobooth']['hours'] = $hours;
        $this->quote_services['photobooth']['price'] = ($hours <= 2) ? $pbConfig['photobooth_2h'] : ($pbConfig['photobooth_3h'] + max(0, $hours - 3) * $extraRate);
        if (!empty($this->quote_services['video_360']['selected']) && !empty($pbConfig['pack_duo_enabled'])) {
            $v360H = max(2, (int)($this->quote_services['video_360']['hours'] ?? 2));
            $this->setPackDuoHours(max($hours, $v360H));
            return;
        }
        $this->quote_services['pack_duo_360']['selected'] = false;
    }

    public function setVideo360Hours(int $hours)
    {
        $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider ?: 'logrono');
        $extraRate = (float)($pbConfig['video_360_extra_hour'] ?? 60);
        $this->quote_services['video_360']['selected'] = true;
        $this->quote_services['video_360']['hours'] = $hours;
        $this->quote_services['video_360']['price'] = ($hours <= 2) ? $pbConfig['video_360_2h'] : ($pbConfig['video_360_3h'] + max(0, $hours - 3) * $extraRate);
        if (!empty($this->quote_services['photobooth']['selected']) && !empty($pbConfig['pack_duo_enabled'])) {
            $pbH = max(2, (int)($this->quote_services['photobooth']['hours'] ?? 2));
            $this->setPackDuoHours(max($pbH, $hours));
            return;
        }
        $this->quote_services['pack_duo_360']['selected'] = false;
    }

    public function setPackDuoHours(int $hours)
    {
        $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider ?: 'logrono');
        $extraRate = (float)($pbConfig['photobooth_extra_hour'] ?? 50) + (float)($pbConfig['video_360_extra_hour'] ?? 60);
        $this->quote_services['pack_duo_360']['selected'] = true;
        $this->quote_services['pack_duo_360']['hours'] = $hours;
        $this->quote_services['pack_duo_360']['price'] = ($hours <= 2) ? $pbConfig['pack_duo_2h'] : ($pbConfig['pack_duo_3h'] + max(0, $hours - 3) * $extraRate);
        $this->quote_services['photobooth']['selected'] = false;
        $this->quote_services['video_360']['selected'] = false;
    }

    public function setChocolateFountainOption(int $fountains, int $hours)
    {
        $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider ?: 'vitoria');
        $f = ($fountains >= 2) ? 2 : 1;
        $h = ($hours >= 3) ? 3 : 2;
        $this->quote_services['chocolate_fountain']['selected'] = true;
        $this->quote_services['chocolate_fountain']['consult'] = false;
        $this->quote_services['chocolate_fountain']['fountains'] = $f;
        $this->quote_services['chocolate_fountain']['hours'] = $h;
        $priceKey = "chocolate_fountain_{$f}_{$h}h";
        $defaultPrice = ($f === 2) ? ($h === 3 ? 590 : 490) : ($h === 3 ? 520 : 420);
        $this->quote_services['chocolate_fountain']['price'] = (float)($pbConfig[$priceKey] ?? $defaultPrice);
    }

    public function setWaffleCartHours(int $hours)
    {
        $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider ?: 'vitoria');
        $h = ($hours >= 3) ? 3 : 2;
        $this->quote_services['waffle_cart']['selected'] = true;
        $this->quote_services['waffle_cart']['consult'] = false;
        $this->quote_services['waffle_cart']['hours'] = $h;
        $this->quote_services['waffle_cart']['price'] = ($h === 3) ? (float)($pbConfig['waffle_cart_3h'] ?? 550) : (float)($pbConfig['waffle_cart_2h'] ?? 420);
    }

    public function saveCurrentPricesAsDefault()
    {
        $evType = $this->event ? $this->event->event_type_clean : 'boda';
        $evLabel = $this->event ? $this->event->event_type_label : 'Boda';

        // Packs Comerciales (guardados específicamente para este tipo de evento)
        $pbPriceVal = (float)($this->quote_services['pack_basic']['price'] ?? 600);
        $pmPriceVal = (float)($this->quote_services['pack_medium']['price'] ?? 950);
        $ppPriceVal = (float)($this->quote_services['pack_premium']['price'] ?? 1350);

        \App\Models\Setting::set("pack_basic_price_{$evType}", $pbPriceVal);
        \App\Models\Setting::set("pack_medium_price_{$evType}", $pmPriceVal);
        \App\Models\Setting::set("pack_premium_price_{$evType}", $ppPriceVal);

        // Servicios Musicales (precios y textos por tipo de evento)
        $cerPriceVal = (float)($this->quote_services['ceremony']['price'] ?? 180);
        $cockPriceVal = (float)($this->quote_services['cocktail']['price'] ?? 150);
        $restPriceVal = (float)($this->quote_services['restaurant']['price'] ?? 150);
        $djPriceVal = (float)($this->quote_services['dj_custom']['price'] ?? 150);
        $ehPriceVal = (float)($this->quote_services['extra_hours']['price'] ?? 120);
        $karPriceVal = (float)($this->quote_services['karaoke']['price'] ?? 80);

        \App\Models\Setting::set("price_ceremony_{$evType}", $cerPriceVal);
        \App\Models\Setting::set("price_cocktail_{$evType}", $cockPriceVal);
        \App\Models\Setting::set("price_restaurant_{$evType}", $restPriceVal);
        \App\Models\Setting::set("price_dj_{$evType}", $djPriceVal);
        \App\Models\Setting::set("price_extra_hours_{$evType}", $ehPriceVal);
        \App\Models\Setting::set("price_karaoke_{$evType}", $karPriceVal);

        if (!empty($this->quote_services['ceremony']['name'])) {
            \App\Models\Setting::set("service_ceremony_name_{$evType}", $this->quote_services['ceremony']['name']);
        }
        if (!empty($this->quote_services['ceremony']['desc'])) {
            \App\Models\Setting::set("service_ceremony_desc_{$evType}", $this->quote_services['ceremony']['desc']);
        }
        if (!empty($this->quote_services['cocktail']['name'])) {
            \App\Models\Setting::set("service_cocktail_name_{$evType}", $this->quote_services['cocktail']['name']);
        }
        if (!empty($this->quote_services['cocktail']['desc'])) {
            \App\Models\Setting::set("service_cocktail_desc_{$evType}", $this->quote_services['cocktail']['desc']);
        }
        if (!empty($this->quote_services['restaurant']['name'])) {
            \App\Models\Setting::set("service_restaurant_name_{$evType}", $this->quote_services['restaurant']['name']);
        }
        if (!empty($this->quote_services['restaurant']['desc'])) {
            \App\Models\Setting::set("service_restaurant_desc_{$evType}", $this->quote_services['restaurant']['desc']);
        }
        if (!empty($this->quote_services['dj_custom']['name'])) {
            \App\Models\Setting::set("service_dj_name_{$evType}", $this->quote_services['dj_custom']['name']);
            if ($this->event && $this->event->brand_clean === 'mago_leugim') {
                \App\Models\Setting::set("service_dj_name_mago_{$evType}", $this->quote_services['dj_custom']['name']);
            }
        }
        if (!empty($this->quote_services['dj_custom']['desc'])) {
            \App\Models\Setting::set("service_dj_desc_{$evType}", $this->quote_services['dj_custom']['desc']);
            if ($this->event && $this->event->brand_clean === 'mago_leugim') {
                \App\Models\Setting::set("service_dj_desc_mago_{$evType}", $this->quote_services['dj_custom']['desc']);
            }
        }

        // Espectáculos de Magia (Mago Leugim: Infantil, Adultos, Mixto y Close-Up)
        foreach (['magic_kids', 'magic_adults', 'magic_mixed', 'magic_closeup'] as $mKey) {
            $mPrice = (float)($this->quote_services[$mKey]['price'] ?? 250);
            \App\Models\Setting::set("price_{$mKey}_{$evType}", $mPrice);
            \App\Models\Setting::set("price_{$mKey}", $mPrice);
            if (!empty($this->quote_services[$mKey]['name'])) {
                \App\Models\Setting::set("service_{$mKey}_name_{$evType}", $this->quote_services[$mKey]['name']);
                \App\Models\Setting::set("service_{$mKey}_name", $this->quote_services[$mKey]['name']);
            }
            if (!empty($this->quote_services[$mKey]['desc'])) {
                \App\Models\Setting::set("service_{$mKey}_desc_{$evType}", $this->quote_services[$mKey]['desc']);
                \App\Models\Setting::set("service_{$mKey}_desc", $this->quote_services[$mKey]['desc']);
            }
        }

        // Guardar también condiciones de señal por tipo de evento
        \App\Models\Setting::set("deposit_type_{$evType}", $this->quote_deposit_type);
        \App\Models\Setting::set("deposit_percentage_{$evType}", (float)$this->quote_deposit_percentage);
        \App\Models\Setting::set("deposit_fixed_amount_{$evType}", (float)$this->quote_deposit_fixed_amount);

        // Si es boda, sincronizar también las claves base generales
        if ($evType === 'boda') {
            \App\Models\Setting::set('pack_basic_price', $pbPriceVal);
            \App\Models\Setting::set('pack_medium_price', $pmPriceVal);
            \App\Models\Setting::set('pack_premium_price', $ppPriceVal);
            \App\Models\Setting::set('price_ceremony', $cerPriceVal);
            \App\Models\Setting::set('price_cocktail', $cockPriceVal);
            \App\Models\Setting::set('price_restaurant', $restPriceVal);
            \App\Models\Setting::set('price_dj', $djPriceVal);
            \App\Models\Setting::set('price_extra_hours', $ehPriceVal);
            \App\Models\Setting::set('price_karaoke', $karPriceVal);
        }

        // Proveedor activo por defecto y tarifas SOLO para el proveedor seleccionado
        $providers = \App\Models\Setting::getPhotoboothProviders();
        $prov = isset($providers[$this->photobooth_provider]) ? $this->photobooth_provider : (array_key_first($providers) ?: 'logrono');
        \App\Models\Setting::set('default_photobooth_provider', $prov);

        $pbHours = (int)($this->quote_services['photobooth']['hours'] ?? 2);
        $pbPrice = (float)($this->quote_services['photobooth']['price'] ?? 350);
        $v360Hours = (int)($this->quote_services['video_360']['hours'] ?? 2);
        $v360Price = (float)($this->quote_services['video_360']['price'] ?? 390);
        $duoHours = (int)($this->quote_services['pack_duo_360']['hours'] ?? 3);
        $duoPrice = (float)($this->quote_services['pack_duo_360']['price'] ?? 850);

        if (isset($providers[$prov])) {
            if ($pbHours <= 2) {
                $providers[$prov]['photobooth_2h'] = $pbPrice;
                $this->quote_services['photobooth']['price_2h'] = $pbPrice;
            } elseif ($pbHours === 3) {
                $providers[$prov]['photobooth_3h'] = $pbPrice;
                $this->quote_services['photobooth']['price_3h'] = $pbPrice;
            }
            if (!empty($this->quote_services['photobooth']['desc'])) {
                $providers[$prov]['photobooth_desc'] = $this->quote_services['photobooth']['desc'];
            }

            if ($v360Hours <= 2) {
                $providers[$prov]['video_360_2h'] = $v360Price;
                $this->quote_services['video_360']['price_2h'] = $v360Price;
            } elseif ($v360Hours === 3) {
                $providers[$prov]['video_360_3h'] = $v360Price;
                $this->quote_services['video_360']['price_3h'] = $v360Price;
            }
            if (!empty($this->quote_services['video_360']['desc'])) {
                $providers[$prov]['video_360_desc'] = $this->quote_services['video_360']['desc'];
            }

            if ($duoHours <= 2) {
                $providers[$prov]['pack_duo_2h'] = $duoPrice;
                $this->quote_services['pack_duo_360']['price_2h'] = $duoPrice;
            } elseif ($duoHours === 3) {
                $providers[$prov]['pack_duo_3h'] = $duoPrice;
                $this->quote_services['pack_duo_360']['price_3h'] = $duoPrice;
            }
            if (!empty($this->quote_services['pack_duo_360']['desc'])) {
                $providers[$prov]['pack_duo_desc'] = $this->quote_services['pack_duo_360']['desc'];
            }

            $chocF = (int)($this->quote_services['chocolate_fountain']['fountains'] ?? 1) >= 2 ? 2 : 1;
            $chocH = (int)($this->quote_services['chocolate_fountain']['hours'] ?? 2) >= 3 ? 3 : 2;
            $chocPrice = (float)($this->quote_services['chocolate_fountain']['price'] ?? 420);
            $providers[$prov]["chocolate_fountain_{$chocF}_{$chocH}h"] = $chocPrice;
            $this->quote_services['chocolate_fountain']["price_{$chocF}_{$chocH}h"] = $chocPrice;
            if (!empty($this->quote_services['chocolate_fountain']['desc'])) {
                $providers[$prov]['chocolate_fountain_desc'] = $this->quote_services['chocolate_fountain']['desc'];
            }

            $providers[$prov]['candy_bar_price'] = (float)($this->quote_services['candy_bar']['price'] ?? 420);
            if (!empty($this->quote_services['candy_bar']['desc'])) {
                $providers[$prov]['candy_bar_desc'] = $this->quote_services['candy_bar']['desc'];
            }

            $waffleH = (int)($this->quote_services['waffle_cart']['hours'] ?? 2) >= 3 ? 3 : 2;
            $wafflePrice = (float)($this->quote_services['waffle_cart']['price'] ?? 420);
            if ($waffleH === 3) {
                $providers[$prov]['waffle_cart_3h'] = $wafflePrice;
                $this->quote_services['waffle_cart']['price_3h'] = $wafflePrice;
            } else {
                $providers[$prov]['waffle_cart_2h'] = $wafflePrice;
                $this->quote_services['waffle_cart']['price_2h'] = $wafflePrice;
            }
            if (!empty($this->quote_services['waffle_cart']['desc'])) {
                $providers[$prov]['waffle_cart_desc'] = $this->quote_services['waffle_cart']['desc'];
            }

            \App\Models\Setting::saveSinglePhotoboothProvider($prov, $providers[$prov]);
        }

        // Efectos y Fotografía
        \App\Models\Setting::set('price_effects_smoke', (float)($this->quote_services['smoke_machine']['price'] ?? 60));
        \App\Models\Setting::set('price_effects_sparks', (float)($this->quote_services['cold_sparks']['price'] ?? 60));
        \App\Models\Setting::set('price_photo_ceremony', (float)($this->quote_services['photo_ceremony']['price'] ?? 250));
        \App\Models\Setting::set('price_photo_restaurant', (float)($this->quote_services['photo_restaurant']['price'] ?? 250));
        \App\Models\Setting::set('price_photo_party', (float)($this->quote_services['photo_party']['price'] ?? 300));
        \App\Models\Setting::set('price_photo_album', (float)($this->quote_services['photo_album']['price'] ?? 150));
        \App\Models\Setting::set('price_photo_full_pack', (float)($this->quote_services['photo_full_pack']['price'] ?? 800));

        session()->flash('quote_message', "💾 ¡Precios y textos actuales guardados como predeterminados para eventos de tipo «{$evLabel}» y proveedor seleccionado!");
    }

    public function syncPackEffectsAdmin()
    {
        $hasPremium = !empty($this->quote_services['pack_premium']['selected']);
        $hasMedium = !empty($this->quote_services['pack_medium']['selected']);

        if ($hasPremium) {
            $this->quote_services['smoke_machine']['included'] = true;
            $this->quote_services['smoke_machine']['selected'] = true;
            $this->quote_services['cold_sparks']['included'] = true;
            $this->quote_services['cold_sparks']['selected'] = true;
            if (empty($this->quote_services['cold_sparks']['quantity'])) {
                $this->quote_services['cold_sparks']['quantity'] = 2;
            }
        } elseif ($hasMedium) {
            $this->quote_services['smoke_machine']['included'] = true;
            $this->quote_services['smoke_machine']['selected'] = true;
            if (!empty($this->quote_services['cold_sparks']['included'])) {
                $this->quote_services['cold_sparks']['selected'] = false;
            }
            $this->quote_services['cold_sparks']['included'] = false;
        } else {
            if (!empty($this->quote_services['smoke_machine']['included'])) {
                $this->quote_services['smoke_machine']['selected'] = false;
            }
            if (!empty($this->quote_services['cold_sparks']['included'])) {
                $this->quote_services['cold_sparks']['selected'] = false;
            }
            $this->quote_services['smoke_machine']['included'] = false;
            $this->quote_services['cold_sparks']['included'] = false;
        }
    }

    public function selectPack($packKey)
    {
        $this->selected_pack_type = $packKey;

        // Reset packs
        $this->quote_services['pack_basic']['selected'] = false;
        $this->quote_services['pack_medium']['selected'] = false;
        $this->quote_services['pack_premium']['selected'] = false;
        $this->quote_services['dj_custom']['selected'] = false;
        $this->quote_services['magic_kids']['selected'] = false;
        $this->quote_services['magic_adults']['selected'] = false;
        $this->quote_services['magic_mixed']['selected'] = false;
        $this->quote_services['magic_closeup']['selected'] = false;

        if ($packKey === 'basic') {
            $this->quote_services['pack_basic']['selected'] = true;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'medium') {
            $this->quote_services['pack_medium']['selected'] = true;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'premium') {
            $this->quote_services['pack_premium']['selected'] = true;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'magic_kids') {
            $this->quote_services['magic_kids']['selected'] = true;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'magic_adults') {
            $this->quote_services['magic_adults']['selected'] = true;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'magic_mixed') {
            $this->quote_services['magic_mixed']['selected'] = true;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'magic_mixed_music') {
            // Espectáculo Magia Mixto / Familiar + 2h Música después de la Magia
            $this->quote_services['magic_mixed']['selected'] = true;
            $this->quote_services['dj_custom']['selected'] = true;
            $this->quote_services['dj_custom']['quantity'] = 2;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'magic_kids_music') {
            // Espectáculo Magia Infantil + 2h Música después de la Magia
            $this->quote_services['magic_kids']['selected'] = true;
            $this->quote_services['dj_custom']['selected'] = true;
            $this->quote_services['dj_custom']['quantity'] = 2;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'wedding_dj') {
            // Pack Boda Típico DJ: Cóctel + Banquete/Regalos + Pack Básico (4h DJ, Sonido + Iluminación)
            $this->quote_services['cocktail']['selected'] = true;
            $this->quote_services['restaurant']['selected'] = true;
            $this->quote_services['pack_basic']['selected'] = true;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'wedding_full') {
            // Pack Boda Completo: Cóctel + Banquete + Pack Medio (5h DJ, Sonido Alta Gama + Luces + Humo) + Pack Completo Fotos
            $this->quote_services['cocktail']['selected'] = true;
            $this->quote_services['restaurant']['selected'] = true;
            $this->quote_services['pack_medium']['selected'] = true;
            $this->quote_services['photo_full_pack']['selected'] = true;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'event_sound_dj') {
            // Sonorización de Evento/Acto + 2h DJ Baile
            $this->quote_services['ceremony']['selected'] = true;
            $this->quote_services['dj_custom']['selected'] = true;
            $this->quote_services['dj_custom']['quantity'] = 2;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'event_sound_only') {
            // Solo Sonorización de Evento / Acto / Escenario
            $this->quote_services['ceremony']['selected'] = true;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'event_meal_dj') {
            // Ambientación Comida/Cena + Pack Esencial DJ (4h)
            $this->quote_services['restaurant']['selected'] = true;
            $this->quote_services['pack_basic']['selected'] = true;
            $this->syncPackEffectsAdmin();
        } elseif ($packKey === 'clear') {
            foreach ($this->quote_services as $k => $v) {
                if (isset($this->quote_services[$k]['selected'])) {
                    $this->quote_services[$k]['selected'] = false;
                }
            }
            $this->quote_services['smoke_machine']['included'] = false;
            $this->quote_services['cold_sparks']['included'] = false;
            $this->quote_services['extra_hours']['quantity'] = 0;
            $this->custom_extras = [
                ['name' => '', 'price' => 0, 'desc' => '', 'consult' => false]
            ];
            $this->quote_admin_notes = '';
            $this->selected_pack_type = 'custom';
        }
    }

    public function addCustomExtra()
    {
        $this->custom_extras[] = ['name' => '', 'price' => 0, 'desc' => '', 'consult' => false];
    }

    public function removeCustomExtra($index)
    {
        if (isset($this->custom_extras[$index])) {
            unset($this->custom_extras[$index]);
            $this->custom_extras = array_values($this->custom_extras);
        }
        if (empty($this->custom_extras)) {
            $this->custom_extras = [['name' => '', 'price' => 0, 'desc' => '', 'consult' => false]];
        }
    }

    public function getCalculatedGrossTotalProperty()
    {
        $total = 0;

        // Packs
        if (!empty($this->quote_services['pack_basic']['selected'])) {
            $total += (float)$this->quote_services['pack_basic']['price'];
        }
        if (!empty($this->quote_services['pack_medium']['selected'])) {
            $total += (float)$this->quote_services['pack_medium']['price'];
        }
        if (!empty($this->quote_services['pack_premium']['selected'])) {
            $total += (float)$this->quote_services['pack_premium']['price'];
        }

        // Espectáculos de Magia (Mago Leugim)
        foreach (['magic_kids', 'magic_adults', 'magic_mixed', 'magic_closeup'] as $mKey) {
            if (!empty($this->quote_services[$mKey]['selected'])) {
                $total += (float)($this->quote_services[$mKey]['price'] ?? 0) * (int)($this->quote_services[$mKey]['quantity'] ?? 1);
            }
        }

        // Servicios musicales
        if (!empty($this->quote_services['ceremony']['selected'])) {
            $total += (float)$this->quote_services['ceremony']['price'] * (int)($this->quote_services['ceremony']['quantity'] ?: 1);
        }
        if (!empty($this->quote_services['cocktail']['selected'])) {
            $total += (float)$this->quote_services['cocktail']['price'] * (int)($this->quote_services['cocktail']['quantity'] ?: 1);
        }
        if (!empty($this->quote_services['restaurant']['selected'])) {
            $total += (float)$this->quote_services['restaurant']['price'] * (int)($this->quote_services['restaurant']['quantity'] ?: 1);
        }

        // Descuento Pack Cóctel + Banquete si ambos están seleccionados
        if (!empty($this->quote_services['cocktail']['selected']) && !empty($this->quote_services['restaurant']['selected'])) {
            $cPrice = (float)($this->quote_services['cocktail']['price'] ?: 0);
            $rPrice = (float)($this->quote_services['restaurant']['price'] ?: 0);
            $packInfo = \App\Models\Setting::getCocktailRestaurantPackInfo($cPrice, $rPrice);
            if (!empty($packInfo['enabled']) && $packInfo['discount_amount'] > 0) {
                $total -= (float)$packInfo['discount_amount'];
            }
        }
        if (!empty($this->quote_services['dj_custom']['selected'])) {
            $total += (float)$this->quote_services['dj_custom']['price'] * (int)($this->quote_services['dj_custom']['quantity'] ?: 1);
        }
        $extraH = (int)($this->quote_services['extra_hours']['quantity'] ?: 0);
        if ($extraH > 0) {
            $total += (float)$this->quote_services['extra_hours']['price'] * $extraH;
        }

        // Fotografía
        if (!empty($this->quote_services['photo_full_pack']['selected'])) {
            $total += (float)$this->quote_services['photo_full_pack']['price'] * (int)($this->quote_services['photo_full_pack']['quantity'] ?: 1);
        } else {
            if (!empty($this->quote_services['photo_ceremony']['selected'])) {
                $total += (float)$this->quote_services['photo_ceremony']['price'] * (int)($this->quote_services['photo_ceremony']['quantity'] ?: 1);
            }
            if (!empty($this->quote_services['photo_restaurant']['selected'])) {
                $total += (float)$this->quote_services['photo_restaurant']['price'] * (int)($this->quote_services['photo_restaurant']['quantity'] ?: 1);
            }
            if (!empty($this->quote_services['photo_party']['selected'])) {
                $total += (float)$this->quote_services['photo_party']['price'] * (int)($this->quote_services['photo_party']['quantity'] ?: 1);
            }
            if (!empty($this->quote_services['photo_album']['selected'])) {
                $total += (float)$this->quote_services['photo_album']['price'] * (int)($this->quote_services['photo_album']['quantity'] ?: 1);
            }
        }

        // Karaoke, Fotomatón, Plataforma 360º, Pack Dúo y Fuente de Chocolate
        if (!empty($this->quote_services['karaoke']['selected'])) {
            $total += (float)$this->quote_services['karaoke']['price'] * (int)($this->quote_services['karaoke']['quantity'] ?: 1);
        }
        if (!empty($this->quote_services['pack_duo_360']['selected'])) {
            $total += (float)$this->quote_services['pack_duo_360']['price'] * (int)($this->quote_services['pack_duo_360']['quantity'] ?: 1);
        } else {
            if (!empty($this->quote_services['photobooth']['selected'])) {
                $total += (float)$this->quote_services['photobooth']['price'] * (int)($this->quote_services['photobooth']['quantity'] ?: 1);
            }
            if (!empty($this->quote_services['video_360']['selected'])) {
                $total += (float)$this->quote_services['video_360']['price'] * (int)($this->quote_services['video_360']['quantity'] ?: 1);
            }
        }
        if (!empty($this->quote_services['chocolate_fountain']['selected']) && empty($this->quote_services['chocolate_fountain']['consult'])) {
            $total += (float)$this->quote_services['chocolate_fountain']['price'] * (int)($this->quote_services['chocolate_fountain']['quantity'] ?: 1);
        }
        if (!empty($this->quote_services['candy_bar']['selected']) && empty($this->quote_services['candy_bar']['consult'])) {
            $total += (float)$this->quote_services['candy_bar']['price'] * (int)($this->quote_services['candy_bar']['quantity'] ?: 1);
        }
        if (!empty($this->quote_services['waffle_cart']['selected']) && empty($this->quote_services['waffle_cart']['consult'])) {
            $total += (float)$this->quote_services['waffle_cart']['price'] * (int)($this->quote_services['waffle_cart']['quantity'] ?: 1);
        }

        // Efectos Especiales (0€ si están incluidos en el Pack contratado)
        if (!empty($this->quote_services['smoke_machine']['selected']) && empty($this->quote_services['smoke_machine']['included'])) {
            $total += (float)$this->quote_services['smoke_machine']['price'] * (int)($this->quote_services['smoke_machine']['quantity'] ?: 1);
        }
        if (!empty($this->quote_services['cold_sparks']['selected'])) {
            $qty = (int)($this->quote_services['cold_sparks']['quantity'] ?: 1);
            $unitPrice = (float)($this->quote_services['cold_sparks']['price'] ?: 0);
            if (!empty($this->quote_services['cold_sparks']['included'])) {
                // En pack se incluyen 2 máquinas (0 €); cualquier máquina adicional se cobra al precio unitario
                $extraQty = max(0, $qty - 2);
                $total += ($extraQty * $unitPrice);
            } else {
                $total += ($qty * $unitPrice);
            }
        }

        if (!empty($this->custom_extras)) {
            foreach ($this->custom_extras as $extra) {
                if (!empty(trim($extra['name'] ?? '')) && empty($extra['consult'])) {
                    $total += (float)($extra['price'] ?: 0);
                }
            }
        }

        return $total;
    }

    public function getCalculatedSubtotalProperty()
    {
        $gross = (float)$this->calculated_gross_total;
        $rate = (float)($this->quote_tax_rate ?: 21.00);

        if ($this->quote_tax_type === 'included') {
            return round($gross / (1 + ($rate / 100)), 2);
        }

        return $gross;
    }

    public function getCalculatedTaxAmountProperty()
    {
        $gross = (float)$this->calculated_gross_total;
        $rate = (float)($this->quote_tax_rate ?: 21.00);

        if ($this->quote_tax_type === 'included') {
            return round($gross - $this->calculated_subtotal, 2);
        }

        if ($this->quote_tax_type === 'excluded') {
            return round(($gross * $rate) / 100, 2);
        }

        return 0.00;
    }

    public function getCalculatedTotalProperty()
    {
        $gross = (float)$this->calculated_gross_total;

        if ($this->quote_tax_type === 'excluded') {
            return round($gross + $this->calculated_tax_amount, 2);
        }

        return $gross;
    }

    public function getCalculatedSignalProperty()
    {
        if ($this->quote_deposit_type === 'none') {
            return 0.0;
        }
        $total = (float)$this->calculated_total;
        if ($this->quote_deposit_type === 'fixed') {
            return min((float)$this->quote_deposit_fixed_amount, $total);
        }
        $pct = (float)($this->quote_deposit_percentage ?: 0);
        return round(($total * $pct) / 100, 2);
    }

    public function getCalculatedRemainingProperty()
    {
        return max(0, (float)$this->calculated_total - (float)$this->calculated_signal);
    }

    private function buildQuoteItemsList(&$total)
    {
        $total = 0;
        $items = [];

        // 1. Packs
        if (!empty($this->quote_services['pack_basic']['selected'])) {
            $p = (float)$this->quote_services['pack_basic']['price'];
            $items[] = [
                'service_name' => $this->quote_services['pack_basic']['name'] . ' (' . $this->quote_services['pack_basic']['hours'] . 'h DJ)',
                'description' => $this->quote_services['pack_basic']['desc'],
                'price' => $p,
                'quantity' => 1
            ];
            $total += $p;
        }
        if (!empty($this->quote_services['pack_medium']['selected'])) {
            $p = (float)$this->quote_services['pack_medium']['price'];
            $items[] = [
                'service_name' => $this->quote_services['pack_medium']['name'] . ' (' . $this->quote_services['pack_medium']['hours'] . 'h DJ)',
                'description' => $this->quote_services['pack_medium']['desc'],
                'price' => $p,
                'quantity' => 1
            ];
            $total += $p;
        }
        if (!empty($this->quote_services['pack_premium']['selected'])) {
            $p = (float)$this->quote_services['pack_premium']['price'];
            $items[] = [
                'service_name' => $this->quote_services['pack_premium']['name'] . ' (' . $this->quote_services['pack_premium']['hours'] . 'h DJ)',
                'description' => $this->quote_services['pack_premium']['desc'],
                'price' => $p,
                'quantity' => 1
            ];
            $total += $p;
        }

        // 1.B Espectáculos de Magia (Mago Leugim: Infantil, Adultos, Mixto y Close-Up)
        $magicDefaults = [
            'magic_kids' => 'Espectáculo de Magia Infantil',
            'magic_adults' => 'Espectáculo de Magia para Adultos',
            'magic_mixed' => 'Espectáculo de Magia Mixto / Familiar (Infantil y Adultos)',
            'magic_closeup' => 'Magia de Cerca (Close-Up en Recepción / Cóctel)',
        ];
        foreach ($magicDefaults as $mKey => $mDefaultTitle) {
            if (!empty($this->quote_services[$mKey]['selected'])) {
                $qty = (int)($this->quote_services[$mKey]['quantity'] ?? 1) ?: 1;
                $p = (float)($this->quote_services[$mKey]['price'] ?? 0);
                $mTitle = trim((string)($this->quote_services[$mKey]['name'] ?? '')) ?: $mDefaultTitle;
                $items[] = [
                    'service_name' => $mTitle,
                    'description' => $this->quote_services[$mKey]['desc'] ?? '',
                    'price' => $p,
                    'quantity' => $qty,
                ];
                $total += ($p * $qty);
            }
        }

        // 2. Servicios Musicales Individuales
        if (!empty($this->quote_services['ceremony']['selected'])) {
            $qty = (int)($this->quote_services['ceremony']['quantity'] ?: 1);
            $p = (float)($this->quote_services['ceremony']['price'] ?: 0);
            $ceremonyTitle = trim((string)($this->quote_services['ceremony']['name'] ?? ''));
            if ($ceremonyTitle === '') {
                $ceremonyTitle = ($this->event && !$this->event->is_wedding) ? 'Sonorización de Evento / Acto / Escenario' : 'Ceremonia';
            }
            $items[] = ['service_name' => $ceremonyTitle, 'description' => $this->quote_services['ceremony']['desc'], 'price' => $p, 'quantity' => $qty];
            $total += ($p * $qty);
        }
        if (!empty($this->quote_services['cocktail']['selected'])) {
            $qty = (int)($this->quote_services['cocktail']['quantity'] ?: 1);
            $p = (float)($this->quote_services['cocktail']['price'] ?: 0);
            $cocktailTitle = trim((string)($this->quote_services['cocktail']['name'] ?? ''));
            if ($cocktailTitle === '') {
                $cocktailTitle = ($this->event && !$this->event->is_wedding) ? 'Ambientación Recepción / Aperitivo' : 'Música en el Cóctel';
            }
            $items[] = ['service_name' => $cocktailTitle, 'description' => $this->quote_services['cocktail']['desc'], 'price' => $p, 'quantity' => $qty];
            $total += ($p * $qty);
        }

        // Regalo incluido (0 €) al contratar desde Ceremonia o Cóctel (marcas DJ / Bodas)
        if (
            ($this->event?->brand_clean ?? '') !== 'mago_leugim' &&
            (!empty($this->quote_services['ceremony']['selected']) || !empty($this->quote_services['cocktail']['selected']))
        ) {
            $items[] = [
                'service_name' => 'Álbum de Fotos QR para Invitados (Incluido de regalo con Ceremonia / Cóctel)',
                'description' => 'Código QR personalizado para que todos los invitados suban y descarguen en calidad original las fotos que hagan con su móvil durante el evento, sin instalar ninguna app.',
                'price' => 0,
                'quantity' => 1,
            ];
        }
        if (!empty($this->quote_services['restaurant']['selected'])) {
            $qty = (int)($this->quote_services['restaurant']['quantity'] ?: 1);
            $p = (float)($this->quote_services['restaurant']['price'] ?: 0);
            $restTitle = trim((string)($this->quote_services['restaurant']['name'] ?? ''));
            if ($restTitle === '' || ($this->event && !$this->event->is_wedding && $restTitle === 'Regalos & Banquete')) {
                $restTitle = ($this->event && !$this->event->is_wedding) ? 'Ambientación Comida / Cena & Momentos Especiales' : 'Sonorización Banquete y Regalos';
            }
            $items[] = ['service_name' => $restTitle, 'description' => $this->quote_services['restaurant']['desc'], 'price' => $p, 'quantity' => $qty];
            $total += ($p * $qty);
        }

        // Descuento Pack Cóctel + Banquete
        if (!empty($this->quote_services['cocktail']['selected']) && !empty($this->quote_services['restaurant']['selected'])) {
            $cPrice = (float)($this->quote_services['cocktail']['price'] ?: 0);
            $rPrice = (float)($this->quote_services['restaurant']['price'] ?: 0);
            $packInfo = \App\Models\Setting::getCocktailRestaurantPackInfo($cPrice, $rPrice);
            if (!empty($packInfo['enabled']) && $packInfo['discount_amount'] > 0) {
                $discountAmt = (float)$packInfo['discount_amount'];
                $discountLabel = ($this->event && !$this->event->is_wedding)
                    ? 'Descuento Especial Pack Recepción + Comida/Cena'
                    : 'Descuento Especial Pack Cóctel + Banquete';
                $items[] = [
                    'service_name' => $discountLabel,
                    'description' => 'Tarifa combinada promocional por contratación conjunta (' . round($packInfo['discount_percentage']) . '% dto.)',
                    'price' => -$discountAmt,
                    'quantity' => 1
                ];
                $total -= $discountAmt;
            }
        }
        if (!empty($this->quote_services['dj_custom']['selected'])) {
            $qty = (int)($this->quote_services['dj_custom']['quantity'] ?: 1);
            $p = (float)($this->quote_services['dj_custom']['price'] ?: 0);
            $djDesc = trim((string)($this->quote_services['dj_custom']['desc'] ?? ''));
            if ($djDesc === '' || $djDesc === 'Horas de DJ en directo.') {
                $djDesc = ($this->event && $this->event->brand_clean === 'mago_leugim')
                    ? 'Sesión musical, animación y baile al finalizar el espectáculo de magia con equipo de sonido e iluminación.'
                    : 'Actuación DJ en directo con repertorio a medida (ampliable durante el evento)';
            }
            $customDjTitle = trim((string)($this->quote_services['dj_custom']['name'] ?? ''));
            $customDjTitle = trim(str_ireplace('(Horas sueltas)', '', $customDjTitle));
            if ($this->event && $this->event->brand_clean === 'mago_leugim') {
                $djBaseTitle = $customDjTitle ?: 'Música y Fiesta después de la Magia';
            } else {
                $djBaseTitle = $customDjTitle ?: (($this->event && !$this->event->is_wedding) ? 'Sesión DJ Fiesta / Baile' : 'Baile DJ');
            }
            $items[] = ['service_name' => $djBaseTitle . ' (' . $qty . ' ' . ($qty === 1 ? 'Hora' : 'Horas') . ')', 'description' => $djDesc, 'price' => $p, 'quantity' => $qty];
            $total += ($p * $qty);
        }
        
        $eh_qty = (int)($this->quote_services['extra_hours']['quantity'] ?: 0);
        if ($eh_qty > 0) {
            $p = (float)($this->quote_services['extra_hours']['price'] ?: 0);
            $ehDesc = trim((string)($this->quote_services['extra_hours']['desc'] ?? '')) ?: 'Horas adicionales sobre el horario pactado';
            $items[] = ['service_name' => 'Horas Extra de Baile (' . $eh_qty . 'h)', 'description' => $ehDesc, 'price' => $p, 'quantity' => $eh_qty];
            $total += ($p * $eh_qty);
        }

        // 3. Fotografía
        if (!empty($this->quote_services['photo_full_pack']['selected'])) {
            $p = (float)($this->quote_services['photo_full_pack']['price'] ?: 0);
            $items[] = ['service_name' => 'Pack Completo Fotografía', 'description' => 'Cobertura completa: Ceremonia, Restaurante, Fiesta, Maquetación álbum digital, todas las fotos en alta calidad y sin marcas de agua.', 'price' => $p, 'quantity' => 1];
            $total += $p;
        } else {
            if (!empty($this->quote_services['photo_ceremony']['selected'])) {
                $p = (float)($this->quote_services['photo_ceremony']['price'] ?: 0);
                $items[] = ['service_name' => 'Fotografía: Ceremonia', 'description' => 'Cobertura fotográfica de la ceremonia en alta resolución.', 'price' => $p, 'quantity' => 1];
                $total += $p;
            }
            if (!empty($this->quote_services['photo_restaurant']['selected'])) {
                $p = (float)($this->quote_services['photo_restaurant']['price'] ?: 0);
                $items[] = ['service_name' => 'Fotografía: Restaurante / Banquete', 'description' => 'Cobertura de momentos emotivos y regalos durante el banquete.', 'price' => $p, 'quantity' => 1];
                $total += $p;
            }
            if (!empty($this->quote_services['photo_party']['selected'])) {
                $p = (float)($this->quote_services['photo_party']['price'] ?: 0);
                $items[] = ['service_name' => 'Fotografía: Fiesta y Baile', 'description' => 'Fotografías del baile nupcial y fiesta de invitados.', 'price' => $p, 'quantity' => 1];
                $total += $p;
            }
            if (!empty($this->quote_services['photo_album']['selected'])) {
                $p = (float)($this->quote_services['photo_album']['price'] ?: 0);
                $items[] = ['service_name' => 'Maquetación Álbum Digital', 'description' => 'Maquetación profesional de álbum digital (sin imprimir, opciones de impresión a parte).', 'price' => $p, 'quantity' => 1];
                $total += $p;
            }
        }
        
        // 4. Karaoke, Fotomatón, Plataforma 360º, Pack Dúo, Mesa con Fuente de Chocolate, Mesa de Chuches y Carrito de Gofres
        if (!empty($this->quote_services['karaoke']['selected'])) {
            $qty = (int)($this->quote_services['karaoke']['quantity'] ?: 1);
            $p = (float)($this->quote_services['karaoke']['price'] ?: 0);
            $items[] = ['service_name' => 'Karaoke & Animación', 'description' => 'Micrófonos inalámbricos y catálogo de canciones para invitados', 'price' => $p, 'quantity' => $qty];
            $total += ($p * $qty);
        }

        if (!empty($this->quote_services['pack_duo_360']['selected'])) {
            $qty = (int)($this->quote_services['pack_duo_360']['quantity'] ?: 1);
            $hours = (int)($this->quote_services['pack_duo_360']['hours'] ?: 3);
            $p = (float)($this->quote_services['pack_duo_360']['price'] ?: 0);
            $items[] = [
                'service_name' => 'Pack Dúo Todo Incluido: Fotomatón + Plataforma 360º (' . $hours . ' Horas)',
                'description' => trim((string)($this->quote_services['pack_duo_360']['desc'] ?? '')),
                'price' => $p,
                'quantity' => $qty
            ];
            $total += ($p * $qty);
        } else {
            if (!empty($this->quote_services['photobooth']['selected'])) {
                $qty = (int)($this->quote_services['photobooth']['quantity'] ?: 1);
                $hours = (int)($this->quote_services['photobooth']['hours'] ?: 2);
                $p = (float)($this->quote_services['photobooth']['price'] ?: 0);
                $items[] = [
                    'service_name' => 'Fotomatón & Photocall (' . $hours . ' Horas)',
                    'description' => trim((string)($this->quote_services['photobooth']['desc'] ?? '')),
                    'price' => $p,
                    'quantity' => $qty
                ];
                $total += ($p * $qty);
            }

            if (!empty($this->quote_services['video_360']['selected'])) {
                $qty = (int)($this->quote_services['video_360']['quantity'] ?: 1);
                $hours = (int)($this->quote_services['video_360']['hours'] ?: 2);
                $p = (float)($this->quote_services['video_360']['price'] ?: 0);
                $items[] = [
                    'service_name' => 'Plataforma Vídeo 360º (' . $hours . ' Horas)',
                    'description' => trim((string)($this->quote_services['video_360']['desc'] ?? '')),
                    'price' => $p,
                    'quantity' => $qty
                ];
                $total += ($p * $qty);
            }
        }

        if (!empty($this->quote_services['chocolate_fountain']['selected'])) {
            $qty = (int)($this->quote_services['chocolate_fountain']['quantity'] ?: 1);
            $fountains = (int)($this->quote_services['chocolate_fountain']['fountains'] ?? 1) >= 2 ? 2 : 1;
            $hours = (int)($this->quote_services['chocolate_fountain']['hours'] ?? 2) >= 3 ? 3 : 2;
            $isConsult = !empty($this->quote_services['chocolate_fountain']['consult']);
            $p = $isConsult ? 0 : (float)($this->quote_services['chocolate_fountain']['price'] ?: 0);
            $fountainLabel = $fountains === 2 ? '2 Fuentes de Chocolate' : '1 Fuente de Chocolate';
            $items[] = [
                'service_name' => "Mesa Dulce con {$fountainLabel} ({$hours} Horas)" . ($isConsult ? ' (A consultar)' : ''),
                'description' => $this->quote_services['chocolate_fountain']['desc'] . ($isConsult ? ' (Tarifa y disponibilidad a consultar según comensales).' : ''),
                'price' => $p,
                'quantity' => $qty
            ];
            $total += ($p * $qty);
        }

        if (!empty($this->quote_services['candy_bar']['selected'])) {
            $qty = (int)($this->quote_services['candy_bar']['quantity'] ?: 1);
            $isConsult = !empty($this->quote_services['candy_bar']['consult']);
            $p = $isConsult ? 0 : (float)($this->quote_services['candy_bar']['price'] ?: 0);
            $items[] = [
                'service_name' => 'Mesa de Solo Chuches' . ($isConsult ? ' (A consultar)' : ''),
                'description' => $this->quote_services['candy_bar']['desc'] . ($isConsult ? ' (Tarifa y disponibilidad a consultar).' : ''),
                'price' => $p,
                'quantity' => $qty
            ];
            $total += ($p * $qty);
        }

        if (!empty($this->quote_services['waffle_cart']['selected'])) {
            $qty = (int)($this->quote_services['waffle_cart']['quantity'] ?: 1);
            $hours = (int)($this->quote_services['waffle_cart']['hours'] ?? 2) >= 3 ? 3 : 2;
            $wafflesApprox = $hours === 3 ? 'unos 100 gofres' : 'unos 60 gofres';
            $isConsult = !empty($this->quote_services['waffle_cart']['consult']);
            $p = $isConsult ? 0 : (float)($this->quote_services['waffle_cart']['price'] ?: 0);
            $items[] = [
                'service_name' => "Carrito de Gofres hechos al momento ({$hours} Horas · {$wafflesApprox})" . ($isConsult ? ' (A consultar)' : ''),
                'description' => $this->quote_services['waffle_cart']['desc'] . ($isConsult ? ' (Tarifa y disponibilidad a consultar).' : ''),
                'price' => $p,
                'quantity' => $qty
            ];
            $total += ($p * $qty);
        }

        // 5. Efectos Especiales (Máquina de Humo & Fuego Frío)
        if (!empty($this->quote_services['smoke_machine']['selected'])) {
            $isIncluded = !empty($this->quote_services['smoke_machine']['included']);
            $packName = !empty($this->quote_services['pack_premium']['selected']) 
                ? ($this->quote_services['pack_premium']['name'] ?? 'Pack') 
                : ($this->quote_services['pack_medium']['name'] ?? 'Pack');
            $p = $isIncluded ? 0 : (float)($this->quote_services['smoke_machine']['price'] ?: 0);
            $qty = (int)($this->quote_services['smoke_machine']['quantity'] ?: 1);

            $items[] = [
                'service_name' => $isIncluded ? 'Máquina de Humo (Incluido en ' . $packName . ')' : 'Máquina de Humo (Efecto pista)',
                'description' => $isIncluded ? 'Efecto de máquina de humo para ambientar la pista de baile (incluido en el pack contratado).' : ($this->quote_services['smoke_machine']['desc'] ?? 'Efecto de humo para pista de baile.'),
                'price' => $p,
                'quantity' => $qty,
            ];
            $total += ($p * $qty);
        }

        if (!empty($this->quote_services['cold_sparks']['selected'])) {
            $isIncluded = !empty($this->quote_services['cold_sparks']['included']);
            $packName = $this->quote_services['pack_premium']['name'] ?? 'Pack';
            $unitPrice = (float)($this->quote_services['cold_sparks']['price'] ?: 0);
            $qty = (int)($this->quote_services['cold_sparks']['quantity'] ?: 1);

            if ($isIncluded) {
                $includedQty = min($qty, 2);
                $items[] = [
                    'service_name' => 'Fuego Frío / Chispas Frías (' . $includedQty . ' ' . ($includedQty == 1 ? 'máquina incluida' : 'máquinas incluidas') . ' en ' . $packName . ')',
                    'description' => 'Efecto especial de chispas frías para momentos clave (incluido en el pack contratado).',
                    'price' => 0,
                    'quantity' => $includedQty,
                ];

                if ($qty > 2) {
                    $extraQty = $qty - 2;
                    $items[] = [
                        'service_name' => 'Fuego Frío / Chispas Frías Adicionales (' . $extraQty . ' ' . ($extraQty == 1 ? 'máquina extra' : 'máquinas extra') . ' a ' . number_format($unitPrice, 0) . ' €/ud)',
                        'description' => 'Máquinas adicionales de chispas frías añadidas al espectáculo.',
                        'price' => $unitPrice,
                        'quantity' => $extraQty,
                    ];
                    $total += ($unitPrice * $extraQty);
                }
            } else {
                $items[] = [
                    'service_name' => 'Fuego Frío / Chispas Frías (' . $qty . ' ' . ($qty == 1 ? 'máquina' : 'máquinas') . ' a ' . number_format($unitPrice, 0) . ' €/ud)',
                    'description' => $this->quote_services['cold_sparks']['desc'] ?? 'Efecto especial de fuego frío / chispas frías.',
                    'price' => $unitPrice,
                    'quantity' => $qty,
                ];
                $total += ($unitPrice * $qty);
            }
        }
        
        if (!empty($this->custom_extras)) {
            foreach ($this->custom_extras as $extra) {
                $custom_name = trim($extra['name'] ?? '');
                if (!empty($custom_name)) {
                    $isConsult = !empty($extra['consult']);
                    $p = $isConsult ? 0 : (float)($extra['price'] ?: 0);
                    $customDesc = trim($extra['desc'] ?? '');
                    
                    if ($isConsult) {
                        $desc = $customDesc ? ($customDesc . ' (Tarifa y disponibilidad a consultar)') : 'Servicio extra personalizado (Tarifa y disponibilidad a consultar según necesidades del evento).';
                    } else {
                        $desc = $customDesc ?: 'Servicio extra personalizado acordado';
                    }

                    $items[] = [
                        'service_name' => $custom_name . ($isConsult ? ' (A consultar)' : ''),
                        'description' => $desc,
                        'price' => $p,
                        'quantity' => 1
                    ];
                    $total += $p;
                }
            }
        }

        return $items;
    }

    public function createQuote()
    {
        $grossTotal = 0;
        $items = $this->buildQuoteItemsList($grossTotal);

        if (empty($items)) {
            session()->flash('quote_error', 'Debes seleccionar al menos un Pack o Servicio para generar la propuesta comercial.');
            return;
        }

        $signal = $this->calculated_signal;
        $subtotal = $this->calculated_subtotal;
        $taxAmount = $this->calculated_tax_amount;
        $finalTotal = $this->calculated_total;

        $quote = $this->event->quotes()->create([
            'amount' => $finalTotal,
            'status' => 'pending',
            'admin_notes' => trim((string)$this->quote_admin_notes) ?: null,
            'deposit_type' => $this->quote_deposit_type,
            'deposit_percentage' => $this->quote_deposit_type === 'percentage' ? (float)$this->quote_deposit_percentage : ($this->quote_deposit_type === 'none' ? 0 : null),
            'deposit_amount' => $signal,
            'tax_type' => $this->quote_tax_type,
            'tax_rate' => (float)$this->quote_tax_rate,
            'subtotal_amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'payment_methods' => $this->quote_payment_methods,
        ]);

        foreach ($items as $item) {
            $quote->items()->create($item);
        }

        $this->editing_quote_id = null;
        $this->event->load('quotes.items');
        session()->flash('quote_message', 'Nueva Propuesta Comercial #' . $quote->id . ' generada exitosamente (Total: ' . number_format($finalTotal, 2, ',', '.') . ' € | Señal de reserva: ' . number_format($signal, 2, ',', '.') . ' €).');
    }

    public function updateQuote()
    {
        if (!$this->editing_quote_id) {
            $this->createQuote();
            return;
        }

        $quote = \App\Models\Quote::findOrFail($this->editing_quote_id);
        
        $grossTotal = 0;
        $items = $this->buildQuoteItemsList($grossTotal);

        if (empty($items)) {
            session()->flash('quote_error', 'Debes seleccionar al menos un Pack o Servicio para la propuesta comercial.');
            return;
        }

        $signal = $this->calculated_signal;
        $subtotal = $this->calculated_subtotal;
        $taxAmount = $this->calculated_tax_amount;
        $finalTotal = $this->calculated_total;

        $quote->update([
            'amount' => $finalTotal,
            'admin_notes' => trim((string)$this->quote_admin_notes) ?: null,
            'deposit_type' => $this->quote_deposit_type,
            'deposit_percentage' => $this->quote_deposit_type === 'percentage' ? (float)$this->quote_deposit_percentage : ($this->quote_deposit_type === 'none' ? 0 : null),
            'deposit_amount' => $signal,
            'tax_type' => $this->quote_tax_type,
            'tax_rate' => (float)$this->quote_tax_rate,
            'subtotal_amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'payment_methods' => $this->quote_payment_methods,
        ]);

        $quote->items()->delete();
        foreach ($items as $item) {
            $quote->items()->create($item);
        }

        $this->editing_quote_id = null;
        $this->event->load('quotes.items');
        session()->flash('quote_message', 'Propuesta Comercial #' . $quote->id . ' actualizada correctamente (Total: ' . number_format($finalTotal, 2, ',', '.') . ' € | Señal: ' . number_format($signal, 2, ',', '.') . ' €).');
    }

    public function applyGradualDeposit()
    {
        $this->quote_deposit_type = 'percentage';
        $this->quote_deposit_percentage = \App\Models\Quote::calculateGradualDepositPercentage($this->event?->event_date);
    }

    public function loadQuoteIntoCalculator($quoteId, bool $flash = true)
    {
        $quote = $this->event->quotes()->with('items')->findOrFail($quoteId);
        $this->editing_quote_id = $flash ? $quote->id : null;
        
        // Cargar configuración de señal (si no tiene porcentaje explícito, calcula gradualmente del 25% al 50% según fecha del evento)
        $this->quote_deposit_type = $quote->deposit_type ?: 'percentage';
        if ($this->quote_deposit_type === 'none') {
            $this->quote_deposit_percentage = 0;
            $this->quote_deposit_fixed_amount = 0;
        } elseif ($this->quote_deposit_type === 'percentage') {
            $this->quote_deposit_percentage = (float)($quote->deposit_percentage ?: \App\Models\Quote::calculateGradualDepositPercentage($this->event?->event_date, $quote->created_at));
        } else {
            $this->quote_deposit_fixed_amount = (float)($quote->deposit_amount ?: 200);
        }

        // Cargar configuración de IVA y Métodos de Pago
        $this->quote_tax_type = $quote->tax_type ?: 'included';
        $this->quote_tax_rate = (float)($quote->tax_rate ?: 21.00);
        $this->quote_payment_methods = $quote->active_payment_methods;

        // Limpiar selección previa
        $this->selectPack('clear');
        $this->quote_admin_notes = $quote->admin_notes ?? '';
        $this->selected_pack_type = 'custom';
        $loadedCustomExtras = [];
        $pbConfig = \App\Models\Setting::getPhotobooth360Config($this->photobooth_provider ?: 'vitoria');

        // Mapear cada línea
        foreach ($quote->items as $item) {
            $name = mb_strtolower($item->service_name ?: $item->concept ?: '');
            
            // IMPORTANTE: Comprobar primero si es una línea de descuento o de efectos (humo/chispas incluidas en pack)
            // para que NO coincidan por nombre con 'cóctel', 'banquete' ni 'pack signature / pack deluxe'
            if (
                str_contains($name, 'descuento') ||
                (float)$item->price < 0 ||
                str_contains($name, 'fotos qr') ||
                str_contains($name, 'álbum colaborativo') ||
                str_contains($name, 'album colaborativo')
            ) {
                continue;
            } elseif (str_contains($name, 'máquina de humo') || str_contains($name, 'maquina de humo') || str_contains($name, 'humo')) {
                $this->quote_services['smoke_machine']['selected'] = true;
                $this->quote_services['smoke_machine']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['smoke_machine']['price'] = (float)$item->price;
                $this->quote_services['smoke_machine']['included'] = ((float)$item->price == 0 || str_contains($name, 'incluido'));
                continue;
            } elseif (str_contains($name, 'fuego frío') || str_contains($name, 'fuego frio') || str_contains($name, 'chispas frías') || str_contains($name, 'chispas frias') || str_contains($name, 'cold sparks')) {
                if (!empty($this->quote_services['cold_sparks']['selected'])) {
                    $this->quote_services['cold_sparks']['quantity'] += (int)($item->quantity ?: 1);
                    if ((float)$item->price > 0) {
                        $this->quote_services['cold_sparks']['price'] = (float)$item->price;
                    }
                } else {
                    $this->quote_services['cold_sparks']['selected'] = true;
                    $this->quote_services['cold_sparks']['quantity'] = (int)($item->quantity ?: 1);
                    if ((float)$item->price > 0) {
                        $this->quote_services['cold_sparks']['price'] = (float)$item->price;
                    } else {
                        $this->quote_services['cold_sparks']['price'] = (float)\App\Models\Setting::get('price_effects_sparks', 60);
                    }
                    $this->quote_services['cold_sparks']['included'] = ((float)$item->price == 0 || str_contains($name, 'incluido'));
                }
                continue;
            }

            if (str_contains($name, 'pack básico') || str_contains($name, 'pack basico') || str_contains($name, 'pack esencial') || str_contains($name, 'esencial') || str_contains($name, 'pack clásico') || str_contains($name, 'pack clasico')) {
                $this->quote_services['pack_basic']['selected'] = true;
                $this->quote_services['pack_basic']['price'] = (float)$item->price;
                $this->selected_pack_type = 'basic';
            } elseif (str_contains($name, 'pack medio') || str_contains($name, 'pack signature') || str_contains($name, 'signature') || str_contains($name, 'premium fiesta')) {
                $this->quote_services['pack_medium']['selected'] = true;
                $this->quote_services['pack_medium']['price'] = (float)$item->price;
                $this->selected_pack_type = 'medium';
            } elseif (str_contains($name, 'pack premium') || str_contains($name, 'pack deluxe') || str_contains($name, 'deluxe show') || str_contains($name, 'deluxe') || str_contains($name, 'pack festival')) {
                $this->quote_services['pack_premium']['selected'] = true;
                $this->quote_services['pack_premium']['price'] = (float)$item->price;
                $this->selected_pack_type = 'premium';
            } elseif (
                (str_contains($name, 'ceremonia') && !str_contains($name, 'foto')) ||
                (str_contains($name, 'sonorizaci') && !str_contains($name, 'banquete') && !str_contains($name, 'regalos') && !str_contains($name, 'comida') && !str_contains($name, 'cena')) ||
                str_contains($name, 'recibimiento') ||
                str_contains($name, 'acto protocolario') ||
                str_contains($name, 'escenario')
            ) {
                $this->quote_services['ceremony']['selected'] = true;
                $this->quote_services['ceremony']['name'] = $item->service_name ?: ($item->concept ?: $this->quote_services['ceremony']['name']);
                $this->quote_services['ceremony']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['ceremony']['price'] = (float)$item->price;
                if (!empty($item->description)) {
                    $this->quote_services['ceremony']['desc'] = $item->description;
                }
            } elseif (str_contains($name, 'cóctel') || str_contains($name, 'coctel') || str_contains($name, 'aperitivo') || str_contains($name, 'networking')) {
                $this->quote_services['cocktail']['selected'] = true;
                $this->quote_services['cocktail']['name'] = $item->service_name ?: ($item->concept ?: $this->quote_services['cocktail']['name']);
                $this->quote_services['cocktail']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['cocktail']['price'] = (float)$item->price;
                if (!empty($item->description)) {
                    $this->quote_services['cocktail']['desc'] = $item->description;
                }
            } elseif (str_contains($name, 'banquete') || str_contains($name, 'regalos') || str_contains($name, 'ambientación comida') || str_contains($name, 'ambientacion comida') || (str_contains($name, 'restaurante') && !str_contains($name, 'foto'))) {
                $this->quote_services['restaurant']['selected'] = true;
                $this->quote_services['restaurant']['name'] = $item->service_name ?: ($item->concept ?: $this->quote_services['restaurant']['name']);
                $this->quote_services['restaurant']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['restaurant']['price'] = (float)$item->price;
                if (!empty($item->description)) {
                    $this->quote_services['restaurant']['desc'] = $item->description;
                }
            } elseif (str_contains($name, 'magia infantil') || (str_contains($name, 'magia') && str_contains($name, 'infantil') && !str_contains($name, 'mixt') && !str_contains($name, 'adulto'))) {
                $this->quote_services['magic_kids']['selected'] = true;
                $this->quote_services['magic_kids']['name'] = $item->service_name ?: ($item->concept ?: $this->quote_services['magic_kids']['name']);
                $this->quote_services['magic_kids']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['magic_kids']['price'] = (float)$item->price;
                if (!empty($item->description)) {
                    $this->quote_services['magic_kids']['desc'] = $item->description;
                }
            } elseif (str_contains($name, 'magia para adultos') || str_contains($name, 'magia adultos') || (str_contains($name, 'magia') && str_contains($name, 'adulto') && !str_contains($name, 'infantil') && !str_contains($name, 'mixt'))) {
                $this->quote_services['magic_adults']['selected'] = true;
                $this->quote_services['magic_adults']['name'] = $item->service_name ?: ($item->concept ?: $this->quote_services['magic_adults']['name']);
                $this->quote_services['magic_adults']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['magic_adults']['price'] = (float)$item->price;
                if (!empty($item->description)) {
                    $this->quote_services['magic_adults']['desc'] = $item->description;
                }
            } elseif (str_contains($name, 'magia mixt') || str_contains($name, 'magia familiar') || (str_contains($name, 'magia') && (str_contains($name, 'mixt') || str_contains($name, 'familiar') || (str_contains($name, 'infantil') && str_contains($name, 'adulto'))))) {
                $this->quote_services['magic_mixed']['selected'] = true;
                $this->quote_services['magic_mixed']['name'] = $item->service_name ?: ($item->concept ?: $this->quote_services['magic_mixed']['name']);
                $this->quote_services['magic_mixed']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['magic_mixed']['price'] = (float)$item->price;
                if (!empty($item->description)) {
                    $this->quote_services['magic_mixed']['desc'] = $item->description;
                }
            } elseif (str_contains($name, 'magia de cerca') || str_contains($name, 'close-up') || str_contains($name, 'close up')) {
                $this->quote_services['magic_closeup']['selected'] = true;
                $this->quote_services['magic_closeup']['name'] = $item->service_name ?: ($item->concept ?: $this->quote_services['magic_closeup']['name']);
                $this->quote_services['magic_closeup']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['magic_closeup']['price'] = (float)$item->price;
                if (!empty($item->description)) {
                    $this->quote_services['magic_closeup']['desc'] = $item->description;
                }
            } elseif (str_contains($name, 'después de la magia') || str_contains($name, 'despues de la magia') || str_contains($name, 'baile dj') || str_contains($name, 'dj baile') || (str_contains($name, 'dj') && !str_contains($name, 'pack'))) {
                $this->quote_services['dj_custom']['selected'] = true;
                $this->quote_services['dj_custom']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['dj_custom']['price'] = (float)$item->price;
                if (!empty($item->description)) {
                    $this->quote_services['dj_custom']['desc'] = $item->description;
                }
            } elseif (str_contains($name, 'hora extra') || str_contains($name, 'horas extra') || str_contains($name, 'horas adicionales') || str_contains($name, 'hora adicional')) {
                $this->quote_services['extra_hours']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['extra_hours']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'pack completo foto') || str_contains($name, 'completo fotografía') || str_contains($name, 'completo fotografia')) {
                $this->quote_services['photo_full_pack']['selected'] = true;
                $this->quote_services['photo_full_pack']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'fotografía: ceremonia') || str_contains($name, 'fotografia: ceremonia')) {
                $this->quote_services['photo_ceremony']['selected'] = true;
                $this->quote_services['photo_ceremony']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'fotografía: restaurante') || str_contains($name, 'fotografia: restaurante') || str_contains($name, 'fotografía: banquete')) {
                $this->quote_services['photo_restaurant']['selected'] = true;
                $this->quote_services['photo_restaurant']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'fotografía: fiesta') || str_contains($name, 'fotografia: fiesta') || str_contains($name, 'fotografía: baile')) {
                $this->quote_services['photo_party']['selected'] = true;
                $this->quote_services['photo_party']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'álbum digital') || str_contains($name, 'album digital')) {
                $this->quote_services['photo_album']['selected'] = true;
                $this->quote_services['photo_album']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'karaoke')) {
                $this->quote_services['karaoke']['selected'] = true;
                $this->quote_services['karaoke']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['karaoke']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'pack dúo') || str_contains($name, 'pack duo') || (str_contains($name, 'fotomat') && str_contains($name, '360'))) {
                $this->quote_services['pack_duo_360']['selected'] = true;
                $this->quote_services['pack_duo_360']['quantity'] = (int)($item->quantity ?: 1);
                $hours = str_contains($name, '2 hora') || str_contains($name, '2h') || ((float)$item->price > 0 && (float)$item->price <= $pbConfig['pack_duo_2h']) ? 2 : 3;
                $this->quote_services['pack_duo_360']['hours'] = $hours;
                $this->quote_services['pack_duo_360']['price'] = (float)$item->price > 0 ? (float)$item->price : ($hours === 2 ? $pbConfig['pack_duo_2h'] : $pbConfig['pack_duo_3h']);
                if (!empty($item->description) && !str_contains(mb_strtolower($item->description), 'consultar')) {
                    $cleanDesc = trim(explode('| 💡 Opción 3 Horas', $item->description)[0]);
                    $this->quote_services['pack_duo_360']['desc'] = $cleanDesc;
                }
            } elseif (str_contains($name, 'fotomatón') || str_contains($name, 'fotomaton') || str_contains($name, 'photobooth')) {
                $this->quote_services['photobooth']['selected'] = true;
                $this->quote_services['photobooth']['quantity'] = (int)($item->quantity ?: 1);
                $hours = (str_contains($name, '3 hora') || str_contains($name, '3h') || ((float)$item->price >= $pbConfig['photobooth_3h'])) ? 3 : 2;
                $this->quote_services['photobooth']['hours'] = $hours;
                $this->quote_services['photobooth']['price'] = (float)$item->price > 0 ? (float)$item->price : ($hours === 3 ? $pbConfig['photobooth_3h'] : $pbConfig['photobooth_2h']);
                if (!empty($item->description) && !str_contains(mb_strtolower($item->description), 'consultar')) {
                    $cleanDesc = trim(explode('| 💡 Opción 3 Horas', $item->description)[0]);
                    $this->quote_services['photobooth']['desc'] = $cleanDesc;
                }
            } elseif (str_contains($name, '360') || str_contains($name, 'plataforma')) {
                $this->quote_services['video_360']['selected'] = true;
                $this->quote_services['video_360']['quantity'] = (int)($item->quantity ?: 1);
                $hours = (str_contains($name, '3 hora') || str_contains($name, '3h') || ((float)$item->price >= $pbConfig['video_360_3h'])) ? 3 : 2;
                $this->quote_services['video_360']['hours'] = $hours;
                $this->quote_services['video_360']['price'] = (float)$item->price > 0 ? (float)$item->price : ($hours === 3 ? $pbConfig['video_360_3h'] : $pbConfig['video_360_2h']);
                if (!empty($item->description) && !str_contains(mb_strtolower($item->description), 'consultar')) {
                    $cleanDesc = trim(explode('| 💡 Opción 3 Horas', $item->description)[0]);
                    $this->quote_services['video_360']['desc'] = $cleanDesc;
                }
            } elseif (str_contains($name, 'chocolate') || str_contains($name, 'fuente de chocolate')) {
                $this->quote_services['chocolate_fountain']['selected'] = true;
                $this->quote_services['chocolate_fountain']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['chocolate_fountain']['consult'] = false;
                $fountains = str_contains($name, '2 fuente') || ((float)$item->price == 490 || (float)$item->price == 590) ? 2 : 1;
                $hours = str_contains($name, '3 hora') || str_contains($name, '3h') || ((float)$item->price == 520 || (float)$item->price == 590) ? 3 : 2;
                $this->quote_services['chocolate_fountain']['fountains'] = $fountains;
                $this->quote_services['chocolate_fountain']['hours'] = $hours;
                $this->quote_services['chocolate_fountain']['price'] = (float)$item->price > 0 ? (float)$item->price : ($pbConfig["chocolate_fountain_{$fountains}_{$hours}h"] ?? 420);
                if (!empty($item->description) && !str_contains(mb_strtolower($item->description), 'consultar')) {
                    $this->quote_services['chocolate_fountain']['desc'] = $item->description;
                }
            } elseif (str_contains($name, 'solo chuches') || str_contains($name, 'mesa de chuches') || str_contains($name, 'candy bar')) {
                $this->quote_services['candy_bar']['selected'] = true;
                $this->quote_services['candy_bar']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['candy_bar']['consult'] = false;
                $this->quote_services['candy_bar']['price'] = (float)$item->price > 0 ? (float)$item->price : ($pbConfig['candy_bar_price'] ?? 420);
                if (!empty($item->description) && !str_contains(mb_strtolower($item->description), 'consultar')) {
                    $this->quote_services['candy_bar']['desc'] = $item->description;
                }
            } elseif (str_contains($name, 'carrito de gofres') || str_contains($name, 'gofres')) {
                $this->quote_services['waffle_cart']['selected'] = true;
                $this->quote_services['waffle_cart']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['waffle_cart']['consult'] = false;
                $hours = str_contains($name, '3 hora') || str_contains($name, '3h') || str_contains($name, '100 gofres') || ((float)$item->price >= 500) ? 3 : 2;
                $this->quote_services['waffle_cart']['hours'] = $hours;
                $this->quote_services['waffle_cart']['price'] = (float)$item->price > 0 ? (float)$item->price : ($hours === 3 ? ($pbConfig['waffle_cart_3h'] ?? 550) : ($pbConfig['waffle_cart_2h'] ?? 420));
                if (!empty($item->description) && !str_contains(mb_strtolower($item->description), 'consultar')) {
                    $this->quote_services['waffle_cart']['desc'] = $item->description;
                }
            } elseif (str_contains($name, 'máquina de humo') || str_contains($name, 'maquina de humo') || str_contains($name, 'humo')) {
                $this->quote_services['smoke_machine']['selected'] = true;
                $this->quote_services['smoke_machine']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['smoke_machine']['price'] = (float)$item->price;
                $this->quote_services['smoke_machine']['included'] = ((float)$item->price == 0 || str_contains($name, 'incluido'));
            } elseif (str_contains($name, 'fuego frío') || str_contains($name, 'fuego frio') || str_contains($name, 'chispas frías') || str_contains($name, 'chispas frias') || str_contains($name, 'cold sparks')) {
                if (!empty($this->quote_services['cold_sparks']['selected'])) {
                    // Si ya se cargó una línea (ej. 2 incluidas), y ahora viene una línea de máquinas adicionales
                    $this->quote_services['cold_sparks']['quantity'] += (int)($item->quantity ?: 1);
                    if ((float)$item->price > 0) {
                        $this->quote_services['cold_sparks']['price'] = (float)$item->price;
                    }
                } else {
                    $this->quote_services['cold_sparks']['selected'] = true;
                    $this->quote_services['cold_sparks']['quantity'] = (int)($item->quantity ?: 1);
                    if ((float)$item->price > 0) {
                        $this->quote_services['cold_sparks']['price'] = (float)$item->price;
                    } else {
                        $this->quote_services['cold_sparks']['price'] = (float)\App\Models\Setting::get('price_effects_sparks', 60);
                    }
                    $this->quote_services['cold_sparks']['included'] = ((float)$item->price == 0 || str_contains($name, 'incluido'));
                }
            } elseif (str_contains($name, 'descuento especial pack') || str_contains($name, 'descuento pack')) {
                // Descuento automático de pack cóctel+banquete, no agregarlo como extra personalizado
                continue;
            } else {
                $rawName = $item->service_name ?: $item->concept ?: '';
                $isConsult = str_contains(mb_strtolower($rawName), 'consultar') || str_contains(mb_strtolower($item->description ?: ''), 'consultar');
                $cleanName = trim(str_ireplace('(a consultar)', '', $rawName));
                $loadedCustomExtras[] = [
                    'name' => $cleanName,
                    'price' => $isConsult ? 0 : (float)$item->price,
                    'desc' => $item->description ?: '',
                    'consult' => $isConsult,
                ];
            }
        }

        if (!empty($loadedCustomExtras)) {
            $this->custom_extras = $loadedCustomExtras;
        } else {
            $this->custom_extras = [
                ['name' => '', 'price' => 0, 'desc' => '', 'consult' => false]
            ];
        }

        if ($flash) {
            session()->flash('quote_message', 'Propuesta #' . $quote->id . ' cargada en el configurador. Puedes realizar cambios y pulsar "Guardar Cambios" o "Guardar como Nueva Versión".');
        }
    }

    public function cancelQuoteEditing()
    {
        $this->editing_quote_id = null;
        $this->selectPack('clear');
        session()->flash('quote_message', 'Edición cancelada.');
    }

    public function deleteQuote($quoteId)
    {
        $quote = \App\Models\Quote::findOrFail($quoteId);
        $quote->items()->delete();
        $quote->delete();

        if ($this->editing_quote_id === $quoteId) {
            $this->editing_quote_id = null;
        }

        $this->event->load('quotes.items');
        session()->flash('quote_message', 'Propuesta comercial eliminada correctamente.');
    }

    public function sendQuoteByEmail($quoteId)
    {
        $quote = $this->event->quotes()->with('items')->find($quoteId);
        if (!$quote) {
            session()->flash('quote_error', 'No se encontró la propuesta comercial.');
            return;
        }

        $client = $this->event->client;
        if (!$client || empty($client->email) || str_ends_with($client->email, '@eventosmusicales.local')) {
            session()->flash('quote_error', 'El cliente no tiene un email válido asignado. Edita los datos del cliente para introducir su correo antes de enviar.');
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($client->email)
                ->send(new \App\Mail\QuoteProposalClientMail($this->event, $quote));

            session()->flash('quote_message', '✉️ ¡Propuesta #' . $quote->id . ' enviada por correo con éxito a ' . $client->email . ' con el PDF oficial adjunto!');
        } catch (\Throwable $e) {
            session()->flash('quote_error', 'Error al enviar el email: ' . $e->getMessage());
        }
    }

    public function createContract()
    {
        $token = \Illuminate\Support\Str::random(32);
        $contract = $this->event->contracts()->create([
            'status' => 'pending',
            'token' => $token,
        ]);
        $this->event->load('contracts');
        session()->flash('contract_message', "Contrato '{$contract->contract_number}' generado con enlace para firma online.");
    }

    public function startEditContractNumber($contractId)
    {
        $contract = $this->event->contracts()->find($contractId);
        if ($contract && $contract->event_id == $this->event->id) {
            $this->editing_contract_id = $contract->id;
            $this->editing_contract_number = $contract->contract_number;
        }
    }

    public function cancelEditContractNumber()
    {
        $this->editing_contract_id = null;
        $this->editing_contract_number = '';
    }

    public function saveContractNumber($contractId)
    {
        $contract = $this->event->contracts()->find($contractId);
        if ($contract && $contract->event_id == $this->event->id) {
            $val = trim((string)$this->editing_contract_number);
            $contract->update([
                'contract_number' => !empty($val) ? $val : null,
            ]);
            $this->editing_contract_id = null;
            $this->editing_contract_number = '';
            $this->event->load('contracts');
            session()->flash('contract_message', 'Nombre / Código del contrato actualizado correctamente.');
        }
    }

    public function deleteContract($contractId)
    {
        $contract = $this->event->contracts()->find($contractId);
        if ($contract && $contract->event_id == $this->event->id) {
            $contract->delete();
            $this->event->load('contracts');
            session()->flash('contract_message', 'Contrato eliminado correctamente.');
        }
    }

    public function signContract($contractId)
    {
        $contract = $this->event->contracts()->find($contractId);
        if ($contract) {
            $contract->update([
                'status' => 'signed',
                'signed_at' => now(),
            ]);
            $this->event->load('contracts');
        }
    }

    public function openVenueModal()
    {
        $this->venue_contact_name = $this->event->venue_contact_name;
        $this->venue_contact_phone = $this->event->venue_contact_phone;
        $this->venue_notes = $this->event->venue_notes;
        $this->showVenueModal = true;
    }

    public function closeVenueModal()
    {
        $this->showVenueModal = false;
    }

    public function saveVenueContact()
    {
        $this->event->update([
            'venue_contact_name' => $this->venue_contact_name,
            'venue_contact_phone' => $this->venue_contact_phone,
            'venue_notes' => $this->venue_notes,
        ]);

        $this->showVenueModal = false;
        session()->flash('venue_message', 'Contacto de la finca/bodega guardado correctamente.');
    }

    public function lookupVenuePhoneAutomatically()
    {
        $location = trim((string)$this->event->location);
        if (!\App\Services\VenueLookupService::isSearchableLocation($location)) {
            session()->flash('message', '⚠️ Indica primero el nombre del restaurante, bodega o finca en el lugar del evento.');
            return;
        }

        $found = \App\Services\VenueLookupService::autoPopulateEventVenuePhone($this->event, true);
        if ($found && !empty($found['phone'])) {
            $this->event->refresh();
            $this->venue_contact_phone = $this->event->venue_contact_phone;
            $this->venue_contact_name = $this->event->venue_contact_name;
            $source = $found['source'] ?? 'Web';
            session()->flash('message', "✅ Teléfono de «{$location}» localizado automáticamente ({$found['phone']}) vía {$source}.");
        } else {
            $this->openVenueModal();
            session()->flash('venue_lookup_error', "No se pudo localizar automáticamente un teléfono público para «{$location}». Puedes introducirlo manualmente aquí.");
        }
    }

    public function openEditEventModal()
    {
        $this->event_edit_name = $this->event->name;
        $this->event_edit_date = $this->event->event_date ? $this->event->event_date->format('Y-m-d') : null;
        $this->event_edit_location = $this->event->location;
        $this->event_edit_type = $this->event->event_type_clean ?: 'boda';
        $this->event_edit_brand = $this->event->brand_clean ?: 'nunez_and_son';
        $this->event_edit_status = $this->event->status ?: 'draft';
        $this->event_edit_notes = $this->event->notes;
        $this->showEditEventModal = true;
    }

    public function closeEditEventModal()
    {
        $this->showEditEventModal = false;
    }

    public function updatedEventEditBrand($value)
    {
        if ($this->event_edit_type === 'video' && !\App\Models\Setting::isVideoServiceEnabled($value)) {
            $this->event_edit_type = 'boda';
        }
    }

    public function saveEventDetails()
    {
        if ($this->event_edit_type === 'video' && !\App\Models\Setting::isVideoServiceEnabled($this->event_edit_brand)) {
            $this->event_edit_type = 'boda';
        }

        $this->validate([
            'event_edit_name' => 'required|string|max:255',
            'event_edit_date' => 'required|date',
            'event_edit_location' => 'required|string|max:255',
            'event_edit_type' => 'required|string|in:boda,video,empresa,cumpleanos,comunion,otro',
            'event_edit_brand' => 'required|string|in:nunez_and_son,javnx,mago_leugim',
            'event_edit_status' => 'required|in:draft,no_response,on_hold,confirmed,completed,rejected,cancelled',
            'event_edit_notes' => 'nullable|string',
        ]);

        $previousLocation = trim((string)$this->event->location);
        $newLocation = trim((string)$this->event_edit_location);

        $updateData = [
            'name' => $this->event_edit_name,
            'event_date' => $this->event_edit_date,
            'location' => $this->event_edit_location,
            'event_type' => $this->event_edit_type,
            'brand' => $this->event_edit_brand,
            'status' => $this->event_edit_status,
            'notes' => $this->event_edit_notes,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('events', 'no_response_at')) {
            if (in_array($this->event_edit_status, ['no_response', 'on_hold'], true)) {
                $updateData['no_response_at'] = ($this->event->status === $this->event_edit_status && !empty($this->event->no_response_at))
                    ? $this->event->no_response_at
                    : now();
            } else {
                $updateData['no_response_at'] = null;
            }
        }

        try {
            $this->event->update($updateData);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Data truncated') || str_contains($e->getMessage(), '1265')) {
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `events` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'draft'");
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('events', 'no_response_at')) {
                        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `events` ADD COLUMN `no_response_at` TIMESTAMP NULL AFTER `status`");
                    }
                    if (in_array($this->event_edit_status, ['no_response', 'on_hold'], true)) {
                        $updateData['no_response_at'] = now();
                    }
                    $this->event->update($updateData);
                } catch (\Throwable $innerEx) {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }

        $this->event->refresh();

        if ($this->quote_deposit_type === 'percentage') {
            $this->quote_deposit_percentage = \App\Models\Quote::calculateGradualDepositPercentage($this->event->event_date);
        }

        // Si cambió el restaurante/finca o aún no tenía teléfono, buscar su teléfono automáticamente en la web
        if (mb_strtolower($previousLocation) !== mb_strtolower($newLocation) || empty(trim((string)$this->event->venue_contact_phone))) {
            try {
                $forceLookup = mb_strtolower($previousLocation) !== mb_strtolower($newLocation);
                $foundVenue = \App\Services\VenueLookupService::autoPopulateEventVenuePhone($this->event, $forceLookup);
                if ($foundVenue) {
                    $this->event->refresh();
                    $this->venue_contact_phone = $this->event->venue_contact_phone;
                    $this->venue_contact_name = $this->event->venue_contact_name;
                }
            } catch (\Throwable $e) {}
        }

        $this->showEditEventModal = false;
        session()->flash('message', '¡Detalles y estado del evento actualizados correctamente!');
    }

    public function openScheduleModal()
    {
        $this->setup_date = $this->event->setup_date ? \Carbon\Carbon::parse($this->event->setup_date)->format('Y-m-d') : null;
        $this->dance_start_time = $this->event->dance_start_time;
        $this->dance_duration_hours = $this->event->dance_duration_hours ?: $this->event->suggested_dance_hours;
        $this->dance_end_time = $this->event->dance_end_time ?: $this->event->calculated_dance_end_time;
        $this->max_end_time = $this->event->max_end_time;
        $this->start_time = $this->event->start_time;
        $this->ceremony_time = $this->event->ceremony_time;
        $this->cocktail_time = $this->event->cocktail_time;
        $this->banquet_time = $this->event->banquet_time;
        $this->schedule_notes = $this->event->schedule_notes;
        $this->showScheduleModal = true;
    }

    public function closeScheduleModal()
    {
        $this->showScheduleModal = false;
    }

    public function setPresetSetupDate($type)
    {
        if ($type === 'same_day') {
            $this->setup_date = $this->event->event_date ? \Carbon\Carbon::parse($this->event->event_date)->format('Y-m-d') : null;
        } elseif ($type === 'day_before') {
            $this->setup_date = $this->event->event_date ? \Carbon\Carbon::parse($this->event->event_date)->subDay()->format('Y-m-d') : null;
        } elseif ($type === 'clear') {
            $this->setup_date = null;
        }
    }

    public function updatedDanceStartTime($value)
    {
        $this->recalculateDanceEndTime();
    }

    public function updatedDanceDurationHours($value)
    {
        $this->recalculateDanceEndTime();
    }

    public function recalculateDanceEndTime()
    {
        if (!empty($this->dance_start_time) && !empty($this->dance_duration_hours)) {
            try {
                $start = \Carbon\Carbon::createFromFormat('H:i', substr($this->dance_start_time, 0, 5));
                $minutes = (int)((float)$this->dance_duration_hours * 60);
                $this->dance_end_time = $start->addMinutes($minutes)->format('H:i');
            } catch (\Throwable $e) {}
        }
    }

    public function setPresetDanceDuration($hours)
    {
        $this->dance_duration_hours = (float)$hours;
        $this->recalculateDanceEndTime();
    }

    public function saveSchedule()
    {
        $this->validate([
            'setup_date' => 'nullable|date',
            'dance_start_time' => 'nullable|string|max:20',
            'dance_duration_hours' => 'nullable|numeric|min:0.5|max:24',
            'dance_end_time' => 'nullable|string|max:20',
            'max_end_time' => 'nullable|string|max:20',
            'start_time' => 'nullable|string|max:20',
            'ceremony_time' => 'nullable|string|max:20',
            'cocktail_time' => 'nullable|string|max:20',
            'banquet_time' => 'nullable|string|max:20',
            'schedule_notes' => 'nullable|string',
        ]);

        if (!empty($this->dance_start_time) && !empty($this->dance_duration_hours) && empty($this->dance_end_time)) {
            $this->recalculateDanceEndTime();
        }

        try {
            // Si las columnas aún no existen, intentamos aplicar migraciones automáticamente
            if (!\Illuminate\Support\Facades\Schema::hasColumn('events', 'setup_date') || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'dance_start_time') || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'max_end_time')) {
                try {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                } catch (\Throwable $migEx) {
                    // Si no tiene permisos de Artisan directo, continuará con los campos disponibles
                }
            }

            $updateData = [];
            $possibleFields = [
                'setup_date' => $this->setup_date ?: null,
                'dance_start_time' => $this->dance_start_time ?: null,
                'dance_duration_hours' => $this->dance_duration_hours ? (float)$this->dance_duration_hours : null,
                'dance_end_time' => $this->dance_end_time ?: null,
                'max_end_time' => $this->max_end_time ?: null,
                'start_time' => $this->start_time ?: null,
                'ceremony_time' => $this->ceremony_time ?: null,
                'cocktail_time' => $this->cocktail_time ?: null,
                'banquet_time' => $this->banquet_time ?: null,
                'schedule_notes' => $this->schedule_notes ?: null,
            ];

            foreach ($possibleFields as $col => $val) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('events', $col)) {
                    $updateData[$col] = $val;
                }
            }

            if (!empty($updateData)) {
                $this->event->update($updateData);
                $this->event->refresh();
                $this->showScheduleModal = false;
                session()->flash('message', '¡Horarios del evento y del baile actualizados correctamente!');
            } else {
                $this->showScheduleModal = false;
                session()->flash('error', '⚠️ Las columnas de horarios aún no existen en la base de datos. Por favor ejecuta "php artisan migrate" en tu servidor.');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error saving schedule: ' . $e->getMessage());
            $this->showScheduleModal = false;
            session()->flash('error', 'No se pudieron guardar los horarios: ' . $e->getMessage() . '. Si faltan columnas, ejecuta "php artisan migrate".');
        }
    }

    public function exportPlaylistM3u()
    {
        $requests = $this->event->musicRequests()->where('status', '!=', 'rejected')->get();
        
        $m3uContent = "#EXTM3U\n";
        $m3uContent .= "#PLAYLIST: " . $this->event->name . " - Playlist Completa\n\n";

        foreach ($requests as $req) {
            $moment = $req->moment ?: ucfirst($req->category);
            $title = $req->title;
            $artist = $req->artist ?: 'Desconocido';
            $cue = $req->cue_time ? " [CUE: {$req->cue_time}]" : "";
            
            $m3uContent .= "#EXTINF:-1,{$artist} - {$title} ({$moment}){$cue}\n";
            if ($req->audio_url) {
                $m3uContent .= $req->audio_url . "\n";
            } elseif ($req->youtube_url) {
                $m3uContent .= $req->youtube_url . "\n";
            } elseif ($req->spotify_url) {
                $m3uContent .= $req->spotify_url . "\n";
            } else {
                $m3uContent .= "{$artist} - {$title}.mp3\n";
            }
        }

        $fileName = 'Playlist_' . \Illuminate\Support\Str::slug($this->event->name) . '.m3u';

        return response()->streamDownload(function () use ($m3uContent) {
            echo $m3uContent;
        }, $fileName, [
            'Content-Type' => 'audio/x-mpegurl',
        ]);
    }

    public function openImportModal()
    {
        $this->importRawText = '';
        $this->importFile = null;
        $this->parsedImportTracks = [];
        $this->parsedImportNotes = [];
        $this->showImportModal = true;
    }

    public function updatedImportFile()
    {
        $this->validate([
            'importFile' => 'nullable|file|mimes:pdf,txt,csv|max:10240',
        ], [
            'importFile.mimes' => 'El archivo debe ser un documento PDF, TXT o CSV.',
            'importFile.max' => 'El tamaño máximo permitido es de 10 MB.',
        ]);

        try {
            if ($this->importFile) {
                $path = $this->importFile->getRealPath() ?: $this->importFile->path();
                $mime = $this->importFile->getMimeType();
                $originalName = strtolower($this->importFile->getClientOriginalName());

                $extractedText = '';
                if (str_ends_with($originalName, '.pdf') || $mime === 'application/pdf') {
                    $extractedText = \App\Services\MusicListImportService::extractTextFromPdf($path);
                } else {
                    $extractedText = @file_get_contents($path) ?: '';
                }

                if (!empty(trim($extractedText))) {
                    $this->importRawText = $extractedText;
                    $this->parseImportInput();
                } else {
                    session()->flash('import_error', 'No se ha detectado texto seleccionable en el PDF. Puedes copiar el texto y pegarlo directamente en el recuadro inferior.');
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Error procesando archivo subido en importador: " . $e->getMessage());
            session()->flash('import_error', 'Ocurrió un problema leyendo el documento. Puedes pegar el texto directamente en el recuadro inferior.');
        }
    }

    public function parseImportInput()
    {
        if (empty(trim($this->importRawText))) {
            $this->parsedImportTracks = [];
            $this->parsedImportNotes = [];
            return;
        }

        $result = \App\Services\MusicListImportService::parseText($this->importRawText);
        $this->parsedImportTracks = array_map(function($t) {
            $t['category'] = $this->importDefaultCategory;
            $t['moment'] = $this->importDefaultMoment;
            $t['selected'] = true;
            return $t;
        }, $result['tracks'] ?? []);
        $this->parsedImportNotes = $result['notes'] ?? [];
    }

    public function removeParsedTrack($index)
    {
        if (isset($this->parsedImportTracks[$index])) {
            unset($this->parsedImportTracks[$index]);
            $this->parsedImportTracks = array_values($this->parsedImportTracks);
        }
    }

    public function executeImport()
    {
        $selectedTracks = array_filter($this->parsedImportTracks, function($t) {
            return !empty($t['selected']);
        });

        if (empty($selectedTracks)) {
            session()->flash('import_error', 'No hay canciones seleccionadas para importar.');
            return;
        }

        $result = \App\Services\MusicListImportService::importTracksToEvent($this->event, $selectedTracks);
        $this->event->load('musicRequests');
        $this->showImportModal = false;
        $this->parsedImportTracks = [];
        $this->importRawText = '';
        $this->importFile = null;

        $msg = "✅ ¡Se han importado {$result['imported']} canciones con carátulas y enlaces a la escaleta!";
        if (!empty($result['duplicates']) && $result['duplicates'] > 0) {
            $msg .= " (ℹ️ {$result['duplicates']} canciones ya existían en el evento y se han omitido para evitar duplicados).";
        }

        session()->flash('music_message', $msg);
    }

    public function createSpotifyPlaylist()
    {
        $res = \App\Services\SpotifyService::createPlaylistForEvent($this->event);
        if ($res['success']) {
            session()->flash('music_message', $res['message']);
            if (!empty($res['url'])) {
                $this->dispatch('open-url', url: $res['url']);
            }
        } else {
            session()->flash('music_error', $res['message']);
        }
    }

    // ==========================================
    // GESTIÓN DE CITAS Y REUNIONES CON CLIENTES
    // ==========================================
    public function openCreateMeetingModal()
    {
        $this->resetValidation();
        $this->editing_meeting_id = null;
        $this->meeting_title = 'Reunión de Escaleta Musical';
        $this->meeting_type = 'escaleta_musica';
        $this->meeting_date = now()->addDays(7)->format('Y-m-d');
        $this->meeting_time = '18:00';
        $this->meeting_duration_minutes = 45;
        $this->meeting_location_type = 'in_person';
        $this->meeting_location = $this->event->location ?: '';
        $this->meeting_video_call_url = '';
        $this->meeting_staff_id = $this->event->dj_id ?: auth()->id();
        $this->meeting_status = 'scheduled';
        $this->meeting_summary = '';
        $this->meeting_notes = '';
        $this->showMeetingModal = true;
    }

    public function editMeeting($meetingId)
    {
        $this->resetValidation();
        $meeting = $this->event->meetings()->find($meetingId);
        if (!$meeting) return;

        $this->editing_meeting_id = $meeting->id;
        $this->meeting_title = $meeting->title;
        $this->meeting_type = $meeting->meeting_type;
        $this->meeting_date = $meeting->meeting_date ? $meeting->meeting_date->format('Y-m-d') : now()->format('Y-m-d');
        $this->meeting_time = $meeting->meeting_date ? $meeting->meeting_date->format('H:i') : '18:00';
        $this->meeting_duration_minutes = $meeting->duration_minutes ?: 45;
        $this->meeting_location_type = $meeting->location_type ?: 'in_person';
        $this->meeting_location = $meeting->location ?: '';
        $this->meeting_video_call_url = $meeting->video_call_url ?: '';
        $this->meeting_staff_id = $meeting->user_id;
        $this->meeting_status = $meeting->status ?: 'scheduled';
        $this->meeting_summary = $meeting->summary ?: '';
        $this->meeting_notes = $meeting->notes ?: '';
        $this->showMeetingModal = true;
    }

    public function saveMeeting()
    {
        $this->validate([
            'meeting_title' => 'required|string|max:255',
            'meeting_type' => 'required|string',
            'meeting_date' => 'required|date',
            'meeting_time' => 'required|string',
            'meeting_duration_minutes' => 'required|integer|min:10|max:480',
            'meeting_location_type' => 'required|string',
            'meeting_location' => 'nullable|string|max:255',
            'meeting_video_call_url' => 'nullable|string|max:255',
            'meeting_staff_id' => 'nullable|exists:users,id',
            'meeting_status' => 'required|string',
            'meeting_summary' => 'nullable|string',
            'meeting_notes' => 'nullable|string',
        ]);

        $dateTime = \Carbon\Carbon::parse($this->meeting_date . ' ' . $this->meeting_time);

        $data = [
            'title' => $this->meeting_title,
            'meeting_type' => $this->meeting_type,
            'meeting_date' => $dateTime,
            'duration_minutes' => $this->meeting_duration_minutes,
            'location_type' => $this->meeting_location_type,
            'location' => $this->meeting_location ?: null,
            'video_call_url' => $this->meeting_video_call_url ?: null,
            'user_id' => $this->meeting_staff_id ?: null,
            'status' => $this->meeting_status,
            'summary' => $this->meeting_summary ?: null,
            'notes' => $this->meeting_notes ?: null,
        ];

        // Ensure table exists
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('client_meetings')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            }
        } catch (\Throwable $e) {}

        if ($this->editing_meeting_id) {
            $meeting = $this->event->meetings()->find($this->editing_meeting_id);
            if ($meeting) {
                $meeting->update($data);
                session()->flash('meeting_message', '¡Cita / Reunión actualizada correctamente!');
            }
        } else {
            $this->event->meetings()->create($data);
            session()->flash('meeting_message', '¡Nueva reunión programada con éxito!');
        }

        $this->event->load('meetings');
        $this->showMeetingModal = false;
    }

    public function deleteMeeting($meetingId)
    {
        $meeting = $this->event->meetings()->find($meetingId);
        if ($meeting) {
            $meeting->delete();
            $this->event->load('meetings');
            session()->flash('meeting_message', 'Reunión eliminada.');
        }
    }

    public function markMeetingCompleted($meetingId)
    {
        $meeting = $this->event->meetings()->find($meetingId);
        if ($meeting) {
            $meeting->update(['status' => 'completed']);
            $this->event->load('meetings');
            session()->flash('meeting_message', '✅ Reunión marcada como Realizada.');
        }
    }

    public function openDjHistoryModal()
    {
        $this->djHistoryFile = null;
        $this->djHistoryRawText = '';
        $this->djHistorySessionName = 'Sesión Baile & Fiesta';
        $this->djHistorySyncRequests = true;
        $this->showDjHistoryModal = true;
    }

    public function closeDjHistoryModal()
    {
        $this->showDjHistoryModal = false;
    }

    public function importDjHistory()
    {
        $content = '';
        $filename = '';

        if ($this->djHistoryFile) {
            $content = file_get_contents($this->djHistoryFile->getRealPath());
            $filename = $this->djHistoryFile->getClientOriginalName();
        } elseif (!empty(trim($this->djHistoryRawText))) {
            $content = $this->djHistoryRawText;
            $filename = 'tracklist_pegado.txt';
        } else {
            session()->flash('error', 'Selecciona un archivo (.csv, .m3u, .m3u8, .txt) o pega la lista de canciones de la sesión.');
            return;
        }

        $service = app(\App\Services\DjHistoryParserService::class);
        $result = $service->importHistory(
            $this->event,
            $content,
            $filename,
            $this->djHistorySessionName ?: 'Sesión Principal',
            $this->djHistorySyncRequests
        );

        if ($result['success']) {
            $this->showDjHistoryModal = false;
            $this->djHistoryFile = null;
            $this->djHistoryRawText = '';
            $this->event->load(['djHistories.matchedRequest', 'musicRequests']);
            session()->flash('message', "🎧 " . $result['message']);
        } else {
            session()->flash('error', "❌ " . $result['message']);
        }
    }

    public function deleteDjHistory()
    {
        $count = $this->event->djHistories()->count();
        $this->event->djHistories()->delete();
        $this->event->load(['djHistories', 'musicRequests']);
        session()->flash('message', "🗑️ Se ha eliminado el historial de sesión ({$count} canciones).");
    }

    public function deleteDjHistoryTrack(int $trackId)
    {
        $this->event->djHistories()->where('id', $trackId)->delete();
        $this->event->load(['djHistories', 'musicRequests']);
    }

    public function getGatewayActiveProperty(): bool
    {
        return \App\Services\EvolutionWhatsAppService::isEnabled();
    }

    public function refreshMusicRequests(): void
    {
        if ($this->event) {
            $this->event->load('musicRequests');
        }
    }

    // ==================== ÁLBUM DE FOTOS QR (FOTOSEVENTOSQR) ====================

    public function openPhotoQrModal(): void
    {
        $this->showPhotoQrModal = true;
        if ($this->event && \App\Services\FotosEventosQrService::isConfigured()) {
            $info = \App\Services\FotosEventosQrService::getEventAlbumInfo($this->event);
            if ($info !== null) {
                $this->refreshPhotoQrStatus(false);
            }
        }
    }

    public function closePhotoQrModal(): void
    {
        $this->showPhotoQrModal = false;
    }

    public function createOrSyncPhotoQrAlbum(): void
    {
        if (!$this->event) {
            return;
        }

        $result = \App\Services\FotosEventosQrService::createOrSyncEventAlbum($this->event, [
            'share_drive_album' => $this->photoQrShareDriveAlbum,
            'allow_videos' => $this->photoQrAllowVideos,
        ]);

        $this->event->refresh();
        $this->showPhotoQrModal = true;

        if (!empty($result['success'])) {
            $this->photoQrLiveStats = $result['event'] ?? null;
            $msg = $result['message'];
            if (!empty($result['warning'])) {
                $msg .= ' (' . $result['warning'] . ')';
            }
            session()->flash('photo_qr_message', $msg);
        } else {
            session()->flash('photo_qr_error', $result['message'] ?? 'No se pudo activar el Álbum de Fotos QR.');
        }
    }

    public function refreshPhotoQrStatus(bool $flash = true): void
    {
        if (!$this->event) {
            return;
        }

        $res = \App\Services\FotosEventosQrService::fetchAlbumStatus($this->event);
        $this->event->refresh();

        if (!empty($res['success']) && !empty($res['found']) && !empty($res['event'])) {
            $this->photoQrLiveStats = $res['event'];
            if ($flash) {
                session()->flash('photo_qr_message', 'Estado del álbum actualizado (' . (int)($res['event']['photos_count'] ?? 0) . ' fotos en Drive).');
            }
        } elseif ($flash && empty($res['success'])) {
            session()->flash('photo_qr_error', $res['message'] ?? 'No se pudo consultar el estado del álbum.');
        }
    }

    public function resetPhotoQrPosterCustomization(): void
    {
        if (!$this->event) {
            return;
        }

        \App\Services\QrPosterPdfService::resetCustomizationForEvent($this->event);
        $this->event->refresh();
        $this->photoQrPosterKey++;
        session()->flash('photo_qr_message', 'Diseño del cartel restaurado con los nombres y fecha actuales del evento.');
    }

    public function render()
    {
        $allEquipment = Equipment::orderBy('category')->orderBy('name')->get();
        $allDjs = User::whereIn('role', ['dj', 'admin'])->orderBy('name')->get();
        $allAssistants = User::whereIn('role', ['assistant', 'dj', 'admin'])->orderBy('name')->get();
        $allClients = User::where('role', 'client')->orderBy('name')->get();

        if ($this->event && $this->activeTab === 'music') {
            $this->event->load('musicRequests');
        }

        $filteredMusicRequests = $this->event ? $this->event->musicRequests : collect();
        if ($this->music_active_category !== 'all' && $filteredMusicRequests) {
            $filteredMusicRequests = $filteredMusicRequests->where('category', $this->music_active_category);
        }

        $allDjHistories = $this->event ? $this->event->djHistories : collect();
        if (!empty($this->djHistoryFilterQuery) && $allDjHistories) {
            $q = mb_strtolower(trim($this->djHistoryFilterQuery));
            $allDjHistories = $allDjHistories->filter(function ($item) use ($q) {
                return str_contains(mb_strtolower($item->title ?? ''), $q) ||
                       str_contains(mb_strtolower($item->artist ?? ''), $q) ||
                       str_contains(mb_strtolower($item->played_at_time ?? ''), $q);
            });
        }

        $allStaff = User::whereIn('role', ['admin', 'dj', 'assistant'])->orderBy('name')->get();

        return view('livewire.admin.event-show', [
            'allEquipment' => $allEquipment,
            'allDjs' => $allDjs,
            'allAssistants' => $allAssistants,
            'allStaff' => $allStaff,
            'allClients' => $allClients,
            'filteredMusicRequests' => $filteredMusicRequests,
            'allDjHistories' => $allDjHistories,
            'gatewayActive' => $this->gatewayActive,
        ])->layout('components.layouts.app', [
            'header' => 'Gestionar Evento: ' . ($this->event ? $this->event->name : 'Evento')
        ]);
    }
}
