<?php

namespace Tests\Unit\Support;

use App\Enums\AdStatus;
use App\Enums\PlaceType;
use App\Models\Ad;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use App\Services\Screen\AdSchedulerService;
use App\Support\AdValidity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 13 — global ad validity dates.
 *
 * The ad form writes `start_date` / `end_date` from `type="date"` inputs, so they
 * arrive as calendar dates stored at midnight. Reading `end_date` as a literal
 * exclusive instant meant an ad ending "Aug 31" stopped at `Aug 31 00:00` and never
 * played on the day the operator picked.
 *
 * A date-only end now covers the whole of that day. A value carrying a time is still
 * an exact instant, which is what keeps legacy rows (the seeded demo ads hold real
 * datetimes) meaning exactly what they always meant — no stored value is rewritten.
 *
 * Schedule-row semantics are untouched: TimeWindow still applies literally, to the
 * second.
 */
class AdValidityTest extends TestCase
{
    use RefreshDatabase;

    private Screen $screen;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['ads.fallback' => null, 'services.screens.playlist_ttl' => 300]);

        $place = Place::create([
            'name' => ['en' => 'Validity Hall'],
            'address' => ['en' => '4 Calendar Way'],
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

    private function makeAd(?string $start, ?string $end): Ad
    {
        $ad = Ad::create([
            'title' => ['en' => 'Validity Campaign'],
            'file_path' => 'upload/ads/validity.png',
            'file_type' => 'image',
            'duration_seconds' => 10,
            'status' => AdStatus::Active,
            'created_by' => User::factory()->create()->id,
            'start_date' => $start,
            'end_date' => $end,
        ]);

        // Assigned, no schedule rows: always-on, so the global window is the only
        // thing under test.
        $this->screen->ads()->attach($ad->id, ['play_order' => 1]);

        return $ad;
    }

    private function isEligibleAt(Ad $ad, string $moment): bool
    {
        Carbon::setTestNow(Carbon::parse($moment));

        $scheduler = app(AdSchedulerService::class);
        $scheduler->forget($this->screen);

        $ids = collect($scheduler->forScreen($this->screen->fresh())['items'])->pluck('ad_id')->all();

        return in_array($ad->id, $ids, true);
    }

    // -------------------------------------------------------------- the unit rule

    public function test_a_date_only_end_is_normalised_to_the_following_midnight(): void
    {
        $end = Carbon::parse('2026-08-31 00:00:00');

        $this->assertTrue(
            AdValidity::endsBefore($end)->equalTo(Carbon::parse('2026-09-01 00:00:00'))
        );
    }

    public function test_an_end_carrying_a_time_is_left_exactly_as_stored(): void
    {
        $end = Carbon::parse('2026-08-31 15:43:47');

        $this->assertTrue(
            AdValidity::endsBefore($end)->equalTo($end),
            'Legacy datetimes must keep their precise meaning — no reinterpretation.'
        );
    }

    public function test_a_start_is_always_used_as_stored(): void
    {
        $start = Carbon::parse('2026-08-01 00:00:00');

        $this->assertTrue(AdValidity::startsAt($start)->equalTo($start));
    }

    public function test_unbounded_dates_stay_unbounded(): void
    {
        $this->assertNull(AdValidity::startsAt(null));
        $this->assertNull(AdValidity::endsBefore(null));
        $this->assertTrue(AdValidity::contains(null, null, Carbon::parse('2026-08-15 12:00:00')));
    }

    // ------------------------------------------------- the operator-visible result

    /**
     * PART 43 — an ad set to run Aug 1 through Aug 31 plays throughout Aug 31.
     */
    public function test_an_ad_ending_aug_31_plays_for_the_whole_of_aug_31(): void
    {
        $ad = $this->makeAd('2026-08-01', '2026-08-31');

        $this->assertTrue($this->isEligibleAt($ad, '2026-08-01 00:00:00'), 'the first instant');
        $this->assertTrue($this->isEligibleAt($ad, '2026-08-30 12:00:00'), 'mid-campaign');
        $this->assertTrue($this->isEligibleAt($ad, '2026-08-31 00:00:00'), 'start of the final day');
        $this->assertTrue($this->isEligibleAt($ad, '2026-08-31 12:00:00'), 'middle of the final day');
        $this->assertTrue($this->isEligibleAt($ad, '2026-08-31 23:59:59'), 'last second of the final day');

        $this->assertFalse($this->isEligibleAt($ad, '2026-09-01 00:00:00'), 'the day after must be excluded');
        $this->assertFalse($this->isEligibleAt($ad, '2026-07-31 23:59:59'), 'the day before must be excluded');
    }

    public function test_a_single_day_campaign_plays_for_that_one_day(): void
    {
        $ad = $this->makeAd('2026-08-15', '2026-08-15');

        $this->assertFalse($this->isEligibleAt($ad, '2026-08-14 23:59:59'));
        $this->assertTrue($this->isEligibleAt($ad, '2026-08-15 00:00:00'));
        $this->assertTrue($this->isEligibleAt($ad, '2026-08-15 23:59:59'));
        $this->assertFalse($this->isEligibleAt($ad, '2026-08-16 00:00:00'));
    }

    public function test_an_open_ended_ad_keeps_playing(): void
    {
        $ad = $this->makeAd('2026-08-01', null);

        $this->assertFalse($this->isEligibleAt($ad, '2026-07-31 23:59:59'));
        $this->assertTrue($this->isEligibleAt($ad, '2026-08-01 00:00:00'));
        $this->assertTrue($this->isEligibleAt($ad, '2030-01-01 00:00:00'));
    }

    public function test_an_ad_with_no_dates_is_always_within_its_window(): void
    {
        $ad = $this->makeAd(null, null);

        $this->assertTrue($this->isEligibleAt($ad, '2020-01-01 00:00:00'));
        $this->assertTrue($this->isEligibleAt($ad, '2040-01-01 00:00:00'));
    }

    /**
     * A legacy row holding a real datetime still expires at that instant, not at the
     * end of its day.
     */
    public function test_a_legacy_datetime_end_is_not_extended_to_the_end_of_its_day(): void
    {
        $ad = $this->makeAd('2025-11-16 15:43:47', '2025-12-17 15:43:47');

        $this->assertTrue($this->isEligibleAt($ad, '2025-12-17 15:43:46'));
        $this->assertFalse($this->isEligibleAt($ad, '2025-12-17 15:43:47'), 'end stays exclusive');
        $this->assertFalse($this->isEligibleAt($ad, '2025-12-17 20:00:00'));
    }

    // ----------------------------------------------------------- device manifest

    /**
     * The manifest must report the window the server will actually honour. Sending the
     * raw `end_date` would tell a player that a date-only "ends Aug 31" campaign stops
     * at Aug 31 00:00, so a device that self-expires on `valid_until` would go dark for
     * the whole of the final day.
     */
    public function test_the_playlist_reports_the_effective_window_for_an_unscheduled_ad(): void
    {
        $ad = $this->makeAd('2026-08-01', '2026-08-31');

        Carbon::setTestNow(Carbon::parse('2026-08-15 12:00:00'));

        $scheduler = app(AdSchedulerService::class);
        $scheduler->forget($this->screen);

        $item = $scheduler->forScreen($this->screen->fresh())['items'][0];

        $this->assertSame('2026-08-01T00:00:00+00:00', $item['valid_from']);
        $this->assertSame('2026-09-01T00:00:00+00:00', $item['valid_until']);
        $this->assertSame('2026-09-01T00:00:00+00:00', $item['ad_valid_until']);
    }

    /**
     * A scheduled ad's own window is exact, so the schedule bounds pass through
     * untouched while `ad_valid_until` still reports the effective ad bound.
     */
    public function test_schedule_bounds_pass_through_unchanged(): void
    {
        $ad = $this->makeAd('2026-08-01', '2026-08-31');

        $ad->schedules()->create([
            'screen_id' => $this->screen->id,
            'start_time' => '2026-08-15 09:00:00',
            'end_time' => '2026-08-15 17:30:00',
            'is_active' => true,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-15 12:00:00'));

        $scheduler = app(AdSchedulerService::class);
        $scheduler->forget($this->screen);

        $item = $scheduler->forScreen($this->screen->fresh())['items'][0];

        $this->assertSame('2026-08-15T09:00:00+00:00', $item['valid_from']);
        $this->assertSame('2026-08-15T17:30:00+00:00', $item['valid_until']);
        $this->assertSame('2026-09-01T00:00:00+00:00', $item['ad_valid_until']);
    }

    // ------------------------------------------------------------ cache boundary

    /**
     * The Phase 12 boundary TTL must use the *effective* end, or the cache would
     * expire a day early and the ad would vanish for the whole of its final day.
     */
    public function test_the_cache_boundary_follows_the_effective_end(): void
    {
        $ad = $this->makeAd('2026-08-01', '2026-08-31');

        Carbon::setTestNow(Carbon::parse('2026-08-31 23:59:50'));

        $scheduler = app(AdSchedulerService::class);
        $scheduler->forget($this->screen);

        $payload = $scheduler->forScreen($this->screen->fresh());

        $this->assertSame([$ad->id], collect($payload['items'])->pluck('ad_id')->all());
        $this->assertTrue(
            $payload['expires_at']->equalTo(Carbon::parse('2026-09-01 00:00:00')),
            'The entry must expire at the effective boundary, not at Aug 31 00:00.'
        );

        // And crossing it drops the ad without waiting for the TTL.
        Carbon::setTestNow(Carbon::parse('2026-09-01 00:00:00'));
        $this->assertSame([], collect($scheduler->forScreen($this->screen->fresh())['items'])->pluck('ad_id')->all());
    }

    /**
     * The start boundary is unchanged: a date-only start is the beginning of that
     * day, inclusive.
     */
    public function test_the_start_boundary_is_the_beginning_of_the_selected_day(): void
    {
        $ad = $this->makeAd('2026-08-05', '2026-08-10');

        Carbon::setTestNow(Carbon::parse('2026-08-04 23:59:50'));

        $scheduler = app(AdSchedulerService::class);
        $scheduler->forget($this->screen);

        $payload = $scheduler->forScreen($this->screen->fresh());

        $this->assertSame([], collect($payload['items'])->pluck('ad_id')->all());
        $this->assertTrue($payload['expires_at']->equalTo(Carbon::parse('2026-08-05 00:00:00')));

        Carbon::setTestNow(Carbon::parse('2026-08-05 00:00:00'));
        $this->assertSame(
            [$ad->id],
            collect($scheduler->forScreen($this->screen->fresh())['items'])->pluck('ad_id')->all()
        );
    }

    /**
     * The ad window and a schedule row are ANDed, and each keeps its own rule: the
     * schedule end stays literal while the ad end covers its day.
     */
    public function test_a_schedule_row_keeps_its_literal_end_inside_a_day_long_ad_window(): void
    {
        $ad = $this->makeAd('2026-08-20', '2026-08-20');

        $ad->schedules()->create([
            'screen_id' => $this->screen->id,
            'start_time' => '2026-08-20 09:00:00',
            'end_time' => '2026-08-20 17:00:00',
            'is_active' => true,
        ]);

        $this->assertFalse($this->isEligibleAt($ad, '2026-08-20 08:59:59'), 'before the schedule window');
        $this->assertTrue($this->isEligibleAt($ad, '2026-08-20 09:00:00'), 'schedule start is inclusive');
        $this->assertTrue($this->isEligibleAt($ad, '2026-08-20 16:59:59'));
        $this->assertFalse($this->isEligibleAt($ad, '2026-08-20 17:00:00'), 'schedule end stays exclusive');
        // The ad's own day is still open, but the schedule gates it.
        $this->assertFalse($this->isEligibleAt($ad, '2026-08-20 23:00:00'));
    }
}
