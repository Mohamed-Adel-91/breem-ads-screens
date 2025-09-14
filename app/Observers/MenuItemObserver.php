<?php

namespace App\Observers;

use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

class MenuItemObserver
{
    public function saved(MenuItem $item): void
    {
        Cache::forget('menu.header');
        Cache::forget('menu.footer');
    }

    public function deleted(MenuItem $item): void
    {
        Cache::forget('menu.header');
        Cache::forget('menu.footer');
    }
}

