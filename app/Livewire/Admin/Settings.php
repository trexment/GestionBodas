<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;
use App\Services\ContractTemplateService;
use App\Services\DossierTemplateService;
use App\Services\MusicSearchService;
use App\Services\SpotifyService;
use Illuminate\Support\Facades\Storage;

class Settings extends Component
{
    use WithFileUploads;

    public $activeTab = 'general';

    // Empresa
    public $company_name;
    public $company_subtitle;
    public $company_phone;
    public $company_cif;
    public $company_email;
    public $company_address;
    public $company_city;
    public $company_season;
    public $public_website_url;
    
    // Perfiles de Marca (Núñez and Son & JAVNX DJ)
    public $brand_nunez_name;
    public $brand_nunez_subtitle;
    public $brand_nunez_phone;
    public $brand_nunez_phone_2;
    public $brand_nunez_email;
    public $brand_nunez_website;

    public $brand_javnx_name;
    public $brand_javnx_subtitle;
    public $brand_javnx_phone;
    public $brand_javnx_phone_2;
    public $brand_javnx_email;
    public $brand_javnx_website;

    public $brand_leugim_name;
    public $brand_leugim_subtitle;
    public $brand_leugim_phone;
    public $brand_leugim_phone_2;
    public $brand_leugim_email;
    public $brand_leugim_website;

    // Marcas donde el servicio de videógrafo está habilitado
    public $video_service_brands = ['javnx'];
    
    // Precios base y Packs Comerciales
    public $pack_basic_name;
    public $pack_basic_price;
    public $pack_basic_hours;
    public $pack_basic_desc;

    public $pack_medium_name;
    public $pack_medium_price;
    public $pack_medium_hours;
    public $pack_medium_desc;

    public $pack_premium_name;
    public $pack_premium_price;
    public $pack_premium_hours;
    public $pack_premium_desc;

    // Servicios adicionales & Fotografía
    public $price_ceremony;
    public $price_cocktail;
    public $price_restaurant;
    public $price_dj;
    public $price_karaoke;
    public $price_extra_hours;
    public $photobooth_settings_tab = 'logrono';
    public $default_photobooth_provider = 'logrono';
    public array $photobooth_providers = [];
    public bool $show_add_provider_form = false;
    public string $new_provider_name = '';
    public string $new_provider_zone = '';
    public string $new_provider_contact = '';

    public $photobooth_logrono_name = 'Logroño';
    public $price_photobooth;
    public $price_photobooth_2h = 430;
    public $price_photobooth_3h = 480;
    public $price_photobooth_extra_hour = 60;
    public $photobooth_desc;
    public $price_video_360_2h = 430;
    public $price_video_360_3h = 490;
    public $price_video_360_extra_hour = 60;
    public $video_360_desc;
    public $price_pack_duo_2h = 690;
    public $price_pack_duo_3h = 850;
    public $pack_duo_desc;
    public $photobooth_deposit_per_service = 100;

    // Proveedor 2: Vitoria / Álava (FotomatonCheese)
    public $photobooth_vitoria_name = 'Vitoria (FotomatonCheese)';
    public $price_photobooth_vitoria_2h = 350;
    public $price_photobooth_vitoria_3h = 420;
    public $price_photobooth_vitoria_extra_hour = 70;
    public $photobooth_vitoria_desc;
    public $price_video_360_vitoria_2h = 390;
    public $price_video_360_vitoria_3h = 490;
    public $price_video_360_vitoria_extra_hour = 100;
    public $video_360_vitoria_desc;
    public $price_pack_duo_vitoria_2h = 690;
    public $price_pack_duo_vitoria_3h = 850;
    public $pack_duo_vitoria_desc;
    public $photobooth_vitoria_deposit_per_service = 100;

    public $price_chocolate_fountain = 420;
    public $price_chocolate_fountain_1_2h = 420;
    public $price_chocolate_fountain_1_3h = 520;
    public $price_chocolate_fountain_2_2h = 490;
    public $price_chocolate_fountain_2_3h = 590;
    public $price_chocolate_fountain_waffle_extra = 2.90;
    public $chocolate_fountain_desc;
    public $price_candy_bar = 420;
    public $candy_bar_desc;
    public $price_waffle_cart_2h = 420;
    public $price_waffle_cart_3h = 550;
    public $waffle_cart_desc;
    public $price_effects_smoke;
    public $price_effects_sparks;
    public $pack_cocktail_restaurant_enabled = true;
    public $pack_cocktail_restaurant_discount_type = 'percentage'; // 'percentage' | 'fixed_price'
    public $pack_cocktail_restaurant_discount_percentage = 25;
    public $pack_cocktail_restaurant_price = 220;
    public $price_photo_ceremony;
    public $price_photo_restaurant;
    public $price_photo_party;
    public $price_photo_album;
    public $price_photo_full_pack;

    // Datos de pago / reserva y Señal
    public $company_iban;
    public $company_bizum;
    public $deposit_type = 'percentage'; // 'percentage' | 'fixed'
    public $deposit_percentage = 40;
    public $deposit_fixed_amount = 200;
    
    // Contratos
    public $contract_title;
    public $contract_body;
    public $contract_footer;
    public $selectedPreset = 'servicio_dj';

    // Dossier y Propuestas Comerciales
    public $dossier_cover_title;
    public $dossier_page2_subtitle;
    public $dossier_page2_title;
    public $dossier_intro_text;
    public $dossier_work_title;
    public $dossier_work_item1;
    public $dossier_work_item2;
    public $dossier_work_item3;
    public $dossier_extra_hours_title;
    public $dossier_extra_hours_desc;
    public $dossier_music_custom_title;
    public $dossier_music_custom_desc;
    public $quote_validity_days = 15;
    public $selectedDossierPreset = 'bodas';

    // Fotos de Montajes y Equipamiento del Dossier (1 a 4 fotos)
    public $dossier_photo1_image;
    public $dossier_photo1_image_upload;
    public $dossier_photo1_title;
    public $dossier_photo1_desc;

    public $dossier_photo2_image;
    public $dossier_photo2_image_upload;
    public $dossier_photo2_title;
    public $dossier_photo2_desc;

    public $dossier_photo3_image;
    public $dossier_photo3_image_upload;
    public $dossier_photo3_title;
    public $dossier_photo3_desc;

    public $dossier_photo4_image;
    public $dossier_photo4_image_upload;
    public $dossier_photo4_title;
    public $dossier_photo4_desc;

    // Foto Panorámica de Fiesta / Montaje (Pie de Página 2 del Dossier)
    public $dossier_banner_image;
    public $dossier_banner_image_upload;
    public $dossier_banner_title;
    public $dossier_banner_subtitle;

    public $logo;
    public $current_logo;

    public $company_signature;
    public $current_company_signature;
    public $company_signature_canvas;

    // Integraciones Musicales (Spotify & Apple Music)
    public $primary_streaming_service = 'auto'; // 'auto', 'apple_music', 'spotify'
    
    // Spotify
    public $spotify_client_id;
    public $spotify_client_secret;
    public $spotify_launch_mode = 'app';
    public $spotifyConnectionStatus = null;
    public $spotifyUser = [];

    // Tidal Developer API
    public $tidal_client_id;
    public $tidal_client_secret;
    public $tidalConnectionStatus = null;

    // Apple Music (MusicKit)
    public $apple_music_developer_token;
    public $apple_music_country = 'es';
    public $apple_music_launch_mode = 'app';

    // Almacenamiento en la Nube (Google Drive / OneDrive)
    public $google_drive_api_key;
    public $google_drive_library_folder;
    public $driveSyncStatus = null;

    // Diagnóstico de Correo Electrónico (SMTP)
    public $test_email_recipient = '';
    public $smtpTestStatus = null;

    // Integración de Correo Entrante (IMAP Leads)
    public $imap_host = '127.0.0.1';
    public $imap_port = 993;
    public $imap_encryption = 'ssl'; // ssl, tls, none
    public $imap_mark_as_read = true;
    public $imap_default_status = 'no_response'; // draft, no_response

    public $imap_nunez_email = 'info@nunezadson.com';
    public $imap_nunez_password = '';
    public $imap_nunez_enabled = true;

    public $imap_javnx_email = 'info@javnxdj.com';
    public $imap_javnx_password = '';
    public $imap_javnx_enabled = true;

    public $imap_leugim_email = 'info@magoleugim.com';
    public $imap_leugim_password = '';
    public $imap_leugim_enabled = false;

    public $imapTestStatus = [];
    public $imapFetchStatus = null;

    // Integración WhatsApp (Evolution API / Gateway)
    public $whatsapp_gateway_enabled = false;
    public $whatsapp_gateway_url = '';
    public $whatsapp_gateway_api_key = '';
    public $whatsapp_instance_miguel = 'whatsapp_miguel';
    public $whatsapp_instance_fran = 'whatsapp_principal';
    public $whatsapp_instance_ezequiel = 'whatsapp_ezequiel';
    public $whatsapp_instance_nunez = 'whatsapp_miguel';
    public $whatsapp_instance_javnx = 'whatsapp_principal';
    public $whatsapp_instance_leugim = 'mago_leugim';
    public $whatsapp_webhook_token = '';
    public $admin_notification_phones = '+34 622 63 47 90, +34 674 37 89 93';
    public $whatsappStatuses = [];
    public $whatsappStatus = null;
    public $whatsappAdminTestReport = null;
    public $whatsappQrBase64 = null;
    public $whatsappQrInstance = null;
    public $whatsappQrPerson = null;

    public function boot()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Solo los administradores pueden acceder a los ajustes del sistema.');
        }
    }

    public function mount()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Solo los administradores pueden acceder a los ajustes del sistema.');
        }

        if (request()->has('tab')) {
            $this->activeTab = request()->get('tab');
        }

        $this->company_name = Setting::get('company_name', 'Núñez and Son');
        $this->company_subtitle = Setting::get('company_subtitle', 'Sound in Motion');
        $this->company_phone = Setting::fixLegacyTypoPhone(Setting::get('company_phone', '+34 622 634 790'), 'company_phone');
        $this->company_cif = Setting::get('company_cif', 'B-12345678');
        $this->company_email = Setting::get('company_email', 'info@nunezandson.com');
        if ($this->company_email === 'info@eventosmusicales.es') {
            $this->company_email = 'info@nunezandson.com';
        }
        $this->company_address = Setting::get('company_address', 'Calle Principal s/n');
        $this->company_city = Setting::get('company_city', 'Navarrete');
        $this->company_season = Setting::get('company_season', 'Temporada 2026/2027');
        $this->public_website_url = Setting::get('public_website_url', '');
        $this->company_iban = Setting::get('company_iban', 'ES00 0000 0000 0000 0000 0000');
        $this->company_bizum = Setting::fixLegacyTypoPhone(Setting::get('company_bizum', '622634790'), 'company_bizum');
        $this->deposit_type = Setting::get('deposit_type', 'percentage');
        $this->deposit_percentage = (float)Setting::get('deposit_percentage', 40);
        $this->deposit_fixed_amount = (float)Setting::get('deposit_fixed_amount', 200);

        // Perfiles de Marca
        $this->brand_nunez_name = Setting::get('brand_nunez_name', 'Núñez and Son');
        $this->brand_nunez_subtitle = Setting::get('brand_nunez_subtitle', 'DJ & Sonido');
        $this->brand_nunez_phone = Setting::fixLegacyTypoPhone(Setting::get('brand_nunez_phone', '+34 622 63 47 90 (Fran)'), 'brand_nunez_phone');
        $this->brand_nunez_phone_2 = Setting::fixLegacyTypoPhone(Setting::get('brand_nunez_phone_2', '+34 674 37 89 93 (Miguel)'), 'brand_nunez_phone_2');
        $this->brand_nunez_email = Setting::get('brand_nunez_email', 'info@nunezandson.com');
        if ($this->brand_nunez_email === 'info@eventosmusicales.es') {
            $this->brand_nunez_email = 'info@nunezandson.com';
        }
        $this->brand_nunez_website = Setting::get('brand_nunez_website', 'nunezandson.com');
        if (str_contains($this->brand_nunez_website, 'landing-bodas')) {
            $this->brand_nunez_website = 'nunezandson.com';
        }

        $this->brand_javnx_name = Setting::get('brand_javnx_name', 'JAVNX DJ');
        $this->brand_javnx_subtitle = Setting::get('brand_javnx_subtitle', 'DJ & Producción de Eventos');
        $this->brand_javnx_phone = Setting::fixLegacyTypoPhone(Setting::get('brand_javnx_phone', '+34 622 63 47 90'), 'brand_javnx_phone');
        $this->brand_javnx_phone_2 = Setting::fixLegacyTypoPhone(Setting::get('brand_javnx_phone_2', ''), 'brand_javnx_phone_2');
        $this->brand_javnx_email = Setting::get('brand_javnx_email', 'info@javnxdj.com');
        $this->brand_javnx_website = Setting::get('brand_javnx_website', 'javnxdj.com');

        $this->brand_leugim_name = Setting::get('brand_leugim_name', 'Mago Leugim');
        $this->brand_leugim_subtitle = Setting::get('brand_leugim_subtitle', 'Ilusionismo, Magia de Cerca & Eventos');
        $this->brand_leugim_phone = Setting::fixLegacyTypoPhone(Setting::get('brand_leugim_phone', '+34 674 37 89 93 (Miguel)'), 'brand_leugim_phone');
        $this->brand_leugim_phone_2 = Setting::fixLegacyTypoPhone(Setting::get('brand_leugim_phone_2', '+34 622 63 47 90 (Fran)'), 'brand_leugim_phone_2');
        $this->brand_leugim_email = Setting::get('brand_leugim_email', 'magoleugim@gmail.com');
        $this->brand_leugim_website = Setting::get('brand_leugim_website', 'magoleugim.es');

        $savedVideoBrands = Setting::get('video_service_brands');
        if ($savedVideoBrands !== null) {
            $decoded = is_array($savedVideoBrands) ? $savedVideoBrands : json_decode($savedVideoBrands, true);
            $this->video_service_brands = is_array($decoded) ? $decoded : ['javnx'];
        } else {
            $this->video_service_brands = ['javnx'];
        }

        // Packs
        $storedBasic = Setting::get('pack_basic_name');
        if (empty($storedBasic) || $storedBasic === 'Pack Básico' || $storedBasic === 'Pack Basico') {
            Setting::set('pack_basic_name', 'Pack Esencial');
            Setting::set('pack_basic_price', 600);
            Setting::set('pack_basic_hours', 4);
            Setting::set('pack_basic_desc', '4 horas de sesión DJ. Sonido profesional estéreo (2 altavoces de alta definición) + iluminación rítmica básica de pista (2 focos/efectos LED). Microfonía inalámbrica para discursos.');
        }

        $storedMedium = Setting::get('pack_medium_name');
        if (empty($storedMedium) || $storedMedium === 'Pack Medio (Recomendado)' || $storedMedium === 'Pack Medio') {
            Setting::set('pack_medium_name', 'Pack Signature');
            Setting::set('pack_medium_price', 950);
            Setting::set('pack_medium_hours', 5);
            Setting::set('pack_medium_desc', '5 horas de sesión DJ. Sonido reforzado con subgraves (impacto y pegada en pista). Cabina DJ retroiluminada + puente/barras de iluminación LED sincronizada y ambientación de pista. Microfonía inalámbrica.');
        }

        $storedPremium = Setting::get('pack_premium_name');
        if (empty($storedPremium) || $storedPremium === 'Pack Premium') {
            Setting::set('pack_premium_name', 'Pack Deluxe Show');
            Setting::set('pack_premium_price', 1350);
            Setting::set('pack_premium_hours', 6);
            Setting::set('pack_premium_desc', 'Cobertura completa (hasta 6h). Montaje de gran formato: sonido multizona/subgraves potentes + show de iluminación avanzada sincronizada. Incluye efecto especial de máquina de humo frío / chispas frías para momentos clave (apertura de baile).');
        }

        $this->pack_basic_name = Setting::get('pack_basic_name', 'Pack Esencial');
        $this->pack_basic_price = Setting::get('pack_basic_price', 600);
        $this->pack_basic_hours = Setting::get('pack_basic_hours', 4);
        $this->pack_basic_desc = Setting::get('pack_basic_desc', '4 horas de sesión DJ. Sonido profesional estéreo (2 altavoces de alta definición) + iluminación rítmica básica de pista (2 focos/efectos LED). Microfonía inalámbrica para discursos.');

        $this->pack_medium_name = Setting::get('pack_medium_name', 'Pack Signature');
        $this->pack_medium_price = Setting::get('pack_medium_price', 950);
        $this->pack_medium_hours = Setting::get('pack_medium_hours', 5);
        $this->pack_medium_desc = Setting::get('pack_medium_desc', '5 horas de sesión DJ. Sonido reforzado con subgraves (impacto y pegada en pista). Cabina DJ retroiluminada + puente/barras de iluminación LED sincronizada y ambientación de pista. Microfonía inalámbrica.');

        $this->pack_premium_name = Setting::get('pack_premium_name', 'Pack Deluxe Show');
        $this->pack_premium_price = Setting::get('pack_premium_price', 1350);
        $this->pack_premium_hours = Setting::get('pack_premium_hours', 6);
        $this->pack_premium_desc = Setting::get('pack_premium_desc', 'Cobertura completa (hasta 6h). Montaje de gran formato: sonido multizona/subgraves potentes + show de iluminación avanzada sincronizada. Incluye efecto especial de máquina de humo frío / chispas frías para momentos clave (apertura de baile).');
        
        // Servicios
        $this->price_ceremony = Setting::get('price_ceremony', 180);
        $this->price_cocktail = Setting::get('price_cocktail', 150);
        $this->price_restaurant = Setting::get('price_restaurant', 150);
        $this->price_dj = Setting::get('price_dj', 150);
        $this->price_karaoke = Setting::get('price_karaoke', 80);
        $this->price_extra_hours = Setting::get('price_extra_hours', 120);
        $this->price_photobooth = Setting::get('price_photobooth', 380);

        $this->photobooth_providers = Setting::getPhotoboothProviders();
        $this->default_photobooth_provider = Setting::get('default_photobooth_provider', 'logrono');
        if (!isset($this->photobooth_providers[$this->default_photobooth_provider])) {
            $this->default_photobooth_provider = array_key_first($this->photobooth_providers) ?: 'logrono';
        }
        $this->photobooth_settings_tab = $this->default_photobooth_provider;

        $pbConfig = $this->photobooth_providers['logrono'] ?? Setting::getPhotobooth360Config('logrono');
        $this->photobooth_logrono_name = $pbConfig['name'] ?? $pbConfig['provider_name'] ?? 'Logroño';
        $this->price_photobooth_2h = $pbConfig['photobooth_2h'];
        $this->price_photobooth_3h = $pbConfig['photobooth_3h'];
        $this->price_photobooth_extra_hour = $pbConfig['photobooth_extra_hour'];
        $this->photobooth_desc = $pbConfig['photobooth_desc'];
        $this->price_video_360_2h = $pbConfig['video_360_2h'];
        $this->price_video_360_3h = $pbConfig['video_360_3h'];
        $this->price_video_360_extra_hour = $pbConfig['video_360_extra_hour'];
        $this->video_360_desc = $pbConfig['video_360_desc'];
        $this->price_pack_duo_2h = $pbConfig['pack_duo_2h'];
        $this->price_pack_duo_3h = $pbConfig['pack_duo_3h'];
        $this->pack_duo_desc = $pbConfig['pack_duo_desc'];
        $this->photobooth_deposit_per_service = $pbConfig['deposit_per_service'];

        $pbVitoria = $this->photobooth_providers['vitoria'] ?? Setting::getPhotobooth360Config('vitoria');
        $this->photobooth_vitoria_name = $pbVitoria['name'] ?? $pbVitoria['provider_name'] ?? 'Vitoria (FotomatonCheese)';
        $this->price_photobooth_vitoria_2h = $pbVitoria['photobooth_2h'];
        $this->price_photobooth_vitoria_3h = $pbVitoria['photobooth_3h'];
        $this->price_photobooth_vitoria_extra_hour = $pbVitoria['photobooth_extra_hour'];
        $this->photobooth_vitoria_desc = $pbVitoria['photobooth_desc'];
        $this->price_video_360_vitoria_2h = $pbVitoria['video_360_2h'];
        $this->price_video_360_vitoria_3h = $pbVitoria['video_360_3h'];
        $this->price_video_360_vitoria_extra_hour = $pbVitoria['video_360_extra_hour'];
        $this->video_360_vitoria_desc = $pbVitoria['video_360_desc'];
        $this->price_pack_duo_vitoria_2h = $pbVitoria['pack_duo_2h'];
        $this->price_pack_duo_vitoria_3h = $pbVitoria['pack_duo_3h'];
        $this->pack_duo_vitoria_desc = $pbVitoria['pack_duo_desc'];
        $this->photobooth_vitoria_deposit_per_service = $pbVitoria['deposit_per_service'];

        $this->price_chocolate_fountain = $pbVitoria['chocolate_fountain_price'] ?? 420;
        $this->price_chocolate_fountain_1_2h = $pbVitoria['chocolate_fountain_1_2h'] ?? 420;
        $this->price_chocolate_fountain_1_3h = $pbVitoria['chocolate_fountain_1_3h'] ?? 520;
        $this->price_chocolate_fountain_2_2h = $pbVitoria['chocolate_fountain_2_2h'] ?? 490;
        $this->price_chocolate_fountain_2_3h = $pbVitoria['chocolate_fountain_2_3h'] ?? 590;
        $this->price_chocolate_fountain_waffle_extra = $pbVitoria['chocolate_fountain_waffle_extra'] ?? 2.90;
        $this->chocolate_fountain_desc = $pbVitoria['chocolate_fountain_desc'] ?? '';
        $this->price_candy_bar = $pbVitoria['candy_bar_price'] ?? 420;
        $this->candy_bar_desc = $pbVitoria['candy_bar_desc'] ?? '';
        $this->price_waffle_cart_2h = $pbVitoria['waffle_cart_2h'] ?? 420;
        $this->price_waffle_cart_3h = $pbVitoria['waffle_cart_3h'] ?? 550;
        $this->waffle_cart_desc = $pbVitoria['waffle_cart_desc'] ?? '';

        $this->price_effects_smoke = Setting::get('price_effects_smoke', 60);
        $storedSparks = Setting::get('price_effects_sparks');
        if (empty($storedSparks) || (float)$storedSparks == 150) {
            Setting::set('price_effects_sparks', 60);
        }
        $this->price_effects_sparks = (float)Setting::get('price_effects_sparks', 60);

        // Pack Cóctel + Banquete
        $this->pack_cocktail_restaurant_enabled = (bool)Setting::get('pack_cocktail_restaurant_enabled', true);
        $this->pack_cocktail_restaurant_discount_type = Setting::get('pack_cocktail_restaurant_discount_type', 'percentage');
        $this->pack_cocktail_restaurant_discount_percentage = (float)Setting::get('pack_cocktail_restaurant_discount_percentage', 25);
        $this->pack_cocktail_restaurant_price = (float)Setting::get('pack_cocktail_restaurant_price', 220);

        // Fotografía
        $this->price_photo_ceremony = Setting::get('price_photo_ceremony', 250);
        $this->price_photo_restaurant = Setting::get('price_photo_restaurant', 250);
        $this->price_photo_party = Setting::get('price_photo_party', 300);
        $this->price_photo_album = Setting::get('price_photo_album', 150);
        $this->price_photo_full_pack = Setting::get('price_photo_full_pack', 800);

        // Contratos
        $this->contract_title = ContractTemplateService::getDefaultTitle();
        $this->contract_body = ContractTemplateService::getDefaultBody();
        $this->contract_footer = ContractTemplateService::getDefaultFooter();

        // Dossier y Propuesta
        $dDefaults = DossierTemplateService::getDefaults();
        $this->dossier_cover_title = Setting::get('dossier_cover_title', $dDefaults['dossier_cover_title']);
        $this->dossier_page2_subtitle = Setting::get('dossier_page2_subtitle', $dDefaults['dossier_page2_subtitle']);
        $this->dossier_page2_title = Setting::get('dossier_page2_title', $dDefaults['dossier_page2_title']);
        $this->dossier_intro_text = Setting::get('dossier_intro_text', $dDefaults['dossier_intro_text']);
        $this->dossier_work_title = Setting::get('dossier_work_title', $dDefaults['dossier_work_title']);
        $this->dossier_work_item1 = Setting::get('dossier_work_item1', $dDefaults['dossier_work_item1']);
        $this->dossier_work_item2 = Setting::get('dossier_work_item2', $dDefaults['dossier_work_item2']);
        $this->dossier_work_item3 = Setting::get('dossier_work_item3', $dDefaults['dossier_work_item3']);
        $this->dossier_extra_hours_title = Setting::get('dossier_extra_hours_title', $dDefaults['dossier_extra_hours_title']);
        $this->dossier_extra_hours_desc = Setting::get('dossier_extra_hours_desc', $dDefaults['dossier_extra_hours_desc']);
        $this->dossier_music_custom_title = Setting::get('dossier_music_custom_title', $dDefaults['dossier_music_custom_title']);
        $this->dossier_music_custom_desc = Setting::get('dossier_music_custom_desc', $dDefaults['dossier_music_custom_desc']);
        $this->quote_validity_days = (int)Setting::get('quote_validity_days', 15);

        $this->dossier_photo1_image = Setting::get('dossier_photo1_image', Setting::get('dossier_block1_image'));
        $this->dossier_photo1_title = Setting::get('dossier_photo1_title', Setting::get('dossier_block1_title', 'Montaje de Sonido'));
        $this->dossier_photo1_desc = Setting::get('dossier_photo1_desc', Setting::get('dossier_block1_desc', ''));

        $this->dossier_photo2_image = Setting::get('dossier_photo2_image', Setting::get('dossier_block2_image'));
        $this->dossier_photo2_title = Setting::get('dossier_photo2_title', Setting::get('dossier_block2_title', 'Iluminación y Efectos'));
        $this->dossier_photo2_desc = Setting::get('dossier_photo2_desc', Setting::get('dossier_block2_desc', ''));

        $this->dossier_photo3_image = Setting::get('dossier_photo3_image', Setting::get('dossier_block3_image'));
        $this->dossier_photo3_title = Setting::get('dossier_photo3_title', Setting::get('dossier_block3_title', 'Cabina DJ'));
        $this->dossier_photo3_desc = Setting::get('dossier_photo3_desc', Setting::get('dossier_block3_desc', ''));

        $this->dossier_photo4_image = Setting::get('dossier_photo4_image');
        $this->dossier_photo4_title = Setting::get('dossier_photo4_title', 'Montaje Especial / Fiesta');
        $this->dossier_photo4_desc = Setting::get('dossier_photo4_desc', '');

        $this->dossier_banner_image = Setting::get('dossier_banner_image');
        $this->dossier_banner_title = Setting::get('dossier_banner_title', 'Fiesta en Directo & Iluminación Espectacular');
        $this->dossier_banner_subtitle = Setting::get('dossier_banner_subtitle', 'Pista de baile siempre activa con efectos visuales, sonido de alta fidelidad y máxima energía.');

        $this->current_logo = Setting::get('company_logo');
        $this->current_company_signature = Setting::get('company_signature');

        // Cargar ajustes musicales
        $this->primary_streaming_service = Setting::get('primary_streaming_service', 'auto');
        $this->spotify_client_id = Setting::get('spotify_client_id', '');
        $this->spotify_client_secret = Setting::get('spotify_client_secret', '');
        $this->spotify_launch_mode = Setting::get('spotify_launch_mode', 'app');
        $this->tidal_client_id = Setting::get('tidal_client_id', '');
        $this->tidal_client_secret = Setting::get('tidal_client_secret', '');
        $this->apple_music_developer_token = Setting::get('apple_music_developer_token', '');
        $this->apple_music_country = Setting::get('apple_music_country', 'es');
        $this->apple_music_launch_mode = Setting::get('apple_music_launch_mode', 'app');
        $this->google_drive_api_key = Setting::get('google_drive_api_key', '');
        $this->google_drive_library_folder = Setting::get('google_drive_library_folder', '');
        $this->spotifyUser = SpotifyService::getUserDetails();
        $this->test_email_recipient = $this->company_email ?: 'info@javnxdj.com';

        // Cargar ajustes IMAP (Correos entrantes / Leads)
        $this->imap_host = Setting::get('imap_host', '127.0.0.1');
        $this->imap_port = (int)Setting::get('imap_port', 993);
        $this->imap_encryption = Setting::get('imap_encryption', 'ssl');
        $this->imap_mark_as_read = Setting::get('imap_mark_as_read', '1') === '1';
        $this->imap_default_status = Setting::get('imap_default_status', 'no_response');

        $this->imap_nunez_email = Setting::get('imap_nunez_email', Setting::get('brand_nunez_email', 'info@nunezadson.com'));
        $this->imap_nunez_password = Setting::get('imap_nunez_password', '');
        $this->imap_nunez_enabled = Setting::get('imap_nunez_enabled', '1') === '1';

        $this->imap_javnx_email = Setting::get('imap_javnx_email', Setting::get('brand_javnx_email', 'info@javnxdj.com'));
        $this->imap_javnx_password = Setting::get('imap_javnx_password', '');
        $this->imap_javnx_enabled = Setting::get('imap_javnx_enabled', '1') === '1';

        $this->imap_leugim_email = Setting::get('imap_leugim_email', Setting::get('brand_leugim_email', 'info@magoleugim.com'));
        $this->imap_leugim_password = Setting::get('imap_leugim_password', '');
        $this->imap_leugim_enabled = Setting::get('imap_leugim_enabled', '0') === '1';

        // WhatsApp Gateway (Evolution API)
        $this->whatsapp_gateway_enabled = Setting::get('whatsapp_gateway_enabled', '0') === '1';
        $this->whatsapp_gateway_url = Setting::get('whatsapp_gateway_url', '');
        $this->whatsapp_gateway_api_key = Setting::get('whatsapp_gateway_api_key', '');
        $this->whatsapp_instance_miguel = Setting::get('whatsapp_instance_miguel', Setting::get('whatsapp_instance_nunez', 'whatsapp_miguel'));
        $this->whatsapp_instance_fran = Setting::get('whatsapp_instance_fran', Setting::get('whatsapp_instance_javnx', 'whatsapp_principal'));
        $this->whatsapp_instance_ezequiel = Setting::get('whatsapp_instance_ezequiel', 'whatsapp_ezequiel');
        $this->whatsapp_instance_nunez = $this->whatsapp_instance_miguel;
        $this->whatsapp_instance_javnx = $this->whatsapp_instance_fran;
        $this->whatsapp_instance_leugim = Setting::get('whatsapp_instance_leugim', 'mago_leugim');
        $this->whatsapp_webhook_token = Setting::get('whatsapp_webhook_token', '');
        $this->admin_notification_phones = Setting::get('admin_notification_phones', '+34 622 63 47 90, +34 674 37 89 93');
    }

    public function loadPreset($presetKey)
    {
        $presets = ContractTemplateService::presets();
        if (isset($presets[$presetKey])) {
            $this->contract_title = $presets[$presetKey]['title'];
            $this->contract_body = $presets[$presetKey]['body'];
            $this->contract_footer = $presets[$presetKey]['footer'];
            $this->selectedPreset = $presetKey;
            session()->flash('contract_preset_loaded', 'Plantilla "' . $presets[$presetKey]['name'] . '" cargada en el editor. Recuerda hacer clic en "Guardar Configuración".');
        }
    }

    public function insertVariable($variable)
    {
        $this->contract_body .= ' ' . $variable;
    }

    public function loadDossierPreset($presetKey)
    {
        $presets = DossierTemplateService::presets();
        if (isset($presets[$presetKey])) {
            foreach ($presets[$presetKey]['values'] as $key => $val) {
                if (property_exists($this, $key)) {
                    $this->$key = $val;
                }
            }
            // Map legacy block titles/descs to photo titles/descs if present
            if (isset($presets[$presetKey]['values']['dossier_block1_title'])) {
                $this->dossier_photo1_title = $presets[$presetKey]['values']['dossier_block1_title'];
                $this->dossier_photo1_desc = $presets[$presetKey]['values']['dossier_block1_desc'] ?? '';
            }
            if (isset($presets[$presetKey]['values']['dossier_block2_title'])) {
                $this->dossier_photo2_title = $presets[$presetKey]['values']['dossier_block2_title'];
                $this->dossier_photo2_desc = $presets[$presetKey]['values']['dossier_block2_desc'] ?? '';
            }
            if (isset($presets[$presetKey]['values']['dossier_block3_title'])) {
                $this->dossier_photo3_title = $presets[$presetKey]['values']['dossier_block3_title'];
                $this->dossier_photo3_desc = $presets[$presetKey]['values']['dossier_block3_desc'] ?? '';
            }
            $this->selectedDossierPreset = $presetKey;
            session()->flash('dossier_preset_loaded', 'Plantilla de dossier "' . $presets[$presetKey]['name'] . '" cargada en el editor. Recuerda hacer clic en "Guardar Configuración".');
        }
    }

    public function deleteDossierImage($slot)
    {
        $keysMap = [
            'photo1' => ['dossier_photo1_image', 'dossier_block1_image'],
            'photo2' => ['dossier_photo2_image', 'dossier_block2_image'],
            'photo3' => ['dossier_photo3_image', 'dossier_block3_image'],
            'photo4' => ['dossier_photo4_image'],
            'banner' => ['dossier_banner_image'],
            'block1' => ['dossier_photo1_image', 'dossier_block1_image'],
            'block2' => ['dossier_photo2_image', 'dossier_block2_image'],
            'block3' => ['dossier_photo3_image', 'dossier_block3_image'],
        ];

        $keys = $keysMap[$slot] ?? ['dossier_' . $slot . '_image'];
        foreach ($keys as $key) {
            $current = Setting::get($key);
            if ($current) {
                Storage::disk('public')->delete($current);
            }
            Setting::set($key, null);
            if (property_exists($this, $key)) {
                $this->$key = null;
            }
            $uploadKey = $key . '_upload';
            if (property_exists($this, $uploadKey)) {
                $this->$uploadKey = null;
            }
        }
        session()->flash('message', 'Foto eliminada correctamente.');
    }

    public function testSpotify()
    {
        Setting::set('spotify_client_id', $this->spotify_client_id);
        Setting::set('spotify_client_secret', $this->spotify_client_secret);
        $this->spotifyConnectionStatus = MusicSearchService::testSpotifyConnection();
    }

    public function testTidal()
    {
        Setting::set('tidal_client_id', $this->tidal_client_id);
        Setting::set('tidal_client_secret', $this->tidal_client_secret);
        $this->tidalConnectionStatus = MusicSearchService::testTidalConnection();
    }

    public function updatedPhotoboothProviders($value, $key): void
    {
        $parts = explode('.', (string)$key);
        $providerKey = $parts[0] ?? '';
        if ($providerKey !== '' && isset($this->photobooth_providers[$providerKey]) && is_array($this->photobooth_providers[$providerKey])) {
            Setting::saveSinglePhotoboothProvider($providerKey, $this->photobooth_providers[$providerKey]);
        }
    }

    public function toggleProviderService(string $providerKey, string $serviceFlag): void
    {
        if (!isset($this->photobooth_providers[$providerKey])) {
            return;
        }
        $allowedFlags = [
            'photobooth_enabled',
            'video_360_enabled',
            'pack_duo_enabled',
            'chocolate_fountain_enabled',
            'candy_bar_enabled',
            'waffle_cart_enabled',
        ];
        if (!in_array($serviceFlag, $allowedFlags, true)) {
            return;
        }
        $current = !empty($this->photobooth_providers[$providerKey][$serviceFlag]);
        $this->photobooth_providers[$providerKey][$serviceFlag] = !$current;
        Setting::saveSinglePhotoboothProvider($providerKey, $this->photobooth_providers[$providerKey]);
    }

    public function addPhotoboothProvider(): void
    {
        $name = trim($this->new_provider_name);
        if ($name === '') {
            session()->flash('error', 'Indica un nombre para el nuevo proveedor externo.');
            return;
        }

        $slugBase = \Illuminate\Support\Str::slug($name, '_');
        if ($slugBase === '') {
            $slugBase = 'proveedor';
        }
        $slug = $slugBase;
        $counter = 2;
        while (isset($this->photobooth_providers[$slug])) {
            $slug = $slugBase . '_' . $counter;
            $counter++;
        }

        $template = $this->photobooth_providers['vitoria'] ?? reset($this->photobooth_providers) ?: [];
        $newProvider = array_merge($template, [
            'provider_key' => $slug,
            'name' => $name,
            'provider_name' => $name,
            'zone' => trim($this->new_provider_zone) ?: $name,
            'contact_info' => trim($this->new_provider_contact),
            'photobooth_enabled' => true,
            'video_360_enabled' => true,
            'pack_duo_enabled' => true,
            'chocolate_fountain_enabled' => false,
            'candy_bar_enabled' => false,
            'waffle_cart_enabled' => false,
        ]);

        $this->photobooth_providers[$slug] = $newProvider;
        Setting::saveSinglePhotoboothProvider($slug, $newProvider);

        $this->photobooth_settings_tab = $slug;
        $this->new_provider_name = '';
        $this->new_provider_zone = '';
        $this->new_provider_contact = '';
        $this->show_add_provider_form = false;

        session()->flash('message', 'Nuevo proveedor "' . $name . '" añadido correctamente. Ahora puedes configurar sus servicios y tarifas.');
    }

    public function deletePhotoboothProvider(string $providerKey): void
    {
        if (in_array($providerKey, ['logrono', 'vitoria'], true)) {
            session()->flash('error', 'Los proveedores base (Logroño y Vitoria) no se pueden eliminar, pero puedes desactivar sus servicios o cambiar su nombre.');
            return;
        }
        if (!isset($this->photobooth_providers[$providerKey])) {
            return;
        }
        $removedName = $this->photobooth_providers[$providerKey]['name'] ?? $providerKey;
        unset($this->photobooth_providers[$providerKey]);
        Setting::deleteSinglePhotoboothProvider($providerKey);

        if ($this->default_photobooth_provider === $providerKey) {
            $this->default_photobooth_provider = array_key_first($this->photobooth_providers) ?: 'logrono';
            Setting::set('default_photobooth_provider', $this->default_photobooth_provider);
        }
        if ($this->photobooth_settings_tab === $providerKey) {
            $this->photobooth_settings_tab = $this->default_photobooth_provider;
        }

        session()->flash('message', 'Proveedor "' . $removedName . '" eliminado.');
    }

    public function save()
    {
        $this->validate([
            'company_name' => 'required|string|max:255',
            'company_subtitle' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:50',
            'company_cif' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_season' => 'required|string|max:50',
            'company_iban' => 'nullable|string|max:50',
            'company_bizum' => 'nullable|string|max:50',
            'deposit_type' => 'required|in:percentage,fixed',
            'deposit_percentage' => 'nullable|numeric|min:0|max:100',
            'deposit_fixed_amount' => 'nullable|numeric|min:0',

            'pack_basic_name' => 'required|string|max:100',
            'pack_basic_price' => 'required|numeric',
            'pack_basic_hours' => 'required|numeric',
            'pack_basic_desc' => 'nullable|string',

            'pack_medium_name' => 'required|string|max:100',
            'pack_medium_price' => 'required|numeric',
            'pack_medium_hours' => 'required|numeric',
            'pack_medium_desc' => 'nullable|string',

            'pack_premium_name' => 'required|string|max:100',
            'pack_premium_price' => 'required|numeric',
            'pack_premium_hours' => 'required|numeric',
            'pack_premium_desc' => 'nullable|string',

            'price_ceremony' => 'required|numeric',
            'price_cocktail' => 'required|numeric',
            'price_restaurant' => 'required|numeric',
            'price_dj' => 'required|numeric',
            'price_karaoke' => 'required|numeric',
            'price_extra_hours' => 'required|numeric',
            'price_effects_smoke' => 'required|numeric',
            'price_effects_sparks' => 'required|numeric',

            'price_photo_ceremony' => 'required|numeric',
            'price_photo_restaurant' => 'required|numeric',
            'price_photo_party' => 'required|numeric',
            'price_photo_album' => 'required|numeric',
            'price_photo_full_pack' => 'required|numeric',

            'contract_title' => 'required|string|max:255',
            'contract_body' => 'required|string',
            'contract_footer' => 'nullable|string',

            'dossier_cover_title' => 'nullable|string|max:255',
            'dossier_page2_subtitle' => 'nullable|string|max:255',
            'dossier_page2_title' => 'nullable|string|max:255',
            'dossier_intro_text' => 'nullable|string',
            'dossier_work_title' => 'nullable|string|max:255',
            'dossier_work_item1' => 'nullable|string',
            'dossier_work_item2' => 'nullable|string',
            'dossier_work_item3' => 'nullable|string',
            'dossier_extra_hours_title' => 'nullable|string|max:255',
            'dossier_extra_hours_desc' => 'nullable|string',
            'dossier_music_custom_title' => 'nullable|string|max:255',
            'dossier_music_custom_desc' => 'nullable|string',
            'quote_validity_days' => 'required|integer|min:1|max:365',

            'dossier_photo1_title' => 'nullable|string|max:255',
            'dossier_photo1_desc' => 'nullable|string|max:500',
            'dossier_photo1_image_upload' => 'nullable|image|max:8192',

            'dossier_photo2_title' => 'nullable|string|max:255',
            'dossier_photo2_desc' => 'nullable|string|max:500',
            'dossier_photo2_image_upload' => 'nullable|image|max:8192',

            'dossier_photo3_title' => 'nullable|string|max:255',
            'dossier_photo3_desc' => 'nullable|string|max:500',
            'dossier_photo3_image_upload' => 'nullable|image|max:8192',

            'dossier_photo4_title' => 'nullable|string|max:255',
            'dossier_photo4_desc' => 'nullable|string|max:500',
            'dossier_photo4_image_upload' => 'nullable|image|max:8192',

            'dossier_banner_title' => 'nullable|string|max:255',
            'dossier_banner_subtitle' => 'nullable|string|max:500',
            'dossier_banner_image_upload' => 'nullable|image|max:10240',

            'logo' => 'nullable|image|max:2048',
            'company_signature' => 'nullable|image|max:2048',
            'company_signature_canvas' => 'nullable|string',
            'primary_streaming_service' => 'required|in:auto,apple_music,spotify',
            'spotify_client_id' => 'nullable|string|max:255',
            'spotify_client_secret' => 'nullable|string|max:255',
            'spotify_launch_mode' => 'required|in:app,web',
            'apple_music_developer_token' => 'nullable|string',
            'apple_music_country' => 'required|string|max:5',
            'apple_music_launch_mode' => 'required|in:app,web',
            'google_drive_api_key' => 'nullable|string|max:255',
            'google_drive_library_folder' => 'nullable|string|max:500',
        ]);

        Setting::set('company_name', $this->company_name);
        Setting::set('company_subtitle', $this->company_subtitle);
        Setting::set('company_phone', $this->company_phone);
        Setting::set('company_cif', $this->company_cif);
        Setting::set('company_email', $this->company_email);
        Setting::set('company_address', $this->company_address);
        Setting::set('company_city', $this->company_city);
        Setting::set('company_season', $this->company_season);
        Setting::set('public_website_url', $this->public_website_url);
        Setting::set('company_iban', $this->company_iban);
        Setting::set('company_bizum', $this->company_bizum);
        Setting::set('deposit_type', $this->deposit_type);
        Setting::set('deposit_percentage', (float)$this->deposit_percentage);
        Setting::set('deposit_fixed_amount', (float)$this->deposit_fixed_amount);

        // Perfiles de Marca
        Setting::set('brand_nunez_name', $this->brand_nunez_name);
        Setting::set('brand_nunez_subtitle', $this->brand_nunez_subtitle);
        Setting::set('brand_nunez_phone', $this->brand_nunez_phone);
        Setting::set('brand_nunez_phone_2', $this->brand_nunez_phone_2);
        Setting::set('brand_nunez_email', $this->brand_nunez_email);
        Setting::set('brand_nunez_website', $this->brand_nunez_website);

        Setting::set('brand_javnx_name', $this->brand_javnx_name);
        Setting::set('brand_javnx_subtitle', $this->brand_javnx_subtitle);
        Setting::set('brand_javnx_phone', $this->brand_javnx_phone);
        Setting::set('brand_javnx_phone_2', $this->brand_javnx_phone_2);
        Setting::set('brand_javnx_email', $this->brand_javnx_email);
        Setting::set('brand_javnx_website', $this->brand_javnx_website);

        Setting::set('brand_leugim_name', $this->brand_leugim_name);
        Setting::set('brand_leugim_subtitle', $this->brand_leugim_subtitle);
        Setting::set('brand_leugim_phone', $this->brand_leugim_phone);
        Setting::set('brand_leugim_phone_2', $this->brand_leugim_phone_2);
        Setting::set('brand_leugim_email', $this->brand_leugim_email);
        Setting::set('brand_leugim_website', $this->brand_leugim_website);

        // Habilitación de videógrafo por marca
        Setting::set('video_service_brands', json_encode(array_values($this->video_service_brands ?: [])));

        // Packs
        Setting::set('pack_basic_name', $this->pack_basic_name);
        Setting::set('pack_basic_price', $this->pack_basic_price);
        Setting::set('pack_basic_hours', $this->pack_basic_hours);
        Setting::set('pack_basic_desc', $this->pack_basic_desc);

        Setting::set('pack_medium_name', $this->pack_medium_name);
        Setting::set('pack_medium_price', $this->pack_medium_price);
        Setting::set('pack_medium_hours', $this->pack_medium_hours);
        Setting::set('pack_medium_desc', $this->pack_medium_desc);

        Setting::set('pack_premium_name', $this->pack_premium_name);
        Setting::set('pack_premium_price', $this->pack_premium_price);
        Setting::set('pack_premium_hours', $this->pack_premium_hours);
        Setting::set('pack_premium_desc', $this->pack_premium_desc);

        // Servicios
        Setting::set('price_ceremony', $this->price_ceremony);
        Setting::set('price_cocktail', $this->price_cocktail);
        Setting::set('price_restaurant', $this->price_restaurant);
        Setting::set('price_dj', $this->price_dj);
        Setting::set('price_karaoke', $this->price_karaoke);
        Setting::set('price_extra_hours', $this->price_extra_hours);

        // Guardar catálogo de proveedores externos independientes
        Setting::savePhotoboothProviders($this->photobooth_providers);
        $validDefaultProv = isset($this->photobooth_providers[$this->default_photobooth_provider])
            ? $this->default_photobooth_provider
            : (array_key_first($this->photobooth_providers) ?: 'logrono');
        Setting::set('default_photobooth_provider', $validDefaultProv);

        Setting::set('price_effects_smoke', $this->price_effects_smoke);
        Setting::set('price_effects_sparks', $this->price_effects_sparks);

        // Pack Cóctel + Banquete
        Setting::set('pack_cocktail_restaurant_enabled', $this->pack_cocktail_restaurant_enabled ? '1' : '0');
        Setting::set('pack_cocktail_restaurant_discount_type', $this->pack_cocktail_restaurant_discount_type);
        Setting::set('pack_cocktail_restaurant_discount_percentage', (float)$this->pack_cocktail_restaurant_discount_percentage);
        Setting::set('pack_cocktail_restaurant_price', (float)$this->pack_cocktail_restaurant_price);

        // Fotografía
        Setting::set('price_photo_ceremony', $this->price_photo_ceremony);
        Setting::set('price_photo_restaurant', $this->price_photo_restaurant);
        Setting::set('price_photo_party', $this->price_photo_party);
        Setting::set('price_photo_album', $this->price_photo_album);
        Setting::set('price_photo_full_pack', $this->price_photo_full_pack);

        // Contratos
        Setting::set('contract_title', $this->contract_title);
        Setting::set('contract_body', $this->contract_body);
        Setting::set('contract_footer', $this->contract_footer);

        // Dossier y Propuesta
        Setting::set('dossier_cover_title', $this->dossier_cover_title);
        Setting::set('dossier_page2_subtitle', $this->dossier_page2_subtitle);
        Setting::set('dossier_page2_title', $this->dossier_page2_title);
        Setting::set('dossier_intro_text', $this->dossier_intro_text);
        Setting::set('dossier_work_title', $this->dossier_work_title);
        Setting::set('dossier_work_item1', $this->dossier_work_item1);
        Setting::set('dossier_work_item2', $this->dossier_work_item2);
        Setting::set('dossier_work_item3', $this->dossier_work_item3);
        Setting::set('dossier_extra_hours_title', $this->dossier_extra_hours_title);
        Setting::set('dossier_extra_hours_desc', $this->dossier_extra_hours_desc);
        Setting::set('dossier_music_custom_title', $this->dossier_music_custom_title);
        Setting::set('dossier_music_custom_desc', $this->dossier_music_custom_desc);
        Setting::set('quote_validity_days', (int)$this->quote_validity_days);

        // Guardar Textos y Fotos del Dossier (1 a 4 fotos)
        for ($i = 1; $i <= 4; $i++) {
            $titleProp = "dossier_photo{$i}_title";
            $descProp = "dossier_photo{$i}_desc";
            $uploadProp = "dossier_photo{$i}_image_upload";
            $imgProp = "dossier_photo{$i}_image";

            Setting::set("dossier_photo{$i}_title", $this->$titleProp);
            Setting::set("dossier_photo{$i}_desc", $this->$descProp);

            // Backwards compatibility
            if ($i <= 3) {
                Setting::set("dossier_block{$i}_title", $this->$titleProp);
                Setting::set("dossier_block{$i}_desc", $this->$descProp);
            }

            if ($this->$uploadProp) {
                if ($this->$imgProp) {
                    Storage::disk('public')->delete($this->$imgProp);
                }
                $path = $this->$uploadProp->store('dossier', 'public');
                Setting::set("dossier_photo{$i}_image", $path);
                if ($i <= 3) {
                    Setting::set("dossier_block{$i}_image", $path);
                }
                $this->$imgProp = $path;
                $this->$uploadProp = null;
            }
        }

        // Guardar Foto Panorámica de Fiesta / Montaje del Dossier
        Setting::set('dossier_banner_title', $this->dossier_banner_title);
        Setting::set('dossier_banner_subtitle', $this->dossier_banner_subtitle);

        if ($this->dossier_banner_image_upload) {
            if ($this->dossier_banner_image) {
                Storage::disk('public')->delete($this->dossier_banner_image);
            }
            $path = $this->dossier_banner_image_upload->store('dossier', 'public');
            Setting::set('dossier_banner_image', $path);
            $this->dossier_banner_image = $path;
            $this->dossier_banner_image_upload = null;
        }

        // Guardar ajustes musicales
        Setting::set('primary_streaming_service', $this->primary_streaming_service);
        Setting::set('spotify_client_id', $this->spotify_client_id);
        Setting::set('spotify_client_secret', $this->spotify_client_secret);
        Setting::set('spotify_launch_mode', $this->spotify_launch_mode);
        Setting::set('tidal_client_id', $this->tidal_client_id);
        Setting::set('tidal_client_secret', $this->tidal_client_secret);
        Setting::set('apple_music_developer_token', $this->apple_music_developer_token);
        Setting::set('apple_music_country', $this->apple_music_country);
        Setting::set('apple_music_launch_mode', $this->apple_music_launch_mode);
        Setting::set('google_drive_api_key', $this->google_drive_api_key);
        Setting::set('google_drive_library_folder', $this->google_drive_library_folder);

        // Guardar ajustes IMAP (Correos entrantes / Leads)
        $this->saveImapSettings();

        if ($this->logo) {
            if ($this->current_logo) {
                Storage::disk('public')->delete($this->current_logo);
            }
            
            $path = $this->logo->store('logos', 'public');
            Setting::set('company_logo', $path);
            $this->current_logo = $path;
            $this->logo = null;
        }

        if ($this->company_signature) {
            if ($this->current_company_signature) {
                Storage::disk('public')->delete($this->current_company_signature);
            }
            
            $sigPath = $this->company_signature->store('signatures', 'public');
            Setting::set('company_signature', $sigPath);
            $this->current_company_signature = $sigPath;
            $this->company_signature = null;
        } elseif (!empty($this->company_signature_canvas) && str_starts_with($this->company_signature_canvas, 'data:image/')) {
            if ($this->current_company_signature) {
                Storage::disk('public')->delete($this->current_company_signature);
            }
            
            $imageParts = explode(';base64,', $this->company_signature_canvas);
            $imageTypeAux = explode('image/', $imageParts[0]);
            $rawType = strtolower($imageTypeAux[1] ?? 'png');
            $imageType = in_array($rawType, ['png', 'jpg', 'jpeg', 'webp']) ? $rawType : 'png';
            $imageBase64 = base64_decode($imageParts[1] ?? '');
            
            $fileName = 'signatures/company_sig_' . time() . '.' . $imageType;
            Storage::disk('public')->put($fileName, $imageBase64);
            
            Setting::set('company_signature', $fileName);
            $this->current_company_signature = $fileName;
            $this->company_signature_canvas = null;
        }

        // WhatsApp Gateway (Evolution API)
        Setting::set('whatsapp_gateway_enabled', $this->whatsapp_gateway_enabled ? '1' : '0');
        Setting::set('whatsapp_gateway_url', trim($this->whatsapp_gateway_url));
        Setting::set('whatsapp_gateway_api_key', trim($this->whatsapp_gateway_api_key));
        Setting::set('whatsapp_instance_miguel', trim($this->whatsapp_instance_miguel));
        Setting::set('whatsapp_instance_fran', trim($this->whatsapp_instance_fran));
        Setting::set('whatsapp_instance_ezequiel', trim($this->whatsapp_instance_ezequiel));
        Setting::set('whatsapp_instance_nunez', trim($this->whatsapp_instance_miguel));
        Setting::set('whatsapp_instance_javnx', trim($this->whatsapp_instance_fran));
        Setting::set('whatsapp_instance_leugim', trim($this->whatsapp_instance_leugim));
        Setting::set('whatsapp_webhook_token', trim($this->whatsapp_webhook_token));
        Setting::set('admin_notification_phones', trim((string)$this->admin_notification_phones));

        $this->spotifyUser = SpotifyService::getUserDetails();
        session()->flash('message', '¡Configuración y Dossier guardados correctamente!');
        $this->dispatch('settings-saved');
    }

    public function saveWhatsAppSettingsQuietly()
    {
        Setting::set('whatsapp_gateway_enabled', $this->whatsapp_gateway_enabled ? '1' : '0');
        Setting::set('whatsapp_gateway_url', trim($this->whatsapp_gateway_url));
        Setting::set('whatsapp_gateway_api_key', trim($this->whatsapp_gateway_api_key));
        Setting::set('whatsapp_instance_miguel', trim($this->whatsapp_instance_miguel));
        Setting::set('whatsapp_instance_fran', trim($this->whatsapp_instance_fran));
        Setting::set('whatsapp_instance_ezequiel', trim($this->whatsapp_instance_ezequiel));
        Setting::set('whatsapp_instance_nunez', trim($this->whatsapp_instance_miguel));
        Setting::set('whatsapp_instance_javnx', trim($this->whatsapp_instance_fran));
        Setting::set('whatsapp_instance_leugim', trim($this->whatsapp_instance_leugim));
        Setting::set('whatsapp_webhook_token', trim($this->whatsapp_webhook_token));
        Setting::set('admin_notification_phones', trim((string)$this->admin_notification_phones));
    }

    public function testAdminWhatsAppNotification(string $brand = 'javnx')
    {
        $this->saveWhatsAppSettingsQuietly();

        $brandLabel = $brand === 'javnx' ? 'JAVNX DJ' : 'NÚÑEZ & SON';
        $testMsg = "🧪 *PRUEBA DE AVISO AUTOMÁTICO ({$brandLabel})*\n\n"
            . "✅ El sistema de avisos de formularios y presupuestos para *{$brandLabel}* está operativo.\n"
            . "⏰ Hora: " . now()->format('d/m/Y H:i:s');

        $report = \App\Services\EvolutionWhatsAppService::notifyAdmins($testMsg, $brand);
        $this->whatsappAdminTestReport = $report;

        if (($report['sent_count'] ?? 0) > 0) {
            session()->flash('whatsapp_success', "✅ Prueba enviada ({$brandLabel}): {$report['sent_count']} destinatario(s) notificado(s). Revisa el desglose debajo.");
        } else {
            session()->flash('whatsapp_error', "⚠️ No se pudo enviar el aviso de prueba ({$brandLabel}). Revisa que el Gateway esté activo y al menos una instancia esté en estado 'open'.");
        }
    }

    public function testWhatsAppGateway(?string $key = null)
    {
        $this->saveWhatsAppSettingsQuietly();

        if ($key) {
            $status = \App\Services\EvolutionWhatsAppService::checkInstanceStatus($key);
            $this->whatsappStatuses[$key] = $status;
            $this->whatsappStatus = $status;
        } else {
            foreach (['miguel', 'fran', 'ezequiel'] as $k) {
                $this->whatsappStatuses[$k] = \App\Services\EvolutionWhatsAppService::checkInstanceStatus($k);
            }
            $this->whatsappStatus = [
                'configured' => true,
                'message' => 'Estado de los 3 teléfonos verificado correctamente.',
            ];
        }
    }

    public function fetchWhatsAppQrCode(?string $key = null)
    {
        $this->saveWhatsAppSettingsQuietly();

        $personNames = [
            'miguel' => 'Miguel (Núñez & Son)',
            'nunez' => 'Miguel (Núñez & Son)',
            'fran' => 'Fran (JAVNX DJ & Núñez & Son)',
            'javnx' => 'Fran (JAVNX DJ & Núñez & Son)',
            'ezequiel' => 'Ezequiel (Núñez & Son)',
        ];

        $res = \App\Services\EvolutionWhatsAppService::getQrCode($key);

        if (!empty($res['already_connected'])) {
            $this->whatsappQrBase64 = null;
            $this->whatsappQrInstance = null;
            $this->whatsappQrPerson = null;
            if ($key) {
                $this->whatsappStatuses[$key] = [
                    'configured' => true,
                    'state' => 'open',
                    'instance' => $res['instance'] ?? $key,
                    'owner' => $res['owner'] ?? null,
                    'message' => '🟢 Conectado y listo para chatear' . (!empty($res['owner']) ? " ({$res['owner']})" : ""),
                ];
            }
            session()->flash('whatsapp_success', $res['message'] ?? '¡Este WhatsApp ya está conectado y activo! No es necesario escanear QR.');
            return;
        }

        if ($res['success'] && !empty($res['base64'])) {
            $this->whatsappQrBase64 = $res['base64'];
            $this->whatsappQrInstance = $res['instance'] ?? $key;
            $this->whatsappQrPerson = $personNames[$key] ?? ($res['instance'] ?? 'WhatsApp');
            if ($key) {
                $this->whatsappStatuses[$key] = [
                    'configured' => true,
                    'state' => 'connecting',
                    'instance' => $res['instance'] ?? $key,
                    'message' => '🟡 Código QR generado. Escanéalo en la pantalla con tu móvil.',
                ];
            }
        } else {
            session()->flash('whatsapp_error', $res['message'] ?? 'No se pudo generar QR');
        }
    }

    public function disconnectWhatsAppInstance(string $key)
    {
        $this->saveWhatsAppSettingsQuietly();
        $res = \App\Services\EvolutionWhatsAppService::disconnectInstance($key);

        $this->whatsappQrBase64 = null;
        $this->whatsappQrInstance = null;
        $this->whatsappQrPerson = null;

        if ($res['success']) {
            $this->testWhatsAppGateway($key);
            session()->flash('whatsapp_success', $res['message']);
        } else {
            session()->flash('whatsapp_error', $res['message']);
        }
    }

    public function closeWhatsAppQrModal()
    {
        $this->whatsappQrBase64 = null;
        $this->whatsappQrInstance = null;
        $this->whatsappQrPerson = null;
    }

    public function testImapConnection(string $brandKey)
    {
        $this->saveImapSettings();

        $service = app(\App\Services\ImapLeadFetcherService::class);
        $result = $service->testConnection($brandKey);
        $this->imapTestStatus[$brandKey] = $result;
    }

    public function fetchImapLeadsNow()
    {
        $this->saveImapSettings();

        $service = app(\App\Services\ImapLeadFetcherService::class);
        $result = $service->fetchAllActiveMailboxes();
        $this->imapFetchStatus = $result;

        if ($result['total_leads_created'] > 0) {
            session()->flash('message', "🎉 ¡Sincronización completada! Se han analizado {$result['total_scanned']} correos y se han creado {$result['total_leads_created']} nuevos eventos/leads.");
        } else {
            session()->flash('message', "ℹ️ Escaneo completado: Se han analizado {$result['total_scanned']} correos no leídos. No se encontraron nuevas solicitudes de presupuesto.");
        }
    }

    protected function saveImapSettings()
    {
        Setting::set('imap_host', $this->imap_host);
        Setting::set('imap_port', $this->imap_port);
        Setting::set('imap_encryption', $this->imap_encryption);
        Setting::set('imap_mark_as_read', $this->imap_mark_as_read ? '1' : '0');
        Setting::set('imap_default_status', $this->imap_default_status);

        Setting::set('imap_nunez_email', $this->imap_nunez_email);
        Setting::set('imap_nunez_password', $this->imap_nunez_password);
        Setting::set('imap_nunez_enabled', $this->imap_nunez_enabled ? '1' : '0');

        Setting::set('imap_javnx_email', $this->imap_javnx_email);
        Setting::set('imap_javnx_password', $this->imap_javnx_password);
        Setting::set('imap_javnx_enabled', $this->imap_javnx_enabled ? '1' : '0');

        Setting::set('imap_leugim_email', $this->imap_leugim_email);
        Setting::set('imap_leugim_password', $this->imap_leugim_password);
        Setting::set('imap_leugim_enabled', $this->imap_leugim_enabled ? '1' : '0');
    }

    public function disconnectSpotify()
    {
        Setting::set('spotify_access_token', null);
        Setting::set('spotify_refresh_token', null);
        Setting::set('spotify_token_expires_at', null);
        Setting::set('spotify_user_id', null);
        Setting::set('spotify_user_name', null);
        Setting::set('spotify_user_product', null);
        Setting::set('spotify_user_image', null);
        $this->spotifyUser = SpotifyService::getUserDetails();
        session()->flash('message', 'Cuenta de Spotify desconectada correctamente.');
    }

    public function syncDriveLibrary()
    {
        Setting::set('google_drive_api_key', $this->google_drive_api_key);
        Setting::set('google_drive_library_folder', $this->google_drive_library_folder);

        $result = \App\Services\CloudMusicStorageService::syncMasterLibraryFromGoogleDrive($this->google_drive_library_folder, $this->google_drive_api_key);
        $this->driveSyncStatus = $result;

        if ($result['success']) {
            session()->flash('drive_sync_success', $result['message']);
        } else {
            session()->flash('drive_sync_error', $result['message']);
        }
    }

    public function testSmtpConnection()
    {
        $this->validate([
            'test_email_recipient' => 'required|email',
        ], [
            'test_email_recipient.required' => 'Introduce un email de destino para la prueba.',
            'test_email_recipient.email' => 'Introduce una dirección de correo electrónico válida.',
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($this->test_email_recipient)
                ->send(new \App\Mail\TestSmtpMail());

            $this->smtpTestStatus = [
                'success' => true,
                'message' => '✅ ¡Correo de prueba enviado con éxito a ' . $this->test_email_recipient . '! Revisa tu bandeja de entrada o carpeta de spam.',
            ];
        } catch (\Throwable $e) {
            $this->smtpTestStatus = [
                'success' => false,
                'message' => '❌ Error al conectar o enviar con el servidor SMTP: ' . $e->getMessage(),
            ];
        }
    }

    public function render()
    {
        return view('livewire.admin.settings', [
            'availableVariables' => ContractTemplateService::availableVariables(),
            'presets' => ContractTemplateService::presets(),
            'dossierPresets' => DossierTemplateService::presets(),
        ])
            ->layout('components.layouts.app', [
                'header' => 'Configuración de Empresa'
            ]);
    }
}
