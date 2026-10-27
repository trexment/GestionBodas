<?php

namespace App\Livewire\Guest;

use Livewire\Component;
use App\Models\User;
use App\Models\Event;
use App\Models\Quote;
use App\Models\Setting;
use Illuminate\Support\Str;

class QuoteCalculator extends Component
{
    // Contact Info
    public $event_token = '';
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
                'icon' => '01',
                'badge' => 'Producción Nupcial',
                'hero_title' => 'Propuesta Técnica y Musical para',
                'hero_highlight' => 'Vuestra Boda',
                'hero_subtitle' => 'Seleccionad las fases del enlace que deseáis cubrir para elaborar vuestro presupuesto personalizado de sonido, microfonía e iluminación.',
                'name_label' => 'Nombre de los Novios / Pareja *',
                'name_placeholder' => 'Ej: Carlos y Laura',
                'location_label' => 'Finca, Bodega, Hotel o Localidad *',
                'location_placeholder' => 'Ej: Bodegas Franco-Españolas, Finca, Hotel...',
                'form_title' => 'Datos del Enlace',
                'form_subtitle' => 'Completad vuestros datos de contacto para remitir la propuesta económica detallada.',
                'show_schedule_box' => false,
                'show_prior_setup' => false,
                'notes_label' => 'Horarios, indicaciones o detalles del evento (opcional)',
                'notes_help' => 'Podéis detallar horarios previstos (ceremonia, cóctel, banquete o barra libre), número aproximado de invitados o necesidades técnicas:',
                'notes_placeholder' => 'Ej: Evento de 13:00 a 20:00h. Ceremonia civil con microfonía para discursos, cóctel, banquete y barra libre posterior...',
                'custom_service_title' => 'Sonorización adicional o requerimiento técnico a medida',
                'custom_service_subtitle' => 'Segundo espacio, iluminación arquitectónica, microfonía adicional para músicos...',
                'custom_service_placeholder' => 'Ej: Sonorización de segundo espacio / Microfonía adicional...',
                'custom_service_desc_placeholder' => 'Ej: Conexión de audio para músico en directo o videógrafo...',
                'ceremony_name' => 'Ceremonia Civil / Religiosa',
                'ceremony_icon' => 'I',
                'ceremony_desc' => 'Sonorización independiente, microfonía inalámbrica para lecturas y control técnico de entradas y salidas.',
                'cocktail_name' => 'Cóctel de Bienvenida',
                'cocktail_icon' => 'II',
                'cocktail_desc' => 'Sistema de sonido independiente y selección musical ambiental para la recepción de invitados.',
                'restaurant_name' => 'Banquete y Momentos Especiales',
                'restaurant_icon' => 'III',
                'restaurant_desc' => 'Ambientación en salón, microfonía inalámbrica y coordinación musical de entradas y entregas.',
                'dj_name' => 'Producción DJ y Barra Libre',
                'dj_icon' => 'IV',
                'dj_desc' => 'Sesión DJ en directo, sistema de sonido de alta presión acústica e iluminación robotizada.',
                'prefix' => 'Boda ',
            ],
            'empresa' => [
                'name' => 'Corporativo / Empresa',
                'icon' => '02',
                'badge' => 'Eventos Corporativos',
                'hero_title' => 'Producción Audiovisual y DJ para',
                'hero_highlight' => 'Eventos Corporativos',
                'hero_subtitle' => 'Soluciones de sonido, microfonía para ponencias, ambientación de recepciones y producción musical para empresas.',
                'name_label' => 'Empresa / Persona de Contacto *',
                'name_placeholder' => 'Ej: Innova Tech S.L. / Roberto Pérez',
                'location_label' => 'Sede, Hotel, Palacio de Congresos o Localidad *',
                'location_placeholder' => 'Ej: Riojaforum, Bodega, Hotel, Sede corporativa...',
                'form_title' => 'Datos del Evento Corporativo',
                'form_subtitle' => 'Indicad los datos de contacto y los requisitos técnicos o de horario.',
                'show_schedule_box' => true,
                'schedule_label' => 'Horario aproximado o escaleta del evento (opcional)',
                'schedule_placeholder' => 'Ej: Ponencias de 18:00 a 19:30h, cóctel a las 20:00h y cierre musical...',
                'show_prior_setup' => true,
                'prior_setup_label' => 'Requiere montaje previo, pruebas técnicas o cobertura en varias jornadas',
                'prior_setup_sublabel' => '(congresos, pruebas de sonido previas a apertura, etc.)',
                'notes_label' => 'Especificaciones técnicas, aforo o detalles del acto',
                'notes_help' => 'Indicad el aforo estimado, necesidades de microfonía (mano/diadema/atril) o detalles de producción:',
                'notes_placeholder' => 'Ej: Cena anual para 120 asistentes. Precisamos 2 micrófonos inalámbricos para intervenciones y 3 horas de sesión musical...',
                'custom_service_title' => 'Requerimiento técnico adicional o montaje a medida',
                'custom_service_subtitle' => 'Sonorización multizona, iluminación corporativa, envío de audio a prensa/vídeo...',
                'custom_service_placeholder' => 'Ej: Microfonía para mesa redonda / Iluminación corporativa...',
                'custom_service_desc_placeholder' => 'Ej: Montaje a las 16:00h y prueba técnica previa...',
                'ceremony_name' => 'Sonorización de Ponencias / Acto Institucional',
                'ceremony_icon' => 'I',
                'ceremony_desc' => 'Megafonía de alta inteligibilidad, microfonía inalámbrica y control técnico durante las intervenciones.',
                'cocktail_name' => 'Recepción y Networking',
                'cocktail_icon' => 'II',
                'cocktail_desc' => 'Sonorización ambiental equilibrada para recepciones profesionales y cócteles.',
                'restaurant_name' => 'Cena de Gala / Banquete Corporativo',
                'restaurant_icon' => 'III',
                'restaurant_desc' => 'Cobertura acústica durante el banquete, intervenciones, entregas de reconocimientos o sorteos.',
                'dj_name' => 'Sesión DJ y Producción de Fiesta',
                'dj_icon' => 'IV',
                'dj_desc' => 'Sesión musical en directo con equipamiento de sonido e iluminación profesional.',
                'prefix' => 'Evento Empresa: ',
            ],
            'cumpleanos' => [
                'name' => 'Fiesta Privada / Aniversario',
                'icon' => '03',
                'badge' => 'Eventos Privados',
                'hero_title' => 'Sonido, Iluminación y DJ para',
                'hero_highlight' => 'Celebraciones Privadas',
                'hero_subtitle' => 'Producción musical a medida, sonorización de espacios y sesión DJ en directo para aniversarios y eventos privados.',
                'name_label' => 'Nombre del Organizador / Homenajeado *',
                'name_placeholder' => 'Ej: Aniversario de David / María',
                'location_label' => 'Restaurante, Finca, Sala o Localidad *',
                'location_placeholder' => 'Ej: Finca privada, Restaurante, Bodega, Hotel...',
                'form_title' => 'Datos de la Celebración',
                'form_subtitle' => 'Completad vuestros datos para elaborar el presupuesto personalizado.',
                'show_schedule_box' => true,
                'schedule_label' => 'Horario previsto de la celebración (opcional)',
                'schedule_placeholder' => 'Ej: De 18:00 a 23:00h / Cena y fiesta posterior...',
                'show_prior_setup' => false,
                'notes_label' => 'Detalles, horarios o preferencias musicales (opcional)',
                'notes_help' => 'Indicad el número aproximado de asistentes, horarios o preferencias de estilo musical:',
                'notes_placeholder' => 'Ej: Celebración para 70 invitados. Buscamos ambientación durante la recepción y sesión DJ posterior...',
                'custom_service_title' => 'Equipamiento adicional o servicio a medida',
                'custom_service_subtitle' => 'Sonido para proyecciones, microfonía adicional, iluminación ambiental...',
                'custom_service_placeholder' => 'Ej: Sonido para proyección / Iluminación adicional...',
                'custom_service_desc_placeholder' => 'Ej: Micrófono inalámbrico para intervenciones...',
                'ceremony_name' => 'Sonorización de Intervenciones / Proyecciones',
                'ceremony_icon' => 'I',
                'ceremony_desc' => 'Equipo de sonido y microfonía inalámbrica para discursos, presentaciones o proyecciones.',
                'cocktail_name' => 'Recepción de Invitados',
                'cocktail_icon' => 'II',
                'cocktail_desc' => 'Equipo de sonido independiente y selección musical de bienvenida.',
                'restaurant_name' => 'Comida / Cena',
                'restaurant_icon' => 'III',
                'restaurant_desc' => 'Ambientación musical durante el banquete y momentos destacados.',
                'dj_name' => 'Sesión DJ y Baile',
                'dj_icon' => 'IV',
                'dj_desc' => 'Sesión DJ en directo con repertorio adaptado y sistema completo de sonido e iluminación.',
                'prefix' => 'Cumpleaños: ',
            ],
            'comunion' => [
                'name' => 'Comunión / Evento Familiar',
                'icon' => '04',
                'badge' => 'Celebraciones Familiares',
                'hero_title' => 'Producción Musical y Animación para',
                'hero_highlight' => 'Celebraciones Familiares',
                'hero_subtitle' => 'Equipamiento profesional de sonido, iluminación y selección musical adaptada a todas las edades.',
                'name_label' => 'Nombre de la Familia / Contacto *',
                'name_placeholder' => 'Ej: Comunión de Mateo / Familia Gómez',
                'location_label' => 'Restaurante, Finca, Hotel o Localidad *',
                'location_placeholder' => 'Ej: Restaurante, Finca, Hotel, Logroño...',
                'form_title' => 'Datos de la Celebración',
                'form_subtitle' => 'Completad vuestros datos para recibir la propuesta económica detallada.',
                'show_schedule_box' => true,
                'schedule_label' => 'Horario aproximado de la celebración (opcional)',
                'schedule_placeholder' => 'Ej: Comida a las 14:30h y sesión musical de 17:30 a 20:30h...',
                'show_prior_setup' => false,
                'notes_label' => 'Observaciones o detalles del evento (opcional)',
                'notes_help' => 'Indicad el número aproximado de asistentes, horarios previstos o necesidades particulares:',
                'notes_placeholder' => 'Ej: Celebración familiar para 50 invitados en restaurante con sesión musical por la tarde...',
                'custom_service_title' => 'Servicio adicional o configuración a medida',
                'custom_service_subtitle' => 'Espectáculo de ilusionismo, sonorización de zona exterior, extras...',
                'custom_service_placeholder' => 'Ej: Espectáculo de Magia / Sonorización adicional...',
                'custom_service_desc_placeholder' => 'Ej: Después del banquete en salón principal...',
                'ceremony_name' => 'Sonorización de Recepción / Acto',
                'ceremony_icon' => 'I',
                'ceremony_desc' => 'Música para entradas y microfonía inalámbrica para intervenciones familiares.',
                'cocktail_name' => 'Aperitivo / Bienvenida',
                'cocktail_icon' => 'II',
                'cocktail_desc' => 'Música ambiental para la recepción de invitados en jardín o salón.',
                'restaurant_name' => 'Comida y Sobremesa',
                'restaurant_icon' => 'III',
                'restaurant_desc' => 'Ambientación musical durante el banquete y entregas.',
                'dj_name' => 'Sesión DJ y Fiesta Familiar',
                'dj_icon' => 'IV',
                'dj_desc' => 'Sesión musical en directo con equipo de sonido e iluminación profesional.',
                'prefix' => 'Comunión: ',
            ],
            'otro' => [
                'name' => 'Institucional / Otros Eventos',
                'icon' => '05',
                'badge' => 'Producción Técnica a Medida',
                'hero_title' => 'Sonorización, Iluminación y DJ para',
                'hero_highlight' => 'Actos e Instituciones',
                'hero_subtitle' => 'Cobertura técnica para centros educativos, asociaciones, ayuntamientos, galas y eventos a medida.',
                'name_label' => 'Entidad / Organización / Persona de Contacto *',
                'name_placeholder' => 'Ej: Colegio San Fernando / Asociación Cultural',
                'location_label' => 'Recinto, Pabellón, Salón o Localidad *',
                'location_placeholder' => 'Ej: Polideportivo, Salón de Actos, Recinto, Plaza...',
                'form_title' => 'Datos y Especificaciones del Evento',
                'form_subtitle' => 'Indicad los datos de contacto, horarios y requisitos técnicos.',
                'show_schedule_box' => true,
                'schedule_label' => 'Horario aproximado o tramo del evento (opcional)',
                'schedule_placeholder' => 'Ej: De 17:00 a 20:30h / Jornada completa...',
                'show_prior_setup' => true,
                'prior_setup_label' => 'Requiere montaje el día anterior, ensayo general o varios días',
                'prior_setup_sublabel' => '(festivales, pruebas técnicas previas, eventos de varios días)',
                'notes_label' => 'Memoria técnica, horarios o descripción del evento',
                'notes_help' => 'Detallad las características del recinto, horarios de montaje/ensayo o necesidades específicas:',
                'notes_placeholder' => 'Ej: Sonorización e iluminación para acto en pabellón. Montaje y prueba el día previo, evento de 17:30 a 20:30h...',
                'custom_service_title' => 'Montaje técnico especial o equipamiento adicional',
                'custom_service_subtitle' => 'Sonorización de gran formato, microfonía múltiple, iluminación escénica...',
                'custom_service_placeholder' => 'Ej: Sonorización de escenario / Microfonía inalámbrica extra...',
                'custom_service_desc_placeholder' => 'Ej: Montaje previo + asistencia técnica durante el acto...',
                'ceremony_name' => 'Sonorización Técnica de Acto / Escenario',
                'ceremony_icon' => 'I',
                'ceremony_desc' => 'Sistema de sonido profesional, microfonía y asistencia técnica para actuaciones o ponencias.',
                'cocktail_name' => 'Recepción / Ambientación Exterior',
                'cocktail_icon' => 'II',
                'cocktail_desc' => 'Sistema de sonido independiente para zonas de recepción o apertura.',
                'restaurant_name' => 'Comida / Cena / Gala',
                'restaurant_icon' => 'III',
                'restaurant_desc' => 'Ambientación musical y cobertura de microfonía durante el banquete.',
                'dj_name' => 'Sesión DJ y Cierre Musical',
                'dj_icon' => 'IV',
                'dj_desc' => 'Producción completa de sonido, iluminación robotizada y sesión DJ en directo.',
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
            $this->services['ceremony']['icon'] = $typeConfig['ceremony_icon'] ?? 'I';
        }

        if (isset($this->services['cocktail'])) {
            $this->services['cocktail']['name'] = $typeConfig['cocktail_name'];
            $this->services['cocktail']['description'] = $typeConfig['cocktail_desc'];
            $this->services['cocktail']['icon'] = $typeConfig['cocktail_icon'] ?? 'II';
        }

        if (isset($this->services['restaurant'])) {
            $this->services['restaurant']['name'] = $typeConfig['restaurant_name'];
            $this->services['restaurant']['description'] = $typeConfig['restaurant_desc'];
            $this->services['restaurant']['icon'] = $typeConfig['restaurant_icon'] ?? 'III';
        }

        if (isset($this->services['dj'])) {
            $this->services['dj']['name'] = $typeConfig['dj_name'];
            $this->services['dj']['description'] = $typeConfig['dj_desc'];
            $this->services['dj']['icon'] = $typeConfig['dj_icon'] ?? 'IV';
        }

        if (empty($typeConfig['show_prior_setup'])) {
            $this->needs_prior_setup = false;
        }
        if (empty($typeConfig['show_schedule_box'])) {
            $this->event_schedule = '';
        }
    }

    public function mount()
    {
        $initialType = request()->get('tipo', 'boda');
        $types = self::getEventTypes();
        $this->event_type = isset($types[$initialType]) ? $initialType : 'boda';

        $tokenParam = trim((string) (request()->query('evento') ?: request()->query('token') ?: ''));
        if ($tokenParam !== '') {
            $linkedEvent = Event::with('client')->where('token', $tokenParam)->first();
            if ($linkedEvent) {
                $this->event_token = $linkedEvent->token;
                $evTypeClean = $linkedEvent->event_type_clean ?? 'boda';
                if (isset($types[$evTypeClean])) {
                    $this->event_type = $evTypeClean;
                }
                if ($linkedEvent->client) {
                    $this->client_name = $linkedEvent->client->name ?: '';
                    $this->client_phone = $linkedEvent->client->phone ?: ($linkedEvent->partner_phone ?: '');
                    $rawMail = (string) ($linkedEvent->client->email ?? '');
                    if ($rawMail !== '' && !str_ends_with($rawMail, '.local') && !str_starts_with($rawMail, 'sin-email-')) {
                        $this->client_email = $rawMail;
                    } elseif (!empty($linkedEvent->partner_email)) {
                        $this->client_email = $linkedEvent->partner_email;
                    }
                } else {
                    $this->client_name = $linkedEvent->partner_name ?: '';
                    $this->client_phone = $linkedEvent->partner_phone ?: '';
                    $this->client_email = $linkedEvent->partner_email ?: '';
                }
                if ($linkedEvent->event_date) {
                    $this->event_date = \Carbon\Carbon::parse($linkedEvent->event_date)->format('Y-m-d');
                }
                if (!empty($linkedEvent->location) && $linkedEvent->location !== 'Por definir') {
                    $this->event_location = $linkedEvent->location;
                }
            }
        }

        $this->services = [
            'ceremony' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => 'I',
                'name' => '', 
                'description' => ''
            ],
            'cocktail' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => 'II',
                'name' => '', 
                'description' => ''
            ],
            'restaurant' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => 'III',
                'name' => '', 
                'description' => ''
            ],
            'dj' => [
                'selected' => true,  
                'quantity' => 4, 
                'icon' => 'IV',
                'name' => '', 
                'description' => ''
            ],
            'smoke_machine' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => 'HM',
                'name' => 'Máquina de Humo', 
                'description' => 'Generador de niebla escénica para ambientar la pista y definir los haces de iluminación.',
                'included' => false,
            ],
            'cold_sparks' => [
                'selected' => false, 
                'quantity' => 2, 
                'icon' => 'CF',
                'name' => 'Fuego Frío / Chispas Frías', 
                'description' => 'Sistema electrónico de proyección de chispas frías sin combustión para apertura de baile o momentos especiales.',
                'included' => false,
            ],
            'karaoke' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => 'KR',
                'name' => 'Sistema de Karaoke', 
                'description' => 'Repertorio audiovisual interactivo y microfonía inalámbrica adicional.'
            ],
            'extra_hours' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '+H',
                'name' => 'Ampliación de Horario (Horas Extra)', 
                'description' => 'Extensión de la cobertura técnica y sesión DJ más allá del bloque principal.'
            ],
            'pack_duo_360' => [
                'selected' => false,
                'quantity' => 1,
                'hours' => 2,
                'icon' => 'DÚO',
                'name' => 'Pack Dúo (Fotomatón + Plataforma 360º)',
                'description' => 'Configuración conjunta con Fotomatón de impresión ilimitada y álbum de firmas + Plataforma de Vídeo 360º con asistencia.'
            ],
            'photobooth' => [
                'selected' => false, 
                'quantity' => 1,
                'hours' => 2,
                'icon' => 'FM',
                'name' => 'Fotomatón y Photocall', 
                'description' => 'Cámara réflex profesional, copias impresas ilimitadas, atrezzo, galería digital USB, técnico asistente y álbum de firmas.'
            ],
            'video_360' => [
                'selected' => false,
                'quantity' => 1,
                'hours' => 2,
                'icon' => '360',
                'name' => 'Plataforma de Vídeo 360º',
                'description' => 'Plataforma circular con grabación en cámara lenta y descarga instantánea mediante código QR, iluminación y asistente.'
            ],
            'chocolate_fountain' => [
                'selected' => false,
                'quantity' => 1,
                'fountains' => 1,
                'hours' => 2,
                'waffle_corner' => false,
                'icon' => 'FC',
                'name' => 'Mesa Dulce con Fuente de Chocolate',
                'description' => 'Fuente de chocolate belga a elegir, selección de fruta fresca, repostería variada, golosinas y montaje decorativo con reposición.'
            ],
            'candy_bar' => [
                'selected' => false,
                'quantity' => 1,
                'icon' => 'CB',
                'name' => 'Mesa Dulce (Candy Bar)',
                'description' => 'Surtido seleccionado de confitería y golosinas con presentación y decoración adaptada al estilo del evento.'
            ],
            'waffle_cart' => [
                'selected' => false,
                'quantity' => 1,
                'hours' => 2,
                'icon' => 'GF',
                'name' => 'Estación de Gofres en Directo',
                'description' => 'Elaboración de gofres al momento con selección de coberturas y toppings variados.'
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
        $clientServicesList = [];
        $recommendedPackForExtra = $this->recommended_dj_pack_for_extra_hours;
        $totalPartyHours = $this->total_party_hours;

        foreach ($this->services as $key => $service) {
            if ($service['selected']) {
                if ($key === 'dj' && $djPack) {
                    $requestedServicesList[] = "• " . $service['name'] . " (" . $djPack['requested_hours'] . "h) ➔ " . $djPack['badge'];
                    $clientServicesList[] = $service['name'] . " (" . $djPack['requested_hours'] . " horas — " . ($djPack['base_name'] ?? 'Configuración DJ') . ")";
                } elseif ($key === 'smoke_machine') {
                    if (!empty($service['included'])) {
                        $requestedServicesList[] = "• " . $service['name'] . " (Incluido en " . ($djPack['base_name'] ?? 'Pack') . ")";
                        $clientServicesList[] = $service['name'] . " (Incluido en " . ($djPack['base_name'] ?? 'Pack') . ")";
                    } else {
                        $requestedServicesList[] = "• " . $service['name'] . " (60 €)";
                        $clientServicesList[] = $service['name'];
                    }
                } elseif ($key === 'cold_sparks') {
                    $qty = (int)($service['quantity'] ?: 1);
                    $unitPrice = $catalog['cold_sparks'] ?? 60;
                    if (!empty($service['included'])) {
                        $extraQty = max(0, $qty - 2);
                        $extraText = $extraQty > 0 ? " (2 máq. incluidas en " . ($djPack['base_name'] ?? 'Pack') . " + {$extraQty} extra a {$unitPrice} €/ud)" : " (2 máq. incluidas en " . ($djPack['base_name'] ?? 'Pack') . ")";
                        $requestedServicesList[] = "• " . $service['name'] . " ({$qty} " . ($qty == 1 ? 'máquina' : 'máquinas') . "){$extraText}";
                        $clientServicesList[] = $service['name'] . " ({$qty} " . ($qty == 1 ? 'unidad' : 'unidades') . " — 2 incluidas en " . ($djPack['base_name'] ?? 'Pack') . ")";
                    } else {
                        $requestedServicesList[] = "• " . $service['name'] . " ({$qty} " . ($qty == 1 ? 'máquina' : 'máquinas') . " x {$unitPrice} €/ud)";
                        $clientServicesList[] = $service['name'] . " ({$qty} " . ($qty == 1 ? 'unidad' : 'unidades') . ")";
                    }
                } elseif ($key === 'extra_hours') {
                    $qty = (int)($service['quantity'] ?: 1);
                    $hoursLabel = $qty === 1 ? '1 hora adicional' : "{$qty} horas adicionales";
                    $totalHoursNote = !empty($this->services['dj']['selected']) ? " ➔ Total baile: {$totalPartyHours}h" : "";
                    $recNote = $recommendedPackForExtra ? " (Recomendado: {$recommendedPackForExtra['base_name']} que ya cubre {$totalPartyHours}h)" : "";
                    $requestedServicesList[] = "• " . $service['name'] . " (+{$hoursLabel}{$totalHoursNote}){$recNote}";
                    $clientServicesList[] = $service['name'] . " (+{$hoursLabel}" . (!empty($this->services['dj']['selected']) ? " · Total: {$totalPartyHours}h" : "") . ")";
                } elseif (in_array($key, ['pack_duo_360', 'photobooth', 'video_360', 'chocolate_fountain', 'candy_bar', 'waffle_cart'], true)) {
                    if (($key === 'photobooth' || $key === 'video_360') && !empty($this->services['pack_duo_360']['selected'])) {
                        continue;
                    }
                    $resolved = $resolveExternalPriceAndMeta($key, $service);
                    $requestedServicesList[] = "• " . $resolved['note_label'];
                    $clientServicesList[] = $resolved['item_name'];
                } else {
                    $qty = (int)($service['quantity'] ?: 1);
                    $qtyText = $qty > 1 ? " ({$qty} uds)" : "";
                    $requestedServicesList[] = "• " . $service['name'] . $qtyText;
                    $clientServicesList[] = $service['name'] . $qtyText;
                }
            }
        }

        if ($packInfo && !empty($packInfo['enabled']) && $packInfo['discount_amount'] > 0) {
            $requestedServicesList[] = "• Pack Cóctel + Banquete incluido (" . $packInfo['savings_label'] . ")";
            $clientServicesList[] = "Modalidad conjunta: Pack Cóctel + Banquete";
        }

        if ($this->custom_service_selected && !empty(trim($this->custom_service_name))) {
            $customName = trim($this->custom_service_name);
            $customDesc = trim($this->custom_service_description);
            $requestedServicesList[] = "• " . $customName . " (A consultar)" . ($customDesc ? " - " . $customDesc : "");
            $clientServicesList[] = $customName . ($customDesc ? " (" . $customDesc . ")" : "");
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

        $cleanClientNotes = trim((string)$this->client_notes);
        $cleanSchedule = trim((string)$this->event_schedule);

        $scheduleAndSetupNotes = "";
        if ($cleanSchedule !== '') {
            $scheduleAndSetupNotes .= "⏰ Horario / Tramo del Evento: " . $cleanSchedule . "\n";
        }
        if ($this->needs_prior_setup) {
            $scheduleAndSetupNotes .= "🔧 Montaje Especial: SÍ (Requiere montaje el día anterior / ensayo previo / varios días)\n";
        }

        $quoteClientNotesParts = [];
        if ($cleanClientNotes !== '') {
            $quoteClientNotesParts[] = $cleanClientNotes;
        }
        if ($cleanSchedule !== '') {
            $quoteClientNotesParts[] = "⏰ Horario indicado: {$cleanSchedule}";
        }
        if ($this->needs_prior_setup) {
            $quoteClientNotesParts[] = "🔧 Requiere montaje previo / ensayo";
        }
        if (!empty($requestedServicesList)) {
            $quoteClientNotesParts[] = "📌 Servicios marcados en /presupuesto:\n" . implode("\n", $requestedServicesList);
        }
        $quoteClientNotes = implode("\n\n", $quoteClientNotesParts);

        $stampNow = now()->format('d/m/Y H:i');
        $fullNotes = "SOLICITUD DE PRESUPUESTO ONLINE (/presupuesto) [{$stampNow}]:\n"
                   . "Tipo de Evento: " . $typeConfig['name'] . "\n"
                   . ($cleanClientNotes !== '' ? "\n📝 NOTAS Y EXPLICACIÓN DEL CLIENTE:\n" . $cleanClientNotes . "\n\n" : "")
                   . $scheduleAndSetupNotes
                   . "Servicios Solicitados:\n"
                   . implode("\n", $requestedServicesList);

        // 4. Localizar evento existente (por token, por cliente, por teléfono/email de pareja o por fecha+nombre) para no perder ni desincronizar datos
        $event = null;
        if (!empty($this->event_token)) {
            $event = Event::where('token', $this->event_token)->first();
        }

        if (!$event) {
            $event = Event::where('client_id', $client->id)
                ->where(function ($q) {
                    if (!empty($this->event_date)) {
                        $q->whereDate('event_date', $this->event_date)
                          ->orWhereNotIn('status', ['cancelled', 'rejected', 'completed']);
                    } else {
                        $q->whereNotIn('status', ['cancelled', 'rejected', 'completed']);
                    }
                })
                ->latest()
                ->first();
        }

        $digitsPhone = preg_replace('/\D/', '', (string) $this->client_phone);
        $last9Phone = strlen($digitsPhone) >= 9 ? substr($digitsPhone, -9) : '';

        if (!$event && $last9Phone !== '') {
            $event = Event::where(function ($q) use ($last9Phone) {
                    $q->where('partner_phone', 'LIKE', "%{$last9Phone}%")
                      ->orWhereHas('client', function ($cq) use ($last9Phone) {
                          $cq->where('phone', 'LIKE', "%{$last9Phone}%");
                      });
                })
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->latest()
                ->first();
        }

        if (!$event && !empty($cleanEmail)) {
            $event = Event::where(function ($q) use ($cleanEmail) {
                    $q->where('partner_email', $cleanEmail)
                      ->orWhereHas('client', function ($cq) use ($cleanEmail) {
                          $cq->where('email', $cleanEmail);
                      });
                })
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->latest()
                ->first();
        }

        if ($event) {
            $eventUpdates = [
                'event_date' => $this->event_date ?: $event->event_date,
                'location' => (!empty($this->event_location) && $this->event_location !== 'Por definir') ? $this->event_location : $event->location,
                'notes' => $fullNotes . ($event->notes ? "\n\n========================================\n" . $event->notes : ''),
            ];
            if (!$event->client_id && $client) {
                $eventUpdates['client_id'] = $client->id;
            }
            if ($cleanClientNotes !== '' || $cleanSchedule !== '') {
                $schedBlock = trim(($cleanSchedule !== '' ? "Horario: {$cleanSchedule}. " : '') . $cleanClientNotes);
                $existingSched = trim((string) $event->schedule_notes);
                if ($schedBlock !== '' && !str_contains($existingSched, $schedBlock)) {
                    $eventUpdates['schedule_notes'] = $existingSched !== '' ? ($schedBlock . "\n" . $existingSched) : $schedBlock;
                }
            }
            $event->update($eventUpdates);
        } else {
            $initialSchedNotes = trim(($cleanSchedule !== '' ? "Horario: {$cleanSchedule}. " : '') . $cleanClientNotes);
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
                'schedule_notes' => $initialSchedNotes !== '' ? $initialSchedNotes : null,
            ]);
        }

        // 4b. Guardar SIEMPRE las opciones seleccionadas (Ceremonia, Cóctel, Banquete, DJ, etc.) y las Notas del cliente en el DOSSIER del evento
        try {
            $formattedDate = $this->event_date ? \Carbon\Carbon::parse($this->event_date)->format('d/m/Y') : 'Por definir';
            $dossierBlock = "### 📋 Solicitud Recibida desde /presupuesto ({$stampNow})\n"
                . "**Cliente:** {$this->client_name} (" . ($this->client_phone ?: 'Sin tel.') . ")\n"
                . "**Tipo de Evento:** {$typeConfig['name']} | **Fecha:** {$formattedDate} | **Lugar:** {$this->event_location}\n\n"
                . "**✅ Servicios y Opciones Marcadas por el Cliente:**\n"
                . implode("\n", $requestedServicesList) . "\n";

            if ($cleanSchedule !== '') {
                $dossierBlock .= "\n**⏰ Horario Indicado:** {$cleanSchedule}\n";
            }
            if ($this->needs_prior_setup) {
                $dossierBlock .= "**🔧 Montaje Especial:** Requiere montaje el día anterior / ensayo previo / varios días\n";
            }
            if ($cleanClientNotes !== '') {
                $dossierBlock .= "\n**📝 Notas, Horarios y Especificaciones del Cliente:**\n{$cleanClientNotes}\n";
            }
            $dossierBlock .= "\n---\n\n";

            $existingDossier = $event->dossiers()->first();
            if ($existingDossier) {
                $currentContent = trim((string) $existingDossier->content);
                $existingDossier->update([
                    'content' => $currentContent !== '' ? ($dossierBlock . $currentContent) : trim($dossierBlock),
                ]);
            } else {
                $event->dossiers()->create([
                    'content' => trim($dossierBlock),
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error guardando dossier desde QuoteCalculator: ' . $e->getMessage());
        }

        // Localizar automáticamente el teléfono del restaurante / bodega / finca si se ha indicado lugar
        try {
            \App\Services\VenueLookupService::autoPopulateEventVenuePhone($event);
        } catch (\Throwable $e) {}

        // 5. Create Quote Draft for Admin (incluyendo las notas del cliente)
        Quote::EnsureQuoteResponseColumns();
        $quote = $event->quotes()->create([
            'brand' => $event->brand ?: Setting::getDetectedBrand(),
            'amount' => $totalEstimated,
            'status' => 'pending',
            'client_notes' => $quoteClientNotes !== '' ? $quoteClientNotes : null,
        ]);

        // Registrar también en la Conversación Registrada del evento para que quede visible en el historial
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('whatsapp_messages')) {
                $chatBody = "📋 *Solicitud desde Calculador de Presupuesto (/solicitar-presupuesto)*\n"
                    . "🎉 *Tipo:* {$typeConfig['name']}\n"
                    . "📍 *Lugar:* {$this->event_location}\n"
                    . ($cleanSchedule !== '' ? "⏰ *Horario:* {$cleanSchedule}\n" : "")
                    . ($this->needs_prior_setup ? "🔧 *Montaje previo / ensayo:* Sí\n" : "")
                    . ($cleanClientNotes !== '' ? "\n📝 *Notas del cliente:*\n{$cleanClientNotes}\n" : "")
                    . "\n*Servicios seleccionados:*\n" . implode("\n", $requestedServicesList);

                \App\Models\WhatsappMessage::create([
                    'event_id' => $event->id,
                    'user_id' => null,
                    'phone' => preg_replace('/\D/', '', (string)$this->client_phone) ?: $this->client_email,
                    'sender_name' => $this->client_name,
                    'direction' => 'inbound',
                    'message' => $chatBody,
                    'status' => 'received',
                    'payload' => ['channel' => 'web_quote_calculator', 'quote_id' => $quote->id],
                ]);
            }
        } catch (\Throwable $e) {}

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

        $detectedBrand = Setting::getDetectedBrand();
        $notifyBrand = ($detectedBrand === 'javnx' || $event->brand_clean === 'javnx') ? 'javnx' : $event->brand_clean;

        // 7. Enviar notificaciones por email:
        // A) A los Administradores de la Empresa y de la Marca (tolerante a fallos por destinatario)
        try {
            \App\Services\AppNotificationService::sendMailableToAdmins(
                fn () => new \App\Mail\NewQuoteRequestAdminMail(
                    $event,
                    $quote,
                    $requestedServicesList,
                    $typeConfig,
                    $cleanClientNotes,
                    $cleanSchedule,
                    (bool)$this->needs_prior_setup
                ),
                $notifyBrand
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo enviar el email de aviso a administradores: ' . $e->getMessage());
        }

        // B) Al Cliente (Confirmación de recepción con servicios seleccionados y observaciones, SIN precios)
        try {
            if ($client && !empty($client->email) && filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
                \Illuminate\Support\Facades\Mail::to($client->email)
                    ->send(new \App\Mail\QuoteRequestConfirmationClientMail(
                        $event,
                        $quote,
                        $clientServicesList,
                        $typeConfig,
                        $cleanClientNotes,
                        $cleanSchedule,
                        (bool)$this->needs_prior_setup
                    ));
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
                $notifyBrand
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
                \App\Services\EvolutionWhatsAppService::notifyAdmins($waAlert, $notifyBrand);
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
