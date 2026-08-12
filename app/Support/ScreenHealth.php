<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * The single source of truth for heartbeat timing.
 *
 * Before Phase 11 the numbers were scattered: the Device API advertised
 * `heartbeat_interval` to devices, CheckScreenHealthJob computed its own grace
 * period inline, and .env.example carried a HEARTBEAT_OFFLINE_THRESHOLD that no
 * config file ever read. Everything that needs to know "how long is too long"
 * now asks this class.
 */
class ScreenHealth
{
    /**
     * How many heartbeat intervals a screen may miss before it is called
     * offline. Two intervals means one lost report is tolerated and the second
     * miss transitions the screen — the effective behaviour the offline job was
     * written for, kept unchanged so this phase does not silently retune alerting.
     */
    public const MISSED_INTERVALS = 2;

    /**
     * The heartbeat cadence advertised to devices, in seconds.
     */
    public static function heartbeatInterval(): int
    {
        return max(1, (int) config('services.screens.heartbeat_interval', 60));
    }

    /**
     * How long a screen may be silent before it is considered offline.
     *
     * Always strictly greater than the heartbeat interval: a threshold at or
     * below the cadence would mark healthy screens offline between two perfectly
     * on-time reports.
     */
    public static function offlineAfter(): int
    {
        $interval = self::heartbeatInterval();
        $configured = config('services.screens.offline_after');

        $seconds = is_numeric($configured)
            ? (int) $configured
            : $interval * self::MISSED_INTERVALS;

        return max($interval + 1, $seconds);
    }

    /**
     * The cutoff: a heartbeat older than this is stale.
     */
    public static function offlineThreshold(): Carbon
    {
        return now()->subSeconds(self::offlineAfter());
    }

    /**
     * Is this heartbeat too old to count as reachable?
     *
     * A screen that has never reported is stale — absence of contact is not
     * evidence of health.
     */
    public static function isStale(?Carbon $lastHeartbeat): bool
    {
        return $lastHeartbeat === null || $lastHeartbeat->lt(self::offlineThreshold());
    }
}
