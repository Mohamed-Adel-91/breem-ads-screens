<?php

namespace App\Services\Playback;

use App\Models\PlaybackLog;
use App\Services\Screen\ScreenApiService;
use Illuminate\Support\Carbon;

class PlaybackService
{
    public function __construct(
        protected ScreenApiService $screenService
    ) {
    }

    /**
     * Persist the incoming playback entries for the resolved screen.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function store(array $payload): array
    {
        $screen = $this->screenService->resolveScreen($payload);

        $entries = collect($payload['entries'])
            ->map(function (array $entry) use ($screen): PlaybackLog {
                return $screen->playbacks()->create([
                    'ad_id' => $entry['ad_id'] ?? null,
                    'played_at' => Carbon::parse($entry['played_at']),
                    'duration' => (int) ($entry['duration'] ?? 0),
                    'extra' => $entry['extra'] ?? null,
                ]);
            });

        return [
            'screen' => $screen->fresh(),
            'count' => $entries->count(),
        ];
    }
}
