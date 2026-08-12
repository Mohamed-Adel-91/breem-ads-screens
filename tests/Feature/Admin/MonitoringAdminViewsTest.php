<?php

namespace Tests\Feature\Admin;

use App\Enums\AdStatus;
use App\Enums\ScreenStatus;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Admin;
use App\Models\Place;
use App\Models\PlaybackLog;
use App\Models\Screen;
use App\Models\ScreenLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 7 — guards the Monitoring admin UI against a regression back to the
 * Vite / Tailwind / Alpine architecture, and pins the filter, status, heartbeat
 * and custom-paginator contracts of MonitoringController.
 *
 * Monitoring is presentation over backend-owned operational data. No test here
 * asserts a status, heartbeat or uptime value derived in Blade, and no real
 * screen, heartbeat or alert is touched — every record is isolated in the
 * in-memory SQLite database used by the suite.
 */
class MonitoringAdminViewsTest extends TestCase
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

        foreach (['monitoring.view', 'monitoring.manage'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Monitoring',
            'last_name' => 'Tester',
            'email' => 'monitoring-tester@example.com',
            'password' => 'password',
            'mobile' => '5000000001',
        ]);

        $this->admin->givePermissionTo(['monitoring.view', 'monitoring.manage']);
    }

    protected function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
    }

    protected function makeScreen(array $overrides = [], string $placeEn = 'Harbour Mall', string $placeAr = 'مول الميناء'): Screen
    {
        $place = Place::factory()->create(['name' => ['en' => $placeEn, 'ar' => $placeAr]]);

        return Screen::factory()->create(array_merge([
            'place_id' => $place->id,
            'status' => ScreenStatus::Online->value,
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
    public function test_monitoring_index_renders_the_canonical_static_layout(string $locale): void
    {
        $screen = $this->makeScreen(['code' => 'SCR-MON-1', 'device_uid' => 'uid-mon-0001']);
        ScreenLog::factory()->create([
            'screen_id' => $screen->id,
            'status' => ScreenStatus::Online->value,
            'reported_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.monitoring.index', ['lang' => $locale]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.monitoring.index');

        // Filter parameter names are part of the controller contract.
        $response->assertSee('name="search"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('name="place_id"', false);
        $response->assertSee('name="has_alerts"', false);

        // Screen identity and the place relation both render.
        $response->assertSee('SCR-MON-1', false);
        $response->assertSee($locale === 'ar' ? 'مول الميناء' : 'Harbour Mall', false);
    }

    #[DataProvider('localeProvider')]
    public function test_monitoring_show_renders_the_canonical_static_layout(string $locale): void
    {
        $screen = $this->makeScreen(['code' => 'SCR-MON-SHOW', 'device_uid' => 'uid-mon-show']);

        $ad = Ad::create([
            'title' => ['en' => 'Monitored Ad', 'ar' => 'إعلان مراقب'],
            'file_path' => 'upload/ads/creative.png',
            'file_type' => 'image',
            'duration_seconds' => 12,
            'status' => AdStatus::Active->value,
            'created_by' => User::factory()->create()->id,
        ]);
        $ad->screens()->attach($screen->id, ['play_order' => 4]);

        AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $screen->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
            'is_active' => true,
        ]);

        ScreenLog::factory()->create([
            'screen_id' => $screen->id,
            'status' => ScreenStatus::Online->value,
            'reported_at' => now()->subHour(),
        ]);

        PlaybackLog::create([
            'screen_id' => $screen->id,
            'ad_id' => $ad->id,
            'played_at' => now()->subHours(2),
            'duration' => 12,
        ]);

        $response = $this->actingAsAdmin()->get(
            route('admin.monitoring.screens.show', ['lang' => $locale, 'screen' => $screen->id])
        );

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.monitoring.show');
        $response->assertSee('SCR-MON-SHOW', false);
        $response->assertSee('uid-mon-show', false);
        $response->assertSee($locale === 'ar' ? 'مول الميناء' : 'Harbour Mall', false);
        $response->assertSee($locale === 'ar' ? 'إعلان مراقب' : 'Monitored Ad', false);
    }

    public function test_monitoring_reuses_the_shared_screen_status_badge_mapping(): void
    {
        $this->makeScreen(['code' => 'SCR-ON', 'status' => ScreenStatus::Online->value]);
        $this->makeScreen(['code' => 'SCR-OFF', 'status' => ScreenStatus::Offline->value]);
        $this->makeScreen(['code' => 'SCR-MNT', 'status' => ScreenStatus::Maintenance->value]);

        $response = $this->actingAsAdmin()->get(route('admin.monitoring.index', ['lang' => 'en']));

        $response->assertOk();
        $response->assertSee('badge-success', false);
        $response->assertSee('badge-danger', false);
        $response->assertSee('badge-warning', false);

        // Stored operational state is never rewritten by rendering.
        $this->assertSame('online', Screen::where('code', 'SCR-ON')->first()->status->value);
        $this->assertSame('offline', Screen::where('code', 'SCR-OFF')->first()->status->value);
        $this->assertSame('maintenance', Screen::where('code', 'SCR-MNT')->first()->status->value);
    }

    public function test_monitoring_reuses_the_shared_heartbeat_presentation(): void
    {
        $this->makeScreen(['code' => 'SCR-NEVER', 'last_heartbeat' => null]);
        $screen = Screen::where('code', 'SCR-NEVER')->first();

        $response = $this->actingAsAdmin()->get(
            route('admin.monitoring.screens.show', ['lang' => 'en', 'screen' => $screen->id])
        );

        $response->assertOk();
        $response->assertSee(__('admin.screens.never_connected', [], 'en'), false);

        // Rendering the page must not stamp a heartbeat.
        $this->assertNull($screen->fresh()->last_heartbeat);
    }

    public function test_monitoring_show_keeps_the_custom_paginator_names(): void
    {
        $screen = $this->makeScreen(['code' => 'SCR-MON-PAGER']);

        ScreenLog::factory()->count(25)->create([
            'screen_id' => $screen->id,
            'status' => ScreenStatus::Online->value,
        ]);

        $response = $this->actingAsAdmin()->get(
            route('admin.monitoring.screens.show', ['lang' => 'en', 'screen' => $screen->id])
        );

        $response->assertOk();

        // The logs paginator keeps its dedicated page parameter, never plain `page`.
        $response->assertSee('logs_page=2', false);
        $response->assertDontSee('?page=2', false);
        $response->assertDontSee('&amp;page=2', false);

        $this->assertSame('logs_page', $response->viewData('recentLogs')->getPageName());
        $this->assertSame('playbacks_page', $response->viewData('recentPlaybacks')->getPageName());
    }

    public function test_paging_the_logs_table_does_not_reset_the_playbacks_table(): void
    {
        $screen = $this->makeScreen(['code' => 'SCR-MON-BOTH']);
        $ad = Ad::create([
            'title' => ['en' => 'Paged Ad'],
            'file_path' => 'upload/ads/creative.png',
            'file_type' => 'image',
            'duration_seconds' => 5,
            'status' => AdStatus::Active->value,
            'created_by' => User::factory()->create()->id,
        ]);

        ScreenLog::factory()->count(25)->create([
            'screen_id' => $screen->id,
            'status' => ScreenStatus::Online->value,
        ]);

        for ($i = 0; $i < 25; $i++) {
            PlaybackLog::create([
                'screen_id' => $screen->id,
                'ad_id' => $ad->id,
                'played_at' => now()->subMinutes($i),
                'duration' => 5,
            ]);
        }

        $response = $this->actingAsAdmin()->get(
            route('admin.monitoring.screens.show', ['lang' => 'en', 'screen' => $screen->id])
                . '?logs_page=2&playbacks_page=2'
        );

        $response->assertOk();

        // Both paginators independently honour their own page parameter.
        $this->assertSame(2, $response->viewData('recentLogs')->currentPage());
        $this->assertSame(2, $response->viewData('recentPlaybacks')->currentPage());

        // Each paginator's links carry the other paginator's page forward.
        $response->assertSee('playbacks_page=2', false);
    }

    public function test_monitoring_index_filters_use_the_existing_query_parameters(): void
    {
        $wanted = $this->makeScreen(['code' => 'SCR-WANTED-MON', 'status' => ScreenStatus::Offline->value]);
        $this->makeScreen(['code' => 'SCR-OTHER-MON', 'status' => ScreenStatus::Online->value]);

        $byStatus = $this->actingAsAdmin()->get(
            route('admin.monitoring.index', ['lang' => 'en']) . '?status=offline'
        );
        $byStatus->assertOk();
        $byStatus->assertSee('SCR-WANTED-MON', false);
        $byStatus->assertDontSee('SCR-OTHER-MON', false);

        $byPlace = $this->actingAsAdmin()->get(
            route('admin.monitoring.index', ['lang' => 'en']) . '?place_id=' . $wanted->place_id
        );
        $byPlace->assertOk();
        $byPlace->assertSee('SCR-WANTED-MON', false);
        $byPlace->assertDontSee('SCR-OTHER-MON', false);

        $byAlerts = $this->actingAsAdmin()->get(
            route('admin.monitoring.index', ['lang' => 'en']) . '?has_alerts=1'
        );
        $byAlerts->assertOk();
        $byAlerts->assertSee('SCR-WANTED-MON', false);
        $byAlerts->assertDontSee('SCR-OTHER-MON', false);
    }

    public function test_monitoring_index_filters_survive_pagination(): void
    {
        $place = Place::factory()->create();

        for ($i = 0; $i < 25; $i++) {
            Screen::factory()->create([
                'place_id' => $place->id,
                'code' => 'SCR-PAGED-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'status' => ScreenStatus::Online->value,
            ]);
        }

        $response = $this->actingAsAdmin()->get(
            route('admin.monitoring.index', ['lang' => 'en']) . '?status=online&page=2'
        );

        $response->assertOk();
        $response->assertSee('status=online', false);
        $this->assertSame('page', $response->viewData('screens')->getPageName());
    }

    public function test_acknowledgement_control_requires_the_manage_permission(): void
    {
        $screen = $this->makeScreen(['code' => 'SCR-ACK']);
        $ackUrl = route('admin.monitoring.screens.acknowledge', ['lang' => 'en', 'screen' => $screen->id]);

        // Phase 11: the form appears only when there is a real open alert to
        // acknowledge, so raise one.
        $screen->logs()->create([
            'status' => ScreenStatus::Offline->value,
            'reported_at' => now()->subMinutes(5),
        ]);

        // A manager sees the acknowledgement form.
        $manager = $this->actingAsAdmin()->get(
            route('admin.monitoring.screens.show', ['lang' => 'en', 'screen' => $screen->id])
        );
        $manager->assertOk();
        $manager->assertSee($ackUrl, false);
        $manager->assertSee('name="note"', false);

        // A view-only admin does not.
        $viewer = Admin::create([
            'first_name' => 'View',
            'last_name' => 'Only',
            'email' => 'view-only-monitoring@example.com',
            'password' => 'password',
            'mobile' => '5000000002',
        ]);
        $viewer->givePermissionTo('monitoring.view');

        $viewerResponse = $this->actingAs($viewer, 'admin')->get(
            route('admin.monitoring.screens.show', ['lang' => 'en', 'screen' => $screen->id])
        );
        $viewerResponse->assertOk();
        $viewerResponse->assertDontSee($ackUrl, false);
        $viewerResponse->assertDontSee('name="note"', false);

        // The mutating route is blocked for them too.
        $this->actingAs($viewer, 'admin')
            ->post($ackUrl, ['note' => 'Trying anyway'])
            ->assertForbidden();
    }

    public function test_monitoring_index_is_forbidden_without_the_view_permission(): void
    {
        $stranger = Admin::create([
            'first_name' => 'No',
            'last_name' => 'Access',
            'email' => 'no-access-monitoring@example.com',
            'password' => 'password',
            'mobile' => '5000000003',
        ]);

        $this->actingAs($stranger, 'admin')
            ->get(route('admin.monitoring.index', ['lang' => 'en']))
            ->assertForbidden();
    }
}
