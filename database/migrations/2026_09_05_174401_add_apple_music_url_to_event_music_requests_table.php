<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_music_requests', function (Blueprint $table) {
            $table->string('apple_music_url')->nullable()->after('spotify_url');
        });
    }

    public function down(): void
    {
        Schema::table('event_music_requests', function (Blueprint $table) {
            $table->dropColumn('apple_music_url');
        });
    }
};
