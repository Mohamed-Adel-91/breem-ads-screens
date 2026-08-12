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
 * Phase 12 — a heartbeat must not invalidate the playlist ETag.
 *
 * Phase 11 documented the defect: the ETag was
 * `sha1(screen.id | screen.updated_at | json(items))`, and every heartbeat writes
 * `screens.status` / `screens.last_heartbeat`, bumping `updated_at`. A device on
 * its normal cadence therefore never got a 304 and re-downloaded an identical
 * manifest on every poll.
 *
 * Both halves of the honest fix are now in place, and the fix is a real one — not
 * an ignored mismatch:
 *
 *   - PlaylistResource embeds PlaylistScreenResource (id + code), so the response
 *     bytes no longer carry heartbeat-driven telemetry;
 *   - AdSchedulerService hashes only that stable screen identity plus the items,
 *     so the ETag describes exactly the bytes the device receives.
 *
 * The tests below pin both: identical bytes AND an identical ETag across a
 * heartbeat, which together make the 304 legitimate.
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

    public function test_a_heartbeat_does_not_change_the_playlist_etag(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 7, 1, 12, 0, 0));

        $screen = $this->makeScreenWithAd();
        $creds = $this->pairScreen($screen);

        $before = $this->playlistEtag($screen, $creds);

        // A perfectly ordinary heartbeat, one interval later.
        Carbon::setTestNow($now->copy()->addSeconds(60));
        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds)->assertOk();

        $after = $this->playlistEtag($screen->fresh(), $creds);

        $this->assertSame(
            $before,
            $after,
            'A heartbeat is operational state, not playlist content: the ETag must hold.'
        );
    }

    /**
     * The ETag is only honest if the bytes really are identical — a stable hash
     * over a changing body would just be an ignored mismatch.
     */
    public function test_the_playlist_screen_block_carries_no_heartbeat_telemetry(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 7, 1, 12, 0, 0));

        $screen = $this->makeScreenWithAd();
        $creds = $this->pairScreen($screen);
        $url = route('api.v1.screens.playlist', ['screen' => $screen->id]);

        $before = $this->deviceGet($url, $creds)->json('data');

        $this->assertSame(['id', 'code'], array_keys($before['screen']));

        Carbon::setTestNow($now->copy()->addSeconds(60));
        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds)->assertOk();

        $after = $this->deviceGet($url, $creds)->json('data');

        $this->assertSame($before['screen'], $after['screen']);
        $this->assertSame($before['items'], $after['items']);
    }

    /**
     * The end-to-end payoff: the device's conditional request is answered with a
     * 304 instead of a full manifest.
     */
    public function test_a_conditional_request_gets_a_304_across_a_heartbeat(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 7, 1, 12, 0, 0));

        $screen = $this->makeScreenWithAd();
        $creds = $this->pairScreen($screen);
        $url = route('api.v1.screens.playlist', ['screen' => $screen->id]);

        $etag = $this->playlistEtag($screen, $creds);

        Carbon::setTestNow($now->copy()->addSeconds(60));
        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds)->assertOk();

        $this->deviceGet($url, $creds, [], ['If-None-Match' => '"'.$etag.'"'])
            ->assertStatus(304);
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
