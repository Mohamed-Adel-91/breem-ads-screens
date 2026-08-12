<?php

namespace App\Http\Requests\Api;

use App\Http\Middleware\EnsureScreenAuthentication;
use App\Models\Screen;
use App\Models\ScreenDeviceCredential;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base for Device API requests.
 *
 * Authentication — token, timestamp, nonce and signature — is enforced by
 * EnsureScreenAuthentication before validation runs, so these classes only
 * describe payload shape. Keeping the two apart is why authentication failures
 * are now 401/403 instead of 422 validation errors.
 */
abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The screen this request was authenticated as, when the route is protected.
     */
    public function authenticatedScreen(): ?Screen
    {
        $screen = $this->attributes->get(EnsureScreenAuthentication::REQUEST_SCREEN);

        return $screen instanceof Screen ? $screen : null;
    }

    public function deviceCredential(): ?ScreenDeviceCredential
    {
        $credential = $this->attributes->get(EnsureScreenAuthentication::REQUEST_CREDENTIAL);

        return $credential instanceof ScreenDeviceCredential ? $credential : null;
    }

    /**
     * Retrieve the If-None-Match header stripped of quotes.
     */
    public function ifNoneMatch(): ?string
    {
        $etag = $this->headers->get('If-None-Match');

        if (! $etag) {
            return null;
        }

        return trim($etag, '"');
    }
}
