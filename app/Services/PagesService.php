<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PagesService
{
    public function home()
    {
        $data = Cache::rememberForever('page.home', function () {
            $page = Page::where('slug', 'home')
                ->with([
                    'sections' => function ($query) {
                        $query->where('is_active', true)
                            ->orderBy('order')
                            ->with([
                                'items' => function ($query) {
                                    $query->orderBy('order');
                                },
                            ]);
                    },
                ])
                ->firstOrFail();

            $disabledSections = [];
            $sections = $page->sections->map(function ($section) use (&$disabledSections) {
                $totalItems = $section->items->count();
                $items = $section->items
                    ->filter(fn($item) => $item->is_active ?? true)
                    ->sortBy('order')
                    ->values();
                if ($items->count() === 0 && $totalItems > 0) {
                    $disabledSections[] = [
                        'section_id' => $section->id,
                        'type' => $section->type ?? null,
                        'total_items' => $totalItems,
                    ];
                }
                $section->setRelation('items', $items);
                return $section;
            });

            if (!empty($disabledSections)) {
                Log::info('Home page sections have no active items', [
                    'page_id' => $page->id,
                    'slug' => $page->slug,
                    'sections' => $disabledSections,
                ]);
            }

            // Collect some metrics to help with later decisions/logging
            $totalSections = $page->sections()->count();
            $disabledCount = $page->sections()->where('is_active', false)->count();

            return [
                'page' => $page,
                'sections' => $sections,
                'metrics' => [
                    'active_sections' => $sections->count(),
                    'total_sections' => $totalSections,
                    'disabled_sections' => $disabledCount,
                ],
            ];
        });

        if ($data['sections']->isEmpty()) {
            Log::warning('Home page has no active sections', $data['metrics'] ?? []);
            return response()->view('404', [], 404);
        }

        return view('web.pages.index', [
            'page' => $data['page'],
            'sections' => $data['sections'],
        ]);
    }

    public function whoweare()
    {
        return view('web.pages.whoweare');
    }

    public function contactUs()
    {
        return view('web.pages.contact_us');
    }
}
