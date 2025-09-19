<?php

use App\Http\Controllers\Admin\{
    AuthController,
    AdminController,
    ProfileController,
    ActivityLogController,
    ContactSubmissionController,
    SettingController,
    DashboardController,
    SeoMetaController,
    UserController,
    PermissionController,
    RoleController,
    AdController,
    ScheduleController,
    ScreenController,
    PlaceController,
    MonitoringController,
    LogController,
    ReportController,
};
use App\Http\Controllers\Admin\Cms\{
    ContactUsPageContentController,
    HomePageContentController,
    PageController,
    PageSectionController,
    SectionItemController,
    WhoWeArePageContentController,
};
use Illuminate\Support\Facades\Route;


Route::redirect('/admin-panel', '/' . app()->getLocale() . '/admin-panel')->name('admin.redirect');
Route::redirect('/admin-panel/login', '/' . app()->getLocale() . '/admin-panel/login')->name('admin.login.redirect');

Route::group([
    'prefix' => '{lang?}/admin-panel',
    'as' => 'admin.',
    'where' => ['lang' => 'en|ar'],
], function () {
    Route::middleware(['guest:admin', 'throttle:10,1'])->group(function () {
        Route::get('/login', [AuthController::class, 'index'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
        Route::post('/login/verify-otp', [AuthController::class, 'verifyOtp'])->name('verifyOtp');
    });

    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
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

        Route::resource('permissions', PermissionController::class)->middleware('role:super-admin');
        Route::resource('roles', RoleController::class)->middleware('role:super-admin');
        Route::get('/settings/edit', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings/update', [SettingController::class, 'update'])->name('settings.update');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::prefix('ads')->as('ads.')->group(function () {
            Route::get('/', [AdController::class, 'index'])->name('index')->middleware('permission:ads.view');
            Route::get('/create', [AdController::class, 'create'])->name('create')->middleware('permission:ads.create');
            Route::post('/', [AdController::class, 'store'])->name('store')->middleware('permission:ads.create');
            Route::get('/{ad}', [AdController::class, 'show'])->name('show')->middleware('permission:ads.view');
            Route::get('/{ad}/edit', [AdController::class, 'edit'])->name('edit')->middleware('permission:ads.edit');
            Route::put('/{ad}', [AdController::class, 'update'])->name('update')->middleware('permission:ads.edit');
            Route::delete('/{ad}', [AdController::class, 'destroy'])->name('destroy')->middleware('permission:ads.delete');

            Route::prefix('{ad}/schedules')->as('schedules.')->group(function () {
                Route::get('/', [ScheduleController::class, 'index'])->name('index')->middleware('permission:ads.view');
                Route::post('/', [ScheduleController::class, 'store'])->name('store')->middleware('permission:ads.schedule');
                Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('update')->middleware('permission:ads.schedule');
                Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy')->middleware('permission:ads.schedule');
            });
        });

        Route::prefix('screens')->as('screens.')->group(function () {
            Route::get('/', [ScreenController::class, 'index'])->name('index')->middleware('permission:screens.view');
            Route::get('/create', [ScreenController::class, 'create'])->name('create')->middleware('permission:screens.create');
            Route::post('/', [ScreenController::class, 'store'])->name('store')->middleware('permission:screens.create');
            Route::get('/{screen}', [ScreenController::class, 'show'])->name('show')->middleware('permission:screens.view');
            Route::get('/{screen}/edit', [ScreenController::class, 'edit'])->name('edit')->middleware('permission:screens.edit');
            Route::put('/{screen}', [ScreenController::class, 'update'])->name('update')->middleware('permission:screens.edit');
            Route::delete('/{screen}', [ScreenController::class, 'destroy'])->name('destroy')->middleware('permission:screens.delete');
        });

        Route::prefix('places')->as('places.')->group(function () {
            Route::get('/', [PlaceController::class, 'index'])->name('index')->middleware('permission:places.view');
            Route::get('/create', [PlaceController::class, 'create'])->name('create')->middleware('permission:places.create');
            Route::post('/', [PlaceController::class, 'store'])->name('store')->middleware('permission:places.create');
            Route::get('/{place}', [PlaceController::class, 'show'])->name('show')->middleware('permission:places.view');
            Route::get('/{place}/edit', [PlaceController::class, 'edit'])->name('edit')->middleware('permission:places.edit');
            Route::put('/{place}', [PlaceController::class, 'update'])->name('update')->middleware('permission:places.edit');
            Route::delete('/{place}', [PlaceController::class, 'destroy'])->name('destroy')->middleware('permission:places.delete');
        });

        Route::prefix('monitoring')->as('monitoring.')->group(function () {
            Route::get('/', [MonitoringController::class, 'index'])->name('index')->middleware('permission:monitoring.view');
            Route::get('/screens/{screen}', [MonitoringController::class, 'showScreen'])->name('screens.show')->middleware('permission:monitoring.view');
            Route::post('/screens/{screen}/acknowledge', [MonitoringController::class, 'acknowledgeAlert'])->name('screens.acknowledge')->middleware('permission:monitoring.manage');
        });

        Route::prefix('logs')->as('logs.')->group(function () {
            Route::get('/', [LogController::class, 'index'])->name('index')->middleware('permission:logs.view');
            Route::get('/download', [LogController::class, 'download'])->name('download')->middleware('permission:logs.export');
        });

        Route::prefix('reports')->as('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index')->middleware('permission:reports.view');
            Route::post('/generate', [ReportController::class, 'generate'])->name('generate')->middleware('permission:reports.generate');
            Route::get('/{report}', [ReportController::class, 'show'])->name('show')->middleware('permission:reports.view');
            Route::get('/{report}/download', [ReportController::class, 'download'])->name('download')->middleware('permission:reports.view');
        });
    });
});
// === CMS management routes ===
Route::group([
    'prefix' => '{lang?}/admin-panel',
    'as' => 'admin.',
    'where' => ['lang' => 'en|ar'],
    'middleware' => ['auth:admin'],
], function () {
    Route::prefix('cms')->as('cms.')->group(function () {
        Route::get('/home/edit', [HomePageContentController::class, 'edit'])->name('home.edit');
        Route::put('/home', [HomePageContentController::class, 'update'])->name('home.update');

        Route::get('/whoweare/edit', [WhoWeArePageContentController::class, 'edit'])->name('whoweare.edit');
        Route::put('/whoweare', [WhoWeArePageContentController::class, 'update'])->name('whoweare.update');

        Route::get('/contact-us/edit', [ContactUsPageContentController::class, 'edit'])->name('contact.edit');
        Route::put('/contact-us', [ContactUsPageContentController::class, 'update'])->name('contact.update');
    });

    Route::get('/cms/pages/{slug}/edit', function (?string $lang, string $slug) {
        $targets = [
            'home' => 'admin.cms.home.edit',
            'whoweare' => 'admin.cms.whoweare.edit',
            'contact-us' => 'admin.cms.contact.edit',
        ];

        if (!array_key_exists($slug, $targets)) {
            abort(404);
        }

        return redirect()->route($targets[$slug], ['lang' => $lang]);
    })->name('cms.pages.edit');

    // Sections
    Route::patch('/cms/sections/{section}/toggle', [PageSectionController::class, 'toggle'])->name('cms.sections.toggle');
    Route::patch('/cms/sections/{section}', [PageSectionController::class, 'update'])->name('cms.sections.update');
    Route::delete('/cms/sections/{section}', [PageSectionController::class, 'destroy'])->name('cms.sections.destroy');

    // Items
    Route::patch('/cms/items/{item}/toggle', [SectionItemController::class, 'toggle'])->name('cms.items.toggle');
    Route::patch('/cms/items/{item}', [SectionItemController::class, 'update'])->name('cms.items.update');
    Route::delete('/cms/items/{item}', [SectionItemController::class, 'destroy'])->name('cms.items.destroy');

    // Contact submissions
    Route::get('/contact-submissions', [ContactSubmissionController::class, 'index'])->name('contact_submissions.index');
    Route::delete('/contact-submissions/{submission}', [ContactSubmissionController::class, 'destroy'])->name('contact_submissions.destroy');
});
