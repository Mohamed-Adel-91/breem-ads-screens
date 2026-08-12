<?php

namespace App\Http\Resources\Api\Screens;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * A screen's playback manifest.
 *
 * `screen` is the stable PlaylistScreenResource, not the general ScreenResource:
 * the manifest must not carry operational telemetry, because the ETag validates
 * these bytes and telemetry changes on every heartbeat. `meta.expires_at` is
 * boundary-aware — it is never later than the next instant at which eligibility
 * can change, so a device that refetches at that time never plays stale content.
 */
class PlaylistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $items = $this->resource['items'];

        if (! $items instanceof Collection) {
            $items = collect($items);
        }

        return [
            'screen' => PlaylistScreenResource::make($this->resource['screen']),
            'meta' => [
                'etag' => $this->resource['etag'],
                'generated_at' => optional($this->resource['generated_at'])->toAtomString(),
                'expires_at' => optional($this->resource['expires_at'])->toAtomString(),
            ],
            'items' => PlaylistItemResource::collection($items),
        ];
    }
}
