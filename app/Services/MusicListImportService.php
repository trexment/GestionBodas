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
                    'title' => $parts[0],
                    'artist' => $parts[1],
                ];
            }
        }

        // 2. Separated by " - " or " / " or " — "
        if (preg_match('/^(.+?)\s+[\-\—\/]\s+(.+)$/u', $clean, $m)) {
            return [
                'title' => trim($m[1]),
                'artist' => trim($m[2]),
            ];
        }

        // 3. Separated by " de " or " by " (e.g. "Despacito de Luis Fonsi")
        if (preg_match('/^(.+?)\s+(?:de|by)\s+(.+)$/iu', $clean, $m)) {
            return [
                'title' => trim($m[1]),
                'artist' => trim($m[2]),
            ];
        }

        // 4. Format: "Title (Artist)" or "Title [Artist]"
        if (preg_match('/^(.+?)\s*[\(\[](.+?)[\)\]]$/u', $clean, $m)) {
            return [
                'title' => trim($m[1]),
                'artist' => trim($m[2]),
            ];
        }

        // 5. Format: "Artist: Title"
        if (preg_match('/^(.+?):\s*(.+)$/u', $clean, $m)) {
            return [
                'title' => trim($m[2]),
                'artist' => trim($m[1]),
            ];
        }

        // 6. Multiple spaces (from table layout without tabs, e.g. "La morocha    Luck Ra y BM")
        if (preg_match('/^(.+?)\s{2,}(.+)$/u', $clean, $m)) {
            return [
                'title' => trim($m[1]),
                'artist' => trim($m[2]),
            ];
        }

        // Fallback: entire line as title
        return [
            'title' => $clean,
            'artist' => '',
        ];
    }

    /**
     * Extract raw text from a PDF file using native PHP stream decoding.
     */
    public static function extractTextFromPdf(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $content = @file_get_contents($filePath);
        if (empty($content)) {
            return '';
        }

        $text = '';

        // Match all PDF streams
        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $streamMatches);

        foreach ($streamMatches[1] as $stream) {
            $data = $stream;
            // Attempt decompression
            $uncompressed = @gzuncompress($data);
            if ($uncompressed === false) {
                // Try inflate if raw deflate
                $uncompressed = @gzinflate($data);
            }

            $decoded = ($uncompressed !== false) ? $uncompressed : $data;

            // Extract text inside BT ... ET text objects
            if (preg_match_all('/BT[\s\S]*?ET/', $decoded, $textBlocks)) {
                foreach ($textBlocks[0] as $block) {
                    // Match Tj operator (single string)
                    if (preg_match_all('/\((.*?)\)\s*Tj/s', $block, $tjMatches)) {
                        foreach ($tjMatches[1] as $tj) {
                            $text .= self::decodePdfString($tj) . " ";
                        }
                        $text .= "\n";
                    }
                    // Match TJ operator (array of strings)
                    if (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $tjArrayMatches)) {
                        foreach ($tjArrayMatches[1] as $arrayContent) {
                            if (preg_match_all('/\((.*?)\)/s', $arrayContent, $stringParts)) {
                                foreach ($stringParts[1] as $part) {
                                    $text .= self::decodePdfString($part);
                                }
                            }
                        }
                        $text .= "\n";
                    }
                }
            }
        }

        // If streams didn't produce clean text, fallback to uncompressed text extraction
        if (strlen(trim($text)) < 20) {
            if (preg_match_all('/\((.*?)\)\s*T[jJ]/s', $content, $rawMatches)) {
                foreach ($rawMatches[1] as $raw) {
                    $text .= self::decodePdfString($raw) . "\n";
                }
            }
        }

        return trim($text);
    }

    /**
     * Helper to decode escaped PDF strings
     */
    private static function decodePdfString(string $str): string
    {
        $str = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $str);
        // Octal escape sequences (\ddd)
        $str = preg_replace_callback('/\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $str);
        return $str;
    }

    /**
     * Import an array of parsed tracks into an Event.
     *
     * @param Event $event
     * @param array $tracks Array of ['title' => string, 'artist' => string, 'category' => string, 'moment' => string]
     * @param string $requestedBy
     * @param bool $enrichMetadata
     * @return int Number of tracks imported
     */
    public static function importTracksToEvent(Event $event, array $tracks, string $requestedBy = 'Cliente (Importado)', bool $enrichMetadata = true): int
    {
        $maxOrder = $event->musicRequests()->max('order') ?: 0;
        $count = 0;

        foreach ($tracks as $track) {
            $title = trim($track['title'] ?? '');
            $artist = trim($track['artist'] ?? '');

            if (empty($title)) {
                continue;
            }

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

            $count++;
        }

        return $count;
    }
}
