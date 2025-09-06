<?php

use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Web\PagesController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect(app()->getLocale());
});

Route::get('/{lang}', function () {
    return view('web.pages.index');
})->name('home');

Route::get('/dashboard', function () {
    return view('admin.main');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


/***************************** Frontend ROUTES **********************************/

// Route::group(['as' => 'web.'], function () {

//     Route::controller(PagesController::class)->group(function () {
//         Route::get('/', 'index')->name('home');
//     });

// });


/***************************** Fallback ROUTES **********************************/


Route::fallback(function () {
    return view('404');
});

require __DIR__.'/auth.php';
require __DIR__.'/artisan.php';
