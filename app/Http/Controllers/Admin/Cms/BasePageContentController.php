<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

abstract class BasePageContentController extends Controller
{
    protected FileServiceInterface $fileService;

    /**
     * @var string[]
     */
    protected array $locales;

    protected string $primaryLocale;

    protected string $fallbackLocale;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
        $default = config('app.locale', 'ar');
        $fallback = config('app.fallback_locale', 'en');

        $this->primaryLocale = $default;
        $this->fallbackLocale = $fallback;

        $locales = array_filter(array_unique([
            $default,
            $fallback,
            'ar',
            'en',
        ]));

        $this->locales = array_values($locales);
    }

    protected function loadPage(string $slug): Page
    {
        return Page::whereSlug($slug)
            ->with(['sections' => function ($query) {
                $query->orderBy('order')->with(['items' => function ($items) {
                    $items->orderBy('order');
                }]);
            }])
            ->firstOrFail();
    }

    protected function sectionByType(Collection $sections, string $type): ?PageSection
    {
        return $sections->firstWhere('type', $type);
    }

    protected function getSectionTranslations(?PageSection $section): array
    {
        $translations = [];

        foreach ($this->locales as $locale) {
            $value = $section?->getTranslation('section_data', $locale, true);

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }

            $translations[$locale] = is_array($value) ? $value : [];
        }

        return $translations;
    }

    protected function getItemsTranslations(?PageSection $section): array
    {
        if (!$section) {
            return [];
        }

        $items = [];

        foreach ($section->items as $item) {
            $translations = [];

            foreach ($this->locales as $locale) {
                $value = $item->getTranslation('data', $locale, true);

                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                }

                $translations[$locale] = is_array($value) ? $value : [];
            }

            $items[$item->id] = $translations;
        }

        return $items;
    }

    protected function upload(Request $request, string $field, string $folder, ?string $existing = null): ?string
    {
        return $this->fileService->uploadSingle($request, $field, $folder, $existing);
    }
}
