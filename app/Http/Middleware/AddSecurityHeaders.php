<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Conservative browser security headers for the admin and the public site.
 *
 * None of these were being sent. They are the three that cost nothing on a
 * server-rendered Blade application and need no per-page compatibility work:
 *
 *   - **X-Content-Type-Options: nosniff** — stop the browser second-guessing a
 *     declared Content-Type. This matters here specifically because operators upload
 *     creatives and CMS media: App\Support\CreativeMedia already derives the stored
 *     extension from sniffed magic bytes rather than the client filename, and this is
 *     the matching promise on the way back out.
 *
 *   - **Referrer-Policy: strict-origin-when-cross-origin** — admin URLs carry record
 *     ids (`/admin-panel/screens/17`), and the CMS embeds third-party map iframes.
 *     Without this, those paths travel to the third party in the Referer header.
 *
 *   - **X-Frame-Options: SAMEORIGIN** — clickjacking cover for the admin. It governs
 *     who may frame BREEM's pages; it does not restrict what Breem's own pages embed,
 *     so the CMS map iframes are unaffected.
 *
 * WHAT IS DELIBERATELY ABSENT: Content-Security-Policy. The admin ships summernote,
 * tinymce and inline handlers from `public/admin-assets`, so any policy strict enough
 * to be worth setting would need `unsafe-inline` — which buys close to nothing — or a
 * real nonce-and-refactor pass across the existing Blade views. Phase 15 does not
 * break a working admin to add a header. It stays a recommendation in
 * docs/production-launch-checklist.md.
 *
 * Existing values are never overwritten: a reverse proxy that already sets a policy
 * stays authoritative, so this cannot fight the web server.
 */
class AddSecurityHeaders
{
    /**
     * @var array<string, string>
     */
    private const HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-Frame-Options' => 'SAMEORIGIN',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $header => $value) {
            if (! $response->headers->has($header)) {
                $response->headers->set($header, $value);
            }
        }

        return $response;
    }
}
