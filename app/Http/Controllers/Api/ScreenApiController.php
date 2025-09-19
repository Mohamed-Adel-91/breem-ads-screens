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
use App\Services\Screen\HeartbeatService;
use App\Services\Screen\ScreenApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ScreenApiController extends Controller
{
    public function __construct(
        protected ScreenApiService $screenService,
        protected HeartbeatService $heartbeatService
    ) {
    }

    /**
     * Handle the screen handshake endpoint.
     */
    public function handshake(HandshakeRequest $request): JsonResponse
    {
        $result = $this->screenService->handshake($request->validated());

        return HandshakeResource::make($result)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Record a heartbeat event for the screen.
     */
    public function heartbeat(HeartbeatRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $screen = $this->screenService->resolveScreen($payload);

        $status = isset($payload['status'])
            ? ScreenStatus::from($payload['status'])
            : ScreenStatus::Online;

        $reportedAt = isset($payload['reported_at'])
            ? Carbon::parse($payload['reported_at'])
            : now();

        $serverTime = now();

        $result = $this->heartbeatService->touch(
            $screen->id,
            $payload['device_uid'] ?? $screen->device_uid,
            [
                'status' => $status,
                'current_ad_code' => $payload['current_ad_code'] ?? null,
                'reported_at' => $reportedAt,
                'last_heartbeat' => $serverTime,
            ]
        );

        if (! $result || ! isset($result['log'])) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, __('Unable to record the heartbeat.'));
        }

        $response = [
            'screen' => $result['screen'],
            'log' => $result['log'],
            'next_heartbeat_at' => (clone $serverTime)->addSeconds((int) config('services.screens.heartbeat_interval', 60)),
        ];

        return HeartbeatResource::make($response)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Return the playlist assigned to the screen.
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
}
