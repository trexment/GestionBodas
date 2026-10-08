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
            $table->string('partner_name')->nullable()->after('client_id');
            $table->string('partner_phone')->nullable()->after('partner_name');
            $table->string('partner_email')->nullable()->after('partner_phone');
            $table->string('partner_dni')->nullable()->after('partner_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'partner_name',
                'partner_phone',
                'partner_email',
                'partner_dni',
            ]);
        });
    }
};
