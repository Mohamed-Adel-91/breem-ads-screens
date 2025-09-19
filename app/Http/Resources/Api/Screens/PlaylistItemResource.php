<?php

namespace App\Http\Resources\Api\Screens;

use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource['id'],
            'ad_id' => $this->resource['ad_id'] ?? $this->resource['id'],
            'file_path' => $this->resource['file_path'],
            'file_url' => $this->resource['file_url'] ?? null,
            'file_type' => $this->resource['file_type'],
            'duration_seconds' => $this->resource['duration_seconds'],
            'play_order' => $this->resource['play_order'],
            'schedule_id' => $this->resource['schedule_id'] ?? null,
            'schedule' => $this->resource['schedule'],
            'valid_from' => $this->resource['valid_from'] ?? null,
            'valid_until' => $this->resource['valid_until'] ?? null,
            'ad_valid_from' => $this->resource['ad_valid_from'] ?? null,
            'ad_valid_until' => $this->resource['ad_valid_until'] ?? null,
        ];
    }
}
