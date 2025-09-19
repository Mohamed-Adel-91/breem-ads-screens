<?php

namespace App\Http\Resources\Api\Screens;

use App\Http\Resources\Api\ScreenResource;
use Illuminate\Http\Resources\Json\JsonResource;

class HandshakeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $screen = $this->resource['screen'];

        return [
            'screen' => ScreenResource::make($screen),
            'config' => $this->resource['config'] ?? [],
            'auth' => [
                'device_uid' => $screen->device_uid,
                'bearer_token' => $this->resource['token'] ?? null,
            ],
            'meta' => $this->resource['meta'] ?? [],
        ];
    }
}
