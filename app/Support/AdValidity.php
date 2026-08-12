<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The boundary contract for an ad's own global validity window
 * (`ads.start_date` / `ads.end_date`).
 *
 * This is deliberately NOT folded into App\Support\TimeWindow. TimeWindow states
 * one plain rule — start inclusive, end exclusive — and schedule rows follow it
 * literally, to the second. The ad's global dates are different in kind: the admin
 * form writes them from `type="date"` inputs, so they arrive as calendar dates
 * rendered as midnight. Reading such a value as a literal exclusive instant makes
 * `end_date = Aug 31` stop playback at `Aug 31 00:00` — the ad never plays on the
 * day the operator picked. Putting that surprise inside the generic helper would
 * make every caller of TimeWindow harder to reason about, so it lives here under a
 * name that says what it is.
 *
 * THE CONTRACT
 *
 *   - `start_date` is used as stored, inclusive. A date-only value is midnight,
 *     which is the start of that calendar day — already what an operator means.
 *   - `end_date` is normalised to an **exclusive** upper bound:
 *       - a midnight (date-only) value covers the whole of that calendar day, so
 *         the bound becomes the following midnight;
 *       - a value carrying a time component is a precise instant and is used as
 *         stored, exclusive.
 *
 * So `start_date = Aug 1`, `end_date = Aug 31` plays from `Aug 1 00:00` up to
 * `Sep 1 00:00` exclusive — throughout Aug 31, which is what the form implies.
 *
 * The second clause is what makes this safe for existing data: legacy rows that
 * hold a real datetime (the seeded demo ads do) keep their exact meaning. Nothing
 * stored is rewritten or migrated; only the interpretation of date-only values
 * changes. Dates are UTC calendar dates, matching the application timezone.
 *
 * Schedule-row semantics are untouched.
 */
final class AdValidity
{
    /**
     * The inclusive instant from which the ad is valid, or null when unbounded.
     */
    public static function startsAt(?CarbonInterface $startDate): ?Carbon
    {
        return $startDate ? Carbon::instance($startDate) : null;
    }

    /**
     * The exclusive instant at which the ad stops being valid, or null when
     * unbounded.
     */
    public static function endsBefore(?CarbonInterface $endDate): ?Carbon
    {
        if (! $endDate) {
            return null;
        }

        $endsAt = Carbon::instance($endDate);

        // A date-only value means "through the end of this day".
        return self::isMidnight($endsAt)
            ? $endsAt->copy()->addDay()
            : $endsAt;
    }

    /**
     * Is the moment inside the ad's global validity window?
     *
     * Start inclusive, normalised end exclusive. The comparison itself is
     * TimeWindow's — this class only decides where the upper bound sits.
     */
    public static function contains(?CarbonInterface $startDate, ?CarbonInterface $endDate, CarbonInterface $moment): bool
    {
        return TimeWindow::contains(
            self::startsAt($startDate),
            self::endsBefore($endDate),
            $moment
        );
    }

    /**
     * The window's boundaries that fall in the future, for playlist cache expiry.
     *
     * @return array<int, Carbon>
     */
    public static function boundaries(?CarbonInterface $startDate, ?CarbonInterface $endDate): array
    {
        return array_values(array_filter([
            self::startsAt($startDate),
            self::endsBefore($endDate),
        ]));
    }

    private static function isMidnight(Carbon $moment): bool
    {
        return $moment->hour === 0
            && $moment->minute === 0
            && $moment->second === 0
            && (int) $moment->micro === 0;
    }
}
