<?php

namespace Tests\Feature\Screens;

use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Jobs\CheckScreenHealthJob;
use App\Models\Place;
use App\Models\Screen;
use App\Notifications\ScreenOfflineNotification;
use App\Services\Screen\HeartbeatService;
use App\Support\ScreenHealth;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Offline detection.
 *
 * Before Phase 11 none of this ran. The only registered scheduler entry invoked
 * `screens:check-status`, a command that has never existed in this repository,
 * so it failed on every tick; CheckScreenHealthJob was registered in
 * app/Console/Kernel.php, which Laravel 12 never binds. A screen that died
 * stayed "online" forever.
 */
class OfflineDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeScreen(array $overrides = []): Screen
    {
        $place = Place::create([
            'name' => ['en' => 'Sweep Hall'],
            'address' => ['en' => '2 Silence Road'],
            'type' => PlaceType::Other,
        ]);

        return Screen::create(array_merge([
            'place_id' => $place->id,
            'code' => 'screen-'.Str::random(8),
            'device_uid' => 'device-'.Str::random(8),
            'status' => ScreenStatus::Online,
            'last_heartbeat' => now(),
        ], $overrides));
    }

    private function sweep(): void
    {
        app(CheckScreenHealthJob::class)->handle(app(HeartbeatService::class));
    }

    /**
     * CheckScreenHealthJob resolves its recipient from `admin.email`, which is
     * unset by default. Tests that assert on notifications must configure one —
     * see test_offline_alerts_are_silently_dropped_without_a_recipient.
     */
    private function withAlertRecipient(): void
    {
        config(['admin.email' => 'ops@example.test']);
    }

    // --------------------------------------------------------------- detection

    public function test_a_screen_with_a_fresh_heartbeat_stays_online(): void
    {
        Notification::fake();

        $screen = $this->makeScreen(['last_heartbeat' => now()->subSeconds(5)]);

        $this->sweep();

        $this->assertSame(ScreenStatus::Online, $screen->fresh()->status);
        $this->assertSame(0, $screen->logs()->count());
        Notification::assertNothingSent();
    }

    public function test_a_silent_screen_becomes_offline(): void
    {
        Notification::fake();

        $screen = $this->makeScreen([
            'last_heartbeat' => now()->subSeconds(ScreenHealth::offlineAfter() + 60),
        ]);
        $staleHeartbeat = $screen->last_heartbeat;

        $this->sweep();

        $screen->refresh();
        $this->assertSame(ScreenStatus::Offline, $screen->status);

        // The evidence of when the device was last heard from survives.
        $this->assertTrue($screen->last_heartbeat->equalTo($staleHeartbeat));

        $log = $screen->logs()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame(ScreenStatus::Offline, $log->status);
    }

    public function test_the_threshold_boundary_is_deterministic(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 4, 1, 9, 0, 0));
        $after = ScreenHealth::offlineAfter();

        // Exactly at the threshold: still considered reachable.
        $onBoundary = $this->makeScreen(['last_heartbeat' => $now->copy()->subSeconds($after)]);
        // One second past it: stale.
        $pastBoundary = $this->makeScreen(['last_heartbeat' => $now->copy()->subSeconds($after + 1)]);

        $this->sweep();

        $this->assertSame(ScreenStatus::Online, $onBoundary->fresh()->status);
        $this->assertSame(ScreenStatus::Offline, $pastBoundary->fresh()->status);
    }

    public function test_the_sweep_is_idempotent_across_repeated_runs(): void
    {
        Notification::fake();

        $this->withAlertRecipient();

        $screen = $this->makeScreen([
            'last_heartbeat' => now()->subSeconds(ScreenHealth::offlineAfter() + 60),
        ]);

        $this->sweep();
        $this->sweep();
        $this->sweep();

        // One transition, one log, one notification — not one per tick.
        $this->assertSame(1, $screen->logs()->count());
        Notification::assertSentTimes(ScreenOfflineNotification::class, 1);
    }

    public function test_a_screen_under_maintenance_is_never_swept_offline(): void
    {
        Notification::fake();

        $screen = $this->makeScreen([
            'status' => ScreenStatus::Maintenance,
            'last_heartbeat' => now()->subDays(3),
        ]);

        $this->sweep();

        $this->assertSame(
            ScreenStatus::Maintenance,
            $screen->fresh()->status,
            'Maintenance means operators own the screen, so connectivity alerting is suppressed.'
        );
        $this->assertSame(0, $screen->logs()->count());
        Notification::assertNothingSent();
    }

    public function test_a_screen_that_never_reported_is_not_transitioned(): void
    {
        Notification::fake();

        // A screen created but never paired has no heartbeat to go stale. It
        // starts offline and there is nothing to detect.
        $screen = $this->makeScreen(['status' => ScreenStatus::Offline, 'last_heartbeat' => null]);

        $this->sweep();

        $this->assertSame(ScreenStatus::Offline, $screen->fresh()->status);
        $this->assertSame(0, $screen->logs()->count());
    }

    public function test_only_the_stale_screens_in_a_mixed_fleet_are_transitioned(): void
    {
        Notification::fake();

        $this->withAlertRecipient();

        $fresh = $this->makeScreen(['last_heartbeat' => now()]);
        $stale = $this->makeScreen(['last_heartbeat' => now()->subSeconds(ScreenHealth::offlineAfter() + 10)]);
        $maintenance = $this->makeScreen([
            'status' => ScreenStatus::Maintenance,
            'last_heartbeat' => now()->subDays(1),
        ]);

        $this->sweep();

        $this->assertSame(ScreenStatus::Online, $fresh->fresh()->status);
        $this->assertSame(ScreenStatus::Offline, $stale->fresh()->status);
        $this->assertSame(ScreenStatus::Maintenance, $maintenance->fresh()->status);
        Notification::assertSentTimes(ScreenOfflineNotification::class, 1);
    }

    /**
     * KNOWN GAP, pinned rather than fixed here.
     *
     * CheckScreenHealthJob builds its recipient from `admin.email` (ADMIN_EMAIL).
     * When that is unset it has nobody to notify and returns silently — the
     * screen is still correctly transitioned to offline, but no one is told.
     * Deployments must set ADMIN_EMAIL; see the deployment runbook.
     *
     * The detection itself is what this phase makes trustworthy. Reworking
     * notification delivery is out of scope, so this test exists to make the
     * behaviour visible instead of surprising.
     */
    public function test_offline_alerts_are_silently_dropped_without_a_recipient(): void
    {
        Notification::fake();
        config(['admin.email' => null]);

        $screen = $this->makeScreen([
            'last_heartbeat' => now()->subSeconds(ScreenHealth::offlineAfter() + 60),
        ]);

        $this->sweep();

        // Detection still works...
        $this->assertSame(ScreenStatus::Offline, $screen->fresh()->status);
        // ...but nobody hears about it.
        Notification::assertNothingSent();
    }

    // ------------------------------------------------------------- thresholds

    public function test_the_offline_threshold_is_always_greater_than_the_heartbeat_interval(): void
    {
        // A threshold at or below the cadence would mark healthy screens offline
        // between two perfectly on-time reports.
        foreach ([10, 60, 300] as $interval) {
            config(['services.screens.heartbeat_interval' => $interval, 'services.screens.offline_after' => null]);
            $this->assertGreaterThan($interval, ScreenHealth::offlineAfter());
        }

        // An explicitly configured value that is too small is floored, not obeyed.
        config(['services.screens.heartbeat_interval' => 60, 'services.screens.offline_after' => 10]);
        $this->assertSame(61, ScreenHealth::offlineAfter());
    }

    public function test_the_offline_threshold_is_configurable(): void
    {
        config(['services.screens.heartbeat_interval' => 60, 'services.screens.offline_after' => 900]);

        $this->assertSame(900, ScreenHealth::offlineAfter());
    }

    // -------------------------------------------------------------- scheduling

    /**
     * A correct job that nobody runs is not offline detection. This asserts the
     * task is genuinely registered on the scheduler the application boots.
     */
    public function test_the_offline_sweep_is_registered_on_the_scheduler(): void
    {
        $events = app(Schedule::class)->events();

        $sweep = collect($events)->first(fn ($event) => $event->description !== null
            && str_contains((string) $event->description, 'Mark screens offline'));

        $this->assertNotNull(
            $sweep,
            'The offline-detection task is missing from the schedule. '
                .'Registered: '.collect($events)->map(fn ($e) => (string) $e->description)->implode(', ')
        );

        $this->assertInstanceOf(CallbackEvent::class, $sweep);
        $this->assertSame('* * * * *', $sweep->expression, 'The sweep should run every minute.');
    }

    public function test_no_phantom_command_remains_on_the_scheduler(): void
    {
        // `screens:check-status` was scheduled for the life of the project and
        // has never existed as a command.
        foreach (app(Schedule::class)->events() as $event) {
            $this->assertStringNotContainsString('screens:check-status', (string) $event->command);
            $this->assertStringNotContainsString('screens:check-status', (string) $event->description);
        }
    }
}
