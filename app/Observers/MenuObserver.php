<?php

namespace App\Observers;

use App\Models\Menu;
use Illuminate\Support\Facades\Cache;

class MenuObserver
{
    public function saved(Menu $menu): void
    {
        // Any menu change may affect header/footer rendering
        Cache::forget('menu.header');
        Cache::forget('menu.footer');
    }

    public function deleted(Menu $menu): void
    {
        Cache::forget('menu.header');
        Cache::forget('menu.footer');
    }
}

