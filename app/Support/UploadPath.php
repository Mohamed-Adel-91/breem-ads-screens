<?php

namespace App\Support;

/**
 * Single seam between a stored (relative) media path and its absolute location
 * on disk.
 *
 * Historically every writer called public_path() directly, which made the
 * upload chain impossible to isolate: it bypasses the Storage facade entirely,
 * so Storage::fake() had no effect and the test suite wrote real files into
 * public/cms. Routing the four call sites through here keeps the production
 * default byte-identical while letting the test suite point the physical root
 * at a temporary directory.
 *
 * This changes only where bytes land. Stored database paths stay relative and
 * public URLs are still produced by App\Support\MediaUrl via asset().
 */
class UploadPath
{
    /**
     * Absolute root directory for managed uploads.
     */
    public static function root(): string
    {
        $configured = config('media.upload_root');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/\\');
        }

        return rtrim(public_path(), '/\\');
    }

    /**
     * Resolve a stored relative path to its absolute location.
     */
    public static function to(string $relative = ''): string
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');

        return $relative === ''
            ? self::root()
            : self::root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }
}
