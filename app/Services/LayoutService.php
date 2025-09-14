<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class LayoutService
{
    public function getHeaderMenu()
    {
        return Cache::rememberForever('menu.header', function () {
            return Menu::where('location', 'header')
                ->where('is_active', true)
                ->with(['items' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->first();
        });
    }

    public function getFooterMenu()
    {
        return Cache::rememberForever('menu.footer', function () {
            return Menu::where('location', 'footer')
                ->where('is_active', true)
                ->with(['items' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->first();
        });
    }

    public function getSettings(): array
    {
        return Cache::rememberForever('layout.settings', function () {
            $phone = Setting::key('site.phone')->first()?->value;
            $socialLinks = Setting::key('social.links')->first()?->getTranslations('value') ?? [];
            $mapTranslations = Setting::key('map.iframe')->first()?->getTranslations('value') ?? [];
            $mapIframe = $mapTranslations['embed'] ?? null;

            return [
                'phone' => $phone,
                'social_links' => $socialLinks,
                'map_iframe' => $mapIframe,
            ];
        });
    }
}
