<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    /**
     * Low-level editor for a page's sections, section data and items.
     *
     * The three curated editors (home / whoweare / contact-us) remain the
     * normal way to edit content; this screen exists for maintenance of the
     * underlying records.
     */
    public function edit(?string $lang, Page $page)
    {
        $page->load(['sections' => function ($query) {
            $query->orderBy('order')->with(['items' => function ($items) {
                $items->orderBy('order');
            }]);
        }]);

        // Bust the per-page cache so the admin always sees the latest state.
        Cache::forget('page.' . $page->slug);

        return view('admin.cms.pages.edit', compact('page'));
    }
}
