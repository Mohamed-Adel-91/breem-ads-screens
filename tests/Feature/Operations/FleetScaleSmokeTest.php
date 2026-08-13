<?php

namespace Tests\Feature\Operations;

use App\Enums\AdStatus;
use App\Enums\ScreenStatus;
use App\Jobs\CheckScreenHealthJob;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Admin;
use App\Models\Place;
use App\Models\PlaybackLog;
use App\Models\Screen;
use App\Models\ScreenLog;
use App\Models\User;
use App\Services\Reports\ReportGenerationService;
use App\Services\Screen\HeartbeatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\SignsScreenRequests;
use Tests\TestCase;

/**
 * Phase 15 — fleet-scale smoke test.
 *
 * A hundred screens, five advertisements each, a schedule per assignment and a week
 * of heartbeat history. Not a benchmark: the numbers below are QUERY COUNTS, which are
 * the same on SQLite and MySQL and are what actually distinguishes a flat query from
 * an N+1. Wall-clock on a laptop proves nothing about a production host, so nothing
 * asserts on it.
 *
 * The bounds are deliberately loose — a few queries either way is a refactor, not a
 * regression. What each one forbids is growth PROPORTIONAL to the fleet, which is the
 * failure mode that only appears once real screens exist.
 */
class FleetScaleSmokeTest extends TestCase
{
    use RefreshDatabase;
    use SignsScreenRequests;

    private const FLEET = 100;
    private const ADS_PER_SCREEN = 5;

    private Admin $admin;
    private Carbon $now;

    /** @var array<int, Screen> */
    private array $screens = [];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['reports.view', 'reports.generate'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Scale',
            'last_name' => 'Tester',
            'email' => 'fleet-scale@example.com',
            'password' => 'password',
            'mobile' => '9700000001',
        ]);
        $this->admin->givePermissionTo(['reports.view', 'reports.generate']);

        $this->now = Carbon::create(2026, 9, 10, 12, 0, 0);
        Carbon::setTestNow($this->now);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Count the queries a closure issues.
     *
     * @return array{queries: int, ms: float, peak_mb: float}
     */
    private function measure(callable $work): array
    {
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $before = memory_get_peak_usage(true);
        $start = microtime(true);

        $work();

        return [
            'queries' => $queries,
            'ms' => round((microtime(true) - $start) * 1000, 1),
            'peak_mb' => round((memory_get_peak_usage(true) - $before) / 1048576, 1),
        ];
    }

    /**
     * Build the fleet with bulk inserts, so setup cost is not what is measured.
     */
    private function buildFleet(): void
    {
        $place = Place::factory()->create(['name' => ['en' => 'Scale Mall']]);
        $owner = User::factory()->create();

        $screenRows = [];
        for ($i = 1; $i <= self::FLEET; $i++) {
            $screenRows[] = [
                'place_id' => $place->id,
                'code' => sprintf('SCR-SCALE-%03d', $i),
                'device_uid' => sprintf('device-scale-%03d', $i),
                'status' => ScreenStatus::Online->value,
                'last_heartbeat' => $this->now->copy()->subSeconds(30),
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        Screen::insert($screenRows);
        $this->screens = Screen::orderBy('id')->get()->all();

        $adRows = [];
        for ($a = 1; $a <= self::ADS_PER_SCREEN; $a++) {
            $adRows[] = [
                'title' => json_encode(['en' => "Scale Creative {$a}"]),
                'file_path' => "upload/ads/scale-{$a}.mp4",
                'file_type' => 'video',
                'duration_seconds' => 15 + $a,
                'status' => AdStatus::Active->value,
                'created_by' => $owner->id,
                'start_date' => $this->now->copy()->subDays(30),
                'end_date' => $this->now->copy()->addDays(30),
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        Ad::insert($adRows);
        $ads = Ad::orderBy('id')->get();

        $assignments = [];
        $schedules = [];
        foreach ($this->screens as $screen) {
            foreach ($ads as $order => $ad) {
                $assignments[] = [
                    'ad_id' => $ad->id,
                    'screen_id' => $screen->id,
                    'play_order' => $order + 1,
                ];
                $schedules[] = [
                    'ad_id' => $ad->id,
                    'screen_id' => $screen->id,
                    'start_time' => $this->now->copy()->subHours(2),
                    'end_time' => $this->now->copy()->addHours(2),
                    'is_active' => true,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];
            }
        }

        foreach (array_chunk($assignments, 500) as $chunk) {
            DB::table('ad_screen')->insert($chunk);
        }
        foreach (array_chunk($schedules, 500) as $chunk) {
            AdSchedule::insert($chunk);
        }
    }

    /**
     * A week of status history plus playback, for the report measurements.
     */
    private function buildHistory(int $logsPerScreen = 20, int $playsPerScreen = 10): void
    {
        $ads = Ad::orderBy('id')->pluck('id')->all();

        $logs = [];
        $plays = [];

        foreach ($this->screens as $screen) {
            for ($i = 0; $i < $logsPerScreen; $i++) {
                $logs[] = [
                    'screen_id' => $screen->id,
                    'status' => $i % 5 === 0 ? ScreenStatus::Offline->value : ScreenStatus::Online->value,
                    'reported_at' => $this->now->copy()->subDays(7)->addHours($i * 8),
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];
            }

            for ($p = 0; $p < $playsPerScreen; $p++) {
                $plays[] = [
                    'screen_id' => $screen->id,
                    'ad_id' => $ads[$p % count($ads)],
                    'played_at' => $this->now->copy()->subDays(3)->addMinutes($p * 7),
                    'duration' => 15,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];
            }
        }

        foreach (array_chunk($logs, 500) as $chunk) {
            ScreenLog::insert($chunk);
        }
        foreach (array_chunk($plays, 500) as $chunk) {
            PlaybackLog::insert($chunk);
        }
    }

    // ------------------------------------------------------------ the measurements

    public function test_the_offline_sweep_stays_flat_across_a_whole_stale_fleet(): void
    {
        Notification::fake();

        $this->buildFleet();

        // Every screen goes silent at once: the worst realistic case, a site outage.
        Screen::query()->update(['last_heartbeat' => $this->now->copy()->subHours(2)]);

        $result = $this->measure(function (): void {
            (new CheckScreenHealthJob())->handle(app(HeartbeatService::class));
        });

        fwrite(STDERR, sprintf(
            "\n  [scale] offline sweep, %d stale screens: %d queries, %sms, +%sMB peak\n",
            self::FLEET,
            $result['queries'],
            $result['ms'],
            $result['peak_mb']
        ));

        $this->assertSame(
            0,
            Screen::where('status', ScreenStatus::Online->value)->count(),
            'Every stale screen must be transitioned.'
        );

        // Per screen the sweep does a bounded amount of work — a status write and a log
        // row — so the count is proportional but the CONSTANT must stay small. A
        // reintroduced N+1 on the `place` relation would push this past the bound.
        $this->assertLessThan(
            self::FLEET * 8,
            $result['queries'],
            'The sweep must not do unbounded per-screen work.'
        );

        $this->assertLessThan(
            128,
            $result['peak_mb'],
            'Streaming the fleet must keep memory bounded.'
        );
    }

    public function test_playlist_generation_is_flat_regardless_of_fleet_size(): void
    {
        $this->buildFleet();

        $screen = $this->screens[0];
        $creds = $this->pairScreen($screen, $screen->device_uid);

        // Warm nothing: the cold path is the one that matters.
        $result = $this->measure(function () use ($screen, $creds): void {
            $this->deviceGet(
                route('api.v1.screens.playlist', ['screen' => $screen->id]),
                $creds
            )->assertOk();
        });

        fwrite(STDERR, sprintf(
            "  [scale] cold playlist for 1 of %d screens (%d ads): %d queries, %sms\n",
            self::FLEET,
            self::ADS_PER_SCREEN,
            $result['queries'],
            $result['ms']
        ));

        // One device's playlist must never scale with the fleet.
        $this->assertLessThan(
            30,
            $result['queries'],
            'Playlist generation must be flat, not fleet-proportional.'
        );
    }

    public function test_a_playback_report_over_the_whole_fleet_is_aggregated_in_sql(): void
    {
        $this->buildFleet();
        $this->buildHistory();

        $service = app(ReportGenerationService::class);
        $payload = null;

        $result = $this->measure(function () use ($service, &$payload): void {
            $payload = $service->build('playback', [
                'from_date' => $this->now->copy()->subDays(7)->toDateString(),
                'to_date' => $this->now->toDateString(),
            ]);
        });

        $rows = PlaybackLog::count();

        fwrite(STDERR, sprintf(
            "  [scale] playback report over %d playback rows: %d queries, %sms, +%sMB peak\n",
            $rows,
            $result['queries'],
            $result['ms'],
            $result['peak_mb']
        ));

        $this->assertSame(self::ADS_PER_SCREEN, count($payload['rows']));
        $this->assertSame($rows, $payload['summary']['plays']);

        // Three aggregate queries plus the titles lookup, whatever the row count. This
        // is the assertion that would fail if the old hydrate-everything version
        // returned.
        $this->assertLessThan(
            12,
            $result['queries'],
            'Playback totals must be aggregated in SQL, not in PHP.'
        );
        $this->assertLessThan(
            96,
            $result['peak_mb'],
            'No report may hydrate every log row.'
        );
    }

    public function test_an_uptime_report_over_the_whole_fleet_stays_chunked(): void
    {
        $this->buildFleet();
        $this->buildHistory();

        $service = app(ReportGenerationService::class);
        $payload = null;

        $result = $this->measure(function () use ($service, &$payload): void {
            $payload = $service->build('screen-uptime', [
                'from_date' => $this->now->copy()->subDays(7)->toDateString(),
                'to_date' => $this->now->toDateString(),
            ]);
        });

        fwrite(STDERR, sprintf(
            "  [scale] uptime report over %d screens / %d log rows: %d queries, %sms, +%sMB peak\n",
            self::FLEET,
            ScreenLog::count(),
            $result['queries'],
            $result['ms'],
            $result['peak_mb']
        ));

        $this->assertCount(self::FLEET, $payload['rows']);
        $this->assertNotNull($payload['summary']['average_availability']);

        // Availability is a timeline walk, so it is per screen by definition — the
        // bound is on the CONSTANT per screen, not on the shape.
        $this->assertLessThan(
            self::FLEET * 5,
            $result['queries'],
            'Availability must stay one bounded walk per screen.'
        );
        $this->assertLessThan(
            128,
            $result['peak_mb'],
            'chunkById must keep the fleet out of memory all at once.'
        );
    }

    public function test_the_reports_index_does_not_grow_with_the_number_of_reports(): void
    {
        $this->buildFleet();

        $reportRows = [];
        for ($i = 1; $i <= 40; $i++) {
            $reportRows[] = [
                'name' => "Scale Report {$i}",
                'type' => 'playback',
                'filters' => json_encode([]),
                // A realistically heavy snapshot, which the index must not read.
                'data' => json_encode(['rows' => array_fill(0, 200, [
                    'ad_id' => 1, 'ad_title' => 'x', 'plays' => 2, 'total_duration' => 30, 'screens' => ['a', 'b'],
                ])]),
                'generated_by' => $this->admin->id,
                'created_at' => $this->now->copy()->subMinutes($i),
                'updated_at' => $this->now,
            ];
        }
        DB::table('reports')->insert($reportRows);

        $result = $this->measure(function (): void {
            $this->actingAs($this->admin, 'admin')
                ->get(route('admin.reports.index', ['lang' => 'en']))
                ->assertOk();
        });

        fwrite(STDERR, sprintf(
            "  [scale] reports index, 40 heavy snapshots: %d queries, %sms, +%sMB peak\n",
            $result['queries'],
            $result['ms'],
            $result['peak_mb']
        ));

        $this->assertLessThan(
            40,
            $result['queries'],
            'The index must not query per listed report.'
        );
    }

    public function test_shared_view_data_is_resolved_once_per_request_not_once_per_component(): void
    {
        // The `View::composer('*')` in AppServiceProvider runs for every template,
        // partial and component a page renders. Its SEO lookup is keyed on the route
        // name, which cannot change mid-request, so it must be resolved once. This test
        // measured 100 identical `seo_metas` selects on this page before Phase 15.
        $this->buildFleet();

        \App\Models\SeoMeta::create([
            'page' => 'admin.reports.index',
            'title' => ['en' => 'Reports'],
        ]);

        $seoQueries = 0;
        DB::listen(function ($query) use (&$seoQueries): void {
            if (str_contains(strtolower($query->sql), 'seo_metas')) {
                $seoQueries++;
            }
        });

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index', ['lang' => 'en']));

        $response->assertOk();

        fwrite(STDERR, sprintf(
            "  [scale] seo_metas lookups on a page rendering %d screen components: %d\n",
            self::FLEET,
            $seoQueries
        ));

        $this->assertSame(
            1,
            $seoQueries,
            'Route-keyed shared view data must be resolved once per request.'
        );

        // Memoisation must still deliver the row, not a null it cached too eagerly.
        $this->assertNotNull($response->viewData('meta'));
        $this->assertSame('admin.reports.index', $response->viewData('meta')->page);
    }

    public function test_a_route_without_seo_metadata_is_also_memoised(): void
    {
        // The null result is the common case, and an isset()-based memo check would
        // miss it and re-query on every single component.
        $this->buildFleet();

        $seoQueries = 0;
        DB::listen(function ($query) use (&$seoQueries): void {
            if (str_contains(strtolower($query->sql), 'seo_metas')) {
                $seoQueries++;
            }
        });

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index', ['lang' => 'en']))
            ->assertOk();

        $this->assertSame(
            1,
            $seoQueries,
            'A missing SEO row must be memoised too, or nothing is saved on most pages.'
        );
    }
}
