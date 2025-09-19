<?php

namespace Tests\Unit\Services\Screen;

use App\Enums\AdStatus;
use App\Enums\PlaceType;
use App\Models\Ad;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use App\Services\Screen\ScreenApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ScreenApiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            Cache::clear();
        } catch (\BadMethodCallException) {
            Cache::flush();
        }

        config([
            'services.screens.playlist_ttl' => 300,
            'app.url' => 'https://example.test',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_playlist_uses_cached_payload_and_etag_handling(): void
    {
        $now = Carbon::create(2024, 1, 1, 9, 30, 0);
        Carbon::setTestNow($now);

        [$screen, $scheduledAd] = $this->createScreenWithAds($now);

        /** @var ScreenApiService $service */
        $service = app(ScreenApiService::class);

        $first = $service->playlist($screen);
        $this->assertInstanceOf(Collection::class, $first['items']);
        $this->assertSame(2, $first['items']->count());
        $this->assertFalse($first['unchanged']);
        $this->assertNotSame('', $first['etag']);

        $etag = $first['etag'];

        Carbon::setTestNow($now->copy()->addMinutes(5));
        $second = $service->playlist($screen, $etag);
        $this->assertTrue($second['unchanged']);
        $this->assertSame($etag, $second['etag']);

        Carbon::setTestNow($now->copy()->addMinutes(10));
        $scheduledAd->update([
            'title' => ['en' => 'Updated Scheduled'],
        ]);

        $third = $service->playlist($screen, $etag);
        $this->assertFalse($third['unchanged']);
        $this->assertNotSame($etag, $third['etag']);
    }

    /**
     * @return array{Screen, Ad}
     */
    private function createScreenWithAds(Carbon $now): array
    {
        $user = User::factory()->create();
        $place = Place::create([
            'name' => ['en' => 'Fixture Place'],
            'address' => ['en' => '456 Avenue'],
            'type' => PlaceType::Mall,
        ]);

        $screen = Screen::create([
            'place_id' => $place->id,
            'code' => 'SCR-200',
        ]);

        $scheduledAd = Ad::create([
            'title' => ['en' => 'Scheduled'],
            'description' => ['en' => 'Scheduled Ad'],
            'file_path' => 'upload/ads/scheduled.mp4',
            'file_type' => 'video',
            'duration_seconds' => 30,
            'status' => AdStatus::Active,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'start_date' => $now->copy()->subDay(),
            'end_date' => $now->copy()->addDay(),
        ]);

        $fallbackAd = Ad::create([
            'title' => ['en' => 'Fallback'],
            'description' => ['en' => 'Fallback Ad'],
            'file_path' => 'upload/ads/fallback.png',
            'file_type' => 'image',
            'duration_seconds' => 20,
            'status' => AdStatus::Active,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'start_date' => $now->copy()->subHours(2),
            'end_date' => $now->copy()->addHours(5),
        ]);

        $screen->ads()->attach($scheduledAd->id, ['play_order' => 1]);
        $screen->ads()->attach($fallbackAd->id, ['play_order' => 2]);

        $scheduledAd->schedules()->create([
            'screen_id' => $screen->id,
            'start_time' => $now->copy()->subMinutes(30),
            'end_time' => $now->copy()->addHour(),
            'is_active' => true,
        ]);

        return [$screen, $scheduledAd];
    }
}
