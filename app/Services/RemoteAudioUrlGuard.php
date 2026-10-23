<?php

namespace App\Services;

/**
 * Protección anti-SSRF para URLs de audio remotas.
 *
 * El servidor solo descarga (proxy) audio de proveedores conocidos y nunca de
 * direcciones internas/privadas. Cualquier otra URL se trata como enlace externo
 * (redirección en el navegador del usuario), sin que el servidor la solicite.
 */
class RemoteAudioUrlGuard
{
    /**
     * Dominios desde los que el servidor puede descargar audio (incluye subdominios).
     */
    protected const ALLOWED_HOSTS = [
        'itunes.apple.com',
        'apple.com',
        'mzstatic.com',
        'scdn.co',
        'spotifycdn.com',
        'dzcdn.net',
        'deezer.com',
        'dropbox.com',
        'dropboxusercontent.com',
        'drive.google.com',
        'googleusercontent.com',
        'googleapis.com',
    ];

    /**
     * Normaliza una URL de audio introducida desde el cliente: solo admite http(s).
     * Devuelve cadena vacía si no es válida (así no se aceptan rutas locales arbitrarias).
     */
    public static function sanitizeInput(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '' || strlen($url) > 2000) {
            return '';
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true) || !parse_url($url, PHP_URL_HOST)) {
            return '';
        }
        return $url;
    }

    /**
     * Indica si el servidor puede hacer la petición a esta URL.
     */
    public static function isSafeToFetch(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return false;
        }

        if (strtolower($parts['scheme'] ?? '') !== 'https') {
            return false;
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        if (isset($parts['port']) && (int) $parts['port'] !== 443) {
            return false;
        }

        $host = strtolower(rtrim($parts['host'], '.'));
        if (!self::hostIsAllowed($host)) {
            return false;
        }

        // Todas las IPs resueltas deben ser públicas (evita DNS apuntando a la red interna)
        $ips = @gethostbynamel($host) ?: [];
        if (empty($ips)) {
            return false;
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Solo permite redirigir el navegador a URLs http(s).
     */
    public static function isSafeToRedirect(string $url): bool
    {
        return self::sanitizeInput($url) !== '';
    }

    protected static function hostIsAllowed(string $host): bool
    {
        foreach (self::ALLOWED_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }
        return false;
    }
}
