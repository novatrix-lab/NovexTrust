<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily compliance recompute (SPEC.md §8/§11). Requires the cron entry
// `* * * * * php artisan schedule:run` on the server.
Schedule::command('deadlines:recompute')->dailyAt('02:00')->withoutOverlapping();

// Queue delivery of due alerts (hourly; day-level cadence).
Schedule::command('alerts:send')->hourly()->withoutOverlapping();
