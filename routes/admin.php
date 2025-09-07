<?php

use App\Http\Controllers\Admin\{
    AuthController,
    AdminController,
    ProfileController,
    ActivityLogController,
    SettingController,
    DashboardController,
    SeoMetaController,
    UserController
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can find all the admin routes for the admin panel.
|
*/

Route::get('/admin-panel', function () {
    return redirect('/' . app()->getLocale() . '/admin-panel');
})->name('admin.redirect');
Route::get('/login-alias', function (\Illuminate\Http\Request $request) {
    $lang = $request->route('lang') ?? app()->getLocale() ?? 'ar';
    return redirect()->route('admin.login', ['lang' => $lang]);
})->name('login');

/***************************** ADMIN ROUTES **********************************/


Route::group([
    'prefix'     => '{lang?}/admin-panel',
    'as'         => 'admin.',
    'where'      => ['lang' => 'en|ar'],
], function () {
    Route::group(['middleware' => ['guest:admin', 'throttle:10,1']], function () {
        Route::get('/login', [AuthController::class, 'index'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
        Route::post('/login/verify-otp', [AuthController::class, 'verifyOtp'])->name('verifyOtp');
    });
    Route::group(['middleware' => ['auth:admin']], function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::prefix('profile')->as('profile.')->group(function () {
            Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
            Route::put('/update', [ProfileController::class, 'update'])->name('update');
            Route::put('/update-password', [ProfileController::class, 'updatePassword'])->name('updatePassword');
            Route::post('/verify-otp', [ProfileController::class, 'verifyPasswordOtp'])->name('verifyPasswordOtp');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('destroy');
        });
        Route::middleware('role:1')->group(function () {
            Route::resource('admins', AdminController::class)->except(['show']);
            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity_logs.index');
            Route::get('activity-logs/download', [ActivityLogController::class, 'download'])->name('activity_logs.download');
        });
        Route::resource('seo_metas', SeoMetaController::class)->except(['show']);
        Route::get('/settings/edit', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings/update', [SettingController::class, 'update'])->name('settings.update');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
});
