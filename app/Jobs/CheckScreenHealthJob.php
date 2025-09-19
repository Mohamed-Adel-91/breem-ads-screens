<?php

namespace App\Jobs;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use App\Notifications\ScreenOfflineNotification;
use App\Services\Screen\HeartbeatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CheckScreenHealthJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(HeartbeatService $heartbeatService): void
    {
        $threshold = $this->threshold();

        $screens = Screen::query()
            ->with('place')
            ->where('status', ScreenStatus::Online)
            ->whereNotNull('last_heartbeat')
            ->where('last_heartbeat', '<', $threshold)
            ->get();

        foreach ($screens as $screen) {
            $heartbeatService->touch($screen, $screen->device_uid, [
                'status' => ScreenStatus::Offline,
                'reported_at' => now(),
                'last_heartbeat' => $screen->last_heartbeat,
            ]);

            $this->notifyAdmins(new ScreenOfflineNotification($screen, now(), $screen->last_heartbeat));
        }
    }

    /**
     * Determine the latest acceptable heartbeat timestamp.
     */
    protected function threshold(): Carbon
    {
        $interval = max(1, (int) config('services.screens.heartbeat_interval', 60));
        $grace = max($interval, (int) ($interval * 2));

        return now()->subSeconds($grace);
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
