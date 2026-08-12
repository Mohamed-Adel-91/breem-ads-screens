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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pre-Phase 8 — locks the schedule activation contract.
 *
 * An unchecked HTML checkbox submits nothing, so `is_active` never reached the
 * controller and both StoreScheduleRequest (`?? true`) and UpdateScheduleRequest
 * (`?? $schedule->is_active`) fell back to "keep it active". A schedule could
 * therefore never be created inactive nor deactivated from the UI. The fix is a
 * hidden `is_active=0` companion field in both forms — no controller, request,
 * route or scheduling rule was touched.
 *
 * NOTE — deliberately NOT changed here: conflict resolution still runs for a
 * schedule submitted as inactive, deactivating overlapping schedules. That is
 * asserted below to pin it, and remains a deferred scheduling defect.
 */
class ScheduleActivationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Ad $ad;
    protected Screen $screen;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['ads.view', 'ads.schedule'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Schedule',
            'last_name' => 'Activation',
            'email' => 'schedule-activation@example.com',
            'password' => 'password',
            'mobile' => '8000000001',
        ]);
        $this->admin->givePermissionTo(['ads.view', 'ads.schedule']);

        $this->ad = Ad::create([
            'title' => ['en' => 'Activation Campaign'],
            'file_path' => 'upload/ads/creative.png',
            'file_type' => 'image',
            'duration_seconds' => 10,
            'status' => AdStatus::Active->value,
            'created_by' => User::factory()->create()->id,
        ]);

        $this->screen = Screen::factory()->create([
            'place_id' => Place::factory()->create()->id,
            'code' => 'SCR-ACTIVATION',
            'status' => ScreenStatus::Online->value,
        ]);
    }

    protected function storeUrl(string $locale = 'en'): string
    {
        return route('admin.ads.schedules.store', ['lang' => $locale, 'ad' => $this->ad->id]);
    }

    protected function updateUrl(AdSchedule $schedule, string $locale = 'en'): string
    {
        return route('admin.ads.schedules.update', [
            'lang' => $locale,
            'ad' => $this->ad->id,
            'schedule' => $schedule->id,
        ]);
    }

    /**
     * Mirrors exactly what the browser sends for the migrated forms:
     * the hidden field always, plus the checkbox value only when checked.
     */
    protected function payload(bool $checked, array $overrides = []): array
    {
        return array_merge([
            'screen_id' => $this->screen->id,
            'start_time' => now()->addDay()->startOfHour()->format('Y-m-d\TH:i'),
            'end_time' => now()->addDays(2)->startOfHour()->format('Y-m-d\TH:i'),
            // Unchecked -> only the hidden "0"; checked -> the checkbox "1" wins.
            'is_active' => $checked ? '1' : '0',
        ], $overrides);
    }

    protected function makeSchedule(bool $isActive): AdSchedule
    {
        return AdSchedule::create([
            'ad_id' => $this->ad->id,
            'screen_id' => $this->screen->id,
            'start_time' => now()->addDays(10)->startOfHour(),
            'end_time' => now()->addDays(11)->startOfHour(),
            'is_active' => $isActive,
        ]);
    }

    public function test_the_forms_submit_a_hidden_zero_alongside_the_checkbox(): void
    {
        $schedule = $this->makeSchedule(true);

        $response = $this->actingAs($this->admin, 'admin')->get(
            route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->ad->id])
        );

        $response->assertOk();
        // The field name is unchanged, and the hidden companion is present.
        $response->assertSee('name="is_active"', false);
        $response->assertSee('<input type="hidden" name="is_active" value="0">', false);
        $response->assertSee('id="create_is_active"', false);
        $response->assertSee('id="schedule_active_' . $schedule->id . '"', false);
    }

    public function test_checked_creates_an_active_schedule(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post($this->storeUrl(), $this->payload(true))
            ->assertRedirect(route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->ad->id]));

        $schedule = AdSchedule::where('ad_id', $this->ad->id)->firstOrFail();
        $this->assertTrue($schedule->is_active);
        $this->assertDatabaseHas('ad_schedules', ['id' => $schedule->id, 'is_active' => true]);
    }

    public function test_unchecked_creates_an_inactive_schedule(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post($this->storeUrl(), $this->payload(false))
            ->assertRedirect(route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->ad->id]));

        $schedule = AdSchedule::where('ad_id', $this->ad->id)->firstOrFail();
        $this->assertFalse($schedule->is_active, 'An unchecked box must create an inactive schedule.');
        $this->assertDatabaseHas('ad_schedules', ['id' => $schedule->id, 'is_active' => false]);
    }

    public function test_checked_activates_an_existing_schedule(): void
    {
        $schedule = $this->makeSchedule(false);

        $this->actingAs($this->admin, 'admin')
            ->put($this->updateUrl($schedule), $this->payload(true))
            ->assertRedirect(route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->ad->id]));

        $this->assertTrue($schedule->fresh()->is_active);
    }

    public function test_unchecked_deactivates_an_existing_schedule(): void
    {
        $schedule = $this->makeSchedule(true);

        $this->actingAs($this->admin, 'admin')
            ->put($this->updateUrl($schedule), $this->payload(false))
            ->assertRedirect(route('admin.ads.schedules.index', ['lang' => 'en', 'ad' => $this->ad->id]));

        $this->assertFalse(
            $schedule->fresh()->is_active,
            'An unchecked box must be able to deactivate an existing schedule.'
        );
    }

    public function test_activation_round_trip_works_in_arabic_too(): void
    {
        $schedule = $this->makeSchedule(true);

        $this->actingAs($this->admin, 'admin')
            ->put($this->updateUrl($schedule, 'ar'), $this->payload(false))
            ->assertRedirect(route('admin.ads.schedules.index', ['lang' => 'ar', 'ad' => $this->ad->id]));
        $this->assertFalse($schedule->fresh()->is_active);

        $this->actingAs($this->admin, 'admin')
            ->put($this->updateUrl($schedule, 'ar'), $this->payload(true))
            ->assertRedirect(route('admin.ads.schedules.index', ['lang' => 'ar', 'ad' => $this->ad->id]));
        $this->assertTrue($schedule->fresh()->is_active);
    }

    public function test_activation_changes_do_not_alter_the_other_schedule_fields(): void
    {
        $schedule = $this->makeSchedule(true);
        $originalScreen = $schedule->screen_id;

        $start = now()->addDays(20)->startOfHour();
        $end = now()->addDays(21)->startOfHour();

        $this->actingAs($this->admin, 'admin')->put($this->updateUrl($schedule), $this->payload(false, [
            'start_time' => $start->format('Y-m-d\TH:i'),
            'end_time' => $end->format('Y-m-d\TH:i'),
        ]));

        $schedule->refresh();
        $this->assertFalse($schedule->is_active);
        $this->assertSame($originalScreen, $schedule->screen_id);
        $this->assertSame($start->format('Y-m-d H:i'), $schedule->start_time->format('Y-m-d H:i'));
        $this->assertSame($end->format('Y-m-d H:i'), $schedule->end_time->format('Y-m-d H:i'));
    }

    /**
     * Pins the KNOWN DEFECT: submitting an INACTIVE schedule still runs conflict
     * resolution and deactivates overlapping schedules. Deferred, not fixed.
     */
    public function test_known_defect_an_inactive_submission_still_deactivates_overlaps(): void
    {
        $existing = AdSchedule::create([
            'ad_id' => $this->ad->id,
            'screen_id' => $this->screen->id,
            'start_time' => now()->addDay()->startOfHour(),
            'end_time' => now()->addDays(3)->startOfHour(),
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'admin')->post($this->storeUrl(), $this->payload(false, [
            'start_time' => now()->addDays(2)->startOfHour()->format('Y-m-d\TH:i'),
            'end_time' => now()->addDays(4)->startOfHour()->format('Y-m-d\TH:i'),
        ]));

        $this->assertFalse(
            $existing->fresh()->is_active,
            'Deferred defect: an inactive submission still deactivates overlapping schedules.'
        );
    }
}
