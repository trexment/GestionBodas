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
use App\Livewire\Guest\EventForm;
use App\Livewire\Guest\ContractSign;
use App\Livewire\Guest\LiveRequests;

// Public Home / Landing Index
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Direct public storage file stream fallback (when symlink is missing or blocked by host)
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (file_exists($fullPath)) {
        return response()->file($fullPath);
    }
    abort(404);
})->where('path', '.*');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Guest Form Public Route (no middleware)
Route::get('/guest/form/{token}', EventForm::class)->name('guest.form');

// Public Online Contract Review & Signature Portal
Route::get('/contrato/{token}', ContractSign::class)->name('guest.contract');

// Public Guest Live Requests Portal (QR Code)
Route::get('/peticiones/{token}', LiveRequests::class)->name('guest.requests');

// Public Staff DJ Booth Live Mode (Tablet/Phone with Token)
Route::get('/live/{token}', DjBoothMode::class)->name('staff.live');

// iCal Calendar Feed Synchronization (RFC 5545)
Route::get('/calendar/feed/{token}.ics', [CalendarFeedController::class, 'feed'])->name('calendar.feed');

// Public Quote Calculator
Route::get('/presupuesto', \App\Livewire\Guest\QuoteCalculator::class)->name('guest.quote');

// Spotify & Apple Music & Full Audio Streaming API Endpoints
Route::get('/spotify/connect', [SpotifyAuthController::class, 'connect'])->name('spotify.connect');
Route::get('/spotify/callback', [SpotifyAuthController::class, 'callback'])->name('spotify.callback');
Route::post('/spotify/disconnect', [SpotifyAuthController::class, 'disconnect'])->name('spotify.disconnect');
Route::get('/api/spotify/token', [SpotifyAuthController::class, 'getToken'])->name('spotify.token');
Route::get('/api/apple-music/token', [SpotifyAuthController::class, 'getAppleMusicToken'])->name('apple-music.token');
Route::get('/api/music/youtube-id', [SpotifyAuthController::class, 'getYoutubeVideoId'])->name('music.youtube-id');
Route::get('/api/music/resolve-track', [SpotifyAuthController::class, 'resolveTrack'])->name('music.resolve-track');
Route::get('/music-requests/{musicRequest}/download', [SpotifyAuthController::class, 'downloadAudio'])->name('admin.music.download');
Route::get('/music/{musicRequest}/download', [SpotifyAuthController::class, 'downloadAudio'])->name('music.download');

// Global PDF Download Aliases
Route::get('/events/{event}/music-escaleta/pdf', [PdfController::class, 'downloadMusicEscaleta'])->name('pdf.music-escaleta');
Route::get('/events/{event}/packing-list/pdf', [PdfController::class, 'downloadPackingList'])->name('pdf.packing-list');
Route::get('/contract/{contract}/pdf', [PdfController::class, 'downloadContract'])->name('pdf.contract');
Route::get('/invoice/{invoice}/pdf', [PdfController::class, 'downloadInvoice'])->name('pdf.invoice');
Route::get('/quote/{quote}/pdf', [PdfController::class, 'downloadQuote'])->name('pdf.quote');

// Protected Auth Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('is_admin')->group(function () {
        // Alias without prefix
        Route::get('/admin/events/{event}/live', DjBoothMode::class)->name('events.live');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', \App\Livewire\Admin\Dashboard::class)->name('dashboard');
            Route::get('/events', EventManager::class)->name('events');
            Route::get('/events/{event}', EventShow::class)->name('events.show');
            Route::get('/events/{event}/live', DjBoothMode::class)->name('events.live');
            Route::get('/calendar', \App\Livewire\Admin\CalendarManager::class)->name('calendar');
            Route::get('/clients', \App\Livewire\Admin\ClientManager::class)->name('clients');
            Route::get('/users', UserManager::class)->name('users');
            Route::get('/music', MusicManager::class)->name('music');
            Route::get('/inventory', \App\Livewire\Admin\InventoryManager::class)->name('inventory');
            Route::get('/settings', Settings::class)->name('settings');
            Route::get('/analytics', AnalyticsManager::class)->name('analytics');

            // Admin PDF Routes
            Route::get('/quote/{quote}/pdf', [PdfController::class, 'downloadQuote'])->name('quote.pdf');
            Route::get('/contract/{contract}/pdf', [PdfController::class, 'downloadContract'])->name('contract.pdf');
            Route::get('/invoice/{invoice}/pdf', [PdfController::class, 'downloadInvoice'])->name('invoice.pdf');
            Route::get('/events/{event}/packing-list/pdf', [PdfController::class, 'downloadPackingList'])->name('events.packing_list.pdf');
            Route::get('/events/{event}/music-escaleta/pdf', [PdfController::class, 'downloadMusicEscaleta'])->name('events.music_escaleta.pdf');
        });
    });
});
