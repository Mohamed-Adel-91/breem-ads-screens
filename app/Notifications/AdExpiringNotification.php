<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class AdExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Ad $ad)
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->routeNotificationFor('mail', $this)) {
            $channels[] = 'mail';
        }

        if ($this->shouldSendSlack() && $notifiable->routeNotificationFor('slack', $this)) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $ad = $this->ad->fresh(['screens']) ?? $this->ad;
        $ad->loadMissing('screens');

        $title = $this->title($ad);
        $endDate = $ad->end_date;

        $message = (new MailMessage())
            ->subject(__('Ad expiring soon: :title', ['title' => $title]))
            ->line(__('The ad ":title" is scheduled to expire soon.', ['title' => $title]));

        if ($endDate instanceof Carbon) {
            $message->line(__('Scheduled end: :timestamp (:diff).', [
                'timestamp' => $endDate->toDateTimeString(),
                'diff' => $endDate->diffForHumans(),
            ]));
        }

        $message->line(__('Screens currently targeted: :count', ['count' => $ad->screens->count()]));

        if ($ad->exists) {
            $message->action(
                __('Review ad'),
                route('admin.ads.show', ['lang' => app()->getLocale(), 'ad' => $ad])
            );
        }

        return $message->line(__('Please review whether the campaign should be extended or replaced.'));
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $ad = $this->ad->fresh(['screens']) ?? $this->ad;
        $ad->loadMissing('screens');
        $endDate = $ad->end_date;
        $url = $ad->exists
            ? route('admin.ads.show', ['lang' => app()->getLocale(), 'ad' => $ad])
            : null;

        return (new SlackMessage())
            ->warning()
            ->content(':hourglass_flowing_sand: Ad expiring soon')
            ->attachment(function ($attachment) use ($ad, $endDate, $url): void {
                $attachment->title($this->title($ad), $url ?? '')
                    ->fields([
                        'Ad ID' => (string) $ad->id,
                        'Ends at' => $endDate instanceof Carbon
                            ? $endDate->toDateTimeString().' ('.$endDate->diffForHumans().')'
                            : __('Not set'),
                        'Screens' => (string) $ad->screens->count(),
                    ]);
            });
    }

    /**
     * Determine if Slack notifications should be attempted.
     */
    protected function shouldSendSlack(): bool
    {
        return filled(config('services.slack.notifications.bot_user_oauth_token'))
            && filled(config('services.slack.notifications.channel'));
    }

    /**
     * Resolve the human friendly ad title.
     */
    protected function title(Ad $ad): string
    {
        $locale = app()->getLocale();
        $title = method_exists($ad, 'getTranslation')
            ? $ad->getTranslation('title', $locale, false)
            : $ad->title;

        if (is_array($title)) {
            $title = $title[$locale] ?? reset($title) ?: null;
        }

        return $title ?: __('Ad #:id', ['id' => $ad->id]);
    }
}
