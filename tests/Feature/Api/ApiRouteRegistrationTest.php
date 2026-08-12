<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Phase 9 — guards the Device API registration.
 *
 * bootstrap/app.php passes a custom `using:` callback to withRouting(), which
 * REPLACES Laravel's default registration. The `api:` and `health:` arguments
 * beside it were therefore ignored and routes/api.php was never loaded, so every
 * /api/v1/* URL 404'd. These tests fail loudly if that regresses.
 */
class ApiRouteRegistrationTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function apiRouteProvider(): array
    {
        return [
            'handshake' => ['api.v1.screens.handshake', 'POST', 'api/v1/screens/handshake'],
            'heartbeat' => ['api.v1.screens.heartbeat', 'POST', 'api/v1/screens/heartbeat'],
            'playlist' => ['api.v1.screens.playlist', 'GET', 'api/v1/screens/{screen}/playlist'],
            'playbacks' => ['api.v1.playbacks.store', 'POST', 'api/v1/playbacks'],
            'config' => ['api.v1.config.show', 'GET', 'api/v1/config'],
        ];
    }

    /**
     * @dataProvider apiRouteProvider
     */
    public function test_device_api_routes_are_registered(string $name, string $method, string $uri): void
    {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Route [{$name}] is not registered.");
        $this->assertSame($uri, $route->uri(), "Route [{$name}] URI drifted.");
        $this->assertContains($method, $route->methods(), "Route [{$name}] lost its {$method} verb.");
    }

    public function test_the_api_prefix_is_applied_exactly_once(): void
    {
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();

            $this->assertStringNotContainsString('api/api/', $uri, "Double api prefix on [{$uri}].");
            $this->assertStringNotContainsString('v1/v1/', $uri, "Double v1 prefix on [{$uri}].");
        }
    }

    public function test_every_api_route_carries_the_api_middleware_and_v1_throttle(): void
    {
        foreach (self::apiRouteProvider() as [$name, , ]) {
            $route = Route::getRoutes()->getByName($name);
            $middleware = $route->gatherMiddleware();

            $this->assertContains('api', $middleware, "[{$name}] lost the api middleware group.");
            $this->assertContains('throttle:api.v1', $middleware, "[{$name}] lost the v1 rate limiter.");
        }
    }

    public function test_device_authentication_guards_every_endpoint_except_the_handshake(): void
    {
        // The group applies screen.auth and the handshake opts out via
        // withoutMiddleware(), which Laravel records as an exclusion.
        $handshake = Route::getRoutes()->getByName('api.v1.screens.handshake');
        $this->assertContains(
            'screen.auth',
            $handshake->excludedMiddleware(),
            'The handshake must stay reachable by an unpaired device.'
        );

        foreach (['api.v1.screens.heartbeat', 'api.v1.screens.playlist', 'api.v1.playbacks.store', 'api.v1.config.show'] as $name) {
            $this->assertContains(
                'screen.auth',
                Route::getRoutes()->getByName($name)->gatherMiddleware(),
                "[{$name}] must sit behind screen.auth."
            );
        }
    }

    public function test_registering_the_api_did_not_disturb_web_or_admin_routes(): void
    {
        foreach (['web.home', 'web.whoweare', 'web.contactUs', 'web.contact.submit'] as $name) {
            $this->assertNotNull(Route::getRoutes()->getByName($name), "Web route [{$name}] disappeared.");
        }

        foreach ([
            'admin.dashboard', 'admin.login', 'admin.places.index', 'admin.screens.index',
            'admin.ads.index', 'admin.ads.schedules.index', 'admin.monitoring.index',
            'admin.reports.index', 'admin.roles.index', 'admin.users.index',
        ] as $name) {
            $this->assertNotNull(Route::getRoutes()->getByName($name), "Admin route [{$name}] disappeared.");
        }

        $adminRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'admin.'))
            ->count();

        $this->assertSame(93, $adminRoutes, 'The admin route surface changed.');
    }

    public function test_the_health_endpoint_declared_in_bootstrap_is_reachable(): void
    {
        $this->get('/up')->assertOk();
    }
}
