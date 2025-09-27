<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V1RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_endpoint_uses_v1_rate_limiter(): void
    {
        $response = $this->getJson('/api/v1/config?code=test-screen');

        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit', '120');
        $response->assertHeader('X-RateLimit-Remaining', '119');
    }
}
