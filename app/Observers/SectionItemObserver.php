<?php

namespace App\Observers;

use App\Models\SectionItem;
use Illuminate\Support\Facades\Cache;

class SectionItemObserver
{
    public function saved(SectionItem $item): void
    {
        $slug = optional($item->section()->with('page')->first())->page->slug ?? null;
        if ($slug) {
            Cache::forget('page.' . $slug);
        }
    }

    public function deleted(SectionItem $item): void
    {
        $slug = optional($item->section()->with('page')->first())->page->slug ?? null;
        if ($slug) {
            Cache::forget('page.' . $slug);
        }
    }
}
