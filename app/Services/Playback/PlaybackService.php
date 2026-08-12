<?php

namespace App\Services\Playback;

use App\Models\PlaybackLog;
use App\Models\Screen;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PlaybackService
{
    /**
     * Persist a playback batch reported by an authenticated screen.
     *
     * The screen is supplied by the caller from the authenticated credential —
     * never from the request body — and every entry must name an advertisement
     * actually assigned to that screen. Without the assignment check a device
     * could manufacture proof-of-play for any advertiser's creative.
     *
     * Whether the ad was *scheduled* at the reported instant is deliberately not
     * enforced here: schedule semantics are still unsettled, and rejecting on
     * them today would drop legitimate playbacks.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function store(Screen $screen, array $payload): array
    {
        $entries = collect($payload['entries'] ?? []);

        $assignedAdIds = $screen->ads()->pluck('ads.id')->all();

        $unassigned = $entries
            ->pluck('ad_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => in_array($id, $assignedAdIds, true))
            ->values();

        if ($unassigned->isNotEmpty()) {
            throw ValidationException::withMessages([
                'entries' => __('Playback was reported for an advertisement that is not assigned to this screen.'),
            ]);
        }

        $stored = $entries->map(fn (array $entry): PlaybackLog => $screen->playbacks()->create([
            'ad_id' => $entry['ad_id'],
            'played_at' => Carbon::parse($entry['played_at']),
            'duration' => (int) ($entry['duration'] ?? 0),
            'extra' => $entry['extra'] ?? null,
        ]));

        return [
            'screen' => $screen->fresh(),
            'count' => $stored->count(),
        ];
    }
}
