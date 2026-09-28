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
}
