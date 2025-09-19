<?php

namespace App\Notifications;

use App\Models\Screen;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class ScreenOfflineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Screen $screen,
        public Carbon $detectedAt,
        public ?Carbon $lastHeartbeat = null
    ) {
        $this->detectedAt = $detectedAt ?? now();
        $this->lastHeartbeat = $lastHeartbeat;
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
        $screen = $this->screen->fresh(['place']) ?? $this->screen;
        $placeName = $screen->place?->name;
        $lastHeartbeat = $this->lastHeartbeat ?? $screen->last_heartbeat;

        $message = (new MailMessage())
            ->subject(__('Screen offline: :code', ['code' => $screen->code]))
            ->line(__('Screen :code has stopped reporting heartbeats.', ['code' => $screen->code]));

        if ($placeName) {
            $message->line(__('Location: :place', ['place' => $placeName]));
        }

        if ($lastHeartbeat) {
            $message->line(__('Last heartbeat received at :timestamp (:diff).', [
                'timestamp' => $lastHeartbeat->toDateTimeString(),
                'diff' => $lastHeartbeat->diffForHumans(),
            ]));
        } else {
            $message->line(__('No heartbeat has been recorded for this screen.'));
        }

        $message->line(__('Offline status detected at :timestamp.', [
            'timestamp' => $this->detectedAt->toDateTimeString(),
        ]));

        if ($screen->exists) {
            $message->action(
                __('View screen details'),
                route('admin.screens.show', ['lang' => app()->getLocale(), 'screen' => $screen])
            );
        }

        return $message->line(__('Please investigate the device to restore connectivity.'));
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
        $screen = $this->screen->fresh(['place']) ?? $this->screen;
        $lastHeartbeat = $this->lastHeartbeat ?? $screen->last_heartbeat;
        $detailsUrl = $screen->exists
            ? route('admin.screens.show', ['lang' => app()->getLocale(), 'screen' => $screen])
            : null;

        $attachment = [
            'color' => 'danger',
            'title' => $screen->code,
            'fields' => [
                [
                    'title' => __('Location'),
                    'value' => $screen->place?->name ?? '—',
                    'short' => true,
                ],
                [
                    'title' => __('Last heartbeat'),
                    'value' => $lastHeartbeat
                        ? $lastHeartbeat->toDateTimeString().' ('.$lastHeartbeat->diffForHumans().')'
                        : __('Unknown'),
                    'short' => true,
                ],
                [
                    'title' => __('Detected at'),
                    'value' => $this->detectedAt->toDateTimeString(),
                    'short' => false,
                ],
                [
                    'title' => __('Device UID'),
                    'value' => $screen->device_uid ?? __('Not assigned'),
                    'short' => true,
                ],
            ],
        ];

        if ($detailsUrl) {
            $attachment['title_link'] = $detailsUrl;
        }

        return [
            'text' => ':rotating_light: '.__('Screen offline detected'),
            'attachments' => [$attachment],
        ];
    }

    /**
     * Get the array representation of the notification for the log channel.
     */
    public function toArray(object $notifiable): array
    {
        if ($this->shouldSendSlack($notifiable)) {
            $this->sendSlackWebhook($notifiable);
        }

        $screen = $this->screen->fresh(['place']) ?? $this->screen;
        $lastHeartbeat = $this->lastHeartbeat ?? $screen->last_heartbeat;

        return [
            'message' => __('Screen offline detected'),
            'screen_id' => $screen->id,
            'screen_code' => $screen->code,
            'location' => $screen->place?->name,
            'detected_at' => $this->detectedAt->toDateTimeString(),
            'last_heartbeat' => $lastHeartbeat?->toDateTimeString(),
            'device_uid' => $screen->device_uid,
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
}
