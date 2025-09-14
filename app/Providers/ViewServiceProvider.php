<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use App\Models\{Menu, Setting};

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer(['web.layouts.*'], function ($view) {
            $lang = App::getLocale() ?? 'ar';

            $headerMenu = Cache::remember(
                "menu_header_{$lang}",
                300,
                fn() =>
                Menu::with(['items' => fn($q) => $q->where('is_active', true)->orderBy('order')])
                    ->where('location', 'header')->first()
            );
            $footerMenu = Cache::remember(
                "menu_footer_{$lang}",
                300,
                fn() =>
                Menu::with(['items' => fn($q) => $q->where('is_active', true)->orderBy('order')])
                    ->where('location', 'footer')->first()
            );

            $settings = Cache::remember("settings_shared_{$lang}", 300, function () {
                return Setting::whereIn('key', [
                    'site.phone',
                    'site.lang_switch',
                    'header.logo',
                    'footer.logo',
                    'social.links',
                    'map.iframe',
                    'sidebar.icons'
                ])->get()->keyBy('key');
            });

            $view->with([
                'headerMenu' => $headerMenu,
                'footerMenu' => $footerMenu,
                'phone'      => $settings['site.phone']->value[$lang] ?? '',
                'headerLogo' => $settings['header.logo']->value ?? [],
                'footerLogo' => $settings['footer.logo']->value ?? [],
                'socialLinks' => $settings['social.links']->value ?? [],
                'mapIframe'  => $settings['map.iframe']->value['embed'] ?? null,
                'sidebarIcons' => collect($settings['sidebar.icons']->value ?? []),
            ]);
        });
    }
}
