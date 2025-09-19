<?php

namespace App\Http\Resources\Api\Screens;

use App\Http\Resources\Api\ScreenResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

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
            'screen' => ScreenResource::make($this->resource['screen']),
            'meta' => [
                'etag' => $this->resource['etag'],
                'generated_at' => optional($this->resource['generated_at'])->toAtomString(),
                'expires_at' => optional($this->resource['expires_at'])->toAtomString(),
            ],
            'items' => PlaylistItemResource::collection($items),
        ];
    }
}
