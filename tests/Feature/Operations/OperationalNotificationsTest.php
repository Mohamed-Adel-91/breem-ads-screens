<?php

namespace Tests\Feature\Operations;

use App\Enums\AdStatus;
use App\Enums\ScreenStatus;
use App\Jobs\CheckExpiringAdsJob;
use App\Jobs\CheckScreenHealthJob;
use App\Models\Ad;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use App\Notifications\AdExpiringNotification;
use App\Notifications\ScreenOfflineNotification;
use App\Support\OperationsRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Phase 14 — operational notification delivery.
 *
 * Three defects sat behind these tests:
 *
 *   1. **Silent drops.** Both jobs resolved a recipient from `admin.email` and simply
 *      returned when it was unset. A screen went offline, the transition was recorded
 *      correctly, and nobody was told — with nothing in the log to say so.
 *   2. **A dead Slack branch.** The jobs tested `services.slack.notifications.channel`
 *      and `.bot_user_oauth_token`, neither of which existed in config/services.php,
 *      so that condition was permanently false. Meanwhile Slack was actually posted
 *      from inside `toArray()` — the *log* channel's payload builder — as an
 *      `Http::post()` side effect whose failures were swallowed.
 *   3. **Expiring-ad spam and a one-day error.** The warning re-sent on every run
 *      while an ad sat inside the window, and both the warning and the automatic
 *      retirement compared the raw `end_date` rather than the effective end from
 *      AdValidity, so a date-only campaign was retired a full day early.
 */
class OperationalNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::create(2026, 10, 5, 12, 0, 0);
        Carbon::setTestNow($this->now);

        config([
            'notifications.operations.email' => 'ops@example.test',
            'admin.email' => null,
            'services.slack.webhook_url' => null,
            'services.slack.notifications.channel' => null,
            'services.slack.notifications.bot_user_oauth_token' => null,
            'services.screens.heartbeat_interval' => 60,
            'services.screens.offline_after' => 120,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ------------------------------------------------------------------- helpers

    private function silentScreen(): Screen
    {
        return Screen::factory()->create([
            'place_id' => Place::factory()->create()->id,
            'code' => 'SCR-ALERT',
            'status' => ScreenStatus::Online->value,
            'last_heartbeat' => $this->now->copy()->subMinutes(30),
        ]);
    }

    private function sweep(): void
    {
        CheckScreenHealthJob::dispatchSync();
    }

    private function makeAd(?string $endDate, array $overrides = []): Ad
    {
        return Ad::create(array_merge([
            'title' => ['en' => 'Expiring Campaign'],
            'file_path' => 'upload/ads/expiring.mp4',
            'file_type' => 'video',
            'duration_seconds' => 20,
            'status' => AdStatus::Active,
            'created_by' => User::factory()->create()->id,
            'start_date' => $this->now->copy()->subMonth(),
            'end_date' => $endDate,
        ], $overrides));
    }

    // ------------------------------------------------------ recipient resolution

    public function test_a_configured_recipient_receives_the_offline_alert(): void
    {
        Notification::fake();

        $screen = $this->silentScreen();

        $this->sweep();

        $this->assertSame(ScreenStatus::Offline, $screen->fresh()->status);
        Notification::assertSentTimes(ScreenOfflineNotification::class, 1);
        Notification::assertSentOnDemand(
            ScreenOfflineNotification::class,
            fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'ops@example.test'
        );
    }

    /**
     * The legacy key still works, so a deployment that only ever set ADMIN_EMAIL keeps
     * receiving alerts. The fallback is resolved at read time.
     */
    public function test_the_legacy_admin_email_still_resolves_as_a_fallback(): void
    {
        config(['notifications.operations.email' => null, 'admin.email' => 'legacy@example.test']);

        $this->assertSame('legacy@example.test', OperationsRecipients::email());
        $this->assertTrue(OperationsRecipients::isConfigured());
    }

    public function test_the_dedicated_key_takes_precedence_over_the_legacy_one(): void
    {
        config(['notifications.operations.email' => 'ops@example.test', 'admin.email' => 'legacy@example.test']);

        $this->assertSame('ops@example.test', OperationsRecipients::email());
    }

    // --------------------------------------------------------- missing recipient

    /**
     * PART 25 — a missing recipient must not roll back the state change.
     */
    public function test_a_missing_recipient_does_not_prevent_the_offline_transition(): void
    {
        config(['notifications.operations.email' => null, 'admin.email' => null]);
        Notification::fake();

        $screen = $this->silentScreen();

        $this->sweep();

        $this->assertSame(
            ScreenStatus::Offline,
            $screen->fresh()->status,
            'Detection must be unaffected by an alerting misconfiguration.'
        );
        $this->assertSame(1, $screen->logs()->where('status', ScreenStatus::Offline->value)->count());
        Notification::assertNothingSent();
    }

    /**
     * And the gap has to be visible rather than silent.
     */
    public function test_a_missing_recipient_logs_an_actionable_warning(): void
    {
        config(['notifications.operations.email' => null, 'admin.email' => null]);
        Notification::fake();

        $captured = [];

        Log::listen(function ($message) use (&$captured): void {
            $captured[] = ['level' => $message->level, 'message' => $message->message, 'context' => $message->context];
        });

        $this->silentScreen();
        $this->sweep();

        $warnings = array_values(array_filter(
            $captured,
            fn (array $entry) => $entry['level'] === 'warning'
                && str_contains($entry['message'], 'no recipient is configured')
        ));

        $this->assertNotEmpty($warnings, 'A skipped alert must be recorded, not dropped in silence.');
        $this->assertSame('screen offline alert', $warnings[0]['context']['context']);
        // The warning names the configuration to set.
        $this->assertStringContainsString('OPS_NOTIFICATION_EMAIL', $warnings[0]['context']['expected_config']);
    }

    public function test_the_resolver_reports_whether_it_is_configured(): void
    {
        $this->assertTrue(OperationsRecipients::isConfigured());

        config(['notifications.operations.email' => null, 'admin.email' => null]);
        $this->assertFalse(OperationsRecipients::isConfigured());
    }

    // -------------------------------------------------------------- deduplication

    /**
     * PART 29 — one transition, one alert. Repeated ticks while already offline send
     * nothing, because the sweep only selects screens that are still online.
     */
    public function test_repeated_sweeps_do_not_resend_the_offline_alert(): void
    {
        Notification::fake();

        $screen = $this->silentScreen();

        $this->sweep();
        $this->sweep();
        $this->sweep();

        Notification::assertSentTimes(ScreenOfflineNotification::class, 1);
        $this->assertSame(
            1,
            $screen->logs()->where('status', ScreenStatus::Offline->value)->count(),
            'And exactly one transition log.'
        );
    }

    public function test_a_screen_that_recovers_and_dies_again_alerts_twice(): void
    {
        Notification::fake();

        $screen = $this->silentScreen();
        $this->sweep();

        // Back online, then silent again. refresh() first: the sweep transitioned the
        // row through its own instance, so this one still believes it is online and
        // writing `Online` over `Online` would be a no-op.
        $screen->refresh();
        $screen->forceFill([
            'status' => ScreenStatus::Online,
            'last_heartbeat' => $this->now->copy()->addMinutes(5),
        ])->save();

        Carbon::setTestNow($this->now->copy()->addMinutes(40));
        $this->sweep();

        Notification::assertSentTimes(ScreenOfflineNotification::class, 2);
    }

    // ------------------------------------------------------------------- queueing

    public function test_operational_notifications_are_queued(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new ScreenOfflineNotification(
            $this->silentScreen(),
            $this->now
        ));

        $this->assertInstanceOf(ShouldQueue::class, new AdExpiringNotification(
            $this->makeAd($this->now->copy()->addHours(6)->toDateTimeString())
        ));
    }

    /**
     * Conservative and finite: a permanent failure must land in `failed_jobs` rather
     * than retry for ever.
     */
    public function test_notification_retry_limits_are_conservative_and_finite(): void
    {
        $offline = new ScreenOfflineNotification($this->silentScreen(), $this->now);

        $this->assertSame(3, $offline->tries);
        $this->assertSame([60, 300], $offline->backoff);
        $this->assertGreaterThan(0, $offline->timeout);

        $expiring = new AdExpiringNotification($this->makeAd(null));

        $this->assertSame(3, $expiring->tries);
        $this->assertSame([60, 300], $expiring->backoff);
    }

    public function test_the_sweep_job_has_a_finite_retry_policy(): void
    {
        $job = new CheckScreenHealthJob();

        $this->assertSame(2, $job->tries);
        $this->assertGreaterThan(0, $job->backoff);
        // Must not outlive its own every-minute cadence.
        $this->assertLessThan(60, $job->timeout);
    }

    public function test_failed_notification_jobs_are_recorded(): void
    {
        // The queue's failure record is what makes a delivery failure observable.
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('failed_jobs'),
            'failed_jobs must exist for delivery failures to be observable.'
        );
    }

    // ---------------------------------------------------------------- no secrets

    public function test_the_offline_payload_carries_no_credentials(): void
    {
        $screen = $this->silentScreen();
        $notification = new ScreenOfflineNotification($screen, $this->now, $this->now->copy()->subMinutes(30));

        $payload = $notification->toArray(new AnonymousNotifiable());
        $encoded = json_encode($payload);

        $this->assertArrayHasKey('screen_code', $payload);
        $this->assertArrayHasKey('detected_at', $payload);

        foreach (['token', 'secret', 'hmac', 'signature', 'password', 'authorization'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase(
                $forbidden,
                (string) $encoded,
                "The alert payload must never carry [{$forbidden}] material."
            );
        }
    }

    public function test_the_mail_body_reports_operational_facts_only(): void
    {
        $screen = $this->silentScreen();
        $notification = new ScreenOfflineNotification($screen, $this->now, $this->now->copy()->subMinutes(30));

        $mail = $notification->toMail(Notification::route('mail', 'ops@example.test'));
        $rendered = json_encode($mail->toArray());

        $this->assertStringContainsString('SCR-ALERT', (string) $rendered);
        $this->assertStringNotContainsStringIgnoringCase('bearer', (string) $rendered);
    }

    // -------------------------------------------------------------------- slack

    /**
     * PART 34 — Slack is a real channel now. It used to be absent from via()
     * entirely, with the webhook posted from inside toArray().
     */
    public function test_slack_is_a_real_channel_when_a_webhook_is_configured(): void
    {
        config(['services.slack.webhook_url' => 'https://hooks.slack.test/services/T/B/x']);

        $notifiable = Notification::route('mail', 'ops@example.test')
            ->route('slack', OperationsRecipients::slackRoute());

        $channels = (new ScreenOfflineNotification($this->silentScreen(), $this->now))->via($notifiable);

        $this->assertContains('slack', $channels);
        $this->assertContains('mail', $channels);
        $this->assertContains('log', $channels);
    }

    public function test_slack_is_absent_from_the_channels_when_unconfigured(): void
    {
        $notifiable = Notification::route('mail', 'ops@example.test');

        $channels = (new ScreenOfflineNotification($this->silentScreen(), $this->now))->via($notifiable);

        $this->assertNotContains('slack', $channels);
        $this->assertContains('log', $channels);
    }

    public function test_the_bot_channel_route_requires_its_token(): void
    {
        config(['services.slack.notifications.channel' => '#ops']);
        $this->assertNull(
            OperationsRecipients::slackRoute(),
            'A channel with no token would fail the Web API call, so it is not offered.'
        );

        config(['services.slack.notifications.bot_user_oauth_token' => 'xoxb-test']);
        $this->assertSame('#ops', OperationsRecipients::slackRoute());
    }

    public function test_a_webhook_takes_precedence_over_the_bot_channel(): void
    {
        config([
            'services.slack.webhook_url' => 'https://hooks.slack.test/services/T/B/x',
            'services.slack.notifications.channel' => '#ops',
            'services.slack.notifications.bot_user_oauth_token' => 'xoxb-test',
        ]);

        $this->assertSame('https://hooks.slack.test/services/T/B/x', OperationsRecipients::slackRoute());
        $this->assertSame('webhook', OperationsRecipients::describe()['slack_route_kind']);
    }

    /**
     * The log payload must be a pure representation — building it must not send
     * anything anywhere.
     */
    public function test_building_the_log_payload_performs_no_delivery(): void
    {
        config(['services.slack.webhook_url' => 'https://hooks.slack.test/services/T/B/x']);
        \Illuminate\Support\Facades\Http::fake();

        (new ScreenOfflineNotification($this->silentScreen(), $this->now))
            ->toArray(new AnonymousNotifiable());

        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_the_status_command_never_prints_slack_credentials(): void
    {
        config([
            'services.slack.webhook_url' => 'https://hooks.slack.test/services/T/B/super-secret',
            'notifications.operations.email' => 'ops@example.test',
        ]);

        $this->artisan('ops:status')
            ->doesntExpectOutputToContain('super-secret')
            ->assertSuccessful();
    }

    public function test_the_status_command_fails_when_no_recipient_is_configured(): void
    {
        config(['notifications.operations.email' => null, 'admin.email' => null]);

        // Exit code 1 so a deployment readiness gate can catch it.
        $this->artisan('ops:status')->assertFailed();
    }

    // ------------------------------------------------------------ expiring ads

    /**
     * PART 38 — a date-only end runs through the whole of that day. Comparing the raw
     * column retired the ad, and warned about it, a day early.
     */
    public function test_a_date_only_ad_is_not_retired_a_day_early(): void
    {
        Notification::fake();

        // end_date is midnight on the 5th; "now" is midday on the 5th. The effective
        // end is midnight on the 6th, so the ad is still live.
        $ad = $this->makeAd('2026-10-05 00:00:00');

        CheckExpiringAdsJob::dispatchSync();

        $this->assertSame(
            AdStatus::Active,
            $ad->fresh()->status,
            'The ad still has the rest of its final day to run.'
        );
    }

    public function test_a_date_only_ad_is_retired_once_its_final_day_has_passed(): void
    {
        Notification::fake();

        $ad = $this->makeAd('2026-10-05 00:00:00');

        Carbon::setTestNow(Carbon::parse('2026-10-06 00:00:00'));
        CheckExpiringAdsJob::dispatchSync();

        $this->assertSame(AdStatus::Expired, $ad->fresh()->status);
    }

    public function test_an_ad_inside_the_warning_window_is_notified(): void
    {
        Notification::fake();

        // Effective end is midnight on the 6th — 12 hours away.
        $this->makeAd('2026-10-05 00:00:00');

        CheckExpiringAdsJob::dispatchSync();

        Notification::assertSentTimes(AdExpiringNotification::class, 1);
    }

    public function test_an_ad_beyond_the_warning_window_is_not_notified(): void
    {
        Notification::fake();

        $this->makeAd('2026-12-31 00:00:00');

        CheckExpiringAdsJob::dispatchSync();

        Notification::assertNothingSent();
    }

    /**
     * PART 39 — the warning is sent once per advertisement per end date, not on every
     * run for as long as the ad sits inside the window.
     */
    public function test_the_expiring_warning_is_not_repeated_on_every_run(): void
    {
        Notification::fake();
        Cache::flush();

        $this->makeAd('2026-10-05 00:00:00');

        CheckExpiringAdsJob::dispatchSync();
        CheckExpiringAdsJob::dispatchSync();
        CheckExpiringAdsJob::dispatchSync();

        Notification::assertSentTimes(AdExpiringNotification::class, 1);
    }

    /**
     * But extending a campaign and approaching a new end date warns again — the dedupe
     * key includes the effective end.
     */
    public function test_extending_an_ad_allows_a_fresh_warning(): void
    {
        Notification::fake();
        Cache::flush();

        $ad = $this->makeAd('2026-10-05 00:00:00');

        CheckExpiringAdsJob::dispatchSync();
        Notification::assertSentTimes(AdExpiringNotification::class, 1);

        // Extended by a week, then time advances to just inside the new window.
        $ad->forceFill(['end_date' => Carbon::parse('2026-10-12 00:00:00')])->save();
        Carbon::setTestNow(Carbon::parse('2026-10-12 06:00:00'));

        CheckExpiringAdsJob::dispatchSync();

        Notification::assertSentTimes(AdExpiringNotification::class, 2);
    }

    public function test_a_retired_ad_is_not_also_warned_about(): void
    {
        Notification::fake();
        Cache::flush();

        $ad = $this->makeAd('2026-10-01 00:00:00');

        CheckExpiringAdsJob::dispatchSync();

        $this->assertSame(AdStatus::Expired, $ad->fresh()->status);
        Notification::assertNothingSent();
    }

    /**
     * The automatic retirement uses the Phase 13 transition map, so it can only ever
     * produce a declared state.
     */
    public function test_retirement_only_applies_to_a_status_that_permits_it(): void
    {
        Notification::fake();

        // Pending has no `expire` edge, so this ad must be left alone.
        $ad = $this->makeAd('2026-10-01 00:00:00', ['status' => AdStatus::Pending]);

        CheckExpiringAdsJob::dispatchSync();

        $this->assertSame(AdStatus::Pending, $ad->fresh()->status);
    }

    public function test_the_expiring_mail_reports_the_effective_end(): void
    {
        $ad = $this->makeAd('2026-10-05 00:00:00');

        $mail = (new AdExpiringNotification($ad))->toMail(Notification::route('mail', 'ops@example.test'));
        $rendered = (string) json_encode($mail->toArray());

        // The following midnight, not 2026-10-05 00:00:00.
        $this->assertStringContainsString('2026-10-06 00:00:00', $rendered);
    }

    public function test_an_ad_with_no_end_date_is_never_retired_or_warned(): void
    {
        Notification::fake();

        $ad = $this->makeAd(null);

        CheckExpiringAdsJob::dispatchSync();

        $this->assertSame(AdStatus::Active, $ad->fresh()->status);
        Notification::assertNothingSent();
    }
}
