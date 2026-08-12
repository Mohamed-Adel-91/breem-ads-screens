<?php

namespace App\Http\Resources\Api\Screens;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The screen block inside a playlist response.
 *
 * The playlist endpoint represents a PLAYBACK MANIFEST, not monitoring state, so
 * this carries only the stable identity a player needs to confirm it is holding
 * the right manifest.
 *
 * It deliberately does NOT reuse the general ScreenResource. That one exposes
 * `status`, `last_heartbeat_at` and `updated_at`, all of which change on every
 * heartbeat: embedding them made the response bytes differ on each poll, which in
 * turn forced the ETag to change and defeated conditional requests entirely. No
 * device contract in this repository reads those fields from the playlist — the
 * heartbeat response is where operational state belongs.
 *
 * @mixin \App\Models\Screen
 */
class PlaylistScreenResource extends JsonResource
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
        ];
    }
}
