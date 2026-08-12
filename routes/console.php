<?php

use App\Jobs\CheckExpiringAdsJob;
use App\Jobs\CheckScreenHealthJob;
use App\Support\ScreenHealth;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console routes and the application schedule
|--------------------------------------------------------------------------
|
| This file is THE schedule. bootstrap/app.php passes it as `commands:`, so
| everything registered here is what `php artisan schedule:list` reports.
|
| app/Console/Kernel.php also declares a schedule() method, but Laravel 12 binds
| Illuminate\Foundation\Console\Kernel directly and nothing binds the application
| subclass — so that file is inert and its entries never ran. Do not add schedule
| entries there.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Offline detection.
|
| This previously read `Schedule::command('screens:check-status')`, a command
| that has never existed in this repository — so the only registered task failed
| on every tick and no screen was ever transitioned to offline. The work is done
| by CheckScreenHealthJob, which is idempotent: it selects only screens that are
| still `online` with a stale heartbeat, so re-running it changes nothing once a
| screen has been transitioned.
|
| Runs every minute so detection latency is bounded by the offline threshold
| rather than by the sweep interval. withoutOverlapping() keeps a slow sweep from
| stacking.
*/
Schedule::job(new CheckScreenHealthJob())
    ->everyMinute()
    ->withoutOverlapping()
    ->name('screens:detect-offline')
    ->description('Mark screens offline after '.ScreenHealth::offlineAfter().'s without a heartbeat');

Schedule::job(new CheckExpiringAdsJob())
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->name('ads:check-expiring')
    ->description('Notify administrators about ads nearing their end date');
