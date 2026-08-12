<?php

namespace Tests\Concerns;

/**
 * Produces the headers a paired device must send on Device API requests.
 *
 * Mirrors App\Http\Requests\Api\ApiRequest exactly:
 *   - GET/HEAD/OPTIONS sign the full URL
 *   - other verbs sign the raw request body
 *   - the digest is HMAC-SHA256 with services.screens.hmac_secret
 *   - it travels in the X-Screen-Signature header
 *
 * This helper does not define the protocol; it only lets tests satisfy the
 * protocol the application already implements.
 */
trait SignsScreenRequests
{
    protected function screenSecret(): string
    {
        return (string) config('services.screens.hmac_secret');
    }

    /**
     * Sign a GET-style request, whose canonical payload is the full URL.
     *
     * @return array<string, string>
     */
    protected function signedGetHeaders(string $url, ?string $deviceUid = null): array
    {
        $headers = [
            'X-Screen-Signature' => hash_hmac('sha256', $url, $this->screenSecret()),
        ];

        if ($deviceUid !== null) {
            $headers['X-Screen-Uid'] = $deviceUid;
        }

        return $headers;
    }

    /**
     * Build a signed JSON request body plus its headers.
     *
     * The digest is taken over the exact bytes returned here, and callers send
     * those same bytes, so the signature matches what the server recomputes from
     * the raw request content.
     *
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: array<string, string>} [rawBody, headers]
     */
    protected function signedJsonBody(array $payload, ?string $deviceUid = null): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Screen-Signature' => hash_hmac('sha256', $body, $this->screenSecret()),
        ];

        if ($deviceUid !== null) {
            $headers['X-Screen-Uid'] = $deviceUid;
        }

        return [$body, $headers];
    }
}
