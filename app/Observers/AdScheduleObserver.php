<?php

namespace App\Observers;

use App\Models\AdSchedule;
use App\Services\Screen\AdSchedulerService;

class AdScheduleObserver
{
    public function __construct(
        protected AdSchedulerService $scheduler
    ) {
    }

    public function saved(AdSchedule $schedule): void
    {
        $this->flushScreens($schedule);
    }

    public function deleted(AdSchedule $schedule): void
    {
        $this->flushScreens($schedule);
    }

    protected function flushScreens(AdSchedule $schedule): void
    {
        $screenIds = array_filter([
            $schedule->screen_id,
            $schedule->getOriginal('screen_id'),
        ]);

        if (!empty($screenIds)) {
            $this->scheduler->forgetMany($screenIds);
        }
    }
}
