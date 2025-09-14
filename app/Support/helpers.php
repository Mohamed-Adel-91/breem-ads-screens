<?php

use Illuminate\Support\Str;

if (! function_exists('media_path')) {
    function media_path(?string $path): ?string
    {
        if (!$path) {
            return $path;
        }

        if (Str::startsWith($path, [
            'http://', 'https://',
            '/storage', 'storage/',
            '/frontend/', 'frontend/',
        ])) {
            return ltrim($path, '/');
        }

        return 'frontend/' . ltrim($path, '/');
    }
}

