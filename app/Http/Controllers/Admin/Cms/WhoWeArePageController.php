<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\WhoWeArePageUpdateRequest;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WhoWeArePageController extends Controller
{
    public function __construct(private FileServiceInterface $fileService)
    {
    }

    public function edit(string $lang = null)
    {
        $page = $this->loadPage();
        $sections = $page->sections->keyBy('type');

        return view('admin.cms.who_we_are.edit', compact('page', 'sections'));
    }

    public function update(WhoWeArePageUpdateRequest $request, string $lang = null)
    {
        $page = $this->loadPage();

        DB::transaction(function () use ($request, $page) {
            $sections = $page->sections->keyBy('type');

            if ($banner = $sections->get('second_banner')) {
                $this->updateBanner($request, $banner);
            }

            if ($who = $sections->get('who_we')) {
                $this->updateWhoSection($request, $who);
            }

            if ($port = $sections->get('port_image')) {
                $this->updatePortImage($request, $port);
            }
        });

        try {
            Cache::forget('page.whoweare');
        } catch (\Throwable $e) {
        }

        return redirect()->back()->with('success', __('admin.cms.saved'));
    }

    private function loadPage(): Page
    {
        return Page::where('slug', 'whoweare')
            ->with(['sections' => function ($query) {
                $query->with(['items' => function ($itemQuery) {
                    $itemQuery->orderBy('order');
                }])->orderBy('order');
            }])
            ->firstOrFail();
    }

    private function updateBanner(WhoWeArePageUpdateRequest $request, PageSection $section): void
    {
        $data = $section->section_data ?? [];
        $imagePath = $this->fileService->uploadCmsFile(
            $request,
            'banner_image',
            'upload/cms/who/banner',
            data_get($data, 'en.image_path')
        );

        if ($imagePath !== null) {
            foreach (['ar', 'en'] as $locale) {
                $data[$locale]['image_path'] = $imagePath;
            }
        }

        $section->section_data = $data;
        $section->save();
    }

    private function updateWhoSection(WhoWeArePageUpdateRequest $request, PageSection $section): void
    {
        $data = $section->section_data ?? [];

        $data['ar']['title'] = $request->input('who_title_ar');
        $data['ar']['description'] = $request->input('who_description_ar');
        $data['en']['title'] = $request->input('who_title_en');
        $data['en']['description'] = $request->input('who_description_en');

        $section->section_data = $data;
        $section->save();

        $featuresInput = $request->input('features', []);

        foreach ($section->items as $item) {
            $itemData = $item->data ?? [];
            $itemInput = $featuresInput[$item->id] ?? [];

            $itemData['ar']['title'] = $itemInput['title_ar'] ?? data_get($itemData, 'ar.title');
            $itemData['en']['title'] = $itemInput['title_en'] ?? data_get($itemData, 'en.title');
            $itemData['ar']['text'] = $itemInput['text_ar'] ?? data_get($itemData, 'ar.text');
            $itemData['en']['text'] = $itemInput['text_en'] ?? data_get($itemData, 'en.text');
            $itemData['ar']['bullets'] = $this->explodeLines($itemInput['bullets_ar'] ?? null, data_get($itemData, 'ar.bullets', []));
            $itemData['en']['bullets'] = $this->explodeLines($itemInput['bullets_en'] ?? null, data_get($itemData, 'en.bullets', []));

            $item->data = $itemData;
            $item->save();
        }
    }

    private function updatePortImage(WhoWeArePageUpdateRequest $request, PageSection $section): void
    {
        $data = $section->section_data ?? [];
        $imagePath = $this->fileService->uploadCmsFile(
            $request,
            'port_image',
            'upload/cms/who/port',
            data_get($data, 'en.image_path')
        );

        if ($imagePath !== null) {
            foreach (['ar', 'en'] as $locale) {
                $data[$locale]['image_path'] = $imagePath;
            }
        }

        $section->section_data = $data;
        $section->save();
    }

    private function explodeLines(?string $value, array $fallback = []): array
    {
        if ($value === null) {
            return $fallback;
        }

        $lines = preg_split("/(\r\n|\r|\n)/", $value) ?: [];
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, fn ($line) => $line !== '');

        return array_values($lines);
    }
}

