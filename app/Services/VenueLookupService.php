<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VenueLookupService
{
    /**
     * Comprueba si el nombre del lugar es válido para buscar (no vacío ni genérico).
     */
    public static function isSearchableLocation(?string $location): bool
    {
        $clean = mb_strtolower(trim((string)$location));
        if (mb_strlen($clean) < 4) {
            return false;
        }

        $ignored = [
            'por definir',
            'por determinar',
            'sin definir',
            'sin determinar',
            'pendiente',
            'no lo se',
            'no lo sé',
            'aun no',
            'aún no',
            'desconocido',
            'sin lugar',
            'particular',
            'casa particular',
        ];

        foreach ($ignored as $term) {
            if ($clean === $term || str_starts_with($clean, $term . ' ')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Busca automáticamente el teléfono de un restaurante, bodega, finca u hotel:
     * 1) En otros eventos ya guardados en la base de datos.
     * 2) En OpenStreetMap / Nominatim (extratags phone / contact:phone / website).
     * 3) En buscadores web y en la propia web oficial del restaurante (enlaces tel:, JSON-LD schema.org y página de contacto).
     */
    public static function findVenuePhone(?string $location, ?int $excludeEventId = null, bool $forceRefresh = false): ?array
    {
        if (!self::isSearchableLocation($location)) {
            return null;
        }

        $cleanLocation = trim((string)$location);

        // 1. Buscar primero en nuestra propia BD de eventos por si ya se guardó antes esa finca/restaurante
        try {
            $query = Event::whereNotNull('venue_contact_phone')
                ->where('venue_contact_phone', '!=', '')
                ->where(function ($q) use ($cleanLocation) {
                    $q->where('location', $cleanLocation)
                      ->orWhere('location', 'LIKE', '%' . $cleanLocation . '%');
                });

            if ($excludeEventId) {
                $query->where('id', '!=', $excludeEventId);
            }

            $existingEvent = $query->latest('updated_at')->first();
            if ($existingEvent && !empty($existingEvent->venue_contact_phone)) {
                return [
                    'phone' => self::formatSpanishPhone($existingEvent->venue_contact_phone),
                    'name' => $existingEvent->venue_contact_name ?: $cleanLocation,
                    'notes' => $existingEvent->venue_notes ?: null,
                    'source' => 'Historial de eventos',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("Error buscando finca en historial local: " . $e->getMessage());
        }

        $cacheKey = 'venue_phone_lookup_v2_' . md5(mb_strtolower($cleanLocation));
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($cleanLocation) {
            return self::performExternalLookup($cleanLocation);
        });
    }

    /**
     * Si el evento tiene lugar/restaurante pero aún no tiene teléfono asignado, lo busca y lo guarda automáticamente.
     */
    public static function autoPopulateEventVenuePhone(Event $event, bool $force = false): ?array
    {
        if (!$force && !empty(trim((string)$event->venue_contact_phone))) {
            return null;
        }

        if (!self::isSearchableLocation($event->location)) {
            return null;
        }

        // Evitar reintentar en cada carga si ya se buscó sin éxito hace poco (a menos que sea forzado con el botón)
        $attemptCacheKey = 'venue_auto_attempted_v2_' . $event->id . '_' . md5(mb_strtolower(trim((string)$event->location)));
        if (!$force && Cache::has($attemptCacheKey)) {
            return null;
        }
        Cache::put($attemptCacheKey, true, now()->addHours(12));

        $found = self::findVenuePhone($event->location, $event->id, $force);
        if ($found && !empty($found['phone'])) {
            $updateData = [
                'venue_contact_phone' => $found['phone'],
            ];
            if (empty(trim((string)$event->venue_contact_name)) && !empty($found['name'])) {
                $updateData['venue_contact_name'] = $found['name'];
            }
            if (empty(trim((string)$event->venue_notes)) && !empty($found['notes'])) {
                $updateData['venue_notes'] = $found['notes'];
            }

            $event->update($updateData);
            return $found;
        }

        return null;
    }

    /**
     * Realiza la búsqueda externa combinando OpenStreetMap, buscadores web y scraping de la web oficial del lugar.
     */
    protected static function performExternalLookup(string $location): ?array
    {
        $candidateWebsites = [];
        $resolvedName = $location;

        // A. Consultar OpenStreetMap Nominatim (extratags incluye teléfono y web oficial de restaurantes/bodegas/hoteles)
        try {
            $osmRes = Http::withHeaders([
                'User-Agent' => 'EventosMusicalesCRM/1.0 (contacto@nunezandson.com)',
                'Accept-Language' => 'es-ES,es;q=0.9',
            ])->timeout(4)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $location,
                'format' => 'jsonv2',
                'extratags' => 1,
                'namedetails' => 1,
                'countrycodes' => 'es',
                'limit' => 3,
            ]);

            if ($osmRes->successful()) {
                $items = $osmRes->json();
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $extra = $item['extratags'] ?? [];
                        $osmPhone = $extra['phone']
                            ?? ($extra['contact:phone']
                            ?? ($extra['contact:mobile']
                            ?? ($extra['phone:mobile'] ?? null)));

                        if (!empty($osmPhone)) {
                            $parsed = self::extractFirstSpanishPhone((string)$osmPhone);
                            if ($parsed) {
                                return [
                                    'phone' => $parsed,
                                    'name' => $item['name'] ?? $location,
                                    'source' => 'OpenStreetMap / Ficha del lugar',
                                ];
                            }
                        }

                        $osmWeb = $extra['website'] ?? ($extra['contact:website'] ?? ($extra['url'] ?? null));
                        if (!empty($osmWeb) && filter_var($osmWeb, FILTER_VALIDATE_URL)) {
                            $candidateWebsites[] = $osmWeb;
                            if (!empty($item['name'])) {
                                $resolvedName = $item['name'];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::debug("OSM lookup falló para {$location}: " . $e->getMessage());
        }

        // B. Buscar en DuckDuckGo HTML y Bing fragmentos con el teléfono y la web oficial del restaurante/bodega
        $searchQueries = [
            "{$location} telefono contacto",
            "{$location} restaurante bodega finca telefono",
        ];

        foreach ($searchQueries as $q) {
            // 1) DuckDuckGo HTML
            try {
                $ddgRes = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept-Language' => 'es-ES,es;q=0.9',
                ])->timeout(5)->get('https://html.duckduckgo.com/html/', [
                    'q' => $q,
                    'kl' => 'es-es',
                ]);

                if ($ddgRes->successful()) {
                    $html = $ddgRes->body();

                    // Extraer URLs oficiales candidatas de los primeros resultados
                    if (preg_match_all('/uddg=([^&"\']+)/i', $html, $urlMatches)) {
                        foreach ($urlMatches[1] as $encodedUrl) {
                            $decodedUrl = urldecode($encodedUrl);
                            if (self::isLikelyOfficialWebsite($decodedUrl)) {
                                $candidateWebsites[] = $decodedUrl;
                            }
                        }
                    }

                    $textOnly = self::htmlToVisibleText($html);
                    $phoneFromSnippet = self::extractFirstSpanishPhone($textOnly);
                    if ($phoneFromSnippet) {
                        return [
                            'phone' => $phoneFromSnippet,
                            'name' => $resolvedName,
                            'source' => 'Búsqueda Web / Google',
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::debug("DDG lookup error para {$location}: " . $e->getMessage());
            }

            // 2) Bing Search HTML fallback
            try {
                $bingRes = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept-Language' => 'es-ES,es;q=0.9',
                ])->timeout(5)->get('https://www.bing.com/search', [
                    'q' => $q,
                    'setlang' => 'es',
                    'cc' => 'ES',
                ]);

                if ($bingRes->successful()) {
                    $bingHtml = $bingRes->body();

                    if (preg_match_all('/<a[^>]+href="(https?:\/\/[^"]+)"/i', $bingHtml, $bUrls)) {
                        foreach ($bUrls[1] as $u) {
                            if (self::isLikelyOfficialWebsite($u)) {
                                $candidateWebsites[] = $u;
                            }
                        }
                    }

                    $bingText = self::htmlToVisibleText($bingHtml);
                    $phoneFromBing = self::extractFirstSpanishPhone($bingText);
                    if ($phoneFromBing) {
                        return [
                            'phone' => $phoneFromBing,
                            'name' => $resolvedName,
                            'source' => 'Búsqueda Web',
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::debug("Bing lookup error para {$location}: " . $e->getMessage());
            }
        }

        // C. Inspeccionar la web oficial del restaurante/bodega (incluyendo dominios deducidos como bodegahika.com / hikabodega.com)
        $guessedDomains = self::guessDirectDomains($location);
        $allWebsites = array_slice(array_values(array_unique(array_merge($candidateWebsites, $guessedDomains))), 0, 5);

        foreach ($allWebsites as $siteUrl) {
            $phoneFromSite = self::scrapePhoneFromWebsite($siteUrl);
            if ($phoneFromSite) {
                $host = parse_url($siteUrl, PHP_URL_HOST) ?: 'Web oficial';
                return [
                    'phone' => $phoneFromSite,
                    'name' => $resolvedName,
                    'source' => "Web oficial ({$host})",
                ];
            }
        }

        return null;
    }

    /**
     * Limpia bloques <script>, <style> y comentarios antes de extraer el texto visible de un HTML
     */
    protected static function htmlToVisibleText(string $html): string
    {
        $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
        $clean = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', (string)$clean);
        $clean = preg_replace('/<noscript\b[^>]*>.*?<\/noscript>/is', ' ', (string)$clean);
        return html_entity_decode(strip_tags((string)$clean), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Infiere posibles dominios oficiales a partir del nombre del restaurante/bodega
     * (ej: "Bodega Hika" -> bodegahika.com y hikabodega.com)
     */
    protected static function guessDirectDomains(string $location): array
    {
        $slug = mb_strtolower($location, 'UTF-8');
        $slug = strtr($slug, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ñ' => 'n',
        ]);
        // Quitar localidad si va tras coma o guion (ej. "Bodega Hika, Villabona" -> "Bodega Hika")
        $parts = preg_split('/[,(\-]/', $slug);
        $mainPart = trim($parts[0] ?? $slug);
        $words = array_values(array_filter(preg_split('/\s+/', preg_replace('/[^a-z0-9\s]/', '', $mainPart))));

        $compact = implode('', $words);
        if (strlen($compact) < 4 || strlen($compact) > 35) {
            return [];
        }

        $domains = [
            "https://www.{$compact}.com",
            "https://{$compact}.com",
            "https://www.{$compact}.es",
        ];

        // Si son 2 palabras (ej: "Bodega Hika" -> "hikabodega.com"), probar también el orden inverso
        if (count($words) === 2) {
            $reversed = $words[1] . $words[0];
            $domains[] = "https://www.{$reversed}.com";
            $domains[] = "https://{$reversed}.com";
            $domains[] = "https://www.{$reversed}.es";
        }

        return array_values(array_unique($domains));
    }

    /**
     * Filtra directorios genéricos para priorizar la web oficial del restaurante/finca/bodega
     */
    protected static function isLikelyOfficialWebsite(string $url): bool
    {
        if (!str_starts_with($url, 'http')) {
            return false;
        }
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        if (empty($host)) {
            return false;
        }

        $excludedDomains = [
            'duckduckgo.com', 'bing.com', 'google.', 'microsoft.com', 'facebook.com',
            'instagram.com', 'twitter.com', 'x.com', 'tiktok.com', 'youtube.com',
            'tripadvisor.', 'thefork.', 'eltenedor.', 'bodas.net', 'zankyou.',
            'paginasamarillas.', 'einforma.', 'axesor.', 'infocif.', 'empresia.',
            'wikipedia.org', 'linkedin.com', 'booking.com', 'kayak.', 'expedia.',
        ];

        foreach ($excludedDomains as $excluded) {
            if (str_contains($host, $excluded)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Visita una URL de restaurante/bodega (y su subpágina /contacto si hace falta) y extrae su teléfono
     */
    protected static function scrapePhoneFromWebsite(string $url): ?string
    {
        $phone = self::scrapeSingleUrlForPhone($url);
        if ($phone) {
            return $phone;
        }

        // Si en la portada no estaba visible, probar /contacto en el mismo dominio
        $parsedUrl = parse_url($url);
        if (!empty($parsedUrl['scheme']) && !empty($parsedUrl['host'])) {
            $base = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            foreach (['/contacto/', '/contacto', '/es/contacto/'] as $contactPath) {
                $phoneContact = self::scrapeSingleUrlForPhone($base . $contactPath);
                if ($phoneContact) {
                    return $phoneContact;
                }
            }
        }

        return null;
    }

    protected static function scrapeSingleUrlForPhone(string $url): ?string
    {
        try {
            $res = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept-Language' => 'es-ES,es;q=0.9',
            ])->timeout(4)->get($url);

            if (!$res->successful()) {
                return null;
            }

            $html = $res->body();

            // 1. Buscar enlaces directos <a href="tel:...">
            if (preg_match_all('/href=["\']tel:([^"\']+)["\']/i', $html, $telMatches)) {
                foreach ($telMatches[1] as $rawTel) {
                    $parsed = self::extractFirstSpanishPhone(urldecode($rawTel));
                    if ($parsed) {
                        return $parsed;
                    }
                }
            }

            // 2. Buscar JSON-LD schema.org ("telephone": "...")
            if (preg_match_all('/"telephone"\s*:\s*"([^"]+)"/i', $html, $jsonTelMatches)) {
                foreach ($jsonTelMatches[1] as $rawTel) {
                    $parsed = self::extractFirstSpanishPhone($rawTel);
                    if ($parsed) {
                        return $parsed;
                    }
                }
            }

            // 3. Buscar en el texto visible de la página (sin <script> ni <style>)
            $cleanText = self::htmlToVisibleText($html);
            return self::extractFirstSpanishPhone($cleanText);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Valida que los 9 dígitos correspondan a un número español real:
     * - Fijos geográficos: 91..98 o 81..88
     * - Móviles: 60..69 o 71..79
     */
    protected static function isValidSpanishNineDigits(string $digits): bool
    {
        if (strlen($digits) !== 9) {
            return false;
        }
        return (bool)preg_match('/^(?:6\d|7[1-9]|9[1-8]|8[1-8])\d{7}$/', $digits);
    }

    /**
     * Extrae y formatea el primer teléfono español válido de un texto.
     */
    public static function extractFirstSpanishPhone(string $text): ?string
    {
        // Prioridad 1: Teléfono precedido explícitamente por +34 o palabras clave (Tel, Tfno, Teléfono, Reservas, Contacto)
        $priorityPattern = '/(?:(?:\+|00)\s*34[\s\.\-]*|(?:tel(?:éfono|efono)?|tfno|telf|reservas|contacto|llámanos|llamanos)[\s\.:]+(?:\+34[\s\.\-]*)?)([6789]\d{2}[\s\.\-]?\d{2,3}[\s\.\-]?\d{2,3}(?:[\s\.\-]?\d{2})?)/iu';
        if (preg_match_all($priorityPattern, $text, $matches)) {
            foreach ($matches[1] as $candidate) {
                $digits = preg_replace('/\D/', '', $candidate);
                if (self::isValidSpanishNineDigits($digits)) {
                    return self::formatSpanishPhone($digits);
                }
            }
        }

        // Prioridad 2: Patrón de teléfono español separado por espacios/puntos/guiones (ej: 943 14 27 09 o 943 142 709)
        $standardPattern = '/(?<!\d)(?:\+34[\s\.\-]*)?([6789]\d{2}[\s\.\-]\d{2,3}[\s\.\-]\d{2,3}(?:[\s\.\-]\d{2})?)(?!\d)/u';
        if (preg_match_all($standardPattern, $text, $matches)) {
            foreach ($matches[1] as $candidate) {
                $digits = preg_replace('/\D/', '', $candidate);
                if (self::isValidSpanishNineDigits($digits)) {
                    return self::formatSpanishPhone($digits);
                }
            }
        }

        // Prioridad 3: Si es una cadena corta (como un valor de href="tel:..."), extraer 9 dígitos directos
        if (strlen($text) <= 30) {
            $digits = preg_replace('/\D/', '', $text);
            if (strlen($digits) === 11 && str_starts_with($digits, '34')) {
                $digits = substr($digits, 2);
            }
            if (self::isValidSpanishNineDigits($digits)) {
                return self::formatSpanishPhone($digits);
            }
        }

        return null;
    }

    /**
     * Formatea un teléfono español de 9 dígitos como "943 14 27 09"
     */
    public static function formatSpanishPhone(string $rawPhone): string
    {
        $digits = preg_replace('/\D/', '', $rawPhone);
        if (strlen($digits) === 11 && str_starts_with($digits, '34')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 9) {
            return substr($digits, 0, 3) . ' ' . substr($digits, 3, 2) . ' ' . substr($digits, 5, 2) . ' ' . substr($digits, 7, 2);
        }

        return trim($rawPhone);
    }
}
