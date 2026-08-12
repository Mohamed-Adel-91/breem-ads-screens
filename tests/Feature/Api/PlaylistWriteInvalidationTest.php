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
 * Phase 12 — every write that can change a playlist invalidates it immediately,
 * and only for the screens actually affected.
 *
 * Cache invalidation has exactly two owners, deliberately not three:
 *   - MODEL writes  -> AdObserver / AdScheduleObserver (create, update, delete);
 *   - PIVOT writes  -> the calling controller, because attach/detach/sync fire no
 *     model events, via Ad::flushScreensCache().
 *
 * The cache key belongs to AdSchedulerService::cacheKeyFor(); these tests read it
 * from there rather than composing "playlist:{id}" by hand.
 */
class PlaylistWriteInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['services.screens.playlist_ttl' => 300]);

        $this->now = Carbon::create(2026, 5, 5, 12, 0, 0);
        Carbon::setTestNow($this->now);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeScreen(): Screen
    {
        $place = Place::create([
            'name' => ['en' => 'Invalidation Hall'],
            'address' => ['en' => '3 Flush Street'],
            'type' => PlaceType::Other,
        ]);

        return Screen::create([
            'place_id' => $place->id,
            'code' => 'screen-'.Str::random(8),
            'device_uid' => 'device-'.Str::random(8),
        ]);
    }

    private function makeAd(array $attributes = []): Ad
    {
        return Ad::create(array_merge([
            'title' => ['en' => 'Invalidation Campaign'],
            'file_path' => 'upload/ads/invalidate.mp4',
            'file_type' => 'video',
            'duration_seconds' => 20,
            'status' => AdStatus::Active,
            'created_by' => User::factory()->create()->id,
        ], $attributes));
    }

    private function scheduler(): AdSchedulerService
    {
        return app(AdSchedulerService::class);
    }

    private function warm(Screen ...$screens): void
    {
        foreach ($screens as $screen) {
            $this->scheduler()->forScreen($screen->fresh());

            $this->assertTrue(
                Cache::has(AdSchedulerService::cacheKeyFor($screen)),
                "Failed to warm the playlist cache for screen {$screen->code}."
            );
        }
    }

    private function assertFlushed(Screen $screen, string $because): void
    {
        $this->assertFalse(
            Cache::has(AdSchedulerService::cacheKeyFor($screen)),
            "The playlist cache must be flushed immediately when {$because}."
        );
    }

    private function assertKept(Screen $screen, string $because): void
    {
        $this->assertTrue(
            Cache::has(AdSchedulerService::cacheKeyFor($screen)),
            "The playlist cache must be left alone when {$because}."
        );
    }

    // ------------------------------------------------------------ schedule writes

    public function test_creating_a_schedule_flushes_the_affected_screen(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAd();
        $screen->ads()->attach($ad->id, ['play_order' => 1]);

        $this->warm($screen);

        AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $screen->id,
            'start_time' => $this->now->copy()->subHour(),
            'end_time' => $this->now->copy()->addHour(),
            'is_active' => true,
        ]);

        $this->assertFlushed($screen, 'a schedule is created');
    }

    public function test_updating_a_schedule_flushes_the_affected_screen(): void
    {
        [$screen, , $schedule] = $this->scheduledAdOnScreen();

        $this->warm($screen);
        $schedule->update(['end_time' => $this->now->copy()->addHours(5)]);

        $this->assertFlushed($screen, 'a schedule window is edited');
    }

    public function test_deactivating_a_schedule_flushes_the_affected_screen(): void
    {
        [$screen, , $schedule] = $this->scheduledAdOnScreen();

        $this->warm($screen);
        $schedule->update(['is_active' => false]);

        $this->assertFlushed($screen, 'a schedule is deactivated');
    }

    public function test_activating_a_schedule_flushes_the_affected_screen(): void
    {
        [$screen, , $schedule] = $this->scheduledAdOnScreen(false);

        $this->warm($screen);
        $schedule->update(['is_active' => true]);

        $this->assertFlushed($screen, 'a schedule is activated');
    }

    public function test_deleting_a_schedule_flushes_the_affected_screen(): void
    {
        [$screen, , $schedule] = $this->scheduledAdOnScreen();

        $this->warm($screen);
        $schedule->delete();

        $this->assertFlushed($screen, 'a schedule is deleted');
    }

    /**
     * Moving a schedule between screens changes both playlists, so both must go.
     */
    public function test_moving_a_schedule_flushes_the_old_and_the_new_screen(): void
    {
        [$from, $ad, $schedule] = $this->scheduledAdOnScreen();
        $to = $this->makeScreen();
        $to->ads()->attach($ad->id, ['play_order' => 1]);

        $this->warm($from, $to);
        $schedule->update(['screen_id' => $to->id]);

        $this->assertFlushed($from, 'a schedule leaves a screen');
        $this->assertFlushed($to, 'a schedule arrives on a screen');
    }

    // ----------------------------------------------------------------- ad writes

    public function test_changing_the_ad_media_flushes_every_assigned_screen(): void
    {
        $screens = [$this->makeScreen(), $this->makeScreen()];
        $ad = $this->makeAd();

        foreach ($screens as $index => $screen) {
            $screen->ads()->attach($ad->id, ['play_order' => $index + 1]);
        }

        $this->warm(...$screens);

        // Ad-level content really does affect both screens.
        $ad->update(['file_path' => 'upload/ads/replacement.mp4', 'file_type' => 'video']);

        foreach ($screens as $screen) {
            $this->assertFlushed($screen, 'the ad creative is replaced');
        }
    }

    public function test_changing_the_ad_duration_flushes_the_assigned_screen(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAd();
        $screen->ads()->attach($ad->id, ['play_order' => 1]);

        $this->warm($screen);
        $ad->update(['duration_seconds' => 45]);

        $this->assertFlushed($screen, 'the ad duration changes');
    }

    public function test_changing_the_ad_status_flushes_the_assigned_screen(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAd();
        $screen->ads()->attach($ad->id, ['play_order' => 1]);

        $this->warm($screen);
        $ad->update(['status' => AdStatus::Expired]);

        $this->assertFlushed($screen, 'the ad status changes');
    }

    public function test_changing_the_ad_global_window_flushes_the_assigned_screen(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAd();
        $screen->ads()->attach($ad->id, ['play_order' => 1]);

        $this->warm($screen);
        $ad->update(['end_date' => $this->now->copy()->addDays(3)]);

        $this->assertFlushed($screen, 'the ad global window changes');
    }

    /**
     * PART 16 — the Phase 9 delete defect must stay fixed. AdObserver materialises
     * the screen ids onto the model during `deleting`, because `deleting` and
     * `deleted` run on different observer instances.
     */
    public function test_deleting_an_assigned_ad_still_flushes_immediately(): void
    {
        $assigned = $this->makeScreen();
        $unrelated = $this->makeScreen();
        $ad = $this->makeAd();
        $assigned->ads()->attach($ad->id, ['play_order' => 1]);

        $this->warm($assigned, $unrelated);
        $ad->delete();

        $this->assertFlushed($assigned, 'an assigned ad is deleted');
        $this->assertKept($unrelated, 'an ad it never carried is deleted');
    }

    // --------------------------------------------------------- assignment writes

    public function test_attaching_an_ad_flushes_the_screen(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAd();

        $this->warm($screen);

        $screen->ads()->attach($ad->id, ['play_order' => 1]);
        $ad->flushScreensCache([$screen->id]);

        $this->assertFlushed($screen, 'an ad is attached');
    }

    public function test_detaching_an_ad_flushes_the_screen(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAd();
        $screen->ads()->attach($ad->id, ['play_order' => 1]);

        $this->warm($screen);

        // Read the ids before detaching — the pivot rows are the only record of
        // which screens were affected.
        $affected = $ad->screens()->pluck('screens.id')->all();
        $screen->ads()->detach($ad->id);
        $ad->flushScreensCache($affected);

        $this->assertFlushed($screen, 'an ad is detached');
    }

    public function test_changing_the_play_order_flushes_the_screen(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAd();
        $screen->ads()->attach($ad->id, ['play_order' => 1]);

        $this->warm($screen);

        $screen->ads()->updateExistingPivot($ad->id, ['play_order' => 7]);
        $ad->flushScreensCache([$screen->id]);

        $this->assertFlushed($screen, 'the play order changes');

        $items = $this->scheduler()->forScreen($screen->fresh())['items'];
        $this->assertSame(7, $items[0]['play_order']);
    }

    /**
     * The admin ad form drives assignment through AdController::syncScreens(),
     * which flushes the union of the previous and the new screen sets.
     */
    public function test_syncing_assignments_flushes_both_the_old_and_the_new_screen(): void
    {
        $from = $this->makeScreen();
        $to = $this->makeScreen();
        $ad = $this->makeAd();
        $from->ads()->attach($ad->id, ['play_order' => 1]);

        $this->warm($from, $to);

        $previous = $ad->screens()->pluck('screens.id')->all();
        $ad->screens()->sync([$to->id => ['play_order' => 1]]);
        $ad->flushScreensCache(array_unique(array_merge($previous, [$to->id])));

        $this->assertFlushed($from, 'an ad is moved off a screen');
        $this->assertFlushed($to, 'an ad is moved onto a screen');
    }

    // ------------------------------------------------------------------ isolation

    /**
     * PART 43 — a screen-specific schedule change must not touch another screen's
     * cache, even when the two screens share the ad.
     */
    public function test_a_screen_specific_schedule_change_leaves_the_other_screen_alone(): void
    {
        $screenA = $this->makeScreen();
        $screenB = $this->makeScreen();
        $ad = $this->makeAd();

        $screenA->ads()->attach($ad->id, ['play_order' => 1]);
        $screenB->ads()->attach($ad->id, ['play_order' => 1]);

        $scheduleA = AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $screenA->id,
            'start_time' => $this->now->copy()->subHour(),
            'end_time' => $this->now->copy()->addHour(),
            'is_active' => true,
        ]);

        $this->warm($screenA, $screenB);

        $scheduleA->update(['end_time' => $this->now->copy()->addHours(4)]);

        $this->assertFlushed($screenA, 'its own schedule is edited');
        $this->assertKept($screenB, 'a schedule belonging to another screen is edited');
    }

    /**
     * The complement: a schedule row scoped to screen A never leaks into screen
     * B's eligibility. B has no rows of its own, so it stays always-on.
     */
    public function test_a_schedule_on_one_screen_does_not_gate_the_other_screen(): void
    {
        $screenA = $this->makeScreen();
        $screenB = $this->makeScreen();
        $ad = $this->makeAd();

        $screenA->ads()->attach($ad->id, ['play_order' => 1]);
        $screenB->ads()->attach($ad->id, ['play_order' => 1]);

        // Screen A gets a window that has not opened yet.
        AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $screenA->id,
            'start_time' => $this->now->copy()->addDay(),
            'end_time' => $this->now->copy()->addDays(2),
            'is_active' => true,
        ]);

        $idsOnA = collect($this->scheduler()->forScreen($screenA->fresh())['items'])->pluck('ad_id')->all();
        $idsOnB = collect($this->scheduler()->forScreen($screenB->fresh())['items'])->pluck('ad_id')->all();

        $this->assertNotContains($ad->id, $idsOnA, 'Screen A is gated by its own future window.');
        $this->assertContains($ad->id, $idsOnB, 'Screen B has no rows of its own and stays always-on.');
    }

    /**
     * @return array{0: Screen, 1: Ad, 2: AdSchedule}
     */
    private function scheduledAdOnScreen(bool $isActive = true): array
    {
        $screen = $this->makeScreen();
        $ad = $this->makeAd();
        $screen->ads()->attach($ad->id, ['play_order' => 1]);

        $schedule = AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $screen->id,
            'start_time' => $this->now->copy()->subHour(),
            'end_time' => $this->now->copy()->addHour(),
            'is_active' => $isActive,
        ]);

        return [$screen, $ad, $schedule];
    }
}
