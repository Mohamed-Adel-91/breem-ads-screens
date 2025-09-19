<?php

namespace App\Http\Resources\Api\Playback;

use App\Http\Resources\Api\ScreenResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaybackResource extends JsonResource
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
            'screen' => ScreenResource::make($this->resource['screen']),
            'ingested' => $this->resource['count'],
        ];
    }
}
