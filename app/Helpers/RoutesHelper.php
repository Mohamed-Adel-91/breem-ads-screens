<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RoutesHelper
{
    /**
     * Get Frontend Routes.
     *
     */
    public static function getFrontendRoutes()
    {
        $pagesRoutes = [];
        foreach (Route::getRoutes() as $route) {
            // Get the action name (e.g. "App\Http\Controllers\Web\PagesController@index")
            $action = $route->getActionName();
            // Skip if the action is a Closure or not from the PagesController
            if ($action === 'Closure' || !Str::contains($action, 'App\Http\Controllers\Web\PagesController')) {
                continue;
            }
            // Filter for routes with names starting with "front."
            $name = $route->getName();
            if ($name && Str::startsWith($name, 'front.')) {
                // If it's the homepage, display "Home page" instead of the URI
                if ($name === 'front.homepage') {
                    $pagesRoutes[$name] = '/ (Home page)';
                } else {
                    $uri = $route->uri();

                    // Remove the {lang} prefix if present
                    $uriWithoutLang = Str::startsWith($uri, '{lang}')
                        ? ltrim(substr($uri, 6), '/')
                        : $uri;

                    // Skip routes that contain dynamic parameters
                    if (Str::contains($uriWithoutLang, '{')) {
                        continue;
                    }

                    $pagesRoutes[$name] = '/' . $uriWithoutLang;
                }
            }
        }
        return $pagesRoutes;
    }

    /**
     * Get all admin route names used for permissions.
     *
     * Excludes auth and redirect routes to keep the permission list clean.
     *
     * Excluded routes: admin.login, admin.login.attempt, admin.verifyOtp,
     * admin.logout, admin.redirect, admin.login.redirect.
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
