<?php

use App\Jobs\CheckExpiringAdsJob;
use App\Jobs\CheckScreenHealthJob;
use App\Models\PlaybackLog;
use App\Models\Report;
use App\Models\ScreenLog;
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

/*
| Operational data retention.
|
| Prunes ScreenLog, PlaybackLog and Report according to config/retention.php via the
| Prunable contract on those models. Daily and off-peak: pruning is a bulk delete
| against the largest tables in the schema, so it must not run at heartbeat cadence
| and should not compete with daytime traffic.
|
| SAFE BY DEFAULT. Every policy is disabled unless a positive retention value is
| configured, and a disabled policy's prunable() query matches no rows — so this task
| runs nightly and deletes precisely nothing until an operator sets a period. See
| App\Support\Retention, and `php artisan ops:status` for what is currently active.
|
| `php artisan model:prune --pretend` reports what WOULD be deleted without touching
| anything.
*/
Schedule::command('model:prune', [
    '--model' => [ScreenLog::class, PlaybackLog::class, Report::class],
])
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->name('operational:prune')
    ->description('Prune operational logs and reports per config/retention.php');
