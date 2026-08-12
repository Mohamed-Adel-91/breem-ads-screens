<?php

namespace App\Services\Reports;

use App\Models\Ad;
use App\Models\PlaybackLog;
use App\Models\Screen;
use App\Services\Monitoring\ScreenAvailabilityService;
use App\Support\ReportType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Builds the stored snapshot for a report.
 *
 * WHY THIS IS A SERVICE: generating a report is a real application capability with
 * its own rules — period semantics, aggregation, and the requirement that screen
 * availability match Monitoring exactly. It was 90 lines of query and Collection
 * work inside ReportController; the controller now handles HTTP and delegates.
 *
 * WHAT CHANGED, AND WHY IT MATTERED
 *
 * The previous implementation ran `PlaybackLog::with(['ad','screen'])->get()` and
 * `ScreenLog::with(['screen.place'])->get()` and then grouped the results in PHP.
 * Every log row in the period was hydrated into a model — with its relations — just
 * to produce a handful of totals. At one heartbeat a minute a week of a 50-screen
 * fleet is ~500k ScreenLog rows; the request would exhaust memory long before it
 * rendered.
 *
 *   - **Playback** is now pure SQL aggregation: `count`, `sum` and `group by` run in
 *     the database and return one row per advertisement. The query count is flat and
 *     nothing scales with the number of log rows.
 *   - **Screen uptime** delegates to ScreenAvailabilityService, per screen. That
 *     deliberately walks the log stream, because availability is a *timeline*
 *     calculation — segments of online/offline/maintenance/unknown time — and no
 *     `count(*)` is equivalent to it. What matters is that the work is bounded per
 *     screen and the numbers are identical to what Monitoring shows. The old report
 *     counted online and offline *events* instead, which is the exact event-ratio
 *     mistake Phase 11 removed from Monitoring: a screen that reported online once
 *     and then died scored perfectly.
 *
 * PERIOD SEMANTICS: `from_date` and `to_date` are UTC calendar dates.
 * `from_date` is inclusive from its start of day; `to_date` is inclusive of the whole
 * day, so the exclusive upper bound is the following midnight. Same shape as
 * App\Support\AdValidity, so an operator reading "1–7 August" gets all seven days.
 * Both bounds are optional.
 */
class ReportGenerationService
{
    /**
     * Snapshot format version, stored with the payload.
     *
     * Legacy rows have no version. Presentation reads defensively rather than
     * assuming, so old reports keep rendering.
     */
    public const SCHEMA_VERSION = 2;

    public function __construct(
        private readonly ScreenAvailabilityService $availability
    ) {
    }

    /**
     * Build the immutable snapshot for a report type and filter set.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(string $type, array $filters): array
    {
        $canonical = ReportType::canonical($type);

        // No silent fall-through. The previous `match` defaulted unknown types to the
        // playback builder, so a report could claim to be one thing and contain
        // another.
        if (! ReportType::isSupported($canonical)) {
            throw new InvalidArgumentException("Unsupported report type [{$type}].");
        }

        [$from, $until] = $this->resolvePeriod($filters);

        $payload = match ($canonical) {
            ReportType::SCREEN_UPTIME => $this->buildScreenUptime($filters, $from, $until),
            default => $this->buildPlayback($filters, $from, $until),
        };

        return array_merge($payload, [
            'schema_version' => self::SCHEMA_VERSION,
            'generated_at' => now()->toDateTimeString(),
            'period' => [
                'from' => $from?->toDateTimeString(),
                'until' => $until?->toDateTimeString(),
                'timezone' => config('app.timezone'),
            ],
        ]);
    }

    /**
     * The report window as [inclusive start, exclusive end], either bound nullable.
     *
     * @param  array<string, mixed>  $filters
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    public function resolvePeriod(array $filters): array
    {
        $from = ! empty($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->startOfDay()
            : null;

        // Inclusive of the whole to_date day: the exclusive bound is the next
        // midnight. endOfDay() would have excluded the final second.
        $until = ! empty($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->startOfDay()->addDay()
            : null;

        return [$from, $until];
    }

    /**
     * Playback volume per advertisement.
     *
     * Two aggregate queries plus one lookup, whatever the number of log rows:
     * totals grouped by ad, distinct screens grouped by ad, and the ad titles.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildPlayback(array $filters, ?Carbon $from, ?Carbon $until): array
    {
        $totals = $this->playbackQuery($filters, $from, $until)
            ->select('ad_id')
            ->selectRaw('COUNT(*) as plays')
            ->selectRaw('COALESCE(SUM(duration), 0) as total_duration')
            ->groupBy('ad_id')
            ->orderByDesc('plays')
            ->orderBy('ad_id')
            ->get();

        // Screen codes per ad, joined and grouped in SQL rather than by hydrating
        // every log and mapping its screen relation.
        $screenCodes = $this->playbackQuery($filters, $from, $until)
            ->join('screens', 'screens.id', '=', 'playback_logs.screen_id')
            ->select('playback_logs.ad_id')
            ->selectRaw('screens.code as code')
            ->groupBy('playback_logs.ad_id', 'screens.code')
            ->get()
            ->groupBy('ad_id')
            ->map(fn ($group) => $group->pluck('code')->filter()->sort()->values()->all());

        $titles = Ad::query()
            ->whereIn('id', $totals->pluck('ad_id')->filter()->all())
            ->get(['id', 'title'])
            ->keyBy('id');

        $locale = app()->getLocale();

        $rows = $totals->map(function ($row) use ($screenCodes, $titles, $locale) {
            $ad = $row->ad_id ? $titles->get($row->ad_id) : null;

            return [
                'ad_id' => $row->ad_id,
                'ad_title' => $ad
                    ? ($ad->getTranslation('title', $locale, false) ?: __('admin.ads.untitled', ['id' => $ad->id]))
                    : '—',
                'plays' => (int) $row->plays,
                'total_duration' => (int) $row->total_duration,
                'screens' => $screenCodes->get($row->ad_id, []),
            ];
        })->values()->all();

        return [
            'rows' => $rows,
            'total_logs' => (int) $totals->sum('plays'),
            'summary' => [
                'advertisements' => count($rows),
                'plays' => (int) $totals->sum('plays'),
                'total_duration' => (int) $totals->sum('total_duration'),
            ],
        ];
    }

    /**
     * Screen availability, measured identically to Monitoring.
     *
     * The window must be closed for a timeline measurement, so unbounded filters
     * fall back to the same default window Monitoring uses.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildScreenUptime(array $filters, ?Carbon $from, ?Carbon $until): array
    {
        $end = $until ?? now();
        $start = $from ?? $end->copy()->subDays(ScreenAvailabilityService::DEFAULT_WINDOW_DAYS);

        $rows = [];

        // Chunked: one availability walk per screen, never every screen's logs at
        // once. `each` streams in pages of 100 rather than hydrating the fleet.
        $this->screenQuery($filters)->chunkById(100, function ($screens) use (&$rows, $start, $end): void {
            foreach ($screens as $screen) {
                $result = $this->availability->forScreen($screen, $start, $end);

                $rows[] = [
                    'screen_id' => $screen->id,
                    'screen_code' => $screen->code,
                    'place' => $screen->place?->getTranslation('name', app()->getLocale(), false) ?: '—',
                    // Identical figures to the Monitoring page, from the same service.
                    'availability' => $result['availability'],
                    'online_seconds' => $result['online_seconds'],
                    'offline_seconds' => $result['offline_seconds'],
                    'maintenance_seconds' => $result['maintenance_seconds'],
                    'unknown_seconds' => $result['unknown_seconds'],
                    'measured_seconds' => $result['measured_seconds'],
                ];
            }
        });

        // Deterministic order: worst availability first, unmeasured last, then code.
        usort($rows, function (array $a, array $b) {
            $left = $a['availability'] ?? PHP_FLOAT_MAX;
            $right = $b['availability'] ?? PHP_FLOAT_MAX;

            return $left <=> $right ?: strcmp((string) $a['screen_code'], (string) $b['screen_code']);
        });

        $measured = array_values(array_filter($rows, fn (array $row) => $row['availability'] !== null));

        return [
            'rows' => $rows,
            'summary' => [
                'screens' => count($rows),
                'measured_screens' => count($measured),
                'average_availability' => $measured === []
                    ? null
                    : round(array_sum(array_column($measured, 'availability')) / count($measured), 2),
                'offline_seconds' => (int) array_sum(array_column($rows, 'offline_seconds')),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function playbackQuery(array $filters, ?Carbon $from, ?Carbon $until): Builder
    {
        $query = PlaybackLog::query();

        if ($from) {
            $query->where('played_at', '>=', $from);
        }

        if ($until) {
            // Exclusive upper bound, matching resolvePeriod().
            $query->where('played_at', '<', $until);
        }

        if (! empty($filters['screen_id'])) {
            $query->where('screen_id', (int) $filters['screen_id']);
        }

        if (! empty($filters['ad_id'])) {
            $query->where('ad_id', (int) $filters['ad_id']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function screenQuery(array $filters): Builder
    {
        $query = Screen::query()->with('place');

        if (! empty($filters['screen_id'])) {
            $query->where('id', (int) $filters['screen_id']);
        }

        return $query;
    }

    /**
     * Column headers for a type's CSV export.
     *
     * @return array<int, string>
     */
    public static function headers(?string $type): array
    {
        return match (ReportType::canonical($type)) {
            ReportType::SCREEN_UPTIME => [
                'Screen ID', 'Code', 'Place', 'Availability %', 'Online Seconds',
                'Offline Seconds', 'Maintenance Seconds', 'Unknown Seconds',
            ],
            default => ['Ad ID', 'Ad Title', 'Plays', 'Total Duration', 'Screens'],
        };
    }

    /**
     * One CSV row, tolerant of legacy snapshots that lack the newer keys.
     *
     * @param  array<string, mixed>  $row
     * @return array<int, mixed>
     */
    public static function formatRow(?string $type, array $row): array
    {
        return match (ReportType::canonical($type)) {
            ReportType::SCREEN_UPTIME => [
                $row['screen_id'] ?? '',
                $row['screen_code'] ?? '',
                $row['place'] ?? '',
                $row['availability'] ?? '',
                $row['online_seconds'] ?? '',
                $row['offline_seconds'] ?? '',
                $row['maintenance_seconds'] ?? '',
                $row['unknown_seconds'] ?? '',
            ],
            default => [
                $row['ad_id'] ?? '',
                $row['ad_title'] ?? '',
                $row['plays'] ?? 0,
                $row['total_duration'] ?? 0,
                implode(', ', (array) ($row['screens'] ?? [])),
            ],
        };
    }
}
