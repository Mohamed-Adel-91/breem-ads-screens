<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * The one place that decides how long a report period may be.
 *
 * A ceiling is a positive number of days. **Anything else — null, empty, zero, a
 * negative number, a non-numeric string — means there is NO ceiling**, and an
 * unbounded period is accepted exactly as it was before this class existed. Reading
 * `config('reports.max_period_days')` directly anywhere else would risk one caller
 * treating a missing value as "0 days" and rejecting every report.
 *
 * PERIOD SEMANTICS ARE THE REQUEST'S, NOT THE QUERY'S. `spanDays()` measures what an
 * operator asked for, using the same calendar-day meaning as
 * App\Services\Reports\ReportGenerationService::resolvePeriod() — `from_date`
 * inclusive from its start of day, `to_date` inclusive of its whole day. A single-day
 * report ("1 August to 1 August") is a span of 1, not 0.
 *
 * An open upper bound is not an unbounded period: `resolvePeriod()` leaves `until`
 * null and the uptime builder then measures up to `now()`, so a `from_date` alone
 * still describes a real, and possibly enormous, window. It is measured to now here
 * for that reason. An open LOWER bound genuinely is bounded — the uptime builder
 * falls back to ScreenAvailabilityService::DEFAULT_WINDOW_DAYS — so a `to_date`
 * alone has no span to check.
 */
final class ReportPeriod
{
    /**
     * The configured ceiling in days, or null when there is no ceiling.
     */
    public static function maxDays(): ?int
    {
        $configured = config('reports.max_period_days');

        if ($configured === null || $configured === '' || ! is_numeric($configured)) {
            return null;
        }

        $days = (int) $configured;

        return $days > 0 ? $days : null;
    }

    /**
     * The number of calendar days an operator's period covers, or null when the
     * request describes no measurable span.
     *
     * @param  array<string, mixed>  $filters  raw request input
     */
    public static function spanDays(array $filters, ?Carbon $now = null): ?int
    {
        if (empty($filters['from_date'])) {
            // No lower bound: the builders supply their own bounded default window.
            return null;
        }

        try {
            $from = Carbon::parse((string) $filters['from_date'])->startOfDay();

            $until = ! empty($filters['to_date'])
                // Inclusive of the whole to_date day, matching resolvePeriod().
                ? Carbon::parse((string) $filters['to_date'])->startOfDay()->addDay()
                : ($now ? $now->copy() : now());
        } catch (\Throwable) {
            // Unparseable dates are the `date` rule's problem, not this one's.
            return null;
        }

        if ($until->lessThanOrEqualTo($from)) {
            // Reversed or same-instant bounds; `after_or_equal` already covers it.
            return null;
        }

        // Round up, so any part of a day counts as a day and the ceiling cannot be
        // squeezed past by a few hours.
        return (int) ceil($from->diffInDays($until, true));
    }

    /**
     * Does this request ask for more than the configured ceiling allows?
     *
     * @param  array<string, mixed>  $filters  raw request input
     */
    public static function exceedsMaximum(array $filters, ?Carbon $now = null): bool
    {
        $max = self::maxDays();

        if ($max === null) {
            return false;
        }

        $span = self::spanDays($filters, $now);

        return $span !== null && $span > $max;
    }
}
