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
use App\Services\Screen\AdSchedulerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 13 — the advertisement approval workflow.
 *
 * Before this phase there was none: `status` was a free select on the ad form, so
 * anyone holding `ads.create` or `ads.edit` could write `active` and put a creative
 * on real screens without review. The `ads.approve` permission existed in the seeder
 * with **zero consumers** anywhere in the codebase.
 *
 * Now status changes only along the edges AdStatus declares, only through
 * admin.ads.transition, and only with `ads.approve`.
 */
class AdApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Admin $approver;
    private Admin $editor;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['ads.view', 'ads.create', 'ads.edit', 'ads.delete', 'ads.approve'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        // Holds ads.approve.
        $this->approver = Admin::create([
            'first_name' => 'Ada',
            'last_name' => 'Approver',
            'email' => 'ad-approver@example.com',
            'password' => 'password',
            'mobile' => '9300000001',
        ]);
        $this->approver->givePermissionTo(['ads.view', 'ads.create', 'ads.edit', 'ads.approve']);

        // Full content rights, deliberately WITHOUT ads.approve.
        $this->editor = Admin::create([
            'first_name' => 'Edd',
            'last_name' => 'Editor',
            'email' => 'ad-editor@example.com',
            'password' => 'password',
            'mobile' => '9300000002',
        ]);
        $this->editor->givePermissionTo(['ads.view', 'ads.create', 'ads.edit', 'ads.delete']);

        $this->owner = User::factory()->create();

        config(['ads.try_ffprobe' => false]);
        Carbon::setTestNow(Carbon::create(2026, 9, 1, 10, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeAd(array $overrides = []): Ad
    {
        return Ad::create(array_merge([
            'title' => ['en' => 'Review Me'],
            'file_path' => 'upload/ads/review.mp4',
            'file_type' => 'video',
            'duration_seconds' => 25,
            'status' => AdStatus::Pending,
            'created_by' => $this->owner->id,
        ], $overrides));
    }

    private function transition(Admin $as, Ad $ad, string $action, array $extra = [])
    {
        return $this->actingAs($as, 'admin')->post(
            route('admin.ads.transition', ['lang' => 'en', 'ad' => $ad->id]),
            array_merge(['action' => $action], $extra)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function editPayload(Ad $ad, array $overrides = []): array
    {
        return array_merge([
            'title' => $ad->getTranslations('title'),
            'description' => $ad->getTranslations('description'),
            'created_by' => $ad->created_by,
            'duration_seconds' => $ad->duration_seconds,
            'start_date' => optional($ad->start_date)->format('Y-m-d'),
            'end_date' => optional($ad->end_date)->format('Y-m-d'),
        ], $overrides);
    }

    // -------------------------------------------------------------- authorization

    public function test_an_admin_without_the_approve_permission_cannot_transition(): void
    {
        $ad = $this->makeAd();

        $this->transition($this->editor, $ad, AdStatus::ACTION_APPROVE)->assertForbidden();

        $this->assertSame(AdStatus::Pending, $ad->fresh()->status);
    }

    public function test_an_authorized_approver_can_approve(): void
    {
        $ad = $this->makeAd();

        $this->transition($this->approver, $ad, AdStatus::ACTION_APPROVE)->assertRedirect();

        $this->assertSame(AdStatus::Approved, $ad->fresh()->status);
    }

    public function test_approval_records_the_approving_admin_and_timestamp(): void
    {
        $ad = $this->makeAd();

        $this->transition($this->approver, $ad, AdStatus::ACTION_APPROVE);

        $ad->refresh();

        $this->assertSame($this->approver->id, $ad->approved_by_admin_id);
        $this->assertTrue($ad->approved_at->equalTo(now()));
        // The legacy users FK is left alone, not filled with an admin id.
        $this->assertNull($ad->approved_by);
    }

    public function test_the_approval_action_panel_is_hidden_without_the_permission(): void
    {
        $ad = $this->makeAd();
        $url = route('admin.ads.show', ['lang' => 'en', 'ad' => $ad->id]);

        $this->actingAs($this->editor, 'admin')->get($url)
            ->assertOk()
            ->assertDontSee(route('admin.ads.transition', ['lang' => 'en', 'ad' => $ad->id]), false);

        $this->actingAs($this->approver, 'admin')->get($url)
            ->assertOk()
            ->assertSee(route('admin.ads.transition', ['lang' => 'en', 'ad' => $ad->id]), false);
    }

    // ------------------------------------------------------------------- bypasses

    public function test_creating_an_ad_always_starts_it_pending(): void
    {
        $this->actingAs($this->editor, 'admin')->post(route('admin.ads.store', ['lang' => 'en']), [
            'title' => ['en' => 'Sneaky'],
            'created_by' => $this->owner->id,
            'duration_seconds' => 10,
            'creative' => UploadedFile::fake()->image('new.jpg')->size(40),
            // Injected, and must be ignored.
            'status' => AdStatus::Active->value,
            'approved_by' => $this->owner->id,
        ])->assertRedirect();

        $ad = Ad::firstOrFail();

        $this->assertSame(AdStatus::Pending, $ad->status, 'A create must never produce a live ad.');
        $this->assertNull($ad->approved_by, 'approved_by must not be settable from the create form.');
        $this->assertNull($ad->approved_by_admin_id);
    }

    public function test_the_create_action_records_the_acting_admin(): void
    {
        $this->actingAs($this->editor, 'admin')->post(route('admin.ads.store', ['lang' => 'en']), [
            'title' => ['en' => 'Attributed'],
            'created_by' => $this->owner->id,
            'duration_seconds' => 10,
            'creative' => UploadedFile::fake()->image('new.jpg')->size(40),
        ])->assertRedirect();

        $ad = Ad::firstOrFail();

        $this->assertSame($this->editor->id, $ad->created_by_admin_id);
        // And the legacy content-owner column keeps its own meaning.
        $this->assertSame($this->owner->id, $ad->created_by);
    }

    public function test_an_ordinary_edit_cannot_inject_a_live_status(): void
    {
        $ad = $this->makeAd();

        $this->actingAs($this->editor, 'admin')->put(
            route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]),
            $this->editPayload($ad, [
                'status' => AdStatus::Active->value,
                'approved_by' => $this->owner->id,
            ])
        )->assertRedirect();

        $ad->refresh();

        $this->assertSame(AdStatus::Pending, $ad->status);
        $this->assertNull($ad->approved_by);
    }

    public function test_an_edit_cannot_promote_an_approved_ad_to_live(): void
    {
        $ad = $this->makeAd(['status' => AdStatus::Approved]);

        $this->actingAs($this->editor, 'admin')->put(
            route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]),
            $this->editPayload($ad, ['status' => AdStatus::Active->value])
        )->assertRedirect();

        $this->assertSame(AdStatus::Approved, $ad->fresh()->status);
    }

    // ----------------------------------------------------------------- transitions

    /**
     * Every edge AdStatus declares, exercised end to end through the HTTP action.
     *
     * @return array<string, array{0: AdStatus, 1: string, 2: AdStatus}>
     */
    public static function allowedTransitionProvider(): array
    {
        return [
            'pending approve' => [AdStatus::Pending, AdStatus::ACTION_APPROVE, AdStatus::Approved],
            'pending reject' => [AdStatus::Pending, AdStatus::ACTION_REJECT, AdStatus::Rejected],
            'approved publish' => [AdStatus::Approved, AdStatus::ACTION_PUBLISH, AdStatus::Active],
            'approved reject' => [AdStatus::Approved, AdStatus::ACTION_REJECT, AdStatus::Rejected],
            'approved expire' => [AdStatus::Approved, AdStatus::ACTION_EXPIRE, AdStatus::Expired],
            'rejected approve' => [AdStatus::Rejected, AdStatus::ACTION_APPROVE, AdStatus::Approved],
            'active unpublish' => [AdStatus::Active, AdStatus::ACTION_UNPUBLISH, AdStatus::Approved],
            'active reject' => [AdStatus::Active, AdStatus::ACTION_REJECT, AdStatus::Rejected],
            'active expire' => [AdStatus::Active, AdStatus::ACTION_EXPIRE, AdStatus::Expired],
            'expired approve' => [AdStatus::Expired, AdStatus::ACTION_APPROVE, AdStatus::Approved],
        ];
    }

    /**
     * @dataProvider allowedTransitionProvider
     */
    public function test_an_allowed_transition_moves_the_ad(AdStatus $from, string $action, AdStatus $to): void
    {
        $ad = $this->makeAd(['status' => $from]);

        $this->transition($this->approver, $ad, $action)->assertRedirect();

        $this->assertSame($to, $ad->fresh()->status, "{$from->value} --{$action}--> {$to->value} failed.");
    }

    /**
     * Every pair the map does NOT declare, derived from the enum itself so a future
     * edge cannot be added without this test noticing.
     *
     * @return array<string, array{0: AdStatus, 1: string}>
     */
    public static function refusedTransitionProvider(): array
    {
        $cases = [];

        foreach (AdStatus::cases() as $status) {
            foreach (AdStatus::actions() as $action) {
                if ($status->allows($action)) {
                    continue;
                }

                $cases["{$status->value} cannot {$action}"] = [$status, $action];
            }
        }

        return $cases;
    }

    /**
     * @dataProvider refusedTransitionProvider
     */
    public function test_an_undeclared_transition_is_refused(AdStatus $from, string $action): void
    {
        $ad = $this->makeAd(['status' => $from]);

        $this->transition($this->approver, $ad, $action)->assertSessionHasErrors('action');

        $this->assertSame(
            $from,
            $ad->fresh()->status,
            "{$from->value} must not be reachable by [{$action}]."
        );
    }

    public function test_pending_can_never_be_published_straight_to_live(): void
    {
        $ad = $this->makeAd();

        $this->transition($this->approver, $ad, AdStatus::ACTION_PUBLISH)->assertSessionHasErrors('action');

        $this->assertSame(AdStatus::Pending, $ad->fresh()->status);
    }

    public function test_an_unknown_action_is_rejected_by_validation(): void
    {
        $ad = $this->makeAd();

        $this->transition($this->approver, $ad, 'make-it-live')->assertSessionHasErrors('action');

        $this->assertSame(AdStatus::Pending, $ad->fresh()->status);
    }

    public function test_a_rejection_note_is_recorded_in_the_activity_log(): void
    {
        $ad = $this->makeAd();

        $this->transition($this->approver, $ad, AdStatus::ACTION_REJECT, [
            'reason' => 'Logo is out of date.',
        ])->assertRedirect();

        $activity = Activity::query()
            ->where('subject_type', Ad::class)
            ->where('subject_id', $ad->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('reject', $activity->properties['action']);
        $this->assertSame('Logo is out of date.', $activity->properties['reason']);
        $this->assertSame('pending', $activity->properties['from']);
        $this->assertSame('rejected', $activity->properties['to']);
    }

    // ------------------------------------------------------- edit after approval

    public function test_replacing_the_creative_of_a_live_ad_requires_reapproval(): void
    {
        // The real route to live: reviewed, then published. Only `approve` writes the
        // approval trail, so this is what a genuine approved_at looks like.
        $ad = $this->makeAd(['status' => AdStatus::Pending]);
        $this->transition($this->approver, $ad, AdStatus::ACTION_APPROVE);
        $this->transition($this->approver, $ad, AdStatus::ACTION_PUBLISH);
        $ad->refresh();

        $this->assertSame(AdStatus::Active, $ad->status);
        $this->assertNotNull($ad->approved_at);
        $this->assertSame($this->approver->id, $ad->approved_by_admin_id);

        $this->actingAs($this->editor, 'admin')->put(
            route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]),
            $this->editPayload($ad, ['creative' => UploadedFile::fake()->image('fresh.jpg')->size(30)])
        )->assertRedirect();

        $ad->refresh();

        $this->assertSame(AdStatus::Pending, $ad->status, 'A new creative must be reviewed again.');
        $this->assertNull($ad->approved_by_admin_id, 'The stale approval trail must be cleared.');
        $this->assertNull($ad->approved_at);
    }

    public function test_changing_the_duration_of_an_approved_ad_requires_reapproval(): void
    {
        $ad = $this->makeAd(['status' => AdStatus::Approved]);

        $this->actingAs($this->editor, 'admin')->put(
            route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]),
            $this->editPayload($ad, ['duration_seconds' => 99])
        )->assertRedirect();

        $this->assertSame(AdStatus::Pending, $ad->fresh()->status);
    }

    public function test_changing_the_validity_window_of_a_live_ad_requires_reapproval(): void
    {
        $ad = $this->makeAd(['status' => AdStatus::Active]);

        $this->actingAs($this->editor, 'admin')->put(
            route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]),
            $this->editPayload($ad, ['end_date' => now()->addMonth()->format('Y-m-d')])
        )->assertRedirect();

        $this->assertSame(AdStatus::Pending, $ad->fresh()->status);
    }

    public function test_a_title_only_edit_preserves_approval(): void
    {
        $ad = $this->makeAd(['status' => AdStatus::Active]);

        $this->actingAs($this->editor, 'admin')->put(
            route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]),
            $this->editPayload($ad, [
                'title' => ['en' => 'Rewritten headline'],
                'description' => ['en' => 'Rewritten body'],
            ])
        )->assertRedirect();

        $ad->refresh();

        $this->assertSame(
            AdStatus::Active,
            $ad->status,
            'Title and description never reach a screen, so approval must hold.'
        );
        $this->assertSame('Rewritten headline', $ad->getTranslation('title', 'en'));
    }

    public function test_resaving_identical_playback_values_preserves_approval(): void
    {
        $ad = $this->makeAd(['status' => AdStatus::Active]);

        $this->actingAs($this->editor, 'admin')->put(
            route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]),
            $this->editPayload($ad)
        )->assertRedirect();

        $this->assertSame(
            AdStatus::Active,
            $ad->fresh()->status,
            'Saving the same values is not a change and must not trigger review.'
        );
    }

    public function test_a_pending_ad_edit_does_not_change_its_status(): void
    {
        $ad = $this->makeAd(['status' => AdStatus::Pending]);

        $this->actingAs($this->editor, 'admin')->put(
            route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]),
            $this->editPayload($ad, ['duration_seconds' => 55])
        )->assertRedirect();

        $this->assertSame(AdStatus::Pending, $ad->fresh()->status);
    }

    /**
     * PART 23 — approval covers the creative, not where or when it runs.
     */
    public function test_assignment_and_schedule_changes_never_revoke_approval(): void
    {
        $screen = Screen::factory()->create([
            'place_id' => Place::factory()->create()->id,
            'code' => 'SCR-APPROVAL',
            'status' => ScreenStatus::Online->value,
        ]);

        $ad = $this->makeAd(['status' => AdStatus::Active]);

        // Assignment, through the ad form.
        $this->actingAs($this->editor, 'admin')->put(
            route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]),
            $this->editPayload($ad, [
                'screens' => [$screen->id],
                'play_order' => [$screen->id => 3],
            ])
        )->assertRedirect();

        $this->assertSame(AdStatus::Active, $ad->fresh()->status, 'Assignment must not revoke approval.');

        // And a schedule write.
        $this->approver->givePermissionTo(Permission::findOrCreate('ads.schedule', 'admin'));

        $this->actingAs($this->approver, 'admin')->post(
            route('admin.ads.schedules.store', ['lang' => 'en', 'ad' => $ad->id]),
            [
                'screen_id' => $screen->id,
                'start_time' => now()->addHour()->format('Y-m-d\TH:i'),
                'end_time' => now()->addHours(3)->format('Y-m-d\TH:i'),
                'is_active' => '1',
            ]
        )->assertRedirect();

        $this->assertSame(AdStatus::Active, $ad->fresh()->status, 'Scheduling must not revoke approval.');
    }

    // ------------------------------------------------------- playlist integration

    private function assignedScreen(Ad $ad): Screen
    {
        $screen = Screen::factory()->create([
            'place_id' => Place::factory()->create()->id,
            'code' => 'SCR-'.$ad->id.'-PL',
            'status' => ScreenStatus::Online->value,
        ]);

        $screen->ads()->attach($ad->id, ['play_order' => 1]);

        AdSchedule::create([
            'ad_id' => $ad->id,
            'screen_id' => $screen->id,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(4),
            'is_active' => true,
        ]);

        return $screen;
    }

    /**
     * @return array<int, int|null>
     */
    private function playlistAdIds(Screen $screen): array
    {
        return collect(app(AdSchedulerService::class)->forScreen($screen->fresh())['items'])
            ->pluck('ad_id')
            ->all();
    }

    public function test_an_unapproved_ad_never_reaches_the_playlist(): void
    {
        config(['ads.fallback' => null]);

        $ad = $this->makeAd(['status' => AdStatus::Pending]);
        $screen = $this->assignedScreen($ad);

        // Scheduling and assignment are both satisfied; only review is missing.
        $this->assertSame([], $this->playlistAdIds($screen));

        $this->transition($this->approver, $ad, AdStatus::ACTION_APPROVE);

        $this->assertSame(
            [],
            $this->playlistAdIds($screen),
            'Approved is cleared for broadcast, not broadcasting: only Active plays.'
        );
    }

    public function test_publishing_puts_the_ad_on_screen_immediately(): void
    {
        config(['ads.fallback' => null]);

        $ad = $this->makeAd(['status' => AdStatus::Approved]);
        $screen = $this->assignedScreen($ad);

        // Warm the cache with the ad absent.
        $this->assertSame([], $this->playlistAdIds($screen));
        $this->assertTrue(Cache::has(AdSchedulerService::cacheKeyFor($screen)));

        $this->transition($this->approver, $ad, AdStatus::ACTION_PUBLISH)->assertRedirect();

        $this->assertFalse(
            Cache::has(AdSchedulerService::cacheKeyFor($screen)),
            'Publishing must invalidate the affected playlist through AdObserver.'
        );
        $this->assertSame([$ad->id], $this->playlistAdIds($screen));
    }

    public function test_taking_a_live_ad_off_air_removes_it_immediately(): void
    {
        config(['ads.fallback' => null]);

        $ad = $this->makeAd(['status' => AdStatus::Active]);
        $screen = $this->assignedScreen($ad);

        $this->assertSame([$ad->id], $this->playlistAdIds($screen));

        $this->transition($this->approver, $ad, AdStatus::ACTION_UNPUBLISH)->assertRedirect();

        $this->assertFalse(Cache::has(AdSchedulerService::cacheKeyFor($screen)));
        $this->assertSame(
            [],
            $this->playlistAdIds($screen),
            'A takedown must not wait for the cache TTL.'
        );
    }

    public function test_rejecting_a_live_ad_removes_it_immediately(): void
    {
        config(['ads.fallback' => null]);

        $ad = $this->makeAd(['status' => AdStatus::Active]);
        $screen = $this->assignedScreen($ad);

        $this->assertSame([$ad->id], $this->playlistAdIds($screen));

        $this->transition($this->approver, $ad, AdStatus::ACTION_REJECT)->assertRedirect();

        $this->assertSame([], $this->playlistAdIds($screen));
    }

    public function test_a_creative_edit_that_forces_reapproval_also_takes_the_ad_off_air(): void
    {
        config(['ads.fallback' => null]);

        $ad = $this->makeAd(['status' => AdStatus::Active]);
        $screen = $this->assignedScreen($ad);

        $this->assertSame([$ad->id], $this->playlistAdIds($screen));

        $this->actingAs($this->editor, 'admin')->put(
            route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]),
            $this->editPayload($ad, ['creative' => UploadedFile::fake()->image('swap.jpg')->size(30)])
        )->assertRedirect();

        $this->assertSame(
            [],
            $this->playlistAdIds($screen),
            'An unreviewed creative must stop playing the moment it is uploaded.'
        );
    }
}
