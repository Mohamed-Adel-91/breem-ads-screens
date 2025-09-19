<?php

namespace App\Services\Screen;

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Screen;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class AdSchedulerService
{
    public function __construct(
        private readonly Repository $cache
    ) {
    }

    /**
     * Retrieve the cached playlist payload for the provided screen.
     *
     * @return array<string, mixed>
     */
    public function forScreen(Screen $screen): array
    {
        $screen = $screen->fresh();

        if (!$screen) {
            return [
                'screen' => null,
                'items' => [],
                'etag' => '',
                'generated_at' => now(),
                'expires_at' => now()->addSeconds($this->ttl()),
            ];
        }

        $key = $this->cacheKey($screen->id);

        $payload = $this->cache->get($key);

        if (!is_array($payload)) {
            $payload = $this->buildPayload($screen);
            $this->cache->put($key, $payload, $this->ttl());
        } else {
            $etag = $this->makeEtag($screen, $payload['items'] ?? []);

            if (($payload['etag'] ?? null) !== $etag) {
                $payload['etag'] = $etag;
                $payload['generated_at'] = now();
                $payload['expires_at'] = (clone $payload['generated_at'])->addSeconds($this->ttl());

                $this->cache->put($key, $payload, $this->ttl());
            }
        }

        return array_merge($payload, [
            'screen' => $screen,
        ]);
    }

    /**
     * Persist the computed playlist payload into the cache.
     *
     * @param  array<string, mixed>  $payload
     */
    public function put(Screen $screen, array $payload): void
    {
        $this->cache->put(
            $this->cacheKey($screen->id),
            Arr::except($payload, ['screen']),
            $this->ttl()
        );
    }

    /**
     * Forget the cached playlist entry for the provided screen.
     */
    public function forget(Screen|int $screen): void
    {
        $screenId = $screen instanceof Screen ? $screen->id : $screen;

        if ($screenId) {
            $this->cache->forget($this->cacheKey((int) $screenId));
        }
    }

    /**
     * Forget cached playlists for multiple screens.
     *
     * @param  iterable<int, int|string>  $screenIds
     */
    public function forgetMany(iterable $screenIds): void
    {
        foreach (collect($screenIds)->filter()->unique() as $id) {
            $this->forget((int) $id);
        }
    }

    /**
     * Build the playlist payload for the screen.
     *
     * @return array<string, mixed>
     */
    protected function buildPayload(Screen $screen): array
    {
        $items = $this->buildItems($screen);
        $generatedAt = now();
        $expiresAt = (clone $generatedAt)->addSeconds($this->ttl());

        return [
            'items' => $items,
            'etag' => $this->makeEtag($screen, $items),
            'generated_at' => $generatedAt,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Build the playlist items for the screen.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildItems(Screen $screen): array
    {
        $screenId = $screen->id;

        $screen->loadMissing([
            'ads' => function ($query): void {
                $query->withPivot('play_order')
                    ->orderBy('ad_screen.play_order');
            },
            'ads.schedules' => function ($query) use ($screenId): void {
                $query->where('screen_id', $screenId)
                    ->orderBy('start_time');
            },
        ]);

        $now = now();

        $eligibleAds = $screen->ads
            ->filter(fn (Ad $ad) => $this->adIsEligible($ad, $now))
            ->values();

        $scheduled = $eligibleAds
            ->map(function (Ad $ad) use ($now) {
                $schedule = $ad->schedules
                    ->filter(fn (AdSchedule $schedule) => $this->scheduleIsActive($schedule, $now))
                    ->sortBy('start_time')
                    ->first();

                if (!$schedule) {
                    return null;
                }

                return $this->makeItem($ad, $schedule);
            })
            ->filter()
            ->values();

        $scheduledAdIds = $scheduled->pluck('ad_id')->unique();

        $fallback = $eligibleAds
            ->reject(fn (Ad $ad) => $scheduledAdIds->contains($ad->id))
            ->map(fn (Ad $ad) => $this->makeItem($ad, null))
            ->values();

        return $scheduled
            ->merge($fallback)
            ->sortBy('play_order')
            ->values()
            ->all();
    }

    /**
     * Determine if the ad is eligible for playback at the given moment.
     */
    protected function adIsEligible(Ad $ad, Carbon $moment): bool
    {
        if ($ad->status !== AdStatus::Active) {
            return false;
        }

        if ($ad->start_date && $ad->start_date->greaterThan($moment)) {
            return false;
        }

        if ($ad->end_date && $ad->end_date->lessThan($moment)) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the schedule is currently active.
     */
    protected function scheduleIsActive(AdSchedule $schedule, Carbon $moment): bool
    {
        if (!$schedule->is_active) {
            return false;
        }

        if ($schedule->start_time && $schedule->start_time->greaterThan($moment)) {
            return false;
        }

        if ($schedule->end_time && $schedule->end_time->lessThan($moment)) {
            return false;
        }

        return true;
    }

    /**
     * Build the playlist item payload for the ad/schedule combination.
     *
     * @return array<string, mixed>
     */
    protected function makeItem(Ad $ad, ?AdSchedule $schedule): array
    {
        $playOrder = (int) ($ad->pivot->play_order ?? 0);

        return [
            'id' => $ad->id,
            'ad_id' => $ad->id,
            'file_path' => $ad->file_path,
            'file_url' => $ad->file_url,
            'file_type' => $ad->file_type,
            'duration_seconds' => (int) $ad->duration_seconds,
            'play_order' => $playOrder,
            'schedule_id' => $schedule?->id,
            'schedule' => $schedule ? [
                'id' => $schedule->id,
                'start_time' => optional($schedule->start_time)->toAtomString(),
                'end_time' => optional($schedule->end_time)->toAtomString(),
                'is_active' => (bool) $schedule->is_active,
            ] : null,
            'valid_from' => $schedule
                ? optional($schedule->start_time)->toAtomString()
                : optional($ad->start_date)->toAtomString(),
            'valid_until' => $schedule
                ? optional($schedule->end_time)->toAtomString()
                : optional($ad->end_date)->toAtomString(),
            'ad_valid_from' => optional($ad->start_date)->toAtomString(),
            'ad_valid_until' => optional($ad->end_date)->toAtomString(),
        ];
    }

    /**
     * Generate an ETag for the playlist payload.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function makeEtag(Screen $screen, array $items): string
    {
        $payload = json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return sha1($screen->id.'|'.($screen->updated_at?->timestamp ?? 0).'|'.$payload);
    }

    /**
     * Resolve the cache key for the playlist entry.
     */
    protected function cacheKey(int $screenId): string
    {
        return "playlist:{$screenId}";
    }

    /**
     * Resolve the cache TTL in seconds.
     */
    protected function ttl(): int
    {
        return max(1, (int) config('services.screens.playlist_ttl', 300));
    }
}
