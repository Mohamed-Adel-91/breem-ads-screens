<?php

namespace Tests\Feature;

use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Screen;
use Database\Seeders\ContactUsPageSeeder;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\WhoWeArePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 11 changed heartbeat, offline detection and monitoring semantics only.
 * These assertions prove nothing else moved.
 */
class Phase11RegressionTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_PERMISSIONS = [
        'screens.view', 'screens.edit', 'ads.view', 'ads.edit',
        'monitoring.view', 'monitoring.manage', 'reports.view', 'places.view',
    ];

    private function adminUser(): Admin
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ADMIN_PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $admin = Admin::create([
            'first_name' => 'Regression',
            'last_name' => 'Admin',
            'email' => 'regression-admin@example.com',
            'password' => 'password',
            'mobile' => '7300000001',
        ]);
        $admin->givePermissionTo(self::ADMIN_PERMISSIONS);

        return $admin;
    }

    private function seedScreen(): Screen
    {
        $place = Place::create([
            'name' => ['en' => 'Regression Hall', 'ar' => 'قاعة الاختبار'],
            'address' => ['en' => '7 Regression Road', 'ar' => 'شارع الاختبار'],
            'type' => PlaceType::Other,
        ]);

        $screen = Screen::create([
            'place_id' => $place->id,
            'code' => 'SCR-REG-1',
            'device_uid' => 'uid-reg-1',
            'status' => ScreenStatus::Online,
            'last_heartbeat' => now(),
        ]);

        $screen->logs()->create([
            'status' => ScreenStatus::Online->value,
            'reported_at' => now()->subMinutes(2),
        ]);

        return $screen;
    }

    public static function publicPageProvider(): array
    {
        return [
            'ar home' => ['/ar'],
            'en home' => ['/en'],
            'ar whoweare' => ['/ar/whoweare'],
            'en whoweare' => ['/en/whoweare'],
            'ar contact' => ['/ar/contact-us'],
            'en contact' => ['/en/contact-us'],
        ];
    }

    #[DataProvider('publicPageProvider')]
    public function test_public_pages_still_return_200(string $url): void
    {
        // The public site is CMS-driven; the pages must exist and be active.
        $this->seed(HomePageSeeder::class);
        $this->seed(WhoWeArePageSeeder::class);
        $this->seed(ContactUsPageSeeder::class);

        $this->get($url)->assertOk();
    }

    public static function adminPageProvider(): array
    {
        $pages = [
            'dashboard' => 'admin.dashboard',
            'screens' => 'admin.screens.index',
            'ads' => 'admin.ads.index',
            'monitoring' => 'admin.monitoring.index',
            'reports' => 'admin.reports.index',
            'places' => 'admin.places.index',
        ];

        $cases = [];

        foreach ($pages as $label => $route) {
            foreach (['en', 'ar'] as $locale) {
                $cases["{$label} {$locale}"] = [$route, $locale];
            }
        }

        return $cases;
    }

    #[DataProvider('adminPageProvider')]
    public function test_admin_pages_render_in_both_locales(string $routeName, string $locale): void
    {
        $this->seedScreen();

        $response = $this->actingAs($this->adminUser(), 'admin')
            ->get(route($routeName, ['lang' => $locale]));

        $response->assertOk();

        // The canonical static admin architecture is untouched.
        $response->assertSee('admin-assets/css/breem-admin.css', false);
        $response->assertDontSee('@vite', false);
        $response->assertDontSee('/build/assets/', false);
        $response->assertDontSee('x-app-layout', false);
    }

    public function test_the_monitoring_and_screen_detail_pages_render(): void
    {
        $screen = $this->seedScreen();
        $admin = $this->adminUser();

        foreach (['en', 'ar'] as $locale) {
            $this->actingAs($admin, 'admin')
                ->get(route('admin.monitoring.screens.show', ['lang' => $locale, 'screen' => $screen->id]))
                ->assertOk();

            $this->actingAs($admin, 'admin')
                ->get(route('admin.screens.show', ['lang' => $locale, 'screen' => $screen->id]))
                ->assertOk();
        }
    }

    /**
     * Custom paginator names carry query state across the two tables on the
     * detail pages; one must never reset the other.
     */
    public function test_the_custom_monitoring_paginator_names_are_preserved(): void
    {
        $screen = $this->seedScreen();

        $response = $this->actingAs($this->adminUser(), 'admin')
            ->get(route('admin.monitoring.screens.show', ['lang' => 'en', 'screen' => $screen->id]));

        $response->assertOk();
        $this->assertSame('logs_page', $response->viewData('recentLogs')->getPageName());
        $this->assertSame('playbacks_page', $response->viewData('recentPlaybacks')->getPageName());
    }

    public function test_the_screen_detail_paginator_names_are_preserved(): void
    {
        $screen = $this->seedScreen();

        $response = $this->actingAs($this->adminUser(), 'admin')
            ->get(route('admin.screens.show', ['lang' => 'en', 'screen' => $screen->id]));

        $response->assertOk();
        $this->assertSame('logs_page', $response->viewData('recentLogs')->getPageName());
        $this->assertSame('playbacks_page', $response->viewData('recentPlaybacks')->getPageName());
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function deviceApiRouteProvider(): array
    {
        return [
            'handshake' => ['api.v1.screens.handshake', 'POST', 'api/v1/screens/handshake'],
            'heartbeat' => ['api.v1.screens.heartbeat', 'POST', 'api/v1/screens/heartbeat'],
            'playlist' => ['api.v1.screens.playlist', 'GET', 'api/v1/screens/{screen}/playlist'],
            'playbacks' => ['api.v1.playbacks.store', 'POST', 'api/v1/playbacks'],
            'config' => ['api.v1.config.show', 'GET', 'api/v1/config'],
        ];
    }

    #[DataProvider('deviceApiRouteProvider')]
    public function test_the_device_api_contract_is_unchanged(string $name, string $method, string $uri): void
    {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Route [{$name}] disappeared.");
        $this->assertSame($uri, $route->uri(), "Route [{$name}] URI changed.");
        $this->assertContains($method, $route->methods());
    }
}
