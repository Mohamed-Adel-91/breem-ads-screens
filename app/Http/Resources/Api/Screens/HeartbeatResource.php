<?php

namespace App\Http\Resources\Api\Screens;

use App\Http\Resources\Api\ScreenResource;
use Illuminate\Http\Resources\Json\JsonResource;

class HeartbeatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $log = $this->resource['log'];

        return [
            'screen' => ScreenResource::make($this->resource['screen']),
            'log' => [
                'id' => $log->id,
                'status' => $log->status,
                'current_ad_code' => $log->current_ad_code,
                'reported_at' => optional($log->reported_at)->toAtomString(),
            ],
            'next_heartbeat_at' => optional($this->resource['next_heartbeat_at'])->toAtomString(),
        ];
    }
}
