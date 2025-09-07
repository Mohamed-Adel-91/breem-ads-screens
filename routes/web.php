<?php


use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Web\PagesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(app()->getLocale());
});




/***************************** Frontend ROUTES **********************************/

Route::group([
    'as'         => 'web.',
    'prefix'     => '{lang?}',
    'middleware' => 'setlocale',
    'where'      => ['lang' => 'en|ar'],
], function () {
    Route::controller(PagesController::class)->group(function () {
        Route::get('/', 'index')->name('home');
    });
});



/***************************** Fallback ROUTES **********************************/


Route::fallback(function () {
    return view('404');
});

require __DIR__ . '/auth.php';
// require __DIR__ . '/artisan.php';
