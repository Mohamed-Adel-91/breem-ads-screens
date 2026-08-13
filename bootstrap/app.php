<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // `commands:` is honoured independently of `using:`.
        commands: base_path('routes/console.php'),

        // A custom `using:` callback REPLACES Laravel's default route
        // registration, so the `web:`, `api:`, `health:` and `pages:` arguments
        // are ignored entirely when it is present. They used to be passed here
        // alongside this closure, which is why routes/api.php and /up were
        // silently never registered. Everything this application serves must be
        // registered explicitly below.
        using: function () {
            Route::middleware(['web'])
                ->group(base_path('routes/web.php'));
            Route::middleware(['web'])
                ->group(base_path('routes/admin.php'));
            Route::middleware(['web'])
                ->group(base_path('routes/artisan.php'));

            // Device API. routes/api.php already declares its own `v1` prefix
            // and its own middleware stack, so only the `api` prefix is added
            // here — matching the /api/v1/... contract the clients and tests use.
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Health endpoint, previously declared as `health: '/up'` and ignored.
            Route::middleware('web')->get('/up', function () {
                Event::dispatch(new DiagnosingHealth);

                return response('OK', 200)->header('Content-Type', 'text/plain');
            })->name('health');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocaleFromRequest::class,
            // Browser-surface hardening only. The Device API is consumed by a native
            // Android client, where these headers mean nothing.
            \App\Http\Middleware\AddSecurityHeaders::class,
        ]);
        $middleware->alias([
            'auth'               => \App\Http\Middleware\Authenticate::class,
            'guest'              => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'screen.auth'        => \App\Http\Middleware\EnsureScreenAuthentication::class,
            'setLocale'          => \App\Http\Middleware\SetLocaleFromRequest::class,
        ]);
    })->withProviders([
        \App\Providers\RateLimitServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
