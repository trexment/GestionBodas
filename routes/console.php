<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Escaneo periódico de buzones IMAP para capturar solicitudes de información y leads
Schedule::command('emails:fetch-leads')->everyFiveMinutes()->withoutOverlapping();

