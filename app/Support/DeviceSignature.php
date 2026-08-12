<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The single definition of how a Device API request is signed.
 *
 * Both the server (EnsureScreenAuthentication) and the test signer derive the
 * canonical message from here, so the algorithm cannot drift between them.
 *
 * Canonical message — six newline-separated lines:
 *
 *     UPPERCASE_METHOD
 *     /request/path                (no scheme, no host, no query)
 *     canonical=query&sorted=byKey (empty string when there is no query)
 *     unix-timestamp
 *     nonce
 *     sha256(raw request body)     (sha256 of "" when there is no body)
 *
 * Signature = hash_hmac('sha256', message, <per-device secret>) as lowercase hex.
 *
 * Scheme and host are deliberately excluded: they vary with proxies, ports and
 * environments and would make a signature environment-dependent. Method, path,
 * query, timestamp, nonce and body cover everything that changes meaning.
 */
class DeviceSignature
{
    public const TIMESTAMP_HEADER = 'X-Screen-Timestamp';
    public const NONCE_HEADER = 'X-Screen-Nonce';
    public const SIGNATURE_HEADER = 'X-Screen-Signature';

    /**
     * Build the canonical message for an incoming request.
     */
    public static function messageFromRequest(Request $request, string $timestamp, string $nonce): string
    {
        return self::message(
            $request->getMethod(),
            $request->getPathInfo(),
            $request->query(),
            $timestamp,
            $nonce,
            (string) $request->getContent()
        );
    }

    /**
     * Build the canonical message from its parts.
     *
     * @param  array<string, mixed>|string  $query
     */
    public static function message(
        string $method,
        string $path,
        array|string $query,
        string $timestamp,
        string $nonce,
        string $body
    ): string {
        return implode("\n", [
            strtoupper($method),
            '/'.ltrim($path, '/'),
            self::canonicalQuery($query),
            $timestamp,
            $nonce,
            hash('sha256', $body),
        ]);
    }

    /**
     * Normalise a query string: parameters sorted by key, RFC3986-encoded.
     *
     * Sorting removes ordering ambiguity between client and server, so a device
     * cannot produce a valid signature for one ordering and be rejected for
     * another.
     *
     * @param  array<string, mixed>|string  $query
     */
    public static function canonicalQuery(array|string $query): string
    {
        if (is_string($query)) {
            parse_str(ltrim($query, '?'), $parsed);
            $query = $parsed;
        }

        if (empty($query)) {
            return '';
        }

        ksort($query);

        return http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Compute a signature for a canonical message.
     */
    public static function sign(string $message, string $secret): string
    {
        return hash_hmac('sha256', $message, $secret);
    }

    /**
     * Constant-time comparison of a presented signature.
     */
    public static function matches(string $message, string $secret, string $presented): bool
    {
        if ($presented === '') {
            return false;
        }

        return hash_equals(self::sign($message, $secret), $presented);
    }

    /**
     * Generate a signing secret for a newly paired device.
     */
    public static function newSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate a bearer token for a newly paired device.
     */
    public static function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
