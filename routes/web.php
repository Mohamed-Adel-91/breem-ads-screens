<?php

use App\Http\Controllers\Web\PagesController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect(app()->getLocale()));

/***************************** Frontend ROUTES **********************************/

Route::group([
    'prefix'     => '{lang?}',
    'as'         => 'web.',
    'where'      => ['lang' => 'en|ar'],
], function () {
    Route::controller(PagesController::class)->group(function () {
        Route::get('/', 'index')->name('home');
    });
});



/***************************** Fallback ROUTES **********************************/

Route::fallback(fn() => view('404'));
