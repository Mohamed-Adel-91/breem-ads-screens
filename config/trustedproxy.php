<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Which upstream addresses may be believed when they say what the original
    | request looked like — its scheme, host, port and client IP — via the
    | `X-Forwarded-*` headers.
    |
    | WHY THIS FILE EXISTS. `Illuminate\Http\Middleware\TrustProxies` is already in
    | Laravel's global middleware stack and reads THIS KEY at request time. Nothing
    | had ever set it, so behind a TLS-terminating proxy — Cloudflare, an ALB, nginx
    | in front of PHP-FPM — the application saw every request as plain HTTP from the
    | proxy's own address. Three things then go quietly wrong, and none of them look
    | like a configuration error:
    |
    |   1. **URLs come out as `http://`.** `asset()` and `url()` build on the request
    |      scheme, so App\Support\MediaUrl hands devices `http://` creative URLs and
    |      admin pages load mixed content — on a deployment whose whole point is that
    |      device traffic is encrypted.
    |   2. **The session cookie loses `Secure`.** With SESSION_SECURE_COOKIE unset,
    |      Laravel derives the flag from whether the request looks secure. It does not.
    |      Set SESSION_SECURE_COOKIE=true as well; do not rely on detection alone.
    |   3. **Rate limiting collapses to one bucket.** The unauthenticated handshake
    |      limiter is keyed on `$request->ip()` at 10/minute. Every device and every
    |      attacker arrives as the proxy address, so one screen re-pairing can exhaust
    |      the budget for the entire fleet and a brute-force source is invisible.
    |
    | THIS IS DEPLOYMENT-SHAPED, SO IT IS NOT GUESSED. The default is null: trust
    | nothing, forwarded headers ignored, which is correct for a direct-to-PHP local
    | environment and is the behaviour every previous phase was tested against. Set it
    | only to match the infrastructure actually in front of the application.
    |
    |   TRUSTED_PROXIES=REMOTE_ADDR     one reverse proxy on the same host (nginx or
    |                                   Apache in front of PHP-FPM). Trusts only the
    |                                   immediate peer. Prefer this when it fits.
    |   TRUSTED_PROXIES=10.0.0.4,10.0.0.5
    |                                   an explicit load-balancer list.
    |   TRUSTED_PROXIES=*               trust whoever connected. Correct ONLY when the
    |                                   web server is unreachable except through the
    |                                   proxy — with Cloudflare that means the origin
    |                                   firewall permits Cloudflare ranges only.
    |                                   Otherwise a client can forge its own scheme
    |                                   and IP by sending X-Forwarded-* itself.
    |
    | Leaving this unset while deploying behind TLS termination is a misconfiguration,
    | not a safe default — see docs/production-env.md.
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
