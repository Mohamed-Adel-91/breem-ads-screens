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
| WHAT IS DELIBERATELY NOT HERE (Phase 15):
|
|   - **`run-seeder` is gone.** `db:seed` is a first-install bootstrap, not an
|     operation, and against a live database it is destructive in three separate
|     ways: AdminUserSeeder `updateOrCreate`s the super-admin and so RESETS its
|     password to `ADMIN_PASSWORD` — or, when that is unset, to a hash of the empty
|     string; DemoSeeder writes a demo place, a screen with the fixed code `SCR-001`
|     and a demo advertiser into production; and ReportsAndLogsSeeder writes
|     fabricated playback logs, which is proof-of-play evidence. None of that may be
|     one authenticated GET away. Seed a NEW environment from the CLI —
|     `php artisan db:seed --class=RoleSeeder` then `--class=AdminUserSeeder` — as
|     docs/production-launch-checklist.md describes.
|
|   - **`clear-cache` no longer migrates.** A route whose whole name is "clear the
|     cache" must not also alter the schema; that was an invisible side effect on the
|     one endpoint here with no day check at all. Migrations have their own endpoint
|     below, and a deployment runs `migrate --force` from the CLI.
|
| RESIDUAL RISK, accepted and recorded: what remains are state-changing GETs, so
| Laravel's CSRF middleware does not cover them (it verifies POST/PUT/PATCH/DELETE
| only). With SESSION_SAME_SITE=lax the session cookie is not sent on cross-site
| subresource requests, so an <img> cannot fire them, but a top-level navigation a
| signed-in super-admin is tricked into following can. The remaining actions are a
| cache clear and a forward-only `migrate --force`, both of which a deployment
| performs anyway, so the impact is availability at worst. Converting them to POST
| with the existing CSRF middleware is the recommended follow-up.
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

    Route::get('clear-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');

        return response('All caches cleared and optimizations reset at: ' . now()->toDateTimeString())
            ->header('Content-Type', 'text/plain');
    });
});
