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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\Concerns\SignsScreenRequests;
use Tests\TestCase;

class PlaylistEtagInvalidationTest extends TestCase
{
    use RefreshDatabase;
    use SignsScreenRequests;

    /** @var array{credential: \App\Models\ScreenDeviceCredential, token: string, secret: string} */
    private array $deviceCredentials;

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * The playlist ETag validates the bytes the device receives, not the ad
     * record's version. The device payload carries media, timing and ordering
     * only — never the ad title or description — so a title-only edit correctly
     * leaves the ETag alone and the device keeps its cached copy.
     *
     * The original expectation here (title change => new ETag) was written
     * before routes/api.php was reachable and never ran. It is stale. The title
     * was deliberately NOT added to the device payload to satisfy it; see
     * docs/ai/digital-signage.md.
     */
    public function test_playlist_etag_is_unchanged_by_an_edit_the_device_never_sees(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));

        [$screen, $ad] = $this->createScreenWithAd();

        $initial = $this->getPlaylistEtag($screen);

        $ad->update([
            'title' => ['en' => 'Updated Headline'],
            'description' => ['en' => 'Rewritten copy'],
        ]);

        $this->assertSame(
            $initial,
            $this->getPlaylistEtag($screen),
            'Title and description are not part of the device playlist, so the ETag must hold.'
        );
    }

    public function test_playlist_etag_changes_when_a_field_the_device_receives_changes(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));

        [$screen, $ad] = $this->createScreenWithAd();

        $initial = $this->getPlaylistEtag($screen);

        $ad->update(['duration_seconds' => 90]);

        $this->assertNotSame(
            $initial,
            $this->getPlaylistEtag($screen),
            'duration_seconds IS sent to the device, so the ETag must change.'
        );
    }

    public function test_playlist_etag_changes_when_the_creative_is_replaced(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));

        [$screen, $ad] = $this->createScreenWithAd();

        $initial = $this->getPlaylistEtag($screen);

        $ad->update(['file_path' => 'upload/ads/replacement.mp4']);

        $this->assertNotSame(
            $initial,
            $this->getPlaylistEtag($screen),
            'file_path and file_url are sent to the device, so the ETag must change.'
        );
    }

    public function test_playlist_etag_changes_when_the_media_type_changes(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));

        [$screen, $ad] = $this->createScreenWithAd();

        $initial = $this->getPlaylistEtag($screen);

        $ad->update(['file_type' => 'image']);

        $this->assertNotSame($initial, $this->getPlaylistEtag($screen));
    }

    public function test_playlist_etag_changes_when_the_play_order_changes(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));

        [$screen, $ad] = $this->createScreenWithAd();

        $initial = $this->getPlaylistEtag($screen);

        // Pivot writes fire no model event, so the caller flushes.
        $screen->ads()->updateExistingPivot($ad->id, ['play_order' => 9]);
        $ad->flushScreensCache([$screen->id]);

        $this->assertNotSame(
            $initial,
            $this->getPlaylistEtag($screen),
            'play_order is part of the manifest the device plays.'
        );
    }

    /**
     * An unrelated screen's manifest is untouched, so its validator must hold —
     * the ETag is per-screen, not global.
     */
    public function test_an_unrelated_screen_keeps_its_playlist_etag(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));

        [$screen, $ad] = $this->createScreenWithAd();

        $unrelated = Screen::create([
            'place_id' => $screen->place_id,
            'code' => 'screen-'.Str::random(8),
            'device_uid' => 'device-'.Str::random(8),
            'status' => ScreenStatus::Online,
            'last_heartbeat' => now(),
        ]);

        // Each screen signs with its own credential; keep both and swap.
        $unrelatedCredentials = $this->pairScreen($unrelated);

        $this->deviceCredentials = $unrelatedCredentials;
        $before = $this->getPlaylistEtag($unrelated);

        // The change touches only the other screen's manifest.
        $ad->update(['duration_seconds' => 120]);

        $this->assertSame($before, $this->getPlaylistEtag($unrelated));
    }

    public function test_playlist_etag_changes_after_schedule_update(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));

        [$screen, $ad, $schedule] = $this->createScreenWithAd();

        $initial = $this->getPlaylistEtag($screen);

        $schedule->update([
            'end_time' => now()->addHours(2),
        ]);

        $updated = $this->getPlaylistEtag($screen);

        $this->assertNotSame($initial, $updated, 'Expected playlist ETag to change after modifying the schedule.');
    }

    public function test_playlist_etag_changes_after_ad_deletion(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));

        [$screen, $ad] = $this->createScreenWithAd();

        $initial = $this->getPlaylistEtag($screen);

        $ad->delete();

        $updated = $this->getPlaylistEtag($screen);

        $this->assertNotSame($initial, $updated, 'Expected playlist ETag to change after deleting the ad.');
    }

    /**
     * @return array{0: \App\Models\Screen, 1: \App\Models\Ad, 2?: \App\Models\AdSchedule}
     */
    private function createScreenWithAd(): array
    {
        Cache::flush();

        $place = Place::create([
            'name' => ['en' => 'Main Hall'],
            'address' => ['en' => '123 Example Street'],
            'type' => PlaceType::Other,
        ]);

        $screen = Screen::create([
            'place_id' => $place->id,
            'code' => 'screen-'.Str::random(8),
            'device_uid' => 'device-'.Str::random(8),
            'status' => ScreenStatus::Online,
            'last_heartbeat' => now(),
        ]);

        $user = User::factory()->create();

        $ad = Ad::create([
            'title' => ['en' => 'Launch Campaign'],
            'description' => ['en' => 'Initial description'],
            'file_path' => 'upload/ads/example.mp4',
            'file_type' => 'video',
            'duration_seconds' => 30,
            'status' => AdStatus::Active,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $screen->ads()->attach($ad->id, ['play_order' => 1]);

        $this->deviceCredentials = $this->pairScreen($screen);

        $schedule = AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $screen->id,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'is_active' => true,
        ]);

        return [$screen->fresh(), $ad->fresh(), $schedule->fresh()];
    }

    private function getPlaylistEtag(Screen $screen): string
    {
        $url = route('api.v1.screens.playlist', ['screen' => $screen->id]);

        // Time is frozen for these tests, so each call needs a fresh nonce —
        // deviceGet() generates one per request.
        $response = $this->deviceGet($url, $this->deviceCredentials);

        $response->assertOk();

        $etag = $response->headers->get('ETag');

        $this->assertNotNull($etag, 'Expected playlist response to include an ETag header.');

        return trim((string) $etag, '"');
    }
}
