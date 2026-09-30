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
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('deposit_paid')->default(false)->after('status');
            $table->decimal('deposit_paid_amount', 10, 2)->nullable()->after('deposit_paid');
            $table->string('deposit_payment_method', 50)->nullable()->after('deposit_paid_amount'); // bizum, transfer, cash, card, other
            $table->date('deposit_paid_at')->nullable()->after('deposit_payment_method');
            $table->text('deposit_notes')->nullable()->after('deposit_paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_paid',
                'deposit_paid_amount',
                'deposit_payment_method',
                'deposit_paid_at',
                'deposit_notes',
            ]);
        });
    }
};
