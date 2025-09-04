<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthPerson
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string $guard
     */
    public function handle(Request $request, Closure $next, string $guard = 'user')
    {
        if (!Auth::guard($guard)->check()) {

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message'   => 'Unauthenticated.',
                    'login_url' => $guard === 'admin'
                        ? route('admin.login_page')
                        : route('web.login'),
                ], 401);
            }

            return $guard === 'admin'
                ? redirect()->guest(route('admin.login_page'))->with('error', 'الرجاء تسجيل الدخول أولاً.')
                : redirect()->guest(route('web.login'))->with('error', 'الرجاء تسجيل الدخول أولاً.');
        }

        return $next($request);
    }
}
