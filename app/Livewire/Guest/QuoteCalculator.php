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
    public $event_schedule = '';
    public $needs_prior_setup = false;
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
                'name' => 'Colegio / Asociación / Otro Evento',
                'icon' => '🎉',
                'badge' => 'Colegios, Fiestas & Actos',
                'hero_title' => 'Música & Sonido para tu',
                'hero_highlight' => 'Evento o Celebración',
                'hero_subtitle' => 'Sonorización para festivales escolares, AMPAs, ayuntamientos, fiestas o actos especiales a medida.',
                'name_label' => 'Nombre del Evento / Colegio / Contacto *',
                'name_placeholder' => 'Ej: AMPA Colegio San Fernando / Asociación Cultural',
                'ceremony_name' => 'Sonorización Acto / Festival / Escenario',
                'ceremony_icon' => '📢',
                'ceremony_desc' => 'Equipo de sonido profesional, microfonía y control técnico para actuaciones, festivales o actos.',
                'cocktail_name' => 'Recepción / Música de Bienvenida',
                'cocktail_icon' => '🍸',
                'cocktail_desc' => 'Música ambiente y equipo de sonido independiente para la bienvenida.',
                'restaurant_name' => 'Comida / Cena / Banquete',
                'restaurant_icon' => '🍽️',
                'restaurant_desc' => 'Ambientación musical durante la comida/cena y momentos destacados.',
                'dj_name' => 'DJ Fiesta & Animación Musical',
                'dj_icon' => '💃',
                'dj_desc' => 'Sesión musical completa con sonido, microfonía e iluminación profesional para la celebración.',
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
            'smoke_machine' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '💨',
                'name' => 'Máquina de Humo', 
                'description' => 'Efecto de humo para ambientar la pista de baile y realzar los haces de luces.',
                'included' => false,
            ],
            'cold_sparks' => [
                'selected' => false, 
                'quantity' => 2, 
                'icon' => '✨',
                'name' => 'Fuego Frío / Chispas Frías', 
                'description' => 'Efecto especial de chispas frías sin combustión (60 € por máquina). Habitual: 2 máquinas para apertura de baile o corte de tarta.',
                'included' => false,
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
            'pack_duo_360' => [
                'selected' => false,
                'quantity' => 1,
                'hours' => 2,
                'icon' => '🔹',
                'name' => 'Pack Dúo Todo Incluido (Fotomatón + Plataforma 360º)',
                'description' => 'Pack ahorro con ambos servicios: Fotomatón con copias ilimitadas y álbum de firmas + Plataforma de Vídeo 360º slow motion con atrezzo y animadores.'
            ],
            'photobooth' => [
                'selected' => false, 
                'quantity' => 1,
                'hours' => 2,
                'icon' => '📸',
                'name' => 'Fotomatón & Photocall', 
                'description' => 'Servicio de fotomatón con cámara réflex, impresión instantánea ilimitada, atrezzo divertido, pendrive USB, animador y libro de firmas personalizado.'
            ],
            'video_360' => [
                'selected' => false,
                'quantity' => 1,
                'hours' => 2,
                'icon' => '🎥',
                'name' => 'Plataforma Vídeo 360º',
                'description' => 'Plataforma de 100cm (4-5 personas) con vídeos slow motion y boomerang ilimitados al instante por QR/AirDrop, confeti, pistola de billetes, atrezzo y animadores.'
            ],
            'chocolate_fountain' => [
                'selected' => false,
                'quantity' => 1,
                'fountains' => 1,
                'hours' => 2,
                'waffle_corner' => false,
                'icon' => '🍫',
                'name' => 'Mesa Dulce con Fuente de Chocolate',
                'description' => 'Chocolate a elegir (blanco, negro o con leche), fruta variada de temporada, donuts, gofres cortados, profiteroles, galletas, chocolatinas, chuches y arco de globos con reposición a mitad del servicio.'
            ],
            'candy_bar' => [
                'selected' => false,
                'quantity' => 1,
                'icon' => '🍬',
                'name' => 'Mesa de Solo Chuches (Candy Bar)',
                'description' => 'Gran variado de golosinas (eligiendo las favoritas del anfitrión), decoración temática personalizada a elegir y globos decorativos.'
            ],
            'waffle_cart' => [
                'selected' => false,
                'quantity' => 1,
                'hours' => 2,
                'icon' => '🧇',
                'name' => 'Carrito de Gofres hechos al momento',
                'description' => 'Gofres recién hechos en directo con 4 siropes a elegir (Kinder, Lotus, Nutella, choco blanco, pistacho...) y 4 toppings secos (Lotus, Oreo, Lacasitos, mini nubes), decorado con globos y banderín con nombre.'
            ],
        ];

        $this->updateServiceLabels();
        $this->syncPackEffects();
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
            'smoke_machine' => (float) Setting::get('price_effects_smoke', 60),
            'cold_sparks' => (float) Setting::get('price_effects_sparks', 60),
            'karaoke' => (float) Setting::get('price_karaoke', 80),
            'extra_hours' => (float) Setting::get('price_extra_hours', 120),
            'photobooth' => (float) Setting::get('price_photobooth', 380),
        ];
    }

    /**
     * Detect best external provider based on event location
     */
    protected function resolveExternalProviderKey(): string
    {
        $loc = mb_strtolower(trim((string)$this->event_location));
        if (
            str_contains($loc, 'vitoria') ||
            str_contains($loc, 'gasteiz') ||
            str_contains($loc, 'álava') ||
            str_contains($loc, 'alava') ||
            str_contains($loc, 'araba') ||
            str_contains($loc, 'bilbao') ||
            str_contains($loc, 'bizkaia') ||
            str_contains($loc, 'vizcaya') ||
            str_contains($loc, 'donostia') ||
            str_contains($loc, 'gipuzkoa') ||
            str_contains($loc, 'guipuzcoa') ||
            str_contains($loc, 'euskadi') ||
            str_contains($loc, 'miranda')
        ) {
            return 'vitoria';
        }
        return 'logrono';
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

    /**
     * Get DJ Pack resolution based on selected hours
     */
    public function getDjPackInfoProperty(): array
    {
        $hours = (int)($this->services['dj']['quantity'] ?? 4);
        return Setting::getDjPackForHours($hours);
    }

    public function setDjHours(int $hours)
    {
        $this->services['dj']['quantity'] = max(2, min(9, $hours));
        if (isset($this->services['smoke_machine'])) {
            unset($this->services['smoke_machine']['manually_toggled']);
        }
        if (isset($this->services['cold_sparks'])) {
            unset($this->services['cold_sparks']['manually_toggled']);
        }
        $this->syncPackEffects();
    }

    public function toggleService(string $key)
    {
        if (!isset($this->services[$key])) {
            return;
        }

        $this->services[$key]['selected'] = !$this->services[$key]['selected'];

        if ($key === 'smoke_machine' || $key === 'cold_sparks') {
            $this->services[$key]['manually_toggled'] = true;
        }

        if ($key === 'pack_duo_360') {
            $this->services['pack_duo_360']['auto_activated'] = false;
            if (!empty($this->services['pack_duo_360']['selected'])) {
                if (isset($this->services['photobooth'])) {
                    $this->services['photobooth']['selected'] = false;
                }
                if (isset($this->services['video_360'])) {
                    $this->services['video_360']['selected'] = false;
                }
            }
        }

        if ($key === 'photobooth' || $key === 'video_360') {
            if (!empty($this->services['photobooth']['selected']) && !empty($this->services['video_360']['selected'])) {
                $this->autoActivatePackDuo();
            } elseif (!empty($this->services[$key]['selected']) && isset($this->services['pack_duo_360'])) {
                $this->services['pack_duo_360']['selected'] = false;
                $this->services['pack_duo_360']['auto_activated'] = false;
            }
        }

        if ($key === 'dj') {
            $this->syncPackEffects();
        }
    }

    protected function autoActivatePackDuo(): void
    {
        if (!isset($this->services['pack_duo_360'])) {
            return;
        }
        $pbHours = (int)($this->services['photobooth']['hours'] ?? 2);
        $v360Hours = (int)($this->services['video_360']['hours'] ?? 2);
        $this->services['pack_duo_360']['hours'] = max(2, min(6, max($pbHours, $v360Hours)));
        $this->services['pack_duo_360']['selected'] = true;
        $this->services['pack_duo_360']['auto_activated'] = true;
        $this->services['photobooth']['selected'] = false;
        $this->services['video_360']['selected'] = false;
    }

    public function updatedServices($value, $key)
    {
        if (str_starts_with($key, 'dj.')) {
            $this->syncPackEffects();
        }
    }

    public function setColdSparksQuantity(int $quantity)
    {
        $this->services['cold_sparks']['quantity'] = max(1, min(10, $quantity));
        $this->services['cold_sparks']['selected'] = true;
        $this->services['cold_sparks']['manually_toggled'] = true;
    }

    public function setExtraHoursQuantity(int $quantity)
    {
        $this->services['extra_hours']['quantity'] = max(1, min(6, $quantity));
        $this->services['extra_hours']['selected'] = true;
    }

    public function setPackDuoHours(int $hours)
    {
        if (!isset($this->services['pack_duo_360'])) {
            return;
        }
        $this->services['pack_duo_360']['hours'] = max(2, min(6, $hours));
        $this->services['pack_duo_360']['selected'] = true;
        if (isset($this->services['photobooth'])) {
            $this->services['photobooth']['selected'] = false;
        }
        if (isset($this->services['video_360'])) {
            $this->services['video_360']['selected'] = false;
        }
    }

    public function setPhotoboothHours(int $hours)
    {
        if (!isset($this->services['photobooth'])) {
            return;
        }
        $this->services['photobooth']['hours'] = max(2, min(6, $hours));
        $this->services['photobooth']['selected'] = true;
        if (!empty($this->services['video_360']['selected'])) {
            $this->autoActivatePackDuo();
            return;
        }
        if (isset($this->services['pack_duo_360'])) {
            $this->services['pack_duo_360']['selected'] = false;
            $this->services['pack_duo_360']['auto_activated'] = false;
        }
    }

    public function setVideo360Hours(int $hours)
    {
        if (!isset($this->services['video_360'])) {
            return;
        }
        $this->services['video_360']['hours'] = max(2, min(6, $hours));
        $this->services['video_360']['selected'] = true;
        if (!empty($this->services['photobooth']['selected'])) {
            $this->autoActivatePackDuo();
            return;
        }
        if (isset($this->services['pack_duo_360'])) {
            $this->services['pack_duo_360']['selected'] = false;
            $this->services['pack_duo_360']['auto_activated'] = false;
        }
    }

    public function applyPackDuoFromSeparate()
    {
        $this->autoActivatePackDuo();
    }

    public function setChocolateFountainOption(int $fountains, int $hours)
    {
        if (!isset($this->services['chocolate_fountain'])) {
            return;
        }
        $this->services['chocolate_fountain']['fountains'] = $fountains >= 2 ? 2 : 1;
        $this->services['chocolate_fountain']['hours'] = $hours >= 3 ? 3 : 2;
        $this->services['chocolate_fountain']['selected'] = true;
    }

    public function toggleChocolateWaffleCorner()
    {
        if (!isset($this->services['chocolate_fountain'])) {
            return;
        }
        $this->services['chocolate_fountain']['waffle_corner'] = empty($this->services['chocolate_fountain']['waffle_corner']);
        $this->services['chocolate_fountain']['selected'] = true;
    }

    public function setWaffleCartHours(int $hours)
    {
        if (!isset($this->services['waffle_cart'])) {
            return;
        }
        $this->services['waffle_cart']['hours'] = $hours >= 3 ? 3 : 2;
        $this->services['waffle_cart']['selected'] = true;
    }

    /**
     * Cómputo total de horas de baile (horas del Pack DJ + horas adicionales seleccionadas)
     */
    public function getTotalPartyHoursProperty(): int
    {
        $baseHours = !empty($this->services['dj']['selected']) ? (int)($this->services['dj']['quantity'] ?? 4) : 0;
        $extraHours = !empty($this->services['extra_hours']['selected']) ? (int)($this->services['extra_hours']['quantity'] ?? 1) : 0;

        return $baseHours + $extraHours;
    }

    /**
     * Si el cliente marca un Pack DJ (ej. 4h) y además marca Horas Adicionales (ej. +1h = 5h totales),
     * comprueba si existe un Pack superior que ya cubra esas horas totales (ej. Pack Signature 5h o Pack Deluxe Show 6h).
     */
    public function getRecommendedDjPackForExtraHoursProperty(): ?array
    {
        if (empty($this->services['dj']['selected']) || empty($this->services['extra_hours']['selected'])) {
            return null;
        }

        $baseHours = (int)($this->services['dj']['quantity'] ?? 4);
        $extraHours = (int)($this->services['extra_hours']['quantity'] ?? 1);
        $totalHours = $baseHours + $extraHours;

        $currentPack = Setting::getDjPackForHours($baseHours);
        $targetPack = Setting::getDjPackForHours($totalHours);

        if (($targetPack['pack_key'] ?? '') !== ($currentPack['pack_key'] ?? '')) {
            $targetPack['base_hours'] = $baseHours;
            $targetPack['added_extra_hours'] = $extraHours;
            $targetPack['combined_total_hours'] = $totalHours;
            $targetPack['current_pack_name'] = $currentPack['base_name'] ?? 'Pack actual';
            return $targetPack;
        }

        return null;
    }

    /**
     * Aplica con 1 clic el Pack recomendado que ya incluye el total de horas de baile
     */
    public function applyRecommendedDjPack()
    {
        $totalHours = $this->total_party_hours;
        if ($totalHours < 2) {
            return;
        }

        if (isset($this->services['extra_hours'])) {
            $this->services['extra_hours']['selected'] = false;
            $this->services['extra_hours']['quantity'] = 1;
        }

        $this->setDjHours(min(9, $totalHours));
    }

    public function syncPackEffects()
    {
        if (empty($this->services['dj']['selected'])) {
            if (isset($this->services['smoke_machine'])) {
                $this->services['smoke_machine']['included'] = false;
            }
            if (isset($this->services['cold_sparks'])) {
                $this->services['cold_sparks']['included'] = false;
            }
            return;
        }

        $pack = $this->dj_pack_info;
        $includesSmoke = !empty($pack['includes_smoke']);
        $includesSparks = !empty($pack['includes_sparks']);

        if (isset($this->services['smoke_machine'])) {
            $this->services['smoke_machine']['included'] = $includesSmoke;
            if ($includesSmoke) {
                $this->services['smoke_machine']['selected'] = true;
            } elseif (empty($this->services['smoke_machine']['manually_toggled'])) {
                $this->services['smoke_machine']['selected'] = false;
            }
        }

        if (isset($this->services['cold_sparks'])) {
            $this->services['cold_sparks']['included'] = $includesSparks;
            if ($includesSparks) {
                $this->services['cold_sparks']['selected'] = true;
                if (empty($this->services['cold_sparks']['manually_toggled'])) {
                    $this->services['cold_sparks']['quantity'] = 2;
                }
            } elseif (empty($this->services['cold_sparks']['manually_toggled'])) {
                $this->services['cold_sparks']['selected'] = false;
                $this->services['cold_sparks']['quantity'] = 2;
            }
        }
    }

    public function submitRequest()
    {
        // Las acciones Livewire van por /livewire/update y no pasan por el throttle de la ruta GET
        $throttleKey = 'quote_calc_submit_' . request()->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            $this->addError('client_email', "Has enviado varias solicitudes seguidas. Inténtalo de nuevo en {$seconds} segundos.");
            return;
        }

        $this->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'required|string|max:50',
            'event_date' => 'required|date',
            'event_location' => 'required|string|max:255',
            'event_schedule' => 'nullable|string|max:255',
            'needs_prior_setup' => 'nullable|boolean',
            'client_notes' => 'nullable|string|max:3000'
        ]);

        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 600);

        $catalog = $this->getPriceCatalog();
        $totalEstimated = 0;
        $djPack = !empty($this->services['dj']['selected']) ? $this->dj_pack_info : null;
        $provKey = $this->resolveExternalProviderKey();
        $pbDetected = Setting::getPhotobooth360Config($provKey);
        $pbVitoria = Setting::getPhotobooth360Config('vitoria');

        $resolveExternalPriceAndMeta = function (string $key, array $service) use ($pbDetected, $pbVitoria, $catalog): array {
            if ($key === 'pack_duo_360') {
                $h = max(2, (int)($service['hours'] ?? 2));
                if ($h <= 2) {
                    $p = (float)($pbDetected['pack_duo_2h'] ?? 690);
                } elseif ($h === 3) {
                    $p = (float)($pbDetected['pack_duo_3h'] ?? 850);
                } else {
                    $extraRate = (float)(($pbDetected['photobooth_extra_hour'] ?? 50) + ($pbDetected['video_360_extra_hour'] ?? 60));
                    $p = (float)($pbDetected['pack_duo_3h'] ?? 850) + (($h - 3) * $extraRate);
                }
                return [
                    'price' => $p,
                    'item_name' => "Pack Dúo Todo Incluido: Fotomatón + Plataforma 360º ({$h} Horas)",
                    'note_label' => "Pack Dúo Todo Incluido: Fotomatón + Plataforma 360º ({$h} Horas)",
                    'description' => $pbDetected['pack_duo_desc'] ?? $service['description'],
                ];
            }

            if ($key === 'photobooth') {
                $h = max(2, (int)($service['hours'] ?? 2));
                if ($h <= 2) {
                    $p = (float)($pbDetected['photobooth_2h'] ?? 380);
                } elseif ($h === 3) {
                    $p = (float)($pbDetected['photobooth_3h'] ?? 430);
                } else {
                    $p = (float)($pbDetected['photobooth_3h'] ?? 430) + (($h - 3) * (float)($pbDetected['photobooth_extra_hour'] ?? 50));
                }
                return [
                    'price' => $p,
                    'item_name' => "Fotomatón & Photocall ({$h} Horas)",
                    'note_label' => "Fotomatón & Photocall ({$h} Horas)",
                    'description' => $pbDetected['photobooth_desc'] ?? $service['description'],
                ];
            }

            if ($key === 'video_360') {
                $h = max(2, (int)($service['hours'] ?? 2));
                if ($h <= 2) {
                    $p = (float)($pbDetected['video_360_2h'] ?? 430);
                } elseif ($h === 3) {
                    $p = (float)($pbDetected['video_360_3h'] ?? 490);
                } else {
                    $p = (float)($pbDetected['video_360_3h'] ?? 490) + (($h - 3) * (float)($pbDetected['video_360_extra_hour'] ?? 60));
                }
                return [
                    'price' => $p,
                    'item_name' => "Plataforma Vídeo 360º ({$h} Horas)",
                    'note_label' => "Plataforma Vídeo 360º ({$h} Horas)",
                    'description' => $pbDetected['video_360_desc'] ?? $service['description'],
                ];
            }

            if ($key === 'chocolate_fountain') {
                $f = (int)($service['fountains'] ?? 1) >= 2 ? 2 : 1;
                $h = (int)($service['hours'] ?? 2) >= 3 ? 3 : 2;
                $waffleCorner = !empty($service['waffle_corner']);
                $cfg = !empty($pbDetected['chocolate_fountain_enabled']) ? $pbDetected : $pbVitoria;
                $p = (float)($cfg["chocolate_fountain_{$f}_{$h}h"] ?? 420);
                $fLabel = $f === 2 ? '2 Fuentes de Chocolate' : '1 Fuente de Chocolate';
                $waffleUnit = number_format((float)($cfg['chocolate_waffle_extra_unit'] ?? 2.90), 2, ',', '.');
                $waffleSuffix = $waffleCorner ? " + Rincón de Gofres ({$waffleUnit} €/ud)" : '';
                $baseDesc = $cfg['chocolate_fountain_desc'] ?? $service['description'];
                if ($waffleCorner) {
                    $baseDesc .= " | 🧇 Solicita añadir Rincón de Gofres (3 biberones de siropes + nata spray a {$waffleUnit} €/unidad).";
                }
                return [
                    'price' => $p,
                    'item_name' => "Mesa Dulce con {$fLabel} ({$h} Horas)" . ($waffleCorner ? ' + Rincón de Gofres' : ''),
                    'note_label' => "Mesa Dulce con {$fLabel} ({$h} Horas){$waffleSuffix}",
                    'description' => $baseDesc,
                ];
            }

            if ($key === 'candy_bar') {
                $cfg = !empty($pbDetected['candy_bar_enabled']) ? $pbDetected : $pbVitoria;
                $p = (float)($cfg['candy_bar_price'] ?? 420);
                return [
                    'price' => $p,
                    'item_name' => 'Mesa de Solo Chuches',
                    'note_label' => 'Mesa de Solo Chuches (Candy Bar)',
                    'description' => $cfg['candy_bar_desc'] ?? $service['description'],
                ];
            }

            if ($key === 'waffle_cart') {
                $h = (int)($service['hours'] ?? 2) >= 3 ? 3 : 2;
                $approx = $h === 3 ? 'unos 100 gofres' : 'unos 60 gofres';
                $cfg = !empty($pbDetected['waffle_cart_enabled']) ? $pbDetected : $pbVitoria;
                $p = (float)($h === 3 ? ($cfg['waffle_cart_3h'] ?? 550) : ($cfg['waffle_cart_2h'] ?? 420));
                return [
                    'price' => $p,
                    'item_name' => "Carrito de Gofres hechos al momento ({$h} Horas · {$approx})",
                    'note_label' => "Carrito de Gofres hechos al momento ({$h} Horas · {$approx})",
                    'description' => $cfg['waffle_cart_desc'] ?? $service['description'],
                ];
            }

            $p = (float)($catalog[$key] ?? 0);
            return [
                'price' => $p,
                'item_name' => $service['name'],
                'note_label' => $service['name'],
                'description' => $service['description'] ?? '',
            ];
        };

        // 1. Calculate internal total
        foreach ($this->services as $key => $service) {
            if ($service['selected']) {
                if ($key === 'dj' && $djPack) {
                    $totalEstimated += (float)$djPack['total_price'];
                } elseif ($key === 'smoke_machine') {
                    if (empty($service['included'])) {
                        $totalEstimated += ($catalog['smoke_machine'] ?? 60);
                    }
                } elseif ($key === 'cold_sparks') {
                    $qty = (int)($service['quantity'] ?: 1);
                    $unitPrice = $catalog['cold_sparks'] ?? 60;
                    if (!empty($service['included'])) {
                        $extraQty = max(0, $qty - 2);
                        $totalEstimated += ($extraQty * $unitPrice);
                    } else {
                        $totalEstimated += ($qty * $unitPrice);
                    }
                } elseif (in_array($key, ['pack_duo_360', 'photobooth', 'video_360', 'chocolate_fountain', 'candy_bar', 'waffle_cart'], true)) {
                    if (($key === 'photobooth' || $key === 'video_360') && !empty($this->services['pack_duo_360']['selected'])) {
                        continue;
                    }
                    $resolved = $resolveExternalPriceAndMeta($key, $service);
                    $totalEstimated += $resolved['price'];
                } else {
                    $qty = (int)($service['quantity'] ?: 1);
                    $price = $catalog[$key] ?? 0;
                    $totalEstimated += ($price * $qty);
                }
            }
        }

        // Apply Pack Cóctel + Banquete discount if active
        $packInfo = $this->has_cocktail_restaurant_pack ? $this->cocktail_restaurant_pack_info : null;
        if ($packInfo && !empty($packInfo['enabled']) && $packInfo['discount_amount'] > 0) {
            $totalEstimated = max(0, $totalEstimated - $packInfo['discount_amount']);
        }

        // 2. Create or Find Client (matching by email or last 9 digits of phone to link with existing IMAP leads).
        // Seguridad: solo se enlaza con usuarios de rol cliente y nunca solo por nombre (formulario público).
        $client = null;
        $cleanEmail = strtolower(trim((string)$this->client_email));
        if (!empty($cleanEmail)) {
            $client = User::where('role', 'client')->where('email', $cleanEmail)->first();
            if (!$client && User::where('email', $cleanEmail)->exists()) {
                // El email pertenece a una cuenta de personal: no enlazar ni reutilizar
                $cleanEmail = '';
            }
        }
        if (!$client && !empty($this->client_phone)) {
            $digits = preg_replace('/\D/', '', (string)$this->client_phone);
            if (strlen($digits) >= 9) {
                $last9 = substr($digits, -9);
                $client = User::where('role', 'client')
                    ->where(function ($q) use ($last9, $digits) {
                        $q->where('phone', 'LIKE', "%{$last9}")
                          ->orWhere('phone', $digits);
                    })->first();
            }
        }

        if ($client) {
            $updates = [];
            if (!empty($cleanEmail) && (empty($client->email) || str_ends_with($client->email, '.local'))) {
                if (!User::where('email', $cleanEmail)->where('id', '!=', $client->id)->exists()) {
                    $updates['email'] = $cleanEmail;
                }
            }
            if (!empty($this->client_phone) && empty($client->phone)) {
                $updates['phone'] = $this->client_phone;
            }
            if (!empty($updates)) {
                $client->update($updates);
            }
        } else {
            $client = User::create([
                'name' => $this->client_name,
                'email' => $cleanEmail ?: ('lead_' . time() . '_' . Str::lower(Str::random(6)) . '@eventosmusicales.local'),
                'password' => bcrypt(Str::random(16)),
                'role' => 'client',
                'phone' => $this->client_phone,
            ]);
        }

        // 3. Format notes with requested services
        $requestedServicesList = [];
        $recommendedPackForExtra = $this->recommended_dj_pack_for_extra_hours;
        $totalPartyHours = $this->total_party_hours;

        foreach ($this->services as $key => $service) {
            if ($service['selected']) {
                if ($key === 'dj' && $djPack) {
                    $requestedServicesList[] = "• " . $service['name'] . " (" . $djPack['requested_hours'] . "h) ➔ " . $djPack['badge'];
                } elseif ($key === 'smoke_machine') {
                    if (!empty($service['included'])) {
                        $requestedServicesList[] = "• " . $service['name'] . " (✅ Incluido en " . ($djPack['base_name'] ?? 'Pack') . ")";
                    } else {
                        $requestedServicesList[] = "• " . $service['name'] . " (60 €)";
                    }
                } elseif ($key === 'cold_sparks') {
                    $qty = (int)($service['quantity'] ?: 1);
                    $unitPrice = $catalog['cold_sparks'] ?? 60;
                    if (!empty($service['included'])) {
                        $extraQty = max(0, $qty - 2);
                        $extraText = $extraQty > 0 ? " (2 máq. incluidas en " . ($djPack['base_name'] ?? 'Pack') . " + {$extraQty} extra a {$unitPrice} €/ud)" : " (✅ 2 máq. incluidas en " . ($djPack['base_name'] ?? 'Pack') . ")";
                        $requestedServicesList[] = "• " . $service['name'] . " ({$qty} " . ($qty == 1 ? 'máquina' : 'máquinas') . "){$extraText}";
                    } else {
                        $requestedServicesList[] = "• " . $service['name'] . " ({$qty} " . ($qty == 1 ? 'máquina' : 'máquinas') . " x {$unitPrice} €/ud)";
                    }
                } elseif ($key === 'extra_hours') {
                    $qty = (int)($service['quantity'] ?: 1);
                    $hoursLabel = $qty === 1 ? '1 hora adicional' : "{$qty} horas adicionales";
                    $totalHoursNote = !empty($this->services['dj']['selected']) ? " ➔ Total baile: {$totalPartyHours}h" : "";
                    $recNote = $recommendedPackForExtra ? " (💡 Recomendado: {$recommendedPackForExtra['base_name']} que ya cubre {$totalPartyHours}h)" : "";
                    $requestedServicesList[] = "• " . $service['name'] . " (+{$hoursLabel}{$totalHoursNote}){$recNote}";
                } elseif (in_array($key, ['pack_duo_360', 'photobooth', 'video_360', 'chocolate_fountain', 'candy_bar', 'waffle_cart'], true)) {
                    if (($key === 'photobooth' || $key === 'video_360') && !empty($this->services['pack_duo_360']['selected'])) {
                        continue;
                    }
                    $resolved = $resolveExternalPriceAndMeta($key, $service);
                    $requestedServicesList[] = "• " . $resolved['note_label'];
                } else {
                    $qty = (int)($service['quantity'] ?: 1);
                    $qtyText = $qty > 1 ? " ({$qty} uds)" : "";
                    $requestedServicesList[] = "• " . $service['name'] . $qtyText;
                }
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

        if (!empty(trim((string)$this->event_schedule))) {
            $requestedServicesList[] = "• ⏰ Horario / Duración indicada: " . trim((string)$this->event_schedule);
        }

        if ($this->needs_prior_setup) {
            $requestedServicesList[] = "• 🔧 Requiere montaje el día anterior / ensayo previo / varios días (A valorar)";
        }

        if (empty($requestedServicesList)) {
            $requestedServicesList[] = "• 📝 Solicitud personalizada a medida (ver notas y detalles del cliente)";
        }

        $typeConfig = self::getEventTypes()[$this->event_type] ?? self::getEventTypes()['boda'];
        $eventTitle = $typeConfig['prefix'] . $this->client_name;

        $scheduleAndSetupNotes = "";
        if (!empty(trim((string)$this->event_schedule))) {
            $scheduleAndSetupNotes .= "Horario / Tramo del Evento: " . trim((string)$this->event_schedule) . "\n";
        }
        if ($this->needs_prior_setup) {
            $scheduleAndSetupNotes .= "Montaje Especial: SÍ (Requiere montaje el día anterior / ensayo previo / varios días)\n";
        }

        $fullNotes = "SOLICITUD DE PRESUPUESTO ONLINE (/presupuesto):\n"
                   . "Tipo de Evento: " . $typeConfig['name'] . "\n"
                   . $scheduleAndSetupNotes
                   . "\nServicios Solicitados:\n"
                   . implode("\n", $requestedServicesList)
                   . (!empty(trim((string)$this->client_notes)) ? "\n\n📝 NOTAS Y EXPLICACIÓN DEL CLIENTE:\n" . trim((string)$this->client_notes) : "");

        // 4. Reutilizar evento existente del cliente si ya entró como Lead (para no duplicarlo) o crear uno nuevo
        $event = Event::where('client_id', $client->id)
            ->where(function ($q) {
                if (!empty($this->event_date)) {
                    $q->whereDate('event_date', $this->event_date)
                      ->orWhereIn('status', ['no_response', 'draft']);
                } else {
                    $q->whereIn('status', ['no_response', 'draft']);
                }
            })
            ->latest()
            ->first();

        if ($event) {
            $event->update([
                'event_date' => $this->event_date ?: $event->event_date,
                'location' => (!empty($this->event_location) && $this->event_location !== 'Por definir') ? $this->event_location : $event->location,
                'notes' => ($event->notes ? $event->notes . "\n\n========================================\n" : '') . $fullNotes,
            ]);
        } else {
            $event = Event::create([
                'name' => $eventTitle,
                'brand' => Setting::getDetectedBrand(),
                'event_type' => $this->event_type ?: 'boda',
                'event_date' => $this->event_date,
                'location' => $this->event_location,
                'status' => 'draft',
                'client_id' => $client->id,
                'token' => Str::random(32),
                'notes' => $fullNotes,
            ]);
        }

        // Localizar automáticamente el teléfono del restaurante / bodega / finca si se ha indicado lugar
        try {
            \App\Services\VenueLookupService::autoPopulateEventVenuePhone($event);
        } catch (\Throwable $e) {}

        // 5. Create Quote Draft for Admin
        $quote = $event->quotes()->create([
            'brand' => $event->brand ?: Setting::getDetectedBrand(),
            'amount' => $totalEstimated,
            'status' => 'pending',
        ]);

        // 6. Create Quote Items
        foreach ($this->services as $key => $service) {
            if ($service['selected']) {
                if ($key === 'dj' && $djPack) {
                    // Base DJ Pack item
                    $quote->items()->create([
                        'service_name' => $djPack['base_name'],
                        'description' => $djPack['description'],
                        'quantity' => 1,
                        'price' => $djPack['pack_price'],
                        'total' => $djPack['pack_price'],
                    ]);

                    // Extra Hours if applicable
                    if ($djPack['extra_hours'] > 0) {
                        $quote->items()->create([
                            'service_name' => 'Horas Adicionales de Fiesta',
                            'description' => "Ampliación de {$djPack['extra_hours']} hora(s) extra sobre el {$djPack['base_name']}",
                            'quantity' => $djPack['extra_hours'],
                            'price' => $djPack['extra_hour_price'],
                            'total' => $djPack['extra_hours'] * $djPack['extra_hour_price'],
                        ]);
                    }
                } elseif ($key === 'smoke_machine') {
                    $isIncluded = !empty($service['included']);
                    $p = $isIncluded ? 0 : ($catalog['smoke_machine'] ?? 60);
                    $quote->items()->create([
                        'service_name' => $isIncluded ? 'Máquina de Humo (Incluido en ' . ($djPack['base_name'] ?? 'Pack') . ')' : 'Máquina de Humo (Efecto pista)',
                        'description' => $isIncluded ? 'Efecto de máquina de humo para ambientar la pista de baile (incluido en el pack contratado).' : $service['description'],
                        'quantity' => 1,
                        'price' => $p,
                        'total' => $p,
                    ]);
                } elseif ($key === 'cold_sparks') {
                    $isIncluded = !empty($service['included']);
                    $qty = (int)($service['quantity'] ?: 1);
                    $unitPrice = (float)($catalog['cold_sparks'] ?? 60);

                    if ($isIncluded) {
                        $extraQty = max(0, $qty - 2);
                        $packBaseName = $djPack['base_name'] ?? 'Pack Deluxe Show';

                        $quote->items()->create([
                            'service_name' => "Fuego Frío / Chispas Frías (2 máquinas - Incluido en {$packBaseName})",
                            'description' => 'Efecto especial de 2 máquinas de chispas frías sin combustión para la apertura del baile (incluido en el pack contratado).',
                            'quantity' => min(2, $qty),
                            'price' => 0,
                            'total' => 0,
                        ]);

                        if ($extraQty > 0) {
                            $quote->items()->create([
                                'service_name' => "Máquinas adicionales de Fuego Frío ({$extraQty} " . ($extraQty == 1 ? 'máquina extra' : 'máquinas extra') . ")",
                                'description' => "Ampliación de espectáculo de chispas frías con {$extraQty} máquina(s) adicional(es) a " . number_format($unitPrice, 0) . " €/ud.",
                                'quantity' => $extraQty,
                                'price' => $unitPrice,
                                'total' => $extraQty * $unitPrice,
                            ]);
                        }
                    } else {
                        $quote->items()->create([
                            'service_name' => "Fuego Frío / Chispas Frías ({$qty} " . ($qty == 1 ? 'máquina' : 'máquinas') . " a " . number_format($unitPrice, 0) . " €/ud)",
                            'description' => 'Efecto especial de chispas frías sin combustión para momentos clave (apertura de baile o corte de tarta).',
                            'quantity' => $qty,
                            'price' => $unitPrice,
                            'total' => $qty * $unitPrice,
                        ]);
                    }
                } elseif ($key === 'extra_hours') {
                    $qty = (int)($service['quantity'] ?: 1);
                    $price = $catalog[$key] ?? 120;
                    $hoursDesc = "Ampliación de {$qty} " . ($qty === 1 ? 'hora adicional' : 'horas adicionales') . " de fiesta/baile";
                    if (!empty($this->services['dj']['selected'])) {
                        $hoursDesc .= " (Total acumulado: {$totalPartyHours}h de baile)";
                    }
                    if ($recommendedPackForExtra) {
                        $hoursDesc .= " — 💡 Recomendable valorar {$recommendedPackForExtra['base_name']} ({$totalPartyHours}h)";
                    }
                    $quote->items()->create([
                        'service_name' => $service['name'] . " (+{$qty} " . ($qty === 1 ? 'hora' : 'horas') . ")",
                        'description' => $hoursDesc,
                        'quantity' => $qty,
                        'price' => $price,
                        'total' => $price * $qty,
                    ]);
                } elseif (in_array($key, ['pack_duo_360', 'photobooth', 'video_360', 'chocolate_fountain', 'candy_bar', 'waffle_cart'], true)) {
                    if (($key === 'photobooth' || $key === 'video_360') && !empty($this->services['pack_duo_360']['selected'])) {
                        continue;
                    }
                    $resolved = $resolveExternalPriceAndMeta($key, $service);
                    $quote->items()->create([
                        'service_name' => $resolved['item_name'],
                        'description' => $resolved['description'],
                        'quantity' => 1,
                        'price' => $resolved['price'],
                        'total' => $resolved['price'],
                    ]);
                } else {
                    $qty = (int)($service['quantity'] ?: 1);
                    $price = $catalog[$key] ?? 0;
                    $quote->items()->create([
                        'service_name' => $service['name'],
                        'description' => $service['description'] ?? null,
                        'quantity' => $qty,
                        'price' => $price,
                        'total' => $price * $qty,
                    ]);
                }
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

        if ($this->needs_prior_setup) {
            $quote->items()->create([
                'service_name' => 'Montaje Día Anterior / Ensayo General / Varios Días (A valorar)',
                'description' => 'Desplazamiento, instalación previa de equipo de sonido/iluminación o asistencia a ensayo previo según necesidades del evento.',
                'quantity' => 1,
                'price' => 0,
                'total' => 0,
            ]);
        }

        if ($quote->items()->count() === 0) {
            $quote->items()->create([
                'service_name' => 'Servicio de Sonorización y DJ a Medida (' . ($typeConfig['name'] ?? 'Evento') . ')',
                'description' => trim((string)$this->client_notes) ?: 'Propuesta personalizada según horarios y necesidades indicadas por el cliente.',
                'quantity' => 1,
                'price' => 0,
                'total' => 0,
            ]);
        }

        // 7. Enviar notificaciones por email:
        // A) A los Administradores de la Empresa y de la Marca (tolerante a fallos por destinatario)
        try {
            \App\Services\AppNotificationService::sendMailableToAdmins(
                fn () => new \App\Mail\NewQuoteRequestAdminMail($event, $quote, $requestedServicesList, $typeConfig),
                $event->brand_clean
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo enviar el email de aviso a administradores: ' . $e->getMessage());
        }

        // B) Al Cliente (Confirmación de recepción con mensaje personalizado y desglose)
        try {
            if ($client && !empty($client->email) && filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
                \Illuminate\Support\Facades\Mail::to($client->email)
                    ->send(new \App\Mail\QuoteRequestConfirmationClientMail($event, $quote, $requestedServicesList, $typeConfig));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo enviar el email de confirmación al cliente: ' . $e->getMessage());
        }

        // C) Notificación en la App (Campana + Push Navegador/PWA + Toast) y WhatsApp a los socios
        try {
            \App\Services\AppNotificationService::push(
                'quote_request',
                "💶 Presupuesto Web: {$this->client_name}",
                "{$event->name} • Total est.: " . number_format((float)$quote->total_amount, 2, ',', '.') . " €",
                url("/admin/events/{$event->id}"),
                $event->id,
                $event->brand_clean
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo registrar notificación en App de presupuesto: ' . $e->getMessage());
        }

        try {
            if (\App\Services\EvolutionWhatsAppService::isEnabled()) {
                $waExtra = "";
                if (!empty(trim((string)$this->event_schedule))) {
                    $waExtra .= "⏰ *Horario:* " . trim((string)$this->event_schedule) . "\n";
                }
                if ($this->needs_prior_setup) {
                    $waExtra .= "🔧 *Montaje previo / Ensayo:* Sí (Día anterior o varios días)\n";
                }
                if (!empty(trim((string)$this->client_notes))) {
                    $waExtra .= "📝 *Notas del cliente:* " . Str::limit(trim((string)$this->client_notes), 350) . "\n";
                }
                $waAlert = "💶 *¡NUEVA SOLICITUD DE PRESUPUESTO!*\n\n"
                    . "👤 *Cliente:* {$this->client_name}\n"
                    . "📞 *Teléfono:* " . ($this->client_phone ?: 'No indicado') . "\n"
                    . "🎉 *Evento:* {$event->name}\n"
                    . "📍 *Lugar:* {$this->event_location}\n"
                    . $waExtra
                    . "💰 *Total estimado:* " . number_format((float)$quote->total_amount, 2, ',', '.') . " €\n\n"
                    . "🔗 *Ficha:* " . url("/admin/events/{$event->id}");
                \App\Services\EvolutionWhatsAppService::notifyAdmins($waAlert, $event->brand_clean);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo enviar alerta WhatsApp a socios de presupuesto: ' . $e->getMessage());
        }

        $this->is_submitted = true;
    }

    public function render()
    {
        return view('livewire.guest.quote-calculator')
            ->layout('components.layouts.wide-guest', ['title' => 'Solicitar Presupuesto - ' . Setting::get('company_name', 'Eventos Musicales')]);
    }
}
