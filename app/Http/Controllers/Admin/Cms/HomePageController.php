<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\HomePageUpdateRequest;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomePageController extends Controller
{
    public function __construct(private FileServiceInterface $fileService)
    {
    }

    public function edit(string $lang = null)
    {
        $page = $this->loadPage();
        $sections = $page->sections->keyBy('type');

        return view('admin.cms.home.edit', compact('page', 'sections'));
    }

    public function update(HomePageUpdateRequest $request, string $lang = null)
    {
        $page = $this->loadPage();

        DB::transaction(function () use ($request, $page) {
            $sections = $page->sections->keyBy('type');

            if ($banner = $sections->get('banner')) {
                $this->updateBanner($request, $banner);
            }

            if ($partners = $sections->get('partners')) {
                $this->updatePartners($request, $partners);
            }

            if ($about = $sections->get('about')) {
                $this->updateAbout($request, $about);
            }

            if ($stats = $sections->get('stats')) {
                $this->updateStats($request, $stats);
            }

            if ($where = $sections->get('where_us')) {
                $this->updateWhereUs($request, $where);
            }

            if ($cta = $sections->get('cta')) {
                $this->updateCta($request, $cta);
            }
        });

        try {
            Cache::forget('page.home');
        } catch (\Throwable $e) {
        }

        return redirect()->back()->with('success', __('admin.cms.saved'));
    }

    private function loadPage(): Page
    {
        return Page::where('slug', 'home')
            ->with(['sections' => function ($query) {
                $query->with(['items' => function ($itemQuery) {
                    $itemQuery->orderBy('order');
                }])->orderBy('order');
            }])
            ->firstOrFail();
    }

    private function updateBanner(HomePageUpdateRequest $request, PageSection $section): void
    {
        $data = $section->section_data ?? [];
        $currentVideo = data_get($data, 'en.video_path');
        $videoPath = $this->fileService->uploadCmsFile($request, 'banner_video', 'upload/cms/home/banner', $currentVideo);

        $flags = [
            'autoplay' => $request->boolean('banner_autoplay'),
            'loop' => $request->boolean('banner_loop'),
            'muted' => $request->boolean('banner_muted'),
            'controls' => $request->boolean('banner_controls'),
            'playsinline' => $request->boolean('banner_playsinline'),
        ];

        foreach (['ar', 'en'] as $locale) {
            if ($videoPath !== null) {
                $data[$locale]['video_path'] = $videoPath;
            }

            foreach ($flags as $key => $value) {
                $data[$locale][$key] = $value;
            }
        }

        $section->section_data = $data;
        $section->save();
    }

    private function updatePartners(HomePageUpdateRequest $request, PageSection $section): void
    {
        $input = $request->input('partners', []);

        foreach ($section->items as $item) {
            $itemData = $item->data ?? [];
            $itemInput = $input[$item->id] ?? [];

            $imagePath = $this->fileService->uploadCmsFile(
                $request,
                'partners.' . $item->id . '.image',
                'upload/cms/home/partners',
                data_get($itemData, 'en.image_path')
            );

            if ($imagePath !== null) {
                $itemData['ar']['image_path'] = $imagePath;
                $itemData['en']['image_path'] = $imagePath;
            }

            $itemData['ar']['alt'] = $itemInput['alt_ar'] ?? data_get($itemData, 'ar.alt');
            $itemData['en']['alt'] = $itemInput['alt_en'] ?? data_get($itemData, 'en.alt');

            if (isset($itemInput['order']) && $itemInput['order'] !== null) {
                $item->order = (int) $itemInput['order'];
            }

            $item->data = $itemData;
            $item->save();
        }
    }

    private function updateAbout(HomePageUpdateRequest $request, PageSection $section): void
    {
        $link = $request->input('about_readmore_link');

        $section->section_data = [
            'ar' => [
                'title' => $request->input('about_title_ar'),
                'desc' => $request->input('about_desc_ar'),
                'readmore_text' => $request->input('about_readmore_text_ar'),
                'readmore_link' => $link,
            ],
            'en' => [
                'title' => $request->input('about_title_en'),
                'desc' => $request->input('about_desc_en'),
                'readmore_text' => $request->input('about_readmore_text_en'),
                'readmore_link' => $link,
            ],
        ];

        $section->save();
    }

    private function updateStats(HomePageUpdateRequest $request, PageSection $section): void
    {
        $input = $request->input('stats', []);

        foreach ($section->items as $item) {
            $itemData = $item->data ?? [];
            $itemInput = $input[$item->id] ?? [];

            $iconPath = $this->fileService->uploadCmsFile(
                $request,
                'stats.' . $item->id . '.icon',
                'upload/cms/home/stats',
                data_get($itemData, 'en.icon_path')
            );

            if ($iconPath !== null) {
                $itemData['ar']['icon_path'] = $iconPath;
                $itemData['en']['icon_path'] = $iconPath;
            }

            $itemData['ar']['number'] = $itemInput['number_ar'] ?? data_get($itemData, 'ar.number');
            $itemData['en']['number'] = $itemInput['number_en'] ?? data_get($itemData, 'en.number');
            $itemData['ar']['label'] = $itemInput['label_ar'] ?? data_get($itemData, 'ar.label');
            $itemData['en']['label'] = $itemInput['label_en'] ?? data_get($itemData, 'en.label');

            $item->data = $itemData;
            $item->save();
        }
    }

    private function updateWhereUs(HomePageUpdateRequest $request, PageSection $section): void
    {
        $data = $section->section_data ?? [];

        $iconPath = $this->fileService->uploadCmsFile(
            $request,
            'where_brochure_icon',
            'upload/cms/home/where',
            data_get($data, 'en.brochure.icon_path')
        );

        $brochurePath = $this->fileService->uploadCmsFile(
            $request,
            'where_brochure_file',
            'upload/cms/home/where',
            data_get($data, 'en.brochure.brochure_path')
        );

        $linkInput = $request->input('where_brochure_link');

        foreach (['ar', 'en'] as $locale) {
            $data[$locale]['title'] = $request->input('where_title_' . $locale);
            $data[$locale]['brochure']['text'] = $request->input('where_brochure_text_' . $locale);

            if ($iconPath !== null) {
                $data[$locale]['brochure']['icon_path'] = $iconPath;
            }

            if ($brochurePath !== null) {
                $data[$locale]['brochure']['brochure_path'] = $brochurePath;
            } elseif ($linkInput !== null) {
                $data[$locale]['brochure']['brochure_path'] = $linkInput;
            }
        }

        $section->section_data = $data;
        $section->save();

        $itemsInput = $request->input('where_items', []);

        foreach ($section->items as $item) {
            $itemData = $item->data ?? [];
            $itemInput = $itemsInput[$item->id] ?? [];

            $imagePath = $this->fileService->uploadCmsFile(
                $request,
                'where_items.' . $item->id . '.image',
                'upload/cms/home/where',
                data_get($itemData, 'en.image_path')
            );

            if ($imagePath !== null) {
                $itemData['ar']['image_path'] = $imagePath;
                $itemData['en']['image_path'] = $imagePath;
            }

            $itemData['ar']['overlay_text'] = $itemInput['overlay_ar'] ?? data_get($itemData, 'ar.overlay_text');
            $itemData['en']['overlay_text'] = $itemInput['overlay_en'] ?? data_get($itemData, 'en.overlay_text');

            if (isset($itemInput['order']) && $itemInput['order'] !== null) {
                $item->order = (int) $itemInput['order'];
            }

            $item->data = $itemData;
            $item->save();
        }
    }

    private function updateCta(HomePageUpdateRequest $request, PageSection $section): void
    {
        $data = $section->section_data ?? [];

        $imagePath = $this->fileService->uploadCmsFile(
            $request,
            'cta_image',
            'upload/cms/home/cta',
            data_get($data, 'en.image_path')
        );

        $overlayPath = $this->fileService->uploadCmsFile(
            $request,
            'cta_overlay_image',
            'upload/cms/home/cta',
            data_get($data, 'en.overlay_image_path')
        );

        foreach (['ar', 'en'] as $locale) {
            $data[$locale]['title'] = $request->input('cta_title_' . $locale);
            $data[$locale]['text'] = $request->input('cta_text_' . $locale);
            $data[$locale]['link_text'] = $request->input('cta_link_text_' . $locale);
            $data[$locale]['link_url'] = $request->input('cta_link_url');

            if ($imagePath !== null) {
                $data[$locale]['image_path'] = $imagePath;
            }

            if ($overlayPath !== null) {
                $data[$locale]['overlay_image_path'] = $overlayPath;
            }
        }

        $section->section_data = $data;
        $section->save();
    }
}

