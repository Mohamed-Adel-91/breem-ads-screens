<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

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

        $channels[] = 'log';

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
     * Determine if Slack notifications should be attempted.
     */
    protected function shouldSendSlack(object $notifiable): bool
    {
        return filled($this->slackWebhookUrl($notifiable));
    }

    /**
     * Post the notification payload to the configured Slack webhook.
     */
    protected function sendSlackWebhook(object $notifiable): void
    {
        $webhookUrl = $this->slackWebhookUrl($notifiable);

        if (! $webhookUrl) {
            return;
        }

        try {
            Http::post($webhookUrl, $this->slackWebhookPayload());
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Build the payload that will be sent to Slack.
     */
    protected function slackWebhookPayload(): array
    {
        $ad = $this->ad->fresh(['screens']) ?? $this->ad;
        $ad->loadMissing('screens');
        $endDate = $ad->end_date;

        $attachment = [
            'color' => 'warning',
            'title' => $this->title($ad),
            'fields' => [
                [
                    'title' => __('Ad ID'),
                    'value' => (string) $ad->id,
                    'short' => true,
                ],
                [
                    'title' => __('Ends at'),
                    'value' => $endDate instanceof Carbon
                        ? $endDate->toDateTimeString().' ('.$endDate->diffForHumans().')'
                        : __('Not set'),
                    'short' => true,
                ],
                [
                    'title' => __('Screens'),
                    'value' => (string) $ad->screens->count(),
                    'short' => true,
                ],
            ],
        ];

        if ($ad->exists) {
            $attachment['title_link'] = route('admin.ads.show', ['lang' => app()->getLocale(), 'ad' => $ad]);
        }

        return [
            'text' => ':hourglass_flowing_sand: '.__('Ad expiring soon'),
            'attachments' => [$attachment],
        ];
    }

    /**
     * Resolve the Slack webhook URL for the notification.
     */
    protected function slackWebhookUrl(object $notifiable): ?string
    {
        $url = $notifiable->routeNotificationFor('slack', $this)
            ?? config('services.slack.webhook_url');

        return is_string($url) && filled($url) ? $url : null;
    }

    /**
     * Get the array representation of the notification for the log channel.
     */
    public function toArray(object $notifiable): array
    {
        if ($this->shouldSendSlack($notifiable)) {
            $this->sendSlackWebhook($notifiable);
        }

        $ad = $this->ad->fresh(['screens']) ?? $this->ad;
        $ad->loadMissing('screens');
        $endDate = $ad->end_date;

        return [
            'message' => __('Ad expiring soon'),
            'ad_id' => $ad->id,
            'title' => $this->title($ad),
            'ends_at' => $endDate instanceof Carbon ? $endDate->toDateTimeString() : null,
            'screens' => $ad->screens->count(),
        ];
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
