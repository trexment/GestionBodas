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
        if (Schema::hasTable('event_provider_bookings')) {
            Schema::table('event_provider_bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('event_provider_bookings', 'has_refund')) {
                    $table->boolean('has_refund')->default(false)->after('paid_by');
                }
                if (!Schema::hasColumn('event_provider_bookings', 'refund_amount')) {
                    $table->decimal('refund_amount', 10, 2)->default(0)->after('has_refund');
                }
                if (!Schema::hasColumn('event_provider_bookings', 'refund_recipient')) {
                    $table->string('refund_recipient', 50)->default('50_50')->after('refund_amount');
                }
                if (!Schema::hasColumn('event_provider_bookings', 'refund_fran_amount')) {
                    $table->decimal('refund_fran_amount', 10, 2)->default(0)->after('refund_recipient');
                }
                if (!Schema::hasColumn('event_provider_bookings', 'refund_miguel_amount')) {
                    $table->decimal('refund_miguel_amount', 10, 2)->default(0)->after('refund_fran_amount');
                }
                if (!Schema::hasColumn('event_provider_bookings', 'management_fee')) {
                    $table->decimal('management_fee', 10, 2)->default(0)->after('refund_miguel_amount');
                }
                if (!Schema::hasColumn('event_provider_bookings', 'social_security_fee')) {
                    $table->decimal('social_security_fee', 10, 2)->default(0)->after('management_fee');
                }
                if (!Schema::hasColumn('event_provider_bookings', 'tax_fee')) {
                    $table->decimal('tax_fee', 10, 2)->default(0)->after('social_security_fee');
                }
                if (!Schema::hasColumn('event_provider_bookings', 'registration_fee')) {
                    $table->decimal('registration_fee', 10, 2)->default(0)->after('tax_fee');
                }
                if (!Schema::hasColumn('event_provider_bookings', 'settlement_number')) {
                    $table->string('settlement_number', 100)->nullable()->after('registration_fee');
                }
                if (!Schema::hasColumn('event_provider_bookings', 'invoice_number')) {
                    $table->string('invoice_number', 100)->nullable()->after('settlement_number');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('event_provider_bookings')) {
            Schema::table('event_provider_bookings', function (Blueprint $table) {
                $columns = [
                    'has_refund',
                    'refund_amount',
                    'refund_recipient',
                    'refund_fran_amount',
                    'refund_miguel_amount',
                    'management_fee',
                    'social_security_fee',
                    'tax_fee',
                    'registration_fee',
                    'settlement_number',
                    'invoice_number',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('event_provider_bookings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
