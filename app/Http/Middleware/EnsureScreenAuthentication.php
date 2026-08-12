<?php

namespace App\Http\Middleware;

use App\Models\Screen;
use App\Models\ScreenDeviceCredential;
use App\Services\Screen\DeviceReplayGuard;
use App\Support\DeviceSignature;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * The single authentication boundary for the Device API.
 *
 * Every protected request must present, together:
 *
 *   Authorization: Bearer <token>     a per-device token; only its hash is stored
 *   X-Screen-Timestamp: <unix>        inside services.screens.signature_leeway
 *   X-Screen-Nonce: <random>          single use, per credential
 *   X-Screen-Signature: <hex>         HMAC-SHA256 over the canonical message,
 *                                     keyed by that device's own secret
 *
 * Fail-closed by design. A missing token, an unknown token, a revoked or expired
 * credential, a stale timestamp, a reused nonce or a bad signature all end the
 * request with 401 and an error code. Knowing a `device_uid` grants nothing.
 */
class EnsureScreenAuthentication
{
    public const REQUEST_CREDENTIAL = 'screen_credential';
    public const REQUEST_SCREEN = 'authenticated_screen';

    public function __construct(
        protected DeviceReplayGuard $replayGuard
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        // Pairing is the only unauthenticated endpoint: a device has no
        // credential until it has completed one.
        if ($request->routeIs('api.v1.screens.handshake')) {
            return $next($request);
        }

        $token = $request->bearerToken();

        if (! $token) {
            return $this->deny('missing_token', __('A device access token is required.'));
        }

        $credential = ScreenDeviceCredential::query()
            ->where('token_hash', ScreenDeviceCredential::hashToken($token))
            ->first();

        if (! $credential) {
            return $this->deny('invalid_token', __('The device access token is not recognised.'));
        }

        if ($credential->isRevoked()) {
            return $this->deny('revoked_token', __('This device has been revoked.'));
        }

        if ($credential->isExpired()) {
            return $this->deny('expired_token', __('This device credential has expired.'));
        }

        $screen = $credential->screen;

        if (! $screen) {
            return $this->deny('unknown_screen', __('The screen for this credential no longer exists.'));
        }

        // A credential authenticates exactly one screen. Addressing another
        // screen's resource with it is a forbidden action, not a bad identity.
        if (! $this->addressesOwnScreen($request, $screen)) {
            return $this->forbid('screen_mismatch', __('This device may not access another screen.'));
        }

        $timestamp = (string) $request->headers->get(DeviceSignature::TIMESTAMP_HEADER, '');

        if (! $this->timestampIsFresh($timestamp)) {
            return $this->deny('stale_timestamp', __('The request timestamp is outside the allowed window.'));
        }

        $nonce = (string) $request->headers->get(DeviceSignature::NONCE_HEADER, '');

        if ($nonce === '' || strlen($nonce) > 128) {
            return $this->deny('missing_nonce', __('A request nonce is required.'));
        }

        $secret = (string) $credential->hmac_secret;

        if ($secret === '') {
            // Never authenticate when the credential cannot actually be verified.
            Log::error('Device credential has no signing secret.', ['credential_id' => $credential->id]);

            return $this->deny('invalid_signature', __('The request signature could not be verified.'));
        }

        $message = DeviceSignature::messageFromRequest($request, $timestamp, $nonce);
        $presented = (string) $request->headers->get(DeviceSignature::SIGNATURE_HEADER, '');

        if (! DeviceSignature::matches($message, $secret, $presented)) {
            return $this->deny('invalid_signature', __('The request signature could not be verified.'));
        }

        // Consume the nonce only after the signature proves the request is
        // genuine, so an unauthenticated caller cannot burn a device's nonces.
        if (! $this->replayGuard->consume($credential, $nonce)) {
            return $this->deny('replayed_request', __('This request has already been processed.'));
        }

        $credential->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->attributes->set(self::REQUEST_CREDENTIAL, $credential);
        $request->attributes->set(self::REQUEST_SCREEN, $screen);

        return $next($request);
    }

    /**
     * When the route addresses a specific screen, it must be this credential's.
     */
    protected function addressesOwnScreen(Request $request, Screen $screen): bool
    {
        $routeScreen = $request->route('screen');

        if ($routeScreen === null) {
            return true;
        }

        $addressedId = $routeScreen instanceof Screen ? $routeScreen->id : $routeScreen;

        return (string) $addressedId === (string) $screen->id
            || (string) $addressedId === (string) $screen->code;
    }

    protected function timestampIsFresh(string $timestamp): bool
    {
        if ($timestamp === '' || ! ctype_digit(ltrim($timestamp, '-'))) {
            return false;
        }

        $leeway = max(1, (int) config('services.screens.signature_leeway', 300));

        return abs(now()->timestamp - (int) $timestamp) <= $leeway;
    }

    protected function deny(string $code, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error' => $code,
        ], Response::HTTP_UNAUTHORIZED);
    }

    protected function forbid(string $code, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error' => $code,
        ], Response::HTTP_FORBIDDEN);
    }
}
