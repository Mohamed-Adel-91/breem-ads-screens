<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Screens\HandshakeRequest;
use App\Http\Requests\Api\Screens\HeartbeatRequest;
use App\Http\Requests\Api\Screens\PlaylistRequest;
use App\Http\Resources\Api\Screens\HandshakeResource;
use App\Http\Resources\Api\Screens\HeartbeatResource;
use App\Http\Resources\Api\Screens\PlaylistResource;
use App\Models\Screen;
use App\Services\Screen\ScreenApiService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ScreenApiController extends Controller
{
    public function __construct(
        protected ScreenApiService $screenService
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
        $result = $this->screenService->heartbeat($request->validated());

        return HeartbeatResource::make($result)
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
