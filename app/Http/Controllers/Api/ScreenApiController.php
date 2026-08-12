<?php

namespace App\Http\Controllers\Api;

use App\Enums\ScreenStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Screens\HandshakeRequest;
use App\Http\Requests\Api\Screens\HeartbeatRequest;
use App\Http\Requests\Api\Screens\PlaylistRequest;
use App\Http\Resources\Api\Screens\HandshakeResource;
use App\Http\Resources\Api\Screens\HeartbeatResource;
use App\Http\Resources\Api\Screens\PlaylistResource;
use App\Models\Screen;
use App\Services\Screen\DevicePairingService;
use App\Services\Screen\HeartbeatService;
use App\Services\Screen\PairingException;
use App\Services\Screen\ScreenApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ScreenApiController extends Controller
{
    public function __construct(
        protected ScreenApiService $screenService,
        protected HeartbeatService $heartbeatService,
        protected DevicePairingService $pairing
    ) {
    }

    /**
     * Claim a screen with a one-time pairing code and issue device credentials.
     *
     * This is the only response that ever contains the bearer token and signing
     * secret; both are shown once and stored hashed/encrypted.
     */
    public function handshake(HandshakeRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->pairing->claim(
                $data['code'],
                $data['pairing_code'],
                $data['device']['uid']
            );
        } catch (PairingException $e) {
            return $this->pairingFailure($e);
        }

        $screen = $result['screen'];
        $screen->forceFill([
            'status' => ScreenStatus::Online,
            'last_heartbeat' => now(),
        ])->save();

        return HandshakeResource::make([
            'screen' => $screen->fresh(),
            'config' => $this->screenService->bootstrapConfig($data),
            'token' => $result['token'],
            'hmac_secret' => $result['hmac_secret'],
            'meta' => ['paired_at' => now()],
        ])->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Record a heartbeat for the authenticated screen.
     */
    public function heartbeat(HeartbeatRequest $request): JsonResponse
    {
        $screen = $request->authenticatedScreen();
        $payload = $request->validated();

        $serverTime = now();

        // The service stamps last_heartbeat with server time itself and decides
        // the authoritative status; the device only contributes telemetry.
        $result = $this->heartbeatService->touch(
            $screen->id,
            $screen->device_uid,
            [
                'status' => isset($payload['status']) ? ScreenStatus::from($payload['status']) : null,
                'current_ad_code' => $payload['current_ad_code'] ?? null,
                'reported_at' => isset($payload['reported_at']) ? Carbon::parse($payload['reported_at']) : null,
            ]
        );

        if (! $result || ! isset($result['log'])) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, __('Unable to record the heartbeat.'));
        }

        return HeartbeatResource::make([
            'screen' => $result['screen'],
            'log' => $result['log'],
            'next_heartbeat_at' => (clone $serverTime)
                ->addSeconds((int) config('services.screens.heartbeat_interval', 60)),
        ])->response()->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Return the playlist for the authenticated screen.
     *
     * The route parameter is validated against the credential by
     * EnsureScreenAuthentication, so a device cannot read another screen.
     */
    public function playlist(PlaylistRequest $request, Screen $screen)
    {
        $result = $this->screenService->playlist($screen, $request->ifNoneMatch());

        if (! empty($result['unchanged'])) {
            return response()
                ->noContent(Response::HTTP_NOT_MODIFIED)
                ->setEtag($result['etag']);
        }

        return PlaylistResource::make($result)
            ->response()
            ->setEtag($result['etag']);
    }

    protected function pairingFailure(PairingException $e): JsonResponse
    {
        [$status, $message] = match ($e->reason) {
            DevicePairingService::REASON_UNKNOWN_SCREEN,
            DevicePairingService::REASON_INVALID_CODE => [
                Response::HTTP_UNAUTHORIZED,
                __('The screen code or pairing code is not valid.'),
            ],
            DevicePairingService::REASON_EXPIRED_CODE => [
                Response::HTTP_UNAUTHORIZED,
                __('This pairing code has expired. Ask an administrator for a new one.'),
            ],
            DevicePairingService::REASON_ALREADY_PAIRED => [
                Response::HTTP_CONFLICT,
                __('This screen is already paired. An administrator must reset it first.'),
            ],
            default => [Response::HTTP_UNAUTHORIZED, __('Pairing failed.')],
        };

        // An unknown screen and a wrong code deliberately share one message so
        // the endpoint cannot be used to enumerate screen codes.
        return response()->json([
            'message' => $message,
            'error' => $e->reason === DevicePairingService::REASON_ALREADY_PAIRED
                ? 'already_paired'
                : 'invalid_pairing',
        ], $status);
    }
}
