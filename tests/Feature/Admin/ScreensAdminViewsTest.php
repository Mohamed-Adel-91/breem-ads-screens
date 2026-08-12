<?php

namespace Tests\Feature\Admin;

use App\Enums\ScreenStatus;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Screen;
use App\Models\ScreenLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 5 — guards the Screens admin UI against a regression back to the
 * Vite / Tailwind / Alpine architecture, and pins the form, filter, status and
 * custom-paginator contracts the Screen controller relies on.
 *
 * Every record is created inside the isolated in-memory SQLite database used by
 * the test suite; no real screen, device UID, status or heartbeat is touched.
 */
class ScreensAdminViewsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    private const FORBIDDEN_MARKERS = [
        '@vite',
        '/build/',
        'x-data=',
        'x-show=',
        'x-transition',
        'alpinejs',
        'x-app-layout',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['screens.view', 'screens.create', 'screens.edit', 'screens.delete', 'places.view'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Screens',
            'last_name' => 'Tester',
            'email' => 'screens-tester@example.com',
            'password' => 'password',
            'mobile' => '2000000001',
        ]);

        $this->admin->givePermissionTo([
            'screens.view', 'screens.create', 'screens.edit', 'screens.delete', 'places.view',
        ]);
    }

    protected function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
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
    public function test_screens_index_renders_the_canonical_static_layout(string $locale): void
    {
        $place = Place::factory()->create(['name' => ['en' => 'Harbour Cafe', 'ar' => 'مقهى الميناء']]);
        Screen::factory()->create([
            'place_id' => $place->id,
            'code' => 'SCR-IDX-1',
            'device_uid' => 'uid-index-0001',
            'status' => ScreenStatus::Online->value,
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.screens.index', ['lang' => $locale]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.screens.index');

        // Filter parameter names are part of the controller contract.
        $response->assertSee('name="search"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('name="place_id"', false);

        // Screen identity, device UID and the place relation all render.
        $response->assertSee('SCR-IDX-1', false);
        $response->assertSee('uid-index-0001', false);
        $response->assertSee($locale === 'ar' ? 'مقهى الميناء' : 'Harbour Cafe', false);
    }

    #[DataProvider('localeProvider')]
    public function test_screens_create_renders_the_canonical_static_layout(string $locale): void
    {
        Place::factory()->create();

        $response = $this->actingAsAdmin()->get(route('admin.screens.create', ['lang' => $locale]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.screens.create');

        // Exact field names expected by StoreScreenRequest.
        $response->assertSee('name="place_id"', false);
        $response->assertSee('name="code"', false);
        $response->assertSee('name="device_uid"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee(route('admin.screens.store', ['lang' => $locale]), false);

        // Phase 11: last_heartbeat is server-owned operational evidence and is
        // no longer a form input. It used to be an editable datetime-local field,
        // which let an administrator forge connectivity freshness.
        $response->assertDontSee('name="last_heartbeat"', false);
    }

    #[DataProvider('localeProvider')]
    public function test_screens_edit_renders_the_canonical_static_layout(string $locale): void
    {
        $screen = Screen::factory()->create([
            'code' => 'SCR-EDIT-1',
            'device_uid' => 'uid-edit-0001',
            'status' => ScreenStatus::Maintenance->value,
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.screens.edit', ['lang' => $locale, 'screen' => $screen->id]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.screens.edit');
        $response->assertSee('name="_method"', false);
        $response->assertSee(route('admin.screens.update', ['lang' => $locale, 'screen' => $screen->id]), false);
        $response->assertSee('SCR-EDIT-1', false);
        $response->assertSee('uid-edit-0001', false);
    }

    #[DataProvider('localeProvider')]
    public function test_screens_show_renders_the_canonical_static_layout(string $locale): void
    {
        $place = Place::factory()->create(['name' => ['en' => 'Grand Mall', 'ar' => 'المول الكبير']]);
        $screen = Screen::factory()->create([
            'place_id' => $place->id,
            'code' => 'SCR-SHOW-2',
            'status' => ScreenStatus::Online->value,
        ]);
        ScreenLog::factory()->create([
            'screen_id' => $screen->id,
            'status' => ScreenStatus::Online->value,
            'reported_at' => now()->subHour(),
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.screens.show', ['lang' => $locale, 'screen' => $screen->id]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.screens.show');
        $response->assertSee('SCR-SHOW-2', false);
        $response->assertSee($locale === 'ar' ? 'المول الكبير' : 'Grand Mall', false);
    }

    public function test_screen_show_keeps_the_custom_paginator_names(): void
    {
        $screen = Screen::factory()->create(['code' => 'SCR-PAGER-1']);

        ScreenLog::factory()->count(25)->create([
            'screen_id' => $screen->id,
            'status' => ScreenStatus::Online->value,
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.screens.show', ['lang' => 'en', 'screen' => $screen->id]));

        $response->assertOk();

        // The logs paginator must keep its dedicated page parameter, never plain `page`.
        $response->assertSee('logs_page=2', false);
        $response->assertDontSee('?page=2', false);
        $response->assertDontSee('&amp;page=2', false);

        $this->assertSame('logs_page', $response->viewData('recentLogs')->getPageName());
        $this->assertSame('playbacks_page', $response->viewData('recentPlaybacks')->getPageName());
    }

    public function test_screen_status_is_rendered_with_semantic_badges_and_stored_values_are_untouched(): void
    {
        $place = Place::factory()->create();

        Screen::factory()->create(['place_id' => $place->id, 'code' => 'SCR-ON', 'status' => ScreenStatus::Online->value]);
        Screen::factory()->create(['place_id' => $place->id, 'code' => 'SCR-OFF', 'status' => ScreenStatus::Offline->value]);
        Screen::factory()->create(['place_id' => $place->id, 'code' => 'SCR-MNT', 'status' => ScreenStatus::Maintenance->value]);

        $response = $this->actingAsAdmin()->get(route('admin.screens.index', ['lang' => 'en']));

        $response->assertOk();
        $response->assertSee('badge-success', false);
        $response->assertSee('badge-danger', false);
        $response->assertSee('badge-warning', false);

        $this->assertSame('online', Screen::where('code', 'SCR-ON')->first()->status->value);
        $this->assertSame('offline', Screen::where('code', 'SCR-OFF')->first()->status->value);
        $this->assertSame('maintenance', Screen::where('code', 'SCR-MNT')->first()->status->value);
    }

    public function test_a_screen_that_never_reported_shows_a_never_connected_label(): void
    {
        Screen::factory()->create(['code' => 'SCR-NEVER', 'last_heartbeat' => null]);

        $response = $this->actingAsAdmin()->get(route('admin.screens.index', ['lang' => 'en']));

        $response->assertOk();
        $response->assertSee(__('admin.screens.never_connected', [], 'en'), false);
    }

    public function test_screens_index_place_filter_uses_the_existing_query_parameter(): void
    {
        $wanted = Place::factory()->create();
        $other = Place::factory()->create();

        Screen::factory()->create(['place_id' => $wanted->id, 'code' => 'SCR-WANTED']);
        Screen::factory()->create(['place_id' => $other->id, 'code' => 'SCR-OTHER']);

        $response = $this->actingAsAdmin()->get(
            route('admin.screens.index', ['lang' => 'en']) . '?place_id=' . $wanted->id
        );

        $response->assertOk();
        $response->assertSee('SCR-WANTED', false);
        $response->assertDontSee('SCR-OTHER', false);
    }

    public function test_screens_index_is_forbidden_without_the_view_permission(): void
    {
        $stranger = Admin::create([
            'first_name' => 'No',
            'last_name' => 'Access',
            'email' => 'no-access-screens@example.com',
            'password' => 'password',
            'mobile' => '2000000002',
        ]);

        $this->actingAs($stranger, 'admin')
            ->get(route('admin.screens.index', ['lang' => 'en']))
            ->assertForbidden();
    }
}
