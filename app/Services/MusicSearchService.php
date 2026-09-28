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
