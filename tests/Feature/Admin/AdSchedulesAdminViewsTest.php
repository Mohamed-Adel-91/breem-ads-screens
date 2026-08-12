<?php

namespace Tests\Feature\Admin;

use App\Enums\AdStatus;
use App\Enums\ScreenStatus;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 6 — guards the Ad scheduling admin UI against a regression back to the
 * Vite / Tailwind / Alpine architecture, and pins the filter, form and
 * activation-state presentation contracts of ScheduleController.
 *
 * No test in this file changes scheduling semantics: the conflict-resolution and
 * is_active rules are only observed, never asserted into a new shape.
 */
class AdSchedulesAdminViewsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Ad $ad;

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

        foreach (['ads.view', 'ads.schedule', 'ads.edit', 'ads.delete'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Schedule',
            'last_name' => 'Tester',
            'email' => 'schedule-tester@example.com',
            'password' => 'password',
            'mobile' => '4000000001',
        ]);

        $this->admin->givePermissionTo(['ads.view', 'ads.schedule', 'ads.edit', 'ads.delete']);

        $this->ad = Ad::create([
            'title' => ['en' => 'Scheduled Campaign', 'ar' => 'حملة مجدولة'],
            'file_path' => 'upload/ads/creative.png',
            'file_type' => 'image',
            'duration_seconds' => 20,
            'status' => AdStatus::Active->value,
            'created_by' => User::factory()->create()->id,
        ]);
    }

    protected function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
    }

    protected function makeScreen(string $code, string $placeNameEn = 'Riverside Cafe', string $placeNameAr = 'مقهى النهر'): Screen
    {
        $place = Place::factory()->create(['name' => ['en' => $placeNameEn, 'ar' => $placeNameAr]]);

        return Screen::factory()->create([
            'place_id' => $place->id,
            'code' => $code,
            'status' => ScreenStatus::Online->value,
        ]);
    }

    protected function makeSchedule(Screen $screen, array $overrides = []): AdSchedule
    {
        return AdSchedule::create(array_merge([
            'ad_id' => $this->ad->id,
            'screen_id' => $screen->id,
            'start_time' => now()->addDay()->startOfHour(),
            'end_time' => now()->addDays(2)->startOfHour(),
            'is_active' => true,
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
    public function test_schedules_index_renders_the_canonical_static_layout(string $locale): void
    {
        $screen = $this->makeScreen('SCR-SCHED-1');
        $this->makeSchedule($screen);

        $response = $this->actingAsAdmin()->get(
            route('admin.ads.schedules.index', ['lang' => $locale, 'ad' => $this->ad->id])
        );

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.ads.schedules.index');

        // Filter parameter names are part of the controller contract.
        $response->assertSee('name="screen_id"', false);
        $response->assertSee('name="is_active"', false);
        $response->assertSee('name="from_date"', false);
        $response->assertSee('name="to_date"', false);

        // The ad relation and the screen relation both render.
        $response->assertSee($locale === 'ar' ? 'حملة مجدولة' : 'Scheduled Campaign', false);
        $response->assertSee('SCR-SCHED-1', false);
        $response->assertSee($locale === 'ar' ? 'مقهى النهر' : 'Riverside Cafe', false);
    }

    #[DataProvider('localeProvider')]
    public function test_schedule_create_form_preserves_the_exact_field_contract(string $locale): void
    {
        $this->makeScreen('SCR-SCHED-CREATE');

        $response = $this->actingAsAdmin()->get(
            route('admin.ads.schedules.index', ['lang' => $locale, 'ad' => $this->ad->id])
        );

        $response->assertOk();

        // Exact field names expected by StoreScheduleRequest.
        $response->assertSee('id="create_screen_id"', false);
        $response->assertSee('name="screen_id"', false);
        $response->assertSee('name="start_time"', false);
        $response->assertSee('name="end_time"', false);
        $response->assertSee('name="is_active"', false);
        $response->assertSee('type="datetime-local"', false);
        $response->assertSee(route('admin.ads.schedules.store', ['lang' => $locale, 'ad' => $this->ad->id]), false);
    }

    #[DataProvider('localeProvider')]
    public function test_schedule_inline_edit_form_targets_the_update_route(string $locale): void
    {
        $screen = $this->makeScreen('SCR-SCHED-EDIT');
        $schedule = $this->makeSchedule($screen);

        $response = $this->actingAsAdmin()->get(
            route('admin.ads.schedules.index', ['lang' => $locale, 'ad' => $this->ad->id])
        );

        $response->assertOk();
        $response->assertSee(
            route('admin.ads.schedules.update', [
                'lang' => $locale,
                'ad' => $this->ad->id,
                'schedule' => $schedule->id,
            ]),
            false
        );
        $response->assertSee(
            route('admin.ads.schedules.destroy', [
                'lang' => $locale,
                'ad' => $this->ad->id,
                'schedule' => $schedule->id,
            ]),
            false
        );
        $response->assertSee('name="_method"', false);

        // The inline editor is a Bootstrap 4 collapse panel, not an Alpine block.
        $response->assertSee('data-toggle="collapse"', false);
        $response->assertSee('id="schedule-edit-' . $schedule->id . '"', false);

        // Existing values are pre-filled in the edit form.
        $response->assertSee('id="schedule_start_' . $schedule->id . '"', false);
        $response->assertSee($schedule->start_time->format('Y-m-d\TH:i'), false);
    }

    public function test_active_and_inactive_schedules_use_semantic_badges(): void
    {
        $activeScreen = $this->makeScreen('SCR-ACTIVE');
        $inactiveScreen = $this->makeScreen('SCR-INACTIVE');

        $this->makeSchedule($activeScreen, ['is_active' => true]);
        $this->makeSchedule($inactiveScreen, ['is_active' => false]);

        $response = $this->actingAsAdmin()->get(
            route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->ad->id])
        );

        $response->assertOk();
        $response->assertSee('badge-success', false);
        $response->assertSee('badge-secondary', false);

        // Stored activation state is untouched by rendering.
        $this->assertDatabaseHas('ad_schedules', ['screen_id' => $activeScreen->id, 'is_active' => true]);
        $this->assertDatabaseHas('ad_schedules', ['screen_id' => $inactiveScreen->id, 'is_active' => false]);
    }

    public function test_schedules_index_screen_filter_uses_the_existing_query_parameter(): void
    {
        $wanted = $this->makeScreen('SCR-FILTER-WANTED');
        $other = $this->makeScreen('SCR-FILTER-OTHER');

        $this->makeSchedule($wanted);
        $this->makeSchedule($other);

        $response = $this->actingAsAdmin()->get(
            route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->ad->id]) . '?screen_id=' . $wanted->id
        );

        $response->assertOk();
        $this->assertCount(1, $response->viewData('schedules')->items());
        $this->assertSame($wanted->id, $response->viewData('schedules')->items()[0]->screen_id);
    }

    public function test_schedules_index_active_filter_uses_the_existing_query_parameter(): void
    {
        $screenA = $this->makeScreen('SCR-ACTIVE-FILTER');
        $screenB = $this->makeScreen('SCR-INACTIVE-FILTER');

        $this->makeSchedule($screenA, ['is_active' => true]);
        $this->makeSchedule($screenB, ['is_active' => false]);

        $response = $this->actingAsAdmin()->get(
            route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->ad->id]) . '?is_active=0'
        );

        $response->assertOk();
        $this->assertCount(1, $response->viewData('schedules')->items());
        $this->assertFalse($response->viewData('schedules')->items()[0]->is_active);
    }

    public function test_schedules_index_filters_survive_pagination(): void
    {
        $screen = $this->makeScreen('SCR-PAGED');

        for ($i = 0; $i < 30; $i++) {
            $this->makeSchedule($screen, [
                'start_time' => now()->addDays($i + 1)->startOfHour(),
                'end_time' => now()->addDays($i + 2)->startOfHour(),
            ]);
        }

        $response = $this->actingAsAdmin()->get(
            route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->ad->id])
                . '?screen_id=' . $screen->id . '&page=2'
        );

        $response->assertOk();
        $response->assertSee('screen_id=' . $screen->id, false);
        $this->assertSame('page', $response->viewData('schedules')->getPageName());
    }

    public function test_schedule_management_controls_are_hidden_without_the_schedule_permission(): void
    {
        $screen = $this->makeScreen('SCR-NO-PERM');
        $schedule = $this->makeSchedule($screen);

        $viewer = Admin::create([
            'first_name' => 'View',
            'last_name' => 'Only',
            'email' => 'view-only-schedules@example.com',
            'password' => 'password',
            'mobile' => '4000000002',
        ]);
        $viewer->givePermissionTo('ads.view');

        $response = $this->actingAs($viewer, 'admin')->get(
            route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->ad->id])
        );

        $response->assertOk();

        // The create card, the inline editor and the row actions are all gated on
        // `ads.schedule`. The schedules index URL itself still appears (filter form
        // action / reset link), so the assertions target the gated controls instead.
        $response->assertDontSee('id="create_screen_id"', false);
        $response->assertDontSee('id="schedule-edit-' . $schedule->id . '"', false);
        $response->assertDontSee(
            route('admin.ads.schedules.destroy', [
                'lang' => 'en',
                'ad' => $this->ad->id,
                'schedule' => $schedule->id,
            ]),
            false
        );
    }

    public function test_schedules_index_is_forbidden_without_the_ads_view_permission(): void
    {
        $stranger = Admin::create([
            'first_name' => 'No',
            'last_name' => 'Access',
            'email' => 'no-access-schedules@example.com',
            'password' => 'password',
            'mobile' => '4000000003',
        ]);

        $this->actingAs($stranger, 'admin')
            ->get(route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->ad->id]))
            ->assertForbidden();
    }
}
