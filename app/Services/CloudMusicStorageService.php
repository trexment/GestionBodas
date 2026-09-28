<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\Event;
use App\Models\EventMusicRequest;

class CloudMusicStorageService
{
    /**
     * Parse Google Drive Folder ID from URL or raw ID
     */
    public static function extractGoogleDriveFolderId(string $urlOrId): ?string
    {
        $urlOrId = trim($urlOrId);
        if (empty($urlOrId)) return null;

        // https://drive.google.com/drive/folders/1ABC123_xyz-098
        if (preg_match('/folders\/([a-zA-Z0-9_-]{15,})/', $urlOrId, $matches)) {
            return $matches[1];
        }

        // https://drive.google.com/open?id=1ABC123_xyz-098
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]{15,})/', $urlOrId, $matches)) {
            return $matches[1];
        }

        // Raw ID format
        if (preg_match('/^[a-zA-Z0-9_-]{20,}$/', $urlOrId)) {
            return $urlOrId;
        }

        return null;
    }

    /**
     * Convert any cloud sharing link (Google Drive, OneDrive, Dropbox) into a direct streaming audio URL
     */
    public static function formatDirectStreamUrl(string $url): string
    {
        $url = trim($url);
        if (empty($url)) return '';

        // Google Drive File
        if (str_contains($url, 'drive.google.com')) {
            if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $m) || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $m)) {
                return "https://drive.google.com/uc?export=download&id={$m[1]}";
            }
        }

        // Dropbox
        if (str_contains($url, 'dropbox.com')) {
            if (str_contains($url, '?dl=0')) {
                return str_replace('?dl=0', '?raw=1', $url);
            }
            if (!str_contains($url, 'raw=1')) {
                return $url . (str_contains($url, '?') ? '&raw=1' : '?raw=1');
            }
        }

        // OneDrive
        if (str_contains($url, '1drv.ms') || str_contains($url, 'onedrive.live.com')) {
            if (str_contains($url, 'redir?')) {
                return str_replace('redir?', 'download?', $url);
            }
        }

        return $url;
    }

    /**
     * Parse artist and title from filename (e.g., "01 - Ed Sheeran - Perfect.mp3" -> Artist: "Ed Sheeran", Title: "Perfect")
     */
    public static function parseFilenameMetadata(string $filename): array
    {
        // Strip extension
        $clean = preg_replace('/\.(mp3|wav|ogg|m4a|flac|aac)$/i', '', $filename);
        // Strip leading track numbers like "01. ", "01 - ", "1 - "
        $clean = preg_replace('/^\d+[\s\.\-_]+/', '', $clean);

        if (str_contains($clean, ' - ')) {
            $parts = explode(' - ', $clean, 2);
            return [
                'artist' => trim($parts[0]),
                'title' => trim($parts[1]),
            ];
        }

        return [
            'artist' => '',
            'title' => trim($clean),
        ];
    }

    /**
     * Scan Google Drive Shared Folder using Google Drive API v3
     */
    public static function scanGoogleDriveFolder(string $folderUrlOrId, ?string $apiKey = null): array
    {
        $folderId = self::extractGoogleDriveFolderId($folderUrlOrId);
        if (!$folderId) {
            return [
                'success' => false,
                'message' => 'El enlace proporcionado no contiene un ID de carpeta de Google Drive válido.',
                'files' => [],
            ];
        }

        $key = $apiKey ?: Setting::get('google_drive_api_key', config('services.google.drive_api_key', env('GOOGLE_DRIVE_API_KEY')));

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'Para escanear carpetas de Google Drive automáticamente, configura una Google Drive API Key en Ajustes > Almacenamiento.',
                'folder_id' => $folderId,
                'files' => [],
            ];
        }

        try {
            $parsedFiles = [];
            $pageToken = null;
            $maxPages = 10; // Supports up to 1000 audio files per sync
            $pageCount = 0;

            do {
                $params = [
                    'q' => "'{$folderId}' in parents and (mimeType contains 'audio/' or name contains '.mp3' or name contains '.wav' or name contains '.m4a' or name contains '.flac' or name contains '.aac' or name contains '.ogg') and trashed=false",
                    'fields' => 'nextPageToken, files(id, name, mimeType, size, webViewLink, webContentLink)',
                    'pageSize' => 100,
                    'key' => $key,
                ];

                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $response = Http::timeout(15)->get('https://www.googleapis.com/drive/v3/files', $params);

                if (!$response->successful()) {
                    $err = $response->json('error.message') ?: 'Error al acceder a Google Drive API.';
                    return [
                        'success' => false,
                        'message' => 'Google Drive API Error: ' . $err . '. Asegúrate de que la carpeta esté compartida como "Cualquier persona con el enlace puede ver".',
                        'files' => [],
                    ];
                }

                $data = $response->json();
                $filesData = $data['files'] ?? [];

                foreach ($filesData as $file) {
                    $meta = self::parseFilenameMetadata($file['name']);
                    $fileId = $file['id'];
                    $streamUrl = "https://drive.google.com/uc?export=download&id={$fileId}";

                    $parsedFiles[] = [
                        'id' => $fileId,
                        'name' => $file['name'],
                        'title' => $meta['title'],
                        'artist' => $meta['artist'],
                        'stream_url' => $streamUrl,
                        'view_url' => $file['webViewLink'] ?? "https://drive.google.com/file/d/{$fileId}/view",
                        'size' => (int)($file['size'] ?? 0),
                    ];
                }

                $pageToken = $data['nextPageToken'] ?? null;
                $pageCount++;
            } while ($pageToken && $pageCount < $maxPages);

            return [
                'success' => true,
                'folder_id' => $folderId,
                'count' => count($parsedFiles),
                'files' => $parsedFiles,
            ];
        } catch (\Throwable $e) {
            Log::error('Google Drive scan error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error de conexión con Google Drive: ' . $e->getMessage(),
                'files' => [],
            ];
        }
    }

    /**
     * Import a scanned cloud folder directly into an Event
     */
    public static function importFolderToEvent(int $eventId, array $files, string $category = 'banquete', string $moment = 'General'): int
    {
        $event = Event::findOrFail($eventId);
        $maxOrder = EventMusicRequest::where('event_id', $eventId)->max('order') ?: 0;
        $imported = 0;

        foreach ($files as $file) {
            $title = $file['title'] ?? $file['name'];
            $artist = $file['artist'] ?? '';
            $streamUrl = $file['stream_url'] ?? '';

            // Check if already exists in this event
            $exists = EventMusicRequest::where('event_id', $eventId)
                ->where('title', $title)
                ->where('artist', $artist)
                ->exists();

            if (!$exists) {
                EventMusicRequest::create([
                    'event_id' => $eventId,
                    'category' => $category,
                    'moment' => $moment,
                    'title' => $title,
                    'artist' => $artist,
                    'requested_by' => 'Carpeta Nube (Drive/OneDrive)',
                    'audio_file' => $streamUrl,
                    'order' => ++$maxOrder,
                    'status' => 'pending',
                ]);
                $imported++;
            }
        }

        return $imported;
    }

    /**
     * Sync Master Google Drive Music Library into the global Tracks database table
     */
    public static function syncMasterLibraryFromGoogleDrive(?string $folderUrlOrId = null, ?string $apiKey = null): array
    {
        $folderUrlOrId = $folderUrlOrId ?: Setting::get('google_drive_library_folder', '');
        if (empty($folderUrlOrId)) {
            return [
                'success' => false,
                'message' => 'No hay configurada una Carpeta de Biblioteca General en Ajustes > Almacenamiento.',
                'synced' => 0,
            ];
        }

        $folderId = self::extractGoogleDriveFolderId($folderUrlOrId);
        if (!$folderId) {
            return [
                'success' => false,
                'message' => 'El enlace de la carpeta de la Biblioteca General no es válido.',
                'synced' => 0,
            ];
        }

        $scanResult = self::scanGoogleDriveFolder($folderId, $apiKey);
        if (!$scanResult['success']) {
            return $scanResult;
        }

        $files = $scanResult['files'] ?? [];
        $added = 0;
        $updated = 0;

        foreach ($files as $file) {
            $fileId = $file['id'];
            $title = $file['title'] ?? $file['name'];
            $artist = $file['artist'] ?? '';
            $streamUrl = $file['stream_url'] ?? '';

            $track = \App\Models\Track::where('cloud_id', $fileId)
                ->orWhere(function($q) use ($title, $artist) {
                    $q->where('title', $title)->where('artist', $artist);
                })
                ->first();

            if ($track) {
                $track->update([
                    'title' => $title,
                    'artist' => $artist,
                    'file_path' => $streamUrl,
                    'cloud_id' => $fileId,
                    'source' => 'google_drive',
                ]);
                $updated++;
            } else {
                \App\Models\Track::create([
                    'title' => $title,
                    'artist' => $artist,
                    'file_path' => $streamUrl,
                    'cloud_id' => $fileId,
                    'source' => 'google_drive',
                ]);
                $added++;
            }
        }

        return [
            'success' => true,
            'message' => "✓ Sincronización completada: {$added} canciones nuevas añadidas y {$updated} actualizadas en la Biblioteca General.",
            'total_scanned' => count($files),
            'added' => $added,
            'updated' => $updated,
        ];
    }

    /**
     * Find a song inside the Google Drive / Local Master Library
     */
    public static function findInDriveLibrary(string $title, ?string $artist = null): ?\App\Models\Track
    {
        $cleanTitle = trim($title);
        $cleanArtist = trim($artist ?? '');

        if (empty($cleanTitle)) {
            return null;
        }

        // 1. Exact match on title and artist
        if (!empty($cleanArtist)) {
            $track = \App\Models\Track::where('title', $cleanTitle)
                ->where('artist', $cleanArtist)
                ->first();
            if ($track) return $track;

            // 2. Fuzzy match title AND artist
            $track = \App\Models\Track::where('title', 'LIKE', "%{$cleanTitle}%")
                ->where('artist', 'LIKE', "%{$cleanArtist}%")
                ->first();
            if ($track) return $track;
        }

        // 3. Exact title match
        $track = \App\Models\Track::where('title', $cleanTitle)->first();
        if ($track) return $track;

        // 4. Substring title match
        if (mb_strlen($cleanTitle) >= 4) {
            $track = \App\Models\Track::where('title', 'LIKE', "%{$cleanTitle}%")->first();
            if ($track) return $track;
        }

        return null;
    }
}
