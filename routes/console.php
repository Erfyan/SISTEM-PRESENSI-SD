<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal pencadangan database harian setiap pukul 01:00 pagi
Schedule::command('app:backup-db --keep=14')->dailyAt('01:00');
