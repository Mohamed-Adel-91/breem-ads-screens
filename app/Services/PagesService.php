<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PagesService
{
    public function home()
    {
        $data = $this->loadPage('home');

        if ($data === null) {
            return $this->notFound();
        }

        if ($data['sections']->isEmpty()) {
            Log::warning('Home page has no active sections', $data['metrics'] ?? []);

            return $this->notFound();
        }

        return view('web.pages.index', [
            'page' => $data['page'],
            'sections' => $data['sections'],
        ]);
    }

    public function whoweare()
    {
        $data = $this->loadPage('whoweare');

        if ($data === null) {
            return $this->notFound();
        }

        if ($data['sections']->isEmpty()) {
            Log::warning('WhoWeAre page has no active sections', $data['metrics'] ?? []);

            return $this->notFound();
        }

        return view('web.pages.whoweare', [
            'page' => $data['page'],
            'sections' => $data['sections'],
        ]);
    }

    public function contactUs()
    {
        $data = $this->loadPage('contact-us');

        if ($data === null || $data['sections']->isEmpty()) {
            return $this->notFound();
        }

        return view('web.pages.contact_us', [
            'page' => $data['page'],
            'sections' => $data['sections'],
        ]);
    }

    /**
     * Load a published page with its active sections and active items.
     *
     * Activation is filtered in the query for pages, sections and items alike,
     * so an inactive record never reaches the view. Returns null when the page
     * does not exist or is not active.
     */
    protected function loadPage(string $slug): ?array
    {
        $cacheKey = 'page.' . $slug;

        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }

        try {
            $page = Page::where('slug', $slug)
                ->where('is_active', true)
                ->with([
                    'sections' => function ($query) {
                        $query->where('is_active', true)
                            ->orderBy('order')
                            // items_count counts every item, while the loaded
                            // relation holds only the active ones.
                            ->withCount('items')
                            ->with([
                                'items' => function ($query) {
                                    $query->where('is_active', true)->orderBy('order');
                                },
                            ]);
                    },
                ])
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            // Cache the miss so an unpublished page does not hit the database on
            // every request. The page observers clear this key on any change.
            Cache::forever($cacheKey, false);

            return null;
        }

        $sections = $page->sections;

        $emptied = $sections
            ->filter(fn ($section) => $section->items->isEmpty() && $section->items_count > 0)
            ->map(fn ($section) => [
                'section_id' => $section->id,
                'type' => $section->type ?? null,
                'total_items' => $section->items_count,
            ])
            ->values()
            ->all();

        if (!empty($emptied)) {
            Log::info('Page sections have no active items', [
                'page_id' => $page->id,
                'slug' => $page->slug,
                'sections' => $emptied,
            ]);
        }

        $data = [
            'page' => $page,
            'sections' => $sections,
            'metrics' => [
                'active_sections' => $sections->count(),
                'total_sections' => $page->sections()->count(),
                'disabled_sections' => $page->sections()->where('is_active', false)->count(),
            ],
        ];

        Cache::forever($cacheKey, $data);

        return $data;
    }

    protected function notFound()
    {
        return response()->view('404', [], 404);
    }
}
