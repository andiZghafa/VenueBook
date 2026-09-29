<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Delete old backups
Schedule::command('backup:clean')->dailyAt('17:00');

// Run the database backup
Schedule::command('backup:run --only-db')->dailyAt('17:00');
