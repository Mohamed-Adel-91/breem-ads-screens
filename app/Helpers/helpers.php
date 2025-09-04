<?php

/**
 * Check if the currently authenticated admin has a given role.
 *
 * @param int|array $roles Role or array of roles to check against
 * @return bool true if the role matches otherwise aborts with 403
 */
if (!function_exists('HasRole')) {
    function HasRole(int|array $roles): bool
    {
        $admin = auth()->guard('admin')->user();

        if (!$admin) {
            abort(403);
        }

        $roles = (array) $roles;

        if (!in_array($admin->role, $roles, true)) {
            abort(403);
        }

        return true;
    }
}

/**
 * Format a number based on the current locale.
 *
 * @param int|float $number The number to format
 * @return string The formatted number
 */
if (!function_exists('formatNumber')) {
    function formatNumber($number)
    {
        $locale = app()->getLocale();

        $formatter = new \NumberFormatter(
            $locale === 'ar' ? 'ar_EG' : 'en_US',
            \NumberFormatter::DECIMAL
        );

        return $formatter->format($number);
    }
}

/**
 * Example helper function for other locale-dependent features.
 */
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount)
    {
        $locale = app()->getLocale();

        $formatter = new \NumberFormatter(
            $locale === 'ar' ? 'ar_EG' : 'en_US',
            \NumberFormatter::CURRENCY
        );

        return $formatter->formatCurrency($amount, 'USD');
    }
}

/**
 * Build a Unicode-safe slug that keeps non-Latin scripts (Arabic) intact.
 *
 * @param  string $text Original title/phrase.
 * @param  string $sep  Word separator (default '-'; you can use '+').
 * @return string       Lowercased, punctuation-free, UTF-8-safe slug.
 */
if (! function_exists('unicode_slug')) {
    function unicode_slug(string $text, string $sep = '-'): string
    {
        // 1) Remove everything except letters, numbers, and whitespace (Unicode-aware).
        $s = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text);

        // 2) Trim and collapse whitespace into a single separator.
        $s = preg_replace('/\s+/u', $sep, trim($s));

        // 3) Lowercase safely for multibyte strings.
        return mb_strtolower($s, 'UTF-8');
    }
}
