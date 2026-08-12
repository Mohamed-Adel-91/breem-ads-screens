<?php

namespace App\Notifications;

use App\Models\Screen;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * A screen has stopped reporting.
 *
 * Delivery is queued (ShouldQueue) so the every-minute sweep never waits on SMTP or
 * on Slack. `$tries`/`$backoff` are deliberately small: a stale offline alert has
 * little value, and a permanent misconfiguration should land in `failed_jobs` where
 * it is visible rather than being retried indefinitely.
 *
 * The payload carries operational facts only — screen code, place, timings, device
 * UID. No bearer token, no HMAC secret, no raw exception text.
 */
class ScreenOfflineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Increasing gaps, then give up and record the failure.
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 300];

    public int $timeout = 30;

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
     *
     * `slack` is a real channel here now. It previously never appeared in this list:
     * Slack delivery was smuggled into toArray() — the *log* channel's payload
     * builder — as an Http::post() side effect, so the network call ran inside the log
     * driver, its failures were swallowed by report(), and the installed
     * laravel/slack-notification-channel package was bypassed entirely.
     *
     * The `log` channel is always included, so a delivery record exists even when no
     * external route is configured.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->routeNotificationFor('mail', $this)) {
            $channels[] = 'mail';
        }

        if ($notifiable->routeNotificationFor('slack', $this)) {
            $channels[] = 'slack';
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
     * The Slack representation.
     *
     * Returns the legacy SlackMessage, which is what SlackWebhookChannel builds its
     * JSON payload from — the same attachment shape the hand-rolled Http::post()
     * assembled, now delivered by the package so failures surface as job failures
     * instead of being swallowed.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $screen = $this->screen->fresh(['place']) ?? $this->screen;
        $lastHeartbeat = $this->lastHeartbeat ?? $screen->last_heartbeat;
        $detailsUrl = $screen->exists
            ? route('admin.screens.show', ['lang' => app()->getLocale(), 'screen' => $screen])
            : null;

        return (new SlackMessage())
            ->error()
            ->content(':rotating_light: '.__('Screen offline detected'))
            ->attachment(function ($attachment) use ($screen, $lastHeartbeat, $detailsUrl): void {
                $attachment->title((string) $screen->code, $detailsUrl)
                    ->fields([
                        __('Location') => $screen->place?->name ?? '—',
                        __('Last heartbeat') => $lastHeartbeat
                            ? $lastHeartbeat->toDateTimeString().' ('.$lastHeartbeat->diffForHumans().')'
                            : __('Unknown'),
                        __('Detected at') => $this->detectedAt->toDateTimeString(),
                        __('Device UID') => $screen->device_uid ?? __('Not assigned'),
                    ]);
            });
    }

    /**
     * Get the array representation of the notification for the log channel.
     *
     * A pure representation. It used to perform an Http::post() to Slack as a side
     * effect, which meant the log channel did network I/O and any other caller of
     * toArray() would have fired a duplicate Slack message.
     */
    public function toArray(object $notifiable): array
    {
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
}
