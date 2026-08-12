<?php

namespace Tests\Feature\Admin;

use App\Enums\AdStatus;
use App\Enums\ScreenStatus;
use App\Http\Requests\Admin\Reports\GenerateReportRequest;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Report;
use App\Models\Screen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 7 — guards the Reports admin UI against a regression back to the
 * Vite / Tailwind / Alpine architecture, and pins the generation-form field
 * contract, the persisted report type values and the download-link contract.
 *
 * No test here changes report calculation. Reports are created directly as
 * fixtures so the generator is never exercised against a large dataset, and
 * every record lives in the isolated in-memory SQLite database.
 */
class ReportsAdminViewsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    private const FORBIDDEN_MARKERS = [
        '@vite',
        '/build/',
        'x-data=',
        'x-show=',
        'x-cloak',
        'x-transition',
        'alpinejs',
        'x-app-layout',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['reports.view', 'reports.generate'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Reports',
            'last_name' => 'Tester',
            'email' => 'reports-tester@example.com',
            'password' => 'password',
            'mobile' => '6000000001',
        ]);

        $this->admin->givePermissionTo(['reports.view', 'reports.generate']);
    }

    protected function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
    }

    protected function makePlaybackReport(array $overrides = []): Report
    {
        return Report::create(array_merge([
            'name' => 'Weekly Playback Report',
            'type' => 'playback',
            'filters' => ['from_date' => '2026-01-01', 'to_date' => '2026-01-31'],
            'data' => [
                'generated_at' => '2026-02-01 09:00:00',
                'total_logs' => 42,
                'rows' => [
                    [
                        'ad_id' => 7,
                        'ad_title' => 'Spring Promo',
                        'plays' => 12,
                        'total_duration' => 180,
                        'screens' => ['SCR-A', 'SCR-B'],
                    ],
                ],
            ],
            'generated_by' => $this->admin->id,
        ], $overrides));
    }

    protected function makeUptimeReport(array $overrides = []): Report
    {
        return Report::create(array_merge([
            'name' => 'Screen Status Report',
            'type' => 'screen-uptime',
            'filters' => ['screen_id' => 3],
            'data' => [
                'generated_at' => '2026-02-02 10:00:00',
                'total_logs' => 18,
                'rows' => [
                    [
                        'screen_id' => 3,
                        'screen_code' => 'SCR-UPTIME-1',
                        'place' => 'Grand Mall',
                        'online_events' => 15,
                        'offline_events' => 3,
                        'last_status' => ScreenStatus::Online->value,
                        'period_start' => '2026-01-01 00:00:00',
                        'period_end' => '2026-01-31 23:59:00',
                    ],
                ],
            ],
            'generated_by' => $this->admin->id,
        ], $overrides));
    }

    private function assertCanonicalStaticPage($response): void
    {
        $response->assertOk();

        $response->assertSee('admin-assets/css/breem-admin.css', false);
        $response->assertSee('class="vertical light breem-admin', false);

        foreach (self::FORBIDDEN_MARKERS as $marker) {
            $response->assertDontSee($marker, false);
        }
    }

    public static function localeProvider(): array
    {
        return [
            'english' => ['en'],
            'arabic' => ['ar'],
        ];
    }

    #[DataProvider('localeProvider')]
    public function test_reports_index_renders_the_canonical_static_layout(string $locale): void
    {
        $this->makePlaybackReport();

        $response = $this->actingAsAdmin()->get(route('admin.reports.index', ['lang' => $locale]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.reports.index');

        // Filter parameter names are part of the controller contract.
        $response->assertSee('name="search"', false);
        $response->assertSee('name="type"', false);

        $response->assertSee('Weekly Playback Report', false);
        $response->assertSee('Reports Tester', false);
    }

    #[DataProvider('localeProvider')]
    public function test_report_generation_form_preserves_the_exact_field_contract(string $locale): void
    {
        $place = Place::factory()->create(['name' => ['en' => 'Airport Hall', 'ar' => 'صالة المطار']]);
        Screen::factory()->create(['place_id' => $place->id, 'code' => 'SCR-REPORT-1']);
        Ad::create([
            'title' => ['en' => 'Reported Ad', 'ar' => 'إعلان التقرير'],
            'file_path' => 'upload/ads/creative.png',
            'file_type' => 'image',
            'duration_seconds' => 8,
            'status' => AdStatus::Active->value,
            'created_by' => User::factory()->create()->id,
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.reports.index', ['lang' => $locale]));

        $response->assertOk();

        // Exact field names expected by GenerateReportRequest.
        $response->assertSee('name="name"', false);
        $response->assertSee('id="type_select"', false);
        $response->assertSee('name="type"', false);
        $response->assertSee('name="screen_id"', false);
        $response->assertSee('name="ad_id"', false);
        $response->assertSee('name="from_date"', false);
        $response->assertSee('name="to_date"', false);
        $response->assertSee(route('admin.reports.generate', ['lang' => $locale]), false);

        // Persisted type values are submitted verbatim; only labels are translated.
        foreach (GenerateReportRequest::TYPES as $type) {
            $response->assertSee('value="' . $type . '"', false);
        }
    }

    #[DataProvider('localeProvider')]
    public function test_report_show_renders_the_canonical_static_layout(string $locale): void
    {
        $report = $this->makePlaybackReport();

        $response = $this->actingAsAdmin()->get(
            route('admin.reports.show', ['lang' => $locale, 'report' => $report->id])
        );

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.reports.show');

        // Stored JSON rows are rendered, not recomputed.
        $response->assertSee('Spring Promo', false);
        $response->assertSee('SCR-A, SCR-B', false);
        $response->assertSee('2026-02-01 09:00:00', false);

        // Applied filters are shown.
        $response->assertSee('2026-01-01', false);

        $response->assertSee(
            route('admin.reports.download', ['lang' => $locale, 'report' => $report->id]),
            false
        );
    }

    public function test_screen_uptime_report_uses_its_own_column_layout(): void
    {
        $report = $this->makeUptimeReport();

        $response = $this->actingAsAdmin()->get(
            route('admin.reports.show', ['lang' => 'en', 'report' => $report->id])
        );

        $response->assertOk();
        $response->assertSee(__('admin.reports.columns.online_events', [], 'en'), false);
        $response->assertSee(__('admin.reports.columns.period_start', [], 'en'), false);
        $response->assertSee('SCR-UPTIME-1', false);
        $response->assertSee('Grand Mall', false);

        // The playback-only column must not appear for this type.
        $response->assertDontSee(__('admin.reports.columns.total_duration', [], 'en'), false);
    }

    /**
     * UPDATED in Phase 14. `performance` and `availability` are no longer "unknown":
     * App\Support\ReportType maps them to the canonical types they always meant, so a
     * legacy row renders with the right layout instead of being flagged as
     * unrecognised. The stored value is still never rewritten.
     */
    public function test_a_stored_report_with_a_legacy_type_renders_with_its_canonical_layout(): void
    {
        $report = Report::create([
            'name' => 'Legacy Performance Report',
            'type' => 'performance',
            'filters' => [],
            'data' => ['rows' => [['ad_title' => 'Legacy Row', 'plays' => 3]]],
            'generated_by' => null,
        ]);

        $response = $this->actingAsAdmin()->get(
            route('admin.reports.show', ['lang' => 'en', 'report' => $report->id])
        );

        $response->assertOk();
        $response->assertSee('Legacy Row', false);
        $response->assertSee(__('admin.reports.system', [], 'en'), false);

        // Recognised, so no unknown-type warning.
        $response->assertDontSee(__('admin.reports.show.unknown_type_notice', [], 'en'), false);

        // Rendering never rewrites the stored value.
        $this->assertSame('performance', $report->fresh()->type);
    }

    /**
     * The safety net still has to hold for a value the registry does not know at all.
     */
    public function test_a_stored_report_with_an_unrecognised_type_still_renders_safely(): void
    {
        $report = Report::create([
            'name' => 'Mystery Report',
            'type' => 'some-retired-format',
            'filters' => [],
            'data' => ['rows' => [['ad_title' => 'Mystery Row', 'plays' => 7]]],
            'generated_by' => null,
        ]);

        $response = $this->actingAsAdmin()->get(
            route('admin.reports.show', ['lang' => 'en', 'report' => $report->id])
        );

        $response->assertOk();
        $response->assertSee('Mystery Row', false);
        $response->assertSee(__('admin.reports.show.unknown_type_notice', [], 'en'), false);

        $this->assertSame('some-retired-format', $report->fresh()->type);
    }

    public function test_a_report_with_no_rows_renders_the_empty_state(): void
    {
        $report = $this->makePlaybackReport([
            'name' => 'Empty Report',
            'data' => ['generated_at' => '2026-02-03 08:00:00', 'total_logs' => 0, 'rows' => []],
        ]);

        $response = $this->actingAsAdmin()->get(
            route('admin.reports.show', ['lang' => 'en', 'report' => $report->id])
        );

        $response->assertOk();
        $response->assertSee(__('admin.reports.show.data_empty', [], 'en'), false);
    }

    public function test_reports_index_type_filter_uses_the_existing_query_parameter(): void
    {
        $this->makePlaybackReport(['name' => 'Wanted Playback']);
        $this->makeUptimeReport(['name' => 'Other Uptime']);

        $response = $this->actingAsAdmin()->get(
            route('admin.reports.index', ['lang' => 'en']) . '?type=playback'
        );

        $response->assertOk();
        $response->assertSee('Wanted Playback', false);
        $response->assertDontSee('Other Uptime', false);
    }

    public function test_reports_index_filters_survive_pagination(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->makePlaybackReport(['name' => 'Paged Report ' . $i]);
        }

        $response = $this->actingAsAdmin()->get(
            route('admin.reports.index', ['lang' => 'en']) . '?type=playback&page=2'
        );

        $response->assertOk();
        $response->assertSee('type=playback', false);
        $this->assertSame('page', $response->viewData('reports')->getPageName());
    }

    public function test_generation_form_requires_the_generate_permission(): void
    {
        $this->makePlaybackReport();
        $generateUrl = route('admin.reports.generate', ['lang' => 'en']);

        $viewer = Admin::create([
            'first_name' => 'View',
            'last_name' => 'Only',
            'email' => 'view-only-reports@example.com',
            'password' => 'password',
            'mobile' => '6000000002',
        ]);
        $viewer->givePermissionTo('reports.view');

        $response = $this->actingAs($viewer, 'admin')->get(route('admin.reports.index', ['lang' => 'en']));

        $response->assertOk();
        $response->assertDontSee($generateUrl, false);
        $response->assertDontSee('id="type_select"', false);

        // The download link stays available to a reports.view holder.
        $response->assertSee('/reports/', false);

        $this->actingAs($viewer, 'admin')
            ->post($generateUrl, ['name' => 'Blocked', 'type' => 'playback'])
            ->assertForbidden();
    }

    public function test_report_download_streams_a_csv_without_changing_the_stored_report(): void
    {
        $report = $this->makePlaybackReport();
        $before = $report->fresh()->toArray();

        $response = $this->actingAsAdmin()->get(
            route('admin.reports.download', ['lang' => 'en', 'report' => $report->id])
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->assertSame($before['data'], $report->fresh()->toArray()['data']);
        $this->assertSame($before['type'], $report->fresh()->toArray()['type']);
    }

    public function test_reports_index_is_forbidden_without_the_view_permission(): void
    {
        $stranger = Admin::create([
            'first_name' => 'No',
            'last_name' => 'Access',
            'email' => 'no-access-reports@example.com',
            'password' => 'password',
            'mobile' => '6000000003',
        ]);

        $this->actingAs($stranger, 'admin')
            ->get(route('admin.reports.index', ['lang' => 'en']))
            ->assertForbidden();
    }
}
