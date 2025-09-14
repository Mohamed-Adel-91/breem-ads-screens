<?php

namespace App\Providers;

use App\Contracts\FileServiceInterface;
use App\Helpers\ComponentHelper;
use App\Services\FileService;
use App\Services\LayoutService;
use App\Models\SeoMeta;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(FileServiceInterface::class, FileService::class);
        require_once app_path('Helpers/helpers.php');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();
        View::share(ComponentHelper::generalComponents());
        View::composer('*', function ($view) {
            $routeName = Route::currentRouteName();
            $meta = SeoMeta::where('page', $routeName)->first();
            $view->with('meta', $meta);
        });

        View::composer([
            'web.layouts.components.transparent-header',
            'web.layouts.components.solid-header',
            'web.layouts.components.footer',
        ], function ($view) {
            $layoutService = app(LayoutService::class);

            $view->with([
                'headerMenu' => $layoutService->getHeaderMenu(),
                'footerMenu' => $layoutService->getFooterMenu(),
                'layoutSettings' => $layoutService->getSettings(),
            ]);
        });
    }
}
