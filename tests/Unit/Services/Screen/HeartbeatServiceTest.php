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
        $this->assertSame(ScreenStatus::Online->value, $result['log']->status);
        $this->assertTrue($result['log']->reported_at?->equalTo($now));
    }

    public function test_it_allows_overriding_status_and_timestamps(): void
    {
        $heartbeatAt = Carbon::create(2024, 3, 5, 8, 0, 0);
        $reportedAt = Carbon::create(2024, 3, 5, 7, 55, 0);

        $screen = $this->createScreen(['device_uid' => 'device-777']);

        /** @var HeartbeatService $service */
        $service = app(HeartbeatService::class);

        $result = $service->touch($screen->id, 'device-777', [
            'status' => ScreenStatus::Offline,
            'reported_at' => $reportedAt,
            'last_heartbeat' => $heartbeatAt,
            'current_ad_code' => null,
        ]);

        $this->assertNotNull($result);
        $this->assertSame(ScreenStatus::Offline, $result['screen']->status);
        $this->assertTrue($result['screen']->last_heartbeat?->equalTo($heartbeatAt));
        $this->assertTrue($result['log']->reported_at?->equalTo($reportedAt));
        $this->assertSame(ScreenStatus::Offline->value, $result['log']->status);
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
