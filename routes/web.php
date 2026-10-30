<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\CalendarFeedController;
use App\Http\Controllers\SpotifyAuthController;
use App\Livewire\Admin\EventManager;
use App\Livewire\Admin\UserManager;
use App\Livewire\Admin\EventShow;
use App\Livewire\Admin\MusicManager;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\DjBoothMode;
use App\Livewire\Admin\AnalyticsManager;
use App\Livewire\Admin\TariffDossierManager;
use App\Livewire\Guest\EventForm;
use App\Livewire\Guest\ContractSign;
use App\Livewire\Guest\LiveRequests;

// Root Route: Redirección directa al Login (o al Dashboard si ya ha iniciado sesión)
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Direct public storage file stream fallback (when symlink is missing or blocked by host)
Route::get('/storage/{path}', function ($path) {
    // Prevent directory traversal and null byte injections
    if (str_contains($path, '..') || str_contains($path, "\0")) {
        abort(404);
    }

    $basePath = realpath(storage_path('app/public'));
    $fullPath = realpath(storage_path('app/public/' . $path));

    if (!$basePath || !$fullPath || !str_starts_with($fullPath, $basePath . DIRECTORY_SEPARATOR) || !is_file($fullPath)) {
        abort(404);
    }

    // Disallow execution/reading of sensitive or executable file types
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    if (in_array($ext, ['php', 'phtml', 'phar', 'env', 'htaccess', 'htpasswd', 'sh', 'bat', 'cmd', 'exe'])) {
        abort(403);
    }

    return response()->file($fullPath);
})->where('path', '.*');

// Auth Routes (with Brute Force Rate Limiting)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

// Guest Form Public Route (no middleware)
Route::get('/guest/form/{token}', EventForm::class)->name('guest.form');

// Public Online Contract Review & Signature Portal
Route::get('/contrato/{token}', ContractSign::class)->name('guest.contract');

// Public Online Quote Review, Dossier PDF Download & Accept/Reject Portal
Route::get('/propuesta/{token}', \App\Livewire\Guest\QuoteResponse::class)->name('guest.quote.view');

// Public Guest Live Requests Portal (QR Code) - Rate limited to 40 requests/min
Route::get('/peticiones/{token}', LiveRequests::class)->middleware('throttle:40,1')->name('guest.requests');

// Public Staff DJ Booth Live Mode (Tablet/Phone with Token)
Route::get('/live/{token}', DjBoothMode::class)->name('staff.live');

// iCal Calendar Feed Synchronization (RFC 5545)
Route::get('/calendar/feed/{token}.ics', [CalendarFeedController::class, 'feed'])->name('calendar.feed');

// Public Quote Calculator - Rate limited to 20 requests/min
Route::get('/presupuesto', \App\Livewire\Guest\QuoteCalculator::class)->middleware('throttle:20,1')->name('guest.quote');

// Public General Tariff Dossier PDF (igual para todas las parejas, distinto del presupuesto por boda)
Route::get('/dossier-tarifas/{brand}.pdf', [PdfController::class, 'downloadTariffDossier'])
    ->where('brand', 'nunez_and_son|javnx')
    ->name('public.tariff-dossier.pdf');
Route::get('/dossier-tarifas.pdf', [PdfController::class, 'downloadTariffDossier'])
    ->name('public.tariff-dossier.default.pdf');
Route::get('/dossier-tarifas/presentacion/{brand?}', [PdfController::class, 'viewTariffDossierPresentation'])
    ->where('brand', 'nunez_and_son|javnx')
    ->name('public.tariff-dossier.presentation');

// Manual Oficial de Uso (Web Interactiva & Descarga PDF)
Route::get('/manual', function () {
    $htmlPath = base_path('docs/MANUAL_DE_USO.html');
    if (!file_exists($htmlPath)) {
        $htmlPath = public_path('docs/index.html');
    }
    if (file_exists($htmlPath)) {
        return response()->file($htmlPath, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }
    abort(404);
})->name('manual');

Route::get('/manual/pdf', function () {
    $pdfPath = base_path('docs/MANUAL_DE_USO.pdf');
    if (!file_exists($pdfPath)) {
        $pdfPath = public_path('docs/MANUAL_DE_USO.pdf');
    }
    if (file_exists($pdfPath)) {
        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Manual_de_Uso_Eventos_Musicales.pdf"'
        ]);
    }
    abort(404);
})->name('manual.pdf');

// Spotify & Apple Music & Full Audio Streaming API Endpoints
Route::get('/api/spotify/token', [SpotifyAuthController::class, 'getToken'])->name('spotify.token');
Route::get('/api/apple-music/token', [SpotifyAuthController::class, 'getAppleMusicToken'])->name('apple-music.token');
Route::get('/api/music/youtube-id', [SpotifyAuthController::class, 'getYoutubeVideoId'])->name('music.youtube-id');
Route::get('/api/music/resolve-track', [SpotifyAuthController::class, 'resolveTrack'])->name('music.resolve-track');
Route::get('/api/drive-stream/{fileId}', [SpotifyAuthController::class, 'streamGoogleDriveAudio'])->name('api.drive.stream');
Route::get('/music-requests/{musicRequest}/download', [SpotifyAuthController::class, 'downloadAudio'])->name('admin.music.download');
// External Web Leads API / Webhook Endpoint (Rate limited to 30 requests/min + CORS Preflight)
Route::options('/api/leads', [\App\Http\Controllers\LeadWebhookController::class, 'options'])->name('api.leads.options');
Route::post('/api/leads', [\App\Http\Controllers\LeadWebhookController::class, 'store'])->middleware('throttle:30,1')->name('api.leads.store');
// Evolution API / WhatsApp Webhook Endpoint (Soporta /api/whatsapp/webhook y /api/whatsapp/webhook/messages-upsert)
Route::post('/api/whatsapp/webhook/{event?}', [\App\Http\Controllers\WhatsAppWebhookController::class, 'handle'])
    ->where('event', '.*')
    ->middleware('throttle:120,1')
    ->name('api.whatsapp.webhook');

// Global PDF Download Aliases (Secured with token or auth inside controller)
Route::get('/events/{event}/music-escaleta/pdf', [PdfController::class, 'downloadMusicEscaleta'])->name('pdf.music-escaleta');
Route::get('/events/{event}/dj-history/pdf', [PdfController::class, 'downloadDjHistory'])->name('pdf.dj-history');
Route::get('/events/{event}/packing-list/pdf', [PdfController::class, 'downloadPackingList'])->name('pdf.packing-list');
Route::get('/events/{event}/photo-qr-poster.pdf', [PdfController::class, 'downloadPhotoQrPoster'])->name('pdf.photo-qr-poster');
Route::get('/contract/{contract}/pdf', [PdfController::class, 'downloadContract'])->name('pdf.contract');
Route::get('/invoice/{invoice}/pdf', [PdfController::class, 'downloadInvoice'])->name('pdf.invoice');
Route::get('/quote/{quote}/pdf', [PdfController::class, 'downloadQuote'])->name('pdf.quote');

// Protected Auth Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('is_admin')->group(function () {
        // Spotify Admin Management
        Route::get('/spotify/connect', [SpotifyAuthController::class, 'connect'])->name('spotify.connect');
        Route::get('/spotify/callback', [SpotifyAuthController::class, 'callback'])->name('spotify.callback');
        Route::post('/spotify/disconnect', [SpotifyAuthController::class, 'disconnect'])->name('spotify.disconnect');

        // Alias without prefix
        Route::get('/admin/events/{event}/live', DjBoothMode::class)->name('events.live');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', \App\Livewire\Admin\Dashboard::class)->name('dashboard');
            Route::get('/events', EventManager::class)->name('events');
            Route::get('/events/{event}', EventShow::class)
                ->name('events.show')
                ->missing(function (\Illuminate\Http\Request $request) {
                    $missingId = (int) $request->route('event');
                    if ($missingId > 0) {
                        \App\Services\AppNotificationService::removeByEventId($missingId);
                    }
                    return redirect()->route('admin.events')
                        ->with('message', '⚠️ Ese evento o lead ya fue eliminado anteriormente.');
                });
            Route::get('/events/{event}/live', DjBoothMode::class)->name('events.live');
            Route::get('/calendar', \App\Livewire\Admin\CalendarManager::class)->name('calendar');
            Route::get('/clients', \App\Livewire\Admin\ClientManager::class)->name('clients');
            Route::get('/users', UserManager::class)->name('users');
            Route::get('/music', MusicManager::class)->name('music');
            Route::get('/inventory', \App\Livewire\Admin\InventoryManager::class)->name('inventory');
            Route::get('/tariff-dossier', TariffDossierManager::class)->name('tariff-dossier');
            Route::get('/tariff-dossier/pdf', [PdfController::class, 'downloadTariffDossier'])->name('tariff-dossier.pdf');
            Route::get('/tariff-dossier/presentation', [PdfController::class, 'viewTariffDossierPresentation'])->name('tariff-dossier.presentation');
            Route::get('/settings', Settings::class)->name('settings');
            Route::get('/analytics', AnalyticsManager::class)->name('analytics');

            // Admin PDF Routes
            Route::get('/quote/{quote}/pdf', [PdfController::class, 'downloadQuote'])->name('quote.pdf');
            Route::get('/contract/{contract}/pdf', [PdfController::class, 'downloadContract'])->name('contract.pdf');
            Route::get('/invoice/{invoice}/pdf', [PdfController::class, 'downloadInvoice'])->name('invoice.pdf');
            Route::get('/events/{event}/packing-list/pdf', [PdfController::class, 'downloadPackingList'])->name('events.packing_list.pdf');
            Route::get('/events/{event}/music-escaleta/pdf', [PdfController::class, 'downloadMusicEscaleta'])->name('events.music_escaleta.pdf');

            // Real-time App Notifications Polling & Read State (WhatsApp inbound & Email Leads)
            Route::get('/notifications/poll', function (\Illuminate\Http\Request $request) {
                if ($request->boolean('check_imap') && \App\Models\Setting::get('imap_enabled', '0') === '1') {
                    $lastSync = (int) \App\Models\Setting::get('imap_last_auto_sync', '0');
                    if ((time() - $lastSync) >= 120) {
                        \App\Models\Setting::set('imap_last_auto_sync', (string) time());
                        try {
                            app(\App\Services\ImapLeadFetcherService::class)->fetchAllMailboxes();
                        } catch (\Throwable $e) {
                            // Silencioso en polling
                        }
                    }
                }
                return response()->json(
                    \App\Services\AppNotificationService::getForUser((int) auth()->id(), 20)
                );
            })->name('notifications.poll');

            Route::post('/notifications/read', function (\Illuminate\Http\Request $request) {
                $userId = (int) auth()->id();
                if ($request->boolean('clear_all')) {
                    \App\Services\AppNotificationService::clearAll();
                } elseif ($request->boolean('all')) {
                    \App\Services\AppNotificationService::markAllRead($userId);
                } elseif ($request->filled('id')) {
                    \App\Services\AppNotificationService::markRead((string) $request->input('id'), $userId);
                }
                return response()->json(
                    \App\Services\AppNotificationService::getForUser($userId, 20)
                );
            })->name('notifications.read');
        });
    });
});
