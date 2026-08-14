<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\LayoutService;

class SettingObserver
{
    public function saved(Setting $setting): void
    {
        $this->flush();
    }

    public function deleted(Setting $setting): void
    {
        $this->flush();
    }

    /**
     * Drop the layout cache for every locale, not just the current one.
     *
     * The key set belongs to App\Services\LayoutService — it is the class that builds
     * those keys, so it is also the class that knows how to enumerate them. Resolving
     * the service here rather than holding a constructor dependency keeps the observer
     * registration in AppServiceProvider unchanged.
     */
    private function flush(): void
    {
        app(LayoutService::class)->flushSettingsCache();
    }
}
