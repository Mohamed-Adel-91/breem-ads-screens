<?php

namespace Tests\Feature\Admin;

use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Screen;
use App\Services\Screen\DevicePairingService;
use App\Support\ScreenHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The admin Screen form cannot forge operational state.
 *
 * Phase 5 shipped `last_heartbeat` as an editable datetime-local input and
 * `status` as a free dropdown, so an administrator could declare a dead screen
 * online and backdate or blank its heartbeat. Phase 10 made `device_uid` an
 * identity rather than a credential; a routine edit must not silently reassign
 * it out from under a paired device.
 */
class ScreenAdminHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['screens.view', 'screens.create', 'screens.edit'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Screen',
            'last_name' => 'Editor',
            'email' => 'screen-editor@example.com',
            'password' => 'password',
            'mobile' => '7200000001',
        ]);
        $this->admin->givePermissionTo(['screens.view', 'screens.create', 'screens.edit']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function place(): Place
    {
        return Place::firstOrCreate(
            ['type' => PlaceType::Other],
            ['name' => ['en' => 'Edit Hall'], 'address' => ['en' => '5 Form Street']]
        );
    }

    private function makeScreen(array $overrides = []): Screen
    {
        return Screen::create(array_merge([
            'place_id' => $this->place()->id,
            'code' => 'SCR-EDIT-'.fake()->unique()->bothify('####'),
            'device_uid' => 'uid-'.fake()->unique()->bothify('########'),
            'status' => ScreenStatus::Offline,
            'last_heartbeat' => null,
        ], $overrides));
    }

    private function update(Screen $screen, array $payload)
    {
        return $this->actingAs($this->admin, 'admin')->put(
            route('admin.screens.update', ['lang' => 'en', 'screen' => $screen->id]),
            array_merge([
                'place_id' => $screen->place_id,
                'code' => $screen->code,
                'device_uid' => $screen->device_uid,
                'status' => $screen->status->value,
            ], $payload)
        );
    }

    // ------------------------------------------------------- last_heartbeat

    public function test_last_heartbeat_cannot_be_forged_through_an_update(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 6, 1, 12, 0, 0));

        $screen = $this->makeScreen();
        $screen->forceFill(['last_heartbeat' => $now->copy()->subDays(4)])->save();
        $real = $screen->last_heartbeat;

        $this->update($screen, ['last_heartbeat' => $now->copy()->toDateTimeString()])
            ->assertRedirect();

        $this->assertTrue(
            $screen->fresh()->last_heartbeat->equalTo($real),
            'An administrator must not be able to stamp connectivity freshness.'
        );
    }

    public function test_omitting_last_heartbeat_does_not_blank_a_live_screen(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 6, 1, 12, 0, 0));

        // The old update() wrote `last_heartbeat => null` whenever the field was
        // absent, so renaming a screen wiped its operational history.
        $screen = $this->makeScreen(['status' => ScreenStatus::Online]);
        $screen->forceFill(['last_heartbeat' => $now->copy()->subSeconds(10)])->save();

        $this->update($screen, ['code' => 'SCR-RENAMED'])->assertRedirect();

        $screen->refresh();
        $this->assertSame('SCR-RENAMED', $screen->code);
        $this->assertNotNull($screen->last_heartbeat);
        $this->assertTrue($screen->last_heartbeat->equalTo($now->copy()->subSeconds(10)));
    }

    public function test_a_created_screen_has_no_heartbeat_whatever_the_form_says(): void
    {
        $this->actingAs($this->admin, 'admin')->post(
            route('admin.screens.store', ['lang' => 'en']),
            [
                'place_id' => $this->place()->id,
                'code' => 'SCR-NEW-1',
                'device_uid' => 'uid-new-1',
                'status' => 'online',
                'last_heartbeat' => now()->toDateTimeString(),
            ]
        )->assertRedirect();

        $screen = Screen::where('code', 'SCR-NEW-1')->firstOrFail();

        $this->assertNull($screen->last_heartbeat);
        $this->assertSame(
            ScreenStatus::Offline,
            $screen->status,
            'A screen that has never been heard from cannot be created online.'
        );
    }

    // -------------------------------------------------------------- status

    public function test_an_administrator_cannot_declare_a_silent_screen_online(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 6, 1, 12, 0, 0));

        $screen = $this->makeScreen(['status' => ScreenStatus::Offline]);
        $screen->forceFill([
            'last_heartbeat' => $now->copy()->subSeconds(ScreenHealth::offlineAfter() + 60),
        ])->save();

        $this->update($screen, ['status' => 'online'])->assertRedirect();

        $this->assertSame(
            ScreenStatus::Offline,
            $screen->fresh()->status,
            'The dashboard must never claim a screen is online merely because an administrator chose it.'
        );
    }

    public function test_an_administrator_may_place_a_screen_into_maintenance(): void
    {
        $screen = $this->makeScreen(['status' => ScreenStatus::Online]);

        $this->update($screen, ['status' => 'maintenance'])->assertRedirect();

        $this->assertSame(ScreenStatus::Maintenance, $screen->fresh()->status);
    }

    public function test_leaving_maintenance_returns_the_screen_to_evidence_based_status(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 6, 1, 12, 0, 0));

        $live = $this->makeScreen(['status' => ScreenStatus::Maintenance]);
        $live->forceFill(['last_heartbeat' => $now->copy()->subSeconds(5)])->save();

        $dead = $this->makeScreen(['status' => ScreenStatus::Maintenance]);
        $dead->forceFill(['last_heartbeat' => $now->copy()->subDays(2)])->save();

        $this->update($live->fresh(), ['status' => 'online'])->assertRedirect();
        $this->update($dead->fresh(), ['status' => 'online'])->assertRedirect();

        // The heartbeat decides, not the dropdown.
        $this->assertSame(ScreenStatus::Online, $live->fresh()->status);
        $this->assertSame(ScreenStatus::Offline, $dead->fresh()->status);
    }

    // ----------------------------------------------------------- device_uid

    public function test_a_paired_device_uid_cannot_be_overwritten_by_a_routine_edit(): void
    {
        $screen = $this->makeScreen(['device_uid' => 'legitimate-device']);
        app(DevicePairingService::class)->issueCredential($screen, 'legitimate-device');

        $this->update($screen->fresh(), ['device_uid' => 'someone-elses-device'])->assertRedirect();

        $this->assertSame(
            'legitimate-device',
            $screen->fresh()->device_uid,
            'Re-pairing is the authoritative way to change which hardware a screen is.'
        );
    }

    public function test_an_unpaired_screen_still_accepts_a_device_uid(): void
    {
        $screen = $this->makeScreen(['device_uid' => 'placeholder-uid']);

        $this->update($screen, ['device_uid' => 'corrected-uid'])->assertRedirect();

        $this->assertSame('corrected-uid', $screen->fresh()->device_uid);
    }

    public function test_the_edit_form_shows_the_heartbeat_without_offering_to_edit_it(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 12, 0, 0));

        $screen = $this->makeScreen();
        $screen->forceFill(['last_heartbeat' => now()->subHour()])->save();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.screens.edit', ['lang' => 'en', 'screen' => $screen->id]));

        $response->assertOk();
        $response->assertDontSee('name="last_heartbeat"', false);
        // Still visible as read-only evidence.
        $response->assertSee('2026-06-01 11:00', false);
    }
}
