<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Playback\StorePlaybackRequest;
use App\Http\Resources\Api\Playback\PlaybackResource;
use App\Services\Playback\PlaybackService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PlaybackController extends Controller
{
    public function __construct(
        protected PlaybackService $playbackService
    ) {
    }

    /**
     * Store the playback batch reported by the screen.
     */
    public function store(StorePlaybackRequest $request): JsonResponse
    {
        $result = $this->playbackService->store($request->validated());

        return PlaybackResource::make($result)
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
