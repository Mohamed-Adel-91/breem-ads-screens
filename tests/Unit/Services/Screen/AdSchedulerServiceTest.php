<?php

namespace Tests\Unit\Services\Screen;

use App\Enums\AdStatus;
use App\Enums\PlaceType;
use App\Models\Ad;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use App\Services\Screen\AdSchedulerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdSchedulerServiceTest extends TestCase
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
        config(['services.screens.playlist_ttl' => 300]);
        config(['app.url' => 'https://example.test']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * The payload shape, plus the three eligibility outcomes side by side.
     *
     * The `$futureAd` case is the Phase 12 correction: it used to be emitted as an
     * "unscheduled" item because an ad whose schedules all lay outside now fell
     * through to always-on playback. An ad with schedule rows may only play inside
     * one of them.
     */
    public function test_for_screen_returns_only_currently_eligible_items(): void
    {
        $now = Carbon::create(2024, 1, 1, 12, 0, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $place = Place::create([
            'name' => ['en' => 'Test Place'],
            'address' => ['en' => '123 Street'],
            'type' => PlaceType::Cafe,
        ]);
        $screen = Screen::create([
            'place_id' => $place->id,
            'code' => 'SCR-001',
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
        // No schedule rows at all: assignment-only, always-on content.
        $alwaysOnAd = Ad::create([
            'title' => ['en' => 'Always On'],
            'description' => ['en' => 'Unscheduled Ad'],
            'file_path' => 'upload/ads/always-on.png',
            'file_type' => 'image',
            'duration_seconds' => 15,
            'status' => AdStatus::Active,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'start_date' => $now->copy()->subHours(2),
            'end_date' => $now->copy()->addHours(6),
        ]);
        // Scheduled, but the window has not opened yet.
        $futureAd = Ad::create([
            'title' => ['en' => 'Future'],
            'description' => ['en' => 'Future Ad'],
            'file_path' => 'upload/ads/future.png',
            'file_type' => 'image',
            'duration_seconds' => 12,
            'status' => AdStatus::Active,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'start_date' => $now->copy()->subHours(2),
            'end_date' => $now->copy()->addHours(6),
        ]);
        $inactiveAd = Ad::create([
            'title' => ['en' => 'Inactive'],
            'description' => ['en' => 'Inactive Ad'],
            'file_path' => 'upload/ads/inactive.png',
            'file_type' => 'image',
            'duration_seconds' => 10,
            'status' => AdStatus::Pending,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'start_date' => $now->copy()->subHours(2),
            'end_date' => $now->copy()->addHours(2),
        ]);

        $screen->ads()->attach($scheduledAd->id, ['play_order' => 1]);
        $screen->ads()->attach($alwaysOnAd->id, ['play_order' => 2]);
        $screen->ads()->attach($futureAd->id, ['play_order' => 3]);
        $screen->ads()->attach($inactiveAd->id, ['play_order' => 4]);

        $scheduledAd->schedules()->create([
            'screen_id' => $screen->id,
            'start_time' => $now->copy()->subHour(),
            'end_time' => $now->copy()->addHour(),
            'is_active' => true,
        ]);
        $futureAd->schedules()->create([
            'screen_id' => $screen->id,
            'start_time' => $now->copy()->addHour(),
            'end_time' => $now->copy()->addHours(2),
            'is_active' => true,
        ]);

        /** @var AdSchedulerService $scheduler */
        $scheduler = app(AdSchedulerService::class);

        $payload = $scheduler->forScreen($screen);

        $this->assertInstanceOf(Screen::class, $payload['screen']);
        $this->assertInstanceOf(Carbon::class, $payload['generated_at']);
        $this->assertInstanceOf(Carbon::class, $payload['expires_at']);
        $this->assertNotSame('', $payload['etag']);

        $items = Collection::make($payload['items']);

        // Pending status and an unopened window are both excluded; ordering is by
        // pivot play_order.
        $this->assertSame(
            [$scheduledAd->id, $alwaysOnAd->id],
            $items->pluck('ad_id')->all()
        );
        $this->assertSame([1, 2], $items->pluck('play_order')->all());

        $scheduledItem = $items->firstWhere('ad_id', $scheduledAd->id);
        $this->assertNotNull($scheduledItem['schedule']);
        $this->assertSame($scheduledAd->file_url, $scheduledItem['file_url']);
        $this->assertSame($scheduledAd->schedules()->first()->start_time->toAtomString(), $scheduledItem['valid_from']);
        $this->assertSame($scheduledAd->start_date->toAtomString(), $scheduledItem['ad_valid_from']);

        // With no schedule rows the ad's own window is the effective window.
        $alwaysOnItem = $items->firstWhere('ad_id', $alwaysOnAd->id);
        $this->assertNull($alwaysOnItem['schedule']);
        $this->assertSame($alwaysOnAd->start_date->toAtomString(), $alwaysOnItem['valid_from']);
        $this->assertSame($alwaysOnAd->end_date->toAtomString(), $alwaysOnItem['valid_until']);

        $this->assertTrue($payload['generated_at']->eq($now));
        $this->assertTrue($payload['expires_at']->eq($now->copy()->addSeconds(config('services.screens.playlist_ttl'))));

        // The line above asserts the entry expires at now + playlist_ttl (300s),
        // so the cache-hit assertion must stay inside that window.
        Carbon::setTestNow($now->copy()->addMinutes(4));
        $cached = $scheduler->forScreen($screen);
        $this->assertEquals($payload['generated_at'], $cached['generated_at']);
        $this->assertEquals($payload['etag'], $cached['etag']);

        $scheduler->forget($screen);
        Carbon::setTestNow($now->copy()->addMinutes(20));
        $reloaded = $scheduler->forScreen($screen);
        $this->assertTrue($reloaded['generated_at']->gt($cached['generated_at']));
    }

    public function test_configured_fallback_is_used_when_playlist_empty(): void
    {
        $now = Carbon::create(2024, 5, 15, 10, 0, 0);
        Carbon::setTestNow($now);

        config(['ads.fallback' => [
            'type' => 'image',
            'image' => 'https://cdn.example.test/fallback.png',
            'duration' => 10,
        ]]);

        $place = Place::create([
            'name' => ['en' => 'Fallback Place'],
            'address' => ['en' => '789 Road'],
            'type' => PlaceType::Cafe,
        ]);

        $screen = Screen::create([
            'place_id' => $place->id,
            'code' => 'SCR-EMPTY',
        ]);

        /** @var AdSchedulerService $scheduler */
        $scheduler = app(AdSchedulerService::class);

        $payload = $scheduler->forScreen($screen);

        $this->assertNotSame('', $payload['etag']);

        $items = Collection::make($payload['items']);
        $this->assertCount(1, $items);

        $fallbackItem = $items->first();
        $this->assertNull($fallbackItem['ad_id']);
        $this->assertSame('image', $fallbackItem['file_type']);
        $this->assertSame('https://cdn.example.test/fallback.png', $fallbackItem['file_url']);
        $this->assertSame(10, $fallbackItem['duration_seconds']);
        $this->assertSame(0, $fallbackItem['play_order']);
    }
}
