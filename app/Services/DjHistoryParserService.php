<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventDjHistory;
use App\Models\EventMusicRequest;

class DjHistoryParserService
{
    /**
     * Parsea un archivo subido o texto plano y guarda el historial en el evento
     */
    public function importHistory(Event $event, string $rawContent, string $filename = '', string $sessionName = 'Sesión Principal', bool $syncPlayedRequests = true): array
    {
        $tracks = $this->parseContent($rawContent, $filename);

        if (empty($tracks)) {
            return [
                'success' => false,
                'message' => 'No se pudieron extraer pistas del archivo proporcionado. Asegúrate de que sea un CSV, M3U o texto válido.',
                'imported_count' => 0,
                'matched_count' => 0,
            ];
        }

        $software = $this->detectSoftware($rawContent, $filename);
        $requests = $event->musicRequests()->get();
        $importedCount = 0;
        $matchedCount = 0;

        // Calcular el orden correlativo inicial
        $currentMaxOrder = (int)$event->djHistories()->max('order') ?: 0;

        foreach ($tracks as $index => $trackData) {
            $currentMaxOrder++;

            $title = trim($trackData['title'] ?? '');
            $artist = trim($trackData['artist'] ?? '');

            if (empty($title)) {
                continue;
            }

            // Buscar coincidencia con peticiones de novios/invitados
            $matchedRequest = null;
            if ($syncPlayedRequests) {
                $matchedRequest = $this->findMatchingRequest($title, $artist, $requests);
                if ($matchedRequest) {
                    $matchedCount++;
                    if ($matchedRequest->status !== 'played') {
                        $matchedRequest->update(['status' => 'played']);
                    }
                }
            }

            EventDjHistory::create([
                'event_id' => $event->id,
                'session_name' => $sessionName ?: 'Sesión Principal',
                'order' => $currentMaxOrder,
                'title' => $title,
                'artist' => $artist ?: null,
                'played_at_time' => $trackData['played_at_time'] ?? null,
                'bpm' => !empty($trackData['bpm']) ? (float)$trackData['bpm'] : null,
                'key' => $trackData['key'] ?? null,
                'duration' => $trackData['duration'] ?? null,
                'genre' => $trackData['genre'] ?? null,
                'source_software' => $software,
                'matched_request_id' => $matchedRequest ? $matchedRequest->id : null,
            ]);

            $importedCount++;
        }

        return [
            'success' => true,
            'message' => "¡Se han importado {$importedCount} canciones correctamente al historial del evento! (" . ($matchedCount > 0 ? "{$matchedCount} peticiones marcadas como sonadas" : "sin peticiones cruzadas") . ")",
            'imported_count' => $importedCount,
            'matched_count' => $matchedCount,
            'software' => $software,
        ];
    }

    /**
     * Parsea el contenido según el tipo de archivo (CSV, M3U, NML o Texto)
     */
    public function parseContent(string $content, string $filename = ''): array
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($ext === 'm3u' || $ext === 'm3u8' || str_contains($content, '#EXTM3U')) {
            return $this->parseM3u($content);
        }

        if ($ext === 'csv' || str_contains($content, ',') || str_contains($content, ';') || str_contains($content, "\t")) {
            $csvResult = $this->parseCsv($content);
            if (!empty($csvResult)) {
                return $csvResult;
            }
        }

        return $this->parsePlainText($content);
    }

    /**
     * Parsea archivos CSV (Engine DJ, Rekordbox, Serato, Traktor)
     */
    protected function parseCsv(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (count($lines) < 2) {
            return [];
        }

        // Detectar delimitador (, ; o tab)
        $firstLine = $lines[0];
        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        $headers = array_map(function ($h) {
            return mb_strtolower(trim(trim($h, '"\'')));
        }, str_getcsv($firstLine, $delimiter));

        // Mapear columnas
        $titleIndex = -1;
        $artistIndex = -1;
        $bpmIndex = -1;
        $keyIndex = -1;
        $timeIndex = -1;
        $durationIndex = -1;
        $genreIndex = -1;

        foreach ($headers as $idx => $header) {
            if (in_array($header, ['song title', 'track title', 'title', 'nombre', 'título', 'titulo', 'track', 'song', 'name'])) {
                if ($titleIndex === -1) $titleIndex = $idx;
            }
            if (in_array($header, ['artist', 'artista', 'performer', 'author'])) {
                if ($artistIndex === -1) $artistIndex = $idx;
            }
            if (in_array($header, ['bpm', 'tempo'])) {
                $bpmIndex = $idx;
            }
            if (in_array($header, ['key', 'tonalidad', 'camelot', 'initial key'])) {
                $keyIndex = $idx;
            }
            if (in_array($header, ['time played', 'start time', 'time', 'date', 'hora', 'played at', 'start'])) {
                $timeIndex = $idx;
            }
            if (in_array($header, ['duration', 'length', 'duración', 'duracion', 'play time', 'time'])) {
                if ($durationIndex === -1 && $idx !== $timeIndex) $durationIndex = $idx;
            }
            if (in_array($header, ['genre', 'género', 'genero', 'style'])) {
                $genreIndex = $idx;
            }
        }

        if ($titleIndex === -1 && count($headers) >= 1) {
            // Intentar detectar por contenido
            $titleIndex = 0;
            if (count($headers) >= 2) $artistIndex = 1;
        }

        $tracks = [];

        for ($i = 1; $i < count($lines); $i++) {
            $rowStr = trim($lines[$i]);
            if (empty($rowStr)) continue;

            $row = str_getcsv($rowStr, $delimiter);
            if (empty($row) || !isset($row[$titleIndex])) continue;

            $rawTitle = trim($row[$titleIndex] ?? '');
            $rawArtist = $artistIndex !== -1 ? trim($row[$artistIndex] ?? '') : '';

            // Si el título viene en formato "Artista - Título" y la columna artista estaba vacía
            if (empty($rawArtist) && str_contains($rawTitle, ' - ')) {
                $parts = explode(' - ', $rawTitle, 2);
                $rawArtist = trim($parts[0]);
                $rawTitle = trim($parts[1]);
            }

            $cleanTitle = $this->cleanTrackName($rawTitle);
            $cleanArtist = $this->cleanTrackName($rawArtist);

            if (empty($cleanTitle)) continue;

            $bpmVal = $bpmIndex !== -1 ? floatval(str_replace(',', '.', trim($row[$bpmIndex] ?? ''))) : null;
            $timeVal = $timeIndex !== -1 ? $this->formatTimePlayed(trim($row[$timeIndex] ?? '')) : null;

            $tracks[] = [
                'title' => $cleanTitle,
                'artist' => $cleanArtist,
                'bpm' => $bpmVal > 0 ? $bpmVal : null,
                'key' => $keyIndex !== -1 ? trim($row[$keyIndex] ?? '') : null,
                'played_at_time' => $timeVal,
                'duration' => $durationIndex !== -1 ? trim($row[$durationIndex] ?? '') : null,
                'genre' => $genreIndex !== -1 ? trim($row[$genreIndex] ?? '') : null,
            ];
        }

        return $tracks;
    }

    /**
     * Parsea listas de reproducción M3U / M3U8
     */
    protected function parseM3u(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        $tracks = [];
        $currentDuration = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line === '#EXTM3U') continue;

            if (str_starts_with($line, '#EXTINF:')) {
                // #EXTINF:240,Artist - Title
                $info = substr($line, 8);
                $parts = explode(',', $info, 2);
                $durationSec = (int)($parts[0] ?? 0);
                $trackLabel = trim($parts[1] ?? '');

                if ($durationSec > 0) {
                    $min = floor($durationSec / 60);
                    $sec = $durationSec % 60;
                    $currentDuration = sprintf('%02d:%02d', $min, $sec);
                }

                if (!empty($trackLabel)) {
                    $artist = '';
                    $title = $trackLabel;
                    if (str_contains($trackLabel, ' - ')) {
                        $split = explode(' - ', $trackLabel, 2);
                        $artist = trim($split[0]);
                        $title = trim($split[1]);
                    }

                    $tracks[] = [
                        'title' => $this->cleanTrackName($title),
                        'artist' => $this->cleanTrackName($artist),
                        'bpm' => null,
                        'key' => null,
                        'played_at_time' => null,
                        'duration' => $currentDuration,
                        'genre' => null,
                    ];
                    $currentDuration = null;
                }
            } elseif (!str_starts_with($line, '#')) {
                // Es una ruta de archivo (ej. /Music/01 Artist - Title.mp3)
                $filename = pathinfo($line, PATHINFO_FILENAME);
                $artist = '';
                $title = $filename;

                if (str_contains($filename, ' - ')) {
                    $split = explode(' - ', $filename, 2);
                    $artist = trim($split[0]);
                    $title = trim($split[1]);
                }

                // Eliminar posibles números de track iniciales "01 ", "01-", etc.
                $artist = preg_replace('/^\d{1,3}[\s._-]+/', '', $artist);
                $title = preg_replace('/^\d{1,3}[\s._-]+/', '', $title);

                $tracks[] = [
                    'title' => $this->cleanTrackName($title),
                    'artist' => $this->cleanTrackName($artist),
                    'bpm' => null,
                    'key' => null,
                    'played_at_time' => null,
                    'duration' => $currentDuration,
                    'genre' => null,
                ];
                $currentDuration = null;
            }
        }

        return $tracks;
    }

    /**
     * Parsea texto plano (1 por línea o pegado desde lista)
     */
    protected function parsePlainText(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        $tracks = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Extraer hora si está al principio (ej: "23:45 Artist - Title" o "[01:23] Artist - Title")
            $timePlayed = null;
            if (preg_match('/^(?:\[?(\d{1,2}:\d{2}(?::\d{2})?)\]?\s*)/', $line, $m)) {
                $timePlayed = $m[1];
                $line = trim(substr($line, strlen($m[0])));
            }

            // Quitar número inicial (ej: "1. ", "01 - ")
            $line = preg_replace('/^\d{1,3}[\.\)\s_-]+\s*/', '', $line);

            $artist = '';
            $title = $line;

            if (str_contains($line, ' - ')) {
                $parts = explode(' - ', $line, 2);
                $artist = trim($parts[0]);
                $title = trim($parts[1]);
            }

            $cleanTitle = $this->cleanTrackName($title);
            $cleanArtist = $this->cleanTrackName($artist);

            if (!empty($cleanTitle)) {
                $tracks[] = [
                    'title' => $cleanTitle,
                    'artist' => $cleanArtist,
                    'bpm' => null,
                    'key' => null,
                    'played_at_time' => $timePlayed,
                    'duration' => null,
                    'genre' => null,
                ];
            }
        }

        return $tracks;
    }

    /**
     * Limpia nombres de archivos y tags comunes
     */
    protected function cleanTrackName(string $str): string
    {
        // Quitar extensiones de audio
        $str = preg_replace('/\.(mp3|wav|aiff|flac|m4a|aac|ogg)$/i', '', $str);
        // Quitar números iniciales si quedaron
        $str = preg_replace('/^\d{1,3}[\s._-]+/', '', $str);
        return trim($str);
    }

    /**
     * Formatea hora de reproducción
     */
    protected function formatTimePlayed(string $timeStr): ?string
    {
        if (empty($timeStr)) return null;
        if (preg_match('/(\d{1,2}:\d{2}(?::\d{2})?)/', $timeStr, $m)) {
            return $m[1];
        }
        return substr($timeStr, 0, 10);
    }

    /**
     * Detecta el software de origen según el contenido
     */
    protected function detectSoftware(string $content, string $filename = ''): string
    {
        $lower = mb_strtolower($content);
        $fn = mb_strtolower($filename);

        if (str_contains($lower, 'engine') || str_contains($fn, 'engine') || str_contains($lower, 'song title')) {
            return 'engine_dj';
        }
        if (str_contains($lower, 'rekordbox') || str_contains($fn, 'rekordbox') || str_contains($lower, 'track title')) {
            return 'rekordbox';
        }
        if (str_contains($lower, 'serato') || str_contains($fn, 'serato')) {
            return 'serato';
        }
        if (str_contains($lower, 'traktor') || str_contains($fn, 'traktor') || str_contains($fn, '.nml')) {
            return 'traktor';
        }
        if (str_contains($fn, '.m3u')) {
            return 'm3u';
        }

        return 'csv';
    }

    /**
     * Cruza la pista con la lista de peticiones de los novios/invitados
     */
    protected function findMatchingRequest(string $title, string $artist, $requests): ?EventMusicRequest
    {
        $titleNorm = $this->normalizeString($title);
        $artistNorm = $this->normalizeString($artist);

        foreach ($requests as $req) {
            $reqTitleNorm = $this->normalizeString($req->title ?? '');
            $reqArtistNorm = $this->normalizeString($req->artist ?? '');

            // 1. Coincidencia exacta o contenida en título
            if (!empty($reqTitleNorm) && (
                $titleNorm === $reqTitleNorm ||
                (strlen($reqTitleNorm) > 4 && str_contains($titleNorm, $reqTitleNorm)) ||
                (strlen($titleNorm) > 4 && str_contains($reqTitleNorm, $titleNorm))
            )) {
                // Si también hay artista, comprobar afinidad
                if (empty($artistNorm) || empty($reqArtistNorm) || str_contains($artistNorm, $reqArtistNorm) || str_contains($reqArtistNorm, $artistNorm)) {
                    return $req;
                }
            }

            // 2. Similitud de texto alta (> 80%)
            similar_text($titleNorm, $reqTitleNorm, $percent);
            if ($percent >= 80) {
                return $req;
            }
        }

        return null;
    }

    protected function normalizeString(string $str): string
    {
        $str = mb_strtolower(trim($str));
        $str = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $str);
        return preg_replace('/[^a-z0-9]/', '', $str);
    }
}
