<?php

namespace App\Jobs;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use App\Notifications\ScreenOfflineNotification;
use App\Services\Screen\HeartbeatService;
use App\Support\OperationsRecipients;
use App\Support\ScreenHealth;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckScreenHealthJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Conservative retry policy.
     *
     * The sweep runs every minute, so a failed attempt is superseded almost
     * immediately by a fresh one; retrying more than a couple of times would pile up
     * duplicate work rather than recover from anything. `failed_jobs` keeps the
     * record when both attempts fail.
     */
    public int $tries = 2;

    /**
     * Seconds between attempts.
     */
    public int $backoff = 30;

    /**
     * Never outlive the sweep interval — withoutOverlapping() already prevents
     * stacking, and a job hung past its own cadence is a fault, not slow progress.
     */
    public int $timeout = 55;

    /**
     * Transition every screen that has gone silent.
     *
     * Idempotent by construction, and this is also what makes the alert idempotent:
     * the query selects only screens that are still `online`, so once a screen has
     * been transitioned it drops out of the result set and no further work — no
     * duplicate log, no second notification — happens on the next tick.
     * HeartbeatService::markOffline() re-checks eligibility, so a screen that
     * heartbeated between the query and the write is left alone and not notified.
     */
    public function handle(HeartbeatService $heartbeatService): void
    {
        $screens = Screen::query()
            ->with('place')
            ->where('status', ScreenStatus::Online)
            ->whereNotNull('last_heartbeat')
            ->where('last_heartbeat', '<', ScreenHealth::offlineThreshold())
            ->get();

        foreach ($screens as $screen) {
            $lastHeartbeat = $screen->last_heartbeat;

            if (! $heartbeatService->markOffline($screen)) {
                continue;
            }

            // The transition is already committed. Delivery is attempted after it and
            // can never undo it.
            $this->notifyOperations(new ScreenOfflineNotification($screen, now(), $lastHeartbeat));
        }
    }

    /**
     * Hand the alert to the configured operational recipients.
     *
     * A missing recipient is not an error here: OperationsRecipients logs a warning
     * naming the missing configuration and returns null, so the screen stays offline
     * and the gap is visible in the log instead of vanishing.
     */
    protected function notifyOperations(BaseNotification $notification): void
    {
        OperationsRecipients::resolve('screen offline alert')?->notify($notification);
    }
}
