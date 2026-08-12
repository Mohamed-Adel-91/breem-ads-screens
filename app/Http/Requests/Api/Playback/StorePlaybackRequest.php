<?php

namespace App\Http\Requests\Api\Playback;

use App\Http\Requests\Api\ApiRequest;

/**
 * Playback batches are always attributed to the authenticated screen. Each entry
 * must name an ad that is actually assigned to that screen — see
 * PlaybackService::store().
 */
class StorePlaybackRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entries' => ['required', 'array', 'min:1', 'max:500'],
            'entries.*.ad_id' => ['required', 'integer', 'exists:ads,id'],
            'entries.*.played_at' => ['required', 'date'],
            'entries.*.duration' => ['nullable', 'integer', 'min:0'],
            'entries.*.extra' => ['nullable', 'array'],
            'entries.*.extra.*' => ['nullable'],
        ];
    }
}
