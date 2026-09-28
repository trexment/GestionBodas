<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('deposit_type', 20)->default('percentage')->after('amount'); // 'percentage' or 'fixed'
            $table->decimal('deposit_percentage', 5, 2)->nullable()->default(40.00)->after('deposit_type');
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('deposit_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['deposit_type', 'deposit_percentage', 'deposit_amount']);
        });
    }
};
