<?php

namespace Tests\Feature\Api;

use App\Enums\AdStatus;
use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use App\Services\Screen\AdSchedulerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 10 — playlist cache invalidation on advertisement changes.
 *
 * AdObserver used to stash the affected screen ids on itself during `deleting`
 * and read them back in `deleted`. Observers are resolved per event, so the two
 * callbacks ran on different instances and the flush silently never happened: a
 * deleted advertisement kept playing on devices until the 300s TTL expired.
 */
class PlaylistCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeScreen(): Screen
    {
        $place = Place::create([
            'name' => ['en' => 'Cache Hall'],
            'address' => ['en' => '1 Cache Way'],
            'type' => PlaceType::Other,
        ]);

        return Screen::create([
            'place_id' => $place->id,
            'code' => 'screen-'.Str::random(8),
            'device_uid' => 'device-'.Str::random(8),
            'status' => ScreenStatus::Online,
            'last_heartbeat' => now(),
        ]);
    }

    /**
     * @param  array<int, Screen>  $screens
     */
    private function makeAdOn(array $screens): Ad
    {
        $ad = Ad::create([
            'title' => ['en' => 'Cached Campaign'],
            'file_path' => 'upload/ads/cached.mp4',
            'file_type' => 'video',
            'duration_seconds' => 20,
            'status' => AdStatus::Active,
            'created_by' => User::factory()->create()->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        foreach ($screens as $index => $screen) {
            $ad->screens()->attach($screen->id, ['play_order' => $index + 1]);

            AdSchedule::create([
                'ad_id' => $ad->id,
                'screen_id' => $screen->id,
                'start_time' => now()->subHour(),
                'end_time' => now()->addHour(),
                'is_active' => true,
            ]);
        }

        return $ad->fresh();
    }

    private function scheduler(): AdSchedulerService
    {
        return app(AdSchedulerService::class);
    }

    /**
     * @return array{items: int, etag: string}
     */
    private function playlist(Screen $screen): array
    {
        $payload = $this->scheduler()->forScreen($screen->fresh());

        return ['items' => count($payload['items']), 'etag' => (string) $payload['etag']];
    }

    private function fallbackUrl(Screen $screen): ?string
    {
        $items = $this->scheduler()->forScreen($screen->fresh())['items'];

        return $items[0]['file_url'] ?? null;
    }

    public function test_deleting_an_assigned_ad_changes_the_playlist_immediately(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAdOn([$screen]);

        $before = $this->playlist($screen);
        $this->assertSame(1, $before['items']);

        $ad->delete();

        // No TTL wait, no manual Cache::forget.
        $this->assertFalse(
            Cache::has('playlist:'.$screen->id),
            'Deleting an ad must invalidate the cached playlist straight away.'
        );
    }

    public function test_the_deleted_ad_is_absent_from_the_rebuilt_playlist(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAdOn([$screen]);

        $this->playlist($screen);
        $ad->delete();

        $items = $this->scheduler()->forScreen($screen->fresh())['items'];

        $this->assertSame(
            [],
            collect($items)->pluck('ad_id')->filter()->all(),
            'The deleted ad must not reappear; only the configured fallback may remain.'
        );

        $this->assertStringContainsString(
            (string) config('ads.fallback.image'),
            (string) $this->fallbackUrl($screen)
        );
    }

    public function test_the_playlist_etag_changes_after_deletion(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAdOn([$screen]);

        $before = $this->playlist($screen);
        $ad->delete();
        $after = $this->playlist($screen);

        $this->assertNotSame($before['etag'], $after['etag']);
    }

    public function test_every_assigned_screen_is_invalidated(): void
    {
        $screens = [$this->makeScreen(), $this->makeScreen(), $this->makeScreen()];
        $ad = $this->makeAdOn($screens);

        foreach ($screens as $screen) {
            $this->playlist($screen);
            $this->assertTrue(Cache::has('playlist:'.$screen->id));
        }

        $ad->delete();

        foreach ($screens as $screen) {
            $this->assertFalse(
                Cache::has('playlist:'.$screen->id),
                "Screen {$screen->code} kept a stale playlist after the ad was deleted."
            );
        }
    }

    public function test_an_unrelated_screen_cache_is_left_alone(): void
    {
        $assigned = $this->makeScreen();
        $unrelated = $this->makeScreen();

        $ad = $this->makeAdOn([$assigned]);

        $this->playlist($assigned);
        $this->playlist($unrelated);

        $this->assertTrue(Cache::has('playlist:'.$unrelated->id));

        $ad->delete();

        $this->assertFalse(Cache::has('playlist:'.$assigned->id));
        $this->assertTrue(
            Cache::has('playlist:'.$unrelated->id),
            'Deleting an ad must not flush screens it was never assigned to.'
        );
    }

    public function test_saving_an_ad_still_invalidates_its_screens(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAdOn([$screen]);

        $this->playlist($screen);
        $this->assertTrue(Cache::has('playlist:'.$screen->id));

        $ad->update(['duration_seconds' => 45]);

        $this->assertFalse(Cache::has('playlist:'.$screen->id));
        $this->assertSame(45, $this->scheduler()->forScreen($screen->fresh())['items'][0]['duration_seconds']);
    }

    public function test_schedule_changes_still_invalidate_the_playlist(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAdOn([$screen]);
        $schedule = AdSchedule::where('ad_id', $ad->id)->firstOrFail();

        $this->playlist($screen);
        $schedule->update(['end_time' => now()->addHours(4)]);
        $this->assertFalse(Cache::has('playlist:'.$screen->id));

        $this->playlist($screen);
        $schedule->delete();
        $this->assertFalse(Cache::has('playlist:'.$screen->id));
    }
}
