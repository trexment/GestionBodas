<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\EventMusicRequest;
use App\Services\MusicSearchService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enrich all existing songs without a spotify_url
        try {
            EventMusicRequest::whereNull('spotify_url')
                ->orWhere('spotify_url', '')
                ->chunkById(50, function ($songs) {
                    foreach ($songs as $song) {
                        $title = trim($song->title ?? '');
                        $artist = trim($song->artist ?? '');
                        if (empty($title)) continue;

                        $meta = MusicSearchService::resolveTrackMetadata($title, $artist);
                        $song->updateQuietly([
                            'spotify_url' => $meta['spotify_url'] ?? $song->spotify_url,
                            'apple_music_url' => $meta['apple_music_url'] ?? $song->apple_music_url,
                            'youtube_url' => $meta['youtube_url'] ?? $song->youtube_url,
                            'audio_file' => empty($song->audio_file) ? ($meta['preview_url'] ?? null) : $song->audio_file,
                        ]);
                    }
                });
        } catch (\Throwable $e) {
            // Non-blocking
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed
    }
};
