<?php

namespace App\Notifications;

use App\Models\Screen;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

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
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $screen = $this->screen->fresh(['place']) ?? $this->screen;
        $lastHeartbeat = $this->lastHeartbeat ?? $screen->last_heartbeat;
        $url = $screen->exists
            ? route('admin.screens.show', ['lang' => app()->getLocale(), 'screen' => $screen])
            : null;

        return (new SlackMessage())
            ->error()
            ->content(':rotating_light: Screen offline detected')
            ->attachment(function ($attachment) use ($screen, $lastHeartbeat, $url): void {
                $attachment->title($screen->code, $url ?? '')
                    ->fields([
                        'Location' => $screen->place?->name ?? '—',
                        'Last heartbeat' => $lastHeartbeat
                            ? $lastHeartbeat->toDateTimeString().' ('.$lastHeartbeat->diffForHumans().')'
                            : __('Unknown'),
                        'Detected at' => $this->detectedAt->toDateTimeString(),
                        'Device UID' => $screen->device_uid ?? __('Not assigned'),
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
}
