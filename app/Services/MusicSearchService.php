<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

/**
 * Servicio de búsqueda musical y obtención de metadatos, carátulas, preescuchas e importación de playlists desde iTunes, Apple Music y Spotify.
 */
class MusicSearchService
{
    /**
     * Search songs in Apple Music / iTunes and Spotify
     * Returns a unified array of track results with album covers, preview URLs and deep links.
     */
    public static function search(string $query, int $limit = 25): array
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
                        'tidal_url' => $dt->tidal_url ?: "https://listen.tidal.com/search?q={$searchQuery}",
                        'tidal_uri' => "tidal://search?q=" . rawurlencode($searchQuery),
                        'youtube_url' => $dt->youtube_url ?: "https://www.youtube.com/results?search_query={$searchQuery}",
                    ];
                }
            } catch (\Throwable $e) {}

            // 1. Deezer API (Gratuita, sin credenciales, preescuchas de 30s en MP3 de 128kbps y carátulas HD)
            try {
                $deezerResults = self::searchDeezerApi($query, min($limit, 15));
                if (!empty($deezerResults)) {
                    $results = array_merge($results, $deezerResults);
                }
            } catch (\Throwable $e) {}

            // 2. iTunes / Apple Music Search API (Rápida, pública, carátulas 600x600 y preescuchas)
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
                                'tidal_url' => "https://listen.tidal.com/search?q={$searchQuery}",
                                'tidal_uri' => "tidal://search?q=" . rawurlencode($artist . ' ' . $title),
                                'youtube_url' => "https://www.youtube.com/results?search_query={$searchQuery}",
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore and try Spotify if configured
            }

            // 3. Opcional: Enriquecimiento con Spotify Web API si Client ID y Secret están configurados
            $spotifyResults = self::searchSpotifyApi($query, $limit);
            if (!empty($spotifyResults)) {
                $results = array_merge($results, $spotifyResults);
            }

            // Desduplicar inteligentemente por título + artista preservando preescucha de audio y carátula
            $unique = [];
            $seen = [];
            foreach ($results as $r) {
                $key = self::normalizeMusicText($r['artist'] . '|' . $r['title'], true);
                if (!isset($seen[$key])) {
                    $seen[$key] = count($unique);
                    $unique[] = $r;
                } else {
                    $idx = $seen[$key];
                    // Si el elemento ya guardado no tiene preview pero este duplicado sí, enriquecerlo
                    if (empty($unique[$idx]['preview_url']) && !empty($r['preview_url'])) {
                        $unique[$idx]['preview_url'] = $r['preview_url'];
                    }
                    if (empty($unique[$idx]['cover_url']) && !empty($r['cover_url'])) {
                        $unique[$idx]['cover_url'] = $r['cover_url'];
                    }
                }
                if (count($unique) >= $limit) {
                    break;
                }
            }

            return $unique;
        });
    }

    /**
     * Search directly in Deezer API (No API key needed, high-quality 30s MP3 preview audio and album covers)
     */
    public static function searchDeezerApi(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        try {
            $response = Http::timeout(4)->get('https://api.deezer.com/search', [
                'q' => $query,
                'limit' => $limit,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['data'] ?? [];
                $out = [];
                foreach ($items as $item) {
                    $title = $item['title'] ?? ($item['title_short'] ?? '');
                    $artist = $item['artist']['name'] ?? 'Artista';
                    $album = $item['album']['title'] ?? '';
                    $cover = $item['album']['cover_big'] ?? ($item['album']['cover_medium'] ?? ($item['album']['cover'] ?? ''));
                    $previewUrl = $item['preview'] ?? null;
                    $deezerLink = $item['link'] ?? null;
                    $durationSec = (int)($item['duration'] ?? 0);
                    $durationMs = $durationSec * 1000;

                    $searchQuery = urlencode($artist . ' ' . $title);
                    $spotifyUrl = "https://open.spotify.com/search/{$searchQuery}";
                    $spotifyUri = "spotify:search:" . rawurlencode($artist . ' ' . $title);
                    $appleMusicUrl = "https://music.apple.com/es/search?term={$searchQuery}";

                    $out[] = [
                        'source' => 'deezer',
                        'id' => 'dz_' . ($item['id'] ?? uniqid()),
                        'title' => $title,
                        'artist' => $artist,
                        'album' => $album,
                        'cover_url' => $cover,
                        'preview_url' => $previewUrl,
                        'duration_ms' => $durationMs,
                        'duration_formatted' => gmdate($durationSec > 3600 ? 'H:i:s' : 'i:s', $durationSec),
                        'spotify_url' => $spotifyUrl,
                        'spotify_uri' => $spotifyUri,
                        'apple_music_url' => $appleMusicUrl,
                        'apple_music_uri' => "music://music.apple.com/search?term=" . $searchQuery,
                        'tidal_url' => "https://listen.tidal.com/search?q={$searchQuery}",
                        'tidal_uri' => "tidal://search?q=" . rawurlencode($artist . ' ' . $title),
                        'youtube_url' => "https://www.youtube.com/results?search_query={$searchQuery}",
                        'deezer_url' => $deezerLink,
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
     * Search directly in Spotify Web API if credentials are provided
     */
    public static function searchSpotifyApi(string $query, int $limit = 10): array
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
                        'tidal_url' => "https://listen.tidal.com/search?q={$searchQuery}",
                        'tidal_uri' => "tidal://search?q=" . rawurlencode($artist . ' ' . $title),
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
     * Normaliza un texto musical (minúsculas, sin tildes ni paréntesis secundarios) para comparar coincidencias reales.
     */
    private static function normalizeMusicText(string $text, bool $stripParentheses = true): string
    {
        $t = mb_strtolower(trim($text), 'UTF-8');
        if ($stripParentheses) {
            $t = (string) preg_replace('/\s*[\(\[].*?[\)\]]/u', '', $t);
        }
        $t = strtr($t, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);
        $t = (string) preg_replace('/\b(feat\.?|ft\.?|con|with)\b.*$/iu', '', $t);
        $t = (string) preg_replace('/[^a-z0-9\s]/u', ' ', $t);
        return trim((string) preg_replace('/\s+/u', ' ', $t));
    }

    /**
     * Evalúa qué tan bien coincide un resultado candidato (de Spotify, Deezer o iTunes) con el título y artista pedidos.
     * Devuelve < 0 si NO es la canción solicitada (ej. distinto artista o karaoke/tributo no pedido).
     */
    public static function scoreTrackCandidate(string $reqTitle, string $reqArtist, string $candTitle, string $candArtist): int
    {
        $normReqTitle = self::normalizeMusicText($reqTitle, true);
        $normReqArtist = self::normalizeMusicText($reqArtist, true);
        $normCandTitle = self::normalizeMusicText($candTitle, true);
        $normCandArtist = self::normalizeMusicText($candArtist, false);
        $rawCandCombined = mb_strtolower($candTitle . ' ' . $candArtist, 'UTF-8');
        $rawReqCombined = mb_strtolower($reqTitle . ' ' . $reqArtist, 'UTF-8');

        if ($normReqTitle === '' || $normCandTitle === '') {
            return -100;
        }

        // Penalizar versiones karaoke, tributo, ringtone o instrumentales si el usuario no las pidió explícitamente
        $junkWords = ['karaoke', 'tribute', 'made famous', 'in the style of', 'ringtone', '8-bit', 'lullaby', 'cover version', 'instrumental version'];
        foreach ($junkWords as $junk) {
            if (str_contains($rawCandCombined, $junk) && !str_contains($rawReqCombined, $junk)) {
                return -100;
            }
        }

        $score = 0;

        // 1. Evaluar Título
        if ($normCandTitle === $normReqTitle) {
            $score += 60;
        } elseif (str_starts_with($normCandTitle, $normReqTitle) || str_starts_with($normReqTitle, $normCandTitle)) {
            $score += 45;
        } elseif (str_contains($normCandTitle, $normReqTitle) || str_contains($normReqTitle, $normCandTitle)) {
            $score += 30;
        } else {
            similar_text($normReqTitle, $normCandTitle, $pctTitle);
            if ($pctTitle >= 75) {
                $score += 25;
            } else {
                return -50; // El título no se parece
            }
        }

        // 2. Evaluar Artista (si el usuario indicó artista, DEBE coincidir razonablemente)
        if ($normReqArtist !== '') {
            $artistParts = array_filter(preg_split('/\s*(?:,|&|\/|\by\b|\band\b|\bx\b)\s*/u', $normReqArtist) ?: [$normReqArtist]);
            $artistMatched = false;

            if ($normCandArtist === $normReqArtist || str_contains($normCandArtist, $normReqArtist) || str_contains($normReqArtist, $normCandArtist)) {
                $score += 60;
                $artistMatched = true;
            } else {
                foreach ($artistParts as $part) {
                    $part = trim($part);
                    if (strlen($part) >= 3 && str_contains($normCandArtist, $part)) {
                        $score += 45;
                        $artistMatched = true;
                        break;
                    }
                }
            }

            if (!$artistMatched) {
                similar_text($normReqArtist, $normCandArtist, $pctArtist);
                if ($pctArtist >= 70) {
                    $score += 30;
                } else {
                    // Artista distinto al solicitado -> descartar para no poner otra canción con el mismo título
                    return -80;
                }
            }
        } else {
            // Si no se especificó artista, bonificar ligeramente para priorizar resultados relevantes
            $score += 15;
        }

        return $score;
    }

    /**
     * Automatically resolve and enrich metadata (Spotify URL, Apple Music URL, YouTube URL, Preview URL, Cover Art)
     * for a given title and artist.
     */
    public static function resolveTrackMetadata(string $title, string $artist = '', bool $forceRefresh = false): array
    {
        $rawTitle = trim($title);
        $rawArtist = trim($artist);

        if (empty($rawTitle) || str_starts_with(mb_strtolower($rawTitle), 'estilos:')) {
            return [
                'is_drive_library' => false,
                'spotify_url' => null,
                'apple_music_url' => null,
                'tidal_url' => null,
                'youtube_url' => null,
                'preview_url' => null,
                'cover_url' => null,
                'detected_title' => $rawTitle,
                'detected_artist' => $rawArtist,
            ];
        }

        // Construir pares candidatos (título, artista) considerando posibles permutaciones y separadores
        $candidatePairs = [];
        $candidatePairs[] = ['title' => $rawTitle, 'artist' => $rawArtist];

        if ($rawArtist === '') {
            // Separador con guión o barra: "Artista - Canción" o "Canción - Artista"
            if (preg_match('/^(.*?)\s*(?:[-–—\/|])\s*(.*)$/u', $rawTitle, $m)) {
                $p1 = trim($m[1]);
                $p2 = trim($m[2]);
                if (!empty($p1) && !empty($p2)) {
                    $candidatePairs[] = ['title' => $p1, 'artist' => $p2];
                    $candidatePairs[] = ['title' => $p2, 'artist' => $p1];
                }
            }
            // "Canción de Artista" o "Canción by Artista"
            if (preg_match('/^(.*?)\s+(?:de|by)\s+(.*)$/iu', $rawTitle, $m)) {
                $p1 = trim($m[1]);
                $p2 = trim($m[2]);
                if (!empty($p1) && !empty($p2)) {
                    $candidatePairs[] = ['title' => $p1, 'artist' => $p2];
                    $candidatePairs[] = ['title' => $p2, 'artist' => $p1];
                }
            }
            // Paréntesis al final: "Canción (Artista)"
            if (preg_match('/^(.*?)\s*[\(\[]([^\)\]]+)[\)\]]$/u', $rawTitle, $m)) {
                $p1 = trim($m[1]);
                $p2 = trim($m[2]);
                if (!empty($p1) && !empty($p2)) {
                    $candidatePairs[] = ['title' => $p1, 'artist' => $p2];
                }
            }
        } else {
            // Si el artista fue introducido pero podría estar invertido
            $candidatePairs[] = ['title' => $rawArtist, 'artist' => $rawTitle];
            if (preg_match('/^(.*?)\s*(?:[-–—\/|])\s*(.*)$/u', $rawTitle, $m)) {
                $candidatePairs[] = ['title' => trim($m[1]), 'artist' => $rawArtist];
                $candidatePairs[] = ['title' => trim($m[2]), 'artist' => $rawArtist];
            }
        }

        $cacheKey = 'track_meta_v4_' . md5(mb_strtolower($rawArtist . '|' . $rawTitle));
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 86400, function () use ($candidatePairs, $rawTitle, $rawArtist) {
            // 0. Prioridad 1: Comprobar si la canción exacta está en la Biblioteca General de Google Drive
            foreach ($candidatePairs as $pair) {
                $driveTrack = \App\Services\CloudMusicStorageService::findInDriveLibrary($pair['title'], $pair['artist']);
                if ($driveTrack && !empty($driveTrack->audio_url)) {
                    return [
                        'is_drive_library' => true,
                        'spotify_url' => $driveTrack->spotify_url,
                        'apple_music_url' => $driveTrack->apple_music_url,
                        'tidal_url' => $driveTrack->tidal_url,
                        'youtube_url' => $driveTrack->youtube_url,
                        'preview_url' => $driveTrack->audio_url,
                        'cover_url' => null,
                        'detected_title' => $driveTrack->title ?: $pair['title'],
                        'detected_artist' => $driveTrack->artist ?: $pair['artist'],
                    ];
                }
            }

            $bestCandidate = null;
            $bestScore = -1;
            $bestPair = $candidatePairs[0];

            // 1. Probar Deezer API (Gratuita, sin claves, preescuchas de 30s en MP3 fiable y carátulas HD)
            foreach ($candidatePairs as $pair) {
                $q = trim(($pair['artist'] ? $pair['artist'] . ' ' : '') . $pair['title']);
                if (empty($q)) continue;

                $deezerResults = self::searchDeezerApi($q, 8);
                foreach ($deezerResults as $cand) {
                    $sc = self::scoreTrackCandidate($pair['title'], $pair['artist'], (string)($cand['title'] ?? ''), (string)($cand['artist'] ?? ''));
                    if ($sc > $bestScore) {
                        $bestScore = $sc;
                        $bestCandidate = $cand;
                        $bestPair = [
                            'title' => $cand['title'] ?? $pair['title'],
                            'artist' => $cand['artist'] ?? $pair['artist'],
                        ];
                    }
                }
                if ($bestScore >= 90) {
                    break;
                }
            }

            // 2. Probar iTunes / Apple Music si Deezer no superó score 90 o no tenía preview
            if ($bestScore < 90 || empty($bestCandidate['preview_url'])) {
                foreach ($candidatePairs as $pair) {
                    $cleanedTitle = trim((string) preg_replace('/\s*[\(\[].*?[\)\]]/u', '', $pair['title'])) ?: $pair['title'];
                    $searchTerms = array_values(array_unique(array_filter([
                        trim(($pair['artist'] ? $pair['artist'] . ' ' : '') . $pair['title']),
                        $cleanedTitle,
                    ])));

                    foreach ($searchTerms as $term) {
                        try {
                            $country = Setting::get('apple_music_country', 'es');
                            $res = Http::timeout(4)->get('https://itunes.apple.com/search', [
                                'term' => $term,
                                'media' => 'music',
                                'entity' => 'song',
                                'country' => $country,
                                'limit' => 12,
                            ]);

                            if ($res->successful()) {
                                $json = $res->json();
                                foreach (($json['results'] ?? []) as $it) {
                                    $sc = self::scoreTrackCandidate(
                                        $pair['title'],
                                        $pair['artist'],
                                        (string) ($it['trackName'] ?? ''),
                                        (string) ($it['artistName'] ?? '')
                                    );
                                    if ($sc > $bestScore) {
                                        $bestScore = $sc;
                                        $cover = $it['artworkUrl100'] ?? '';
                                        if ($cover) {
                                            $cover = str_replace(['100x100bb.jpg', '100x100bb.png'], '600x600bb.jpg', $cover);
                                        }
                                        $bestCandidate = [
                                            'source' => 'apple_music',
                                            'title' => $it['trackName'] ?? '',
                                            'artist' => $it['artistName'] ?? '',
                                            'album' => $it['collectionName'] ?? '',
                                            'cover_url' => $cover,
                                            'preview_url' => $it['previewUrl'] ?? null,
                                            'apple_music_url' => $it['trackViewUrl'] ?? null,
                                        ];
                                        $bestPair = [
                                            'title' => $it['trackName'] ?? $pair['title'],
                                            'artist' => $it['artistName'] ?? $pair['artist'],
                                        ];
                                    }
                                }
                                if ($bestScore >= 90 && !empty($bestCandidate['preview_url'])) {
                                    break 2;
                                }
                            }
                        } catch (\Throwable $e) {}
                    }
                }
            }

            // 3. Probar Spotify API para obtener enlaces directos externos si hay credenciales
            $spotifyUrl = null;
            $resolvedArtist = $bestPair['artist'] ?: $rawArtist;
            $resolvedTitle = $bestPair['title'] ?: $rawTitle;

            $spotifyQueries = array_values(array_unique(array_filter([
                $resolvedArtist !== '' ? "track:{$resolvedTitle} artist:{$resolvedArtist}" : null,
                trim($resolvedArtist . ' ' . $resolvedTitle),
            ])));

            foreach ($spotifyQueries as $spQ) {
                $spotifyResults = self::searchSpotifyApi($spQ, 6);
                foreach ($spotifyResults as $spTrack) {
                    $sc = self::scoreTrackCandidate($resolvedTitle, $resolvedArtist, (string)($spTrack['title'] ?? ''), (string)($spTrack['artist'] ?? ''));
                    if ($sc >= 30) {
                        $spotifyUrl = $spTrack['spotify_url'] ?? null;
                        if (empty($bestCandidate['cover_url']) && !empty($spTrack['cover_url'])) {
                            $bestCandidate['cover_url'] = $spTrack['cover_url'];
                        }
                        if (empty($bestCandidate['preview_url']) && !empty($spTrack['preview_url'])) {
                            $bestCandidate['preview_url'] = $spTrack['preview_url'];
                        }
                        break 2;
                    }
                }
            }

            // 4. Buscar YouTube Video ID
            $youtubeUrl = null;
            $searchFull = trim($resolvedArtist . ' ' . $resolvedTitle);
            try {
                $ytVideoId = \App\Http\Controllers\SpotifyAuthController::searchYoutubeVideoId($searchFull);
                if ($ytVideoId) {
                    $youtubeUrl = "https://www.youtube.com/watch?v={$ytVideoId}";
                }
            } catch (\Throwable $e) {}

            // Construir enlaces de fallback si no se encontraron enlaces directos
            $encodedQuery = urlencode($searchFull);
            if (empty($spotifyUrl)) {
                $spotifyUrl = "https://open.spotify.com/search/{$encodedQuery}";
            }
            $appleMusicUrl = $bestCandidate['apple_music_url'] ?? "https://music.apple.com/es/search?term={$encodedQuery}";
            $tidalUrl = "https://listen.tidal.com/search?q={$encodedQuery}";
            if (empty($youtubeUrl)) {
                $youtubeUrl = "https://www.youtube.com/results?search_query={$encodedQuery}";
            }

            return [
                'is_drive_library' => false,
                'spotify_url' => $spotifyUrl,
                'apple_music_url' => $appleMusicUrl,
                'tidal_url' => $tidalUrl,
                'youtube_url' => $youtubeUrl,
                'preview_url' => $bestCandidate['preview_url'] ?? null,
                'cover_url' => $bestCandidate['cover_url'] ?? null,
                'detected_title' => $bestScore >= 25 ? $resolvedTitle : $rawTitle,
                'detected_artist' => $bestScore >= 25 ? $resolvedArtist : $rawArtist,
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
                                    'tidal_url' => "https://listen.tidal.com/search?q={$searchQuery}",
                                    'tidal_uri' => "tidal://search?q=" . rawurlencode($artist . ' ' . $title),
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
                                    'tidal_url' => "https://listen.tidal.com/search?q={$searchQuery}",
                                    'tidal_uri' => "tidal://search?q=" . rawurlencode($artist . ' ' . $title),
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

    /**
     * Get or cache Tidal OAuth 2.0 Access Token
     */
    public static function getTidalAccessToken(string $clientId, string $clientSecret): ?string
    {
        return Cache::remember('tidal_access_token', 3300, function () use ($clientId, $clientSecret) {
            try {
                $res = Http::asForm()
                    ->withBasicAuth($clientId, $clientSecret)
                    ->timeout(4)
                    ->post('https://auth.tidal.com/v1/oauth2/token', [
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
     * Test Tidal Developer API Connection
     */
    public static function testTidalConnection(): array
    {
        $clientId = Setting::get('tidal_client_id');
        $clientSecret = Setting::get('tidal_client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return [
                'success' => false,
                'message' => 'Falta configurar el Client ID o Client Secret de Tidal Developer.',
            ];
        }

        Cache::forget('tidal_access_token');
        $token = self::getTidalAccessToken($clientId, $clientSecret);

        if ($token) {
            return [
                'success' => true,
                'message' => '¡Conexión con Tidal API establecida correctamente!',
            ];
        }

        return [
            'success' => false,
            'message' => 'No se pudo autenticar con Tidal. Verifica que el Client ID y Client Secret de developer.tidal.com sean correctos.',
        ];
    }
}
