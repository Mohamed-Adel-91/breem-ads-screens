<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Number;

class SetLocaleFromRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $allowedLocales = ['en', 'ar'];

        $locale = $request->route('lang');

        if (!is_string($locale) || !in_array($locale, $allowedLocales, true)) {
            $locale = Session::get('locale', config('app.locale'));
        }

        if (!is_string($locale) || !in_array($locale, $allowedLocales, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        app()->instance('isRtl', $locale === 'ar');

        Carbon::setLocale($locale);
        Number::useLocale($locale);

        return $next($request);
    }
}
