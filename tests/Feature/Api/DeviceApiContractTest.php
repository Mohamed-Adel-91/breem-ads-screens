<?php

namespace Tests\Feature\Api;

use App\Enums\AdStatus;
use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Ad;
use App\Models\Place;
use App\Models\Screen;
use App\Models\ScreenLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SignsScreenRequests;
use Tests\TestCase;

/**
 * Phase 9 — pins the Device API contract as it exists today, now that the routes
 * are reachable for the first time.
 *
 * This documents current behaviour. It deliberately does NOT assert a hardened
 * protocol: several assertions below record weaknesses (a device UID alone is
 * accepted as authentication, pairing can reassign a screen, a signature is
 * replayable). Those are named as known findings so the Phase 10 hardening work
 * has to change them consciously.
 *
 * Every fixture is isolated in the in-memory test database; no real screen,
 * device UID, heartbeat or pairing is touched.
 */
class DeviceApiContractTest extends TestCase
{
    use RefreshDatabase;
    use SignsScreenRequests;

    private function makeScreen(array $overrides = []): Screen
    {
        $place = Place::create([
            'name' => ['en' => 'Contract Hall'],
            'address' => ['en' => '1 Contract Way'],
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

    private function makeActiveAd(Screen $screen): Ad
    {
        $ad = Ad::create([
            'title' => ['en' => 'Contract Ad'],
            'file_path' => 'upload/ads/contract.mp4',
            'file_type' => 'video',
            'duration_seconds' => 15,
            'status' => AdStatus::Active,
            'created_by' => User::factory()->create()->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $screen->ads()->attach($ad->id, ['play_order' => 1]);

        return $ad;
    }

    // ---------------------------------------------------------------- handshake

    public function test_handshake_pairs_a_screen_by_code_and_returns_its_config(): void
    {
        $screen = $this->makeScreen(['code' => 'PAIR-ME', 'device_uid' => null]);

        [$body, $headers] = $this->signedJsonBody([
            'code' => 'PAIR-ME',
            'timestamp' => now()->timestamp,
            'device' => ['uid' => 'brand-new-device', 'model' => 'BX-1'],
        ]);

        $response = $this->call('POST', '/api/v1/screens/handshake', [], [], [], $this->transformHeadersToServerVars($headers), $body);

        $response->assertOk();
        $response->assertJsonPath('data.screen.code', 'PAIR-ME');
        $response->assertJsonPath('data.auth.device_uid', 'brand-new-device');
        $response->assertJsonStructure([
            'data' => ['screen', 'config' => ['heartbeat_interval', 'playlist_ttl', 'timezone'], 'auth' => ['device_uid', 'bearer_token'], 'meta'],
        ]);

        $screen->refresh();
        $this->assertSame('brand-new-device', $screen->device_uid);
        $this->assertSame(ScreenStatus::Online, $screen->status);
        $this->assertNotNull($screen->last_heartbeat);
    }

    public function test_handshake_rejects_an_unknown_screen_code(): void
    {
        [$body, $headers] = $this->signedJsonBody([
            'code' => 'NO-SUCH-SCREEN',
            'timestamp' => now()->timestamp,
            'device' => ['uid' => 'someone'],
        ]);

        $this->call('POST', '/api/v1/screens/handshake', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertNotFound();
    }

    /**
     * KNOWN FINDING (CRITICAL) — pairing is not authenticated beyond the shared
     * HMAC secret, so anyone able to sign can seize an already-paired screen by
     * quoting its code. Pinned so hardening has to change it deliberately.
     */
    public function test_known_finding_handshake_reassigns_an_already_paired_screen(): void
    {
        $screen = $this->makeScreen(['code' => 'TAKE-OVER', 'device_uid' => 'legitimate-device']);

        [$body, $headers] = $this->signedJsonBody([
            'code' => 'TAKE-OVER',
            'timestamp' => now()->timestamp,
            'device' => ['uid' => 'attacker-device'],
        ]);

        $this->call('POST', '/api/v1/screens/handshake', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk();

        $this->assertSame(
            'attacker-device',
            $screen->fresh()->device_uid,
            'Deferred defect: a second handshake silently re-pairs the screen.'
        );
    }

    // ----------------------------------------------------------- authentication

    public function test_an_unsigned_request_is_rejected_when_a_secret_is_configured(): void
    {
        $screen = $this->makeScreen();

        $this->withHeader('X-Screen-Uid', $screen->device_uid)
            ->getJson(route('api.v1.screens.playlist', ['screen' => $screen->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('signature');
    }

    public function test_a_forged_signature_is_rejected(): void
    {
        $screen = $this->makeScreen();
        $url = route('api.v1.screens.playlist', ['screen' => $screen->id]);

        $this->withHeaders([
            'X-Screen-Uid' => $screen->device_uid,
            'X-Screen-Signature' => str_repeat('a', 64),
        ])->getJson($url)->assertStatus(422)->assertJsonValidationErrors('signature');
    }

    public function test_an_unknown_device_uid_header_is_rejected_by_the_auth_middleware(): void
    {
        $screen = $this->makeScreen();
        $url = route('api.v1.screens.playlist', ['screen' => $screen->id]);

        $this->withHeaders($this->signedGetHeaders($url, 'not-a-real-device'))
            ->getJson($url)
            ->assertStatus(401);
    }

    /**
     * KNOWN FINDING (CRITICAL) — the device UID is a bearer-equivalent secret
     * transported in a header, and the returned "bearer_token" IS that same UID.
     * No token is ever validated. Pinned so hardening has to change it.
     */
    public function test_known_finding_the_bearer_token_is_just_the_device_uid(): void
    {
        $screen = $this->makeScreen(['code' => 'TOKEN-CHECK', 'device_uid' => null]);

        [$body, $headers] = $this->signedJsonBody([
            'code' => 'TOKEN-CHECK',
            'timestamp' => now()->timestamp,
            'device' => ['uid' => 'uid-doubles-as-token'],
        ]);

        $response = $this->call('POST', '/api/v1/screens/handshake', [], [], [], $this->transformHeadersToServerVars($headers), $body);

        $response->assertJsonPath('data.auth.bearer_token', 'uid-doubles-as-token');
        $response->assertJsonPath('data.auth.device_uid', 'uid-doubles-as-token');
    }

    // -------------------------------------------------------------- heartbeat

    public function test_heartbeat_updates_the_screen_and_writes_a_log(): void
    {
        $screen = $this->makeScreen();

        [$body, $headers] = $this->signedJsonBody([
            'device_uid' => $screen->device_uid,
            'timestamp' => now()->timestamp,
            'status' => 'online',
            'current_ad_code' => 'AD-9',
        ], $screen->device_uid);

        $response = $this->call('POST', '/api/v1/screens/heartbeat', [], [], [], $this->transformHeadersToServerVars($headers), $body);

        $response->assertOk();
        $response->assertJsonPath('data.screen.status', 'online');
        $response->assertJsonStructure(['data' => ['screen', 'log' => ['id', 'status', 'current_ad_code', 'reported_at'], 'next_heartbeat_at']]);

        $screen->refresh();
        $this->assertSame(ScreenStatus::Online, $screen->status);
        $this->assertNotNull($screen->last_heartbeat);
        $this->assertSame(1, $screen->logs()->count());
    }

    /**
     * The Pre-Phase-8 screen_logs enum widening must remain in place: a device
     * reporting `maintenance` used to violate the column's CHECK constraint.
     */
    public function test_heartbeat_accepts_the_maintenance_status(): void
    {
        $screen = $this->makeScreen();

        [$body, $headers] = $this->signedJsonBody([
            'device_uid' => $screen->device_uid,
            'timestamp' => now()->timestamp,
            'status' => 'maintenance',
        ], $screen->device_uid);

        $this->call('POST', '/api/v1/screens/heartbeat', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk();

        $this->assertSame(ScreenStatus::Maintenance, $screen->fresh()->status);
        $this->assertSame('maintenance', ScreenLog::first()->status->value);
    }

    public function test_heartbeat_requires_a_timestamp_inside_the_leeway_window(): void
    {
        $screen = $this->makeScreen();

        [$body, $headers] = $this->signedJsonBody([
            'device_uid' => $screen->device_uid,
            'timestamp' => now()->subHours(2)->timestamp,
            'status' => 'online',
        ], $screen->device_uid);

        $this->call('POST', '/api/v1/screens/heartbeat', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertStatus(422)
            ->assertJsonValidationErrors('timestamp');
    }

    // --------------------------------------------------------------- playlist

    public function test_playlist_returns_items_and_an_etag_and_honours_if_none_match(): void
    {
        $screen = $this->makeScreen();
        $this->makeActiveAd($screen);

        $url = route('api.v1.screens.playlist', ['screen' => $screen->id]);

        $first = $this->withHeaders($this->signedGetHeaders($url, $screen->device_uid))->getJson($url);
        $first->assertOk();
        $etag = trim((string) $first->headers->get('ETag'), '"');
        $this->assertNotSame('', $etag);

        $second = $this->withHeaders(array_merge(
            $this->signedGetHeaders($url, $screen->device_uid),
            ['If-None-Match' => $etag]
        ))->getJson($url);

        $second->assertStatus(304);
    }

    public function test_playlist_can_be_addressed_by_screen_code_as_well_as_id(): void
    {
        $screen = $this->makeScreen();
        $this->makeActiveAd($screen);

        $url = url('/api/v1/screens/'.$screen->code.'/playlist');

        $this->withHeaders($this->signedGetHeaders($url, $screen->device_uid))
            ->getJson($url)
            ->assertOk();
    }

    // --------------------------------------------------------------- playback

    public function test_playback_batches_are_ingested(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeActiveAd($screen);

        [$body, $headers] = $this->signedJsonBody([
            'device_uid' => $screen->device_uid,
            'timestamp' => now()->timestamp,
            'entries' => [
                ['ad_id' => $ad->id, 'played_at' => now()->subMinutes(2)->toIso8601String(), 'duration' => 15],
                ['ad_id' => $ad->id, 'played_at' => now()->subMinute()->toIso8601String(), 'duration' => 15],
            ],
        ], $screen->device_uid);

        $response = $this->call('POST', '/api/v1/playbacks', [], [], [], $this->transformHeadersToServerVars($headers), $body);

        $response->assertStatus(202);
        $response->assertJsonPath('data.ingested', 2);
        $this->assertSame(2, $screen->playbacks()->count());
    }

    /**
     * KNOWN FINDING (HIGH) — playback reports are not checked against the
     * ad↔screen assignment, so a device can claim to have played any ad.
     */
    public function test_known_finding_playback_accepts_an_ad_not_assigned_to_the_screen(): void
    {
        $screen = $this->makeScreen();
        $otherScreen = $this->makeScreen();
        $foreignAd = $this->makeActiveAd($otherScreen);

        [$body, $headers] = $this->signedJsonBody([
            'device_uid' => $screen->device_uid,
            'timestamp' => now()->timestamp,
            'entries' => [
                ['ad_id' => $foreignAd->id, 'played_at' => now()->toIso8601String(), 'duration' => 15],
            ],
        ], $screen->device_uid);

        $this->call('POST', '/api/v1/playbacks', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertStatus(202);

        $this->assertSame(
            1,
            $screen->playbacks()->where('ad_id', $foreignAd->id)->count(),
            'Deferred defect: proof-of-play is not validated against assignment.'
        );
    }

    // ----------------------------------------------------------------- config

    public function test_config_endpoint_returns_the_device_configuration(): void
    {
        $screen = $this->makeScreen();
        $url = url('/api/v1/config?device_uid='.$screen->device_uid);

        $response = $this->withHeaders($this->signedGetHeaders($url, $screen->device_uid))->getJson($url);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['screen', 'config' => ['heartbeat_interval', 'playlist_ttl', 'refresh_interval', 'settings'], 'meta' => ['etag', 'generated_at', 'expires_at']],
        ]);
    }

    // ------------------------------------------------------------ replay probe

    /**
     * KNOWN FINDING (CRITICAL) — there is no nonce and no used-signature store,
     * so a captured signed request can be replayed until the timestamp leeway
     * expires. A GET has no timestamp at all, so its signature never expires.
     */
    public function test_known_finding_a_signed_get_can_be_replayed_indefinitely(): void
    {
        $screen = $this->makeScreen();
        $this->makeActiveAd($screen);

        $url = route('api.v1.screens.playlist', ['screen' => $screen->id]);
        $headers = $this->signedGetHeaders($url, $screen->device_uid);

        $this->withHeaders($headers)->getJson($url)->assertOk();

        $this->travel(2)->hours();

        $this->withHeaders($headers)->getJson($url)->assertOk();

        $this->travelBack();
    }
}
