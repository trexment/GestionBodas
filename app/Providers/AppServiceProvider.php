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
                || !\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'refund_amount')
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
            if (\Illuminate\Support\Facades\Schema::hasTable('event_provider_bookings')) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'paid_by')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `paid_by` VARCHAR(100) NULL DEFAULT 'Fran' AFTER `final_payment_method`");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'has_refund')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `has_refund` TINYINT(1) NOT NULL DEFAULT 0 AFTER `paid_by`");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'refund_amount')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `has_refund`");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'refund_recipient')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `refund_recipient` VARCHAR(50) NOT NULL DEFAULT '50_50' AFTER `refund_amount`");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'refund_fran_amount')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `refund_fran_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `refund_recipient`");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'refund_miguel_amount')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `refund_miguel_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `refund_fran_amount`");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'management_fee')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `management_fee` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `refund_miguel_amount`");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'social_security_fee')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `social_security_fee` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `management_fee`");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'tax_fee')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `tax_fee` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `social_security_fee`");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'registration_fee')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `registration_fee` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `tax_fee`");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'settlement_number')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `settlement_number` VARCHAR(100) NULL AFTER `registration_fee`");
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'invoice_number')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `event_provider_bookings` ADD `invoice_number` VARCHAR(100) NULL AFTER `settlement_number`");
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Direct column injection failed: " . $e->getMessage());
        }
    }
}
