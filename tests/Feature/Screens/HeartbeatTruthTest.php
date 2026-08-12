<?php

namespace Tests\Feature\Screens;

use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Place;
use App\Models\Screen;
use App\Support\DeviceSignature;
use App\Support\ScreenHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Concerns\SignsScreenRequests;
use Tests\TestCase;

/**
 * `screens.last_heartbeat` is server-owned.
 *
 * Every test here goes through the real signed Device API rather than calling
 * the service directly, so the Phase 10 guarantees (bearer token, per-device
 * HMAC, timestamp, nonce, replay protection) are exercised alongside the
 * Phase 11 heartbeat semantics — a heartbeat that cannot authenticate must not
 * be able to move operational state at all.
 */
class HeartbeatTruthTest extends TestCase
{
    use RefreshDatabase;
    use SignsScreenRequests;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeScreen(array $overrides = []): Screen
    {
        $place = Place::create([
            'name' => ['en' => 'Heartbeat Hall'],
            'address' => ['en' => '1 Pulse Street'],
            'type' => PlaceType::Other,
        ]);

        return Screen::create(array_merge([
            'place_id' => $place->id,
            'code' => 'screen-'.Str::random(8),
            'device_uid' => 'device-'.Str::random(8),
            'status' => ScreenStatus::Offline,
            'last_heartbeat' => null,
        ], $overrides));
    }

    public function test_a_valid_heartbeat_stamps_server_receipt_time(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 3, 1, 12, 0, 0));

        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds)->assertOk();

        $this->assertTrue($screen->fresh()->last_heartbeat->equalTo($now));
    }

    public function test_a_stale_client_reported_at_does_not_make_last_heartbeat_stale(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 3, 1, 12, 0, 0));

        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->devicePost('/api/v1/screens/heartbeat', [
            'status' => 'online',
            'reported_at' => $now->copy()->subDays(30)->toIso8601String(),
        ], $creds)->assertOk();

        $screen->refresh();
        $this->assertTrue($screen->last_heartbeat->equalTo($now));
        $this->assertFalse(ScreenHealth::isStale($screen->last_heartbeat));
    }

    public function test_a_future_client_reported_at_does_not_make_last_heartbeat_future(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 3, 1, 12, 0, 0));

        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->devicePost('/api/v1/screens/heartbeat', [
            'status' => 'online',
            'reported_at' => $now->copy()->addYear()->toIso8601String(),
        ], $creds)->assertOk();

        $screen->refresh();
        $this->assertTrue($screen->last_heartbeat->equalTo($now));
        $this->assertFalse($screen->last_heartbeat->isFuture());
    }

    public function test_a_valid_heartbeat_recovers_an_offline_screen_without_admin_intervention(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 3, 1, 12, 0, 0));

        $screen = $this->makeScreen([
            'status' => ScreenStatus::Offline,
            'last_heartbeat' => $now->copy()->subHours(6),
        ]);
        $creds = $this->pairScreen($screen);

        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds)->assertOk();

        $screen->refresh();
        $this->assertSame(ScreenStatus::Online, $screen->status);
        $this->assertTrue($screen->last_heartbeat->equalTo($now));

        // The recovery is recorded in the log stream.
        $this->assertSame(ScreenStatus::Online, $screen->logs()->latest('id')->first()->status);
    }

    public function test_an_unauthenticated_heartbeat_cannot_mutate_the_screen(): void
    {
        $screen = $this->makeScreen([
            'status' => ScreenStatus::Offline,
            'last_heartbeat' => now()->subDays(2),
        ]);
        $before = $screen->last_heartbeat;

        $this->postJson('/api/v1/screens/heartbeat', ['status' => 'online'])
            ->assertUnauthorized();

        $screen->refresh();
        $this->assertSame(ScreenStatus::Offline, $screen->status);
        $this->assertTrue($screen->last_heartbeat->equalTo($before));
        $this->assertSame(0, $screen->logs()->count());
    }

    public function test_a_replayed_heartbeat_cannot_mutate_the_screen(): void
    {
        Carbon::setTestNow($start = Carbon::create(2026, 3, 1, 12, 0, 0));

        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);
        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string) $start->timestamp;

        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds, [
            'nonce' => $nonce, 'timestamp' => $timestamp,
        ])->assertOk();

        $firstHeartbeat = $screen->fresh()->last_heartbeat;

        // Time moves on; the captured request is re-sent verbatim.
        Carbon::setTestNow($start->copy()->addSeconds(30));

        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds, [
            'nonce' => $nonce, 'timestamp' => $timestamp,
        ])->assertUnauthorized()->assertJsonPath('error', 'replayed_request');

        $screen->refresh();
        $this->assertTrue(
            $screen->last_heartbeat->equalTo($firstHeartbeat),
            'A replayed heartbeat must not refresh liveness.'
        );
        $this->assertSame(1, $screen->logs()->count());
    }

    public function test_a_heartbeat_with_a_forged_signature_cannot_mutate_the_screen(): void
    {
        $screen = $this->makeScreen(['status' => ScreenStatus::Offline]);
        $creds = $this->pairScreen($screen);

        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds, [
            'signature' => str_repeat('a', 64),
        ])->assertUnauthorized();

        $screen->refresh();
        $this->assertSame(ScreenStatus::Offline, $screen->status);
        $this->assertNull($screen->last_heartbeat);
    }

    public function test_a_device_cannot_report_a_heartbeat_for_another_screen(): void
    {
        $mine = $this->makeScreen();
        $theirs = $this->makeScreen(['status' => ScreenStatus::Offline]);
        $creds = $this->pairScreen($mine);

        // The heartbeat endpoint takes no screen reference at all — the
        // credential decides — so the only screen this can touch is its own.
        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds)->assertOk();

        $this->assertSame(ScreenStatus::Offline, $theirs->fresh()->status);
        $this->assertNull($theirs->fresh()->last_heartbeat);
    }

    /**
     * Pairing is itself a successful device communication, so marking the screen
     * online and stamping the heartbeat is evidence-based rather than assumed.
     * Crucially it is not a permanent claim: a device that pairs and then never
     * reports again is swept offline like any other silent screen.
     */
    public function test_pairing_marks_a_screen_online_but_does_not_exempt_it_from_the_sweep(): void
    {
        Carbon::setTestNow($now = Carbon::create(2026, 3, 1, 12, 0, 0));

        $screen = $this->makeScreen(['device_uid' => null, 'code' => 'PAIR-ONLINE']);
        $issued = app(\App\Services\Screen\DevicePairingService::class)->issuePairingCode($screen);

        $this->postJson('/api/v1/screens/handshake', [
            'code' => 'PAIR-ONLINE',
            'pairing_code' => $issued['code'],
            'device' => ['uid' => 'fresh-device'],
        ])->assertCreated();

        $screen->refresh();
        $this->assertSame(ScreenStatus::Online, $screen->status);
        $this->assertTrue($screen->last_heartbeat->equalTo($now));

        // The device is then installed badly and never heartbeats.
        Carbon::setTestNow($now->copy()->addSeconds(ScreenHealth::offlineAfter() + 60));
        app(\App\Jobs\CheckScreenHealthJob::class)->handle(app(\App\Services\Screen\HeartbeatService::class));

        $this->assertSame(
            ScreenStatus::Offline,
            $screen->fresh()->status,
            'Credential issuance is not a substitute for continuous connectivity.'
        );
    }

    public function test_the_signing_protocol_is_unchanged_by_this_phase(): void
    {
        // Phase 11 touched heartbeat semantics, never the transport.
        $this->assertSame('X-Screen-Timestamp', DeviceSignature::TIMESTAMP_HEADER);
        $this->assertSame('X-Screen-Nonce', DeviceSignature::NONCE_HEADER);
        $this->assertSame('X-Screen-Signature', DeviceSignature::SIGNATURE_HEADER);
    }
}
