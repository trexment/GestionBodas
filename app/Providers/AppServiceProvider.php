<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor principal de servicios de la aplicación: fuerza HTTPS en producción y verifica la integridad del esquema de base de datos en el arranque.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production') || request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Auto-migración segura en arranque si faltan tablas clave o columnas
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('whatsapp_messages') 
                || !\Illuminate\Support\Facades\Schema::hasTable('event_provider_bookings')
                || !\Illuminate\Support\Facades\Schema::hasColumn('contracts', 'contract_number')
                || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'partner_name')
                || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'no_response_at')
                || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'guest_token')
                || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'deposit_collected_by')
                || !\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'collected_by')
                || !\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'paid_by')
                || !\Illuminate\Support\Facades\Schema::hasColumn('quotes', 'rejection_reason')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Auto-migration check failed: " . $e->getMessage());
        }

        // Asegurar columnas críticas en caliente mediante DDL directo
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('events') && !\Illuminate\Support\Facades\Schema::hasColumn('events', 'deposit_collected_by')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `events` ADD `deposit_collected_by` VARCHAR(100) NULL DEFAULT 'Fran' AFTER `deposit_payment_method`");
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('invoices') && !\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'collected_by')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `invoices` ADD `collected_by` VARCHAR(100) NULL DEFAULT 'Fran' AFTER `payment_method`");
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('event_provider_bookings') && !\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'paid_by')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `paid_by` VARCHAR(100) NULL DEFAULT 'Fran' AFTER `final_payment_method`");
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Direct column injection failed: " . $e->getMessage());
        }
    }
}
