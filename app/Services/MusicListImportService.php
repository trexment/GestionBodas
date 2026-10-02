<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventMusicRequest;
use Illuminate\Support\Facades\Log;

class MusicListImportService
{
    /**
     * Parse raw text (from WhatsApp, Word, PDF text, CSV, etc.) into structured tracks.
     *
     * @param string $text
     * @return array Array of ['title' => string, 'artist' => string, 'notes' => string, 'moment' => string]
     */
    public static function parseText(string $text): array
    {
        // Ensure clean UTF-8 string
        $text = self::sanitizeUtf8($text);

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $tracks = [];
        $currentMoment = 'Baile & Fiesta';
        $currentCategory = 'baile';
        $generalNotes = [];
        $isInGeneralNotesSection = false;

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if (empty($line)) {
                continue;
            }

            // Ignore table headers or page numbers
            if (preg_match('/^(Lista musical|Peticiones musicales|Canciones solicitadas|N[ºo\.]?\s+Canci[oó]n|P[aá]gina\s+\d+)/iu', $line)) {
                continue;
            }

            // Detect section headers for styles, artists or special moments
            if (preg_match('/^(Artistas y estilos|Estilos solicitados|Notas|Observaciones|Comentarios)/iu', $line)) {
                $isInGeneralNotesSection = true;
                continue;
            }

            if ($isInGeneralNotesSection) {
                // Collect bullet points or notes
                $cleanNote = preg_replace('/^[•\-\*·\s]+/', '', $line);
                if (!empty($cleanNote)) {
                    $generalNotes[] = $cleanNote;
                }
                continue;
            }

            // Check if line is a bullet point with style/artist request
            if (preg_match('/^[•\-\*·]\s*(.*)$/u', $line, $m)) {
                $item = trim($m[1]);
                if (preg_match('/^(M[uú]sica|Algo de|Pop|Rock|Reggaeton|Latino|Electro)/iu', $item)) {
                    $generalNotes[] = $item;
                    continue;
                }
            }

            // Parse track lines
            $parsed = self::parseTrackLine($line);
            if ($parsed && !empty($parsed['title'])) {
                $parsed['moment'] = $currentMoment;
                $parsed['category'] = $currentCategory;
                $tracks[] = $parsed;
            }
        }

        return [
            'tracks' => $tracks,
            'notes' => $generalNotes,
        ];
    }

    /**
     * Parse a single line to extract title and artist.
     */
    public static function parseTrackLine(string $line): ?array
    {
        // Remove leading numbering like "1.", "1 -", "1", "Nº 1", "#1"
        $clean = preg_replace('/^(?:N[ºo\.]?\s*)?\d+[\.\-\)\s:]+\s*/iu', '', $line);
        $clean = trim($clean);

        if (empty($clean)) {
            return null;
        }

        $title = '';
        $artist = '';

        // 1. Tab separated (from PDF or Excel table copy-paste)
        if (str_contains($clean, "\t")) {
            $parts = explode("\t", $clean);
            $parts = array_values(array_filter(array_map('trim', $parts)));
            if (count($parts) >= 2) {
                return [
                    'title' => self::sanitizeUtf8($parts[0]),
                    'artist' => self::sanitizeUtf8($parts[1]),
                ];
            }
        }

        // 2. Separated by " - " or " / " or " — "
        if (preg_match('/^(.+?)\s+[\-\—\/]\s+(.+)$/u', $clean, $m)) {
            return [
                'title' => self::sanitizeUtf8(trim($m[1])),
                'artist' => self::sanitizeUtf8(trim($m[2])),
            ];
        }

        // 3. Separated by " de " or " by " (e.g. "Despacito de Luis Fonsi")
        if (preg_match('/^(.+?)\s+(?:de|by)\s+(.+)$/iu', $clean, $m)) {
            return [
                'title' => self::sanitizeUtf8(trim($m[1])),
                'artist' => self::sanitizeUtf8(trim($m[2])),
            ];
        }

        // 4. Format: "Title (Artist)" or "Title [Artist]"
        if (preg_match('/^(.+?)\s*[\(\[](.+?)[\)\]]$/u', $clean, $m)) {
            return [
                'title' => self::sanitizeUtf8(trim($m[1])),
                'artist' => self::sanitizeUtf8(trim($m[2])),
            ];
        }

        // 5. Format: "Artist: Title"
        if (preg_match('/^(.+?):\s*(.+)$/u', $clean, $m)) {
            return [
                'title' => self::sanitizeUtf8(trim($m[2])),
                'artist' => self::sanitizeUtf8(trim($m[1])),
            ];
        }

        // 6. Multiple spaces (from table layout without tabs, e.g. "La morocha    Luck Ra y BM")
        if (preg_match('/^(.+?)\s{2,}(.+)$/u', $clean, $m)) {
            return [
                'title' => self::sanitizeUtf8(trim($m[1])),
                'artist' => self::sanitizeUtf8(trim($m[2])),
            ];
        }

        // Fallback: entire line as title
        return [
            'title' => self::sanitizeUtf8($clean),
            'artist' => '',
        ];
    }

    /**
     * Extract raw text from a PDF file using robust, safe stream decoding.
     */
    public static function extractTextFromPdf(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        try {
            $content = @file_get_contents($filePath);
            if (empty($content)) {
                return '';
            }

            $extractedText = '';

            // 1. Locate all streams using binary substring offsets to avoid catastrophic PCRE backtracking
            $offset = 0;
            $len = strlen($content);
            $maxStreams = 500;
            $streamCount = 0;

            while ($offset < $len && $streamCount < $maxStreams) {
                $streamStart = stripos($content, 'stream', $offset);
                if ($streamStart === false) {
                    break;
                }

                // Advance past "stream" and newlines
                $dataStart = $streamStart + 6;
                if ($dataStart < $len && $content[$dataStart] === "\r") {
                    $dataStart++;
                }
                if ($dataStart < $len && $content[$dataStart] === "\n") {
                    $dataStart++;
                }

                $streamEnd = stripos($content, 'endstream', $dataStart);
                if ($streamEnd === false) {
                    break;
                }

                $streamLength = $streamEnd - $dataStart;
                if ($streamLength > 0 && $streamLength < 10485760) { // Safety: max 10MB per stream
                    $data = substr($content, $dataStart, $streamLength);
                    
                    // Attempt decompression
                    $uncompressed = @gzuncompress($data);
                    if ($uncompressed === false) {
                        $uncompressed = @gzinflate($data);
                    }
                    if ($uncompressed === false) {
                        $uncompressed = @zlib_decode($data);
                    }

                    $decoded = ($uncompressed !== false) ? $uncompressed : $data;

                    $textInStream = self::extractTextFromPdfStream($decoded);
                    if (!empty($textInStream)) {
                        $extractedText .= $textInStream . "\n";
                    }
                }

                $offset = $streamEnd + 9;
                $streamCount++;
            }

            // 2. Fallback: If no stream text found, scan for uncompressed text blocks
            if (strlen(trim($extractedText)) < 15) {
                $extractedText = self::extractTextFromPdfStream($content);
            }

            return self::sanitizeUtf8(trim($extractedText));
        } catch (\Throwable $e) {
            Log::warning("Error extrayendo texto de PDF: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Parse text operators (BT...ET, Tj, TJ) from a uncompressed PDF stream
     */
    private static function extractTextFromPdfStream(string $streamData): string
    {
        $out = '';

        // Extract BT ... ET blocks safely
        if (preg_match_all('/BT([\s\S]*?)ET/s', $streamData, $blocks)) {
            foreach ($blocks[1] as $block) {
                // Match Tj operator (single string)
                if (preg_match_all('/\((.*?)\)\s*Tj/s', $block, $tjMatches)) {
                    foreach ($tjMatches[1] as $tj) {
                        $out .= self::decodePdfString($tj) . " ";
                    }
                    $out .= "\n";
                }

                // Match TJ operator (array of strings / kerning)
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $tjArrayMatches)) {
                    foreach ($tjArrayMatches[1] as $arrayContent) {
                        if (preg_match_all('/\((.*?)\)/s', $arrayContent, $stringParts)) {
                            foreach ($stringParts[1] as $part) {
                                $out .= self::decodePdfString($part);
                            }
                        }
                    }
                    $out .= "\n";
                }

                // Match hex strings <48656c6c6f> Tj
                if (preg_match_all('/<([0-9a-fA-F\s]+)>\s*Tj/s', $block, $hexMatches)) {
                    foreach ($hexMatches[1] as $hex) {
                        $cleanHex = preg_replace('/\s+/', '', $hex);
                        if (strlen($cleanHex) % 2 === 0) {
                            $out .= @hex2bin($cleanHex) . " ";
                        }
                    }
                    $out .= "\n";
                }
            }
        }

        return $out;
    }

    /**
     * Helper to decode escaped PDF strings
     */
    private static function decodePdfString(string $str): string
    {
        $str = str_replace(['\\(', '\\)', '\\\\', '\\n', '\\r', '\\t'], ['(', ')', '\\', "\n", "\r", "\t"], $str);
        // Octal escape sequences (\ddd)
        $str = preg_replace_callback('/\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $str);
        return $str;
    }

    /**
     * Sanitize string to clean printable UTF-8 (prevent JSON serialization 500 errors)
     */
    public static function sanitizeUtf8(string $str): string
    {
        // Convert encoding to UTF-8 ignoring invalid bytes
        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
        }
        // Remove non-printable binary control characters except newlines and tabs
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str);
        return trim($str);
    }

    /**
     * Import an array of parsed tracks into an Event, avoiding duplicates.
     *
     * @param Event $event
     * @param array $tracks Array of ['title' => string, 'artist' => string, 'category' => string, 'moment' => string]
     * @param string $requestedBy
     * @param bool $enrichMetadata
     * @return array ['imported' => int, 'duplicates' => int, 'total' => int]
     */
    public static function importTracksToEvent(Event $event, array $tracks, string $requestedBy = 'Cliente (Importado)', bool $enrichMetadata = true): array
    {
        $event->load('musicRequests');
        
        // Build normalized list of existing tracks in this event
        $existingTracks = [];
        foreach ($event->musicRequests as $req) {
            $norm = self::normalizeTrackKey($req->title, $req->artist);
            $existingTracks[$norm] = true;
        }

        $maxOrder = $event->musicRequests->max('order') ?: 0;
        $importedCount = 0;
        $duplicatesCount = 0;

        foreach ($tracks as $track) {
            $title = self::sanitizeUtf8($track['title'] ?? '');
            $artist = self::sanitizeUtf8($track['artist'] ?? '');

            if (empty($title)) {
                continue;
            }

            // Check if track is already in the event
            $trackKey = self::normalizeTrackKey($title, $artist);
            if (isset($existingTracks[$trackKey])) {
                $duplicatesCount++;
                continue; // Skip duplicate!
            }

            // Mark as existing so intra-list duplicates are also prevented
            $existingTracks[$trackKey] = true;

            $category = $track['category'] ?? 'baile';
            $moment = $track['moment'] ?? 'Baile & Fiesta';

            $spotifyUrl = null;
            $appleMusicUrl = null;
            $youtubeUrl = null;
            $audioFile = null;

            if ($enrichMetadata) {
                try {
                    $meta = MusicSearchService::resolveTrackMetadata($title, $artist);
                    $spotifyUrl = $meta['spotify_url'] ?? null;
                    $appleMusicUrl = $meta['apple_music_url'] ?? null;
                    $youtubeUrl = $meta['youtube_url'] ?? null;
                    if (!empty($meta['is_drive_library']) && !empty($meta['preview_url'])) {
                        $audioFile = $meta['preview_url'];
                    }
                } catch (\Throwable $e) {
                    Log::warning("No se pudo enriquecer metadata para '{$title}': " . $e->getMessage());
                }
            }

            $maxOrder++;
            $event->musicRequests()->create([
                'category' => $category,
                'moment' => $moment,
                'title' => $title,
                'artist' => $artist,
                'requested_by' => $requestedBy,
                'spotify_url' => $spotifyUrl,
                'apple_music_url' => $appleMusicUrl,
                'youtube_url' => $youtubeUrl,
                'audio_file' => $audioFile,
                'status' => 'pending',
                'order' => $maxOrder,
            ]);

            $importedCount++;
        }

        return [
            'imported' => $importedCount,
            'duplicates' => $duplicatesCount,
            'total' => count($tracks),
        ];
    }

    /**
     * Normalize title and artist to a comparable string
     */
    private static function normalizeTrackKey(string $title, string $artist = ''): string
    {
        $raw = strtolower(trim($title . ' ' . $artist));
        // Remove non alphanumeric characters
        return preg_replace('/[^a-z0-9áéíóúñ]/u', '', $raw);
    }
}
