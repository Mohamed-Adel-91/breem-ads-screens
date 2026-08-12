<?php

namespace App\Services\Monitoring;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use App\Models\ScreenLog;
use Illuminate\Support\Carbon;

/**
 * Time-based availability for a screen.
 *
 * Replaces the "uptime" that Monitoring and the Screen detail page used to show,
 * which was `online log rows / total log rows` — an event ratio, not a duration.
 * A screen that reported online once and then died for six days scored 100%.
 *
 * The algorithm walks the log stream as a timeline:
 *
 *   1. Determine the status in effect at the start of the window: the status of
 *      the last log written BEFORE the window opened. If there is none, the
 *      period begins as `unknown` — we genuinely were not observing the screen.
 *   2. Each log inside the window closes the running segment at its
 *      `reported_at` and opens a new one with its status.
 *   3. The final segment runs to the end of the window.
 *
 * Seconds accumulate per status. Availability is
 * `online / (online + offline)`.
 *
 * Two exclusions are deliberate:
 *
 *   - **Unknown time is never counted as online.** Absence of evidence is not
 *     evidence of health, so unobserved time only shrinks the denominator and is
 *     reported separately.
 *   - **Maintenance is excluded from the denominator.** It is planned, operator-
 *     owned downtime, so it neither counts as available nor penalises the screen.
 *     It is reported separately so it stays visible.
 *
 * When the denominator is zero — no observed online or offline time at all —
 * availability is null and the UI says so instead of printing a misleading 0%
 * or 100%.
 */
class ScreenAvailabilityService
{
    /**
     * The reporting window used by Monitoring and the Screen detail page.
     */
    public const DEFAULT_WINDOW_DAYS = 7;

    /**
     * @return array{
     *     period_start: Carbon,
     *     period_end: Carbon,
     *     period_seconds: int,
     *     online_seconds: int,
     *     offline_seconds: int,
     *     maintenance_seconds: int,
     *     unknown_seconds: int,
     *     measured_seconds: int,
     *     availability: float|null,
     * }
     */
    public function forScreen(Screen $screen, ?Carbon $start = null, ?Carbon $end = null): array
    {
        $end = $end ? $end->copy() : now();
        $start = $start ? $start->copy() : $end->copy()->subDays(self::DEFAULT_WINDOW_DAYS);

        if ($start->gte($end)) {
            return $this->emptyResult($start, $end);
        }

        $seconds = [
            ScreenStatus::Online->value => 0,
            ScreenStatus::Offline->value => 0,
            ScreenStatus::Maintenance->value => 0,
        ];
        $unknown = 0;

        $cursor = $start->copy();
        $current = $this->statusAt($screen, $start);

        foreach ($this->logsWithin($screen, $start, $end) as $log) {
            $boundary = $log->reported_at;

            // A log written exactly at the window start opens the period rather
            // than closing a zero-length segment. The guard also keeps the cursor
            // monotonic if two entries share a timestamp.
            if ($boundary->gt($cursor)) {
                // Carbon 3 returns a float; these are whole seconds by contract.
                $elapsed = (int) $cursor->diffInSeconds($boundary);

                if ($current === null) {
                    $unknown += $elapsed;
                } else {
                    $seconds[$current->value] += $elapsed;
                }

                $cursor = $boundary->copy();
            }

            $current = $log->status instanceof ScreenStatus
                ? $log->status
                : ScreenStatus::tryFrom((string) $log->status);
        }

        if ($cursor->lt($end)) {
            $elapsed = (int) $cursor->diffInSeconds($end);

            if ($current === null) {
                $unknown += $elapsed;
            } else {
                $seconds[$current->value] += $elapsed;
            }
        }

        $online = $seconds[ScreenStatus::Online->value];
        $offline = $seconds[ScreenStatus::Offline->value];
        $measured = $online + $offline;

        return [
            'period_start' => $start,
            'period_end' => $end,
            'period_seconds' => (int) $start->diffInSeconds($end),
            'online_seconds' => $online,
            'offline_seconds' => $offline,
            'maintenance_seconds' => $seconds[ScreenStatus::Maintenance->value],
            'unknown_seconds' => $unknown,
            'measured_seconds' => $measured,
            'availability' => $measured > 0 ? round($online / $measured * 100, 2) : null,
        ];
    }

    /**
     * The status in effect when the window opened.
     *
     * Null means the screen had never reported anything before the window, so
     * the leading time is genuinely unobserved rather than implicitly online.
     */
    protected function statusAt(Screen $screen, Carbon $moment): ?ScreenStatus
    {
        $previous = $screen->logs()
            ->where('reported_at', '<', $moment)
            ->orderByDesc('reported_at')
            ->orderByDesc('id')
            ->first();

        if (! $previous) {
            return null;
        }

        return $previous->status instanceof ScreenStatus
            ? $previous->status
            : ScreenStatus::tryFrom((string) $previous->status);
    }

    /**
     * @return \Illuminate\Support\Collection<int, ScreenLog>
     */
    protected function logsWithin(Screen $screen, Carbon $start, Carbon $end)
    {
        return $screen->logs()
            ->whereBetween('reported_at', [$start, $end])
            ->orderBy('reported_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyResult(Carbon $start, Carbon $end): array
    {
        return [
            'period_start' => $start,
            'period_end' => $end,
            'period_seconds' => 0,
            'online_seconds' => 0,
            'offline_seconds' => 0,
            'maintenance_seconds' => 0,
            'unknown_seconds' => 0,
            'measured_seconds' => 0,
            'availability' => null,
        ];
    }
}
