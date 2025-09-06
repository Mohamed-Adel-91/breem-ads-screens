<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Artisan Routes
|--------------------------------------------------------------------------
|
| Here is where you can find all the artisan routes for the artisan commands.
|
*/

/***************************** Artisan ROUTES **********************************/

Route::get('run-optimize/day{day_number}', function ($day_number) {
    $today = date('d');
    if ($day_number == $today) {
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');
        dd('Caches cleared, configurations cleared, routes cleared, views cleared, and optimized successfully !');
    } else {
        abort(404);
    }
})->where('day_number', '[0-9]{2}');

Route::get('run-migrate/day{day_number}', function ($day_number) {
    $today = date('d');
    if ($day_number == $today) {
        Artisan::call('migrate', ['--force' => true]);
        dd('New migrations run successfully !');
    } else {
        abort(404);
    }
})->where('day_number', '[0-9]{2}');

Route::get('run-seeder/day{day_number}', function ($day_number) {
    $today = date('d');

    if ($day_number == $today) {
        Artisan::call('db:seed', ['--force' => true]);
        dd('Database seeded successfully !');
    } else {
        abort(404);
    }
})->where('day_number', '[0-9]{2}');


Route::get('clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');
    Artisan::call('migrate', ['--force' => true]);

    dd("✅ All caches cleared, optimizations reset, and migrations run at: " . now()->toDateTimeString());
});
