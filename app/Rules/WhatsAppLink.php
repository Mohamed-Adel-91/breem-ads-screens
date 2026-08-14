<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * WhatsApp is the one social channel whose link is not a profile page.
 *
 * The other seven are `https://<platform>/<handle>` and a generic `url:https` is the
 * right check for them. WhatsApp uses a click-to-chat endpoint carrying a phone number,
 * and the two forms Meta documents are:
 *
 *   https://wa.me/966500000000
 *   https://api.whatsapp.com/send?phone=966500000000
 *
 * Accepting a bare `url:https` here would let an administrator save their WhatsApp *web*
 * session URL, or a group invite, and the footer would render a link that does nothing
 * useful on a phone. So the host is checked, and — for `wa.me` — the path is checked to
 * be a number, because `wa.me/` with no number is a dead link that still parses.
 *
 * The scheme check is the security half: `javascript:`, `data:` and `file:` never reach
 * an `href` through this field.
 */
class WhatsAppLink implements Rule
{
    /**
     * Hosts that produce a working click-to-chat link.
     *
     * @var list<string>
     */
    private const HOSTS = ['wa.me', 'www.wa.me', 'api.whatsapp.com', 'chat.whatsapp.com'];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (! is_string($value) || trim($value) === '') {
            // Emptiness is `nullable`'s job.
            return true;
        }

        $parts = parse_url(trim($value));

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (strtolower($parts['scheme']) !== 'https') {
            return false;
        }

        $host = strtolower($parts['host']);

        if (! in_array($host, self::HOSTS, true)) {
            return false;
        }

        // `wa.me/<number>` — the number is the whole point of the link.
        if ($host === 'wa.me' || $host === 'www.wa.me') {
            return (bool) preg_match('/^\/\+?\d{6,15}$/', $parts['path'] ?? '');
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.whatsapp_link');
    }
}
