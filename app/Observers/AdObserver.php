<?php

namespace App\Observers;

use App\Models\Ad;
use App\Services\Screen\AdSchedulerService;

/**
 * Keeps cached device playlists in step with advertisement changes.
 *
 * Observers are resolved from the container per event, so a fresh instance
 * handles `deleting` and another handles `deleted`. Any state written to `$this`
 * in the first is gone by the second — which is why deleting an ad used to leave
 * it playing until the playlist TTL expired. The affected screen ids are
 * therefore materialised onto the *model* while the pivot rows still exist; the
 * model instance is the one thing both callbacks share.
 */
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

    public function deleting(Ad $ad): void
    {
        // ad_screen rows cascade away with the ad, so read them now.
        $ad->loadMissing('screens');
    }

    public function deleted(Ad $ad): void
    {
        $this->flushScreens($ad, $this->capturedScreenIds($ad));
    }

    /**
     * Screen ids captured before deletion, or an empty list when the relation
     * was never materialised (for example a caller that detached first and
     * flushes the cache itself).
     *
     * @return array<int, int>
     */
    protected function capturedScreenIds(Ad $ad): array
    {
        if (! $ad->relationLoaded('screens')) {
            return [];
        }

        return $ad->getRelation('screens')->pluck('id')->all();
    }

    /**
     * @param  array<int, int|string>|null  $screenIds
     */
    protected function flushScreens(Ad $ad, ?array $screenIds = null): void
    {
        $screenIds ??= $ad->screens()->pluck('screens.id')->all();

        $screenIds = collect($screenIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! empty($screenIds)) {
            $this->scheduler->forgetMany($screenIds);
        }
    }
}
