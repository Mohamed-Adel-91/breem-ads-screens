<?php

namespace App\Observers;

use App\Models\SectionItem;
use Illuminate\Support\Facades\Cache;

class SectionItemObserver
{
    public function saved(SectionItem $item): void
    {
        Cache::forget('page.home');
    }

    public function deleted(SectionItem $item): void
    {
        Cache::forget('page.home');
    }
}

