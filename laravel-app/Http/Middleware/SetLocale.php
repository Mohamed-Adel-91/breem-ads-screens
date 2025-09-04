<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->route('lang', Session::get('locale', config('app.locale')));

        App::setLocale($locale);
        Session::put('locale', $locale);

        if ($locale === 'ar') {
            setlocale(LC_ALL, 'ar_EG.UTF-8');
        } else {
            setlocale(LC_ALL, 'en_US.UTF-8');
        }

        return $next($request);
    }

    /**
     * Format a number based on the current locale.
     *
     * @param int|float $number
     * @return string
     */
    public static function formatNumber($number)
    {
        $locale = App::getLocale();
        $formatter = new \NumberFormatter($locale === 'ar' ? 'ar_EG' : 'en_US', \NumberFormatter::DECIMAL);
        return $formatter->format($number);
    }
}
