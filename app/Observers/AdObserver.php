<?php

namespace App\Observers;

use App\Models\Ad;
use App\Services\Screen\AdSchedulerService;

class AdObserver
{
    /**
     * Cached screen identifiers captured prior to deleting the ad.
     *
     * @var array<int, array<int, int>>
     */
    protected array $originalScreenIds = [];

    public function __construct(
        protected AdSchedulerService $scheduler
    ) {
    }

    public function saved(Ad $ad): void
    {
        $this->flushScreens($ad);
    }

    public function deleting(Ad $ad): void
    {
        $this->originalScreenIds[$this->objectKey($ad)] = $ad->screens()->pluck('screens.id')->all();
    }

    public function deleted(Ad $ad): void
    {
        $screenIds = $this->originalScreenIds[$this->objectKey($ad)] ?? [];

        unset($this->originalScreenIds[$this->objectKey($ad)]);

        $this->flushScreens($ad, $screenIds);
    }

    protected function flushScreens(Ad $ad, ?array $screenIds = null): void
    {
        $screenIds ??= $ad->screens()->pluck('screens.id')->all();

        $screenIds = collect($screenIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($screenIds)) {
            $this->scheduler->forgetMany($screenIds);
        }
    }

    protected function objectKey(Ad $ad): int
    {
        return spl_object_id($ad);
    }
}
