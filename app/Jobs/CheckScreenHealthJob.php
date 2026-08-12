<?php

namespace App\Jobs;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use App\Notifications\ScreenOfflineNotification;
use App\Services\Screen\HeartbeatService;
use App\Support\ScreenHealth;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class CheckScreenHealthJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Transition every screen that has gone silent.
     *
     * Idempotent by construction. The query selects only screens that are still
     * `online`, so once a screen has been transitioned it drops out and no
     * further work — no duplicate log, no repeat notification — happens on the
     * next tick. HeartbeatService::markOffline() re-checks eligibility, so a
     * screen that heartbeated between the query and the write is left alone.
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

            $this->notifyAdmins(new ScreenOfflineNotification($screen, now(), $lastHeartbeat));
        }
    }

    /**
     * Send the notification to the configured administrative channels.
     */
    protected function notifyAdmins(BaseNotification $notification): void
    {
        $notifiable = $this->adminNotifiable();

        if (! $notifiable) {
            return;
        }

        $notifiable->notify($notification);
    }

    /**
     * Resolve the anonymous notifiable instance for admin recipients.
     */
    protected function adminNotifiable(): ?AnonymousNotifiable
    {
        $email = (string) config('admin.email');
        $slackChannel = config('services.slack.notifications.channel');
        $slackToken = config('services.slack.notifications.bot_user_oauth_token');

        $notifiable = null;

        if ($email !== '') {
            $notifiable = Notification::route('mail', $email);
        }

        if ($slackChannel && $slackToken) {
            $notifiable = $notifiable
                ? $notifiable->route('slack', $slackChannel)
                : Notification::route('slack', $slackChannel);
        }

        return $notifiable;
    }
}
