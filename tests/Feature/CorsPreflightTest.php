<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsPreflightTest extends TestCase
{
    public function test_allowed_origin_receives_cors_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://android-app.example',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-Client-Id, X-Screens-Signature, Authorization',
        ])->options('/api/v1/screens/handshake');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', 'https://android-app.example');
        $response->assertHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->assertHeader('Access-Control-Allow-Headers', 'Accept, Authorization, Content-Type, If-None-Match, X-Client-Id, X-Screens-Signature');
        $response->assertHeader('Access-Control-Max-Age', '600');
    }

    public function test_disallowed_origin_is_rejected(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://evil.example',
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/v1/screens/handshake');

        $response->assertStatus(403);
    }
}
