<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException && $request->routeIs('admin.*')) {
            Log::warning('CSRF Mismatch (Admin)', [
                'url' => $request->fullUrl(),
                'referer' => $request->headers->get('referer'),
                'host' => $request->getHost(),
                'is_secure' => $request->isSecure(),
                'session_id' => $request->session()->getId(),
                'has_token' => $request->session()->has('_token'),
                'cookie_names' => array_keys($_COOKIE ?? []),
                'cookie_session_present' => array_key_exists(config('session.cookie'), $_COOKIE ?? []),
            ]);
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $loginUrl = route('admin.login_page');

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'login_url' => $loginUrl,
                ], 419);
            }

            return redirect()->route('admin.login_page');
        }

        if ($e instanceof TokenMismatchException && $request->routeIs('web.*')) {
            Log::warning('CSRF Mismatch (USER)', [
                'url' => $request->fullUrl(),
                'referer' => $request->headers->get('referer'),
                'host' => $request->getHost(),
                'is_secure' => $request->isSecure(),
                'session_id' => $request->session()->getId(),
                'has_token' => $request->session()->has('_token'),
                'cookie_names' => array_keys($_COOKIE ?? []),
                'cookie_session_present' => array_key_exists(config('session.cookie'), $_COOKIE ?? []),
            ]);
            Auth::guard('user')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $loginUrl = route('web.login');

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'login_url' => $loginUrl,
                ], 419);
            }

            return redirect()->route('web.login');
        }

        return parent::render($request, $e);
    }
}
