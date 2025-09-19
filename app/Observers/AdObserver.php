<?php

namespace App\Observers;

use App\Models\Ad;
use App\Services\Screen\AdSchedulerService;

class AdObserver
{
    public function __construct(
        protected AdSchedulerService $scheduler
    ) {
    }

    public function saved(Ad $ad): void
    {
        $this->flushScreens($ad);
    }

    public function deleted(Ad $ad): void
    {
        $this->flushScreens($ad);
    }

    protected function flushScreens(Ad $ad): void
    {
        $screenIds = $ad->screens()->pluck('screens.id')->all();

        if (!empty($screenIds)) {
            $this->scheduler->forgetMany($screenIds);
        }
    }
}
