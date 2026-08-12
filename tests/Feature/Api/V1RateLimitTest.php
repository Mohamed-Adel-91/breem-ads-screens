<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SignsScreenRequests;
use Tests\TestCase;

class V1RateLimitTest extends TestCase
{
    use RefreshDatabase;
    use SignsScreenRequests;

    public function test_config_endpoint_uses_v1_rate_limiter(): void
    {
        $url = url('/api/v1/config?code=test-screen');

        $response = $this
            ->withHeaders($this->signedGetHeaders($url))
            ->getJson($url);

        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit', '120');
        $response->assertHeader('X-RateLimit-Remaining', '119');
    }
}
