<?php

namespace App\Observers;

use App\Models\PageSection;
use Illuminate\Support\Facades\Cache;

class PageSectionObserver
{
    public function saved(PageSection $section): void
    {
        Cache::forget('page.home');
    }

    public function deleted(PageSection $section): void
    {
        Cache::forget('page.home');
    }
}

