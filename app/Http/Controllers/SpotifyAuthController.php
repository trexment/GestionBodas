<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SpotifyService;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SpotifyAuthController extends Controller
{
    /**
     * Redirect to Spotify OAuth Authorization Screen
     */
    public function connect()
    {
        $url = SpotifyService::getAuthorizationUrl();
        if (!$url) {
            return redirect()->route('admin.settings')
                ->with('error', 'Por favor, introduce primero tu Client ID y Client Secret de Spotify en la configuración.');
        }

        return redirect()->away($url);
    }

    /**
     * Handle OAuth Callback from Spotify
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('admin.settings')
                ->with('error', 'Autorización cancelada o denegada por Spotify: ' . $request->get('error'));
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('admin.settings')
                ->with('error', 'No se recibió el código de autorización de Spotify.');
        }

        $result = SpotifyService::handleCallback($code);

        if ($result['success']) {
            return redirect()->route('admin.settings')
                ->with('message', $result['message']);
        }

        return redirect()->route('admin.settings')
            ->with('error', $result['message']);
    }

    /**
     * Disconnect Spotify User Account
     */
    public function disconnect()
    {
        SpotifyService::disconnect();
        return redirect()->route('admin.settings')
            ->with('message', 'Cuenta de Spotify desconectada correctamente.');
    }

    /**
     * API Endpoint for Spotify Web Playback SDK
     */
    public function getToken()
    {
        $token = SpotifyService::getValidUserAccessToken();
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No hay cuenta de Spotify conectada.',
            ], 401);
        }

        $user = SpotifyService::getUserDetails();

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * API Endpoint for Apple Music MusicKit JS
     */
    public function getAppleMusicToken()
    {
        $devToken = Setting::get('apple_music_developer_token', '');
        return response()->json([
            'success' => !empty($devToken),
            'developer_token' => $devToken,
            'country' => Setting::get('apple_music_country', 'es'),
        ]);
    }

    /**
     * API Endpoint to find YouTube Video ID for full song streaming
     */
    public function getYoutubeVideoId(Request $request)
    {
        $query = trim($request->get('q', ''));
        if (empty($query)) {
            return response()->json(['success' => false, 'message' => 'Query is required'], 400);
        }

        $videoId = self::searchYoutubeVideoId($query);

        if ($videoId) {
            return response()->json([
                'success' => true,
                'video_id' => $videoId,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No video ID found',
        ], 404);
    }

    /**
     * API Endpoint to resolve audio stream, preview, cover and video ID for any track
     */
    public function resolveTrack(Request $request)
    {
        $title = trim($request->get('title', ''));
        $artist = trim($request->get('artist', ''));
        $query = trim($artist . ' ' . $title);

        if (empty($query)) {
            return response()->json(['success' => false, 'message' => 'Title or query is required'], 400);
        }

        $cacheKey = 'track_res_v2_' . md5(strtolower($query));
        $data = Cache::remember($cacheKey, 86400, function () use ($query, $title, $artist) {
            $previewUrl = null;
            $coverUrl = null;
            $durationMs = 0;
            $trackName = $title;
            $artistName = $artist;

            // Clean title to improve iTunes / Apple Music matching
            $cleanedTitle = preg_replace('/\s*[\(\[].*?[\)\]]/', '', $title);
            $searchTerms = array_unique(array_filter([
                $query,
                trim($artist . ' ' . $cleanedTitle),
                $title,
                $cleanedTitle,
            ]));

            foreach ($searchTerms as $term) {
                try {
                    $res = \Illuminate\Support\Facades\Http::timeout(4)->get('https://itunes.apple.com/search', [
                        'term' => $term,
                        'media' => 'music',
                        'entity' => 'song',
                        'country' => 'es',
                        'limit' => 3,
                    ]);

                    if ($res->successful()) {
                        $json = $res->json();
                        if (!empty($json['results'])) {
                            foreach ($json['results'] as $item) {
                                if (!empty($item['previewUrl'])) {
                                    $previewUrl = $item['previewUrl'];
                                    $coverUrl = str_replace('100x100bb.jpg', '600x600bb.jpg', $item['artworkUrl100'] ?? '');
                                    $durationMs = $item['trackTimeMillis'] ?? 0;
                                    $trackName = $item['trackName'] ?? $title;
                                    $artistName = $item['artistName'] ?? $artist;
                                    break 2;
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }

            return [
                'resolved' => !empty($previewUrl) || !empty($coverUrl),
                'preview_url' => $previewUrl,
                'cover_url' => $coverUrl,
                'duration_ms' => $durationMs,
                'track_name' => $trackName,
                'artist_name' => $artistName,
                'spotify_url' => 'https://open.spotify.com/search/' . urlencode($query),
                'apple_music_url' => 'https://music.apple.com/es/search?term=' . urlencode($query),
            ];
        });

        return response()->json(array_merge(['success' => true], $data));
    }

    /**
     * Helper to find YouTube Video ID across multiple sources
     */
    public static function searchYoutubeVideoId(string $query): ?string
    {
        $query = trim($query);
        if (empty($query)) {
            return null;
        }

        // Direct YouTube URL parsing
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $query, $match)) {
            return $match[1];
        }

        $cacheKey = 'yt_vid_v2_' . md5(strtolower($query));
        return Cache::remember($cacheKey, 86400, function () use ($query) {
            // 1. YouTube Data API (if key is configured in settings or env)
            $apiKey = Setting::get('youtube_api_key', config('services.youtube.api_key', env('YOUTUBE_API_KEY')));
            if (!empty($apiKey)) {
                try {
                    $res = \Illuminate\Support\Facades\Http::timeout(4)->get('https://www.googleapis.com/youtube/v3/search', [
                        'part' => 'id',
                        'q' => $query . ' audio',
                        'type' => 'video',
                        'maxResults' => 1,
                        'key' => $apiKey,
                    ]);
                    if ($res->successful()) {
                        $json = $res->json();
                        if (!empty($json['items'][0]['id']['videoId'])) {
                            return $json['items'][0]['id']['videoId'];
                        }
                    }
                } catch (\Throwable $e) {}
            }

            // 2. Direct YouTube Scraping with Consent Cookies & Proper User Agent
            try {
                $searchUrl = 'https://www.youtube.com/results?search_query=' . urlencode($query . ' audio') . '&sp=EgIQAQ%253D%253D';
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
                    'Cookie' => 'SOCS=CAESEwgDEgk2MTQyMDM1MzgaAmVuIAEaBgiA_LyaBg; CONSENT=YES+cb.20210328-17-p0.es+FX+999',
                ])->timeout(4)->get($searchUrl);

                if ($response->successful()) {
                    $html = $response->body();
                    if (preg_match('/"videoId":"([a-zA-Z0-9_-]{11})"/', $html, $m)) {
                        return $m[1];
                    }
                    if (preg_match('/\/watch\?v=([a-zA-Z0-9_-]{11})/', $html, $m)) {
                        return $m[1];
                    }
                }
            } catch (\Throwable $e) {}

            // 3. Invidious Public Instance Fallback
            $invidiousInstances = [
                'https://yewtu.be',
                'https://vid.puffyan.us',
                'https://inv.nadeko.net',
            ];
            foreach ($invidiousInstances as $instance) {
                try {
                    $invRes = \Illuminate\Support\Facades\Http::timeout(3)->get($instance . '/api/v1/search', [
                        'q' => $query,
                        'type' => 'video',
                    ]);
                    if ($invRes->successful()) {
                        $items = $invRes->json();
                        if (is_array($items) && !empty($items[0]['videoId'])) {
                            return $items[0]['videoId'];
                        }
                    }
                } catch (\Throwable $e) {}
            }

            return null;
        });
    }

    /**
     * Download or proxy direct audio MP3 stream for DJ / Client
     */
    public function downloadAudio(\App\Models\EventMusicRequest $musicRequest)
    {
        $title = $musicRequest->title ?: 'Cancion';
        $artist = $musicRequest->artist ?: '';
        $cleanFilename = \Illuminate\Support\Str::slug(($artist ? $artist . ' - ' : '') . $title) ?: 'track';

        // 1. Auto-resolve if empty
        if (empty($musicRequest->audio_file)) {
            $meta = \App\Services\MusicSearchService::resolveTrackMetadata($musicRequest->title, $musicRequest->artist ?? '');
            if (!empty($meta['preview_url'])) {
                $musicRequest->update(['audio_file' => $meta['preview_url']]);
            }
        }

        if (empty($musicRequest->audio_file)) {
            $fallbackUrl = $musicRequest->youtube_url ?: ("https://www.youtube.com/results?search_query=" . urlencode($musicRequest->title . ' ' . $musicRequest->artist));
            return redirect()->away($fallbackUrl);
        }

        $audioFile = trim($musicRequest->audio_file);

        // 2. Local storage file
        if (!str_starts_with($audioFile, 'http://') && !str_starts_with($audioFile, 'https://')) {
            $ext = pathinfo($audioFile, PATHINFO_EXTENSION) ?: 'mp3';
            $fullPath = storage_path('app/public/' . $audioFile);
            if (file_exists($fullPath)) {
                return response()->download($fullPath, "{$cleanFilename}.{$ext}");
            }
        }

        // 3. Remote URL stream proxy
        $streamUrl = $musicRequest->audio_url ?: $audioFile;

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                ])
                ->get($streamUrl);

            if ($response->successful()) {
                $contentType = $response->header('Content-Type') ?: 'audio/mpeg';
                $ext = (str_contains($contentType, 'mp4') || str_contains($contentType, 'm4a') || str_contains($streamUrl, '.m4a')) ? 'm4a' : 'mp3';

                return response($response->body(), 200, [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => "attachment; filename=\"{$cleanFilename}.{$ext}\"",
                    'Content-Length' => strlen($response->body()),
                    'Cache-Control' => 'no-cache, private',
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Remote audio proxy failed for {$streamUrl}: " . $e->getMessage());
        }

        return redirect()->away($streamUrl);
    }

    /**
     * Proxy streaming of Google Drive audio directly to HTML5 audio players
     */
    public function streamGoogleDriveAudio(string $fileId)
    {
        $fileId = trim($fileId);
        $key = Setting::get('google_drive_api_key', config('services.google.drive_api_key', env('GOOGLE_DRIVE_API_KEY')));

        $targetUrl = !empty($key)
            ? "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media&key={$key}"
            : "https://drive.google.com/uc?export=download&id={$fileId}";

        try {
            $context = stream_context_create([
                'http' => [
                    'follow_location' => 1,
                    'timeout' => 30,
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
                ],
            ]);

            $handle = @fopen($targetUrl, 'rb', false, $context);
            if ($handle) {
                return response()->stream(function () use ($handle) {
                    while (!feof($handle)) {
                        echo fread($handle, 1024 * 64);
                        flush();
                    }
                    fclose($handle);
                }, 200, [
                    'Content-Type' => 'audio/mpeg',
                    'Accept-Ranges' => 'bytes',
                    'Access-Control-Allow-Origin' => '*',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Google Drive stream proxy error for {$fileId}: " . $e->getMessage());
        }

        return redirect()->away("https://drive.google.com/uc?export=download&id={$fileId}");
    }
}
