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
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 12 — the cross-ad schedules overview.
 *
 * The sidebar's "Schedules" entry used to point at `/ads?tab=schedules`. AdController
 * never read `tab`, so the link silently rendered the ads list and the item was a
 * placeholder. It now opens admin.ads.schedules.overview.
 */
class ScheduleOverviewTest extends TestCase
{
    use RefreshDatabase;

    private const FORBIDDEN_MARKERS = ['@vite', '/build/', 'x-data=', 'alpinejs', 'x-app-layout'];

    private Admin $admin;
    private Carbon $now;

    /** @var array<string, AdSchedule> */
    private array $schedules = [];

    private Screen $screenA;
    private Screen $screenB;
    private Ad $adA;
    private Ad $adB;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['ads.view', 'ads.schedule'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Overview',
            'last_name' => 'Tester',
            'email' => 'schedule-overview@example.com',
            'password' => 'password',
            'mobile' => '9000000001',
        ]);
        $this->admin->givePermissionTo(['ads.view', 'ads.schedule']);

        $this->now = Carbon::create(2026, 6, 15, 12, 0, 0);
        Carbon::setTestNow($this->now);

        $placeA = Place::factory()->create(['name' => ['en' => 'North Mall', 'ar' => 'المول الشمالي']]);
        $placeB = Place::factory()->create(['name' => ['en' => 'South Mall', 'ar' => 'المول الجنوبي']]);

        $this->screenA = Screen::factory()->create([
            'place_id' => $placeA->id,
            'code' => 'SCR-NORTH',
            'status' => ScreenStatus::Online->value,
        ]);
        $this->screenB = Screen::factory()->create([
            'place_id' => $placeB->id,
            'code' => 'SCR-SOUTH',
            'status' => ScreenStatus::Online->value,
        ]);

        $creator = User::factory()->create();

        $this->adA = Ad::create([
            'title' => ['en' => 'Alpha Campaign', 'ar' => 'حملة ألفا'],
            'file_path' => 'upload/ads/alpha.png',
            'file_type' => 'image',
            'duration_seconds' => 10,
            'status' => AdStatus::Active->value,
            'created_by' => $creator->id,
        ]);
        $this->adB = Ad::create([
            'title' => ['en' => 'Beta Campaign', 'ar' => 'حملة بيتا'],
            'file_path' => 'upload/ads/beta.png',
            'file_type' => 'image',
            'duration_seconds' => 10,
            'status' => AdStatus::Active->value,
            'created_by' => $creator->id,
        ]);

        // One row per state, so every filter and badge has something to match.
        $this->schedules['current'] = $this->makeSchedule($this->adA, $this->screenA, -1, 1, true);
        $this->schedules['upcoming'] = $this->makeSchedule($this->adA, $this->screenB, 24, 48, true);
        $this->schedules['ended'] = $this->makeSchedule($this->adB, $this->screenA, -48, -24, true);
        $this->schedules['inactive'] = $this->makeSchedule($this->adB, $this->screenB, -1, 1, false);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeSchedule(Ad $ad, Screen $screen, int $startOffsetHours, int $endOffsetHours, bool $isActive): AdSchedule
    {
        return AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $screen->id,
            'start_time' => $this->now->copy()->addHours($startOffsetHours),
            'end_time' => $this->now->copy()->addHours($endOffsetHours),
            'is_active' => $isActive,
        ]);
    }

    private function url(string $locale = 'en', array $query = []): string
    {
        return route('admin.ads.schedules.overview', array_merge(['lang' => $locale], $query));
    }

    private function visit(string $locale = 'en', array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'admin')->get($this->url($locale, $query));
    }

    /**
     * @return array<int, int>
     */
    private function listedScheduleIds(\Illuminate\Testing\TestResponse $response): array
    {
        return collect($response->viewData('schedules')->items())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function localeProvider(): array
    {
        return [
            'english' => ['en'],
            'arabic' => ['ar'],
        ];
    }

    // ------------------------------------------------------------------ rendering

    #[DataProvider('localeProvider')]
    public function test_the_overview_renders_in_both_locales(string $locale): void
    {
        $response = $this->visit($locale);

        $response->assertOk();
        $response->assertViewIs('admin.ads.schedules.overview');

        // Rows from every ad and screen, not just one ad's.
        $response->assertSee($locale === 'ar' ? 'حملة ألفا' : 'Alpha Campaign', false);
        $response->assertSee($locale === 'ar' ? 'حملة بيتا' : 'Beta Campaign', false);
        $response->assertSee('SCR-NORTH', false);
        $response->assertSee('SCR-SOUTH', false);

        // Canonical static admin layout: no Vite, no Alpine.
        $html = $response->getContent();
        foreach (self::FORBIDDEN_MARKERS as $marker) {
            $this->assertStringNotContainsString($marker, $html, "Overview leaked [{$marker}].");
        }
        $this->assertStringContainsString('admin-assets', $html);
    }

    #[DataProvider('localeProvider')]
    public function test_arabic_renders_right_to_left(string $locale): void
    {
        $html = $this->visit($locale)->assertOk()->getContent();

        $this->assertStringContainsString(
            $locale === 'ar' ? 'dir="rtl"' : 'dir="ltr"',
            $html
        );
    }

    public function test_every_state_is_rendered_with_its_own_label(): void
    {
        $response = $this->visit()->assertOk();

        $response->assertSee('Active now', false);
        $response->assertSee('Upcoming', false);
        $response->assertSee('Ended', false);
        $response->assertSee('Inactive', false);
    }

    public function test_a_row_links_to_the_per_ad_schedule_manager(): void
    {
        $response = $this->visit()->assertOk();

        $response->assertSee(
            route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->adA->id]),
            false
        );
    }

    // -------------------------------------------------------------------- filters

    public function test_the_ad_filter_narrows_the_list(): void
    {
        $ids = $this->listedScheduleIds($this->visit('en', ['ad_id' => $this->adA->id])->assertOk());

        $this->assertEqualsCanonicalizing(
            [$this->schedules['current']->id, $this->schedules['upcoming']->id],
            $ids
        );
    }

    public function test_the_screen_filter_narrows_the_list(): void
    {
        $ids = $this->listedScheduleIds($this->visit('en', ['screen_id' => $this->screenA->id])->assertOk());

        $this->assertEqualsCanonicalizing(
            [$this->schedules['current']->id, $this->schedules['ended']->id],
            $ids
        );
    }

    public function test_the_place_filter_narrows_the_list(): void
    {
        $ids = $this->listedScheduleIds(
            $this->visit('en', ['place_id' => $this->screenB->place_id])->assertOk()
        );

        $this->assertEqualsCanonicalizing(
            [$this->schedules['upcoming']->id, $this->schedules['inactive']->id],
            $ids
        );
    }

    /**
     * The state filter's SQL must agree with the badge on the row, including
     * end-exclusivity.
     */
    public function test_each_state_filter_returns_exactly_its_own_rows(): void
    {
        foreach (['current', 'upcoming', 'ended', 'inactive'] as $state) {
            $ids = $this->listedScheduleIds($this->visit('en', ['state' => $state])->assertOk());

            $this->assertSame(
                [$this->schedules[$state]->id],
                $ids,
                "The [{$state}] filter returned the wrong rows."
            );
        }
    }

    public function test_the_state_filter_and_the_rendered_badge_agree(): void
    {
        foreach (['current', 'upcoming', 'ended', 'inactive'] as $state) {
            $response = $this->visit('en', ['state' => $state])->assertOk();

            $row = collect($response->viewData('schedules')->items())->first();

            $this->assertSame($state, $row->currentState());
        }
    }

    public function test_the_date_range_filter_uses_the_existing_query_parameters(): void
    {
        $response = $this->visit('en', [
            'from_date' => $this->now->copy()->addHours(12)->format('Y-m-d'),
        ])->assertOk();

        $this->assertSame(
            [$this->schedules['upcoming']->id],
            $this->listedScheduleIds($response)
        );
    }

    public function test_an_unknown_state_value_is_ignored_rather_than_erroring(): void
    {
        $response = $this->visit('en', ['state' => 'not-a-state'])->assertOk();

        $this->assertCount(4, $this->listedScheduleIds($response));
    }

    // ----------------------------------------------------------------- pagination

    public function test_the_list_is_paginated_and_filters_survive_the_page_links(): void
    {
        // 25 per page; add enough rows to force a second page.
        for ($i = 0; $i < 26; $i++) {
            $this->makeSchedule($this->adA, $this->screenA, -1, 1, true);
        }

        $response = $this->visit('en', ['ad_id' => $this->adA->id])->assertOk();

        $paginator = $response->viewData('schedules');

        $this->assertSame(25, $paginator->perPage());
        $this->assertTrue($paginator->hasPages(), 'The overview must paginate rather than hydrate everything.');
        $this->assertStringContainsString('ad_id='.$this->adA->id, $paginator->nextPageUrl());
    }

    public function test_the_stats_header_counts_every_state(): void
    {
        $stats = $this->visit()->assertOk()->viewData('stats');

        $this->assertSame(4, $stats['total']);
        $this->assertSame(1, $stats['current']);
        $this->assertSame(1, $stats['upcoming']);
        $this->assertSame(1, $stats['ended']);
        $this->assertSame(1, $stats['inactive']);
    }

    // ---------------------------------------------------------------- permissions

    public function test_the_overview_is_forbidden_without_the_ads_view_permission(): void
    {
        $stranger = Admin::create([
            'first_name' => 'No',
            'last_name' => 'Access',
            'email' => 'schedule-overview-stranger@example.com',
            'password' => 'password',
            'mobile' => '9000000002',
        ]);

        $this->actingAs($stranger, 'admin')->get($this->url())->assertForbidden();
    }

    // -------------------------------------------------------------- menu wiring

    #[DataProvider('localeProvider')]
    public function test_the_sidebar_schedules_link_points_at_the_overview(string $locale): void
    {
        $html = $this->visit($locale)->assertOk()->getContent();

        $this->assertStringContainsString(e($this->url($locale)), $html);
        $this->assertStringNotContainsString('?tab=schedules', $html, 'The inert placeholder must be gone.');
    }

    /**
     * The overview URI must never be swallowed by /ads/{ad}.
     */
    public function test_the_overview_uri_is_not_bound_as_an_ad(): void
    {
        $response = $this->visit()->assertOk();

        $response->assertViewIs('admin.ads.schedules.overview');
        $this->assertSame(
            'admin.ads.schedules.overview',
            request()->route()?->getName() ?? 'admin.ads.schedules.overview'
        );
    }
}
