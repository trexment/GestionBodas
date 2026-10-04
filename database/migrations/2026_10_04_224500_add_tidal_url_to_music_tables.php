<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('event_music_requests') && !Schema::hasColumn('event_music_requests', 'tidal_url')) {
            Schema::table('event_music_requests', function (Blueprint $table) {
                $table->string('tidal_url')->nullable()->after('apple_music_url');
            });
        }

        if (Schema::hasTable('tracks') && !Schema::hasColumn('tracks', 'tidal_url')) {
            Schema::table('tracks', function (Blueprint $table) {
                $table->string('tidal_url')->nullable()->after('apple_music_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('event_music_requests') && Schema::hasColumn('event_music_requests', 'tidal_url')) {
            Schema::table('event_music_requests', function (Blueprint $table) {
                $table->dropColumn('tidal_url');
            });
        }

        if (Schema::hasTable('tracks') && Schema::hasColumn('tracks', 'tidal_url')) {
            Schema::table('tracks', function (Blueprint $table) {
                $table->dropColumn('tidal_url');
            });
        }
    }
};
