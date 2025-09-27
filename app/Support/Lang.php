<?php

namespace App\Support;

class Lang
{
    /**
     * Translate the given key with an optional fallback.
     */
    public static function t(string $key, ?string $default = null): string
    {
        $translated = __($key);

        if (is_string($translated) && $translated !== $key) {
            return $translated;
        }

        return $default ?? $key;
    }
}
