<?php

namespace Tests\Feature\Operations;

use App\Enums\AdStatus;
use App\Enums\ScreenStatus;
use App\Http\Requests\Admin\Reports\GenerateReportRequest;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Place;
use App\Models\PlaybackLog;
use App\Models\Report;
use App\Models\Screen;
use App\Models\ScreenLog;
use App\Models\User;
use App\Services\Monitoring\ScreenAvailabilityService;
use App\Services\Reports\ReportGenerationService;
use App\Support\ReportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 14 — report generation.
 *
 * Two problems, one of them a scaling wall:
 *
 *   1. **Everything was hydrated.** `PlaybackLog::with(['ad','screen'])->get()` and
 *      `ScreenLog::with(['screen.place'])->get()` loaded every log row in the period,
 *      with relations, in order to produce a handful of totals. A week of a modest
 *      fleet is hundreds of thousands of `screen_logs` rows.
 *   2. **Uptime was measured wrongly.** The report counted online and offline *events*
 *      — the exact event-ratio mistake Phase 11 removed from Monitoring — so
 *      Monitoring and the report could report different availability for the same
 *      screen and window.
 */
class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Screen $screenA;
    private Screen $screenB;
    private Ad $adA;
    private Ad $adB;
    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['reports.view', 'reports.generate'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Report',
            'last_name' => 'Tester',
            'email' => 'report-generation@example.com',
            'password' => 'password',
            'mobile' => '9400000001',
        ]);
        $this->admin->givePermissionTo(['reports.view', 'reports.generate']);

        $this->now = Carbon::create(2026, 8, 10, 12, 0, 0);
        Carbon::setTestNow($this->now);

        $place = Place::factory()->create(['name' => ['en' => 'Report Plaza']]);

        $this->screenA = Screen::factory()->create([
            'place_id' => $place->id,
            'code' => 'SCR-RPT-A',
            'status' => ScreenStatus::Online->value,
        ]);
        $this->screenB = Screen::factory()->create([
            'place_id' => $place->id,
            'code' => 'SCR-RPT-B',
            'status' => ScreenStatus::Online->value,
        ]);

        $creator = User::factory()->create();

        $this->adA = Ad::create([
            'title' => ['en' => 'Alpha Creative'],
            'file_path' => 'upload/ads/a.mp4',
            'file_type' => 'video',
            'duration_seconds' => 20,
            'status' => AdStatus::Active,
            'created_by' => $creator->id,
        ]);
        $this->adB = Ad::create([
            'title' => ['en' => 'Beta Creative'],
            'file_path' => 'upload/ads/b.mp4',
            'file_type' => 'video',
            'duration_seconds' => 30,
            'status' => AdStatus::Active,
            'created_by' => $creator->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ------------------------------------------------------------------- helpers

    private function service(): ReportGenerationService
    {
        return app(ReportGenerationService::class);
    }

    private function playback(Ad $ad, Screen $screen, string $moment, int $duration = 20): PlaybackLog
    {
        return PlaybackLog::create([
            'screen_id' => $screen->id,
            'ad_id' => $ad->id,
            'played_at' => Carbon::parse($moment),
            'duration' => $duration,
        ]);
    }

    private function screenLog(Screen $screen, string $status, string $moment): ScreenLog
    {
        return ScreenLog::create([
            'screen_id' => $screen->id,
            'status' => $status,
            'reported_at' => Carbon::parse($moment),
        ]);
    }

    // ------------------------------------------------------------- type registry

    public function test_the_registry_and_the_form_request_can_never_drift(): void
    {
        $this->assertSame(
            ReportType::supported(),
            GenerateReportRequest::TYPES,
            'GenerateReportRequest::TYPES must mirror ReportType::supported() exactly.'
        );
    }

    public function test_every_supported_type_can_actually_be_generated(): void
    {
        foreach (ReportType::supported() as $type) {
            $payload = $this->service()->build($type, []);

            $this->assertArrayHasKey('rows', $payload, "[{$type}] produced no rows key.");
            $this->assertArrayHasKey('summary', $payload);
            $this->assertArrayHasKey('period', $payload);
            $this->assertSame(ReportGenerationService::SCHEMA_VERSION, $payload['schema_version']);
        }
    }

    /**
     * The old `match` silently defaulted an unrecognised type to the playback builder,
     * so a report could claim one thing and contain another.
     */
    public function test_an_unsupported_type_is_refused_rather_than_silently_substituted(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service()->build('some-retired-format', []);
    }

    public function test_legacy_type_values_resolve_to_their_canonical_meaning(): void
    {
        $this->assertSame(ReportType::PLAYBACK, ReportType::canonical('performance'));
        $this->assertSame(ReportType::SCREEN_UPTIME, ReportType::canonical('availability'));

        $this->assertTrue(ReportType::isLegacy('performance'));
        $this->assertTrue(ReportType::isPresentable('availability'));

        // But they are not offered for new generation.
        $this->assertFalse(ReportType::isSupported('performance'));
        $this->assertFalse(ReportType::isSupported('availability'));
    }

    public function test_a_legacy_type_is_rejected_by_the_generate_endpoint(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.reports.generate', ['lang' => 'en']), [
                'name' => 'Stale Type',
                'type' => 'performance',
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame(0, Report::count());
    }

    // ------------------------------------------------------ playback aggregation

    public function test_the_playback_report_aggregates_in_the_database(): void
    {
        $this->playback($this->adA, $this->screenA, '2026-08-05 10:00:00', 20);
        $this->playback($this->adA, $this->screenA, '2026-08-05 11:00:00', 25);
        $this->playback($this->adA, $this->screenB, '2026-08-06 10:00:00', 15);
        $this->playback($this->adB, $this->screenB, '2026-08-06 12:00:00', 30);

        $payload = $this->service()->build(ReportType::PLAYBACK, [
            'from_date' => '2026-08-01',
            'to_date' => '2026-08-09',
        ]);

        $rows = collect($payload['rows'])->keyBy('ad_id');

        $this->assertSame(3, $rows[$this->adA->id]['plays']);
        $this->assertSame(60, $rows[$this->adA->id]['total_duration']);
        $this->assertEqualsCanonicalizing(['SCR-RPT-A', 'SCR-RPT-B'], $rows[$this->adA->id]['screens']);
        $this->assertSame('Alpha Creative', $rows[$this->adA->id]['ad_title']);

        $this->assertSame(1, $rows[$this->adB->id]['plays']);
        $this->assertSame(30, $rows[$this->adB->id]['total_duration']);

        $this->assertSame(4, $payload['summary']['plays']);
        $this->assertSame(90, $payload['summary']['total_duration']);
        $this->assertSame(2, $payload['summary']['advertisements']);
    }

    /**
     * PART 6 / PART 53 — the query count must not grow with the number of log rows,
     * which is the property that made the old implementation unusable. Asserting the
     * shape of the work rather than a memory figure keeps this stable across
     * environments.
     */
    public function test_the_playback_query_count_does_not_grow_with_the_log_volume(): void
    {
        $measure = function (int $logCount): int {
            PlaybackLog::query()->delete();

            $rows = [];
            for ($i = 0; $i < $logCount; $i++) {
                $rows[] = [
                    'screen_id' => $i % 2 === 0 ? $this->screenA->id : $this->screenB->id,
                    'ad_id' => $i % 3 === 0 ? $this->adA->id : $this->adB->id,
                    'played_at' => $this->now->copy()->subDays(3)->addMinutes($i),
                    'duration' => 20,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];
            }
            PlaybackLog::insert($rows);

            $queries = 0;
            DB::listen(function () use (&$queries): void {
                $queries++;
            });

            $this->service()->build(ReportType::PLAYBACK, [
                'from_date' => $this->now->copy()->subDays(7)->toDateString(),
                'to_date' => $this->now->toDateString(),
            ]);

            return $queries;
        };

        $small = $measure(20);
        $large = $measure(2000);

        $this->assertSame(
            $small,
            $large,
            "Generation is not flat: {$small} queries for 20 logs, {$large} for 2000."
        );
        $this->assertLessThanOrEqual(5, $large, 'Playback generation should stay within a handful of queries.');
    }

    /**
     * The stored snapshot must be bounded by the number of advertisements, never by
     * the number of log rows.
     */
    public function test_the_stored_snapshot_is_bounded_by_ads_not_by_log_rows(): void
    {
        $rows = [];
        for ($i = 0; $i < 500; $i++) {
            $rows[] = [
                'screen_id' => $this->screenA->id,
                'ad_id' => $this->adA->id,
                'played_at' => $this->now->copy()->subDays(2)->addMinutes($i),
                'duration' => 20,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        PlaybackLog::insert($rows);

        $payload = $this->service()->build(ReportType::PLAYBACK, []);

        $this->assertCount(1, $payload['rows'], '500 logs for one ad must store one row.');
        $this->assertSame(500, $payload['rows'][0]['plays']);
        $this->assertSame(500, $payload['total_logs']);
    }

    // -------------------------------------------------------------- period bounds

    /**
     * PART 46 — `to_date` is inclusive of the whole day. `endOfDay()` would have
     * dropped the final second; a bare midnight bound drops the whole final day.
     */
    public function test_the_period_includes_the_whole_of_the_to_date(): void
    {
        $this->playback($this->adA, $this->screenA, '2026-08-05 00:00:00');
        $this->playback($this->adA, $this->screenA, '2026-08-05 23:59:59');
        $this->playback($this->adA, $this->screenA, '2026-08-06 00:00:00');

        $payload = $this->service()->build(ReportType::PLAYBACK, [
            'from_date' => '2026-08-05',
            'to_date' => '2026-08-05',
        ]);

        $this->assertSame(2, $payload['rows'][0]['plays'], 'Both entries on 5 Aug count; 6 Aug does not.');
    }

    public function test_the_period_start_is_inclusive_from_its_first_instant(): void
    {
        $this->playback($this->adA, $this->screenA, '2026-08-04 23:59:59');
        $this->playback($this->adA, $this->screenA, '2026-08-05 00:00:00');

        $payload = $this->service()->build(ReportType::PLAYBACK, ['from_date' => '2026-08-05']);

        $this->assertSame(1, $payload['rows'][0]['plays']);
    }

    public function test_the_period_and_its_timezone_are_recorded_in_the_snapshot(): void
    {
        $payload = $this->service()->build(ReportType::PLAYBACK, [
            'from_date' => '2026-08-01',
            'to_date' => '2026-08-07',
        ]);

        $this->assertSame('2026-08-01 00:00:00', $payload['period']['from']);
        $this->assertSame('2026-08-08 00:00:00', $payload['period']['until']);
        $this->assertSame('UTC', $payload['period']['timezone']);
    }

    public function test_an_inverted_date_range_is_rejected(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.reports.generate', ['lang' => 'en']), [
                'name' => 'Backwards',
                'type' => ReportType::PLAYBACK,
                'from_date' => '2026-08-10',
                'to_date' => '2026-08-01',
            ])
            ->assertSessionHasErrors('to_date');

        $this->assertSame(0, Report::count());
    }

    public function test_report_filters_are_server_validated(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.reports.generate', ['lang' => 'en']), [
                'name' => '',
                'type' => 'nonsense',
                'screen_id' => 999999,
                'ad_id' => 999999,
                'from_date' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['name', 'type', 'screen_id', 'ad_id', 'from_date']);
    }

    // -------------------------------------------------------- availability parity

    /**
     * PART 48 — Monitoring and Reports must not disagree. Both read
     * ScreenAvailabilityService; the report no longer counts events.
     */
    public function test_report_availability_matches_the_monitoring_service_exactly(): void
    {
        // A screen that was online, dropped, then came back inside the window.
        $this->screenLog($this->screenA, ScreenStatus::Online->value, '2026-08-03 00:00:00');
        $this->screenLog($this->screenA, ScreenStatus::Offline->value, '2026-08-05 00:00:00');
        $this->screenLog($this->screenA, ScreenStatus::Online->value, '2026-08-06 00:00:00');

        $from = '2026-08-04';
        $to = '2026-08-09';

        $payload = $this->service()->build(ReportType::SCREEN_UPTIME, [
            'from_date' => $from,
            'to_date' => $to,
            'screen_id' => $this->screenA->id,
        ]);

        $reportRow = collect($payload['rows'])->firstWhere('screen_id', $this->screenA->id);

        // The same window the report used: from-date start of day to the exclusive
        // bound after to-date.
        $expected = app(ScreenAvailabilityService::class)->forScreen(
            $this->screenA->fresh(),
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->startOfDay()->addDay()
        );

        $this->assertSame($expected['availability'], $reportRow['availability']);
        $this->assertSame($expected['online_seconds'], $reportRow['online_seconds']);
        $this->assertSame($expected['offline_seconds'], $reportRow['offline_seconds']);
        $this->assertSame($expected['unknown_seconds'], $reportRow['unknown_seconds']);
        $this->assertSame($expected['measured_seconds'], $reportRow['measured_seconds']);
    }

    /**
     * The specific failure the old event-ratio produced: one online log and then
     * silence scored perfectly.
     */
    public function test_a_screen_that_reported_once_and_died_does_not_score_full_availability(): void
    {
        $this->screenLog($this->screenA, ScreenStatus::Online->value, '2026-08-04 00:00:00');
        $this->screenLog($this->screenA, ScreenStatus::Offline->value, '2026-08-04 01:00:00');

        $payload = $this->service()->build(ReportType::SCREEN_UPTIME, [
            'from_date' => '2026-08-04',
            'to_date' => '2026-08-08',
            'screen_id' => $this->screenA->id,
        ]);

        $row = $payload['rows'][0];

        $this->assertNotNull($row['availability']);
        $this->assertLessThan(
            50.0,
            $row['availability'],
            'One hour online followed by days offline is not high availability.'
        );
    }

    public function test_uptime_availability_is_null_when_nothing_was_observed(): void
    {
        $payload = $this->service()->build(ReportType::SCREEN_UPTIME, [
            'from_date' => '2026-08-04',
            'to_date' => '2026-08-08',
            'screen_id' => $this->screenB->id,
        ]);

        $this->assertNull(
            $payload['rows'][0]['availability'],
            'Unobserved time must not be reported as 0% or 100%.'
        );
    }

    public function test_the_uptime_report_covers_every_screen_when_unfiltered(): void
    {
        $payload = $this->service()->build(ReportType::SCREEN_UPTIME, [
            'from_date' => '2026-08-04',
            'to_date' => '2026-08-08',
        ]);

        $this->assertEqualsCanonicalizing(
            ['SCR-RPT-A', 'SCR-RPT-B'],
            collect($payload['rows'])->pluck('screen_code')->all()
        );
        $this->assertSame(2, $payload['summary']['screens']);
    }

    // ----------------------------------------------------------- HTTP end to end

    public function test_generating_a_report_stores_the_bounded_snapshot(): void
    {
        $this->playback($this->adA, $this->screenA, '2026-08-05 10:00:00', 20);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.reports.generate', ['lang' => 'en']), [
                'name' => 'August Playback',
                'type' => ReportType::PLAYBACK,
                'from_date' => '2026-08-01',
                'to_date' => '2026-08-09',
            ])
            ->assertRedirect();

        $report = Report::firstOrFail();

        $this->assertSame(ReportType::PLAYBACK, $report->type);
        $this->assertSame($this->admin->id, $report->generated_by);
        $this->assertSame(['from_date' => '2026-08-01', 'to_date' => '2026-08-09'], $report->filters);
        $this->assertCount(1, $report->data['rows']);
        $this->assertSame(1, $report->data['summary']['plays']);
    }

    public function test_a_failed_generation_stores_no_report(): void
    {
        // A type that passes validation but is made unbuildable mid-flight is not
        // reachable through the form, so drive the service directly and assert the
        // controller's transaction contract by checking nothing is left behind.
        try {
            $this->service()->build('not-a-type', []);
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->assertSame(0, Report::count(), 'A failed generation must leave no row.');
    }

    public function test_the_report_index_paginates_and_keeps_its_filters(): void
    {
        for ($i = 0; $i < 25; $i++) {
            Report::create([
                'name' => 'Bulk Report '.$i,
                'type' => ReportType::PLAYBACK,
                'filters' => [],
                'data' => ['rows' => []],
                'generated_by' => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->admin, 'admin')->get(
            route('admin.reports.index', ['lang' => 'en', 'type' => ReportType::PLAYBACK])
        );

        $response->assertOk();

        $paginator = $response->viewData('reports');

        $this->assertSame(20, $paginator->perPage());
        $this->assertTrue($paginator->hasPages());
        $this->assertStringContainsString('type='.ReportType::PLAYBACK, $paginator->nextPageUrl());
    }

    public function test_the_index_only_offers_generatable_types(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index', ['lang' => 'en']));

        $this->assertSame(ReportType::supported(), $response->viewData('types'));
    }

    public function test_the_csv_export_streams_the_snapshot_rows(): void
    {
        $this->playback($this->adA, $this->screenA, '2026-08-05 10:00:00', 20);
        $this->playback($this->adB, $this->screenB, '2026-08-05 11:00:00', 30);

        $report = Report::create([
            'name' => 'Export Me',
            'type' => ReportType::PLAYBACK,
            'filters' => [],
            'data' => $this->service()->build(ReportType::PLAYBACK, []),
            'generated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(
            route('admin.reports.download', ['lang' => 'en', 'report' => $report->id])
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        // fputcsv quotes any field containing a space, so the header line reads
        // "Ad ID","Ad Title",Plays,...
        $this->assertStringContainsString('"Ad ID","Ad Title",Plays', $csv);
        $this->assertStringContainsString('Alpha Creative', $csv);
        $this->assertStringContainsString('Beta Creative', $csv);
    }

    public function test_the_uptime_export_uses_availability_columns(): void
    {
        $report = Report::create([
            'name' => 'Uptime Export',
            'type' => ReportType::SCREEN_UPTIME,
            'filters' => [],
            'data' => $this->service()->build(ReportType::SCREEN_UPTIME, []),
            'generated_by' => $this->admin->id,
        ]);

        $csv = $this->actingAs($this->admin, 'admin')->get(
            route('admin.reports.download', ['lang' => 'en', 'report' => $report->id])
        )->assertOk()->streamedContent();

        $this->assertStringContainsString('Availability %', $csv);
        // The event columns the old report exported are gone.
        $this->assertStringNotContainsString('Online Events', $csv);
    }

    /**
     * A legacy-typed report exports with its canonical column set rather than falling
     * through to the playback layout.
     */
    public function test_a_legacy_typed_report_exports_with_canonical_headers(): void
    {
        $report = Report::create([
            'name' => 'Legacy Availability',
            'type' => 'availability',
            'filters' => [],
            'data' => ['rows' => [['screen_id' => 1, 'screen_code' => 'SCR-OLD', 'availability' => 98.5]]],
            'generated_by' => null,
        ]);

        $csv = $this->actingAs($this->admin, 'admin')->get(
            route('admin.reports.download', ['lang' => 'en', 'report' => $report->id])
        )->assertOk()->streamedContent();

        $this->assertStringContainsString('Availability %', $csv);
        $this->assertStringContainsString('SCR-OLD', $csv);
    }

    // ------------------------------------------------------------ no view queries

    /**
     * PART 49 — the show page must render from the snapshot alone. If it queried logs,
     * a report would change after retention pruned them.
     */
    public function test_the_show_page_runs_no_log_queries(): void
    {
        $report = Report::create([
            'name' => 'Static Snapshot',
            'type' => ReportType::PLAYBACK,
            'filters' => [],
            'data' => ['rows' => [['ad_id' => 1, 'ad_title' => 'Stored', 'plays' => 9, 'total_duration' => 90, 'screens' => []]]],
            'generated_by' => null,
        ]);

        $touched = [];
        DB::listen(function ($query) use (&$touched): void {
            $touched[] = strtolower(str_replace(['`', '"'], '', $query->sql));
        });

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.show', ['lang' => 'en', 'report' => $report->id]))
            ->assertOk()
            ->assertSee('Stored', false);

        foreach ($touched as $sql) {
            $this->assertStringNotContainsString('from playback_logs', $sql, 'The show page must not query logs.');
            $this->assertStringNotContainsString('from screen_logs', $sql, 'The show page must not query logs.');
        }
    }

    // ------------------------------------------------------------------ bilingual

    public function test_the_report_pages_render_in_both_locales(): void
    {
        $report = Report::create([
            'name' => 'Bilingual Report',
            'type' => ReportType::PLAYBACK,
            'filters' => ['from_date' => '2026-08-01'],
            'data' => ['rows' => [['ad_id' => 1, 'ad_title' => 'Alpha Creative', 'plays' => 4, 'total_duration' => 80, 'screens' => ['SCR-RPT-A']]]],
            'generated_by' => $this->admin->id,
        ]);

        foreach (['en', 'ar'] as $locale) {
            $this->actingAs($this->admin, 'admin')
                ->get(route('admin.reports.index', ['lang' => $locale]))
                ->assertOk()
                ->assertSee($locale === 'ar' ? 'dir="rtl"' : 'dir="ltr"', false);

            $this->actingAs($this->admin, 'admin')
                ->get(route('admin.reports.show', ['lang' => $locale, 'report' => $report->id]))
                ->assertOk()
                ->assertSee('Alpha Creative', false);
        }
    }
}
