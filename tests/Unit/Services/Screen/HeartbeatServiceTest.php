<?php

namespace Tests\Unit\Services\Screen;

use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Place;
use App\Models\Screen;
use App\Services\Screen\HeartbeatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class HeartbeatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_updates_screen_and_creates_log_entry(): void
    {
        Carbon::setTestNow($now = Carbon::create(2024, 2, 1, 10, 15, 0));

        $screen = $this->createScreen();

        /** @var HeartbeatService $service */
        $service = app(HeartbeatService::class);

        $result = $service->touch($screen->id, 'device-001', 'AD-42');

        $this->assertNotNull($result);
        $this->assertSame('device-001', $result['screen']->device_uid);
        $this->assertTrue($result['screen']->last_heartbeat?->equalTo($now));
        $this->assertSame(ScreenStatus::Online, $result['screen']->status);

        $this->assertSame('AD-42', $result['log']->current_ad_code);
        // ScreenLog casts `status` to the ScreenStatus enum, so compare enum to enum.
        $this->assertSame(ScreenStatus::Online, $result['log']->status);
        $this->assertTrue($result['log']->reported_at?->equalTo($now));
    }

    /**
     * A heartbeat is proof the device is reachable, so it can never be the thing
     * that makes the server believe the same device is offline. Only silence,
     * detected by CheckScreenHealthJob, does that.
     */
    public function test_a_device_cannot_declare_itself_offline(): void
    {
        Carbon::setTestNow($now = Carbon::create(2024, 3, 5, 8, 0, 0));

        $screen = $this->createScreen(['device_uid' => 'device-777']);

        $result = app(HeartbeatService::class)->touch($screen->id, 'device-777', [
            'status' => ScreenStatus::Offline,
            'current_ad_code' => null,
        ]);

        $this->assertNotNull($result);
        $this->assertSame(ScreenStatus::Online, $result['screen']->status);
        $this->assertSame(ScreenStatus::Online, $result['log']->status);
        $this->assertTrue($result['screen']->last_heartbeat?->equalTo($now));
    }

    /**
     * Maintenance is a real device mode: reachable, but not serving.
     */
    public function test_a_device_may_declare_maintenance(): void
    {
        $screen = $this->createScreen(['device_uid' => 'device-778']);

        $result = app(HeartbeatService::class)->touch($screen->id, 'device-778', [
            'status' => ScreenStatus::Maintenance,
        ]);

        $this->assertSame(ScreenStatus::Maintenance, $result['screen']->status);
    }

    /**
     * Maintenance set by an administrator is sticky. A heartbeat still refreshes
     * last_heartbeat — the device really is reachable — but does not quietly
     * return the screen to service behind the operator's back.
     */
    public function test_a_heartbeat_does_not_clear_administrator_maintenance(): void
    {
        Carbon::setTestNow($now = Carbon::create(2024, 3, 5, 9, 0, 0));

        $screen = $this->createScreen([
            'device_uid' => 'device-779',
            'status' => ScreenStatus::Maintenance,
        ]);

        $result = app(HeartbeatService::class)->touch($screen->id, 'device-779', [
            'status' => ScreenStatus::Online,
        ]);

        $this->assertSame(ScreenStatus::Maintenance, $result['screen']->status);
        $this->assertTrue($result['screen']->last_heartbeat?->equalTo($now));
    }

    /**
     * `reported_at` is device telemetry that orders the log stream, so it is
     * clamped to the window the signed request could legitimately have come
     * from. It never reaches `last_heartbeat`.
     */
    public function test_client_reported_at_is_stored_but_clamped(): void
    {
        Carbon::setTestNow($now = Carbon::create(2024, 3, 5, 10, 0, 0));
        config(['services.screens.signature_leeway' => 300]);

        $screen = $this->createScreen(['device_uid' => 'device-780']);
        $service = app(HeartbeatService::class);

        // Within leeway: kept verbatim.
        $recent = $service->touch($screen->id, 'device-780', [
            'reported_at' => $now->copy()->subSeconds(120),
        ]);
        $this->assertTrue($recent['log']->reported_at->equalTo($now->copy()->subSeconds(120)));

        // Far in the past: clamped forward to the leeway boundary.
        $stale = $service->touch($screen->id, 'device-780', [
            'reported_at' => $now->copy()->subDays(30),
        ]);
        $this->assertTrue($stale['log']->reported_at->equalTo($now->copy()->subSeconds(300)));

        // In the future: clamped back to server receipt.
        $future = $service->touch($screen->id, 'device-780', [
            'reported_at' => $now->copy()->addYear(),
        ]);
        $this->assertTrue($future['log']->reported_at->equalTo($now));

        // Through all of it, last_heartbeat is server time.
        $this->assertTrue($future['screen']->last_heartbeat->equalTo($now));
    }

    public function test_mark_offline_preserves_the_last_heartbeat(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 3, 5, 12, 0, 0));

        $screen = $this->createScreen(['status' => ScreenStatus::Online]);
        $screen->forceFill(['last_heartbeat' => now()->subHour()])->save();
        $staleHeartbeat = $screen->last_heartbeat;

        $log = app(HeartbeatService::class)->markOffline($screen->fresh());

        $this->assertNotNull($log);
        $this->assertSame(ScreenStatus::Offline, $screen->fresh()->status);
        $this->assertTrue(
            $screen->fresh()->last_heartbeat->equalTo($staleHeartbeat),
            'Going offline must not rewrite the evidence of when the device was last heard from.'
        );
    }

    public function test_mark_offline_is_a_no_op_for_ineligible_screens(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 3, 5, 12, 0, 0));

        $service = app(HeartbeatService::class);

        // Already offline.
        $offline = $this->createScreen(['status' => ScreenStatus::Offline]);
        $offline->forceFill(['last_heartbeat' => now()->subHour()])->save();
        $this->assertNull($service->markOffline($offline->fresh()));

        // Under maintenance: operators own it, alerting is suppressed.
        $maintenance = $this->createScreen(['status' => ScreenStatus::Maintenance]);
        $maintenance->forceFill(['last_heartbeat' => now()->subHour()])->save();
        $this->assertNull($service->markOffline($maintenance->fresh()));

        // Online and fresh.
        $fresh = $this->createScreen(['status' => ScreenStatus::Online]);
        $fresh->forceFill(['last_heartbeat' => now()])->save();
        $this->assertNull($service->markOffline($fresh->fresh()));

        $this->assertSame(0, $offline->logs()->count());
        $this->assertSame(0, $maintenance->logs()->count());
        $this->assertSame(0, $fresh->logs()->count());
    }

    public function test_it_returns_null_when_screen_is_missing(): void
    {
        /** @var HeartbeatService $service */
        $service = app(HeartbeatService::class);

        $this->assertNull($service->touch(999999, 'missing-device', null));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createScreen(array $overrides = []): Screen
    {
        $place = Place::create([
            'name' => ['en' => 'Test Place'],
            'address' => ['en' => '123 Test Street'],
            'type' => PlaceType::Mall,
        ]);

        return Screen::create(array_merge([
            'place_id' => $place->id,
            'code' => 'SCR-'.Str::random(6),
            'status' => ScreenStatus::Offline,
        ], $overrides));
    }
}
