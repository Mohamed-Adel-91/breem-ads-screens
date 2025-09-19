<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Config\ConfigRequest;
use App\Http\Resources\Api\Config\ConfigResource;
use App\Services\Config\DeviceConfigService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ConfigController extends Controller
{
    public function __construct(
        protected DeviceConfigService $configService
    ) {}

    /**
     * Return the configuration payload for the screen.
     */
    public function __invoke(ConfigRequest $request): JsonResponse
    {
        $result = $this->configService->forRequest($request->validated());
        $etag = $result['etag'] ?? null;

        if ($etag && $request->ifNoneMatch() && hash_equals($etag, $request->ifNoneMatch())) {
            return response()
                ->json(null, Response::HTTP_NOT_MODIFIED)
                ->setEtag($etag);
        }

        $response = ConfigResource::make($result)->response();

        if ($etag) {
            $response->setEtag($etag);
        }

        return $response;
    }
}
