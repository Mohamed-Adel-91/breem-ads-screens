<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;

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

            $sections = $page->sections->map(function ($section) {
                $items = $section->items
                    ->filter(fn($item) => $item->is_active ?? true)
                    ->sortBy('order')
                    ->values();
                $section->setRelation('items', $items);
                return $section;
            });

            return compact('page', 'sections');
        });

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
