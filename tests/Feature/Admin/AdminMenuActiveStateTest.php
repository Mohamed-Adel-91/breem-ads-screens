<?php

namespace Tests\Feature\Admin;

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use App\Services\Admin\MenuBuilder;
use Database\Seeders\ContactUsPageSeeder;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WhoWeArePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Sidebar active-state contract.
 *
 * "All Ads" and "Schedules" both point at admin.ads.index, and MenuBuilder used
 * to treat an item's own route as an implicit active pattern. Visiting /ads
 * therefore lit up both children at once. The rules now live entirely in
 * config/admin_menu.php: a route pattern may carry query constraints, so two
 * siblings sharing one route stay mutually exclusive.
 *
 * Invariant asserted throughout: a menu group never has more than one active
 * child. The parent may stay active/open at the same time — that is expected.
 */
class AdminMenuActiveStateTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(HomePageSeeder::class);
        $this->seed(WhoWeArePageSeeder::class);
        $this->seed(ContactUsPageSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = Admin::create([
            'first_name' => 'Menu',
            'last_name' => 'Tester',
            'email' => 'menu-active@example.com',
            'password' => 'password',
            'mobile' => '9600000001',
        ]);
        $this->admin->assignRole(Role::findOrCreate('super-admin', 'admin'));
        $this->admin->givePermissionTo(Permission::where('guard_name', 'admin')->pluck('name')->all());

        $place = Place::factory()->create();
        Screen::factory()->create(['place_id' => $place->id]);

        $this->ad = Ad::create([
            'title' => ['en' => 'Menu Ad', 'ar' => 'إعلان القائمة'],
            'file_path' => 'upload/ads/creative.png',
            'file_type' => 'image',
            'duration_seconds' => 10,
            'status' => AdStatus::Active->value,
            'created_by' => User::factory()->create()->id,
        ]);
    }

    /**
     * Visit a URL, then rebuild the menu for that request context.
     *
     * @return array<string, mixed>
     */
    private function groupAfterVisiting(string $url, string $groupKey): array
    {
        // The menu is cached per admin/locale/permission signature, and the probe
        // must reflect this request rather than a previous one.
        Cache::flush();

        $this->actingAs($this->admin, 'admin')->get($url)->assertOk();

        $group = collect(app(MenuBuilder::class)->build('static-sidebar'))
            ->firstWhere('key', $groupKey);

        $this->assertNotNull($group, "Menu group [{$groupKey}] is missing.");

        return $group;
    }

    /**
     * @return array<int, string>
     */
    private function activeChildKeys(array $group): array
    {
        return collect($group['children'] ?? [])
            ->filter(fn (array $child) => (bool) ($child['is_active'] ?? false))
            ->pluck('key')
            ->values()
            ->all();
    }

    private function assertExactlyOneActiveChild(array $group, string $expectedKey, string $context): void
    {
        $this->assertSame(
            [$expectedKey],
            $this->activeChildKeys($group),
            "{$context}: exactly one child must be active."
        );
    }

    public static function localeProvider(): array
    {
        return [
            'english' => ['en'],
            'arabic' => ['ar'],
        ];
    }

    #[DataProvider('localeProvider')]
    public function test_ads_index_activates_only_all_ads(string $locale): void
    {
        $group = $this->groupAfterVisiting("/{$locale}/admin-panel/ads", 'ads_system');

        $this->assertTrue($group['is_open'], 'Ads System must stay open.');
        $this->assertTrue($group['is_active'], 'Ads System may stay highlighted.');

        $this->assertExactlyOneActiveChild($group, 'ads_system_all_ads', "/{$locale}/admin-panel/ads");
    }

    /**
     * The Schedules entry now opens a real page of its own. It used to point at
     * `/ads?tab=schedules`, a parameter AdController never read.
     */
    #[DataProvider('localeProvider')]
    public function test_the_schedules_overview_activates_only_schedules(string $locale): void
    {
        $url = route('admin.ads.schedules.overview', ['lang' => $locale]);

        $group = $this->groupAfterVisiting($url, 'ads_system');

        $this->assertTrue($group['is_open'], 'Ads System must stay open.');

        $this->assertExactlyOneActiveChild($group, 'ads_system_schedules', $url);
    }

    /**
     * A bookmark on the retired placeholder must still resolve sensibly: it is
     * the ads index, so All Ads owns it and nothing is left with zero highlights.
     */
    #[DataProvider('localeProvider')]
    public function test_the_retired_schedules_tab_query_activates_all_ads(string $locale): void
    {
        $group = $this->groupAfterVisiting("/{$locale}/admin-panel/ads?tab=schedules", 'ads_system');

        $this->assertExactlyOneActiveChild(
            $group,
            'ads_system_all_ads',
            "/{$locale}/admin-panel/ads?tab=schedules"
        );
    }

    #[DataProvider('localeProvider')]
    public function test_nested_schedule_route_activates_only_schedules(string $locale): void
    {
        $group = $this->groupAfterVisiting(
            "/{$locale}/admin-panel/ads/{$this->ad->id}/schedules",
            'ads_system'
        );

        $this->assertTrue($group['is_open'], 'Ads System must stay open.');

        $this->assertExactlyOneActiveChild(
            $group,
            'ads_system_schedules',
            'admin.ads.schedules.index'
        );
    }

    public function test_ad_create_edit_and_show_keep_all_ads_active_and_schedules_inactive(): void
    {
        $urls = [
            'create' => '/en/admin-panel/ads/create',
            'show' => '/en/admin-panel/ads/' . $this->ad->id,
            'edit' => '/en/admin-panel/ads/' . $this->ad->id . '/edit',
        ];

        foreach ($urls as $label => $url) {
            $group = $this->groupAfterVisiting($url, 'ads_system');

            $this->assertTrue($group['is_open'], "Ads System must stay open on {$label}.");
            $this->assertExactlyOneActiveChild($group, 'ads_system_all_ads', $label);
        }
    }

    public function test_sibling_ads_system_entries_are_unaffected(): void
    {
        $cases = [
            '/en/admin-panel/screens' => 'ads_system_screens',
            '/en/admin-panel/places' => 'ads_system_places',
            '/en/admin-panel/monitoring' => 'ads_system_monitoring',
            '/en/admin-panel/reports' => 'ads_system_reports',
            '/en/admin-panel/logs' => 'ads_system_logs',
        ];

        foreach ($cases as $url => $expectedKey) {
            $group = $this->groupAfterVisiting($url, 'ads_system');

            $this->assertExactlyOneActiveChild($group, $expectedKey, $url);
        }
    }

    /**
     * Regression guard for the other nested groups: removing the implicit
     * own-route fallback must not have changed their behaviour.
     */
    public function test_other_nested_menu_groups_still_resolve_exactly_one_active_child(): void
    {
        $cases = [
            ['admin.admins.index', 'admins_management', 'admins_management_admins'],
            ['admin.permissions.index', 'admins_management', 'admins_management_permissions'],
            ['admin.roles.index', 'admins_management', 'admins_management_roles'],
            ['admin.users.index', 'users_management', 'users_management_all_users'],
            ['admin.users.create', 'users_management', 'users_management_create_user'],
            ['admin.seo_metas.index', 'website_cms', 'website_cms_seo_metas'],
            ['admin.cms.home.edit', 'website_cms', 'website_cms_home_page'],
            ['admin.cms.whoweare.edit', 'website_cms', 'website_cms_who_we_are'],
            ['admin.cms.contact.edit', 'website_cms', 'website_cms_contact_us'],
            ['admin.contact_submissions.index', 'contact_submissions', 'contact_submissions_all'],
        ];

        foreach ($cases as [$routeName, $groupKey, $expectedKey]) {
            $url = route($routeName, ['lang' => 'en']);
            $group = $this->groupAfterVisiting($url, $groupKey);

            $this->assertTrue($group['is_open'], "{$groupKey} must be open on {$url}.");
            $this->assertExactlyOneActiveChild($group, $expectedKey, $url);
        }
    }

    /**
     * The complaint was visual, so assert the rendered markup too: the sidebar
     * must carry the `active` class on exactly one of the two Ads child links.
     */
    #[DataProvider('localeProvider')]
    public function test_rendered_sidebar_marks_only_one_ads_child_link_active(string $locale): void
    {
        $allAdsUrl = route('admin.ads.index', ['lang' => $locale]);
        $schedulesUrl = route('admin.ads.schedules.overview', ['lang' => $locale]);

        $cases = [
            $allAdsUrl => ['active' => $allAdsUrl, 'inactive' => $schedulesUrl],
            $schedulesUrl => ['active' => $schedulesUrl, 'inactive' => $allAdsUrl],
        ];

        foreach ($cases as $visit => $expected) {
            Cache::flush();

            $html = $this->actingAs($this->admin, 'admin')->get($visit)->assertOk()->getContent();

            $activeHref = $this->sidebarLinkIsActive($html, $expected['active']);
            $inactiveHref = $this->sidebarLinkIsActive($html, $expected['inactive']);

            $this->assertTrue($activeHref, "{$visit}: expected {$expected['active']} to render as active.");
            $this->assertFalse($inactiveHref, "{$visit}: expected {$expected['inactive']} NOT to render as active.");
        }
    }

    /**
     * Does the sidebar <a> pointing at $href carry the `active` class?
     */
    private function sidebarLinkIsActive(string $html, string $href): bool
    {
        $quoted = preg_quote(e($href), '/');

        if (!preg_match('/<a[^>]*href="' . $quoted . '"[^>]*>/', $html, $matches)) {
            $this->fail("Sidebar link for [{$href}] was not rendered at all.");
        }

        return str_contains($matches[0], 'active');
    }

    public function test_no_menu_group_ever_reports_more_than_one_active_child(): void
    {
        $urls = [
            '/en/admin-panel',
            '/en/admin-panel/ads',
            '/en/admin-panel/ads?tab=schedules',
            '/en/admin-panel/ads/schedules/overview',
            '/en/admin-panel/ads/' . $this->ad->id . '/schedules',
            '/en/admin-panel/screens',
            '/en/admin-panel/places',
            '/en/admin-panel/monitoring',
            '/en/admin-panel/reports',
            '/en/admin-panel/logs',
            '/ar/admin-panel/ads',
            '/ar/admin-panel/ads?tab=schedules',
            '/ar/admin-panel/ads/schedules/overview',
        ];

        foreach ($urls as $url) {
            Cache::flush();
            $this->actingAs($this->admin, 'admin')->get($url)->assertOk();

            foreach (app(MenuBuilder::class)->build('static-sidebar') as $group) {
                $active = $this->activeChildKeys($group);

                $this->assertLessThanOrEqual(
                    1,
                    count($active),
                    sprintf('%s: group [%s] has %d active children (%s).',
                        $url, $group['key'], count($active), implode(', ', $active))
                );
            }
        }
    }
}
