<?php

namespace App\Jobs;

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Notifications\AdExpiringNotification;
use App\Services\Screen\AdSchedulerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CheckExpiringAdsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(AdSchedulerService $scheduler): void
    {
        $now = now();

        $expiredAds = Ad::query()
            ->with('screens:id')
            ->active()
            ->whereNotNull('end_date')
            ->where('end_date', '<=', $now)
            ->get();

        if ($expiredAds->isNotEmpty()) {
            foreach ($expiredAds as $ad) {
                $ad->forceFill(['status' => AdStatus::Expired])->save();
            }

            $scheduler->forgetMany(
                $expiredAds
                    ->flatMap(fn (Ad $ad) => $ad->screens->pluck('id'))
                    ->unique()
                    ->all()
            );
        }

        $expiringAds = Ad::query()
            ->with('screens:id')
            ->expiringSoon($this->expirationThreshold())
            ->get();

        foreach ($expiringAds as $ad) {
            $this->notifyAdmins(new AdExpiringNotification($ad));
        }
    }

    /**
     * Resolve the expiration threshold for sending notifications.
     */
    protected function expirationThreshold(): Carbon
    {
        return now()->addDay();
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
