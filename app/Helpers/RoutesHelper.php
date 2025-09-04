<?php

namespace App\Helpers;

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
}
