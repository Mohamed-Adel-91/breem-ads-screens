<?php

namespace Tests\Unit\Services\Screen;

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
 * Phase 12 — the eligibility contract, exhaustively.
 *
 * AdSchedulerService is the single authority: every case below goes through the
 * playlist it produces, never through a re-derived rule.
 */
class PlaylistEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $now;
    private Screen $screen;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // No configured fallback, so an empty playlist really is empty and the
        // eligibility assertions cannot be confused with fallback content.
        config(['ads.fallback' => null]);
        config(['services.screens.playlist_ttl' => 300]);

        $this->now = Carbon::create(2026, 3, 10, 12, 0, 0);
        Carbon::setTestNow($this->now);

        $this->screen = $this->makeScreen();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeScreen(): Screen
    {
        $place = Place::create([
            'name' => ['en' => 'Eligibility Hall'],
            'address' => ['en' => '1 Rule Street'],
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
            'title' => ['en' => 'Campaign'],
            'file_path' => 'upload/ads/creative.mp4',
            'file_type' => 'video',
            'duration_seconds' => 20,
            'status' => AdStatus::Active,
            'created_by' => User::factory()->create()->id,
            'start_date' => null,
            'end_date' => null,
        ], $attributes));
    }

    private function assign(Ad $ad, int $playOrder = 1, ?Screen $screen = null): Ad
    {
        ($screen ?? $this->screen)->ads()->attach($ad->id, ['play_order' => $playOrder]);

        return $ad;
    }

    private function schedule(Ad $ad, Carbon $start, Carbon $end, bool $isActive = true, ?Screen $screen = null): AdSchedule
    {
        return AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => ($screen ?? $this->screen)->id,
            'start_time' => $start,
            'end_time' => $end,
            'is_active' => $isActive,
        ]);
    }

    /**
     * @return array<int, int|null>
     */
    private function playlistAdIds(?Screen $screen = null): array
    {
        $payload = app(AdSchedulerService::class)->forScreen(($screen ?? $this->screen)->fresh());

        return collect($payload['items'])->pluck('ad_id')->all();
    }

    // ---------------------------------------------------------------- base rules

    public function test_an_assigned_ad_with_no_schedules_is_eligible(): void
    {
        $ad = $this->assign($this->makeAd());

        $this->assertSame([$ad->id], $this->playlistAdIds());
    }

    public function test_an_unassigned_ad_is_never_eligible(): void
    {
        $ad = $this->makeAd();
        $this->schedule($ad, $this->now->copy()->subHour(), $this->now->copy()->addHour());

        $this->assertSame([], $this->playlistAdIds());
    }

    public function test_an_ad_whose_status_forbids_playback_is_not_eligible(): void
    {
        foreach ([AdStatus::Pending, AdStatus::Approved, AdStatus::Rejected, AdStatus::Expired] as $status) {
            $screen = $this->makeScreen();
            $ad = $this->assign($this->makeAd(['status' => $status]), 1, $screen);

            $this->assertSame(
                [],
                $this->playlistAdIds($screen),
                "Status [{$status->value}] must not play."
            );

            $this->assertNotNull($ad->id);
        }
    }

    public function test_an_ad_whose_global_window_has_not_opened_is_not_eligible(): void
    {
        $this->assign($this->makeAd(['start_date' => $this->now->copy()->addDay()]));

        $this->assertSame([], $this->playlistAdIds());
    }

    public function test_an_ad_whose_global_window_has_closed_is_not_eligible(): void
    {
        $this->assign($this->makeAd(['end_date' => $this->now->copy()->subDay()]));

        $this->assertSame([], $this->playlistAdIds());
    }

    /**
     * PART 8 — both constraints apply. A schedule cannot extend playback past the
     * ad's own validity window.
     */
    public function test_a_schedule_cannot_extend_playback_beyond_the_ads_global_window(): void
    {
        $ad = $this->assign($this->makeAd([
            'start_date' => $this->now->copy()->subDays(10),
            'end_date' => $this->now->copy()->subHour(),
        ]));

        // The schedule window is wide open right now, but the ad has expired.
        $this->schedule($ad, $this->now->copy()->subHour(), $this->now->copy()->addHours(5));

        $this->assertSame([], $this->playlistAdIds());
    }

    // ----------------------------------------------------------- schedule policy

    public function test_an_ad_inside_an_active_schedule_is_eligible(): void
    {
        $ad = $this->assign($this->makeAd());
        $this->schedule($ad, $this->now->copy()->subHour(), $this->now->copy()->addHour());

        $this->assertSame([$ad->id], $this->playlistAdIds());
    }

    public function test_an_ad_whose_only_schedule_is_in_the_future_does_not_play(): void
    {
        $ad = $this->assign($this->makeAd());
        $this->schedule($ad, $this->now->copy()->addHour(), $this->now->copy()->addHours(2));

        $this->assertSame([], $this->playlistAdIds());
    }

    public function test_an_ad_whose_only_schedule_has_expired_does_not_play(): void
    {
        $ad = $this->assign($this->makeAd());
        $this->schedule($ad, $this->now->copy()->subHours(3), $this->now->copy()->subHour());

        $this->assertSame([], $this->playlistAdIds());
    }

    /**
     * The historical defect: schedules existed but none matched, and the ad fell
     * back to always-on playback. It must not.
     */
    public function test_an_ad_with_schedules_never_falls_back_to_always_on(): void
    {
        $ad = $this->assign($this->makeAd());
        $this->schedule($ad, $this->now->copy()->subDays(2), $this->now->copy()->subDay());
        $this->schedule($ad, $this->now->copy()->addDay(), $this->now->copy()->addDays(2));

        $this->assertSame([], $this->playlistAdIds());
    }

    public function test_an_inactive_schedule_is_ignored_and_cannot_grant_eligibility(): void
    {
        $ad = $this->assign($this->makeAd());

        // Inactive, yet its window contains now.
        $this->schedule($ad, $this->now->copy()->subHour(), $this->now->copy()->addHour(), false);

        $this->assertSame(
            [],
            $this->playlistAdIds(),
            'An inactive schedule contributes nothing: existence still gates the ad.'
        );
    }

    public function test_an_active_schedule_wins_alongside_an_inactive_one(): void
    {
        $ad = $this->assign($this->makeAd());
        $this->schedule($ad, $this->now->copy()->subHour(), $this->now->copy()->addHour(), false);
        $this->schedule($ad, $this->now->copy()->subMinutes(30), $this->now->copy()->addMinutes(30), true);

        $this->assertSame([$ad->id], $this->playlistAdIds());
    }

    // ---------------------------------------------------------------- boundaries

    public function test_the_schedule_start_is_inclusive_and_the_end_is_exclusive(): void
    {
        $ad = $this->assign($this->makeAd());
        $start = $this->now->copy()->addHour();
        $end = $this->now->copy()->addHours(2);
        $this->schedule($ad, $start, $end);

        $scheduler = app(AdSchedulerService::class);

        $cases = [
            'one second before start' => [$start->copy()->subSecond(), false],
            'exactly at start' => [$start->copy(), true],
            'one second after start' => [$start->copy()->addSecond(), true],
            'one second before end' => [$end->copy()->subSecond(), true],
            'exactly at end' => [$end->copy(), false],
            'one second after end' => [$end->copy()->addSecond(), false],
        ];

        foreach ($cases as $label => [$moment, $expected]) {
            Carbon::setTestNow($moment);
            $scheduler->forget($this->screen);

            $ids = collect($scheduler->forScreen($this->screen->fresh())['items'])->pluck('ad_id')->all();

            $this->assertSame(
                $expected ? [$ad->id] : [],
                $ids,
                "Boundary case [{$label}] is wrong."
            );
        }
    }

    /**
     * The point of end-exclusivity: an instant belongs to exactly one of two
     * adjacent windows, so the playlist at that instant is unambiguous.
     */
    public function test_adjacent_windows_hand_over_cleanly_at_the_shared_instant(): void
    {
        $handover = $this->now->copy()->addHour();

        $first = $this->assign($this->makeAd(), 1);
        $second = $this->assign($this->makeAd(), 2);

        $this->schedule($first, $this->now->copy(), $handover);
        $this->schedule($second, $handover, $handover->copy()->addHour());

        Carbon::setTestNow($handover->copy()->subSecond());
        $this->assertSame([$first->id], $this->playlistAdIds());

        Carbon::setTestNow($handover);
        $this->assertSame([$second->id], $this->playlistAdIds());
    }

    public function test_the_ad_global_window_uses_the_same_inclusivity(): void
    {
        $start = $this->now->copy()->addHour();
        $end = $this->now->copy()->addHours(2);
        $ad = $this->assign($this->makeAd(['start_date' => $start, 'end_date' => $end]));

        Carbon::setTestNow($start->copy()->subSecond());
        $this->assertSame([], $this->playlistAdIds());

        Carbon::setTestNow($start);
        $this->assertSame([$ad->id], $this->playlistAdIds());

        Carbon::setTestNow($end->copy()->subSecond());
        $this->assertSame([$ad->id], $this->playlistAdIds());

        Carbon::setTestNow($end);
        $this->assertSame([], $this->playlistAdIds());
    }

    // ------------------------------------------------------- overlap & duplicates

    /**
     * PART 41 — eligibility is boolean. Two matching rows do not mean two items.
     */
    public function test_multiple_overlapping_schedules_produce_exactly_one_item(): void
    {
        $ad = $this->assign($this->makeAd());
        $this->schedule($ad, $this->now->copy()->subHours(2), $this->now->copy()->addHours(2));
        $this->schedule($ad, $this->now->copy()->subHour(), $this->now->copy()->addHour());
        $this->schedule($ad, $this->now->copy()->subMinutes(10), $this->now->copy()->addMinutes(10));

        $this->assertSame([$ad->id], $this->playlistAdIds());
    }

    /**
     * The representative row is deterministic: earliest end, then earliest start,
     * then lowest id.
     */
    public function test_the_representative_schedule_is_the_earliest_ending_match(): void
    {
        $ad = $this->assign($this->makeAd());
        $wide = $this->schedule($ad, $this->now->copy()->subHours(2), $this->now->copy()->addHours(2));
        $narrow = $this->schedule($ad, $this->now->copy()->subHour(), $this->now->copy()->addMinutes(30));

        $items = app(AdSchedulerService::class)->forScreen($this->screen->fresh())['items'];

        $this->assertSame($narrow->id, $items[0]['schedule_id']);
        $this->assertNotSame($wide->id, $items[0]['schedule_id']);
    }

    /**
     * PART 42 — legitimate overlap across different ads. Neither is deactivated to
     * make the other work; the playlist simply carries both.
     */
    public function test_two_different_ads_may_be_eligible_at_the_same_instant(): void
    {
        $adA = $this->assign($this->makeAd(), 1);
        $adB = $this->assign($this->makeAd(), 2);

        // A: 10:00 -> 14:00, B: 12:00 -> 16:00, now sits at 12:00 in both.
        $this->schedule($adA, $this->now->copy()->subHours(2), $this->now->copy()->addHours(2));
        $this->schedule($adB, $this->now->copy(), $this->now->copy()->addHours(4));

        $this->assertSame([$adA->id, $adB->id], $this->playlistAdIds());

        $this->assertTrue(AdSchedule::where('is_active', true)->count() === 2);
    }

    // ----------------------------------------------------------------- ordering

    public function test_items_are_ordered_by_play_order_then_ad_id(): void
    {
        $third = $this->assign($this->makeAd(), 5);
        $first = $this->assign($this->makeAd(), 1);
        $second = $this->assign($this->makeAd(), 1);

        $ids = $this->playlistAdIds();

        // play_order first; the shared order 1 is broken by ad id, so the ad
        // created earlier wins.
        $this->assertSame([$first->id, $second->id, $third->id], $ids);
    }

    /**
     * PART 40 — the same state at the same instant is fully reproducible, right
     * down to the ETag, across repeated uncached calculations.
     */
    public function test_the_playlist_is_deterministic_across_repeated_rebuilds(): void
    {
        $adA = $this->assign($this->makeAd(), 2);
        $adB = $this->assign($this->makeAd(), 1);
        $this->schedule($adA, $this->now->copy()->subHour(), $this->now->copy()->addHour());
        $this->schedule($adA, $this->now->copy()->subHours(2), $this->now->copy()->addHours(3));
        $this->schedule($adB, $this->now->copy()->subHour(), $this->now->copy()->addHour());

        $scheduler = app(AdSchedulerService::class);

        $first = $scheduler->forScreen($this->screen->fresh());

        $scheduler->forget($this->screen);
        $second = $scheduler->forScreen($this->screen->fresh());

        $scheduler->forget($this->screen);
        $third = $scheduler->forScreen($this->screen->fresh());

        $this->assertSame($first['items'], $second['items']);
        $this->assertSame($second['items'], $third['items']);
        $this->assertSame($first['etag'], $second['etag']);
        $this->assertSame($second['etag'], $third['etag']);
    }

    // --------------------------------------------------------------- performance

    /**
     * PART 44 — building a playlist costs a fixed number of queries whatever the
     * number of ads or schedule rows: one for the pivot-ordered ads, one for the
     * screen's schedule rows, plus the screen refresh. Eligibility is decided in
     * memory, so there is no per-ad or per-schedule query.
     */
    public function test_the_playlist_query_count_does_not_grow_with_the_catalogue(): void
    {
        $measure = function (int $adCount, int $schedulesPerAd): int {
            $screen = $this->makeScreen();

            for ($i = 0; $i < $adCount; $i++) {
                $ad = $this->assign($this->makeAd(), $i + 1, $screen);

                for ($j = 0; $j < $schedulesPerAd; $j++) {
                    $this->schedule(
                        $ad,
                        $this->now->copy()->subHours($j + 1),
                        $this->now->copy()->addHours($j + 1),
                        true,
                        $screen
                    );
                }
            }

            $scheduler = app(AdSchedulerService::class);
            $scheduler->forget($screen);

            $queries = 0;
            \Illuminate\Support\Facades\DB::listen(function () use (&$queries): void {
                $queries++;
            });

            $scheduler->forScreen($screen->fresh());

            return $queries;
        };

        $small = $measure(2, 1);
        $large = $measure(25, 4);

        $this->assertSame(
            $small,
            $large,
            "Playlist generation is not flat: {$small} queries for 2 ads, {$large} for 25."
        );
        $this->assertLessThanOrEqual(4, $large, 'Playlist generation should stay within a handful of queries.');
    }

    // ------------------------------------------------------------------ fallback

    public function test_the_configured_fallback_only_stands_in_for_an_empty_playlist(): void
    {
        config(['ads.fallback' => [
            'type' => 'image',
            'image' => 'https://cdn.example.test/fallback.png',
            'duration' => 10,
        ]]);

        $ad = $this->assign($this->makeAd());
        $this->schedule($ad, $this->now->copy()->addHour(), $this->now->copy()->addHours(2));

        // Nothing eligible yet -> fallback alone.
        $this->assertSame([null], $this->playlistAdIds());

        // At the boundary the real ad takes over and the fallback disappears.
        Carbon::setTestNow($this->now->copy()->addHour());
        $this->assertSame([$ad->id], $this->playlistAdIds());

        // After the window closes the fallback returns.
        Carbon::setTestNow($this->now->copy()->addHours(2));
        $this->assertSame([null], $this->playlistAdIds());
    }
}
