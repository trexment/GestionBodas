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

        $cacheKey = 'yt_vid_' . md5(strtolower($query));
        $videoId = Cache::remember($cacheKey, 86400, function () use ($query) {
            $url = 'https://www.youtube.com/results?search_query=' . urlencode($query . ' audio');
            $ctx = stream_context_create([
                'http' => [
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\nAccept-Language: es-ES,es;q=0.9\r\n",
                    'timeout' => 5,
                ]
            ]);

            $res = @file_get_contents($url, false, $ctx);
            if ($res && preg_match('/"videoId":"([a-zA-Z0-9_-]{11})"/', $res, $m)) {
                return $m[1];
            }
            return null;
        });

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
}
