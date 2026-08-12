<?php

namespace App\Support;

/**
 * The one authoritative list of report types.
 *
 * There were three disagreeing lists before this: `GenerateReportRequest::TYPES`
 * accepted `playback` and `screen-uptime`; `ReportsAndLogsSeeder` wrote
 * `performance` and `availability`; and the generator's `match` fell through to the
 * playback builder for anything it did not recognise. So a seeded "Screen
 * Availability Snapshot" was stored under a type nothing could produce, and asking
 * the generator for it would have quietly built a playback report instead.
 *
 * Deliberately a class and not an enum: the `reports.type` column is a plain string
 * and **live rows already hold the legacy values**. An enum cast would throw on
 * reading them, so legacy values are mapped rather than outlawed, and no historical
 * row is rewritten.
 */
final class ReportType
{
    /**
     * Playback volume per advertisement — plays, total duration, screens reached.
     */
    public const PLAYBACK = 'playback';

    /**
     * Time-based screen availability, using the same measurement as Monitoring.
     */
    public const SCREEN_UPTIME = 'screen-uptime';

    /**
     * Legacy type values found on existing rows, mapped to the canonical type that
     * means the same thing.
     *
     * Both were stale seeder inventions describing reports this application already
     * had under another name — `performance` is playback volume, `availability` is
     * screen uptime. They are readable and presentable; they are not offered for
     * new generation.
     *
     * @var array<string, string>
     */
    private const LEGACY_ALIASES = [
        'performance' => self::PLAYBACK,
        'availability' => self::SCREEN_UPTIME,
    ];

    /**
     * Types a new report may be generated as. This is the production contract and
     * the only list the generate form offers.
     *
     * @return array<int, string>
     */
    public static function supported(): array
    {
        return [self::PLAYBACK, self::SCREEN_UPTIME];
    }

    /**
     * May a new report be generated as this type?
     */
    public static function isSupported(?string $type): bool
    {
        return in_array($type, self::supported(), true);
    }

    /**
     * Is this a legacy value that only exists on historical rows?
     */
    public static function isLegacy(?string $type): bool
    {
        return array_key_exists((string) $type, self::LEGACY_ALIASES);
    }

    /**
     * The canonical type used to decide how to present a stored report.
     *
     * Legacy values resolve to the type they always meant, so an existing
     * `availability` report renders with the screen-uptime columns instead of
     * silently falling through to the playback layout. An unrecognised value is
     * returned untouched — callers decide how to handle it, and nothing guesses.
     */
    public static function canonical(?string $type): ?string
    {
        $type = (string) $type;

        if (self::isSupported($type)) {
            return $type;
        }

        return self::LEGACY_ALIASES[$type] ?? ($type === '' ? null : $type);
    }

    /**
     * Is this type presentable — i.e. does a row layout and CSV header set exist?
     */
    public static function isPresentable(?string $type): bool
    {
        return self::isSupported(self::canonical($type));
    }

    /**
     * The translation key for a type's label.
     */
    public static function labelKey(?string $type): string
    {
        return 'admin.reports.types.'.(string) $type;
    }

    /**
     * A readable fallback label for a type with no translation, including legacy and
     * unknown values.
     */
    public static function fallbackLabel(?string $type): string
    {
        return ucfirst(str_replace('-', ' ', (string) $type));
    }
}
