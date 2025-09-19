<?php

namespace App\Http\Resources\Api\Config;

use App\Http\Resources\Api\ScreenResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $screen = $this->resource['screen'] ?? null;

        return [
            'screen' => $screen ? ScreenResource::make($screen) : null,
            'config' => $this->resource['config'],
            'meta' => [
                'etag' => $this->resource['etag'],
                'generated_at' => optional($this->resource['generated_at'])->toAtomString(),
                'expires_at' => optional($this->resource['expires_at'])->toAtomString(),
            ],
        ];
    }
}
