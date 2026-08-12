<?php

namespace Tests\Feature\Api;

use App\Enums\AdStatus;
use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Ad;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Concerns\SignsScreenRequests;
use Tests\TestCase;

/**
 * DOCUMENTED DEFECT — the playlist ETag is invalidated by every heartbeat.
 *
 * The ETag is `sha1(screen.id | screen.updated_at | json(items))`, and every
 * heartbeat writes `screens.status` / `screens.last_heartbeat`, which bumps
 * `updated_at`. A device that heartbeats on its normal cadence therefore never
 * gets a 304 for its playlist: it re-downloads the whole manifest on every poll
 * even when nothing about its content changed.
 *
 * This is a bandwidth defect, not a correctness one — the playlist served is
 * always right. It is NOT fixed here because the honest fixes both reach outside
 * Phase 11's scope:
 *
 *   - drop `updated_at` from the ETag: wrong on its own, because
 *     PlaylistResource embeds ScreenResource, which carries `status` and
 *     `last_heartbeat_at`. Those really do change on every heartbeat, so the
 *     response bytes genuinely differ and a stable ETag would be a lie.
 *   - remove the live fields from the playlist's screen block: a Device API
 *     contract change, and Phase 11 is explicitly forbidden from redesigning the
 *     playlist.
 *
 * Recommended for Phase 12, which owns playlist and cache-boundary correctness.
 * This test pins the current behaviour so the eventual fix is deliberate.
 */
class PlaylistEtagHeartbeatInteractionTest extends TestCase
{
    use RefreshDatabase;
    use SignsScreenRequests;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeScreenWithAd(): Screen
    {
        $place = Place::create([
            'name' => ['en' => 'ETag Hall'],
            'address' => ['en' => '6 Cache Lane'],
            'type' => PlaceType::Other,
        ]);

        $screen = Screen::create([
            'place_id' => $place->id,
            'code' => 'screen-'.Str::random(8),
            'device_uid' => 'device-'.Str::random(8),
            'status' => ScreenStatus::Online,
            'last_heartbeat' => now(),
        ]);

        $ad = Ad::create([
            'title' => ['en' => 'ETag Ad'],
            'file_path' => 'upload/ads/etag.mp4',
            'file_type' => 'video',
            'duration_seconds' => 20,
            'status' => AdStatus::Active,
            'created_by' => User::factory()->create()->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $screen->ads()->attach($ad->id, ['play_order' => 1]);

        return $screen;
    }

    private function playlistEtag(Screen $screen, array $creds): string
    {
        $response = $this->deviceGet(
            route('api.v1.screens.playlist', ['screen' => $screen->id]),
            $creds
        );

        $response->assertOk();

        return trim((string) $response->headers->get('ETag'), '"');
    }

    public function test_the_playlist_etag_is_stable_when_nothing_happens(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 1, 12, 0, 0));

        $screen = $this->makeScreenWithAd();
        $creds = $this->pairScreen($screen);

        $this->assertSame(
            $this->playlistEtag($screen, $creds),
            $this->playlistEtag($screen->fresh(), $creds)
        );
    }

    public function test_a_heartbeat_invalidates_the_playlist_etag(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 7, 1, 12, 0, 0));

        $screen = $this->makeScreenWithAd();
        $creds = $this->pairScreen($screen);

        $before = $this->playlistEtag($screen, $creds);

        // A perfectly ordinary heartbeat, one interval later.
        Carbon::setTestNow($now->copy()->addSeconds(60));
        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds)->assertOk();

        $after = $this->playlistEtag($screen->fresh(), $creds);

        $this->assertNotSame(
            $before,
            $after,
            'Documented defect: a heartbeat bumps screens.updated_at, which is part of the playlist ETag.'
        );
    }

    public function test_the_playlist_content_itself_is_unchanged_by_a_heartbeat(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 7, 1, 12, 0, 0));

        $screen = $this->makeScreenWithAd();
        $creds = $this->pairScreen($screen);
        $url = route('api.v1.screens.playlist', ['screen' => $screen->id]);

        $before = $this->deviceGet($url, $creds)->json('data.items');

        Carbon::setTestNow($now->copy()->addSeconds(60));
        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds)->assertOk();

        $after = $this->deviceGet($url, $creds)->json('data.items');

        // The device re-downloads an identical item list. That is the waste.
        $this->assertSame($before, $after);
    }
}
