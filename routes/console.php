<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Runs from a single cron entry: * * * * * php artisan schedule:run (see docs/DEPLOYMENT-CPANEL.md)

// Queued mail (order notifications). Shared hosting has no long-running worker, so drain the queue each minute.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('backup:run')->dailyAt('02:30')->withoutOverlapping();
Schedule::command('security:prune')->dailyAt('03:15');
