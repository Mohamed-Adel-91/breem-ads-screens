<?php

namespace App\Observers;

use App\Models\PageSection;
use Illuminate\Support\Facades\Cache;

class PageSectionObserver
{
    public function saved(PageSection $section): void
    {
        $slug = $section->page()->value('slug');
        if ($slug) {
            Cache::forget('page.' . $slug);
        }
    }

    public function deleted(PageSection $section): void
    {
        $slug = $section->page()->value('slug');
        if ($slug) {
            Cache::forget('page.' . $slug);
        }
    }
}
