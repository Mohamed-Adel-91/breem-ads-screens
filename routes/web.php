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

Route::fallback(fn() => view('404'));
