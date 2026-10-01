<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class MusicSearchService
{
    /**
     * Search songs in Apple Music / iTunes and Spotify
     * Returns a unified array of track results with album covers, preview URLs and deep links.
     */
    public static function search(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        $cacheKey = 'music_search_' . md5(strtolower($query) . '_' . $limit);
        
        return Cache::remember($cacheKey, 3600, function () use ($query, $limit) {
            $results = [];

            // 0. Prioridad 1: Buscar primero en la Biblioteca General de Google Drive (Tracks)
            try {
                $driveTracks = \App\Models\Track::where(function($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('artist', 'LIKE', "%{$query}%")
                      ->orWhere('genre', 'LIKE', "%{$query}%");
                })->limit($limit)->get();

                foreach ($driveTracks as $dt) {
                    $searchQuery = urlencode(trim(($dt->artist ? $dt->artist . ' ' : '') . $dt->title));
                    $results[] = [
                        'source' => $dt->source ?: 'google_drive',
                        'is_drive_library' => true,
                        'id' => 'drive_' . $dt->id,
                        'title' => $dt->title,
                        'artist' => $dt->artist ?: 'Mi Biblioteca',
                        'album' => $dt->cloud_folder ?: 'Google Drive',
                        'cover_url' => '',
                        'preview_url' => $dt->audio_url,
                        'duration_ms' => 0,
                        'duration_formatted' => 'MP3 Nube',
                        'spotify_url' => $dt->spotify_url ?: "https://open.spotify.com/search/{$searchQuery}",
                        'spotify_uri' => "spotify:search:" . rawurlencode($searchQuery),
                        'apple_music_url' => $dt->apple_music_url ?: "https://music.apple.com/es/search?term={$searchQuery}",
                        'apple_music_uri' => "music://music.apple.com/search?term=" . $searchQuery,
                        'youtube_url' => $dt->youtube_url ?: "https://www.youtube.com/results?search_query={$searchQuery}",
                    ];
                }
            } catch (\Throwable $e) {}

            // 1. iTunes / Apple Music Search API (Fast, Free, High-res album arts and 30s official audio previews)
            try {
                $country = Setting::get('apple_music_country', 'es');
                $response = Http::timeout(4)->get('https://itunes.apple.com/search', [
                    'term' => $query,
                    'media' => 'music',
                    'entity' => 'song',
                    'country' => $country,
                    'limit' => $limit,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['results'])) {
                        foreach ($data['results'] as $item) {
                            $title = $item['trackName'] ?? 'Sin título';
                            $artist = $item['artistName'] ?? 'Artista Desconocido';
                            $album = $item['collectionName'] ?? '';
                            $cover = $item['artworkUrl100'] ?? '';
                            if ($cover) {
                                // Request high-resolution artwork 600x600
                                $cover = str_replace(['100x100bb.jpg', '100x100bb.png'], '600x600bb.jpg', $cover);
                            }

                            $previewUrl = $item['previewUrl'] ?? null;
                            $appleUrl = $item['trackViewUrl'] ?? null;
                            $durationMs = $item['trackTimeMillis'] ?? 0;
                            
                            $searchQuery = urlencode($artist . ' ' . $title);
                            $spotifyUrl = "https://open.spotify.com/search/{$searchQuery}";
                            $spotifyUri = "spotify:search:" . rawurlencode($artist . ' ' . $title);
                            $appleUri = "music://music.apple.com/search?term=" . $searchQuery;

                            $results[] = [
                                'source' => 'apple_music',
                                'id' => (string)($item['trackId'] ?? uniqid()),
                                'title' => $title,
                                'artist' => $artist,
                                'album' => $album,
                                'cover_url' => $cover,
                                'preview_url' => $previewUrl,
                                'duration_ms' => $durationMs,
                                'duration_formatted' => gmdate($durationMs > 3600000 ? 'H:i:s' : 'i:s', (int)($durationMs / 1000)),
                                'spotify_url' => $spotifyUrl,
                                'spotify_uri' => $spotifyUri,
                                'apple_music_url' => $appleUrl ?: "https://music.apple.com/es/search?term={$searchQuery}",
                                'apple_music_uri' => $appleUri,
                                'youtube_url' => "https://www.youtube.com/results?search_query={$searchQuery}",
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore and try Spotify if configured
            }

            // 2. Optional Spotify API enrichment if Client ID and Secret are configured
            $spotifyResults = self::searchSpotifyApi($query, $limit);
            if (!empty($spotifyResults)) {
                // Merge or prioritize Spotify results if available
                $results = array_merge($spotifyResults, $results);
            }

            // Deduplicate by title + artist
            $unique = [];
            $seen = [];
            foreach ($results as $r) {
                $key = strtolower(trim($r['artist'] . '|' . $r['title']));
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $unique[] = $r;
                }
                if (count($unique) >= $limit) {
                    break;
                }
            }

            return $unique;
        });
    }

    /**
     * Search directly in Spotify Web API if credentials are provided
     */
    public static function searchSpotifyApi(string $query, int $limit = 5): array
    {
        $clientId = Setting::get('spotify_client_id');
        $clientSecret = Setting::get('spotify_client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return [];
        }

        $token = self::getSpotifyAccessToken($clientId, $clientSecret);
        if (!$token) {
            return [];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(4)
                ->get('https://api.spotify.com/v1/search', [
                    'q' => $query,
                    'type' => 'track',
                    'market' => 'ES',
                    'limit' => $limit,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['tracks']['items'] ?? [];
                $out = [];
                foreach ($items as $track) {
                    $title = $track['name'] ?? '';
                    $artist = $track['artists'][0]['name'] ?? '';
                    $album = $track['album']['name'] ?? '';
                    $cover = $track['album']['images'][0]['url'] ?? '';
                    $previewUrl = $track['preview_url'] ?? null;
                    $spotifyUrl = $track['external_urls']['spotify'] ?? '';
                    $spotifyUri = $track['uri'] ?? ("spotify:track:" . ($track['id'] ?? ''));
                    $durationMs = $track['duration_ms'] ?? 0;
                    $searchQuery = urlencode($artist . ' ' . $title);

                    $out[] = [
                        'source' => 'spotify',
                        'id' => $track['id'] ?? uniqid(),
                        'title' => $title,
                        'artist' => $artist,
                        'album' => $album,
                        'cover_url' => $cover,
                        'preview_url' => $previewUrl,
                        'duration_ms' => $durationMs,
                        'duration_formatted' => gmdate($durationMs > 3600000 ? 'H:i:s' : 'i:s', (int)($durationMs / 1000)),
                        'spotify_url' => $spotifyUrl,
                        'spotify_uri' => $spotifyUri,
                        'apple_music_url' => "https://music.apple.com/es/search?term={$searchQuery}",
                        'apple_music_uri' => "music://music.apple.com/search?term=" . $searchQuery,
                        'youtube_url' => "https://www.youtube.com/results?search_query={$searchQuery}",
                    ];
                }
                return $out;
            }
        } catch (\Throwable $e) {
            return [];
        }

        return [];
    }

    /**
     * Get or cache Spotify Client Credentials Access Token
     */
    public static function getSpotifyAccessToken(string $clientId, string $clientSecret): ?string
    {
        return Cache::remember('spotify_access_token', 3300, function () use ($clientId, $clientSecret) {
            try {
                $res = Http::asForm()
                    ->withBasicAuth($clientId, $clientSecret)
                    ->timeout(4)
                    ->post('https://accounts.spotify.com/api/token', [
                        'grant_type' => 'client_credentials',
                    ]);

                if ($res->successful()) {
                    return $res->json('access_token');
                }
            } catch (\Throwable $e) {
                return null;
            }
            return null;
        });
    }

    /**
     * Automatically resolve and enrich metadata (Spotify URL, Apple Music URL, YouTube URL, Preview URL, Cover Art)
     * for a given title and artist.
     */
    public static function resolveTrackMetadata(string $title, string $artist = ''): array
    {
        $title = trim($title);
        $artist = trim($artist);
        $query = trim($artist . ' ' . $title);

        if (empty($query)) {
            return [
                'spotify_url' => null,
                'apple_music_url' => null,
                'youtube_url' => null,
                'preview_url' => null,
                'cover_url' => null,
            ];
        }

        $cacheKey = 'track_meta_v2_' . md5(strtolower($query));
        return Cache::remember($cacheKey, 86400, function () use ($query, $title, $artist) {
            $spotifyUrl = null;
            $appleMusicUrl = null;
            $youtubeUrl = null;
            $previewUrl = null;
            $coverUrl = null;
            $isDriveLibrary = false;

            // 0. Prioridad 1: Comprobar si la canción está en la Biblioteca General de Google Drive
            $driveTrack = \App\Services\CloudMusicStorageService::findInDriveLibrary($title, $artist);
            if ($driveTrack && !empty($driveTrack->audio_url)) {
                $previewUrl = $driveTrack->audio_url;
                $spotifyUrl = $driveTrack->spotify_url;
                $appleMusicUrl = $driveTrack->apple_music_url;
                $youtubeUrl = $driveTrack->youtube_url;
                $isDriveLibrary = true;
            }

            // 1. Try search in Spotify API if available
            $spotifyResults = self::searchSpotifyApi($query, 1);
            if (!empty($spotifyResults[0])) {
                $item = $spotifyResults[0];
                if (!$spotifyUrl) $spotifyUrl = $item['spotify_url'] ?? null;
                if (!$coverUrl) $coverUrl = $item['cover_url'] ?? null;
                if (!$previewUrl) $previewUrl = $item['preview_url'] ?? null;
            }

            // 2. Search in iTunes / Apple Music (Free, fast, universal)
            $cleanedTitle = preg_replace('/\s*[\(\[].*?[\)\]]/', '', $title);
            $searchTerms = array_unique(array_filter([
                $query,
                trim($artist . ' ' . $cleanedTitle),
                $title,
            ]));

            foreach ($searchTerms as $term) {
                try {
                    $country = Setting::get('apple_music_country', 'es');
                    $res = Http::timeout(3)->get('https://itunes.apple.com/search', [
                        'term' => $term,
                        'media' => 'music',
                        'entity' => 'song',
                        'country' => $country,
                        'limit' => 2,
                    ]);

                    if ($res->successful()) {
                        $json = $res->json();
                        if (!empty($json['results'][0])) {
                            $it = $json['results'][0];
                            if (!$previewUrl && !empty($it['previewUrl'])) {
                                $previewUrl = $it['previewUrl'];
                            }
                            if (!$appleMusicUrl && !empty($it['trackViewUrl'])) {
                                $appleMusicUrl = $it['trackViewUrl'];
                            }
                            if (!$coverUrl && !empty($it['artworkUrl100'])) {
                                $coverUrl = str_replace('100x100bb.jpg', '600x600bb.jpg', $it['artworkUrl100']);
                            }
                            break;
                        }
                    }
                } catch (\Throwable $e) {}
            }

            // 3. Search direct YouTube Video ID as secondary fallback for rare tracks
            try {
                $ytVideoId = \App\Http\Controllers\SpotifyAuthController::searchYoutubeVideoId($query);
                if ($ytVideoId) {
                    $youtubeUrl = "https://www.youtube.com/watch?v={$ytVideoId}";
                }
            } catch (\Throwable $e) {}

            // Fallbacks if not exact link found
            $encodedQuery = urlencode($query);
            if (empty($spotifyUrl)) {
                $spotifyUrl = "https://open.spotify.com/search/{$encodedQuery}";
            }
            if (empty($appleMusicUrl)) {
                $appleMusicUrl = "https://music.apple.com/es/search?term={$encodedQuery}";
            }
            if (empty($youtubeUrl)) {
                $youtubeUrl = "https://www.youtube.com/results?search_query={$encodedQuery}";
            }

            return [
                'is_drive_library' => $isDriveLibrary,
                'spotify_url' => $spotifyUrl,
                'apple_music_url' => $appleMusicUrl,
                'youtube_url' => $youtubeUrl,
                'preview_url' => $previewUrl,
                'cover_url' => $coverUrl,
            ];
        });
    }

    /**
     * Get Top 50 Trending Songs / Charts (Spain, Global, Party & Viral)
     */
    public static function getTopCharts(string $chartType = 'spain_top50', int $limit = 50): array
    {
        $cacheKey = 'top_music_chart_' . $chartType . '_' . $limit;

        try {
            return Cache::remember($cacheKey, 14400, function () use ($chartType, $limit) {
                $tracks = [];

                // 1. Intentar obtener desde Spotify Playlists oficiales si hay token
                $spotifyPlaylistId = match ($chartType) {
                    'spain_top50' => '37i9dQZEVXbNFJfN1Vw8d9', // Top 50 España
                    'global_top50' => '37i9dQZEVXbMDoHDwVN2tF', // Top 50 Global
                    'party_spain' => '37i9dQZF1DX1qNSk37U9hB', // Éxitos España / Fiesta
                    'viral_spain' => '37i9dQZEVXbJvgoTIeZvdn', // Top Viral España
                    default => '37i9dQZEVXbNFJfN1Vw8d9',
                };

                $clientId = Setting::get('spotify_client_id');
                $clientSecret = Setting::get('spotify_client_secret');
                $token = null;
                if (!empty($clientId) && !empty($clientSecret)) {
                    $token = self::getSpotifyAccessToken($clientId, $clientSecret);
                }

                if ($token && $spotifyPlaylistId) {
                    try {
                        $response = Http::timeout(5)->withToken($token)->get("https://api.spotify.com/v1/playlists/{$spotifyPlaylistId}/tracks", [
                            'limit' => $limit,
                            'market' => 'ES',
                        ]);

                        if ($response->successful()) {
                            $items = $response->json('items') ?: [];
                            $pos = 1;
                            foreach ($items as $wrapper) {
                                $item = $wrapper['track'] ?? null;
                                if (!$item || empty($item['name'])) continue;

                                $artist = !empty($item['artists']) ? implode(', ', array_column($item['artists'], 'name')) : 'Artista';
                                $title = $item['name'];
                                $cover = $item['album']['images'][0]['url'] ?? '';
                                $spotifyUrl = $item['external_urls']['spotify'] ?? '';
                                $searchQuery = urlencode($artist . ' ' . $title);
                                $currentPos = $pos++;

                                $tracks[] = [
                                    'position' => $currentPos,
                                    'rank' => $currentPos,
                                    'id' => 'sp_' . ($item['id'] ?? uniqid()),
                                    'title' => $title,
                                    'artist' => $artist,
                                    'album' => $item['album']['name'] ?? '',
                                    'cover_url' => $cover,
                                    'preview_url' => $item['preview_url'] ?? null,
                                    'duration_ms' => $item['duration_ms'] ?? 0,
                                    'duration_formatted' => gmdate(($item['duration_ms'] ?? 0) > 3600000 ? 'H:i:s' : 'i:s', (int)(($item['duration_ms'] ?? 0) / 1000)),
                                    'spotify_url' => $spotifyUrl ?: "https://open.spotify.com/search/{$searchQuery}",
                                    'spotify_uri' => $item['uri'] ?? ("spotify:search:" . rawurlencode($artist . ' ' . $title)),
                                    'apple_music_url' => "https://music.apple.com/es/search?term={$searchQuery}",
                                    'youtube_url' => "https://www.youtube.com/results?search_query={$searchQuery}",
                                ];
                            }
                        }
                    } catch (\Throwable $e) {}
                }

                // 2. Fallback Apple Music / iTunes RSS Feed si no hay resultados de Spotify
                if (empty($tracks)) {
                    try {
                        $country = ($chartType === 'global_top50') ? 'us' : 'es';
                        $response = Http::timeout(5)->get("https://rss.applemarketingtools.com/api/v2/{$country}/music/most-played/{$limit}/songs.json");

                        if ($response->successful()) {
                            $feedResults = $response->json('feed.results') ?: [];
                            $pos = 1;
                            foreach ($feedResults as $song) {
                                $title = $song['name'] ?? 'Sin título';
                                $artist = $song['artistName'] ?? 'Artista';
                                $cover = $song['artworkUrl100'] ?? '';
                                if ($cover) {
                                    $cover = str_replace(['100x100bb.jpg', '100x100bb.png'], '600x600bb.jpg', $cover);
                                }
                                $appleUrl = $song['url'] ?? '';
                                $searchQuery = urlencode($artist . ' ' . $title);
                                $currentPos = $pos++;

                                $tracks[] = [
                                    'position' => $currentPos,
                                    'rank' => $currentPos,
                                    'id' => 'ap_' . ($song['id'] ?? uniqid()),
                                    'title' => $title,
                                    'artist' => $artist,
                                    'album' => '',
                                    'cover_url' => $cover,
                                    'preview_url' => null,
                                    'duration_ms' => 0,
                                    'duration_formatted' => 'Hit',
                                    'spotify_url' => "https://open.spotify.com/search/{$searchQuery}",
                                    'spotify_uri' => "spotify:search:" . rawurlencode($artist . ' ' . $title),
                                    'apple_music_url' => $appleUrl ?: "https://music.apple.com/es/search?term={$searchQuery}",
                                    'youtube_url' => "https://www.youtube.com/results?search_query={$searchQuery}",
                                ];
                            }
                        }
                    } catch (\Throwable $e) {}
                }

                return $tracks;
            }) ?: [];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("TopCharts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Test Spotify Connection
     */
    public static function testSpotifyConnection(): array
    {
        $clientId = Setting::get('spotify_client_id');
        $clientSecret = Setting::get('spotify_client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return [
                'success' => false,
                'message' => 'Falta configurar el Client ID o Client Secret de Spotify.',
            ];
        }

        Cache::forget('spotify_access_token');
        $token = self::getSpotifyAccessToken($clientId, $clientSecret);

        if ($token) {
            return [
                'success' => true,
                'message' => '¡Conexión con Spotify API establecida correctamente!',
            ];
        }

        return [
            'success' => false,
            'message' => 'No se pudo autenticar con Spotify. Verifica que el Client ID y Client Secret sean correctos.',
        ];
    }
}
