<?php

namespace App\Support;

use Illuminate\Support\Facades\App;

/**
 * The one place that turns a number into the digits a locale reads.
 *
 * Arabic presentation uses Arabic-Indic digits (٠١٢٣٤٥٦٧٨٩); English keeps ASCII. That is
 * the whole job.
 *
 * THIS IS PRESENTATION ONLY, AND THE DISTINCTION MATTERS MORE THAN THE MAPPING.
 *
 * Nothing here changes a stored value, a column type, an API payload or a machine
 * identifier. `٢٠` is not a number — it is a string of characters that a browser draws.
 * The moment it reaches anything that parses, compares, sorts, routes or transmits, it is
 * wrong. So:
 *
 *   USE IT FOR      visible counters, result totals, pagination link labels, displayed
 *                   durations, a formatted date already rendered for a human
 *
 *   NEVER USE IT ON route parameters · query strings (`?page=2` stays `?page=2`) · href
 *                   or `tel:` values · API/JSON output · screen codes (`SCR-001` stays
 *                   `SCR-001`) · device UIDs · UUIDs · tokens · HMAC values · filenames ·
 *                   media paths · CSS classes · HTML ids · `data-*` machine attributes ·
 *                   anything written back to the database
 *
 * The rule of thumb: if a computer will read it again, leave it alone.
 *
 * SCOPE OF THE MAPPING. Only the ten digits are substituted. The decimal point, the
 * thousands separator, `+`, `-`, `%` and `/` are left exactly as they arrive. Arabic
 * typography does have its own separators (U+066B, U+066C), but choosing them is a
 * product decision nobody has made, and guessing would silently change the meaning of
 * every decimal on the site. Digit substitution is safe and reversible; separator
 * substitution is not.
 *
 * BIDI. The `+` in `+658` is left where it is. Producing `+٦٥٨` keeps the logical order,
 * and the browser's bidi algorithm places the sign correctly for the paragraph direction.
 * Do not try to reorder characters here — reordering logical text to "fix" visual order
 * is how strings become uncopyable.
 *
 * LOCALE COMES FROM THE APPLICATION. `App::getLocale()`, set by
 * App\Http\Middleware\SetLocaleFromRequest from the `{lang}` route segment. There is no
 * second locale source here and there must not be one.
 */
final class LocalizedDigits
{
    /**
     * The locale whose numerals differ from ASCII.
     */
    public const ARABIC_LOCALE = 'ar';

    /**
     * ASCII digit → Arabic-Indic digit (U+0660–U+0669).
     *
     * @var array<string, string>
     */
    private const ARABIC_INDIC = [
        '0' => '٠',
        '1' => '١',
        '2' => '٢',
        '3' => '٣',
        '4' => '٤',
        '5' => '٥',
        '6' => '٦',
        '7' => '٧',
        '8' => '٨',
        '9' => '٩',
    ];

    /**
     * Render a value's digits for a locale.
     *
     * Any scalar is accepted because call sites hand over whatever the model or the
     * paginator gave them — an int, a float, a pre-formatted string like "1,024" or
     * "+658", or null. The return is always a string, because the result is display text
     * and must never be mistaken for a number again.
     */
    public static function format(mixed $value, ?string $locale = null): string
    {
        if ($value === null || is_bool($value)) {
            return '';
        }

        if (is_array($value) || is_object($value)) {
            // Nothing sensible to render, and stringifying an object here would hide the
            // real mistake at the call site.
            return '';
        }

        $string = (string) $value;

        if (! self::usesArabicIndic($locale)) {
            return $string;
        }

        return strtr($string, self::ARABIC_INDIC);
    }

    /**
     * Should this locale be shown Arabic-Indic digits?
     */
    public static function usesArabicIndic(?string $locale = null): bool
    {
        return ($locale ?? App::getLocale()) === self::ARABIC_LOCALE;
    }

    /**
     * Turn Arabic-Indic digits back into ASCII.
     *
     * Present so that anything which has to read a displayed value back — a test, a
     * debugging session, a future import of hand-typed input — has one correct way to do
     * it instead of inventing a second map. Not used on the request path: incoming form
     * data is validated as numeric, and nothing accepts Arabic-Indic input today.
     */
    public static function toAscii(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return strtr($value, array_flip(self::ARABIC_INDIC));
    }
}
