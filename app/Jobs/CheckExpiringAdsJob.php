<?php

namespace App\Jobs;

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Notifications\AdExpiringNotification;
use App\Services\Screen\AdSchedulerService;
use App\Support\AdValidity;
use App\Support\OperationsRecipients;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CheckExpiringAdsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * How long before its effective end an advertisement counts as "expiring soon".
     */
    public const WARNING_WINDOW_HOURS = 24;

    /**
     * Runs daily, so two attempts is enough to ride out a transient mail failure
     * without turning a permanent one into a retry storm.
     */
    public int $tries = 2;

    public int $backoff = 300;

    public int $timeout = 120;

    /**
     * Retire ads whose window has closed, and warn about ads about to close.
     *
     * BOTH halves use the EFFECTIVE end from App\Support\AdValidity, not the raw
     * `end_date`. Phase 13 established that a date-only `end_date` of Aug 31 means
     * "through the whole of Aug 31", so comparing the raw column retired an ad at
     * `Aug 31 00:00` — a full day of paid airtime lost, and a warning email a day
     * early. The candidate set is bounded by the number of active ads with an end
     * date, so resolving the effective bound per ad in PHP is cheap; expressing the
     * midnight-only `+1 day` rule in SQL is not.
     */
    public function handle(AdSchedulerService $scheduler): void
    {
        $now = now();

        $candidates = Ad::query()
            ->with('screens:id')
            ->active()
            ->whereNotNull('end_date')
            // Superset: every ad whose raw end is at or before the warning horizon.
            // The effective bound can only ever be later than the raw value, so
            // nothing eligible is missed.
            ->where('end_date', '<=', $this->warningHorizon($now))
            ->get();

        $expired = $candidates->filter(
            fn (Ad $ad) => $this->effectiveEnd($ad)?->lessThanOrEqualTo($now) ?? false
        );

        $this->retire($expired, $scheduler);

        // An ad retired in this run is no longer "expiring soon" — it has expired.
        $expiring = $candidates
            ->reject(fn (Ad $ad) => $expired->contains('id', $ad->id))
            ->filter(fn (Ad $ad) => $this->isExpiringSoon($ad, $now));

        foreach ($expiring as $ad) {
            $this->warnOnce($ad);
        }
    }

    /**
     * Move finished ads to `expired`.
     *
     * The status change goes through the Phase 13 transition map rather than forcing
     * the column, so this system action can only produce a state the lifecycle
     * declares. `active --expire--> expired` is a declared edge; an ad in any other
     * status is left alone.
     *
     * @param  \Illuminate\Support\Collection<int, Ad>  $expired
     */
    protected function retire($expired, AdSchedulerService $scheduler): void
    {
        if ($expired->isEmpty()) {
            return;
        }

        $affectedScreenIds = [];

        foreach ($expired as $ad) {
            if (! $ad->status->allows(AdStatus::ACTION_EXPIRE)) {
                continue;
            }

            $ad->status = $ad->status->resultOf(AdStatus::ACTION_EXPIRE);
            $ad->save();

            $affectedScreenIds = array_merge($affectedScreenIds, $ad->screens->pluck('id')->all());
        }

        // Saving the ad already flushes its screens through AdObserver; this is the
        // belt-and-braces flush the previous implementation performed, kept because
        // it is harmless and covers a detached-relation edge case.
        $scheduler->forgetMany(array_unique($affectedScreenIds));
    }

    /**
     * Is the ad inside the warning window — still valid now, but not for much longer?
     */
    protected function isExpiringSoon(Ad $ad, Carbon $now): bool
    {
        $end = $this->effectiveEnd($ad);

        if ($end === null) {
            return false;
        }

        return $end->greaterThan($now) && $end->lessThanOrEqualTo($this->warningHorizon($now));
    }

    /**
     * The instant the ad actually stops being valid.
     */
    protected function effectiveEnd(Ad $ad): ?Carbon
    {
        return AdValidity::endsBefore($ad->end_date);
    }

    /**
     * The far edge of the warning window.
     */
    protected function warningHorizon(?Carbon $now = null): Carbon
    {
        return ($now ? $now->copy() : now())->addHours(self::WARNING_WINDOW_HOURS);
    }

    /**
     * Send the warning at most once per advertisement per end date.
     *
     * Without this the job re-notified the same ad on every run for as long as it sat
     * inside the window — daily today, and every run of whatever cadence someone
     * chooses later. The key includes the effective end, so genuinely extending a
     * campaign and letting it approach a new end date warns again, which is the
     * behaviour an operator wants. No new table and no campaign framework: a cache
     * key that expires shortly after the ad does.
     */
    protected function warnOnce(Ad $ad): void
    {
        $end = $this->effectiveEnd($ad);
        $key = sprintf('ads:expiring-warned:%d:%s', $ad->id, $end?->timestamp ?? 'none');

        // add() is atomic, so two workers processing the same tick cannot both win.
        $ttl = $end ? max(60, (int) now()->diffInSeconds($end) + 3600) : 3600;

        if (! Cache::add($key, true, $ttl)) {
            return;
        }

        $this->notifyOperations(new AdExpiringNotification($ad));
    }

    /**
     * Hand the alert to the configured operational recipients. A missing recipient
     * logs a warning rather than disappearing.
     */
    protected function notifyOperations(BaseNotification $notification): void
    {
        OperationsRecipients::resolve('advertisement expiring alert')?->notify($notification);
    }
}
