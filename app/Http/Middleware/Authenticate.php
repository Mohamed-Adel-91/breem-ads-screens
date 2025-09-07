<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
protected function redirectTo(Request $request): ?string
{
    if (! $request->expectsJson()) {
        $lang = $request->route('lang') ?? session('locale', config('app.locale', 'en'));
        $lang = in_array($lang, ['en', 'ar']) ? $lang : 'en';
        if ($request->is("$lang/admin-panel*") || $request->is('*/admin-panel*') || $request->is('admin-panel*')) {
            return route('admin.login_page', ['lang' => $lang]);
        }
        return route('web.home');
    }

    return null;
}
}
