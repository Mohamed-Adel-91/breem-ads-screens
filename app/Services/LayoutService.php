<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Setting;

class LayoutService
{
    public function getHeaderMenu()
    {
        return Menu::where('location', 'header')
            ->where('is_active', true)
            ->with(['items' => function ($query) {
                $query->where('is_active', true);
            }])
            ->first();
    }

    public function getFooterMenu()
    {
        return Menu::where('location', 'footer')
            ->where('is_active', true)
            ->with(['items' => function ($query) {
                $query->where('is_active', true);
            }])
            ->first();
    }

    public function getSettings(): array
    {
        $phone = Setting::key('site.phone')->first()?->value;
        $socialLinks = Setting::key('social.links')->first()?->getTranslations('value') ?? [];
        $mapIframe = Setting::key('map.iframe')->first()?->getTranslations('value')['embed'] ?? null;

        return [
            'phone' => $phone,
            'social_links' => $socialLinks,
            'map_iframe' => $mapIframe,
        ];
    }
}

