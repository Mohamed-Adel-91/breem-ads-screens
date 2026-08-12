<?php

namespace App\Services\Screen;

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Screen;
use App\Support\AdValidity;
use App\Support\MediaUrl;
use App\Support\TimeWindow;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The single authoritative source of playlist eligibility.
 *
 * No controller, resource, observer or Blade view may re-derive "is this ad
 * playable right now" — they all read the payload this service produces.
 *
 * ELIGIBILITY (all applicable rules must pass):
 *   1. the ad is assigned to the screen (ad_screen pivot);
 *   2. its status is Active;
 *   3. now is inside the ad's own global window (start_date/end_date);
 *   4. the schedule policy below permits playback.
 *
 * SCHEDULE POLICY, decided per (ad, screen):
 *   - ZERO schedule rows for that pair  -> always scheduled. Assignment-only ads
 *     are valid always-on content, still gated by rules 2 and 3.
 *   - ONE OR MORE rows for that pair    -> playback requires at least one row
 *     that is `is_active` AND currently contains now. Rows existing but none
 *     matching means the ad does NOT play. This closes the historical defect
 *     where an ad with schedules fell back to unscheduled playback outside every
 *     window.
 *
 * Existence is counted over ALL rows, matching only over active ones: an ad
 * whose only row is inactive is gated and cannot play, because an inactive
 * schedule contributes nothing to eligibility and must never make an ad
 * eligible.
 *
 * Boundary inclusivity is delegated to App\Support\TimeWindow (start inclusive,
 * end exclusive) and applies identically to the ad's global window and to
 * schedule rows. Both constraints apply, so the effective window is their
 * intersection — a schedule can never extend playback past the ad's own
 * validity.
 *
 * DETERMINISM: one assigned ad yields exactly one playlist item however many
 * schedule rows match. Items are ordered by pivot `play_order` then ad id, so the
 * same authoritative state at the same instant always produces the same items,
 * the same order and the same ETag.
 */
class AdSchedulerService
{
    /**
     * Lowest cache lifetime handed to the store, in seconds.
     */
    protected const MIN_TTL_SECONDS = 1;

    public function __construct(
        private readonly Repository $cache
    ) {
    }

    /**
     * The cache key owning a screen's playlist payload. This service owns the
     * key format; nothing else may compose it by hand.
     */
    public static function cacheKeyFor(Screen|int $screen): string
    {
        $screenId = $screen instanceof Screen ? $screen->id : $screen;

        return 'playlist:'.(int) $screenId;
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
            $now = now();

            return [
                'screen' => null,
                'items' => [],
                'etag' => '',
                'generated_at' => $now,
                'expires_at' => $now->copy()->addSeconds($this->ttl()),
            ];
        }

        $key = self::cacheKeyFor($screen);

        $payload = $this->cache->get($key);

        // A cached payload is only reusable until its own expiry. The store's TTL
        // is a whole number of seconds, so it cannot land exactly on a schedule
        // boundary; re-checking `expires_at` here makes the transition exact
        // regardless of the store's granularity.
        if (!is_array($payload) || $this->hasExpired($payload)) {
            $payload = $this->buildPayload($screen);

            $this->cache->put($key, $payload, $this->cacheSeconds($payload));
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
        $payload = Arr::except($payload, ['screen']);

        $this->cache->put(
            self::cacheKeyFor($screen),
            $payload,
            $this->cacheSeconds($payload)
        );
    }

    /**
     * Forget the cached playlist entry for the provided screen.
     */
    public function forget(Screen|int $screen): void
    {
        $screenId = $screen instanceof Screen ? $screen->id : $screen;

        if ($screenId) {
            $this->cache->forget(self::cacheKeyFor((int) $screenId));
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
     * The next instant at which this screen's playlist may change because of
     * time alone, or null when no assigned ad has an upcoming boundary.
     *
     * This is the one calculation behind both the payload's `expires_at` and the
     * cache lifetime; cache code never recomputes it.
     */
    public function nextBoundaryFor(Screen $screen, ?CarbonInterface $moment = null): ?Carbon
    {
        $moment = $moment ? Carbon::instance($moment) : now();

        return $this->nextBoundary($this->assignedAds($screen), $moment);
    }

    /**
     * Build the playlist payload for the screen.
     *
     * @return array<string, mixed>
     */
    protected function buildPayload(Screen $screen): array
    {
        $now = now();
        $ads = $this->assignedAds($screen);

        $items = $this->buildItems($ads, $now);
        $boundaryAt = $this->nextBoundary($ads, $now);

        return [
            'items' => $items,
            'etag' => $this->makeEtag($screen, $items),
            'generated_at' => $now,
            'expires_at' => $this->resolveExpiry($now, $boundaryAt),
        ];
    }

    /**
     * Load the ads assigned to the screen together with that screen's schedule
     * rows.
     *
     * Two queries, whatever the number of ads or schedules: the pivot ordering
     * is applied in SQL and the schedules are constrained to this screen so the
     * eligibility pass never touches the database.
     *
     * @return EloquentCollection<int, Ad>
     */
    protected function assignedAds(Screen $screen): EloquentCollection
    {
        $screenId = $screen->id;

        $screen->load([
            'ads' => function ($query): void {
                $query->withPivot('play_order')
                    ->orderBy('ad_screen.play_order')
                    ->orderBy('ads.id');
            },
            'ads.schedules' => function ($query) use ($screenId): void {
                $query->where('screen_id', $screenId)
                    ->orderBy('start_time')
                    ->orderBy('id');
            },
        ]);

        // A duplicated pivot row must never produce a duplicated playlist item.
        return $screen->ads->unique('id')->values();
    }

    /**
     * Build the playlist items for the screen.
     *
     * @param  EloquentCollection<int, Ad>  $ads
     * @return array<int, array<string, mixed>>
     */
    protected function buildItems(EloquentCollection $ads, Carbon $now): array
    {
        $items = $ads
            ->map(fn (Ad $ad) => $this->resolveItem($ad, $now))
            ->filter()
            ->sortBy([
                ['play_order', 'asc'],
                ['ad_id', 'asc'],
            ])
            ->values();

        if ($items->isEmpty()) {
            // Fallback content only ever stands in for an empty playlist; it
            // never plays alongside eligible ads.
            $fallbackItem = $this->makeConfiguredFallbackItem();

            if ($fallbackItem) {
                $items = collect([$fallbackItem]);
            }
        }

        return $items->values()->all();
    }

    /**
     * Resolve the single playlist item for an assigned ad, or null when the ad
     * is not eligible at this instant.
     *
     * @return array<string, mixed>|null
     */
    protected function resolveItem(Ad $ad, Carbon $now): ?array
    {
        if (!$this->adIsEligible($ad, $now)) {
            return null;
        }

        $rows = $ad->schedules;

        // Case A — no schedule rows for this screen: always-on assigned content.
        if ($rows->isEmpty()) {
            return $this->makeItem($ad, null);
        }

        // Case B — rows exist, so a currently matching active row is required.
        $matching = $rows->filter(fn (AdSchedule $schedule) => $this->scheduleIsActive($schedule, $now));

        if ($matching->isEmpty()) {
            return null;
        }

        return $this->makeItem($ad, $this->representativeSchedule($matching));
    }

    /**
     * Determine if the ad's own status and global window allow playback.
     *
     * The global window goes through AdValidity, not TimeWindow directly: the ad's
     * dates come from date-only inputs, so a stored `end_date` of Aug 31 means
     * "through Aug 31", not "up to Aug 31 00:00". Schedule rows keep TimeWindow's
     * literal to-the-second rule.
     */
    protected function adIsEligible(Ad $ad, Carbon $moment): bool
    {
        if ($ad->status !== AdStatus::Active) {
            return false;
        }

        return AdValidity::contains($ad->start_date, $ad->end_date, $moment);
    }

    /**
     * Determine if the schedule row is active and currently within its window.
     *
     * An inactive row is ignored outright: it can neither grant eligibility nor
     * shift a boundary.
     */
    protected function scheduleIsActive(AdSchedule $schedule, Carbon $moment): bool
    {
        if (!$schedule->is_active) {
            return false;
        }

        return TimeWindow::contains($schedule->start_time, $schedule->end_time, $moment);
    }

    /**
     * Pick the schedule row that represents the ad in the payload when several
     * match at once.
     *
     * Eligibility is boolean, so this choice never affects *whether* the ad
     * plays — only the schedule metadata the item carries. The order is earliest
     * end, then earliest start, then lowest id, which is total (ids are unique)
     * and therefore deterministic.
     *
     * @param  Collection<int, AdSchedule>  $matching
     */
    protected function representativeSchedule(Collection $matching): AdSchedule
    {
        return $matching
            ->sortBy([
                ['end_time', 'asc'],
                ['start_time', 'asc'],
                ['id', 'asc'],
            ])
            ->first();
    }

    /**
     * The next instant at which eligibility may change for these ads.
     *
     * Ads whose status forbids playback are skipped: no passage of time makes
     * them eligible, and a status change flushes the cache through AdObserver.
     * Inactive schedule rows are skipped for the same reason — they contribute
     * nothing at any instant.
     *
     * @param  EloquentCollection<int, Ad>  $ads
     */
    protected function nextBoundary(EloquentCollection $ads, Carbon $now): ?Carbon
    {
        $moments = [];

        foreach ($ads as $ad) {
            if ($ad->status !== AdStatus::Active) {
                continue;
            }

            // The ad's own boundaries are the *effective* ones, so a date-only
            // end_date expires the cache at the following midnight — the instant
            // eligibility actually changes — not a day early.
            foreach (AdValidity::boundaries($ad->start_date, $ad->end_date) as $moment) {
                $moments[] = $moment;
            }

            foreach ($ad->schedules as $schedule) {
                if (!$schedule->is_active) {
                    continue;
                }

                $moments[] = $schedule->start_time;
                $moments[] = $schedule->end_time;
            }
        }

        return TimeWindow::nextBoundaryAfter($moments, $now);
    }

    /**
     * When the payload stops being valid: the configured TTL, cut short by the
     * next scheduling boundary.
     *
     * Without the cut, a playlist computed at 09:59:50 for an ad starting at
     * 10:00:00 would stay authoritative until 10:04:50 — five minutes of a
     * device showing the wrong content. The same applies in reverse to an ad
     * about to expire.
     */
    protected function resolveExpiry(Carbon $now, ?Carbon $boundaryAt): Carbon
    {
        $expiresAt = $now->copy()->addSeconds($this->ttl());

        if ($boundaryAt && $boundaryAt->lessThan($expiresAt)) {
            // nextBoundary() only ever returns an instant strictly after $now,
            // so the entry always has a non-zero lifetime.
            return $boundaryAt->copy();
        }

        return $expiresAt;
    }

    /**
     * Whether a cached payload has reached its own expiry.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function hasExpired(array $payload): bool
    {
        $expiresAt = $payload['expires_at'] ?? null;

        return $expiresAt instanceof CarbonInterface
            && now()->greaterThanOrEqualTo($expiresAt);
    }

    /**
     * The store lifetime for a payload, derived from its own expiry.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function cacheSeconds(array $payload): int
    {
        $expiresAt = $payload['expires_at'] ?? null;

        if (!$expiresAt instanceof CarbonInterface) {
            return $this->ttl();
        }

        $seconds = (int) ceil($expiresAt->getTimestamp() - now()->getTimestamp());

        return max(self::MIN_TTL_SECONDS, min($this->ttl(), $seconds));
    }

    /**
     * Build the playlist item payload for the ad/schedule combination.
     *
     * @return array<string, mixed>
     */
    protected function makeItem(Ad $ad, ?AdSchedule $schedule): array
    {
        $playOrder = (int) ($ad->pivot->play_order ?? 0);

        return $this->normalizeItem([
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
            // Ad-derived bounds are the EFFECTIVE ones, from AdValidity. Reporting the
            // raw end_date would tell a player that a date-only "ends Aug 31" campaign
            // stops at Aug 31 00:00, a day before the server actually stops serving it
            // — so a device that self-expires on this value would go dark early.
            // Schedule-derived bounds are already exact and are passed through.
            'valid_from' => $schedule
                ? optional($schedule->start_time)->toAtomString()
                : optional($ad->validFrom())->toAtomString(),
            'valid_until' => $schedule
                ? optional($schedule->end_time)->toAtomString()
                : optional($ad->validBefore())->toAtomString(),
            'ad_valid_from' => optional($ad->validFrom())->toAtomString(),
            'ad_valid_until' => optional($ad->validBefore())->toAtomString(),
        ]);
    }

    /**
     * Build the fallback playlist item defined via configuration.
     */
    protected function makeConfiguredFallbackItem(): ?array
    {
        $fallback = config('ads.fallback');

        if (!is_array($fallback)) {
            return null;
        }

        $image = $fallback['image'] ?? null;

        if (!$image) {
            return null;
        }

        $isRemote = Str::startsWith($image, ['http://', 'https://']);
        $path = $isRemote ? null : ltrim((string) $image, '/');

        return $this->normalizeItem([
            'id' => null,
            'ad_id' => null,
            'file_path' => $path,
            'file_url' => $image,
            'file_type' => $fallback['type'] ?? null,
            'duration_seconds' => (int) ($fallback['duration'] ?? 0),
            'play_order' => 0,
            'schedule_id' => null,
            'schedule' => null,
            'valid_from' => null,
            'valid_until' => null,
            'ad_valid_from' => null,
            'ad_valid_until' => null,
        ]);
    }

    /**
     * Normalize a playlist item payload to ensure consistent structure.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function normalizeItem(array $item): array
    {
        if (!array_key_exists('duration_seconds', $item)) {
            $item['duration_seconds'] = (int) ($item['duration'] ?? 0);
        }

        unset($item['duration']);

        if (!array_key_exists('file_type', $item) && array_key_exists('type', $item)) {
            $item['file_type'] = $item['type'];
        }

        unset($item['type']);

        $path = $item['file_path'] ?? null;
        $url = $item['file_url'] ?? null;

        if (!$url) {
            $url = $path;
        }

        $resolvedUrl = MediaUrl::resolve($url);
        $item['file_url'] = $resolvedUrl;

        if (!$path) {
            $item['file_path'] = $url;
        }

        return $item;
    }

    /**
     * Generate the ETag for the playlist payload.
     *
     * The ETag validates the *playback manifest* the device receives: the stable
     * screen identity plus the items. It deliberately excludes
     * `screens.updated_at`, `status` and `last_heartbeat` — a heartbeat is
     * operational state, not playlist content, and hashing it made every device
     * re-download an identical manifest on its normal poll cadence.
     *
     * `generated_at`/`expires_at` are excluded for the same reason: they are
     * cache bookkeeping, so including them would give the same state at the same
     * instant a different ETag on every rebuild.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function makeEtag(Screen $screen, array $items): string
    {
        $manifest = json_encode([
            'screen' => [
                'id' => $screen->id,
                'code' => $screen->code,
            ],
            'items' => $items,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return sha1((string) $manifest);
    }

    /**
     * Resolve the cache TTL in seconds.
     */
    protected function ttl(): int
    {
        return max(1, (int) config('services.screens.playlist_ttl', 300));
    }
}
