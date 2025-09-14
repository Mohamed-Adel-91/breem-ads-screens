<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    public function edit(string $lang = null, string $slug = '')
    {
        $page = Page::where('slug', $slug)
            ->with(['sections' => function ($q) {
                $q->orderBy('order')->with(['items' => function ($iq) {
                    $iq->orderBy('order');
                }]);
            }])
            ->firstOrFail();

        // Bust per-page cache so admin always sees latest state
        try { Cache::forget('page.' . $slug); } catch (\Throwable $e) {}

        return view('admin.cms.pages.edit', compact('page'));
    }
}
