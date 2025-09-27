<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: base_path('routes/web.php'),
        api: base_path('routes/api.php'),
        commands: base_path('routes/console.php'),
        health: '/up',
        using: function () {
            Route::middleware(['web'])
                ->group(base_path('routes/web.php'));
            Route::middleware(['web'])
                ->group(base_path('routes/admin.php'));
            Route::middleware(['web'])
                ->group(base_path('routes/auth.php'));
            Route::middleware(['web'])
                ->group(base_path('routes/artisan.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocaleFromRequest::class,
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
