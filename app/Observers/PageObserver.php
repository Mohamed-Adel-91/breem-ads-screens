<?php

namespace App\Observers;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;

class PageObserver
{
    public function saved(Page $page): void
    {
        // For now, clear home page cache if any page changes might affect menus/sections visibility
        Cache::forget('page.home');
    }

    public function deleted(Page $page): void
    {
        Cache::forget('page.home');
    }
}

