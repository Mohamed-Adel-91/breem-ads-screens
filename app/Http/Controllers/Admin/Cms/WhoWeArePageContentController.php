<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Models\SectionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WhoWeArePageContentController extends BasePageContentController
{
    public function edit(string $lang)
    {
        $page = $this->loadPage('whoweare');
        $sections = $page->sections->keyBy('type');

        $banner = $sections->get('second_banner');
        $whoWe = $sections->get('who_we');
        $port = $sections->get('port_image');

        return view('admin.cms.whoweare.edit', [
            'page' => $page,
            'locales' => $this->locales,
            'banner' => $banner,
            'bannerData' => $this->getSectionTranslations($banner),
            'whoWe' => $whoWe,
            'whoWeData' => $this->getSectionTranslations($whoWe),
            'whoWeItems' => $this->getItemsTranslations($whoWe),
            'port' => $port,
            'portData' => $this->getSectionTranslations($port),
        ]);
    }

    public function update(string $lang, Request $request)
    {
        $page = $this->loadPage('whoweare');
        $sections = $page->sections->keyBy('type');

        $banner = $sections->get('second_banner');
        $whoWe = $sections->get('who_we');
        $port = $sections->get('port_image');

        $rules = [
            'banner.image' => ['nullable', 'file', 'image', 'max:15360'],
            'who_we.items' => ['nullable', 'array'],
            'who_we.items.*.id' => ['nullable', 'integer'],
            'who_we.items.*.order' => ['nullable', 'integer', 'min:0'],
            'port.image' => ['nullable', 'file', 'image', 'max:15360'],
        ];

        foreach ($this->locales as $locale) {
            $rules["who_we.$locale.title"] = ['nullable', 'string', 'max:255'];
            $rules["who_we.$locale.description"] = ['nullable', 'string'];

            $rules["who_we.items.*.title.$locale"] = ['nullable', 'string', 'max:255'];
            $rules["who_we.items.*.text.$locale"] = ['nullable', 'string'];
            $rules["who_we.items.*.bullets.$locale"] = ['nullable', 'string'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($request, $banner, $whoWe, $port) {
            if ($banner) {
                $this->updateBanner($request, $banner);
            }

            if ($whoWe) {
                $this->updateWhoWeSection($request, $whoWe);
            }

            if ($port) {
                $this->updatePortSection($request, $port);
            }
        });

        Cache::forget('page.whoweare');

        return redirect()
            ->route('admin.cms.whoweare.edit', ['lang' => $lang])
            ->with('success', 'تم تحديث محتوى صفحة من نحن بنجاح.');
    }

    protected function updateBanner(Request $request, $section): void
    {
        $data = $this->getSectionTranslations($section);
        $existingImage = $data[$this->primaryLocale]['image_path'] ?? null;

        $imagePath = $this->upload($request, 'banner.image', 'cms/whoweare/banner', $existingImage);

        foreach ($this->locales as $locale) {
            $data[$locale] = array_merge($data[$locale] ?? [], [
                'image_path' => $imagePath,
            ]);
        }

        $section->section_data = $data;
        $section->save();
    }

    protected function updateWhoWeSection(Request $request, $section): void
    {
        $data = $this->getSectionTranslations($section);

        foreach ($this->locales as $locale) {
            $payload = $request->input("who_we.$locale", []);
            $data[$locale] = array_merge($data[$locale] ?? [], [
                'title' => $payload['title'] ?? null,
                'description' => $payload['description'] ?? null,
            ]);
        }

        $section->section_data = $data;
        $section->save();

        $this->syncWhoWeItems($request, $section);
    }

    protected function syncWhoWeItems(Request $request, $section): void
    {
        $inputItems = $request->input('who_we.items', []);
        $existingCollection = $section->items;
        $existingItems = $existingCollection->keyBy('id');
        $existingData = $this->getItemsTranslations($section);
        $persistedIds = [];

        foreach ($inputItems as $payload) {
            $itemId = isset($payload['id']) ? (int) $payload['id'] : null;
            $item = $itemId ? $existingItems->get($itemId) : new SectionItem(['section_id' => $section->id]);
            $currentTranslations = $itemId && isset($existingData[$itemId])
                ? $existingData[$itemId]
                : array_fill_keys($this->locales, []);

            $itemData = [];

            foreach ($this->locales as $locale) {
                $bulletsRaw = $payload['bullets'][$locale] ?? '';
                $lines = preg_split("/(\r\n|\n|\r)/", (string) $bulletsRaw);
                $bullets = array_values(array_filter(array_map('trim', $lines), fn($line) => $line !== ''));

                $itemData[$locale] = array_merge($currentTranslations[$locale] ?? [], [
                    'title' => $payload['title'][$locale] ?? null,
                    'text' => $payload['text'][$locale] ?? null,
                    'bullets' => $bullets,
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

    protected function updatePortSection(Request $request, $section): void
    {
        $data = $this->getSectionTranslations($section);
        $existingImage = $data[$this->primaryLocale]['image_path'] ?? null;

        $imagePath = $this->upload($request, 'port.image', 'cms/whoweare/port', $existingImage);

        foreach ($this->locales as $locale) {
            $data[$locale] = array_merge($data[$locale] ?? [], [
                'image_path' => $imagePath,
            ]);
        }

        $section->section_data = $data;
        $section->save();
    }
}
