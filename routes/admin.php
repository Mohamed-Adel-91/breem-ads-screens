<?php

use App\Http\Controllers\Admin\{
    AuthController,
    AdminController,
    ProfileController,
    ActivityLogController,
    SettingController,
    DashboardController,
    SeoMetaController,
    UserController,
    PermissionController,
    RoleController
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
Route::get('/admin-panel/login', function () {
    return redirect('/' . app()->getLocale() . '/admin-panel');
})->name('admin.login.redirect');
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
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::prefix('profile')->as('profile.')->group(function () {
            Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
            Route::put('/update', [ProfileController::class, 'update'])->name('update');
            Route::put('/update-password', [ProfileController::class, 'updatePassword'])->name('updatePassword');
            Route::post('/verify-otp', [ProfileController::class, 'verifyPasswordOtp'])->name('verifyPasswordOtp');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('destroy');
        });
        Route::resource('admins', AdminController::class)->except(['show'])
            ->middleware('role_or_permission:super-admin|admins.view|admins.create|admins.edit');
        Route::middleware('role:super-admin')->group(function () {
            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity_logs.index');
            Route::get('activity-logs/download', [ActivityLogController::class, 'download'])->name('activity_logs.download');
        });
        Route::resource('seo_metas', SeoMetaController::class)->except(['show']);
        Route::get('/settings/edit', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings/update', [SettingController::class, 'update'])->name('settings.update');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::resource('permissions', PermissionController::class)->middleware('role:super-admin');
        Route::resource('roles', RoleController::class)->middleware('role:super-admin');
    });
});

// === CMS management routes ===
Route::group([
    'prefix' => '{lang?}/admin-panel',
    'as' => 'admin.',
    'where' => ['lang' => 'en|ar'],
    'middleware' => ['auth:admin'],
], function () {
    // Pages edit
    Route::get('/cms/pages/{slug}/edit', [\App\Http\Controllers\Admin\Cms\PageController::class, 'edit'])->name('cms.pages.edit');

    // Sections
    Route::patch('/cms/sections/{section}/toggle', [\App\Http\Controllers\Admin\Cms\PageSectionController::class, 'toggle'])->name('cms.sections.toggle');
    Route::patch('/cms/sections/{section}', [\App\Http\Controllers\Admin\Cms\PageSectionController::class, 'update'])->name('cms.sections.update');
    Route::delete('/cms/sections/{section}', [\App\Http\Controllers\Admin\Cms\PageSectionController::class, 'destroy'])->name('cms.sections.destroy');

    // Items
    Route::patch('/cms/items/{item}/toggle', [\App\Http\Controllers\Admin\Cms\SectionItemController::class, 'toggle'])->name('cms.items.toggle');
    Route::patch('/cms/items/{item}', [\App\Http\Controllers\Admin\Cms\SectionItemController::class, 'update'])->name('cms.items.update');
    Route::delete('/cms/items/{item}', [\App\Http\Controllers\Admin\Cms\SectionItemController::class, 'destroy'])->name('cms.items.destroy');

    // Contact submissions
    Route::get('/contact-submissions', [\App\Http\Controllers\Admin\ContactSubmissionController::class, 'index'])->name('contact_submissions.index');
    Route::delete('/contact-submissions/{submission}', [\App\Http\Controllers\Admin\ContactSubmissionController::class, 'destroy'])->name('contact_submissions.destroy');
});

