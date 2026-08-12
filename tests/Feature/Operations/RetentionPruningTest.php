<?php

namespace Tests\Feature\Operations;

use App\Enums\AdStatus;
use App\Enums\ScreenStatus;
use App\Models\Ad;
use App\Models\Place;
use App\Models\PlaybackLog;
use App\Models\Report;
use App\Models\Screen;
use App\Models\ScreenLog;
use App\Models\User;
use App\Support\ReportType;
use App\Support\Retention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 14 — operational data retention.
 *
 * `screen_logs` grows at roughly `fleet size × 1440` rows a day (one per heartbeat
 * plus one per transition) and `playback_logs` can grow faster still. Nothing bounded
 * either table before this.
 *
 * The mechanism is Laravel's Prunable contract driven by config/retention.php. The
 * property that matters most is the DEFAULT: every policy is disabled unless someone
 * sets a positive number of days, and a disabled policy deletes nothing. These tables
 * hold telemetry and commercial proof-of-play, so an invented default would be an
 * invented data-loss policy.
 */
class RetentionPruningTest extends TestCase
{
    use RefreshDatabase;

    private Screen $screen;
    private Ad $ad;
    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::create(2026, 9, 20, 12, 0, 0);
        Carbon::setTestNow($this->now);

        // Disabled everywhere unless a test opts in.
        config([
            'retention.screen_logs_days' => null,
            'retention.playback_logs_days' => null,
            'retention.reports_days' => null,
        ]);

        $this->screen = Screen::factory()->create([
            'place_id' => Place::factory()->create()->id,
            'code' => 'SCR-RETENTION',
            'status' => ScreenStatus::Online->value,
        ]);

        $this->ad = Ad::create([
            'title' => ['en' => 'Retention Campaign'],
            'file_path' => 'upload/ads/retention.mp4',
            'file_type' => 'video',
            'duration_seconds' => 20,
            'status' => AdStatus::Active,
            'created_by' => User::factory()->create()->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ------------------------------------------------------------------- helpers

    private function screenLogAt(string $moment): ScreenLog
    {
        return ScreenLog::create([
            'screen_id' => $this->screen->id,
            'status' => ScreenStatus::Online->value,
            'reported_at' => Carbon::parse($moment),
        ]);
    }

    private function playbackLogAt(string $moment): PlaybackLog
    {
        return PlaybackLog::create([
            'screen_id' => $this->screen->id,
            'ad_id' => $this->ad->id,
            'played_at' => Carbon::parse($moment),
            'duration' => 20,
        ]);
    }

    private function reportCreatedAt(string $moment): Report
    {
        $report = Report::create([
            'name' => 'Snapshot '.$moment,
            'type' => ReportType::PLAYBACK,
            'filters' => [],
            'data' => ['rows' => []],
            'generated_by' => null,
        ]);

        // created_at is managed by Eloquent, so age it explicitly.
        $report->forceFill(['created_at' => Carbon::parse($moment)])->saveQuietly();

        return $report;
    }

    private function prune(bool $pretend = false): void
    {
        $this->artisan('model:prune', array_filter([
            '--model' => [ScreenLog::class, PlaybackLog::class, Report::class],
            '--pretend' => $pretend ?: null,
        ]))->assertSuccessful();
    }

    // ------------------------------------------------------------------ disabled

    public function test_retention_is_disabled_by_default(): void
    {
        foreach (Retention::policies() as $policy) {
            $this->assertFalse(Retention::enabled($policy), "[{$policy}] must be off by default.");
            $this->assertNull(Retention::days($policy));
            $this->assertNull(Retention::cutoffFor($policy));
        }
    }

    public function test_disabled_retention_deletes_nothing_however_old_the_rows_are(): void
    {
        $this->screenLogAt('2019-01-01 00:00:00');
        $this->playbackLogAt('2019-01-01 00:00:00');
        $this->reportCreatedAt('2019-01-01 00:00:00');

        $this->prune();

        $this->assertSame(1, ScreenLog::count());
        $this->assertSame(1, PlaybackLog::count());
        $this->assertSame(1, Report::count());
    }

    /**
     * Every falsy or nonsensical value means "off", never "zero days".
     *
     * @dataProvider disablingValueProvider
     */
    public function test_a_non_positive_value_disables_rather_than_deleting_everything(mixed $value): void
    {
        config(['retention.screen_logs_days' => $value]);

        $this->screenLogAt('2019-01-01 00:00:00');

        $this->assertFalse(Retention::enabled(Retention::SCREEN_LOGS));

        $this->prune();

        $this->assertSame(1, ScreenLog::count(), 'A non-positive period must never wipe the table.');
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function disablingValueProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'zero' => [0],
            'zero string' => ['0'],
            'negative' => [-30],
            'non-numeric' => ['forever'],
        ];
    }

    // -------------------------------------------------------------- screen logs

    public function test_old_screen_logs_are_pruned_and_recent_ones_are_kept(): void
    {
        config(['retention.screen_logs_days' => 30]);

        $old = $this->screenLogAt('2026-07-01 12:00:00');   // ~81 days
        $recent = $this->screenLogAt('2026-09-19 12:00:00'); // 1 day

        $this->prune();

        $this->assertNull($old->fresh(), 'A log past the retention window must be pruned.');
        $this->assertNotNull($recent->fresh(), 'A recent log must be kept.');
    }

    /**
     * The cutoff is deterministic and errs towards keeping data: a row exactly at the
     * boundary survives, because the comparison is `<`.
     */
    public function test_the_screen_log_boundary_is_deterministic(): void
    {
        config(['retention.screen_logs_days' => 30]);

        $cutoff = $this->now->copy()->subDays(30);

        $before = $this->screenLogAt($cutoff->copy()->subSecond()->toDateTimeString());
        $exactly = $this->screenLogAt($cutoff->toDateTimeString());
        $after = $this->screenLogAt($cutoff->copy()->addSecond()->toDateTimeString());

        $this->prune();

        $this->assertNull($before->fresh(), 'One second past the cutoff is eligible.');
        $this->assertNotNull($exactly->fresh(), 'Exactly at the cutoff is kept.');
        $this->assertNotNull($after->fresh(), 'Inside the window is kept.');
    }

    // ------------------------------------------------------------ playback logs

    public function test_old_playback_logs_are_pruned_and_recent_ones_are_kept(): void
    {
        config(['retention.playback_logs_days' => 90]);

        $old = $this->playbackLogAt('2026-01-01 12:00:00');
        $recent = $this->playbackLogAt('2026-09-01 12:00:00');

        $this->prune();

        $this->assertNull($old->fresh());
        $this->assertNotNull($recent->fresh());
    }

    public function test_the_playback_log_boundary_is_deterministic(): void
    {
        config(['retention.playback_logs_days' => 90]);

        $cutoff = $this->now->copy()->subDays(90);

        $before = $this->playbackLogAt($cutoff->copy()->subSecond()->toDateTimeString());
        $exactly = $this->playbackLogAt($cutoff->toDateTimeString());

        $this->prune();

        $this->assertNull($before->fresh());
        $this->assertNotNull($exactly->fresh());
    }

    // -------------------------------------------------------- per-type policies

    /**
     * Each policy is independent: enabling one must not prune another's table.
     */
    public function test_policies_are_type_specific(): void
    {
        config([
            'retention.screen_logs_days' => 7,
            'retention.playback_logs_days' => null,
            'retention.reports_days' => null,
        ]);

        $screenLog = $this->screenLogAt('2026-01-01 12:00:00');
        $playbackLog = $this->playbackLogAt('2026-01-01 12:00:00');
        $report = $this->reportCreatedAt('2026-01-01 12:00:00');

        $this->prune();

        $this->assertNull($screenLog->fresh(), 'The enabled policy prunes.');
        $this->assertNotNull($playbackLog->fresh(), 'A disabled policy must not prune.');
        $this->assertNotNull($report->fresh(), 'A disabled policy must not prune.');
    }

    public function test_different_periods_apply_to_different_tables(): void
    {
        config([
            'retention.screen_logs_days' => 10,
            'retention.playback_logs_days' => 200,
        ]);

        // 60 days old: past the 10-day screen-log window, inside the 200-day
        // playback-log window.
        $screenLog = $this->screenLogAt('2026-07-22 12:00:00');
        $playbackLog = $this->playbackLogAt('2026-07-22 12:00:00');

        $this->prune();

        $this->assertNull($screenLog->fresh());
        $this->assertNotNull($playbackLog->fresh());
    }

    // ---------------------------------------------------------------- dry run

    public function test_a_dry_run_reports_without_deleting(): void
    {
        config(['retention.screen_logs_days' => 30, 'retention.playback_logs_days' => 30]);

        $this->screenLogAt('2019-01-01 00:00:00');
        $this->playbackLogAt('2019-01-01 00:00:00');

        $this->prune(pretend: true);

        $this->assertSame(1, ScreenLog::count(), 'A dry run must not delete anything.');
        $this->assertSame(1, PlaybackLog::count());
    }

    // ------------------------------------------------------------- idempotence

    public function test_repeated_pruning_is_idempotent(): void
    {
        config(['retention.screen_logs_days' => 30]);

        $this->screenLogAt('2019-01-01 00:00:00');
        $this->screenLogAt('2019-06-01 00:00:00');
        $keep = $this->screenLogAt('2026-09-19 12:00:00');

        $this->prune();
        $afterFirst = ScreenLog::count();

        $this->prune();
        $this->prune();

        $this->assertSame(1, $afterFirst);
        $this->assertSame($afterFirst, ScreenLog::count(), 'Re-running must change nothing.');
        $this->assertNotNull($keep->fresh());
    }

    public function test_pruning_an_empty_table_is_safe(): void
    {
        config(['retention.screen_logs_days' => 1]);

        $this->prune();

        $this->assertSame(0, ScreenLog::count());
    }

    // ---------------------------------------------------- report interaction

    /**
     * PART 21 — the immutable snapshot is the point. A generated report keeps showing
     * its figures after the logs it was built from have been pruned away.
     */
    public function test_a_report_summary_survives_the_pruning_of_its_source_logs(): void
    {
        config(['retention.screen_logs_days' => 30, 'retention.playback_logs_days' => 30]);

        $this->playbackLogAt('2019-01-01 00:00:00');

        $report = Report::create([
            'name' => 'Historic Playback',
            'type' => ReportType::PLAYBACK,
            'filters' => ['from_date' => '2019-01-01', 'to_date' => '2019-01-31'],
            'data' => [
                'rows' => [['ad_id' => $this->ad->id, 'ad_title' => 'Retention Campaign', 'plays' => 41, 'total_duration' => 820, 'screens' => ['SCR-RETENTION']]],
                'summary' => ['advertisements' => 1, 'plays' => 41, 'total_duration' => 820],
            ],
            'generated_by' => null,
        ]);

        $this->prune();

        $this->assertSame(0, PlaybackLog::count(), 'The source logs are gone.');

        $fresh = $report->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame(41, $fresh->data['rows'][0]['plays'], 'The snapshot must be unchanged.');
        $this->assertSame(41, $fresh->data['summary']['plays']);
    }

    public function test_old_reports_are_pruned_only_when_their_own_policy_is_set(): void
    {
        config(['retention.reports_days' => 365]);

        $old = $this->reportCreatedAt('2024-01-01 00:00:00');
        $recent = $this->reportCreatedAt('2026-09-01 00:00:00');

        $this->prune();

        $this->assertNull($old->fresh());
        $this->assertNotNull($recent->fresh());
    }

    // ------------------------------------------------------------- scheduling

    public function test_the_prune_task_is_registered_on_the_scheduler(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('model:prune')
            ->assertSuccessful();

        // And the Phase 11 offline sweep is still there alongside it.
        $this->artisan('schedule:list')
            ->expectsOutputToContain('Mark screens offline')
            ->assertSuccessful();
    }

    public function test_pruning_does_not_touch_the_screens_or_ads_it_references(): void
    {
        config(['retention.screen_logs_days' => 1, 'retention.playback_logs_days' => 1]);

        $this->screenLogAt('2019-01-01 00:00:00');
        $this->playbackLogAt('2019-01-01 00:00:00');

        $this->prune();

        $this->assertSame(0, ScreenLog::count());
        $this->assertSame(0, PlaybackLog::count());

        // Retention removes telemetry, never the fleet or the campaign records.
        $this->assertNotNull($this->screen->fresh());
        $this->assertNotNull($this->ad->fresh());
    }
}
