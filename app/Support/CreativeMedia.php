<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * The single authoritative map of what an ad creative may be.
 *
 * Accepted MIME types, the category each one belongs to, the extension a stored
 * file is given, and the size ceiling per category all live here. They used to be
 * spread across StoreAdRequest, UpdateAdRequest, AdController and the Blade form,
 * which meant four lists that could disagree — and they did: the requests
 * validated `mimetypes:` from file *content* while the controller classified the
 * creative from the *client filename*, so a renamed file was accepted as one thing
 * and recorded as another.
 *
 * Two rules follow from that history:
 *
 *   1. **The MIME type is the authority.** `category()` takes a server-detected
 *      MIME type. The client filename is presentation metadata and never decides
 *      what a file is.
 *   2. **The stored extension is derived, never copied.** `extensionFor()` returns
 *      the extension for a detected MIME type, so a GIF uploaded as `payload.php`
 *      lands on disk as `<random>.gif`. Trusting the client extension meant a
 *      polyglot creative could be written into a web-served directory with an
 *      executable suffix.
 */
final class CreativeMedia
{
    public const CATEGORY_IMAGE = 'image';
    public const CATEGORY_GIF = 'gif';
    public const CATEGORY_VIDEO = 'video';

    /**
     * Accepted MIME types, grouped by the `ads.file_type` value they map to.
     *
     * The enum column only accepts `video`, `image` and `gif`, so these three
     * groups are the whole domain. Do not add a format because a browser can
     * render it — the Android player has to be able to play it.
     *
     * @var array<string, array<int, string>>
     */
    private const MIME_MAP = [
        self::CATEGORY_VIDEO => [
            'video/mp4',
            'video/x-m4v',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-ms-wmv',
            'video/mpeg',
            'video/webm',
        ],
        self::CATEGORY_GIF => [
            'image/gif',
        ],
        self::CATEGORY_IMAGE => [
            'image/jpeg',
            'image/png',
        ],
    ];

    /**
     * The extension a stored creative is given for each accepted MIME type.
     *
     * @var array<string, string>
     */
    private const EXTENSION_MAP = [
        'video/mp4' => 'mp4',
        'video/x-m4v' => 'm4v',
        'video/quicktime' => 'mov',
        'video/x-msvideo' => 'avi',
        'video/x-ms-wmv' => 'wmv',
        'video/mpeg' => 'mpeg',
        'video/webm' => 'webm',
        'image/gif' => 'gif',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Every accepted MIME type, for the `mimetypes:` validation rule.
     *
     * @return array<int, string>
     */
    public static function allowedMimeTypes(): array
    {
        return array_merge(...array_values(self::MIME_MAP));
    }

    /**
     * The same list as a comma-separated string, for the rule and the form's
     * `accept` attribute.
     */
    public static function allowedMimeTypeList(): string
    {
        return implode(',', self::allowedMimeTypes());
    }

    /**
     * The `file_type` category for a server-detected MIME type, or null when the
     * type is not accepted.
     */
    public static function category(?string $mimeType): ?string
    {
        $mimeType = strtolower(trim((string) $mimeType));

        foreach (self::MIME_MAP as $category => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes, true)) {
                return $category;
            }
        }

        return null;
    }

    /**
     * The category of an uploaded file, detected from its contents.
     *
     * `getMimeType()` guesses from the file's magic bytes (falling back to the
     * client type only when detection is unavailable), which is why this — and not
     * the filename — decides the category.
     */
    public static function categoryOf(UploadedFile $file): ?string
    {
        return self::category($file->getMimeType());
    }

    /**
     * The extension a stored creative should carry for a detected MIME type.
     */
    public static function extensionFor(?string $mimeType): ?string
    {
        return self::EXTENSION_MAP[strtolower(trim((string) $mimeType))] ?? null;
    }

    /**
     * The extension a stored creative should carry for an uploaded file.
     */
    public static function extensionOf(UploadedFile $file): ?string
    {
        return self::extensionFor($file->getMimeType());
    }

    /**
     * The size ceiling in kilobytes for a category, from config('ads.upload').
     */
    public static function maxKilobytes(string $category): int
    {
        $configured = match ($category) {
            self::CATEGORY_VIDEO => config('ads.upload.video_max_kb'),
            self::CATEGORY_GIF => config('ads.upload.gif_max_kb'),
            default => config('ads.upload.image_max_kb'),
        };

        return max(1, (int) $configured);
    }

    /**
     * The largest configured ceiling, used as the blanket `max:` rule so an
     * oversized payload is rejected by standard validation before any
     * category-specific check runs.
     */
    public static function absoluteMaxKilobytes(): int
    {
        return max(array_map(
            static fn (string $category) => self::maxKilobytes($category),
            [self::CATEGORY_IMAGE, self::CATEGORY_GIF, self::CATEGORY_VIDEO]
        ));
    }

    /**
     * Does this category require a playable duration?
     *
     * Images and GIFs are shown for however long the playlist says. A video's
     * duration is a property of the file itself.
     */
    public static function requiresProbedDuration(string $category): bool
    {
        return $category === self::CATEGORY_VIDEO;
    }
}
