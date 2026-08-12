<?php

namespace App\Providers;

use App\Http\Middleware\EnsureScreenAuthentication;
use App\Models\ScreenDeviceCredential;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        /**
         * Device API traffic is keyed by the authenticated credential so that a
         * site with many screens behind one NAT address does not share a single
         * bucket. Requests that have not authenticated yet fall back to the IP.
         */
        RateLimiter::for('api.v1', function (Request $request) {
            $credential = $request->attributes->get(EnsureScreenAuthentication::REQUEST_CREDENTIAL);

            $key = $credential instanceof ScreenDeviceCredential
                ? 'device:'.$credential->id
                : 'ip:'.$request->ip();

            return Limit::perMinute(120)->by($key);
        });

        /**
         * Pairing is unauthenticated, so it gets a much tighter budget to blunt
         * brute-forcing of pairing codes. Keyed by IP only — keying by the
         * submitted screen code would let an attacker probe which codes exist.
         */
        RateLimiter::for('api.v1.handshake', function (Request $request) {
            return Limit::perMinute(10)->by('handshake:'.$request->ip());
        });
    }
}
