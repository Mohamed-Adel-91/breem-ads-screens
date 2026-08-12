<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Artisan (operations) Routes
|--------------------------------------------------------------------------
|
| Maintenance endpoints that execute Artisan commands over HTTP.
|
| SECURITY: these were previously reachable by anyone on the internet. The
| day-of-month check is not authentication — it is guessable in at most 31
| attempts, and `clear-cache` had no check at all while still running
| `migrate --force`. They are now behind the existing admin guard and the
| existing `super-admin` role; no new role or permission was introduced.
|
*/

Route::middleware(['auth:admin', 'role:super-admin'])->group(function (): void {
    Route::get('run-optimize/day{day_number}', function (string $day_number) {
        abort_unless($day_number === date('d'), 404);

        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');

        return response('Caches, configuration, routes and views cleared successfully.')
            ->header('Content-Type', 'text/plain');
    })->where('day_number', '[0-9]{2}');

    Route::get('run-migrate/day{day_number}', function (string $day_number) {
        abort_unless($day_number === date('d'), 404);

        Artisan::call('migrate', ['--force' => true]);

        return response('New migrations run successfully.')
            ->header('Content-Type', 'text/plain');
    })->where('day_number', '[0-9]{2}');

    Route::get('run-seeder/day{day_number}', function (string $day_number) {
        abort_unless($day_number === date('d'), 404);

        Artisan::call('db:seed', ['--force' => true]);

        return response('Database seeded successfully.')
            ->header('Content-Type', 'text/plain');
    })->where('day_number', '[0-9]{2}');

    Route::get('clear-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');
        Artisan::call('migrate', ['--force' => true]);

        return response('All caches cleared, optimizations reset and migrations run at: ' . now()->toDateTimeString())
            ->header('Content-Type', 'text/plain');
    });
});
