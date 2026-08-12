<?php

namespace Tests\Feature\Api;

use App\Enums\AdStatus;
use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Ad;
use App\Models\Place;
use App\Models\Screen;
use App\Models\ScreenDeviceCredential;
use App\Models\ScreenLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Config\DeviceConfigService;
use App\Services\Screen\DevicePairingService;
use App\Support\DeviceSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SignsScreenRequests;
use Tests\TestCase;

/**
 * The Device API contract after Phase 10 hardening.
 *
 * Every protected request must prove four things together: a per-device bearer
 * token, a per-device HMAC signature over a canonical message, a fresh
 * timestamp, and a single-use nonce. Fixtures are isolated in the in-memory
 * database — no real screen, credential or pairing code is touched.
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

    private function pairing(): DevicePairingService
    {
        return app(DevicePairingService::class);
    }

    private function playlistUrl(Screen $screen): string
    {
        return route('api.v1.screens.playlist', ['screen' => $screen->id]);
    }

    // ---------------------------------------------------------------- pairing

    public function test_a_device_pairs_with_a_one_time_code_and_receives_credentials(): void
    {
        $screen = $this->makeScreen(['code' => 'PAIR-ME', 'device_uid' => null]);
        $issued = $this->pairing()->issuePairingCode($screen);

        $response = $this->postJson('/api/v1/screens/handshake', [
            'code' => 'PAIR-ME',
            'pairing_code' => $issued['code'],
            'device' => ['uid' => 'brand-new-device', 'model' => 'BX-1'],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.screen.code', 'PAIR-ME');
        $response->assertJsonPath('data.auth.token_type', 'Bearer');
        $response->assertJsonStructure([
            'data' => [
                'screen',
                'config' => ['heartbeat_interval', 'playlist_ttl', 'timezone'],
                'auth' => ['token_type', 'access_token', 'hmac_secret', 'signature_algorithm'],
            ],
        ]);

        $screen->refresh();
        $this->assertSame('brand-new-device', $screen->device_uid);
        $this->assertSame(ScreenStatus::Online, $screen->status);
        $this->assertNotNull($this->pairing()->activeCredential($screen));
    }

    public function test_a_pairing_code_cannot_be_used_twice(): void
    {
        $screen = $this->makeScreen(['code' => 'ONCE-ONLY', 'device_uid' => null]);
        $issued = $this->pairing()->issuePairingCode($screen);

        $payload = [
            'code' => 'ONCE-ONLY',
            'pairing_code' => $issued['code'],
            'device' => ['uid' => 'first-device'],
        ];

        $this->postJson('/api/v1/screens/handshake', $payload)->assertCreated();

        // Reset first, so "already paired" cannot mask the one-time check.
        $this->pairing()->resetDevice($screen->fresh());

        $this->postJson('/api/v1/screens/handshake', array_merge($payload, [
            'device' => ['uid' => 'second-device'],
        ]))->assertUnauthorized()->assertJsonPath('error', 'invalid_pairing');
    }

    public function test_regenerating_a_pairing_code_invalidates_the_previous_one(): void
    {
        $screen = $this->makeScreen(['code' => 'REGEN', 'device_uid' => null]);

        $leaked = $this->pairing()->issuePairingCode($screen);
        $replacement = $this->pairing()->issuePairingCode($screen);

        $this->assertNotSame($leaked['code'], $replacement['code']);

        // An administrator regenerates precisely because the old code leaked; it
        // must not stay claimable for the rest of its TTL.
        $this->postJson('/api/v1/screens/handshake', [
            'code' => 'REGEN',
            'pairing_code' => $leaked['code'],
            'device' => ['uid' => 'eavesdropper'],
        ])->assertUnauthorized()->assertJsonPath('error', 'invalid_pairing');

        $this->assertNull($this->pairing()->activeCredential($screen->fresh()));

        $this->postJson('/api/v1/screens/handshake', [
            'code' => 'REGEN',
            'pairing_code' => $replacement['code'],
            'device' => ['uid' => 'installer'],
        ])->assertCreated();
    }

    public function test_an_expired_pairing_code_is_rejected(): void
    {
        $screen = $this->makeScreen(['code' => 'STALE-CODE', 'device_uid' => null]);
        $issued = $this->pairing()->issuePairingCode($screen);

        $this->travel(2)->hours();

        $this->postJson('/api/v1/screens/handshake', [
            'code' => 'STALE-CODE',
            'pairing_code' => $issued['code'],
            'device' => ['uid' => 'late-device'],
        ])->assertUnauthorized();

        $this->travelBack();
    }

    public function test_a_wrong_pairing_code_is_rejected(): void
    {
        $screen = $this->makeScreen(['code' => 'WRONG-CODE', 'device_uid' => null]);
        $this->pairing()->issuePairingCode($screen);

        $this->postJson('/api/v1/screens/handshake', [
            'code' => 'WRONG-CODE',
            'pairing_code' => 'AAAA-BBBB-CCCC',
            'device' => ['uid' => 'guessing-device'],
        ])->assertUnauthorized();

        $this->assertNull($this->pairing()->activeCredential($screen->fresh()));
    }

    public function test_knowing_only_the_screen_code_no_longer_pairs_a_device(): void
    {
        $screen = $this->makeScreen(['code' => 'CODE-ONLY', 'device_uid' => null]);

        // No pairing code was ever issued for this screen.
        $this->postJson('/api/v1/screens/handshake', [
            'code' => 'CODE-ONLY',
            'pairing_code' => 'ZZZZ-ZZZZ-ZZZZ',
            'device' => ['uid' => 'opportunist'],
        ])->assertUnauthorized();

        $this->assertNull($this->pairing()->activeCredential($screen->fresh()));
    }

    public function test_an_already_paired_screen_cannot_be_silently_reclaimed(): void
    {
        $screen = $this->makeScreen(['code' => 'TAKE-OVER', 'device_uid' => 'legitimate-device']);
        $this->pairScreen($screen, 'legitimate-device');

        $issued = $this->pairing()->issuePairingCode($screen);

        $this->postJson('/api/v1/screens/handshake', [
            'code' => 'TAKE-OVER',
            'pairing_code' => $issued['code'],
            'device' => ['uid' => 'attacker-device'],
        ])->assertStatus(409)->assertJsonPath('error', 'already_paired');

        $this->assertSame('legitimate-device', $screen->fresh()->device_uid);
    }

    public function test_an_administrator_reset_allows_re_pairing(): void
    {
        $screen = $this->makeScreen(['code' => 'RESET-ME', 'device_uid' => 'old-device']);
        $this->pairScreen($screen, 'old-device');

        $this->pairing()->resetDevice($screen);
        $this->assertNull($this->pairing()->activeCredential($screen->fresh()));

        $issued = $this->pairing()->issuePairingCode($screen->fresh());

        $this->postJson('/api/v1/screens/handshake', [
            'code' => 'RESET-ME',
            'pairing_code' => $issued['code'],
            'device' => ['uid' => 'replacement-device'],
        ])->assertCreated();

        $this->assertSame('replacement-device', $screen->fresh()->device_uid);
    }

    public function test_a_concurrent_claim_cannot_succeed_twice(): void
    {
        $screen = $this->makeScreen(['code' => 'RACE', 'device_uid' => null]);
        $issued = $this->pairing()->issuePairingCode($screen);

        $winners = 0;

        foreach (['device-a', 'device-b'] as $uid) {
            try {
                $this->pairing()->claim('RACE', $issued['code'], $uid);
                $winners++;
            } catch (\Throwable) {
                // Loser: the code is consumed, or the screen is already paired.
            }
        }

        $this->assertSame(1, $winners, 'Exactly one device may consume a pairing code.');
        $this->assertSame(1, ScreenDeviceCredential::whereNull('revoked_at')->count());
    }

    // ------------------------------------------------------------------ token

    public function test_the_issued_token_is_random_and_stored_only_as_a_hash(): void
    {
        $screen = $this->makeScreen(['code' => 'TOKENS', 'device_uid' => null]);
        $issued = $this->pairing()->issuePairingCode($screen);

        $response = $this->postJson('/api/v1/screens/handshake', [
            'code' => 'TOKENS',
            'pairing_code' => $issued['code'],
            'device' => ['uid' => 'token-device'],
        ])->assertCreated();

        $token = $response->json('data.auth.access_token');
        $secret = $response->json('data.auth.hmac_secret');

        $this->assertNotSame('token-device', $token);
        $this->assertNotSame('TOKENS', $token);
        $this->assertNotSame((string) $screen->id, $token);
        $this->assertSame(64, strlen($token));
        $this->assertNotSame($token, $secret, 'The signing secret must differ from the token.');

        $this->assertDatabaseMissing('screen_device_credentials', ['token_hash' => $token]);
        $this->assertDatabaseHas('screen_device_credentials', [
            'token_hash' => ScreenDeviceCredential::hashToken($token),
        ]);
    }

    public function test_the_device_uid_alone_grants_nothing(): void
    {
        $screen = $this->makeScreen();
        $this->pairScreen($screen);

        $this->withHeader('X-Screen-Uid', $screen->device_uid)
            ->getJson($this->playlistUrl($screen))
            ->assertUnauthorized()
            ->assertJsonPath('error', 'missing_token');
    }

    public function test_a_missing_token_is_rejected(): void
    {
        $screen = $this->makeScreen();
        $this->pairScreen($screen);

        $this->getJson($this->playlistUrl($screen))
            ->assertUnauthorized()
            ->assertJsonPath('error', 'missing_token');
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->deviceGet($this->playlistUrl($screen), $creds, ['token' => str_repeat('f', 64)])
            ->assertUnauthorized()
            ->assertJsonPath('error', 'invalid_token');
    }

    public function test_a_revoked_token_is_rejected(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->pairing()->revokeActive($screen);

        $this->deviceGet($this->playlistUrl($screen), $creds)
            ->assertUnauthorized()
            ->assertJsonPath('error', 'revoked_token');
    }

    public function test_an_expired_credential_is_rejected(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $creds['credential']->forceFill(['expires_at' => now()->subMinute()])->saveQuietly();

        $this->deviceGet($this->playlistUrl($screen), $creds)
            ->assertUnauthorized()
            ->assertJsonPath('error', 'expired_token');
    }

    public function test_a_credential_cannot_reach_another_screen(): void
    {
        $mine = $this->makeScreen();
        $theirs = $this->makeScreen();
        $creds = $this->pairScreen($mine);

        $this->deviceGet($this->playlistUrl($theirs), $creds)
            ->assertForbidden()
            ->assertJsonPath('error', 'screen_mismatch');
    }

    // ------------------------------------------------------------------- HMAC

    public function test_a_valid_signature_is_accepted(): void
    {
        $screen = $this->makeScreen();
        $this->makeActiveAd($screen);
        $creds = $this->pairScreen($screen);

        $this->deviceGet($this->playlistUrl($screen), $creds)->assertOk();
    }

    public function test_a_forged_signature_is_rejected(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->deviceGet($this->playlistUrl($screen), $creds, ['signature' => str_repeat('a', 64)])
            ->assertUnauthorized()
            ->assertJsonPath('error', 'invalid_signature');
    }

    public function test_one_device_cannot_sign_for_another(): void
    {
        $screenA = $this->makeScreen();
        $screenB = $this->makeScreen();
        $credsA = $this->pairScreen($screenA);
        $credsB = $this->pairScreen($screenB);

        // B's token presented, but signed with A's secret.
        $mixed = ['token' => $credsB['token'], 'secret' => $credsA['secret'], 'credential' => $credsB['credential']];

        $this->deviceGet($this->playlistUrl($screenB), $mixed)
            ->assertUnauthorized()
            ->assertJsonPath('error', 'invalid_signature');
    }

    public function test_a_credential_without_a_secret_never_authenticates(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        // Corrupted credential material must fail closed, never open.
        $creds['credential']->forceFill(['hmac_secret' => ''])->saveQuietly();

        $this->deviceGet($this->playlistUrl($screen), $creds)
            ->assertUnauthorized()
            ->assertJsonPath('error', 'invalid_signature');
    }

    public function test_the_canonical_message_covers_method_path_query_timestamp_nonce_and_body(): void
    {
        $message = DeviceSignature::message('get', 'api/v1/config', 'b=2&a=1', '1700000000', 'abc', '{"x":1}');

        $this->assertSame(implode("\n", [
            'GET',
            '/api/v1/config',
            'a=1&b=2',
            '1700000000',
            'abc',
            hash('sha256', '{"x":1}'),
        ]), $message);
    }

    // -------------------------------------------------------------- timestamp

    public function test_a_timestamp_is_mandatory_on_get(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->deviceGet($this->playlistUrl($screen), $creds, ['without' => [DeviceSignature::TIMESTAMP_HEADER]])
            ->assertUnauthorized()
            ->assertJsonPath('error', 'stale_timestamp');
    }

    public function test_a_timestamp_is_mandatory_on_post(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds, [
            'without' => [DeviceSignature::TIMESTAMP_HEADER],
        ])->assertUnauthorized()->assertJsonPath('error', 'stale_timestamp');
    }

    public function test_a_stale_timestamp_is_rejected(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->deviceGet($this->playlistUrl($screen), $creds, ['timestamp' => (string) now()->subHour()->timestamp])
            ->assertUnauthorized()
            ->assertJsonPath('error', 'stale_timestamp');
    }

    public function test_a_future_timestamp_outside_tolerance_is_rejected(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->deviceGet($this->playlistUrl($screen), $creds, ['timestamp' => (string) now()->addHour()->timestamp])
            ->assertUnauthorized()
            ->assertJsonPath('error', 'stale_timestamp');
    }

    // ------------------------------------------------------------------ nonce

    public function test_a_nonce_is_required(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->deviceGet($this->playlistUrl($screen), $creds, ['without' => [DeviceSignature::NONCE_HEADER]])
            ->assertUnauthorized()
            ->assertJsonPath('error', 'missing_nonce');
    }

    public function test_an_exact_request_replay_is_rejected(): void
    {
        $screen = $this->makeScreen();
        $this->makeActiveAd($screen);
        $creds = $this->pairScreen($screen);
        $url = $this->playlistUrl($screen);

        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string) now()->timestamp;

        $this->deviceGet($url, $creds, ['nonce' => $nonce, 'timestamp' => $timestamp])->assertOk();

        $this->deviceGet($url, $creds, ['nonce' => $nonce, 'timestamp' => $timestamp])
            ->assertUnauthorized()
            ->assertJsonPath('error', 'replayed_request');
    }

    public function test_a_captured_get_cannot_be_replayed_later(): void
    {
        $screen = $this->makeScreen();
        $this->makeActiveAd($screen);
        $creds = $this->pairScreen($screen);
        $url = $this->playlistUrl($screen);

        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string) now()->timestamp;

        $this->deviceGet($url, $creds, ['nonce' => $nonce, 'timestamp' => $timestamp])->assertOk();

        $this->travel(2)->hours();

        // The nonce is spent and the timestamp is now stale.
        $this->deviceGet($url, $creds, ['nonce' => $nonce, 'timestamp' => $timestamp])->assertUnauthorized();

        $this->travelBack();
    }

    public function test_a_reused_nonce_with_a_different_body_is_rejected(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);
        $nonce = bin2hex(random_bytes(16));

        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'online'], $creds, ['nonce' => $nonce])
            ->assertOk();

        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'maintenance'], $creds, ['nonce' => $nonce])
            ->assertUnauthorized()
            ->assertJsonPath('error', 'replayed_request');
    }

    public function test_two_devices_may_use_the_same_nonce_independently(): void
    {
        $screenA = $this->makeScreen();
        $screenB = $this->makeScreen();
        $this->makeActiveAd($screenA);
        $this->makeActiveAd($screenB);
        $credsA = $this->pairScreen($screenA);
        $credsB = $this->pairScreen($screenB);

        $nonce = bin2hex(random_bytes(16));

        $this->deviceGet($this->playlistUrl($screenA), $credsA, ['nonce' => $nonce])->assertOk();
        $this->deviceGet($this->playlistUrl($screenB), $credsB, ['nonce' => $nonce])->assertOk();
    }

    // --------------------------------------------------------------- playlist

    public function test_an_authenticated_screen_receives_its_playlist_with_an_etag(): void
    {
        $screen = $this->makeScreen();
        $this->makeActiveAd($screen);
        $creds = $this->pairScreen($screen);
        $url = $this->playlistUrl($screen);

        $first = $this->deviceGet($url, $creds);
        $first->assertOk();

        $etag = trim((string) $first->headers->get('ETag'), '"');
        $this->assertNotSame('', $etag);

        // A fresh nonce means a fresh request, and the ETag is stable across it.
        $second = $this->deviceGet($url, $creds);
        $second->assertOk();
        $this->assertSame($first->headers->get('ETag'), $second->headers->get('ETag'));
    }

    public function test_the_playlist_response_never_echoes_the_device_uid(): void
    {
        $screen = $this->makeScreen(['device_uid' => 'super-secret-uid']);
        $this->makeActiveAd($screen);
        $creds = $this->pairScreen($screen, 'super-secret-uid');

        $this->deviceGet($this->playlistUrl($screen), $creds)
            ->assertOk()
            ->assertDontSee('super-secret-uid');
    }

    // --------------------------------------------------------------- heartbeat

    public function test_heartbeat_updates_the_authenticated_screen(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $response = $this->devicePost('/api/v1/screens/heartbeat', [
            'status' => 'online',
            'current_ad_code' => 'AD-9',
        ], $creds);

        $response->assertOk();
        $response->assertJsonPath('data.screen.status', 'online');

        $screen->refresh();
        $this->assertSame(ScreenStatus::Online, $screen->status);
        $this->assertSame(1, $screen->logs()->count());
    }

    public function test_heartbeat_still_accepts_the_maintenance_status(): void
    {
        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $this->devicePost('/api/v1/screens/heartbeat', ['status' => 'maintenance'], $creds)->assertOk();

        $this->assertSame(ScreenStatus::Maintenance, $screen->fresh()->status);
        $this->assertSame('maintenance', ScreenLog::first()->status->value);
    }

    // --------------------------------------------------------------- playback

    public function test_playback_for_an_assigned_ad_is_accepted(): void
    {
        $screen = $this->makeScreen();
        $ad = $this->makeActiveAd($screen);
        $creds = $this->pairScreen($screen);

        $response = $this->devicePost('/api/v1/playbacks', [
            'entries' => [
                ['ad_id' => $ad->id, 'played_at' => now()->subMinutes(2)->toIso8601String(), 'duration' => 15],
                ['ad_id' => $ad->id, 'played_at' => now()->subMinute()->toIso8601String(), 'duration' => 15],
            ],
        ], $creds);

        $response->assertStatus(202);
        $response->assertJsonPath('data.ingested', 2);
        $this->assertSame(2, $screen->playbacks()->count());
    }

    public function test_playback_for_an_unassigned_ad_is_rejected(): void
    {
        $screen = $this->makeScreen();
        $otherScreen = $this->makeScreen();
        $foreignAd = $this->makeActiveAd($otherScreen);
        $creds = $this->pairScreen($screen);

        $this->devicePost('/api/v1/playbacks', [
            'entries' => [
                ['ad_id' => $foreignAd->id, 'played_at' => now()->toIso8601String(), 'duration' => 15],
            ],
        ], $creds)->assertStatus(422)->assertJsonValidationErrors('entries');

        $this->assertSame(0, $screen->playbacks()->count());
    }

    public function test_a_playback_batch_is_always_attributed_to_the_authenticated_screen(): void
    {
        $screen = $this->makeScreen();
        $otherScreen = $this->makeScreen();
        $ad = $this->makeActiveAd($screen);
        $creds = $this->pairScreen($screen);

        // A device_uid in the body is ignored; the credential decides.
        $this->devicePost('/api/v1/playbacks', [
            'device_uid' => $otherScreen->device_uid,
            'entries' => [['ad_id' => $ad->id, 'played_at' => now()->toIso8601String(), 'duration' => 5]],
        ], $creds)->assertStatus(202);

        $this->assertSame(1, $screen->playbacks()->count());
        $this->assertSame(0, $otherScreen->playbacks()->count());
    }

    // ----------------------------------------------------------------- config

    public function test_config_returns_only_allow_listed_settings(): void
    {
        Setting::create(['key' => 'site.phone', 'value' => '+99612345']);
        Setting::create(['key' => 'admin.secret_webhook', 'value' => 'https://internal.example/hook']);
        Setting::create(['key' => 'map.iframe', 'value' => '<iframe src="https://maps.example"></iframe>']);

        $screen = $this->makeScreen();
        $creds = $this->pairScreen($screen);

        $response = $this->deviceGet(url('/api/v1/config'), $creds);

        $response->assertOk();
        $response->assertDontSee('secret_webhook');
        $response->assertDontSee('maps.example');

        $returned = array_keys($response->json('data.config.settings'));
        $this->assertContains('site.phone', $returned);

        foreach ($returned as $key) {
            $this->assertContains($key, DeviceConfigService::ALLOWED_SETTING_KEYS);
        }
    }

    public function test_config_requires_authentication(): void
    {
        $this->getJson('/api/v1/config')->assertUnauthorized();
    }
}
