<?php

namespace App\Livewire\Guest;

use Livewire\Component;
use App\Models\User;
use App\Models\Event;
use App\Models\Setting;
use Illuminate\Support\Str;

class QuoteCalculator extends Component
{
    // Contact Info
    public $client_name = '';
    public $client_email = '';
    public $client_phone = '';
    public $event_date = '';
    public $event_location = '';
    public $client_notes = '';
    public $event_type = 'boda'; // boda, empresa, cumpleanos, comunion, otro

    // Services selected by the user (No pricing in state to avoid leaking in wire:snapshot)
    public $services = [];
    public $custom_service_selected = false;
    public $custom_service_name = '';
    public $custom_service_description = '';
    public $is_submitted = false;

    public static function getEventTypes(): array
    {
        return [
            'boda' => [
                'name' => 'Boda / Enlace',
                'icon' => '💍',
                'badge' => 'Boda & Enlace',
                'hero_title' => 'Diseña la Música de tu',
                'hero_highlight' => 'Día Especial',
                'hero_subtitle' => 'Selecciona las fases de vuestra boda para recibir una propuesta personalizada con el mejor sonido e iluminación profesional.',
                'name_label' => 'Nombre de los Novios / Pareja *',
                'name_placeholder' => 'Ej: Carlos & Laura',
                'ceremony_name' => 'Ceremonia Civil / Religiosa',
                'ceremony_icon' => '💍',
                'ceremony_desc' => 'Sonorización profesional, microfonía inalámbrica y música de momentos clave (entradas, anillos, firmas y salida).',
                'cocktail_name' => 'Cóctel de Bienvenida',
                'cocktail_icon' => '🍸',
                'cocktail_desc' => 'Música ambiente seleccionada y equipo de sonido independiente para la zona del cóctel.',
                'restaurant_name' => 'Banquete & Momentos Especiales',
                'restaurant_icon' => '🍽️',
                'restaurant_desc' => 'Música ambiente en comedor, entrada de novios, entrega de regalos, ramo y corte de tarta.',
                'dj_name' => 'DJ Baile & Barra Libre',
                'dj_icon' => '💃',
                'dj_desc' => 'Sesión de DJ en directo, equipo de sonido de alta fidelidad, puente de luces y efectos.',
                'prefix' => 'Boda ',
            ],
            'empresa' => [
                'name' => 'Evento de Empresa / Corporativo',
                'icon' => '🏢',
                'badge' => 'Empresas & Gala',
                'hero_title' => 'Sonorización & Música para tu',
                'hero_highlight' => 'Evento Corporativo',
                'hero_subtitle' => 'Microfonía para ponencias, música ambiente para cóctel/cena y DJ para celebraciones de equipo o aniversarios.',
                'name_label' => 'Empresa / Persona de Contacto *',
                'name_placeholder' => 'Ej: Innova Tech S.L. / Roberto Pérez',
                'ceremony_name' => 'Sonorización Presentación / Ponencias',
                'ceremony_icon' => '🎙️',
                'ceremony_desc' => 'Microfonía inalámbrica de mano/solapa, atril, megafonía y música de apoyo corporativa.',
                'cocktail_name' => 'Cóctel & Networking',
                'cocktail_icon' => '🍸',
                'cocktail_desc' => 'Música ambiente sofisticada para la recepción y networking de los asistentes.',
                'restaurant_name' => 'Cena de Gala / Banquete Corporativo',
                'restaurant_icon' => '🍽️',
                'restaurant_desc' => 'Ambientación durante la cena, sonorización de discursos, entrega de premios o sorteos.',
                'dj_name' => 'DJ Fiesta de Empresa',
                'dj_icon' => '💃',
                'dj_desc' => 'Sesión musical festiva para celebrar los logros del equipo con iluminación y sonido profesional.',
                'prefix' => 'Evento Empresa: ',
            ],
            'cumpleanos' => [
                'name' => 'Cumpleaños / Fiesta Privada',
                'icon' => '🎂',
                'badge' => 'Cumpleaños & Fiesta',
                'hero_title' => 'Celebra a lo Grande tu',
                'hero_highlight' => 'Cumpleaños o Fiesta',
                'hero_subtitle' => 'Música a medida para sorprender al homenajeado, animación, karaoke y los mejores temas de vuestras épocas favoritas.',
                'name_label' => 'Nombre del Homenajeado / Organizador *',
                'name_placeholder' => 'Ej: 40 Cumpleaños de David / María',
                'ceremony_name' => 'Sonorización Sorpresa / Discursos',
                'ceremony_icon' => '🎬',
                'ceremony_desc' => 'Microfonía y música de apoyo para proyección de vídeos, dedicatorias o sorpresas especiales.',
                'cocktail_name' => 'Recepción / Bienvenida',
                'cocktail_icon' => '🍸',
                'cocktail_desc' => 'Música ambiente alegre para recibir a todos los amigos y familiares.',
                'restaurant_name' => 'Comida / Cena & Momento Tarta',
                'restaurant_desc' => 'Ambientación musical durante la comida/cena, soplar velas de la tarta y entrega de regalos.',
                'dj_name' => 'DJ Fiesta & Baile',
                'dj_icon' => '💃',
                'dj_desc' => 'Sesión de DJ en directo con los grandes éxitos adaptados a los gustos de vuestro grupo.',
                'prefix' => 'Cumpleaños: ',
            ],
            'comunion' => [
                'name' => 'Comunión / Bautizo / Familiar',
                'icon' => '🕊️',
                'badge' => 'Comunión & Familia',
                'hero_title' => 'Música & Diversión para una',
                'hero_highlight' => 'Comunión Inolvidable',
                'hero_subtitle' => 'Sonido profesional y música divertida para que disfruten tanto los peques como los mayores.',
                'name_label' => 'Nombre del Niño/a o Familia *',
                'name_placeholder' => 'Ej: Comunión de Mateo / Familia Gómez',
                'ceremony_name' => 'Sonorización Especial / Entrada',
                'ceremony_icon' => '🌟',
                'ceremony_desc' => 'Música especial para la entrada triunfal del protagonista y microfonía para palabras familiares.',
                'cocktail_name' => 'Aperitivo / Recepción Familiar',
                'cocktail_icon' => '🍸',
                'cocktail_desc' => 'Música ambiente alegre y distendida para recibir a los familiares e invitados.',
                'restaurant_name' => 'Comida & Momento de la Tarta',
                'restaurant_icon' => '🍽️',
                'restaurant_desc' => 'Música de fondo durante el banquete, corte de la tarta y entrega de recordatorios.',
                'dj_name' => 'DJ Baile & Animación Familiar',
                'dj_icon' => '💃',
                'dj_desc' => 'Música para bailar toda la familia, grandes éxitos, animación y diversión para todas las edades.',
                'prefix' => 'Comunión: ',
            ],
            'otro' => [
                'name' => 'Otro Evento / Celebración',
                'icon' => '🎉',
                'badge' => 'Celebración General',
                'hero_title' => 'Música & Sonido para tu',
                'hero_highlight' => 'Evento o Celebración',
                'hero_subtitle' => 'Personaliza el equipamiento técnico, sonorización y DJ para cualquier tipo de fiesta o acto.',
                'name_label' => 'Nombre del Evento / Contacto *',
                'name_placeholder' => 'Ej: Fiesta de Verano / Asociación Cultural',
                'ceremony_name' => 'Sonorización / Acto Protocolario',
                'ceremony_icon' => '📢',
                'ceremony_desc' => 'Megafonía, microfonía y música institucional o protocolaria.',
                'cocktail_name' => 'Recepción / Música de Bienvenida',
                'cocktail_icon' => '🍸',
                'cocktail_desc' => 'Música ambiente y equipo de sonido independiente para la bienvenida.',
                'restaurant_name' => 'Comida / Cena / Banquete',
                'restaurant_icon' => '🍽️',
                'restaurant_desc' => 'Ambientación musical durante la comida/cena y momentos destacados.',
                'dj_name' => 'DJ Fiesta & Baile',
                'dj_icon' => '💃',
                'dj_desc' => 'Sesión musical completa con sonido e iluminación profesional para la fiesta.',
                'prefix' => 'Evento: ',
            ],
        ];
    }

    public function setEventType($type)
    {
        $types = self::getEventTypes();
        if (!isset($types[$type])) {
            $type = 'boda';
        }
        $this->event_type = $type;
        $this->updateServiceLabels();
    }

    public function updatedEventType()
    {
        $this->updateServiceLabels();
    }

    public function updateServiceLabels()
    {
        $types = self::getEventTypes();
        $typeConfig = $types[$this->event_type] ?? $types['boda'];

        if (isset($this->services['ceremony'])) {
            $this->services['ceremony']['name'] = $typeConfig['ceremony_name'];
            $this->services['ceremony']['description'] = $typeConfig['ceremony_desc'];
            $this->services['ceremony']['icon'] = $typeConfig['ceremony_icon'] ?? '💍';
        }

        if (isset($this->services['cocktail'])) {
            $this->services['cocktail']['name'] = $typeConfig['cocktail_name'];
            $this->services['cocktail']['description'] = $typeConfig['cocktail_desc'];
            $this->services['cocktail']['icon'] = $typeConfig['cocktail_icon'] ?? '🍸';
        }

        if (isset($this->services['restaurant'])) {
            $this->services['restaurant']['name'] = $typeConfig['restaurant_name'];
            $this->services['restaurant']['description'] = $typeConfig['restaurant_desc'];
            $this->services['restaurant']['icon'] = $typeConfig['restaurant_icon'] ?? '🍽️';
        }

        if (isset($this->services['dj'])) {
            $this->services['dj']['name'] = $typeConfig['dj_name'];
            $this->services['dj']['description'] = $typeConfig['dj_desc'];
            $this->services['dj']['icon'] = $typeConfig['dj_icon'] ?? '💃';
        }
    }

    public function mount()
    {
        $initialType = request()->get('tipo', 'boda');
        $types = self::getEventTypes();
        $this->event_type = isset($types[$initialType]) ? $initialType : 'boda';

        $this->services = [
            'ceremony' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '💍',
                'name' => '', 
                'description' => ''
            ],
            'cocktail' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '🍸',
                'name' => '', 
                'description' => ''
            ],
            'restaurant' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '🍽️',
                'name' => '', 
                'description' => ''
            ],
            'dj' => [
                'selected' => true,  
                'quantity' => 4, 
                'icon' => '💃',
                'name' => '', 
                'description' => ''
            ],
            'karaoke' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '🎤',
                'name' => 'Animación & Karaoke', 
                'description' => 'Catálogo interactivo de canciones y micrófonos para cantar durante la fiesta.'
            ],
            'extra_hours' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '⏰',
                'name' => 'Horas Adicionales de Fiesta', 
                'description' => 'Ampliación de horario para continuar el baile sin interrupciones.'
            ],
            'photobooth' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '📸',
                'name' => 'Fotomatón & Photocall', 
                'description' => 'Servicio de fotomatón con impresión instantánea, atrezzo divertido y libro de firmas personalizado.'
            ],
        ];

        $this->updateServiceLabels();
    }

    /**
     * Get internal price catalog from settings
     */
    protected function getPriceCatalog(): array
    {
        return [
            'ceremony' => (float) Setting::get('price_ceremony', 180),
            'cocktail' => (float) Setting::get('price_cocktail', 150),
            'restaurant' => (float) Setting::get('price_restaurant', 150),
            'dj' => (float) Setting::get('price_dj', 150),
            'karaoke' => (float) Setting::get('price_karaoke', 80),
            'extra_hours' => (float) Setting::get('price_extra_hours', 120),
            'photobooth' => (float) Setting::get('price_photobooth', 350),
        ];
    }

    /**
     * Check if both Cocktail and Restaurant are selected
     */
    public function getHasCocktailRestaurantPackProperty(): bool
    {
        return !empty($this->services['cocktail']['selected']) && !empty($this->services['restaurant']['selected']);
    }

    /**
     * Get Cocktail + Restaurant combined pack settings and calculations
     */
    public function getCocktailRestaurantPackInfoProperty(): array
    {
        $catalog = $this->getPriceCatalog();
        return Setting::getCocktailRestaurantPackInfo($catalog['cocktail'] ?? 150, $catalog['restaurant'] ?? 150);
    }

    public function submitRequest()
    {
        $this->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'required|string|max:50',
            'event_date' => 'required|date',
            'event_location' => 'required|string|max:255',
            'client_notes' => 'nullable|string|max:1000'
        ]);

        $catalog = $this->getPriceCatalog();
        $totalEstimated = 0;

        // 1. Calculate internal total
        foreach ($this->services as $key => $service) {
            if ($service['selected']) {
                $qty = (int)($service['quantity'] ?: 1);
                $price = $catalog[$key] ?? 0;
                $totalEstimated += ($price * $qty);
            }
        }

        // Apply Pack Cóctel + Banquete discount if active
        $packInfo = $this->has_cocktail_restaurant_pack ? $this->cocktail_restaurant_pack_info : null;
        if ($packInfo && !empty($packInfo['enabled']) && $packInfo['discount_amount'] > 0) {
            $totalEstimated = max(0, $totalEstimated - $packInfo['discount_amount']);
        }

        // 2. Create or Find Client
        $client = User::firstOrCreate(
            ['email' => $this->client_email],
            [
                'name' => $this->client_name,
                'password' => bcrypt(Str::random(16)),
                'role' => 'client',
                'phone' => $this->client_phone,
            ]
        );

        // 3. Format notes with requested services
        $requestedServicesList = [];
        foreach ($this->services as $key => $service) {
            if ($service['selected']) {
                $qty = (int)($service['quantity'] ?: 1);
                $qtyText = $key === 'dj' ? " ({$qty} horas)" : ($qty > 1 ? " ({$qty} uds)" : "");
                $requestedServicesList[] = "• " . $service['name'] . $qtyText;
            }
        }

        if ($packInfo && !empty($packInfo['enabled']) && $packInfo['discount_amount'] > 0) {
            $requestedServicesList[] = "• 🎁 Pack Cóctel + Banquete incluido (" . $packInfo['savings_label'] . ")";
        }

        if ($this->custom_service_selected && !empty(trim($this->custom_service_name))) {
            $customName = trim($this->custom_service_name);
            $customDesc = trim($this->custom_service_description);
            $requestedServicesList[] = "• ✨ " . $customName . " (A consultar)" . ($customDesc ? " - " . $customDesc : "");
        }

        $typeConfig = self::getEventTypes()[$this->event_type] ?? self::getEventTypes()['boda'];
        $eventTitle = $typeConfig['prefix'] . $this->client_name;

        $fullNotes = "SOLICITUD DE PRESUPUESTO ONLINE:\n"
                   . "Tipo de Evento: " . $typeConfig['name'] . "\n\n"
                   . "Servicios Solicitados:\n"
                   . implode("\n", $requestedServicesList)
                   . (!empty($this->client_notes) ? "\n\nObservaciones del Cliente:\n" . $this->client_notes : "");

        // 4. Create Event Draft
        $event = Event::create([
            'name' => $eventTitle,
            'event_date' => $this->event_date,
            'location' => $this->event_location,
            'status' => 'draft',
            'client_id' => $client->id,
            'token' => Str::random(32),
            'notes' => $fullNotes,
        ]);

        // 5. Create Quote Draft for Admin
        $quote = $event->quotes()->create([
            'amount' => $totalEstimated,
            'status' => 'pending',
        ]);

        // 6. Create Quote Items
        foreach ($this->services as $key => $service) {
            if ($service['selected']) {
                $qty = (int)($service['quantity'] ?: 1);
                $price = $catalog[$key] ?? 0;
                $quote->items()->create([
                    'service_name' => $service['name'],
                    'quantity' => $qty,
                    'price' => $price,
                    'total' => $price * $qty,
                ]);
            }
        }

        if ($packInfo && !empty($packInfo['enabled']) && $packInfo['discount_amount'] > 0) {
            $quote->items()->create([
                'service_name' => 'Descuento Especial Pack Cóctel + Banquete',
                'description' => 'Tarifa combinada promocional por contratación conjunta de Cóctel y Banquete (' . round($packInfo['discount_percentage']) . '% dto.)',
                'quantity' => 1,
                'price' => -$packInfo['discount_amount'],
                'total' => -$packInfo['discount_amount'],
            ]);
        }

        if ($this->custom_service_selected && !empty(trim($this->custom_service_name))) {
            $customName = trim($this->custom_service_name);
            $customDesc = trim($this->custom_service_description);
            $quote->items()->create([
                'service_name' => $customName . ' (A consultar)',
                'description' => $customDesc ?: 'Servicio / Extra solicitado por el cliente (Presupuesto y disponibilidad a consultar)',
                'quantity' => 1,
                'price' => 0,
                'total' => 0,
            ]);
        }

        // 7. Enviar notificación por email al Administrador / Empresa
        try {
            $adminEmail = Setting::get('company_email', config('mail.from.address'));
            if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                \Illuminate\Support\Facades\Mail::to($adminEmail)
                    ->send(new \App\Mail\NewQuoteRequestAdminMail($event, $quote, $requestedServicesList, $typeConfig));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo enviar el email de aviso de presupuesto: ' . $e->getMessage());
        }

        $this->is_submitted = true;
    }

    public function render()
    {
        return view('livewire.guest.quote-calculator')
            ->layout('components.layouts.wide-guest', ['title' => 'Solicitar Presupuesto - ' . Setting::get('company_name', 'Eventos Musicales')]);
    }
}
