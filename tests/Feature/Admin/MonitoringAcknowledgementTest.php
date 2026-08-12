<?php

namespace Tests\Feature\Admin;

use App\Enums\ScreenStatus;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Screen;
use App\Models\ScreenLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Monitoring acknowledgement means one thing: an administrator has seen an alert.
 *
 * Phase 11 separated that from connectivity. Acknowledging used to write
 * `screens.status` from a dropdown AND stamp `screens.last_heartbeat = now()`,
 * so a dead screen reported as healthy the instant somebody clicked the button —
 * and the stamp destroyed the evidence of how long it had been dead.
 *
 * The tests below pin the new contract. The old known-defect test that asserted
 * the heartbeat WAS stamped is replaced by its inverse, because the defect it
 * described is fixed rather than merely documented.
 */
class MonitoringAcknowledgementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['monitoring.view', 'monitoring.manage'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Ack',
            'last_name' => 'Tester',
            'email' => 'ack-tester@example.com',
            'password' => 'password',
            'mobile' => '7000000001',
        ]);

        $this->admin->givePermissionTo(['monitoring.view', 'monitoring.manage']);
    }

    protected function makeScreen(): Screen
    {
        return Screen::factory()->create([
            'place_id' => Place::factory()->create()->id,
            'code' => 'SCR-ACK-'.fake()->unique()->bothify('####'),
            'status' => ScreenStatus::Offline->value,
            'last_heartbeat' => now()->subDays(3),
        ]);
    }

    /**
     * The offline event an administrator would be acknowledging.
     */
    protected function raiseAlert(Screen $screen): ScreenLog
    {
        return $screen->logs()->create([
            'status' => ScreenStatus::Offline->value,
            'reported_at' => now()->subHours(2),
        ]);
    }

    protected function acknowledge(Screen $screen, string $locale = 'en', array $payload = [])
    {
        return $this->actingAs($this->admin, 'admin')->post(
            route('admin.monitoring.screens.acknowledge', ['lang' => $locale, 'screen' => $screen->id]),
            $payload
        );
    }

    public static function localeProvider(): array
    {
        return ['english' => ['en'], 'arabic' => ['ar']];
    }

    #[DataProvider('localeProvider')]
    public function test_acknowledging_records_who_saw_the_alert_and_when(string $locale): void
    {
        $screen = $this->makeScreen();
        $alert = $this->raiseAlert($screen);

        $response = $this->acknowledge($screen, $locale, ['note' => 'Technician dispatched']);

        $response->assertRedirect(
            route('admin.monitoring.screens.show', ['lang' => $locale, 'screen' => $screen->id])
        );
        $response->assertSessionHas('success');

        $alert->refresh();
        $this->assertNotNull($alert->acknowledged_at);
        $this->assertSame($this->admin->id, $alert->acknowledged_by);
        $this->assertSame('Technician dispatched', $alert->acknowledgement_note);
        $this->assertSame($this->admin->id, $alert->acknowledger->id);
    }

    public function test_acknowledging_never_touches_last_heartbeat(): void
    {
        $screen = $this->makeScreen();
        $this->raiseAlert($screen);

        $before = $screen->last_heartbeat;

        $this->acknowledge($screen, 'en', ['note' => 'Seen it']);

        $this->assertTrue(
            $screen->fresh()->last_heartbeat->equalTo($before),
            'Acknowledging an alert is not device contact and must never restamp last_heartbeat.'
        );
    }

    public function test_acknowledging_cannot_make_a_dead_screen_look_online(): void
    {
        $screen = $this->makeScreen();
        $this->raiseAlert($screen);

        $this->acknowledge($screen, 'en', ['note' => 'Acknowledged, still dead']);

        $this->assertSame(
            ScreenStatus::Offline,
            $screen->fresh()->status,
            'The dashboard must never claim a screen is online because an administrator clicked a button.'
        );
    }

    public function test_acknowledging_does_not_write_a_new_screen_log(): void
    {
        $screen = $this->makeScreen();
        $this->raiseAlert($screen);

        $this->acknowledge($screen, 'en');

        // The original offline event is annotated, not duplicated — and not erased.
        $logs = $screen->logs()->get();
        $this->assertCount(1, $logs);
        $this->assertSame(ScreenStatus::Offline, $logs->first()->status);
    }

    public function test_a_status_submitted_by_a_client_is_ignored_entirely(): void
    {
        $screen = $this->makeScreen();
        $this->raiseAlert($screen);

        // `status` was a real field before Phase 11. A stale client, a bookmarked
        // form or a hand-crafted POST must not be able to resurrect it.
        $this->acknowledge($screen, 'en', ['status' => 'online', 'note' => 'Forged']);

        $screen->refresh();
        $this->assertSame(ScreenStatus::Offline, $screen->status);
        $this->assertTrue($screen->last_heartbeat->lt(now()->subDays(2)));
    }

    public function test_acknowledging_twice_leaves_the_first_acknowledgement_intact(): void
    {
        $screen = $this->makeScreen();
        $alert = $this->raiseAlert($screen);

        $this->acknowledge($screen, 'en', ['note' => 'First']);
        $firstAcknowledgedAt = $alert->fresh()->acknowledged_at;

        // The alert is closed now, so a second submission has nothing to act on.
        $this->acknowledge($screen, 'en', ['note' => 'Second'])
            ->assertSessionHas('warning');

        $alert->refresh();
        $this->assertSame('First', $alert->acknowledgement_note);
        $this->assertTrue($alert->acknowledged_at->equalTo($firstAcknowledgedAt));
    }

    public function test_acknowledging_with_no_open_alert_changes_nothing(): void
    {
        $screen = $this->makeScreen();

        $this->acknowledge($screen, 'en', ['note' => 'Nothing to see'])
            ->assertSessionHas('warning');

        $this->assertSame(0, $screen->logs()->whereNotNull('acknowledged_at')->count());
        $this->assertSame(ScreenStatus::Offline, $screen->fresh()->status);
    }

    public function test_the_note_is_length_limited(): void
    {
        $screen = $this->makeScreen();
        $alert = $this->raiseAlert($screen);

        $this->acknowledge($screen, 'en', ['note' => str_repeat('x', 501)])
            ->assertSessionHasErrors('note');

        $this->assertNull($alert->fresh()->acknowledged_at);
    }

    public function test_screen_logs_accepts_every_authoritative_screen_status(): void
    {
        $screen = $this->makeScreen();

        foreach (ScreenStatus::cases() as $case) {
            $log = ScreenLog::create([
                'screen_id' => $screen->id,
                'status' => $case->value,
                'reported_at' => now(),
            ]);

            $this->assertSame($case->value, $log->fresh()->status->value);
        }

        $this->assertSame(count(ScreenStatus::cases()), $screen->logs()->count());
    }

    public function test_acknowledgement_requires_the_manage_permission(): void
    {
        $screen = $this->makeScreen();
        $alert = $this->raiseAlert($screen);

        $viewer = Admin::create([
            'first_name' => 'View',
            'last_name' => 'Only',
            'email' => 'view-only-ack@example.com',
            'password' => 'password',
            'mobile' => '7000000002',
        ]);
        $viewer->givePermissionTo('monitoring.view');

        $this->actingAs($viewer, 'admin')
            ->post(
                route('admin.monitoring.screens.acknowledge', ['lang' => 'en', 'screen' => $screen->id]),
                ['note' => 'Not allowed']
            )
            ->assertForbidden();

        $this->assertNull($alert->fresh()->acknowledged_at);
        $this->assertSame(ScreenStatus::Offline, $screen->fresh()->status);
    }
}
