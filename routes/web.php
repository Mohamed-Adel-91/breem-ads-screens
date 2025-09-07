<?php


use App\Http\Controllers\Web\PagesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(app()->getLocale());
});

/***************************** Frontend ROUTES **********************************/

Route::group([
    'as'         => 'web.',
    'prefix'     => '{lang?}',
    'where'      => ['lang' => 'en|ar'],
], function () {
    Route::controller(PagesController::class)->group(function () {
        Route::get('/', 'index')->name('home');
    });
});

require __DIR__ . '/auth.php';
require __DIR__ . '/artisan.php';

/***************************** Fallback ROUTES **********************************/

Route::fallback(fn () => view('404'));

