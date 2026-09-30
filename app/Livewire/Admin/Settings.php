<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;
use App\Services\ContractTemplateService;
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
    public $price_photobooth;
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

    public $logo;
    public $current_logo;

    // Integraciones Musicales (Spotify & Apple Music)
    public $primary_streaming_service = 'auto'; // 'auto', 'apple_music', 'spotify'
    
    // Spotify
    public $spotify_client_id;
    public $spotify_client_secret;
    public $spotify_launch_mode = 'app';
    public $spotifyConnectionStatus = null;
    public $spotifyUser = [];

    // Apple Music (MusicKit)
    public $apple_music_developer_token;
    public $apple_music_country = 'es';
    public $apple_music_launch_mode = 'app';

    // Almacenamiento en la Nube (Google Drive / OneDrive)
    public $google_drive_api_key;
    public $google_drive_library_folder;
    public $driveSyncStatus = null;

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
        $this->company_phone = Setting::get('company_phone', '+34 622 634 790');
        $this->company_cif = Setting::get('company_cif', 'B-12345678');
        $this->company_email = Setting::get('company_email', 'info@eventosmusicales.es');
        $this->company_address = Setting::get('company_address', 'Calle Principal s/n');
        $this->company_city = Setting::get('company_city', 'Navarrete');
        $this->company_season = Setting::get('company_season', 'Temporada 2026/2027');
        $this->public_website_url = Setting::get('public_website_url', '');
        $this->company_iban = Setting::get('company_iban', 'ES00 0000 0000 0000 0000 0000');
        $this->company_bizum = Setting::get('company_bizum', '622634790');
        $this->deposit_type = Setting::get('deposit_type', 'percentage');
        $this->deposit_percentage = (float)Setting::get('deposit_percentage', 40);
        $this->deposit_fixed_amount = (float)Setting::get('deposit_fixed_amount', 200);

        // Packs
        $this->pack_basic_name = Setting::get('pack_basic_name', 'Pack Básico');
        $this->pack_basic_price = Setting::get('pack_basic_price', 400);
        $this->pack_basic_hours = Setting::get('pack_basic_hours', 4);
        $this->pack_basic_desc = Setting::get('pack_basic_desc', '4 Horas de servicio DJ, Equipo de sonido profesional e iluminación de pista básica.');

        $this->pack_medium_name = Setting::get('pack_medium_name', 'Pack Medio (Recomendado)');
        $this->pack_medium_price = Setting::get('pack_medium_price', 700);
        $this->pack_medium_hours = Setting::get('pack_medium_hours', 5);
        $this->pack_medium_desc = Setting::get('pack_medium_desc', 'Hasta 5 Horas de servicio DJ, Sonido alta gama, Iluminación avanzada de pista + Máquina de humo.');

        $this->pack_premium_name = Setting::get('pack_premium_name', 'Pack Premium');
        $this->pack_premium_price = Setting::get('pack_premium_price', 1000);
        $this->pack_premium_hours = Setting::get('pack_premium_hours', 6);
        $this->pack_premium_desc = Setting::get('pack_premium_desc', 'Hasta 6 Horas de servicio DJ, Iluminación profesional gran potencia + Efectos especiales + Fuego frío.');
        
        // Servicios
        $this->price_ceremony = Setting::get('price_ceremony', 180);
        $this->price_cocktail = Setting::get('price_cocktail', 150);
        $this->price_restaurant = Setting::get('price_restaurant', 150);
        $this->price_dj = Setting::get('price_dj', 150);
        $this->price_karaoke = Setting::get('price_karaoke', 80);
        $this->price_extra_hours = Setting::get('price_extra_hours', 120);
        $this->price_photobooth = Setting::get('price_photobooth', 350);

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

        $this->contract_title = ContractTemplateService::getDefaultTitle();
        $this->contract_body = ContractTemplateService::getDefaultBody();
        $this->contract_footer = ContractTemplateService::getDefaultFooter();

        $this->current_logo = Setting::get('company_logo');

        // Cargar ajustes musicales
        $this->primary_streaming_service = Setting::get('primary_streaming_service', 'auto');
        $this->spotify_client_id = Setting::get('spotify_client_id', '');
        $this->spotify_client_secret = Setting::get('spotify_client_secret', '');
        $this->spotify_launch_mode = Setting::get('spotify_launch_mode', 'app');
        $this->apple_music_developer_token = Setting::get('apple_music_developer_token', '');
        $this->apple_music_country = Setting::get('apple_music_country', 'es');
        $this->apple_music_launch_mode = Setting::get('apple_music_launch_mode', 'app');
        $this->google_drive_api_key = Setting::get('google_drive_api_key', '');
        $this->google_drive_library_folder = Setting::get('google_drive_library_folder', '');
        $this->spotifyUser = SpotifyService::getUserDetails();
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

    public function testSpotify()
    {
        Setting::set('spotify_client_id', $this->spotify_client_id);
        Setting::set('spotify_client_secret', $this->spotify_client_secret);
        $this->spotifyConnectionStatus = MusicSearchService::testSpotifyConnection();
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
            'price_photobooth' => 'required|numeric',

            'price_photo_ceremony' => 'required|numeric',
            'price_photo_restaurant' => 'required|numeric',
            'price_photo_party' => 'required|numeric',
            'price_photo_album' => 'required|numeric',
            'price_photo_full_pack' => 'required|numeric',

            'contract_title' => 'required|string|max:255',
            'contract_body' => 'required|string',
            'contract_footer' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
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
        Setting::set('price_photobooth', $this->price_photobooth);

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

        Setting::set('contract_title', $this->contract_title);
        Setting::set('contract_body', $this->contract_body);
        Setting::set('contract_footer', $this->contract_footer);

        // Guardar ajustes musicales
        Setting::set('primary_streaming_service', $this->primary_streaming_service);
        Setting::set('spotify_client_id', $this->spotify_client_id);
        Setting::set('spotify_client_secret', $this->spotify_client_secret);
        Setting::set('spotify_launch_mode', $this->spotify_launch_mode);
        Setting::set('apple_music_developer_token', $this->apple_music_developer_token);
        Setting::set('apple_music_country', $this->apple_music_country);
        Setting::set('apple_music_launch_mode', $this->apple_music_launch_mode);
        Setting::set('google_drive_api_key', $this->google_drive_api_key);
        Setting::set('google_drive_library_folder', $this->google_drive_library_folder);

        if ($this->logo) {
            if ($this->current_logo) {
                Storage::disk('public')->delete($this->current_logo);
            }
            
            $path = $this->logo->store('logos', 'public');
            Setting::set('company_logo', $path);
            $this->current_logo = $path;
            $this->logo = null;
        }

        $this->spotifyUser = SpotifyService::getUserDetails();
        session()->flash('message', 'Configuración guardada correctamente.');
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

    public function render()
    {
        return view('livewire.admin.settings', [
            'availableVariables' => ContractTemplateService::availableVariables(),
            'presets' => ContractTemplateService::presets(),
        ])
            ->layout('components.layouts.app', [
                'header' => 'Configuración de Empresa'
            ]);
    }
}
