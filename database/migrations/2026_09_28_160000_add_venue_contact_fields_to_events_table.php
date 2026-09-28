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
            $table->string('venue_contact_name')->nullable()->after('location');
            $table->string('venue_contact_phone')->nullable()->after('venue_contact_name');
            $table->text('venue_notes')->nullable()->after('venue_contact_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['venue_contact_name', 'venue_contact_phone', 'venue_notes']);
        });
    }
};
