<?php

namespace App\Support;

/**
 * The one registry of social channels the website knows about.
 *
 * Before this existed the same information was spelled out in four places that had
 * already drifted apart: the `social.links` setting held four platforms, the admin form
 * offered a different four, the footer's icon map named a fifth combination, and the
 * floating sidebar ignored all of them and rendered `href="#"`. Adding a channel meant
 * finding every one of those lists, and missing one was invisible until someone noticed
 * the icon never appeared.
 *
 * Everything about a platform is declared once here — its key, its label, the order it
 * is drawn in, its placeholder, and how its URL is validated. The admin form, the
 * validator, the layout service and both public components all read from this class.
 *
 * THE CANONICAL KEY FOR X IS `x`, NOT `twitter`. The setting was originally seeded with
 * `twitter` and production data still holds it, so LEGACY_ALIASES maps the old key to the
 * new one on read. Nothing migrates automatically and no URL is discarded: a value stored
 * under `twitter` keeps working forever, and is rewritten under `x` the first time an
 * administrator saves the Settings form.
 */
final class SocialPlatforms
{
    /**
     * Every supported channel, in the order the footer draws them.
     *
     * ORDER IS PART OF THE CONTRACT. It used to be whatever order the keys happened to
     * be inserted into the stored JSON, which meant the footer silently rearranged
     * itself depending on which field an administrator filled in first.
     *
     * `url_pattern` is a hint for the placeholder only — validation is the `rules` entry.
     *
     * @var array<string, array{label: string, placeholder: string, rules: list<string>}>
     */
    private const PLATFORMS = [
        'facebook' => [
            'label' => 'Facebook',
            'placeholder' => 'https://facebook.com/yourpage',
        ],
        'instagram' => [
            'label' => 'Instagram',
            'placeholder' => 'https://instagram.com/yourprofile',
        ],
        'x' => [
            'label' => 'X (Twitter)',
            'placeholder' => 'https://x.com/yourprofile',
        ],
        'linkedin' => [
            'label' => 'LinkedIn',
            'placeholder' => 'https://linkedin.com/company/yourcompany',
        ],
        'youtube' => [
            'label' => 'YouTube',
            'placeholder' => 'https://youtube.com/@yourchannel',
        ],
        'tiktok' => [
            'label' => 'TikTok',
            'placeholder' => 'https://tiktok.com/@yourprofile',
        ],
        'snapchat' => [
            'label' => 'Snapchat',
            'placeholder' => 'https://snapchat.com/add/yourprofile',
        ],
        'whatsapp' => [
            'label' => 'WhatsApp',
            'placeholder' => 'https://wa.me/966500000000',
        ],
    ];

    /**
     * Old stored key => canonical key.
     *
     * Read-only compatibility. Writes always use the canonical key.
     *
     * @var array<string, string>
     */
    public const LEGACY_ALIASES = [
        'twitter' => 'x',
    ];

    /**
     * The subset the floating sidebar draws.
     *
     * The rail is a fixed four-slot design element, not a complete directory — the
     * footer is where every configured channel appears. This is a PRESENTATION choice
     * only: both surfaces read the same URLs from `social.links`, so there is still one
     * place to configure a link.
     *
     * @var list<string>
     */
    public const SIDEBAR_PLATFORMS = ['facebook', 'x', 'youtube', 'tiktok'];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::PLATFORMS);
    }

    /**
     * @return array<string, array{label: string, placeholder: string}>
     */
    public static function all(): array
    {
        return self::PLATFORMS;
    }

    public static function label(string $platform): string
    {
        return self::PLATFORMS[$platform]['label'] ?? ucfirst($platform);
    }

    public static function placeholder(string $platform): string
    {
        return self::PLATFORMS[$platform]['placeholder'] ?? 'https://';
    }

    /**
     * Normalise a stored `social.links` array onto canonical keys.
     *
     * A legacy key is honoured only when the canonical one holds nothing, so once an
     * administrator has saved an `x` URL the stale `twitter` row can never resurrect
     * itself over the top of it.
     *
     * @param  array<string, mixed>  $stored
     * @return array<string, string> canonical key => trimmed URL, empty values dropped
     */
    public static function normalise(array $stored): array
    {
        $links = [];

        foreach (self::keys() as $platform) {
            $url = $stored[$platform] ?? null;

            if (! is_string($url) || trim($url) === '') {
                $legacyKey = array_search($platform, self::LEGACY_ALIASES, true);
                $url = $legacyKey === false ? null : ($stored[$legacyKey] ?? null);
            }

            if (! is_string($url) || trim($url) === '') {
                continue;
            }

            $links[$platform] = trim($url);
        }

        return $links;
    }
}
