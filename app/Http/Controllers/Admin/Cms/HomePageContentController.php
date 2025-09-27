<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Models\SectionItem;
use App\Support\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomePageContentController extends BasePageContentController
{
    public function edit(string $lang)
    {
        $page = $this->loadPage('home');
        $sections = $page->sections->keyBy('type');

        $banner = $sections->get('banner');
        $partners = $sections->get('partners');
        $about = $sections->get('about');
        $stats = $sections->get('stats');
        $where = $sections->get('where_us');
        $cta = $sections->get('cta');

        return view('admin.cms.home.edit', [
            'page' => $page,
            'locales' => $this->locales,
            'banner' => $banner,
            'bannerData' => $this->getSectionTranslations($banner),
            'partners' => $partners,
            'partnerItemData' => $this->getItemsTranslations($partners),
            'about' => $about,
            'aboutData' => $this->getSectionTranslations($about),
            'stats' => $stats,
            'statsItemData' => $this->getItemsTranslations($stats),
            'whereUs' => $where,
            'whereData' => $this->getSectionTranslations($where),
            'whereItemsData' => $this->getItemsTranslations($where),
            'cta' => $cta,
            'ctaData' => $this->getSectionTranslations($cta),
        ]);
    }

    public function update(string $lang, Request $request)
    {
        $page = $this->loadPage('home');
        $sections = $page->sections->keyBy('type');

        $banner = $sections->get('banner');
        $partners = $sections->get('partners');
        $about = $sections->get('about');
        $stats = $sections->get('stats');
        $where = $sections->get('where_us');
        $cta = $sections->get('cta');

        $rules = [
            'banner.video' => ['nullable', 'file', 'mimetypes:video/mp4', 'max:153600'],
            'banner.autoplay' => ['nullable', 'boolean'],
            'banner.loop' => ['nullable', 'boolean'],
            'banner.muted' => ['nullable', 'boolean'],
            'banner.controls' => ['nullable', 'boolean'],
            'banner.playsinline' => ['nullable', 'boolean'],
            'partners.items' => ['nullable', 'array'],
            'partners.items.*.id' => ['nullable', 'integer'],
            'partners.items.*.order' => ['nullable', 'integer', 'min:0'],
            'partners.items.*.image' => ['nullable', 'file', 'image', 'max:10240'],
            'partners.items.*.existing_image' => ['nullable', 'string'],
            'about' => ['nullable', 'array'],
            'stats.items' => ['nullable', 'array'],
            'stats.items.*.id' => ['nullable', 'integer'],
            'stats.items.*.order' => ['nullable', 'integer', 'min:0'],
            'stats.items.*.icon' => ['nullable', 'file', 'image', 'max:5120'],
            'stats.items.*.existing_icon' => ['nullable', 'string'],
            'where_us.title' => ['nullable', 'array'],
            'where_us.brochure_text' => ['nullable', 'array'],
            'where_us.brochure_icon' => ['nullable', 'file', 'image', 'max:5120'],
            'where_us.brochure_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'where_us.brochure_link' => ['nullable', 'string', 'max:500'],
            'where_us.items' => ['nullable', 'array'],
            'where_us.items.*.id' => ['nullable', 'integer'],
            'where_us.items.*.order' => ['nullable', 'integer', 'min:0'],
            'where_us.items.*.image' => ['nullable', 'file', 'image', 'max:10240'],
            'where_us.items.*.existing_image' => ['nullable', 'string'],
            'cta.image' => ['nullable', 'file', 'image', 'max:10240'],
            'cta.overlay_image' => ['nullable', 'file', 'image', 'max:10240'],
        ];

        foreach ($this->locales as $locale) {
            $rules["about.$locale.title"] = ['nullable', 'string', 'max:255'];
            $rules["about.$locale.desc"] = ['nullable', 'string'];
            $rules["about.$locale.readmore_text"] = ['nullable', 'string', 'max:255'];
            $rules["about.$locale.readmore_link"] = ['nullable', 'string', 'max:255'];

            $rules["partners.items.*.alt.$locale"] = ['nullable', 'string', 'max:255'];

            $rules["stats.items.*.number.$locale"] = ['nullable', 'string', 'max:255'];
            $rules["stats.items.*.label.$locale"] = ['nullable', 'string', 'max:255'];

            $rules["where_us.title.$locale"] = ['nullable', 'string', 'max:255'];
            $rules["where_us.brochure_text.$locale"] = ['nullable', 'string', 'max:255'];
            $rules["where_us.items.*.overlay.$locale"] = ['nullable', 'string', 'max:255'];

            $rules["cta.$locale.title"] = ['nullable', 'string', 'max:255'];
            $rules["cta.$locale.text"] = ['nullable', 'string'];
            $rules["cta.$locale.link_text"] = ['nullable', 'string', 'max:255'];
            $rules["cta.$locale.link_url"] = ['nullable', 'string', 'max:500'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($request, $banner, $partners, $about, $stats, $where, $cta) {
            if ($banner) {
                $this->updateBannerSection($request, $banner);
            }

            if ($partners) {
                $this->syncPartnerItems($request, $partners);
            }

            if ($about) {
                $this->updateAboutSection($request, $about);
            }

            if ($stats) {
                $this->syncStatsItems($request, $stats);
            }

            if ($where) {
                $this->updateWhereUsSection($request, $where);
            }

            if ($cta) {
                $this->updateCtaSection($request, $cta);
            }
        });

        Cache::forget('page.home');

        return redirect()
            ->route('admin.cms.home.edit', ['lang' => $lang])
            ->with('success', Lang::t('admin.flash.cms.home_updated', 'تم تحديث محتوى صفحة الرئيسية بنجاح.'));
    }

    protected function updateBannerSection(Request $request, $section): void
    {
        $data = $this->getSectionTranslations($section);
        $existingVideo = $data[$this->primaryLocale]['video_path'] ?? null;

        $videoPath = $this->upload($request, 'banner.video', 'cms/home/banner', $existingVideo);

        foreach ($this->locales as $locale) {
            $current = $data[$locale] ?? [];

            $data[$locale] = array_merge($current, [
                'video_path' => $videoPath,
                'autoplay' => $request->boolean('banner.autoplay'),
                'loop' => $request->boolean('banner.loop'),
                'muted' => $request->boolean('banner.muted'),
                'controls' => $request->boolean('banner.controls'),
                'playsinline' => $request->boolean('banner.playsinline'),
            ]);
        }

        $section->section_data = $data;
        $section->save();
    }

    protected function syncPartnerItems(Request $request, $section): void
    {
        $inputItems = $request->input('partners.items', []);
        $existingCollection = $section->items;
        $existingItems = $existingCollection->keyBy('id');
        $existingData = $this->getItemsTranslations($section);

        $persistedIds = [];

        foreach ($inputItems as $index => $payload) {
            $itemId = isset($payload['id']) ? (int) $payload['id'] : null;
            $item = $itemId ? $existingItems->get($itemId) : new SectionItem(['section_id' => $section->id]);

            $currentTranslations = $itemId && isset($existingData[$itemId])
                ? $existingData[$itemId]
                : array_fill_keys($this->locales, []);

            $existingPath = $payload['existing_image'] ?? ($currentTranslations[$this->primaryLocale]['image_path'] ?? null);

            $imagePath = $this->upload($request, "partners.items.$index.image", 'cms/home/partners', $existingPath);

            if (!$itemId && !$imagePath) {
                // Skip incomplete new items
                continue;
            }

            $itemData = [];

            foreach ($this->locales as $locale) {
                $itemData[$locale] = array_merge($currentTranslations[$locale] ?? [], [
                    'image_path' => $imagePath,
                    'alt' => $payload['alt'][$locale] ?? null,
                ]);
            }

            $item->section_id = $section->id;
            $item->order = (int) ($payload['order'] ?? 0);
            $item->data = $itemData;
            $item->save();

            $persistedIds[] = $item->id;
        }

        if (!empty($persistedIds)) {
            $section->items()->whereNotIn('id', $persistedIds)->delete();
        } elseif ($existingCollection->isNotEmpty() && empty($inputItems)) {
            $section->items()->delete();
        }
    }

    protected function updateAboutSection(Request $request, $section): void
    {
        $data = $this->getSectionTranslations($section);

        foreach ($this->locales as $locale) {
            $payload = $request->input("about.$locale", []);
            $data[$locale] = array_merge($data[$locale] ?? [], [
                'title' => $payload['title'] ?? null,
                'desc' => $payload['desc'] ?? null,
                'readmore_text' => $payload['readmore_text'] ?? null,
                'readmore_link' => $payload['readmore_link'] ?? null,
            ]);
        }

        $section->section_data = $data;
        $section->save();
    }

    protected function syncStatsItems(Request $request, $section): void
    {
        $inputItems = $request->input('stats.items', []);
        $existingCollection = $section->items;
        $existingItems = $existingCollection->keyBy('id');
        $existingData = $this->getItemsTranslations($section);
        $persistedIds = [];

        foreach ($inputItems as $index => $payload) {
            $itemId = isset($payload['id']) ? (int) $payload['id'] : null;
            $item = $itemId ? $existingItems->get($itemId) : new SectionItem(['section_id' => $section->id]);
            $currentTranslations = $itemId && isset($existingData[$itemId])
                ? $existingData[$itemId]
                : array_fill_keys($this->locales, []);

            $existingIcon = $payload['existing_icon'] ?? ($currentTranslations[$this->primaryLocale]['icon_path'] ?? null);
            $iconPath = $this->upload($request, "stats.items.$index.icon", 'cms/home/stats', $existingIcon);

            if (!$itemId && !$iconPath) {
                continue;
            }

            $itemData = [];
            foreach ($this->locales as $locale) {
                $itemData[$locale] = array_merge($currentTranslations[$locale] ?? [], [
                    'icon_path' => $iconPath,
                    'number' => $payload['number'][$locale] ?? null,
                    'label' => $payload['label'][$locale] ?? null,
                ]);
            }

            $item->section_id = $section->id;
            $item->order = (int) ($payload['order'] ?? 0);
            $item->data = $itemData;
            $item->save();

            $persistedIds[] = $item->id;
        }

        if (!empty($persistedIds)) {
            $section->items()->whereNotIn('id', $persistedIds)->delete();
        } elseif ($existingCollection->isNotEmpty() && empty($inputItems)) {
            $section->items()->delete();
        }
    }

    protected function updateWhereUsSection(Request $request, $section): void
    {
        $data = $this->getSectionTranslations($section);

        $existingIcon = $data[$this->primaryLocale]['brochure']['icon_path'] ?? null;
        $iconPath = $this->upload($request, 'where_us.brochure_icon', 'cms/home/where-us', $existingIcon);

        $existingBrochure = $data[$this->primaryLocale]['brochure']['brochure_path'] ?? null;
        $brochurePath = $this->upload($request, 'where_us.brochure_file', 'cms/home/where-us', $existingBrochure);
        $brochureLink = $request->input('where_us.brochure_link');

        foreach ($this->locales as $locale) {
            $title = $request->input("where_us.title.$locale");
            $brochureText = $request->input("where_us.brochure_text.$locale");

            $current = $data[$locale] ?? [];
            $brochure = $current['brochure'] ?? [];

            $brochure['text'] = $brochureText;
            $brochure['icon_path'] = $iconPath;
            $brochure['brochure_path'] = $brochurePath ?: ($brochureLink ?: ($brochure['brochure_path'] ?? null));

            $data[$locale] = array_merge($current, [
                'title' => $title,
                'brochure' => $brochure,
            ]);
        }

        $section->section_data = $data;
        $section->save();

        $this->syncWhereUsItems($request, $section);
    }

    protected function syncWhereUsItems(Request $request, $section): void
    {
        $inputItems = $request->input('where_us.items', []);
        $existingCollection = $section->items;
        $existingItems = $existingCollection->keyBy('id');
        $existingData = $this->getItemsTranslations($section);
        $persistedIds = [];

        foreach ($inputItems as $index => $payload) {
            $itemId = isset($payload['id']) ? (int) $payload['id'] : null;
            $item = $itemId ? $existingItems->get($itemId) : new SectionItem(['section_id' => $section->id]);
            $currentTranslations = $itemId && isset($existingData[$itemId])
                ? $existingData[$itemId]
                : array_fill_keys($this->locales, []);

            $existingImage = $payload['existing_image'] ?? ($currentTranslations[$this->primaryLocale]['image_path'] ?? null);
            $imagePath = $this->upload($request, "where_us.items.$index.image", 'cms/home/where-us', $existingImage);

            if (!$itemId && !$imagePath) {
                continue;
            }

            $itemData = [];

            foreach ($this->locales as $locale) {
                $itemData[$locale] = array_merge($currentTranslations[$locale] ?? [], [
                    'image_path' => $imagePath,
                    'overlay_text' => $payload['overlay'][$locale] ?? null,
                ]);
            }

            $item->section_id = $section->id;
            $item->order = (int) ($payload['order'] ?? 0);
            $item->data = $itemData;
            $item->save();

            $persistedIds[] = $item->id;
        }

        if (!empty($persistedIds)) {
            $section->items()->whereNotIn('id', $persistedIds)->delete();
        } elseif ($existingCollection->isNotEmpty() && empty($inputItems)) {
            $section->items()->delete();
        }
    }

    protected function updateCtaSection(Request $request, $section): void
    {
        $data = $this->getSectionTranslations($section);

        $existingImage = $data[$this->primaryLocale]['image_path'] ?? null;
        $existingOverlay = $data[$this->primaryLocale]['overlay_image_path'] ?? null;

        $imagePath = $this->upload($request, 'cta.image', 'cms/home/cta', $existingImage);
        $overlayPath = $this->upload($request, 'cta.overlay_image', 'cms/home/cta', $existingOverlay);

        foreach ($this->locales as $locale) {
            $payload = $request->input("cta.$locale", []);
            $data[$locale] = array_merge($data[$locale] ?? [], [
                'title' => $payload['title'] ?? null,
                'text' => $payload['text'] ?? null,
                'link_text' => $payload['link_text'] ?? null,
                'link_url' => $payload['link_url'] ?? null,
                'image_path' => $imagePath,
                'overlay_image_path' => $overlayPath,
            ]);
        }

        $section->section_data = $data;
        $section->save();
    }
}
