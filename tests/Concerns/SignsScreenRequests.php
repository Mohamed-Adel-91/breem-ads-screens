<?php

namespace Tests\Concerns;

use App\Models\Screen;
use App\Models\ScreenDeviceCredential;
use App\Services\Screen\DevicePairingService;
use App\Support\DeviceSignature;
use Illuminate\Testing\TestResponse;

/**
 * Issues Device API requests the way a paired device does.
 *
 * The canonical message comes from App\Support\DeviceSignature — the same class
 * the server verifies with — so the protocol has one definition and the tests
 * cannot drift from the implementation.
 *
 * Requests are sent through call() rather than getJson()/postJson because those
 * helpers always write a JSON body (an empty GET becomes a literal "[]"), which
 * a real device never sends and which would change the signed body hash.
 */
trait SignsScreenRequests
{
    /**
     * Pair a screen and keep the plaintext credentials for signing.
     *
     * @return array{credential: ScreenDeviceCredential, token: string, secret: string}
     */
    protected function pairScreen(Screen $screen, ?string $deviceUid = null): array
    {
        $result = app(DevicePairingService::class)
            ->issueCredential($screen, $deviceUid ?? $screen->device_uid);

        return [
            'credential' => $result['credential'],
            'token' => $result['token'],
            'secret' => $result['hmac_secret'],
        ];
    }

    /**
     * Signed GET, exactly as a device sends it: no request body.
     *
     * @param  array{token: string, secret: string}  $creds
     * @param  array<string, string>  $overrides  drop or corrupt headers to test failures
     * @param  array<string, string>  $extraHeaders  additional headers (e.g. If-None-Match).
     *                                              These sit outside the signed message —
     *                                              DeviceSignature covers method, path, query,
     *                                              timestamp, nonce and body only — so adding
     *                                              them cannot mask a signature failure.
     */
    protected function deviceGet(string $url, array $creds, array $overrides = [], array $extraHeaders = []): TestResponse
    {
        $headers = $this->signedHeaders('GET', $url, '', $creds, $overrides) + $extraHeaders;

        return $this->call('GET', $url, [], [], [], $this->deviceServerVars($headers), '');
    }

    /**
     * Signed POST carrying a JSON body.
     *
     * @param  array<string, mixed>  $payload
     * @param  array{token: string, secret: string}  $creds
     * @param  array<string, string>  $overrides
     */
    protected function devicePost(string $url, array $payload, array $creds, array $overrides = []): TestResponse
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $headers = $this->signedHeaders('POST', $url, $body, $creds, $overrides)
            + ['Content-Type' => 'application/json'];

        return $this->call('POST', $url, [], [], [], $this->deviceServerVars($headers), $body);
    }

    /**
     * @param  array{token: string, secret: string}  $creds
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    protected function signedHeaders(string $method, string $url, string $body, array $creds, array $overrides = []): array
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';

        $timestamp = $overrides['timestamp'] ?? (string) now()->timestamp;
        $nonce = $overrides['nonce'] ?? bin2hex(random_bytes(16));

        $message = DeviceSignature::message($method, $path, $query, $timestamp, $nonce, $body);

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.($overrides['token'] ?? $creds['token']),
            DeviceSignature::TIMESTAMP_HEADER => $timestamp,
            DeviceSignature::NONCE_HEADER => $nonce,
            DeviceSignature::SIGNATURE_HEADER => $overrides['signature']
                ?? DeviceSignature::sign($message, $creds['secret']),
        ];

        foreach (($overrides['without'] ?? []) as $drop) {
            unset($headers[$drop]);
        }

        return $headers;
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    protected function deviceServerVars(array $headers): array
    {
        return $this->transformHeadersToServerVars($headers);
    }
}
