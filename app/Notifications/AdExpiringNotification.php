<?php

namespace App\Notifications;

use App\Models\Ad;
use App\Support\AdValidity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * An advertisement is about to stop being valid.
 *
 * Queued, with the same conservative retry policy as the offline alert. Reports the
 * EFFECTIVE end from App\Support\AdValidity, not the raw `end_date`: a date-only end
 * of Aug 31 runs through the whole of Aug 31, and quoting `2026-08-31 00:00` in the
 * email would tell an operator the campaign stops a day before it does.
 */
class AdExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300];

    public int $timeout = 30;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Ad $ad)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * See ScreenOfflineNotification::via() — `slack` is a real channel now rather
     * than an Http::post() hidden inside toArray().
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
     * The instant this ad actually stops being valid.
     */
    protected function effectiveEnd(Ad $ad): ?Carbon
    {
        return AdValidity::endsBefore($ad->end_date);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $ad = $this->ad->fresh(['screens']) ?? $this->ad;
        $ad->loadMissing('screens');

        $title = $this->title($ad);
        // The instant playback genuinely stops, not the raw stored date.
        $endDate = $this->effectiveEnd($ad);

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
     * The Slack representation, delivered by the installed package's channel.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $ad = $this->ad->fresh(['screens']) ?? $this->ad;
        $ad->loadMissing('screens');
        $endDate = $this->effectiveEnd($ad);
        $detailsUrl = $ad->exists
            ? route('admin.ads.show', ['lang' => app()->getLocale(), 'ad' => $ad])
            : null;

        return (new SlackMessage())
            ->warning()
            ->content(':hourglass_flowing_sand: '.__('Ad expiring soon'))
            ->attachment(function ($attachment) use ($ad, $endDate, $detailsUrl): void {
                $attachment->title($this->title($ad), $detailsUrl)
                    ->fields([
                        __('Ad ID') => (string) $ad->id,
                        __('Ends at') => $endDate instanceof Carbon
                            ? $endDate->toDateTimeString().' ('.$endDate->diffForHumans().')'
                            : __('Not set'),
                        __('Screens') => (string) $ad->screens->count(),
                    ]);
            });
    }

    /**
     * Get the array representation of the notification for the log channel.
     *
     * A pure representation — the Http::post() side effect that used to live here is
     * gone; see via().
     */
    public function toArray(object $notifiable): array
    {
        $ad = $this->ad->fresh(['screens']) ?? $this->ad;
        $ad->loadMissing('screens');
        $endDate = $this->effectiveEnd($ad);

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
