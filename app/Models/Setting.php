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
}

