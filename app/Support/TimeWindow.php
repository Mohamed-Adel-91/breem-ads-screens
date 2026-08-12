<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The one place that decides whether a moment falls inside a scheduling window.
 *
 * Boundary contract — **start inclusive, end exclusive**: `start <= moment < end`.
 * A null bound means "open in that direction". Two adjacent windows (10:00→11:00
 * and 11:00→12:00) therefore never both contain 11:00, which is what makes the
 * playlist deterministic at a boundary instant.
 *
 * Every scheduling comparison in the application goes through here: the ad's
 * global validity window, the per-screen schedule window, and the admin schedule
 * state badge. Reproducing `>=`/`<` inline anywhere else reintroduces the
 * ambiguity this class exists to remove.
 */
final class TimeWindow
{
    /**
     * Does the window contain the moment? Start inclusive, end exclusive.
     */
    public static function contains(?CarbonInterface $start, ?CarbonInterface $end, CarbonInterface $moment): bool
    {
        if ($start && $start->greaterThan($moment)) {
            return false;
        }

        if ($end && $end->lessThanOrEqualTo($moment)) {
            return false;
        }

        return true;
    }

    /**
     * The earliest of the supplied moments that lies strictly after `$moment`,
     * or null when none does.
     *
     * "Strictly after" matters: a boundary equal to now has already been applied
     * by contains(), so treating it as upcoming would pin the cache TTL to zero
     * and rebuild the same payload on every request.
     *
     * @param  iterable<mixed>  $moments
     */
    public static function nextBoundaryAfter(iterable $moments, CarbonInterface $moment): ?Carbon
    {
        $next = null;

        foreach ($moments as $candidate) {
            if (! $candidate instanceof CarbonInterface) {
                continue;
            }

            if (! $candidate->greaterThan($moment)) {
                continue;
            }

            if ($next === null || $candidate->lessThan($next)) {
                $next = $candidate;
            }
        }

        return $next ? Carbon::instance($next) : null;
    }
}
