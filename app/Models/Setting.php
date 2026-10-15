<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key.
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set($key, $value)
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get the absolute filesystem path for the company logo (used in PDFs).
     */
    public static function getLogoPathForPdf()
    {
        $logo = self::get('company_logo');
        if (!$logo) {
            return null;
        }

        $cleanPath = ltrim($logo, '/');

        // Check storage/app/public/
        if (file_exists(storage_path('app/public/' . $cleanPath))) {
            return storage_path('app/public/' . $cleanPath);
        }

        // Check public/storage/
        if (file_exists(public_path('storage/' . $cleanPath))) {
            return public_path('storage/' . $cleanPath);
        }

        // Check public/
        if (file_exists(public_path($cleanPath))) {
            return public_path($cleanPath);
        }

        return null;
    }

    /**
     * Get the public URL for the company logo.
     */
    public static function getLogoUrl()
    {
        $logo = self::get('company_logo');
        if (!$logo) {
            return null;
        }

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }

        $cleanPath = ltrim($logo, '/');

        // Check if the physical file exists on the server
        if (!file_exists(storage_path('app/public/' . $cleanPath)) &&
            !file_exists(public_path('storage/' . $cleanPath)) &&
            !file_exists(public_path($cleanPath))) {
            return null;
        }

        return asset('storage/' . $cleanPath);
    }

    /**
     * Get the absolute filesystem path for the company signature / stamp (used in PDFs).
     */
    public static function getCompanySignaturePathForPdf(?string $brandKey = null): ?string
    {
        $sig = null;
        if (!empty($brandKey)) {
            $sig = self::get('brand_' . $brandKey . '_signature');
        }
        if (!$sig) {
            $sig = self::get('company_signature');
        }

        if (!$sig) {
            return null;
        }

        $cleanPath = ltrim($sig, '/');

        // Check storage/app/public/
        if (file_exists(storage_path('app/public/' . $cleanPath))) {
            return storage_path('app/public/' . $cleanPath);
        }

        // Check public/storage/
        if (file_exists(public_path('storage/' . $cleanPath))) {
            return public_path('storage/' . $cleanPath);
        }

        // Check public/
        if (file_exists(public_path($cleanPath))) {
            return public_path($cleanPath);
        }

        return null;
    }

    /**
     * Get the public URL for the company signature / stamp.
     */
    public static function getCompanySignatureUrl(?string $brandKey = null): ?string
    {
        $sig = null;
        if (!empty($brandKey)) {
            $sig = self::get('brand_' . $brandKey . '_signature');
        }
        if (!$sig) {
            $sig = self::get('company_signature');
        }

        if (!$sig) {
            return null;
        }

        if (str_starts_with($sig, 'http://') || str_starts_with($sig, 'https://')) {
            return $sig;
        }

        $cleanPath = ltrim($sig, '/');

        if (!file_exists(storage_path('app/public/' . $cleanPath)) &&
            !file_exists(public_path('storage/' . $cleanPath)) &&
            !file_exists(public_path($cleanPath))) {
            return null;
        }

        return asset('storage/' . $cleanPath);
    }

    /**
     * Get the dynamic favicon URL.
     */
    public static function getFaviconUrl()
    {
        return self::getLogoUrl() ?: asset('favicon.svg');
    }

    /**
     * Get the company name with default fallback.
     */
    public static function getCompanyName($default = 'Núñez and Son')
    {
        return self::get('company_name', $default);
    }

    /**
     * Get the company subtitle with default fallback.
     */
    public static function getCompanySubtitle($default = 'Sound in Motion')
    {
        return self::get('company_subtitle', $default);
    }

    /**
     * Get list of available brand profiles.
     */
    public static function getAvailableBrands(): array
    {
        return [
            'nunez_and_son' => [
                'name' => self::get('brand_nunez_name', self::get('company_name', 'Núñez and Son')),
                'icon' => '👑',
                'label' => 'Núñez and Son (Bodas & Sonorización)',
            ],
            'javnx' => [
                'name' => self::get('brand_javnx_name', 'JAVNX DJ'),
                'icon' => '🎧',
                'label' => 'JAVNX DJ (Eventos & Sesiones)',
            ],
            'mago_leugim' => [
                'name' => self::get('brand_leugim_name', 'Mago Leugim'),
                'icon' => '🎩',
                'label' => 'Mago Leugim (Ilusionismo & Magia)',
            ],
        ];
    }

    /**
     * Get the detected brand key based on the current request domain / host.
     * e.g., app.javnxdj.com or javnxdj.com -> 'javnx'
     *       magoleugim.es or leugim -> 'mago_leugim'
     *       otherwise default_brand or 'nunez_and_son'
     */
    public static function getDetectedBrand(): string
    {
        if (!app()->runningInConsole() && request()) {
            $host = strtolower(request()->getHost());
            if (str_contains($host, 'javnx')) {
                return 'javnx';
            }
            if (str_contains($host, 'leugim')) {
                return 'mago_leugim';
            }
        }
        return self::get('default_brand', 'nunez_and_son');
    }

    /**
     * Comprueba si el servicio de videógrafo / colaboración está habilitado para una marca o dominio.
     * Por defecto habilitado para JAVNX DJ (javnxdj.com) y deshabilitado para Núñez and Son (nunezandson.com).
     */
    public static function isVideoServiceEnabled(?string $brandKey = null): bool
    {
        if (empty($brandKey)) {
            $brandKey = self::getDetectedBrand();
        }

        $saved = self::get('video_service_brands');
        if ($saved !== null) {
            $decoded = is_array($saved) ? $saved : json_decode($saved, true);
            if (is_array($decoded)) {
                return in_array($brandKey, $decoded);
            }
        }

        // Por defecto: solo habilitado para 'javnx' (javnxdj.com)
        return $brandKey === 'javnx';
    }

    /**
     * Get details for a specific brand profile ('nunez_and_son' | 'javnx' | 'mago_leugim')
     */
    public static function getBrandInfo(?string $brandKey = null): array
    {
        if (empty($brandKey)) {
            $brandKey = self::getDetectedBrand();
        }

        if ($brandKey === 'mago_leugim') {
            $name = self::get('brand_leugim_name', 'Mago Leugim');
            $subtitle = self::get('brand_leugim_subtitle', 'Ilusionismo, Magia de Cerca & Eventos');
            $phone = self::get('brand_leugim_phone');
            if (empty($phone) || strlen(trim(preg_replace('/[^0-9]/', '', $phone))) < 7) {
                $phone = '+34 674 37 89 93 (Miguel)';
            }
            $phone2 = self::get('brand_leugim_phone_2');
            if (empty($phone2) || strlen(trim(preg_replace('/[^0-9]/', '', $phone2))) < 7) {
                $phone2 = '+34 622 62 47 90 (Fran)';
            }
            $email = self::get('brand_leugim_email', self::get('company_email', 'magoleugim@gmail.com'));
            $website = self::get('brand_leugim_website', 'magoleugim.es');
            if (empty($website) || str_contains($website, 'landing-bodas')) {
                $website = 'magoleugim.es';
            }
            $logo = self::get('brand_leugim_logo', self::get('company_logo'));
        } elseif ($brandKey === 'javnx') {
            $name = self::get('brand_javnx_name', 'JAVNX DJ');
            $subtitle = self::get('brand_javnx_subtitle', 'DJ & Producción de Eventos');
            $phone = self::get('brand_javnx_phone', self::get('company_phone'));
            if (empty($phone) || strlen(trim(preg_replace('/[^0-9]/', '', $phone))) < 7) {
                $phone = '+34 622 62 47 90';
            }
            $phone2 = self::get('brand_javnx_phone_2', '');
            $email = self::get('brand_javnx_email', self::get('company_email', 'info@javnxdj.com'));
            $website = self::get('brand_javnx_website', 'javnxdj.com');
            if (empty($website) || str_contains($website, 'landing-bodas')) {
                $website = 'javnxdj.com';
            }
            $logo = self::get('brand_javnx_logo', self::get('company_logo'));
        } else {
            // Default: Núñez and Son
            $name = self::get('brand_nunez_name', self::get('company_name', 'Núñez and Son'));
            $subtitle = self::get('brand_nunez_subtitle', self::get('company_subtitle', 'DJ & Sonido'));
            $phone = self::get('brand_nunez_phone', self::get('company_phone'));
            if (empty($phone) || strlen(trim(preg_replace('/[^0-9]/', '', $phone))) < 7) {
                $phone = '+34 622 62 47 90 (Fran)';
            }
            $phone2 = self::get('brand_nunez_phone_2', self::get('company_phone_2'));
            if (empty($phone2) || strlen(trim(preg_replace('/[^0-9]/', '', $phone2))) < 7) {
                $phone2 = '+34 674 37 89 93 (Miguel)';
            }
            $email = self::get('brand_nunez_email', self::get('company_email', 'info@nunezandson.com'));
            if (empty($email) || $email === 'info@eventosmusicales.es') {
                $email = 'info@nunezandson.com';
            }
            $website = self::get('brand_nunez_website', self::get('company_website', 'nunezandson.com'));
            if (empty($website) || str_contains($website, 'landing-bodas')) {
                $website = 'nunezandson.com';
            }
            $logo = self::get('brand_nunez_logo', self::get('company_logo'));
        }

        // Resolve logo path for PDF
        $logoPath = null;
        if (!empty($logo)) {
            $cleanPath = ltrim($logo, '/');
            if (file_exists(storage_path('app/public/' . $cleanPath))) {
                $logoPath = storage_path('app/public/' . $cleanPath);
            } elseif (file_exists(public_path('storage/' . $cleanPath))) {
                $logoPath = public_path('storage/' . $cleanPath);
            } elseif (file_exists(public_path($cleanPath))) {
                $logoPath = public_path($cleanPath);
            }
        }

        if (!$logoPath && $brandKey === 'nunez_and_son' && file_exists(storage_path('app/public/logos/BqwZKGcLFmX0kuiPh9EWKg3BKmHnRB3puOsxm9TI.png'))) {
            $logoPath = storage_path('app/public/logos/BqwZKGcLFmX0kuiPh9EWKg3BKmHnRB3puOsxm9TI.png');
        }

        $cif = self::get('brand_' . $brandKey . '_cif', self::get('company_cif', 'B-12345678'));
        $iban = self::get('brand_' . $brandKey . '_iban', self::get('company_iban', 'ES00 0000 0000 0000 0000 0000'));
        $bizum = self::get('brand_' . $brandKey . '_bizum', self::get('company_bizum', '622634790'));

        return [
            'key' => $brandKey,
            'name' => $name,
            'subtitle' => $subtitle,
            'phone' => $phone,
            'phone_2' => $phone2,
            'email' => $email,
            'website' => $website,
            'logo' => $logo,
            'logo_path' => $logoPath,
            'cif' => $cif,
            'iban' => $iban,
            'bizum' => $bizum,
        ];
    }

    /**
     * Devuelve la lista de correos electrónicos de los administradores de la empresa y de la marca.
     */
    public static function getAdminNotificationEmails(?string $brandKey = null): array
    {
        $emails = [];

        // 1. Emails de los usuarios administradores registrados en la plataforma
        try {
            $adminUserEmails = \App\Models\User::where('role', 'admin')
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();
            $emails = array_merge($emails, $adminUserEmails);
        } catch (\Throwable $e) {
            // Ignorar en caso de error
        }

        // 2. Email de la marca específica (según dominio/marca del evento)
        $brandInfo = self::getBrandInfo($brandKey);
        if (!empty($brandInfo['email'])) {
            $emails[] = $brandInfo['email'];
        }

        // 3. Fallback a email general de la empresa y from.address
        $companyEmail = self::get('company_email');
        if (!empty($companyEmail)) {
            $emails[] = $companyEmail;
        }

        $mailFrom = config('mail.from.address');
        if (!empty($mailFrom)) {
            $emails[] = $mailFrom;
        }

        // Normalizar, limpiar y eliminar duplicados y dominios dummy/inválidos
        $validEmails = [];
        foreach ($emails as $email) {
            $email = trim(mb_strtolower($email));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            // Filtrar dominios dummy de pruebas y dominios no existentes
            if (
                str_ends_with($email, '@example.com') ||
                str_ends_with($email, '@example.org') ||
                str_ends_with($email, '@test.com') ||
                str_ends_with($email, '@localhost') ||
                str_ends_with($email, '@eventosmusicales.es')
            ) {
                continue;
            }
            $validEmails[] = $email;
        }

        return array_values(array_unique($validEmails));
    }

    /**
     * Devuelve los teléfonos de los administradores (Fran y su hermano) para notificaciones y alertas por WhatsApp.
     */
    public static function getAdminNotificationPhones(?string $brandKey = null): array
    {
        $phones = [];

        // 1. Teléfonos de usuarios registrados con rol admin
        try {
            $adminUserPhones = \App\Models\User::where('role', 'admin')
                ->whereNotNull('phone')
                ->pluck('phone')
                ->toArray();
            $phones = array_merge($phones, $adminUserPhones);
        } catch (\Throwable $e) {}

        // 2. Teléfonos configurados en los ajustes de marcas
        $brandInfo = self::getBrandInfo($brandKey);
        if (!empty($brandInfo['phone'])) {
            $phones[] = $brandInfo['phone'];
        }
        if (!empty($brandInfo['phone_2'])) {
            $phones[] = $brandInfo['phone_2'];
        }

        // 3. Teléfonos globales de la empresa
        $companyPhone1 = self::get('company_phone');
        $companyPhone2 = self::get('company_phone_2');
        if (!empty($companyPhone1)) {
            $phones[] = $companyPhone1;
        }
        if (!empty($companyPhone2)) {
            $phones[] = $companyPhone2;
        }

        // 4. Fallbacks por defecto para Fran y su hermano/socio
        $phones[] = '+34 622 62 47 90';
        $phones[] = '+34 674 37 89 93';

        $validPhones = [];
        foreach ($phones as $p) {
            $cleaned = \App\Services\WhatsAppTemplateService::cleanPhone((string)$p);
            if (!empty($cleaned) && strlen($cleaned) >= 9) {
                $validPhones[] = $cleaned;
            }
        }

        return array_values(array_unique($validPhones));
    }

    /**
     * Get the public website URL (auto-detects domain or uses custom setting).
     * e.g., app.javnxdj.com -> https://javnxdj.com
     *       app.nunezandson.com -> https://nunezandson.com
     */
    public static function getPublicWebsiteUrl()
    {
        $customUrl = self::get('public_website_url');
        if (!empty($customUrl)) {
            return $customUrl;
        }

        if (app()->runningInConsole()) {
            return config('app.url', 'https://nunezandson.com');
        }

        $host = request()->getHost();
        $scheme = request()->getScheme();

        // 1. If running under standard subdomain like app.javnxdj.com or app.nunezandson.com
        if (str_starts_with(strtolower($host), 'app.')) {
            $mainDomain = substr($host, 4);
            return $scheme . '://' . $mainDomain;
        }

        // 2. If running under appeventos.frannunez.es
        if (str_starts_with(strtolower($host), 'appeventos.')) {
            $mainDomain = substr($host, 11);
            return $scheme . '://' . $mainDomain;
        }

        // 3. If running locally
        if ($host === 'localhost' || $host === '127.0.0.1' || str_contains($host, '.test') || str_contains($host, '.local')) {
            return url('/');
        }

        return $scheme . '://' . $host;
    }

    /**
     * Get Cocktail + Restaurant combined pack settings and calculation.
     */
    public static function getCocktailRestaurantPackInfo($cocktailPrice = null, $restaurantPrice = null)
    {
        $cocktail = $cocktailPrice !== null ? (float)$cocktailPrice : (float)self::get('price_cocktail', 150);
        $restaurant = $restaurantPrice !== null ? (float)$restaurantPrice : (float)self::get('price_restaurant', 150);
        $sum = $cocktail + $restaurant;

        $enabled = (bool)self::get('pack_cocktail_restaurant_enabled', true);
        $discountType = self::get('pack_cocktail_restaurant_discount_type', 'percentage'); // 'percentage' or 'fixed_price'
        $discountPercent = (float)self::get('pack_cocktail_restaurant_discount_percentage', 25); // default 25%
        $fixedPackPrice = (float)self::get('pack_cocktail_restaurant_price', 220); // default 220€

        if (!$enabled || $sum <= 0) {
            return [
                'enabled' => false,
                'discount_type' => $discountType,
                'discount_percentage' => 0,
                'individual_sum' => $sum,
                'pack_price' => $sum,
                'discount_amount' => 0,
                'savings_label' => 'Sin descuento de pack',
            ];
        }

        if ($discountType === 'fixed_price') {
            $packPrice = max(0, $fixedPackPrice);
            $discountAmount = max(0, $sum - $packPrice);
            $effectivePercent = $sum > 0 ? round(($discountAmount / $sum) * 100, 1) : 0;
        } else {
            // percentage discount
            $discountAmount = round($sum * ($discountPercent / 100), 2);
            $packPrice = max(0, $sum - $discountAmount);
            $effectivePercent = $discountPercent;
        }

        return [
            'enabled' => true,
            'discount_type' => $discountType,
            'discount_percentage' => $effectivePercent,
            'individual_sum' => $sum,
            'pack_price' => $packPrice,
            'discount_amount' => $discountAmount,
            'savings_label' => 'Ahorro de ' . number_format($discountAmount, 2, ',', '.') . ' € (' . round($effectivePercent) . '% dto.)',
        ];
    }

    /**
     * Get DJ Pack resolution and pricing based on requested hours.
     */
    public static function getDjPackForHours(int $hours): array
    {
        $hours = max(1, $hours);

        $basicName = self::get('pack_basic_name', 'Pack Esencial');
        $basicPrice = (float)self::get('pack_basic_price', 600);
        $basicHours = (int)self::get('pack_basic_hours', 4);
        $basicDesc = self::get('pack_basic_desc', '4 horas de sesión DJ. Sonido profesional estéreo (2 altavoces de alta definición) + iluminación rítmica básica de pista (2 focos/efectos LED). Microfonía inalámbrica para discursos.');

        $mediumName = self::get('pack_medium_name', 'Pack Signature');
        $mediumPrice = (float)self::get('pack_medium_price', 950);
        $mediumHours = (int)self::get('pack_medium_hours', 5);
        $mediumDesc = self::get('pack_medium_desc', '5 horas de sesión DJ. Sonido reforzado con subgraves (impacto y pegada en pista). Cabina DJ retroiluminada + puente/barras de iluminación LED sincronizada y ambientación de pista. Microfonía inalámbrica.');

        $premiumName = self::get('pack_premium_name', 'Pack Deluxe Show');
        $premiumPrice = (float)self::get('pack_premium_price', 1350);
        $premiumHours = (int)self::get('pack_premium_hours', 6);
        $premiumDesc = self::get('pack_premium_desc', 'Cobertura completa (hasta 6h). Montaje de gran formato: sonido multizona/subgraves potentes + show de iluminación avanzada sincronizada. Incluye efecto especial de máquina de humo frío / chispas frías para momentos clave (apertura de baile).');

        $extraHourPrice = (float)self::get('price_extra_hours', 120);

        if ($hours >= $premiumHours) {
            $extra = $hours - $premiumHours;
            $price = $premiumPrice + ($extra * $extraHourPrice);
            return [
                'pack_key' => 'premium',
                'name' => $premiumName . ($extra > 0 ? " (+{$extra}h extra)" : ''),
                'base_name' => $premiumName,
                'pack_price' => $premiumPrice,
                'hours_included' => $premiumHours,
                'requested_hours' => $hours,
                'extra_hours' => $extra,
                'extra_hour_price' => $extraHourPrice,
                'total_price' => $price,
                'description' => $premiumDesc . ($extra > 0 ? " Incluye {$extra} hora(s) adicional(es) de fiesta." : ''),
                'badge' => '👑 ' . $premiumName . ($extra > 0 ? " +{$extra}h Extra" : ''),
                'tag' => $premiumName . ' (' . $hours . 'h)',
                'includes_smoke' => true,
                'includes_sparks' => true,
                'effects_badge' => 'Máquina de Humo + Chispas Frías (Fuego frío)',
            ];
        }

        if ($hours >= $mediumHours) {
            $extra = $hours - $mediumHours;
            $price = $mediumPrice + ($extra * $extraHourPrice);
            return [
                'pack_key' => 'medium',
                'name' => $mediumName . ($extra > 0 ? " (+{$extra}h extra)" : ''),
                'base_name' => $mediumName,
                'pack_price' => $mediumPrice,
                'hours_included' => $mediumHours,
                'requested_hours' => $hours,
                'extra_hours' => $extra,
                'extra_hour_price' => $extraHourPrice,
                'total_price' => $price,
                'description' => $mediumDesc . ($extra > 0 ? " Incluye {$extra} hora(s) adicional(es) de fiesta." : ''),
                'badge' => '🌟 ' . $mediumName . ($extra > 0 ? " +{$extra}h Extra" : ''),
                'tag' => $mediumName . ' (' . $hours . 'h)',
                'includes_smoke' => true,
                'includes_sparks' => false,
                'effects_badge' => 'Máquina de Humo para pista',
            ];
        }

        // Basic Pack (covers up to basicHours)
        $extra = max(0, $hours - $basicHours);
        $price = $basicPrice + ($extra * $extraHourPrice);
        return [
            'pack_key' => 'basic',
            'name' => $basicName . ($extra > 0 ? " (+{$extra}h extra)" : ''),
            'base_name' => $basicName,
            'pack_price' => $basicPrice,
            'hours_included' => $basicHours,
            'requested_hours' => $hours,
            'extra_hours' => $extra,
            'extra_hour_price' => $extraHourPrice,
            'total_price' => $price,
            'description' => $basicDesc . ($extra > 0 ? " Incluye {$extra} hora(s) adicional(es) de fiesta." : ''),
            'badge' => '✨ ' . $basicName . ($hours < $basicHours ? ' (Base técnica completa)' : ''),
            'tag' => $basicName . ' (' . $hours . 'h)',
            'includes_smoke' => false,
            'includes_sparks' => false,
            'effects_badge' => '',
        ];
    }
}

