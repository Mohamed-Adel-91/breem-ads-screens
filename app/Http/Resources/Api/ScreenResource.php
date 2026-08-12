<?php

namespace App\Http\Resources\Api;

use App\Enums\ScreenStatus;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Screen representation for the Device API.
 *
 * `device_uid` is deliberately absent. The device already knows its own UID, and
 * echoing it on every response widened the exposure of a value that used to be
 * accepted as authentication. Admin screens use their own Blade views, so this
 * change does not affect the dashboard.
 *
 * @mixin \App\Models\Screen
 */
class ScreenResource extends JsonResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status instanceof ScreenStatus ? $this->status->value : $this->status,
            'last_heartbeat_at' => optional($this->last_heartbeat)->toAtomString(),
            'created_at' => optional($this->created_at)->toAtomString(),
            'updated_at' => optional($this->updated_at)->toAtomString(),
        ];
    }
}
