<?php

namespace Tests\Feature\Admin;

use App\Enums\PlaceType;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Screen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 5 — guards the Places admin UI against a regression back to the
 * Vite / Tailwind / Alpine architecture, and pins the form + filter contract
 * that the Place controller and form requests rely on.
 *
 * Every record is created inside the isolated in-memory SQLite database used by
 * the test suite; no real place is ever read or written.
 */
class PlacesAdminViewsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    /**
     * Markers that must never reappear in a migrated admin page.
     */
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

        foreach (['places.view', 'places.create', 'places.edit', 'places.delete', 'screens.view', 'screens.create', 'screens.edit'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Places',
            'last_name' => 'Tester',
            'email' => 'places-tester@example.com',
            'password' => 'password',
            'mobile' => '1000000001',
        ]);

        $this->admin->givePermissionTo([
            'places.view', 'places.create', 'places.edit', 'places.delete',
            'screens.view', 'screens.create', 'screens.edit',
        ]);
    }

    protected function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
    }

    private function assertCanonicalStaticPage($response): void
    {
        $response->assertOk();

        // Canonical static layout markers.
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
    public function test_places_index_renders_the_canonical_static_layout(string $locale): void
    {
        Place::factory()->create(['type' => PlaceType::Mall->value]);

        $response = $this->actingAsAdmin()->get(route('admin.places.index', ['lang' => $locale]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.places.index');

        // Filter parameter names are part of the controller contract.
        $response->assertSee('name="search"', false);
        $response->assertSee('name="type"', false);
    }

    #[DataProvider('localeProvider')]
    public function test_places_create_renders_the_canonical_static_layout(string $locale): void
    {
        $response = $this->actingAsAdmin()->get(route('admin.places.create', ['lang' => $locale]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.places.create');

        // Exact field names expected by StorePlaceRequest.
        $response->assertSee('name="name[en]"', false);
        $response->assertSee('name="name[ar]"', false);
        $response->assertSee('name="address[en]"', false);
        $response->assertSee('name="address[ar]"', false);
        $response->assertSee('name="type"', false);
        $response->assertSee(route('admin.places.store', ['lang' => $locale]), false);
    }

    #[DataProvider('localeProvider')]
    public function test_places_edit_renders_the_canonical_static_layout(string $locale): void
    {
        $place = Place::factory()->create([
            'name' => ['en' => 'Riverside Mall', 'ar' => 'مول النهر'],
            'address' => ['en' => '12 River Street', 'ar' => '١٢ شارع النهر'],
            'type' => PlaceType::Mall->value,
        ]);

        $response = $this->actingAsAdmin()->get(route('admin.places.edit', ['lang' => $locale, 'place' => $place->id]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.places.edit');
        $response->assertSee('name="_method"', false);
        $response->assertSee(route('admin.places.update', ['lang' => $locale, 'place' => $place->id]), false);

        // Stored translations render per locale, independently of the dashboard locale.
        $response->assertSee('Riverside Mall', false);
        $response->assertSee('مول النهر', false);
    }

    #[DataProvider('localeProvider')]
    public function test_places_show_renders_the_canonical_static_layout(string $locale): void
    {
        $place = Place::factory()->create(['name' => ['en' => 'Central Cafe', 'ar' => 'المقهى المركزي']]);
        Screen::factory()->create(['place_id' => $place->id, 'code' => 'SCR-SHOW-1']);

        $response = $this->actingAsAdmin()->get(route('admin.places.show', ['lang' => $locale, 'place' => $place->id]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.places.show');
        $response->assertSee('SCR-SHOW-1', false);
        $response->assertSee(route('admin.places.destroy', ['lang' => $locale, 'place' => $place->id]), false);
    }

    public function test_place_index_filters_are_preserved_across_pagination_links(): void
    {
        Place::factory()->count(25)->create(['type' => PlaceType::Cafe->value]);
        Place::factory()->create([
            'name' => ['en' => 'Sunset Club', 'ar' => 'نادي الغروب'],
            'type' => PlaceType::Club->value,
        ]);

        $response = $this->actingAsAdmin()->get(
            route('admin.places.index', ['lang' => 'en']) . '?type=cafe&search=&page=2'
        );

        $response->assertOk();
        $response->assertSee('type=cafe', false);
        $response->assertDontSee('Sunset Club', false);
    }

    public function test_place_type_labels_are_localised_without_changing_stored_values(): void
    {
        Place::factory()->create(['type' => PlaceType::Club->value]);

        $arabic = $this->actingAsAdmin()->get(route('admin.places.index', ['lang' => 'ar']));
        $arabic->assertOk();
        $arabic->assertSee(__('admin.places.types.club', [], 'ar'), false);

        // The stored enum value is untouched.
        $this->assertSame('club', Place::first()->type->value);
    }

    public function test_places_index_is_forbidden_without_the_view_permission(): void
    {
        $stranger = Admin::create([
            'first_name' => 'No',
            'last_name' => 'Access',
            'email' => 'no-access-places@example.com',
            'password' => 'password',
            'mobile' => '1000000002',
        ]);

        $this->actingAs($stranger, 'admin')
            ->get(route('admin.places.index', ['lang' => 'en']))
            ->assertForbidden();
    }
}
