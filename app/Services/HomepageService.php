<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use App\Models\{
    Page,
    PageSection,
    Menu,
    Setting
};

class HomepageService
{
    public function index(?string $lang = null)
    {
        // ضبط اللغة
        $lang = in_array($lang, ['ar', 'en']) ? $lang : (app()->getLocale() ?? 'ar');
        App::setLocale($lang);

        // نجيب صفحة الهوم بكل السيكشنز والأيتيمز (كاش خفيف)
        $page = Cache::remember("home_page_{$lang}", 300, function () {
            return Page::where('slug', 'home')
                ->with(['sections' => function ($q) {
                    $q->where('is_active', true)->orderBy('order');
                }, 'sections.items' => function ($q) {
                    $q->orderBy('order');
                }])
                ->first();
        });

        // safety
        if (!$page) {
            abort(404, 'Home page not found.');
        }

        // حول الأقسام لمصفوفة سريعة بالـ type
        $sectionsByType = $page->sections->keyBy('type');

        // --------------- Banner ---------------
        $banner = $sectionsByType->get('banner');
        $bannerData = [
            'video_url'   => $banner->settings['video_url'] ?? null,
            'autoplay'    => $banner->settings['autoplay'] ?? true,
            'loop'        => $banner->settings['loop'] ?? true,
            'muted'       => $banner->settings['muted'] ?? true,
            'controls'    => $banner->settings['controls'] ?? false,
            'playsinline' => $banner->settings['playsinline'] ?? true,
        ];

        // --------------- Partners Slider ---------------
        $partners = $sectionsByType->get('partners');
        $sliderItems = $partners?->items?->map(function ($it) {
            return [
                'image_url' => $it->data['image_url'] ?? '',
                'alt'       => $it->data['alt'][app()->getLocale()] ?? ($it->data['alt']['ar'] ?? ''),
            ];
        }) ?? collect();

        // --------------- Knowmore / About ---------------
        $about = $sectionsByType->get('about');
        $knowMore = [
            'title' => $about->settings['title'][app()->getLocale()] ?? ($about->settings['title']['ar'] ?? ''),
            'desc'  => $about->settings['desc'][app()->getLocale()]  ?? ($about->settings['desc']['ar']  ?? ''),
            'readmore_link' => $about->settings['readmore_link'] ?? '#',
        ];

        // --------------- Media Stats ---------------
        $stats = $sectionsByType->get('stats');
        $mediaStats = $stats?->items?->map(function ($it) {
            return [
                'icon_url' => $it->data['icon_url'] ?? '',
                'number'   => $it->data['number'] ?? '',
                'label'    => $it->data['label'][app()->getLocale()] ?? ($it->data['label']['ar'] ?? ''),
            ];
        }) ?? collect();

        // --------------- Where Us ---------------
        $where = $sectionsByType->get('where_us');
        $whereTitle = $where->settings['title'][app()->getLocale()] ?? ($where->settings['title']['ar'] ?? '');
        $whereSlides = $where?->items?->map(function ($it) {
            return [
                'image_url'   => $it->data['image_url'] ?? '',
                'overlay_text' => $it->data['overlay_text'][app()->getLocale()] ?? ($it->data['overlay_text']['ar'] ?? ''),
            ];
        }) ?? collect();
        $brochure = [
            'text'     => $where->settings['brochure']['text'][app()->getLocale()] ?? ($where->settings['brochure']['text']['ar'] ?? ''),
            'icon_url' => $where->settings['brochure']['icon_url'] ?? 'img/download.png',
            'link_url' => $where->settings['brochure']['link_url'] ?? '#',
        ];

        // --------------- CTA ---------------
        $cta = $sectionsByType->get('cta');
        $ctaData = [
            'title'            => $cta->settings['title'][app()->getLocale()] ?? ($cta->settings['title']['ar'] ?? ''),
            'text'             => $cta->settings['text'][app()->getLocale()]  ?? ($cta->settings['text']['ar']  ?? ''),
            'link_text'        => $cta->settings['link_text'][app()->getLocale()] ?? ($cta->settings['link_text']['ar'] ?? ''),
            'link_url'         => $cta->settings['link_url'] ?? '#',
            'image_url'        => $cta->settings['image_url'] ?? '',
            'overlay_image_url' => $cta->settings['overlay_image_url'] ?? '',
        ];

        // --------------- Header/Menu/Sidebar/Settings ---------------
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

        $settings = Cache::remember("settings_{$lang}", 300, function () {
            return Setting::whereIn('key', [
                'site.phone',
                'site.lang_switch',
                'header.logo',
                'footer.logo',
                'social.links',
                'map.iframe',
                'sidebar.icons',
            ])->get()->keyBy('key');
        });

        $phone        = $settings['site.phone']->value[app()->getLocale()] ?? ($settings['site.phone']->value['ar'] ?? '');
        $langSwitch   = $settings['site.lang_switch']->value[app()->getLocale()] ?? '';
        $headerLogo   = $settings['header.logo']->value ?? ['image_url' => 'img/logo.png', 'alt' => ['ar' => 'بريم', 'en' => 'Breem']];
        $footerLogo   = $settings['footer.logo']->value ?? ['image_url' => 'img/whitelogo.png', 'alt' => ['ar' => 'بريم', 'en' => 'Breem']];
        $socialLinks  = $settings['social.links']->value ?? [];
        $mapIframe    = $settings['map.iframe']->value['embed'] ?? null;
        $sidebarIcons = collect($settings['sidebar.icons']->value ?? [])->map(function ($it) {
            return [
                'title' => $it['title'][app()->getLocale()] ?? ($it['title']['ar'] ?? ''),
                'url'   => $it['url'] ?? '#',
                'svg_fill' => $it['svg_fill'] ?? '#41A8A6',
            ];
        });

        // نبعت المتغيرات للـ master + الـ includes
        return view('web.pages.index', [
            'lang'            => $lang,

            // banner
            'banner'          => $bannerData,

            // slider (partners)
            'sliderItems'     => $sliderItems,

            // knowmore
            'knowMore'        => $knowMore,

            // media stats
            'mediaStats'      => $mediaStats,

            // where_us
            'whereTitle'      => $whereTitle,
            'whereSlides'     => $whereSlides,
            'brochure'        => $brochure,

            // cta
            'cta'             => $ctaData,

            // header/footer/shared
            'headerMenu'      => $headerMenu,
            'footerMenu'      => $footerMenu,
            'phone'           => $phone,
            'langSwitch'      => $langSwitch,
            'headerLogo'      => $headerLogo,
            'footerLogo'      => $footerLogo,
            'socialLinks'     => $socialLinks,
            'mapIframe'       => $mapIframe,
            'sidebarIcons'    => $sidebarIcons,
        ]);
    }
}
