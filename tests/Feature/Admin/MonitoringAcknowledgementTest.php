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
 * Pre-Phase 8 — locks the Monitoring acknowledgement path against the defect
 * reproduced in Phase 7: `screen_logs.status` was enum('online','offline') while
 * AcknowledgeAlertRequest accepts `maintenance`, so acknowledging a screen as
 * under maintenance died with an integrity-constraint violation (HTTP 500).
 *
 * NOTE — deliberately NOT changed here: acknowledging also sets
 * `last_heartbeat = now()` without any device contact. That is asserted below so
 * the behaviour is pinned, but it remains a known defect deferred to the Digital
 * Signage functional stabilization phase.
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
            'code' => 'SCR-ACK-' . fake()->unique()->bothify('####'),
            'status' => ScreenStatus::Offline->value,
            'last_heartbeat' => now()->subDays(3),
        ]);
    }

    public static function acknowledgeableStatusProvider(): array
    {
        return [
            'online in english' => ['online', 'en'],
            'online in arabic' => ['online', 'ar'],
            'maintenance in english' => ['maintenance', 'en'],
            'maintenance in arabic' => ['maintenance', 'ar'],
        ];
    }

    #[DataProvider('acknowledgeableStatusProvider')]
    public function test_acknowledging_stores_the_status_and_writes_a_screen_log(string $status, string $locale): void
    {
        $screen = $this->makeScreen();

        $response = $this->actingAs($this->admin, 'admin')->post(
            route('admin.monitoring.screens.acknowledge', ['lang' => $locale, 'screen' => $screen->id]),
            ['status' => $status, 'note' => 'Technician dispatched']
        );

        // No HTTP 500 — the request completes and redirects to the detail page.
        $response->assertRedirect(
            route('admin.monitoring.screens.show', ['lang' => $locale, 'screen' => $screen->id])
        );
        $response->assertSessionHas('success');

        // The screen carries the acknowledged status.
        $this->assertSame($status, $screen->fresh()->status->value);

        // Exactly one screen log is written, with the same status.
        $logs = $screen->logs()->get();
        $this->assertCount(1, $logs);
        $this->assertSame($status, $logs->first()->status->value);
        $this->assertNotNull($logs->first()->reported_at);
        $this->assertDatabaseHas('screen_logs', [
            'screen_id' => $screen->id,
            'status' => $status,
        ]);
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

    public function test_maintenance_acknowledgement_no_longer_throws(): void
    {
        $screen = $this->makeScreen();

        // withoutExceptionHandling() turns the previous QueryException into a
        // test failure instead of a rendered 500 page.
        $this->withoutExceptionHandling()
            ->actingAs($this->admin, 'admin')
            ->post(
                route('admin.monitoring.screens.acknowledge', ['lang' => 'en', 'screen' => $screen->id]),
                ['status' => 'maintenance']
            )
            ->assertRedirect();

        $this->assertSame('maintenance', $screen->fresh()->status->value);
    }

    public function test_acknowledgement_rejects_a_status_outside_the_allowed_set(): void
    {
        $screen = $this->makeScreen();

        $this->actingAs($this->admin, 'admin')
            ->post(
                route('admin.monitoring.screens.acknowledge', ['lang' => 'en', 'screen' => $screen->id]),
                ['status' => 'offline']
            )
            ->assertSessionHasErrors('status');

        // Nothing was written.
        $this->assertSame('offline', $screen->fresh()->status->value);
        $this->assertSame(0, $screen->logs()->count());
    }

    public function test_acknowledgement_requires_the_manage_permission(): void
    {
        $screen = $this->makeScreen();

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
                ['status' => 'maintenance']
            )
            ->assertForbidden();

        $this->assertSame('offline', $screen->fresh()->status->value);
        $this->assertSame(0, $screen->logs()->count());
    }

    /**
     * Pins the KNOWN DEFECT so a future fix is a deliberate, visible change.
     * Acknowledging stamps last_heartbeat even though no device reported in.
     */
    public function test_known_defect_acknowledgement_still_stamps_last_heartbeat(): void
    {
        $screen = $this->makeScreen();
        $staleHeartbeat = $screen->last_heartbeat;

        $this->actingAs($this->admin, 'admin')->post(
            route('admin.monitoring.screens.acknowledge', ['lang' => 'en', 'screen' => $screen->id]),
            ['status' => 'maintenance']
        );

        $screen->refresh();

        $this->assertTrue(
            $screen->last_heartbeat->isAfter($staleHeartbeat),
            'Deferred defect: acknowledging rewrites last_heartbeat without device contact.'
        );
    }
}
