<?php

namespace Tests\Feature\Admin;

use App\Enums\AdStatus;
use App\Enums\ScreenStatus;
use App\Models\Ad;
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
 * Phase 6 — guards the Ads admin UI against a regression back to the
 * Vite / Tailwind / Alpine architecture, and pins the form, filter, status and
 * media contracts that AdController and Store/UpdateAdRequest rely on.
 *
 * Every record is created inside the isolated in-memory SQLite database used by
 * the test suite; no real ad, creative file, assignment or schedule is touched.
 */
class AdsAdminViewsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected User $owner;

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

        foreach (['ads.view', 'ads.create', 'ads.edit', 'ads.delete', 'ads.schedule'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Ads',
            'last_name' => 'Tester',
            'email' => 'ads-tester@example.com',
            'password' => 'password',
            'mobile' => '3000000001',
        ]);

        $this->admin->givePermissionTo(['ads.view', 'ads.create', 'ads.edit', 'ads.delete', 'ads.schedule']);

        $this->owner = User::factory()->create(['name' => 'Campaign Owner']);
    }

    protected function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
    }

    protected function makeAd(array $overrides = []): Ad
    {
        return Ad::create(array_merge([
            'title' => ['en' => 'Summer Campaign', 'ar' => 'حملة الصيف'],
            'description' => ['en' => 'English copy', 'ar' => 'نص عربي'],
            'file_path' => 'upload/ads/creative.png',
            'file_type' => 'image',
            'duration_seconds' => 15,
            'status' => AdStatus::Active->value,
            'created_by' => $this->owner->id,
        ], $overrides));
    }

    protected function makeScreen(array $overrides = []): Screen
    {
        $place = Place::factory()->create(['name' => ['en' => 'Harbour Mall', 'ar' => 'مول الميناء']]);

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
    public function test_ads_index_renders_the_canonical_static_layout(string $locale): void
    {
        $screen = $this->makeScreen(['code' => 'SCR-AD-1']);
        $ad = $this->makeAd();
        $ad->screens()->attach($screen->id, ['play_order' => 3]);

        $response = $this->actingAsAdmin()->get(route('admin.ads.index', ['lang' => $locale]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.ads.index');

        // Filter parameter names are part of the controller contract.
        $response->assertSee('name="search"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('name="screen_id"', false);
        $response->assertSee('name="from_date"', false);
        $response->assertSee('name="to_date"', false);

        $response->assertSee($locale === 'ar' ? 'حملة الصيف' : 'Summer Campaign', false);
        $response->assertSee('Campaign Owner', false);
    }

    #[DataProvider('localeProvider')]
    public function test_ads_create_renders_the_canonical_static_layout(string $locale): void
    {
        $this->makeScreen(['code' => 'SCR-CREATE-1']);

        $response = $this->actingAsAdmin()->get(route('admin.ads.create', ['lang' => $locale]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.ads.create');

        // Exact field names expected by StoreAdRequest.
        $response->assertSee('name="title[en]"', false);
        $response->assertSee('name="title[ar]"', false);
        $response->assertSee('name="description[en]"', false);
        $response->assertSee('name="description[ar]"', false);
        $response->assertSee('name="creative"', false);
        $response->assertSee('name="duration_seconds"', false);
        $response->assertSee('name="status"', false);
        $response->assertSee('name="created_by"', false);
        $response->assertSee('name="approved_by"', false);
        $response->assertSee('name="start_date"', false);
        $response->assertSee('name="end_date"', false);
        $response->assertSee('name="screens[]"', false);
        $response->assertSee('name="play_order[', false);

        // Multipart is mandatory for the creative upload.
        $response->assertSee('enctype="multipart/form-data"', false);
        $response->assertSee(route('admin.ads.store', ['lang' => $locale]), false);
    }

    #[DataProvider('localeProvider')]
    public function test_ads_edit_renders_the_canonical_static_layout(string $locale): void
    {
        $screen = $this->makeScreen(['code' => 'SCR-EDIT-AD']);
        $ad = $this->makeAd();
        $ad->screens()->attach($screen->id, ['play_order' => 7]);

        $response = $this->actingAsAdmin()->get(route('admin.ads.edit', ['lang' => $locale, 'ad' => $ad->id]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.ads.edit');
        $response->assertSee('name="_method"', false);
        $response->assertSee('enctype="multipart/form-data"', false);
        $response->assertSee(route('admin.ads.update', ['lang' => $locale, 'ad' => $ad->id]), false);

        // Stored translations render per locale, independently of the dashboard locale.
        $response->assertSee('Summer Campaign', false);
        $response->assertSee('حملة الصيف', false);

        // The existing play order is preserved in the form.
        $response->assertSee('value="7"', false);
    }

    #[DataProvider('localeProvider')]
    public function test_ads_show_renders_the_canonical_static_layout(string $locale): void
    {
        $screen = $this->makeScreen(['code' => 'SCR-SHOW-AD']);
        $ad = $this->makeAd();
        $ad->screens()->attach($screen->id, ['play_order' => 2]);

        $response = $this->actingAsAdmin()->get(route('admin.ads.show', ['lang' => $locale, 'ad' => $ad->id]));

        $this->assertCanonicalStaticPage($response);
        $response->assertViewIs('admin.ads.show');
        $response->assertSee('SCR-SHOW-AD', false);
        $response->assertSee(route('admin.ads.schedules.index', ['lang' => $locale, 'ad' => $ad->id]), false);
        $response->assertSee(route('admin.ads.destroy', ['lang' => $locale, 'ad' => $ad->id]), false);
    }

    public function test_ad_status_is_rendered_with_semantic_badges_and_stored_values_are_untouched(): void
    {
        foreach (AdStatus::cases() as $index => $status) {
            $this->makeAd([
                'title' => ['en' => 'Ad ' . $status->value],
                'status' => $status->value,
                'duration_seconds' => $index,
            ]);
        }

        $response = $this->actingAsAdmin()->get(route('admin.ads.index', ['lang' => 'en']));

        $response->assertOk();
        $response->assertSee('badge-success', false);   // active
        $response->assertSee('badge-info', false);      // approved
        $response->assertSee('badge-warning', false);   // pending
        $response->assertSee('badge-danger', false);    // rejected
        $response->assertSee('badge-secondary', false); // expired

        foreach (AdStatus::cases() as $status) {
            $this->assertDatabaseHas('ads', ['status' => $status->value]);
        }
    }

    public function test_image_and_video_creatives_use_the_canonical_media_presentation(): void
    {
        $imageAd = $this->makeAd([
            'file_path' => 'upload/ads/banner.png',
            'file_type' => 'image',
        ]);

        $imageResponse = $this->actingAsAdmin()->get(route('admin.ads.show', ['lang' => 'en', 'ad' => $imageAd->id]));
        $imageResponse->assertOk();
        $imageResponse->assertSee('admin-media-preview', false);
        $imageResponse->assertSee('upload/ads/banner.png', false);
        $imageResponse->assertDontSee('<video', false);

        $videoAd = $this->makeAd([
            'title' => ['en' => 'Video Campaign'],
            'file_path' => 'upload/ads/spot.mp4',
            'file_type' => 'video',
        ]);

        $videoResponse = $this->actingAsAdmin()->get(route('admin.ads.show', ['lang' => 'en', 'ad' => $videoAd->id]));
        $videoResponse->assertOk();
        $videoResponse->assertSee('<video', false);
        $videoResponse->assertSee('upload/ads/spot.mp4', false);
    }

    public function test_ads_index_status_filter_uses_the_existing_query_parameter(): void
    {
        $this->makeAd(['title' => ['en' => 'Wanted Ad'], 'status' => AdStatus::Pending->value]);
        $this->makeAd(['title' => ['en' => 'Other Ad'], 'status' => AdStatus::Expired->value]);

        $response = $this->actingAsAdmin()->get(
            route('admin.ads.index', ['lang' => 'en']) . '?status=pending'
        );

        $response->assertOk();
        $response->assertSee('Wanted Ad', false);
        $response->assertDontSee('Other Ad', false);
    }

    public function test_ads_index_screen_filter_uses_the_existing_query_parameter(): void
    {
        $wantedScreen = $this->makeScreen(['code' => 'SCR-WANTED-AD']);
        $otherScreen = $this->makeScreen(['code' => 'SCR-OTHER-AD']);

        $wantedAd = $this->makeAd(['title' => ['en' => 'Attached Ad']]);
        $wantedAd->screens()->attach($wantedScreen->id, ['play_order' => 0]);

        $otherAd = $this->makeAd(['title' => ['en' => 'Unattached Ad']]);
        $otherAd->screens()->attach($otherScreen->id, ['play_order' => 0]);

        $response = $this->actingAsAdmin()->get(
            route('admin.ads.index', ['lang' => 'en']) . '?screen_id=' . $wantedScreen->id
        );

        $response->assertOk();
        $response->assertSee('Attached Ad', false);
        $response->assertDontSee('Unattached Ad', false);
    }

    public function test_ads_index_filters_survive_pagination(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->makeAd([
                'title' => ['en' => 'Paged Ad ' . $i],
                'status' => AdStatus::Active->value,
            ]);
        }

        $response = $this->actingAsAdmin()->get(
            route('admin.ads.index', ['lang' => 'en']) . '?status=active&page=2'
        );

        $response->assertOk();
        $response->assertSee('status=active', false);
        $this->assertSame('page', $response->viewData('ads')->getPageName());
    }

    public function test_ads_index_is_forbidden_without_the_view_permission(): void
    {
        $stranger = Admin::create([
            'first_name' => 'No',
            'last_name' => 'Access',
            'email' => 'no-access-ads@example.com',
            'password' => 'password',
            'mobile' => '3000000002',
        ]);

        $this->actingAs($stranger, 'admin')
            ->get(route('admin.ads.index', ['lang' => 'en']))
            ->assertForbidden();
    }
}
