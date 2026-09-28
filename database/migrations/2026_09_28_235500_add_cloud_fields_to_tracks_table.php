<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->string('cloud_id')->nullable()->after('file_path');
            $table->string('cloud_folder')->nullable()->after('cloud_id');
            $table->string('source')->default('local')->after('cloud_folder');
            $table->string('spotify_url')->nullable()->after('source');
            $table->string('apple_music_url')->nullable()->after('spotify_url');
            $table->string('youtube_url')->nullable()->after('apple_music_url');
        });
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn([
                'cloud_id',
                'cloud_folder',
                'source',
                'spotify_url',
                'apple_music_url',
                'youtube_url',
            ]);
        });
    }
};
