<?php

namespace App\Http\Resources\Api;

use App\Enums\ScreenStatus;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Screen
 */
class ScreenResource extends JsonResource
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
            'id' => $this->id,
            'code' => $this->code,
            'device_uid' => $this->device_uid,
            'status' => $this->status instanceof ScreenStatus ? $this->status->value : $this->status,
            'last_heartbeat_at' => optional($this->last_heartbeat)->toAtomString(),
            'created_at' => optional($this->created_at)->toAtomString(),
            'updated_at' => optional($this->updated_at)->toAtomString(),
        ];
    }
}
