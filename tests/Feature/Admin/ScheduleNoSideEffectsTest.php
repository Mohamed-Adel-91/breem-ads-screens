<?php

namespace Tests\Feature\Admin;

use App\Enums\AdStatus;
use App\Enums\ScreenStatus;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 12 — saving schedule X must never mutate schedule Y.
 *
 * ScheduleController::resolveScheduleConflicts() used to deactivate every
 * overlapping row on the same screen, whichever ad owned it. Publishing one
 * campaign therefore took a *different advertiser's* campaign off the air, with no
 * warning and no way to tell from the form that it had happened. This is the most
 * important invariant of the phase, so it is pinned across ads, across screens and
 * in both directions.
 */
class ScheduleNoSideEffectsTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Screen $screen;
    private Screen $otherScreen;
    private Ad $adA;
    private Ad $adB;
    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['ads.view', 'ads.schedule'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'No',
            'last_name' => 'SideEffects',
            'email' => 'schedule-side-effects@example.com',
            'password' => 'password',
            'mobile' => '9100000001',
        ]);
        $this->admin->givePermissionTo(['ads.view', 'ads.schedule']);

        $this->now = Carbon::create(2026, 7, 20, 12, 0, 0);
        Carbon::setTestNow($this->now);

        $place = Place::factory()->create();
        $this->screen = Screen::factory()->create([
            'place_id' => $place->id,
            'code' => 'SCR-SHARED',
            'status' => ScreenStatus::Online->value,
        ]);
        $this->otherScreen = Screen::factory()->create([
            'place_id' => $place->id,
            'code' => 'SCR-OTHER',
            'status' => ScreenStatus::Online->value,
        ]);

        $creator = User::factory()->create();

        $this->adA = Ad::create([
            'title' => ['en' => 'Advertiser A'],
            'file_path' => 'upload/ads/a.png',
            'file_type' => 'image',
            'duration_seconds' => 10,
            'status' => AdStatus::Active->value,
            'created_by' => $creator->id,
        ]);
        $this->adB = Ad::create([
            'title' => ['en' => 'Advertiser B'],
            'file_path' => 'upload/ads/b.png',
            'file_type' => 'image',
            'duration_seconds' => 10,
            'status' => AdStatus::Active->value,
            'created_by' => $creator->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function schedule(Ad $ad, int $startHours, int $endHours, bool $isActive = true, ?Screen $screen = null): AdSchedule
    {
        return AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => ($screen ?? $this->screen)->id,
            'start_time' => $this->now->copy()->addHours($startHours),
            'end_time' => $this->now->copy()->addHours($endHours),
            'is_active' => $isActive,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $startHours, int $endHours, bool $isActive = true, ?Screen $screen = null): array
    {
        return [
            'screen_id' => ($screen ?? $this->screen)->id,
            'start_time' => $this->now->copy()->addHours($startHours)->format('Y-m-d\TH:i'),
            'end_time' => $this->now->copy()->addHours($endHours)->format('Y-m-d\TH:i'),
            'is_active' => $isActive ? '1' : '0',
        ];
    }

    private function storeFor(Ad $ad, array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'admin')->post(
            route('admin.ads.schedules.store', ['lang' => 'en', 'ad' => $ad->id]),
            $payload
        );
    }

    private function updateFor(Ad $ad, AdSchedule $schedule, array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'admin')->put(
            route('admin.ads.schedules.update', ['lang' => 'en', 'ad' => $ad->id, 'schedule' => $schedule->id]),
            $payload
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(AdSchedule $schedule): array
    {
        $fresh = $schedule->fresh();

        return [
            'screen_id' => $fresh->screen_id,
            'start_time' => $fresh->start_time->toDateTimeString(),
            'end_time' => $fresh->end_time->toDateTimeString(),
            'is_active' => $fresh->is_active,
        ];
    }

    // ---------------------------------------------------------------- cross-ad

    /**
     * The headline case: Ad A runs 10:00→14:00, Ad B is published for 12:00→16:00.
     * Both stay active; the screen rotates between them from 12:00 to 14:00.
     */
    public function test_publishing_an_overlapping_schedule_for_another_ad_changes_nothing(): void
    {
        $scheduleA = $this->schedule($this->adA, -2, 2);
        $before = $this->snapshot($scheduleA);

        $this->storeFor($this->adB, $this->payload(0, 4))
            ->assertRedirect(route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->adB->id]));

        $this->assertSame($before, $this->snapshot($scheduleA), "Advertiser A's schedule was mutated.");

        $this->assertSame(
            2,
            AdSchedule::where('screen_id', $this->screen->id)->where('is_active', true)->count(),
            'Both advertisers must remain scheduled.'
        );
    }

    /**
     * And the consequence at the API boundary: both are genuinely eligible.
     */
    public function test_both_overlapping_ads_reach_the_playlist(): void
    {
        $this->screen->ads()->attach($this->adA->id, ['play_order' => 1]);
        $this->screen->ads()->attach($this->adB->id, ['play_order' => 2]);

        $this->schedule($this->adA, -2, 2);
        $this->storeFor($this->adB, $this->payload(0, 4));

        $items = app(\App\Services\Screen\AdSchedulerService::class)
            ->forScreen($this->screen->fresh())['items'];

        $this->assertSame(
            [$this->adA->id, $this->adB->id],
            collect($items)->pluck('ad_id')->all()
        );
    }

    public function test_a_fully_enclosing_schedule_does_not_deactivate_the_enclosed_one(): void
    {
        $enclosed = $this->schedule($this->adA, 1, 2);
        $before = $this->snapshot($enclosed);

        // A window that completely contains the existing one — the old conflict
        // query's third branch.
        $this->storeFor($this->adB, $this->payload(0, 5));

        $this->assertSame($before, $this->snapshot($enclosed));
    }

    public function test_an_identical_window_for_another_ad_is_accepted_without_mutation(): void
    {
        $existing = $this->schedule($this->adA, 1, 3);
        $before = $this->snapshot($existing);

        $this->storeFor($this->adB, $this->payload(1, 3));

        $this->assertSame($before, $this->snapshot($existing));
        $this->assertSame(2, AdSchedule::where('is_active', true)->count());
    }

    // ------------------------------------------------------------------ same-ad

    public function test_editing_one_schedule_leaves_the_ads_other_schedules_untouched(): void
    {
        $edited = $this->schedule($this->adA, 1, 3);
        $sibling = $this->schedule($this->adA, 2, 6);
        $before = $this->snapshot($sibling);

        $this->updateFor($this->adA, $edited, $this->payload(1, 8));

        $this->assertSame($before, $this->snapshot($sibling), 'A sibling schedule was mutated.');
        $this->assertTrue($edited->fresh()->is_active);
    }

    public function test_deactivating_one_schedule_does_not_deactivate_a_sibling(): void
    {
        $edited = $this->schedule($this->adA, 1, 3);
        $sibling = $this->schedule($this->adA, 2, 6);

        $this->updateFor($this->adA, $edited, $this->payload(1, 3, false));

        $this->assertFalse($edited->fresh()->is_active);
        $this->assertTrue($sibling->fresh()->is_active);
    }

    public function test_deleting_one_schedule_leaves_the_others_in_place(): void
    {
        $doomed = $this->schedule($this->adA, 1, 3);
        $survivor = $this->schedule($this->adA, 2, 6);
        $otherAd = $this->schedule($this->adB, 1, 4);

        $this->actingAs($this->admin, 'admin')->delete(
            route('admin.ads.schedules.destroy', ['lang' => 'en', 'ad' => $this->adA->id, 'schedule' => $doomed->id])
        )->assertRedirect();

        $this->assertNull($doomed->fresh());
        $this->assertNotNull($survivor->fresh());
        $this->assertTrue($otherAd->fresh()->is_active);
    }

    // -------------------------------------------------------------- other screens

    public function test_a_schedule_on_one_screen_never_touches_another_screens_rows(): void
    {
        $elsewhere = $this->schedule($this->adA, 0, 4, true, $this->otherScreen);
        $before = $this->snapshot($elsewhere);

        $this->storeFor($this->adB, $this->payload(0, 4));

        $this->assertSame($before, $this->snapshot($elsewhere));
    }

    // ------------------------------------------------------------------ validation

    /**
     * PART 34 — an inverted window is a validation error, never a silent swap.
     */
    public function test_an_end_before_the_start_is_rejected(): void
    {
        $response = $this->storeFor($this->adA, $this->payload(4, 1));

        $response->assertSessionHasErrors('end_time');
        $this->assertSame(0, AdSchedule::count(), 'Nothing may be persisted from an invalid window.');
    }

    public function test_an_end_equal_to_the_start_is_rejected(): void
    {
        $response = $this->storeFor($this->adA, $this->payload(2, 2));

        $response->assertSessionHasErrors('end_time');
        $this->assertSame(0, AdSchedule::count());
    }

    public function test_an_invalid_update_leaves_the_stored_row_alone(): void
    {
        $schedule = $this->schedule($this->adA, 1, 3);
        $before = $this->snapshot($schedule);

        $this->updateFor($this->adA, $schedule, $this->payload(6, 2))
            ->assertSessionHasErrors('end_time');

        $this->assertSame($before, $this->snapshot($schedule));
    }
}
