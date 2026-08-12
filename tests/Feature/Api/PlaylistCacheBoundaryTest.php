<?php

namespace Tests\Feature\Api;

use App\Enums\AdStatus;
use App\Enums\PlaceType;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use App\Services\Screen\AdSchedulerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 12 — the playlist cache may never outlive a scheduling boundary.
 *
 * With a flat 300s TTL a playlist computed at 09:59:50 for an ad starting at
 * 10:00:00 stayed authoritative until 10:04:50: five minutes of a device showing
 * content that had stopped being correct. The effective lifetime is now the
 * configured TTL cut short by the next boundary among the assigned ads' global
 * dates and active schedule windows.
 *
 * Clock control only — no sleeps.
 */
class PlaylistCacheBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $now;
    private Screen $screen;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['ads.fallback' => null]);
        config(['services.screens.playlist_ttl' => 300]);

        $this->now = Carbon::create(2026, 4, 20, 9, 55, 0);
        Carbon::setTestNow($this->now);

        $place = Place::create([
            'name' => ['en' => 'Boundary Hall'],
            'address' => ['en' => '2 Clock Road'],
            'type' => PlaceType::Other,
        ]);

        $this->screen = Screen::create([
            'place_id' => $place->id,
            'code' => 'screen-'.Str::random(8),
            'device_uid' => 'device-'.Str::random(8),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function scheduler(): AdSchedulerService
    {
        return app(AdSchedulerService::class);
    }

    private function makeAd(array $attributes = []): Ad
    {
        return Ad::create(array_merge([
            'title' => ['en' => 'Boundary Campaign'],
            'file_path' => 'upload/ads/boundary.mp4',
            'file_type' => 'video',
            'duration_seconds' => 20,
            'status' => AdStatus::Active,
            'created_by' => User::factory()->create()->id,
        ], $attributes));
    }

    /**
     * @return array<int, int|null>
     */
    private function playlistAdIds(): array
    {
        return collect($this->scheduler()->forScreen($this->screen->fresh())['items'])
            ->pluck('ad_id')
            ->all();
    }

    private function expiresAt(): Carbon
    {
        return $this->scheduler()->forScreen($this->screen->fresh())['expires_at'];
    }

    // ----------------------------------------------------------------- future start

    public function test_the_ttl_is_cut_short_by_an_upcoming_schedule_start(): void
    {
        $ad = $this->makeAd();
        $this->screen->ads()->attach($ad->id, ['play_order' => 1]);

        $start = $this->now->copy()->addSeconds(10);
        AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $this->screen->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'is_active' => true,
        ]);

        $this->assertTrue(
            $this->expiresAt()->eq($start),
            'The entry must expire exactly at the boundary, not 300s later.'
        );
    }

    public function test_a_cached_empty_playlist_cannot_survive_the_ads_activation(): void
    {
        $ad = $this->makeAd();
        $this->screen->ads()->attach($ad->id, ['play_order' => 1]);

        $start = $this->now->copy()->addSeconds(10);
        AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $this->screen->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'is_active' => true,
        ]);

        // Warm the cache while the ad is still not eligible.
        $this->assertSame([], $this->playlistAdIds());

        Carbon::setTestNow($start->copy()->subSecond());
        $this->assertSame([], $this->playlistAdIds(), 'One second early it must still be absent.');

        Carbon::setTestNow($start);
        $this->assertSame(
            [$ad->id],
            $this->playlistAdIds(),
            'At the boundary the cached empty playlist must be recomputed.'
        );
    }

    public function test_an_upcoming_global_start_date_also_cuts_the_ttl(): void
    {
        $start = $this->now->copy()->addSeconds(30);
        $ad = $this->makeAd(['start_date' => $start]);
        $this->screen->ads()->attach($ad->id, ['play_order' => 1]);

        $this->assertSame([], $this->playlistAdIds());
        $this->assertTrue($this->expiresAt()->eq($start));

        Carbon::setTestNow($start);
        $this->assertSame([$ad->id], $this->playlistAdIds());
    }

    // ------------------------------------------------------------------ future end

    public function test_the_ttl_is_cut_short_by_an_imminent_schedule_end(): void
    {
        $ad = $this->makeAd();
        $this->screen->ads()->attach($ad->id, ['play_order' => 1]);

        $end = $this->now->copy()->addSeconds(20);
        AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $this->screen->id,
            'start_time' => $this->now->copy()->subHour(),
            'end_time' => $end,
            'is_active' => true,
        ]);

        $this->assertSame([$ad->id], $this->playlistAdIds());
        $this->assertTrue($this->expiresAt()->eq($end));

        Carbon::setTestNow($end->copy()->subSecond());
        $this->assertSame([$ad->id], $this->playlistAdIds());

        Carbon::setTestNow($end);
        $this->assertSame(
            [],
            $this->playlistAdIds(),
            'An expiring ad must not stay cached for the rest of the TTL.'
        );
    }

    public function test_an_imminent_global_end_date_also_cuts_the_ttl(): void
    {
        $end = $this->now->copy()->addSeconds(20);
        $ad = $this->makeAd(['end_date' => $end]);
        $this->screen->ads()->attach($ad->id, ['play_order' => 1]);

        $this->assertSame([$ad->id], $this->playlistAdIds());
        $this->assertTrue($this->expiresAt()->eq($end));

        Carbon::setTestNow($end);
        $this->assertSame([], $this->playlistAdIds());
    }

    // -------------------------------------------------------------------- no cut

    public function test_a_distant_boundary_leaves_the_configured_ttl_intact(): void
    {
        $ad = $this->makeAd();
        $this->screen->ads()->attach($ad->id, ['play_order' => 1]);

        AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $this->screen->id,
            'start_time' => $this->now->copy()->subHour(),
            'end_time' => $this->now->copy()->addHours(6),
            'is_active' => true,
        ]);

        $this->assertTrue($this->expiresAt()->eq($this->now->copy()->addSeconds(300)));
    }

    public function test_an_inactive_schedule_boundary_does_not_shorten_the_ttl(): void
    {
        $ad = $this->makeAd();
        $this->screen->ads()->attach($ad->id, ['play_order' => 1]);

        // Inactive rows contribute nothing at any instant, so their edges are not
        // boundaries.
        AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $this->screen->id,
            'start_time' => $this->now->copy()->addSeconds(5),
            'end_time' => $this->now->copy()->addSeconds(10),
            'is_active' => false,
        ]);

        $this->assertTrue($this->expiresAt()->eq($this->now->copy()->addSeconds(300)));
    }

    // ------------------------------------------------------------------- fallback

    /**
     * PART 26 — a cached fallback must expire at the boundary too, otherwise the
     * cache hides the very transition it exists to serve.
     */
    public function test_a_cached_fallback_expires_when_real_content_becomes_eligible(): void
    {
        config(['ads.fallback' => [
            'type' => 'image',
            'image' => 'https://cdn.example.test/fallback.png',
            'duration' => 10,
        ]]);

        $ad = $this->makeAd();
        $this->screen->ads()->attach($ad->id, ['play_order' => 1]);

        $start = $this->now->copy()->addSeconds(30);
        AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $this->screen->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'is_active' => true,
        ]);

        $this->assertSame([null], $this->playlistAdIds(), 'Fallback stands in for now.');
        $this->assertTrue($this->expiresAt()->eq($start));

        Carbon::setTestNow($start);
        $this->assertSame(
            [$ad->id],
            $this->playlistAdIds(),
            'The fallback must disappear exactly when valid content becomes eligible.'
        );
    }

    // ---------------------------------------------------------------- clock reuse

    public function test_a_warm_cache_is_still_reused_inside_its_lifetime(): void
    {
        $ad = $this->makeAd();
        $this->screen->ads()->attach($ad->id, ['play_order' => 1]);

        $first = $this->scheduler()->forScreen($this->screen->fresh());

        Carbon::setTestNow($this->now->copy()->addSeconds(120));
        $second = $this->scheduler()->forScreen($this->screen->fresh());

        // Same entry: generated_at is not refreshed on a hit.
        $this->assertTrue($second['generated_at']->eq($first['generated_at']));
        $this->assertSame($first['etag'], $second['etag']);
    }

    public function test_the_entry_is_recomputed_once_the_ttl_has_elapsed(): void
    {
        $ad = $this->makeAd();
        $this->screen->ads()->attach($ad->id, ['play_order' => 1]);

        $first = $this->scheduler()->forScreen($this->screen->fresh());

        Carbon::setTestNow($this->now->copy()->addSeconds(301));
        $second = $this->scheduler()->forScreen($this->screen->fresh());

        $this->assertTrue($second['generated_at']->gt($first['generated_at']));

        // The manifest did not change, so its ETag must not either.
        $this->assertSame($first['etag'], $second['etag']);
    }
}
