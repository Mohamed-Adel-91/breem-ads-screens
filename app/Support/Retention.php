<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * The one place that decides what "retention" means.
 *
 * A period is a positive number of days. **Anything else — null, empty, zero, a
 * negative number, a non-numeric string — means retention is DISABLED**, and a
 * disabled policy deletes nothing at all. Reading `config('retention.*')` directly
 * anywhere else would risk a different component treating a missing value as "0
 * days" and erasing the table.
 *
 * `cutoffFor()` returns null when disabled, which callers turn into a query that
 * matches no rows. Nothing is ever deleted by default.
 */
final class Retention
{
    public const SCREEN_LOGS = 'screen_logs_days';
    public const PLAYBACK_LOGS = 'playback_logs_days';
    public const REPORTS = 'reports_days';

    /**
     * Every policy this application knows about.
     *
     * @return array<int, string>
     */
    public static function policies(): array
    {
        return [self::SCREEN_LOGS, self::PLAYBACK_LOGS, self::REPORTS];
    }

    /**
     * The configured number of days for a policy, or null when disabled.
     */
    public static function days(string $policy): ?int
    {
        $configured = config('retention.'.$policy);

        if ($configured === null || $configured === '' || ! is_numeric($configured)) {
            return null;
        }

        $days = (int) $configured;

        return $days > 0 ? $days : null;
    }

    /**
     * Is this policy actively pruning?
     */
    public static function enabled(string $policy): bool
    {
        return self::days($policy) !== null;
    }

    /**
     * Records older than this instant are eligible for pruning. Null when the
     * policy is disabled, meaning nothing is eligible.
     *
     * The boundary is exclusive in the callers' favour: they compare with `<`, so a
     * record written exactly `days` ago is kept. Erring towards keeping data is the
     * only safe direction for an irreversible delete.
     */
    public static function cutoffFor(string $policy, ?Carbon $now = null): ?Carbon
    {
        $days = self::days($policy);

        if ($days === null) {
            return null;
        }

        return ($now ? $now->copy() : now())->subDays($days);
    }

    /**
     * A human-readable summary for the operations status command.
     *
     * @return array<string, array{enabled: bool, days: int|null, cutoff: string|null}>
     */
    public static function describe(): array
    {
        $summary = [];

        foreach (self::policies() as $policy) {
            $cutoff = self::cutoffFor($policy);

            $summary[$policy] = [
                'enabled' => self::enabled($policy),
                'days' => self::days($policy),
                'cutoff' => $cutoff?->toDateTimeString(),
            ];
        }

        return $summary;
    }
}
