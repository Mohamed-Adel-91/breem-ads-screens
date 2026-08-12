<?php

namespace Tests\Feature\Api;

use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Place;
use App\Models\Screen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SignsScreenRequests;
use Tests\TestCase;

class V1RateLimitTest extends TestCase
{
    use RefreshDatabase;
    use SignsScreenRequests;

    private function makeScreen(): Screen
    {
        $place = Place::create([
            'name' => ['en' => 'Rate Limit Hall'],
            'address' => ['en' => '1 Limit Road'],
            'type' => PlaceType::Other,
        ]);

        return Screen::create([
            'place_id' => $place->id,
            'code' => 'screen-'.Str::random(8),
            'device_uid' => 'device-'.Str::random(8),
            'status' => ScreenStatus::Offline,
            'last_heartbeat' => null,
        ]);
    }

    public function test_authenticated_endpoints_use_the_v1_rate_limiter(): void
    {
        $creds = $this->pairScreen($this->makeScreen());

        $response = $this->deviceGet(url('/api/v1/config'), $creds);

        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit', '120');
        $response->assertHeader('X-RateLimit-Remaining', '119');
    }

    /**
     * Handshake is unauthenticated, so it gets its own tighter limiter — a
     * shared bucket with the authenticated traffic would let pairing-code
     * guessing consume a device's normal request budget, and vice versa.
     */
    public function test_handshake_uses_its_own_tighter_rate_limiter(): void
    {
        $response = $this->postJson('/api/v1/screens/handshake', [
            'code' => 'no-such-screen',
            'pairing_code' => 'AAAA-BBBB-CCCC',
            'device' => ['uid' => 'probe'],
        ]);

        $response->assertUnauthorized();
        $response->assertHeader('X-RateLimit-Limit', '10');
        $response->assertHeader('X-RateLimit-Remaining', '9');
    }
}
