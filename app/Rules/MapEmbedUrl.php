<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * The footer map is an embedded third-party document, so the URL it is given is the
 * whole security boundary.
 *
 * The setting used to hold a complete `<iframe>` element that the public footer echoed
 * with `{!! !!}`. Anything an admin pasted became markup on every page of the website.
 * The stored shape is unchanged — `map.iframe` still holds `{"embed": "<iframe ...>"}`
 * so nothing that reads it breaks — but the value is now BUILT from a URL that passed
 * through here, and the footer renders its own element around the extracted `src`.
 *
 * What is enforced:
 *
 *   scheme   https only. `http` would be mixed content inside an https page; `javascript:`
 *            and `data:` are the reason this class exists at all.
 *   host     a Google Maps host. Regional domains are real (`google.com.sa`), so the
 *            check is a pattern rather than a literal list, but it is anchored at both
 *            ends — `google.com.evil.test` does not match.
 *   path     `/maps/embed`. A share link or a search URL is not an embeddable document;
 *            it renders as a refused frame, which looks like a site fault rather than a
 *            configuration mistake.
 *
 * Deliberately NOT enforced: the `pb=` payload. It is an opaque Google-generated blob
 * and validating its shape would reject valid embeds the first time Google changes it.
 */
class MapEmbedUrl implements Rule
{
    /**
     * `google.<tld>`, optionally under a `www.` or `maps.` label.
     *
     * The TLD group allows one or two labels so `google.com` and `google.com.sa` both
     * pass, and the anchors are what stop a lookalike host from matching.
     */
    private const HOST_PATTERN = '/^(?:www\.|maps\.)?google\.[a-z]{2,}(?:\.[a-z]{2,})?$/i';

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
            // Emptiness is `nullable`'s job, not this rule's.
            return true;
        }

        $parts = parse_url(trim($value));

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (strtolower($parts['scheme']) !== 'https') {
            return false;
        }

        if (! preg_match(self::HOST_PATTERN, $parts['host'])) {
            return false;
        }

        return str_starts_with($parts['path'] ?? '', '/maps/embed');
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.map_embed_url');
    }
}
