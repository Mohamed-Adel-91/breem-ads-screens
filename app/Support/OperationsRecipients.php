<?php

namespace App\Support;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Resolves who receives operational fleet alerts.
 *
 * This replaces an `adminNotifiable()` method that was **duplicated verbatim** in
 * CheckScreenHealthJob and CheckExpiringAdsJob, and that had two problems:
 *
 *   1. **It dropped alerts in silence.** With no `ADMIN_EMAIL` configured it
 *      returned null and the caller returned too, so a screen went offline, the
 *      transition was recorded correctly, and nobody was ever told. Nothing
 *      anywhere reported that delivery had been skipped.
 *   2. **Its Slack branch could not fire.** It read
 *      `services.slack.notifications.channel` / `.bot_user_oauth_token`, which were
 *      absent from config/services.php, so the condition was permanently false.
 *
 * Both are fixed here. A missing recipient now logs a clear, actionable warning and
 * returns null; it never throws, because an alerting misconfiguration must not roll
 * back a screen's offline transition — the state change is the important part and it
 * has already been committed by the time this is reached.
 */
final class OperationsRecipients
{
    /**
     * The notifiable for operational alerts, or null when nothing is configured.
     *
     * `$context` names the alert in the warning, so a log line says which event was
     * not delivered rather than just "notification skipped".
     */
    public static function resolve(string $context = 'operational alert'): ?AnonymousNotifiable
    {
        $email = self::email();
        $slackRoute = self::slackRoute();

        $notifiable = null;

        if ($email !== null) {
            $notifiable = Notification::route('mail', $email);
        }

        if ($slackRoute !== null) {
            $notifiable = $notifiable
                ? $notifiable->route('slack', $slackRoute)
                : Notification::route('slack', $slackRoute);
        }

        if ($notifiable === null) {
            // Not an exception: the caller's state change is already committed and
            // must stand. This is the record that delivery did not happen.
            Log::warning('Operational notification skipped: no recipient is configured.', [
                'context' => $context,
                'expected_config' => 'notifications.operations.email (OPS_NOTIFICATION_EMAIL or ADMIN_EMAIL)',
                'optional_config' => 'services.slack.webhook_url or services.slack.notifications.*',
                'hint' => 'Run `php artisan ops:status` to see the current operational configuration.',
            ]);
        }

        return $notifiable;
    }

    /**
     * The configured operations mailbox, or null.
     *
     * `notifications.operations.email` first, then `admin.email` — the key the jobs
     * read before this class existed, so a deployment that only sets ADMIN_EMAIL keeps
     * receiving alerts. The fallback is resolved here rather than as a nested env()
     * default in config, so it still applies when `admin.email` is changed at runtime.
     */
    public static function email(): ?string
    {
        foreach (['notifications.operations.email', 'admin.email'] as $key) {
            $email = trim((string) config($key));

            if ($email !== '') {
                return $email;
            }
        }

        return null;
    }

    /**
     * The Slack route to hand the package's router: a webhook URL, or a channel
     * name when a bot token is configured. Null when Slack is not set up.
     *
     * The router in laravel/slack-notification-channel inspects the route — a
     * `http(s)://` string goes to SlackWebhookChannel, anything else to the Web API
     * channel — so the channel name is only offered when its token exists, or the
     * Web API call would fail with no credentials.
     */
    public static function slackRoute(): ?string
    {
        $webhook = trim((string) config('services.slack.webhook_url'));

        if ($webhook !== '') {
            return $webhook;
        }

        $channel = trim((string) config('services.slack.notifications.channel'));
        $token = trim((string) config('services.slack.notifications.bot_user_oauth_token'));

        return ($channel !== '' && $token !== '') ? $channel : null;
    }

    /**
     * Is at least one delivery route configured?
     */
    public static function isConfigured(): bool
    {
        return self::email() !== null || self::slackRoute() !== null;
    }

    /**
     * Configuration summary for `ops:status`. Deliberately reports *whether* Slack
     * is configured and by which route, never the webhook URL or token themselves.
     *
     * @return array<string, mixed>
     */
    public static function describe(): array
    {
        $webhook = trim((string) config('services.slack.webhook_url'));
        $channel = trim((string) config('services.slack.notifications.channel'));
        $token = trim((string) config('services.slack.notifications.bot_user_oauth_token'));

        return [
            'email' => self::email(),
            'slack_configured' => self::slackRoute() !== null,
            'slack_route_kind' => match (true) {
                $webhook !== '' => 'webhook',
                $channel !== '' && $token !== '' => 'bot_channel',
                default => null,
            },
            'configured' => self::isConfigured(),
        ];
    }
}
