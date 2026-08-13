<?php

namespace Tests\Feature\Operations;

use App\Enums\AdStatus;
use App\Enums\ScreenStatus;
use App\Jobs\CheckScreenHealthJob;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Report;
use App\Models\Screen;
use App\Models\User;
use App\Support\ReportPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 15 — the production gate.
 *
 * Each test here pins something that was only ever true by luck, or by an operator
 * remembering. They are grouped by the risk they close:
 *
 *   - **Report period** had no upper bound, so an authenticated operator could ask
 *     the uptime builder to walk decades of log stream per screen by typing a date.
 *   - **The reports index** selected `reports.data` — every stored snapshot on the
 *     page — to render a table that shows none of it.
 *   - **The offline sweep** loaded every stale screen at once; a site-wide power cut
 *     makes that the whole fleet.
 *   - **A zero-duration video** could be created whenever ffprobe was switched off,
 *     which is the shipped default, and the playlist would hand it to a device.
 *   - **The maintenance endpoints** ran `db:seed` and `migrate --force` over HTTP.
 *   - **`/up`** was silently unregistered once before, by the custom `using:` router.
 */
class ProductionGateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Admin $superAdmin;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['reports.view', 'reports.generate', 'ads.view', 'ads.create', 'ads.edit'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        Role::findOrCreate('super-admin', 'admin');

        $this->admin = Admin::create([
            'first_name' => 'Gate',
            'last_name' => 'Tester',
            'email' => 'production-gate@example.com',
            'password' => 'password',
            'mobile' => '9500000001',
        ]);
        $this->admin->givePermissionTo(['reports.view', 'reports.generate', 'ads.view', 'ads.create', 'ads.edit']);

        $this->superAdmin = Admin::create([
            'first_name' => 'Root',
            'last_name' => 'Operator',
            'email' => 'production-gate-root@example.com',
            'password' => 'password',
            'mobile' => '9500000002',
        ]);
        $this->superAdmin->assignRole('super-admin');

        $this->owner = User::factory()->create();

        // The shipped production default. Every ffprobe-dependent assertion below is
        // about what happens with probing OFF, because that is what is deployed.
        config(['ads.try_ffprobe' => false]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ------------------------------------------------------ report period ceiling

    private function generate(array $overrides = [])
    {
        return $this->actingAs($this->admin, 'admin')->post(
            route('admin.reports.generate', ['lang' => 'en']),
            array_merge([
                'name' => 'Gate Report',
                'type' => 'screen-uptime',
            ], $overrides)
        );
    }

    public function test_a_report_period_longer_than_the_ceiling_is_rejected(): void
    {
        config(['reports.max_period_days' => 31]);

        $this->generate([
            'from_date' => '2026-01-01',
            'to_date' => '2026-06-30',
        ])->assertSessionHasErrors('from_date');

        $this->assertSame(0, Report::count(), 'No report may be generated over an oversized period.');
    }

    public function test_a_report_period_inside_the_ceiling_is_accepted(): void
    {
        config(['reports.max_period_days' => 31]);

        $this->generate([
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Report::count());
    }

    public function test_an_open_ended_period_is_measured_to_now_and_still_bounded(): void
    {
        // A `from_date` with no `to_date` is not an unbounded query — the uptime
        // builder measures up to now() — so the ceiling has to see it.
        Carbon::setTestNow(Carbon::create(2026, 8, 13, 12, 0, 0));
        config(['reports.max_period_days' => 31]);

        $this->generate(['from_date' => '2020-01-01'])
            ->assertSessionHasErrors('from_date');

        $this->assertSame(0, Report::count());
    }

    public function test_a_to_date_alone_has_no_span_to_bound(): void
    {
        // No lower bound: the builder falls back to its own default window, so there
        // is nothing here for the ceiling to reject.
        config(['reports.max_period_days' => 31]);

        $this->generate(['to_date' => '2026-08-01'])->assertSessionHasNoErrors();

        $this->assertSame(1, Report::count());
    }

    public function test_the_ceiling_can_be_disabled_for_a_one_off_historical_report(): void
    {
        config(['reports.max_period_days' => null]);

        $this->generate([
            'from_date' => '2000-01-01',
            'to_date' => '2026-08-01',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Report::count());
    }

    public function test_the_ceiling_is_a_positive_number_of_days_or_nothing(): void
    {
        foreach ([null, '', 0, -1, 'soon'] as $configured) {
            config(['reports.max_period_days' => $configured]);

            $this->assertNull(
                ReportPeriod::maxDays(),
                'A non-positive ceiling must mean "no ceiling", never "0 days".'
            );
        }

        config(['reports.max_period_days' => '90']);
        $this->assertSame(90, ReportPeriod::maxDays(), 'A numeric string is a valid ceiling.');
    }

    public function test_a_single_day_report_spans_one_day_not_zero(): void
    {
        // to_date is inclusive of its whole day, matching resolvePeriod(). A ceiling of
        // 1 must therefore still allow a one-day report.
        $this->assertSame(1, ReportPeriod::spanDays([
            'from_date' => '2026-08-01',
            'to_date' => '2026-08-01',
        ]));
    }

    public function test_the_period_ceiling_is_declared_in_configuration_not_in_blade(): void
    {
        $this->assertSame(
            366,
            (int) config('reports.max_period_days'),
            'The documented default lives in config/reports.php.'
        );
    }

    // -------------------------------------------------------- reports index payload

    public function test_the_reports_index_does_not_load_the_stored_snapshots(): void
    {
        Report::create([
            'name' => 'Heavy Snapshot',
            'type' => 'playback',
            'filters' => [],
            'data' => ['rows' => array_fill(0, 50, ['ad_id' => 1, 'plays' => 3])],
            'generated_by' => $this->admin->id,
        ]);

        $selects = [];

        DB::listen(function ($query) use (&$selects): void {
            $sql = strtolower(str_replace(['`', '"'], '', $query->sql));

            if (str_starts_with($sql, 'select') && str_contains($sql, 'from reports')) {
                $selects[] = $sql;
            }
        });

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index', ['lang' => 'en']));

        $response->assertOk();

        $this->assertNotEmpty($selects, 'The index must have queried the reports table.');

        foreach ($selects as $sql) {
            $this->assertStringNotContainsString(
                'reports.data',
                $sql,
                'The index must not select the snapshot column it never renders.'
            );
            $this->assertStringNotContainsString(
                'select *',
                $sql,
                'The index must name its columns rather than selecting everything.'
            );
        }

        // The behavioural half: the attribute is genuinely absent from the row, so
        // nothing downstream can quietly depend on it being there.
        $listed = $response->viewData('reports')->first();

        $this->assertFalse(
            array_key_exists('data', $listed->getAttributes()),
            'The listed report must not carry a hydrated snapshot.'
        );
        $this->assertSame('Heavy Snapshot', $listed->name);
    }

    public function test_showing_and_downloading_a_report_still_read_the_snapshot(): void
    {
        $report = Report::create([
            'name' => 'Readable Snapshot',
            'type' => 'playback',
            'filters' => [],
            'data' => [
                'rows' => [[
                    'ad_id' => 4,
                    'ad_title' => 'Gate Creative',
                    'plays' => 7,
                    'total_duration' => 140,
                    'screens' => ['SCR-GATE'],
                ]],
                'summary' => ['advertisements' => 1, 'plays' => 7, 'total_duration' => 140],
            ],
            'generated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.show', ['lang' => 'en', 'report' => $report->id]))
            ->assertOk()
            ->assertSee('Gate Creative');

        $download = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.download', ['lang' => 'en', 'report' => $report->id]));

        $download->assertOk();
        $this->assertStringContainsString('Gate Creative', $download->streamedContent());
    }

    // ------------------------------------------------------------- offline sweep

    public function test_the_sweep_transitions_an_entire_stale_fleet(): void
    {
        Notification::fake();

        $place = Place::factory()->create();
        $stale = now()->subMinutes(30);

        // A whole-site outage: every screen goes silent on the same tick, so the sweep
        // has to walk the fleet, not a handful of failures.
        for ($i = 1; $i <= 25; $i++) {
            Screen::factory()->create([
                'place_id' => $place->id,
                'code' => sprintf('SCR-FLEET-%02d', $i),
                'status' => ScreenStatus::Online->value,
                'last_heartbeat' => $stale,
            ]);
        }

        (new CheckScreenHealthJob())->handle(app(\App\Services\Screen\HeartbeatService::class));

        $this->assertSame(
            0,
            Screen::where('status', ScreenStatus::Online->value)->count(),
            'Streaming the fleet must not skip screens the way OFFSET paging would.'
        );
        $this->assertSame(25, Screen::where('status', ScreenStatus::Offline->value)->count());
    }

    public function test_a_second_sweep_over_the_same_fleet_changes_nothing(): void
    {
        Notification::fake();

        $place = Place::factory()->create();

        for ($i = 1; $i <= 12; $i++) {
            Screen::factory()->create([
                'place_id' => $place->id,
                'code' => sprintf('SCR-IDEM-%02d', $i),
                'status' => ScreenStatus::Online->value,
                'last_heartbeat' => now()->subMinutes(30),
            ]);
        }

        $health = app(\App\Services\Screen\HeartbeatService::class);

        (new CheckScreenHealthJob())->handle($health);
        $firstPass = $this->fleetFingerprint();

        (new CheckScreenHealthJob())->handle($health);

        $this->assertSame(
            $firstPass,
            $this->fleetFingerprint(),
            'An already-offline fleet must not be rewritten on the next tick.'
        );
    }

    /**
     * Status and last-write time per screen, as comparable strings.
     *
     * @return array<string, string>
     */
    private function fleetFingerprint(): array
    {
        return Screen::orderBy('code')
            ->get(['code', 'status', 'updated_at'])
            ->mapWithKeys(fn (Screen $screen) => [
                $screen->code => $screen->status->value.'@'.$screen->updated_at->toDateTimeString(),
            ])
            ->all();
    }

    // ------------------------------------------------------- zero-duration video

    private function adPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => ['en' => 'Gate Creative'],
            'created_by' => $this->owner->id,
        ], $overrides);
    }

    public function test_a_video_cannot_be_created_without_a_duration_when_probing_is_off(): void
    {
        // The shipped default is ADS_TRY_FFPROBE=false. Before Phase 15 this wrote
        // duration_seconds=0 and the playlist served an unplayable item.
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->adPayload([
                'creative' => UploadedFile::fake()->create('silent.mp4', 128, 'video/mp4'),
            ]))
            ->assertSessionHasErrors('duration_seconds');

        $this->assertSame(0, Ad::count(), 'No ad may be written with an unusable duration.');
    }

    public function test_an_explicit_zero_duration_video_is_rejected_too(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->adPayload([
                'creative' => UploadedFile::fake()->create('zero.mp4', 128, 'video/mp4'),
                'duration_seconds' => 0,
            ]))
            ->assertSessionHasErrors('duration_seconds');

        $this->assertSame(0, Ad::count());
    }

    public function test_a_video_with_an_operator_supplied_duration_is_accepted(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->adPayload([
                'creative' => UploadedFile::fake()->create('fine.mp4', 128, 'video/mp4'),
                'duration_seconds' => 30,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(30, (int) Ad::sole()->duration_seconds);
    }

    public function test_a_still_image_needs_no_duration_at_all(): void
    {
        // How long a still is shown is a playlist decision, so zero stays legal here
        // and the ceiling above must not have changed that.
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->adPayload([
                'creative' => UploadedFile::fake()->image('still.jpg', 640, 480)->size(40),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('image', Ad::sole()->file_type);
    }

    public function test_editing_a_video_without_replacing_it_keeps_its_duration(): void
    {
        $ad = Ad::create([
            'title' => ['en' => 'Existing'],
            'file_path' => 'upload/ads/existing.mp4',
            'file_type' => 'video',
            'duration_seconds' => 45,
            'status' => AdStatus::Pending,
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]), $this->adPayload([
                'title' => ['en' => 'Renamed'],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(45, (int) $ad->fresh()->duration_seconds);
    }

    // -------------------------------------------------- maintenance route surface

    public static function maintenanceRouteProvider(): array
    {
        return [
            'cache clear' => ['/clear-cache'],
            'optimize' => ['/run-optimize/day'.date('d')],
            'migrate' => ['/run-migrate/day'.date('d')],
        ];
    }

    #[DataProvider('maintenanceRouteProvider')]
    public function test_a_maintenance_route_is_unreachable_without_a_session(string $uri): void
    {
        $this->get($uri)->assertRedirect();
    }

    #[DataProvider('maintenanceRouteProvider')]
    public function test_a_maintenance_route_refuses_an_admin_who_is_not_super_admin(string $uri): void
    {
        $this->actingAs($this->admin, 'admin')->get($uri)->assertForbidden();
    }

    public function test_no_route_seeds_the_database_over_http(): void
    {
        // db:seed resets the super-admin password and writes demo screens and
        // fabricated playback logs. It is a CLI bootstrap, never an endpoint.
        foreach (Route::getRoutes() as $route) {
            $this->assertStringNotContainsString(
                'run-seeder',
                $route->uri(),
                'The seeder endpoint must not come back.'
            );
        }

        $this->actingAs($this->superAdmin, 'admin')
            ->get('/run-seeder/day'.date('d'))
            ->assertNotFound();
    }

    public function test_the_cache_clear_route_does_not_also_migrate(): void
    {
        $statements = [];

        DB::listen(function ($query) use (&$statements): void {
            $statements[] = strtolower($query->sql);
        });

        $this->actingAs($this->superAdmin, 'admin')->get('/clear-cache')->assertOk();

        foreach ($statements as $sql) {
            $this->assertStringNotContainsString(
                'migrations',
                $sql,
                'A cache-clear endpoint must not touch the schema.'
            );
        }
    }

    // -------------------------------------------------------------- trusted proxies

    /**
     * A request as a TLS-terminating proxy forwards it: the hop itself is plain HTTP.
     */
    private function forwardedGet(string $uri = '/up')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])->get($uri, [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-For' => '203.0.113.77',
            'X-Forwarded-Host' => 'breem.example',
        ]);
    }

    public function test_forwarded_headers_are_ignored_when_no_proxy_is_trusted(): void
    {
        // The shipped default. A client that simply sends X-Forwarded-* must not be
        // able to claim a scheme or an address it does not have.
        config(['trustedproxy.proxies' => null]);

        $this->forwardedGet();

        $this->assertFalse(request()->isSecure(), 'An untrusted hop may not assert https.');
        $this->assertSame('10.0.0.9', request()->ip(), 'The client IP must stay the real peer.');
    }

    public function test_a_trusted_proxy_restores_the_original_scheme_and_client_ip(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $this->forwardedGet();

        $this->assertTrue(
            request()->isSecure(),
            'Behind a trusted TLS terminator the request must be recognised as secure, '
            .'or every generated URL and the session cookie flag are wrong.'
        );
        $this->assertSame(
            '203.0.113.77',
            request()->ip(),
            'Rate limiting must key on the device, not on the proxy.'
        );
    }

    public function test_a_trusted_proxy_makes_generated_media_urls_https(): void
    {
        config(['trustedproxy.proxies' => 'REMOTE_ADDR']);

        $this->forwardedGet();

        $this->assertStringStartsWith(
            'https://',
            \App\Support\MediaUrl::resolve('upload/ads/creative.mp4'),
            'A device must never be handed a plaintext creative URL.'
        );
    }

    // ------------------------------------------------------- browser security headers

    public function test_the_public_site_sends_conservative_security_headers(): void
    {
        $response = $this->get('/en');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_the_admin_sends_conservative_security_headers(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index', ['lang' => 'en']));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_an_upstream_header_is_not_overwritten(): void
    {
        // A reverse proxy that already sets a policy stays authoritative.
        Route::middleware(['web'])->get('/__gate-upstream-header', function () {
            return response('ok')->header('X-Frame-Options', 'DENY');
        });

        $this->get('/__gate-upstream-header')->assertHeader('X-Frame-Options', 'DENY');
    }

    // -------------------------------------------------------- error disclosure

    public function test_a_server_error_discloses_nothing_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        Route::middleware(['web'])->get('/__gate-explode', function () {
            // A message shaped like the things that actually leak: a query, a path and
            // a credential-ish string.
            throw new \RuntimeException(
                'select * from screen_device_credentials where token_hash = "deadbeefcafe" '
                .'in /var/www/breem/app/Secret.php'
            );
        });

        $response = $this->get('/__gate-explode');

        $this->assertSame(500, $response->getStatusCode());

        $body = $response->getContent();

        foreach ([
            'screen_device_credentials',
            'deadbeefcafe',
            '/var/www/breem',
            'RuntimeException',
            'Stack trace',
        ] as $leak) {
            $this->assertStringNotContainsString(
                $leak,
                $body,
                "A production error page must not disclose [{$leak}]."
            );
        }
    }

    public function test_client_errors_stay_plain(): void
    {
        config(['app.debug' => false]);

        // 404 for an unknown url, and a real 403 from the maintenance surface.
        $notFound = $this->get('/definitely-not-a-breem-url');
        $this->assertSame(404, $notFound->getStatusCode());
        $this->assertStringNotContainsString('Stack trace', $notFound->getContent());

        $forbidden = $this->actingAs($this->admin, 'admin')->get('/clear-cache');
        $this->assertSame(403, $forbidden->getStatusCode());
        $this->assertStringNotContainsString('Stack trace', $forbidden->getContent());
    }

    public function test_a_validation_failure_returns_422_without_disclosure_on_the_device_api(): void
    {
        config(['app.debug' => false]);

        // Unauthenticated: the device API must fail closed at 401 before it ever
        // reaches validation, and say nothing about why.
        $response = $this->postJson('/api/v1/screens/heartbeat', ['status' => 'online']);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringNotContainsString('screen_device_credentials', $response->getContent());
        $this->assertStringNotContainsString('token_hash', $response->getContent());
    }

    // ------------------------------------------------------------------- liveness

    public function test_the_liveness_endpoint_is_registered_and_public(): void
    {
        // This was silently dropped once already: a custom `using:` callback replaces
        // Laravel's default route registration, so the `health:` argument was ignored.
        $this->get('/up')->assertOk()->assertSee('OK');

        $this->assertNotNull(Route::getRoutes()->getByName('health'));
    }

    public function test_liveness_reports_nothing_about_configuration(): void
    {
        $body = $this->get('/up')->getContent();

        foreach ([config('database.connections.mysql.password'), config('app.key')] as $secret) {
            if (is_string($secret) && $secret !== '') {
                $this->assertStringNotContainsString($secret, $body);
            }
        }

        $this->assertSame('OK', trim($body), 'Liveness is liveness; readiness is ops:status.');
    }
}
