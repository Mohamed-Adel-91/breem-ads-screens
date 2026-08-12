<?php

use App\Http\Controllers\Web\PagesController;
use App\Http\Controllers\Web\ContactSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect(app()->getLocale()));

/***************************** Frontend ROUTES **********************************/

Route::group([
    'prefix'     => '{lang?}',
    'as'         => 'web.',
    'where'      => ['lang' => 'en|ar'],
    'middleware' => ['setLocale'],
], function () {
    Route::controller(PagesController::class)->group(function () {
        Route::get('/', 'index')->name('home');
        Route::get('/whoweare', 'whoweare')->name('whoweare');
        Route::get('/contact-us', 'contactUs')->name('contactUs');
    });

    // Contact forms submission
    Route::post('/contact-submit/{type}', [ContactSubmissionController::class, 'store'])
        ->where(['type' => 'ads|screens|create|faq'])
        ->name('contact.submit');
});

/***************************** Fallback ROUTES **********************************/

// Same 404 view as before, but with a real 404 status instead of 200.
Route::fallback(fn() => response()->view('404', [], 404));
