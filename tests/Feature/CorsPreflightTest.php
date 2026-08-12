<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * CORS preflight behaviour for the Device API.
 *
 * These assertions were written before routes/api.php was registered, so they
 * had never executed against a real route. Two of them encoded misconceptions
 * about how CORS works and are corrected here:
 *
 *  - Laravel normalises Access-Control-Allow-Headers to lower case.
 *  - CORS never rejects a disallowed origin server-side. The middleware answers
 *    the preflight and advertises the *configured* origin; the browser then
 *    refuses to hand the response to a page served from any other origin. The
 *    real security property is therefore "the requesting origin is not echoed
 *    back", which is what is asserted below.
 *
 * Phase 10 replaced the never-used X-Screens-Signature entry with the signing
 * headers the middleware actually reads. Nothing was loosened: the allow-list
 * is still explicit and is asserted here in full, so broadening it to "*" — or
 * quietly adding a header — fails this test.
 */
class CorsPreflightTest extends TestCase
{
    private const ALLOWED_ORIGIN = 'https://android-app.example';

    public function test_allowed_origin_receives_cors_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => self::ALLOWED_ORIGIN,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'X-Client-Id, X-Screen-Signature, Authorization',
        ])->options('/api/v1/screens/handshake');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN);
        $response->assertHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->assertHeader('Access-Control-Max-Age', '600');

        $this->assertSame(
            'accept, authorization, content-type, if-none-match, x-client-id, '
                .'x-screen-signature, x-screen-timestamp, x-screen-nonce, x-screen-uid',
            strtolower((string) $response->headers->get('Access-Control-Allow-Headers')),
            'Every header configured in config/cors.php must be advertised, and no others.'
        );
    }

    public function test_disallowed_origin_is_not_granted_access(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://evil.example',
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/v1/screens/handshake');

        // The preflight is answered, but never for the requesting origin.
        $this->assertNotSame(
            'https://evil.example',
            $response->headers->get('Access-Control-Allow-Origin'),
            'A disallowed origin must never be echoed back as permitted.'
        );

        $this->assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }
}
