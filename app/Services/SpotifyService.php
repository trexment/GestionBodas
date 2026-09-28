<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use Carbon\Carbon;

class SpotifyService
{
    /**
     * Get Spotify OAuth Authorization URL for Web Playback SDK (Streaming)
     */
    public static function getAuthorizationUrl(): ?string
    {
        $clientId = Setting::get('spotify_client_id');
        if (empty($clientId)) {
            return null;
        }

        $redirectUri = route('spotify.callback');
        $scopes = [
            'streaming',
            'user-read-email',
            'user-read-private',
            'user-modify-playback-state',
            'user-read-playback-state',
        ];

        $params = [
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes),
            'show_dialog' => 'true',
        ];

        return 'https://accounts.spotify.com/authorize?' . http_build_query($params);
    }

    /**
     * Handle OAuth Callback Code from Spotify
     */
    public static function handleCallback(string $code): array
    {
        $clientId = Setting::get('spotify_client_id');
        $clientSecret = Setting::get('spotify_client_secret');
        $redirectUri = route('spotify.callback');

        if (empty($clientId) || empty($clientSecret)) {
            return [
                'success' => false,
                'message' => 'Client ID o Client Secret no configurados en el sistema.',
            ];
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post('https://accounts.spotify.com/api/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'];
                $refreshToken = $data['refresh_token'] ?? null;
                $expiresIn = $data['expires_in'] ?? 3600;

                Setting::set('spotify_user_access_token', $accessToken);
                if ($refreshToken) {
                    Setting::set('spotify_user_refresh_token', $refreshToken);
                }
                Setting::set('spotify_token_expires_at', Carbon::now()->addSeconds($expiresIn - 60)->toDateTimeString());

                // Fetch user profile info
                $profileRes = Http::withToken($accessToken)->get('https://api.spotify.com/v1/me');
                if ($profileRes->successful()) {
                    $profile = $profileRes->json();
                    Setting::set('spotify_user_name', $profile['display_name'] ?? $profile['id'] ?? 'Usuario Spotify');
                    Setting::set('spotify_user_email', $profile['email'] ?? '');
                    Setting::set('spotify_user_product', $profile['product'] ?? 'free');
                }

                return [
                    'success' => true,
                    'message' => '¡Cuenta de Spotify vinculada con éxito!',
                ];
            } else {
                $err = $response->json('error_description') ?: 'Error al obtener token de Spotify.';
                return [
                    'success' => false,
                    'message' => $err,
                ];
            }
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Excepción durante la autenticación: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get a valid user access token, refreshing it automatically if expired
     */
    public static function getValidUserAccessToken(): ?string
    {
        $accessToken = Setting::get('spotify_user_access_token');
        $refreshToken = Setting::get('spotify_user_refresh_token');
        $expiresAt = Setting::get('spotify_token_expires_at');

        if (empty($accessToken) || empty($refreshToken)) {
            return null;
        }

        // Check if token is still valid (with 60s safety margin)
        if (!empty($expiresAt) && Carbon::parse($expiresAt)->isFuture()) {
            return $accessToken;
        }

        // Refresh token
        $clientId = Setting::get('spotify_client_id');
        $clientSecret = Setting::get('spotify_client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return null;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post('https://accounts.spotify.com/api/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $newAccessToken = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;

                Setting::set('spotify_user_access_token', $newAccessToken);
                if (!empty($data['refresh_token'])) {
                    Setting::set('spotify_user_refresh_token', $data['refresh_token']);
                }
                Setting::set('spotify_token_expires_at', Carbon::now()->addSeconds($expiresIn - 60)->toDateTimeString());

                return $newAccessToken;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Disconnect user account
     */
    public static function disconnect(): void
    {
        Setting::set('spotify_user_access_token', null);
        Setting::set('spotify_user_refresh_token', null);
        Setting::set('spotify_token_expires_at', null);
        Setting::set('spotify_user_name', null);
        Setting::set('spotify_user_email', null);
        Setting::set('spotify_user_product', null);
    }

    /**
     * Check if Spotify user is connected
     */
    public static function isConnected(): bool
    {
        return !empty(self::getValidUserAccessToken());
    }

    /**
     * Get user details
     */
    public static function getUserDetails(): array
    {
        return [
            'connected' => self::isConnected(),
            'name' => Setting::get('spotify_user_name', ''),
            'email' => Setting::get('spotify_user_email', ''),
            'product' => Setting::get('spotify_user_product', ''),
            'is_premium' => strtolower(Setting::get('spotify_user_product', '')) === 'premium',
        ];
    }

    /**
     * Create a playlist on user's Spotify account for a specific event
     */
    public static function createPlaylistForEvent(\App\Models\Event $event): array
    {
        $token = self::getValidUserAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'message' => 'No hay una cuenta de Spotify conectada. Conéctala en Ajustes > Spotify & Apple Music.',
            ];
        }

        try {
            // 1. Fetch current user id
            $meRes = Http::withToken($token)->get('https://api.spotify.com/v1/me');
            if (!$meRes->successful()) {
                return ['success' => false, 'message' => 'Error al verificar la cuenta de Spotify.'];
            }
            $userId = $meRes->json('id');

            // 2. Create playlist
            $dateStr = $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') : '';
            $playlistRes = Http::withToken($token)->post("https://api.spotify.com/v1/users/{$userId}/playlists", [
                'name' => "{$event->name} - Playlist del Evento",
                'description' => "Canciones y momentos para {$event->name} ({$dateStr}) generada desde el panel.",
                'public' => false,
            ]);

            if (!$playlistRes->successful()) {
                return ['success' => false, 'message' => 'Error al crear la playlist en Spotify: ' . ($playlistRes->json('error.message') ?? '')];
            }

            $playlistData = $playlistRes->json();
            $playlistId = $playlistData['id'];
            $playlistUrl = $playlistData['external_urls']['spotify'] ?? '';

            // 3. Search and collect tracks
            $requests = $event->musicRequests()->where('status', '!=', 'rejected')->get();
            $trackUris = [];

            foreach ($requests as $req) {
                if (!empty($req->spotify_url) && preg_match('/track\/([a-zA-Z0-9]+)/', $req->spotify_url, $m)) {
                    $trackUris[] = "spotify:track:" . $m[1];
                } else {
                    $query = trim("{$req->title} {$req->artist}");
                    if (!empty($query)) {
                        $searchRes = Http::withToken($token)->get('https://api.spotify.com/v1/search', [
                            'q' => $query,
                            'type' => 'track',
                            'limit' => 1,
                        ]);
                        if ($searchRes->successful()) {
                            $items = $searchRes->json('tracks.items');
                            if (!empty($items[0]['uri'])) {
                                $trackUris[] = $items[0]['uri'];
                            }
                        }
                    }
                }
            }

            if (!empty($trackUris)) {
                $trackUris = array_values(array_unique($trackUris));
                foreach (array_chunk($trackUris, 100) as $chunk) {
                    Http::withToken($token)->post("https://api.spotify.com/v1/playlists/{$playlistId}/tracks", [
                        'uris' => $chunk,
                    ]);
                }
            }

            return [
                'success' => true,
                'message' => '¡Playlist "' . $playlistData['name'] . '" creada en tu Spotify con ' . count($trackUris) . ' canciones!',
                'url' => $playlistUrl,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Excepción al crear la playlist en Spotify: ' . $e->getMessage(),
            ];
        }
    }
}
