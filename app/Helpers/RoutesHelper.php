<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RoutesHelper
{
    /**
     * Get Frontend Routes: returns an associative array of route name => display URL.
     * Includes routes named with prefixes 'web.' (current) and 'front.' (legacy),
     * filters to GET-only routes, strips the {lang}/{lang?} prefix, and skips parameterized URIs.
     */
    public static function getFrontendRoutes(): array
    {
        $pagesRoutes = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (!$name) {
                continue;
            }

            if (!Str::startsWith($name, ['web.', 'front.'])) {
                continue;
            }

            if (!in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();
            if (Str::startsWith($uri, '{lang?}')) {
                $uri = ltrim(substr($uri, 7), '/');
            } elseif (Str::startsWith($uri, '{lang}')) {
                $uri = ltrim(substr($uri, 6), '/');
            }

            if (Str::contains($uri, '{')) {
                continue;
            }

            $label = ($uri === '' || $uri === '/') ? '/ (Home page)' : '/' . $uri;
            $pagesRoutes[$name] = $label;
        }

        ksort($pagesRoutes);
        return $pagesRoutes;
    }

    /**
     * Get all admin route names used for permissions.
     */
    public static function getAdminRouteNames(): array
    {
        $excluded = [
            'admin.login',
            'admin.login.attempt',
            'admin.verifyOtp',
            'admin.logout',
            'admin.redirect',
            'admin.login.redirect',
        ];

        return Cache::remember('admin_route_names', 3600, function () use ($excluded) {
            return collect(app('router')->getRoutes())
                ->map->getName()
                ->filter(function ($name) use ($excluded) {
                    return $name && Str::startsWith($name, 'admin.') && !in_array($name, $excluded);
                })
                ->unique()
                ->values()
                ->all();
        });
    }
}

