<?php

use Illuminate\Support\Arr;
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
            '/cms/', 'cms/',
        ])) {
            return ltrim($path, '/');
        }

        return 'frontend/' . ltrim($path, '/');
    }
}

if (! function_exists('nav_active')) {
    /**
     * Determine navigation state classes for the current route.
     *
     * @param  string|array<int, string>  $patterns
     * @param  string  $activeClass
     * @param  string  $openClass
     * @return array{is_active: bool, active: string, open: string}
     */
    function nav_active(string|array $patterns, string $activeClass = 'active', string $openClass = 'open'): array
    {
        $patterns = Arr::wrap($patterns);

        $isActive = false;

        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                $isActive = true;
                break;
            }
        }

        return [
            'is_active' => $isActive,
            'active' => $isActive ? $activeClass : '',
            'open' => $isActive ? $openClass : '',
        ];
    }
}

