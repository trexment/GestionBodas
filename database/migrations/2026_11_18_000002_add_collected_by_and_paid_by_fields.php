<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('events') && !Schema::hasColumn('events', 'deposit_collected_by')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('deposit_collected_by', 100)->nullable()->default('Fran')->after('deposit_payment_method');
            });
        }

        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'collected_by')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('collected_by', 100)->nullable()->default('Fran')->after('payment_method');
            });
        }

        if (Schema::hasTable('event_provider_bookings') && !Schema::hasColumn('event_provider_bookings', 'paid_by')) {
            Schema::table('event_provider_bookings', function (Blueprint $table) {
                $table->string('paid_by', 100)->nullable()->default('Fran')->after('final_payment_method');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'deposit_collected_by')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('deposit_collected_by');
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'collected_by')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('collected_by');
            });
        }

        if (Schema::hasTable('event_provider_bookings') && Schema::hasColumn('event_provider_bookings', 'paid_by')) {
            Schema::table('event_provider_bookings', function (Blueprint $table) {
                $table->dropColumn('paid_by');
            });
        }
    }
};
