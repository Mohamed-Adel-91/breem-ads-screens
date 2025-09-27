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
use Tests\TestCase;

class PlaylistEtagInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_playlist_etag_changes_after_ad_update(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));

        [$screen, $ad] = $this->createScreenWithAd();

        $initial = $this->getPlaylistEtag($screen);

        $ad->update([
            'title' => ['en' => 'Updated Headline'],
        ]);

        $updated = $this->getPlaylistEtag($screen);

        $this->assertNotSame($initial, $updated, 'Expected playlist ETag to change after updating the ad.');
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
        $response = $this
            ->withHeader('X-Screen-Uid', $screen->device_uid)
            ->getJson(route('api.v1.screens.playlist', ['screen' => $screen->id]));

        $response->assertOk();

        $etag = $response->headers->get('ETag');

        $this->assertNotNull($etag, 'Expected playlist response to include an ETag header.');

        return trim((string) $etag, '"');
    }
}
