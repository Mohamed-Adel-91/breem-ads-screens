<?php

namespace App\Providers;

use App\Contracts\FileServiceInterface;
use App\Helpers\ComponentHelper;
use App\Services\FileService;
use App\Services\LayoutService;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SectionItem;
use App\Models\SeoMeta;
use App\Models\Setting;
use App\Observers\AdObserver;
use App\Observers\AdScheduleObserver;
use App\Observers\MenuItemObserver;
use App\Observers\MenuObserver;
use App\Observers\PageObserver;
use App\Observers\PageSectionObserver;
use App\Observers\SectionItemObserver;
use App\Observers\SettingObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
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
        Blade::directive('t', function ($expression) {
            return "<?php echo e(\\App\\Support\\Lang::t($expression)); ?>";
        });
        View::share(ComponentHelper::generalComponents());
        // Shared view data for EVERY view. `'*'` means every template AND every
        // partial, layout and Blade component a page renders, so this closure runs
        // dozens to hundreds of times per request — a page listing 100 screens renders
        // 100 option components and ran this 100 times.
        //
        // The SEO lookup is keyed on the current route name, which cannot change
        // within a request, so every one of those calls issued the identical
        // `select * from seo_metas where page = ?`. Phase 15's fleet-scale smoke test
        // measured 100 of them on a single admin page. It is resolved once per route
        // per request now.
        //
        // Memoised in the CONTAINER, not in a `static`: the container is rebuilt for
        // each request and for each test, whereas a static closure variable would
        // survive both and hand one test the previous test's SEO row. The value is
        // wrapped in an array because `bound()` is an isset() check and a legitimately
        // null result would otherwise never be seen as memoised — the miss case, and
        // therefore the repeated query, is exactly the common one.
        View::composer('*', function ($view) {
            $routeName = Route::currentRouteName();
            $key = 'breem.seo_meta.'.($routeName ?? '');

            if (! app()->bound($key)) {
                app()->instance($key, ['meta' => SeoMeta::where('page', $routeName)->first()]);
            }

            $view->with('meta', app($key)['meta']);
            $view->with('currentLocale', app()->getLocale());
        });

        // Register model observers that invalidate cached layout/page data
        Ad::observe(AdObserver::class);
        AdSchedule::observe(AdScheduleObserver::class);
        Menu::observe(MenuObserver::class);
        MenuItem::observe(MenuItemObserver::class);
        Setting::observe(SettingObserver::class);
        Page::observe(PageObserver::class);
        PageSection::observe(PageSectionObserver::class);
        SectionItem::observe(SectionItemObserver::class);

        View::composer([
            'web.layouts.components.transparent-header',
            'web.layouts.components.solid-header',
            'web.layouts.components.footer',
            // The floating social rail is included by the layout directly rather than by
            // a header, so it inherits nothing and needs its own entry. Without it the
            // rail sees no $layoutSettings and silently renders no links at all.
            'web.layouts.components.sidebar',
        ], function ($view) {
            /*
             * Resolved per view, deliberately, and NOT memoised in the container the way
             * the SEO lookup above is.
             *
             * That memoisation was tried and reverted. It is safe for SEO because a route
             * name cannot change within a request. It is NOT safe here for two reasons:
             * the values are locale-resolved, so a container shared across requests serves
             * one language's address to the other — the exact bug LayoutService's
             * per-locale cache key exists to prevent — and they are mutable, so an admin
             * save followed by a read in the same lifetime returns the stale copy.
             *
             * The cost of getting it right is three extra reads of an already-warm cache,
             * which are only queries at all because this environment uses the database
             * cache store. LayoutService::getSettings() is one bounded query on a miss.
             */
            $layoutService = app(LayoutService::class);

            $view->with([
                'headerMenu' => $layoutService->getHeaderMenu(),
                'footerMenu' => $layoutService->getFooterMenu(),
                'layoutSettings' => $layoutService->getSettings(),
            ]);
        });
    }
}
