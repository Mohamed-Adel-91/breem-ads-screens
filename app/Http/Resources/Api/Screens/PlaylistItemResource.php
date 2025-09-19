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
            'file_type' => $this->resource['file_type'],
            'duration_seconds' => $this->resource['duration_seconds'],
            'play_order' => $this->resource['play_order'],
            'schedule' => $this->resource['schedule'],
        ];
    }
}
