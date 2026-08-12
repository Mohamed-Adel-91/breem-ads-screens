<?php

namespace Tests\Unit\Services\Monitoring;

use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Place;
use App\Models\Screen;
use App\Services\Monitoring\ScreenAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Availability is elapsed time, not a count of report events.
 *
 * The metric this replaces was `online logs / total logs`, so a screen that
 * reported online once and then died for a week scored 100%. Every test here
 * therefore asserts on durations.
 */
class ScreenAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::create(2026, 5, 8, 12, 0, 0);
        Carbon::setTestNow($this->now);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeScreen(): Screen
    {
        $place = Place::create([
            'name' => ['en' => 'Availability Hall'],
            'address' => ['en' => '3 Timeline Way'],
            'type' => PlaceType::Other,
        ]);

        return Screen::create([
            'place_id' => $place->id,
            'code' => 'screen-'.Str::random(8),
            'device_uid' => 'device-'.Str::random(8),
            'status' => ScreenStatus::Online,
            'last_heartbeat' => now(),
        ]);
    }

    private function log(Screen $screen, ScreenStatus $status, Carbon $at): void
    {
        $screen->logs()->create(['status' => $status->value, 'reported_at' => $at]);
    }

    private function service(): ScreenAvailabilityService
    {
        return app(ScreenAvailabilityService::class);
    }

    /** Days before "now", as a Carbon instant. */
    private function daysAgo(float $days): Carbon
    {
        return $this->now->copy()->subSeconds((int) round($days * 86400));
    }

    private const WEEK = 7 * 86400;

    public function test_a_screen_online_for_the_whole_period_is_one_hundred_percent(): void
    {
        $screen = $this->makeScreen();

        // One event before the window opens sets the state for the entire period.
        $this->log($screen, ScreenStatus::Online, $this->daysAgo(10));

        $result = $this->service()->forScreen($screen);

        $this->assertSame(100.0, $result['availability']);
        $this->assertSame(self::WEEK, $result['online_seconds']);
        $this->assertSame(0, $result['offline_seconds']);
        $this->assertSame(0, $result['unknown_seconds']);
    }

    public function test_a_screen_offline_for_the_whole_period_is_zero_percent(): void
    {
        $screen = $this->makeScreen();
        $this->log($screen, ScreenStatus::Offline, $this->daysAgo(10));

        $result = $this->service()->forScreen($screen);

        $this->assertSame(0.0, $result['availability']);
        $this->assertSame(self::WEEK, $result['offline_seconds']);
        $this->assertSame(0, $result['online_seconds']);
    }

    /**
     * The exact case the old metric got wrong: one online report, then silence.
     */
    public function test_a_single_online_report_followed_by_a_long_outage_is_not_one_hundred_percent(): void
    {
        $screen = $this->makeScreen();

        $this->log($screen, ScreenStatus::Online, $this->daysAgo(7));
        $this->log($screen, ScreenStatus::Offline, $this->daysAgo(6));

        $result = $this->service()->forScreen($screen);

        // Online for 1 day of 7, offline for 6.
        $this->assertEqualsWithDelta(14.29, $result['availability'], 0.01);
        $this->assertSame(86400, $result['online_seconds']);
        $this->assertSame(6 * 86400, $result['offline_seconds']);
    }

    public function test_an_offline_screen_that_recovers_is_measured_by_duration(): void
    {
        $screen = $this->makeScreen();

        $this->log($screen, ScreenStatus::Offline, $this->daysAgo(7));
        $this->log($screen, ScreenStatus::Online, $this->daysAgo(3.5));

        $result = $this->service()->forScreen($screen);

        $this->assertSame(50.0, $result['availability']);
    }

    public function test_multiple_transitions_accumulate(): void
    {
        $screen = $this->makeScreen();

        $this->log($screen, ScreenStatus::Online, $this->daysAgo(7));    // online 1 day
        $this->log($screen, ScreenStatus::Offline, $this->daysAgo(6));   // offline 1 day
        $this->log($screen, ScreenStatus::Online, $this->daysAgo(5));    // online 3 days
        $this->log($screen, ScreenStatus::Offline, $this->daysAgo(2));   // offline 1 day
        $this->log($screen, ScreenStatus::Online, $this->daysAgo(1));    // online 1 day

        $result = $this->service()->forScreen($screen);

        $this->assertSame(5 * 86400, $result['online_seconds']);
        $this->assertSame(2 * 86400, $result['offline_seconds']);
        $this->assertEqualsWithDelta(71.43, $result['availability'], 0.01);
    }

    public function test_a_transition_exactly_at_the_period_boundary_opens_the_period(): void
    {
        $screen = $this->makeScreen();

        // Written at precisely the window start.
        $this->log($screen, ScreenStatus::Online, $this->daysAgo(7));

        $result = $this->service()->forScreen($screen);

        $this->assertSame(self::WEEK, $result['online_seconds']);
        $this->assertSame(0, $result['unknown_seconds']);
        $this->assertSame(100.0, $result['availability']);
    }

    public function test_a_screen_with_no_history_has_no_availability(): void
    {
        $screen = $this->makeScreen();

        $result = $this->service()->forScreen($screen);

        $this->assertNull($result['availability']);
        $this->assertSame(self::WEEK, $result['unknown_seconds']);
        $this->assertSame(0, $result['measured_seconds']);
    }

    /**
     * Time before the first-ever report is unobserved. Counting it as online
     * would flatter a newly installed screen; counting it as offline would
     * punish one. It is excluded from the denominator and reported separately.
     */
    public function test_time_before_the_first_report_is_unknown_not_online(): void
    {
        $screen = $this->makeScreen();

        // First contact happens 2 days into a 7-day window.
        $this->log($screen, ScreenStatus::Online, $this->daysAgo(5));

        $result = $this->service()->forScreen($screen);

        $this->assertSame(2 * 86400, $result['unknown_seconds']);
        $this->assertSame(5 * 86400, $result['online_seconds']);
        $this->assertSame(100.0, $result['availability'], 'Only observed time counts toward the percentage.');
        $this->assertSame(5 * 86400, $result['measured_seconds']);
    }

    /**
     * Maintenance is planned, operator-owned downtime: neither available nor a
     * failure. It leaves the denominator entirely.
     */
    public function test_maintenance_is_excluded_from_the_denominator(): void
    {
        $screen = $this->makeScreen();

        $this->log($screen, ScreenStatus::Online, $this->daysAgo(7));        // online 2 days
        $this->log($screen, ScreenStatus::Maintenance, $this->daysAgo(5));   // maintenance 3 days
        $this->log($screen, ScreenStatus::Online, $this->daysAgo(2));        // online 2 days

        $result = $this->service()->forScreen($screen);

        $this->assertSame(4 * 86400, $result['online_seconds']);
        $this->assertSame(3 * 86400, $result['maintenance_seconds']);
        $this->assertSame(0, $result['offline_seconds']);
        $this->assertSame(4 * 86400, $result['measured_seconds']);
        $this->assertSame(100.0, $result['availability']);
    }

    public function test_a_period_entirely_in_maintenance_has_no_availability(): void
    {
        $screen = $this->makeScreen();
        $this->log($screen, ScreenStatus::Maintenance, $this->daysAgo(10));

        $result = $this->service()->forScreen($screen);

        $this->assertNull($result['availability']);
        $this->assertSame(self::WEEK, $result['maintenance_seconds']);
    }

    public function test_a_currently_online_screen_counts_up_to_now(): void
    {
        $screen = $this->makeScreen();
        $this->log($screen, ScreenStatus::Online, $this->daysAgo(1));

        $result = $this->service()->forScreen($screen);

        // The trailing segment runs to the window end, not to the last log.
        $this->assertSame(86400, $result['online_seconds']);
        $this->assertTrue($result['period_end']->equalTo($this->now));
    }

    public function test_a_currently_offline_screen_counts_the_outage_up_to_now(): void
    {
        $screen = $this->makeScreen();

        $this->log($screen, ScreenStatus::Online, $this->daysAgo(7));
        $this->log($screen, ScreenStatus::Offline, $this->daysAgo(1));

        $result = $this->service()->forScreen($screen);

        $this->assertSame(86400, $result['offline_seconds']);
        $this->assertSame(6 * 86400, $result['online_seconds']);
    }

    public function test_logs_outside_the_window_do_not_leak_into_the_totals(): void
    {
        $screen = $this->makeScreen();

        $this->log($screen, ScreenStatus::Offline, $this->daysAgo(30));
        $this->log($screen, ScreenStatus::Online, $this->daysAgo(20));

        $result = $this->service()->forScreen($screen);

        // The state carried into the window is online; nothing older is counted.
        $this->assertSame(self::WEEK, $result['online_seconds']);
        $this->assertSame(0, $result['offline_seconds']);
        $this->assertSame(self::WEEK, $result['period_seconds']);
    }

    public function test_an_explicit_window_is_honoured(): void
    {
        $screen = $this->makeScreen();
        $this->log($screen, ScreenStatus::Online, $this->daysAgo(10));

        $result = $this->service()->forScreen(
            $screen,
            $this->now->copy()->subDay(),
            $this->now->copy()
        );

        $this->assertSame(86400, $result['period_seconds']);
        $this->assertSame(86400, $result['online_seconds']);
    }

    public function test_an_inverted_window_yields_an_empty_result(): void
    {
        $screen = $this->makeScreen();
        $this->log($screen, ScreenStatus::Online, $this->daysAgo(10));

        $result = $this->service()->forScreen(
            $screen,
            $this->now->copy(),
            $this->now->copy()->subDay()
        );

        $this->assertNull($result['availability']);
        $this->assertSame(0, $result['period_seconds']);
    }
}
