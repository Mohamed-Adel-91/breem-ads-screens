<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Setting;
use App\Rules\MapEmbedUrl;
use App\Support\LocalizedDigits;
use App\Support\SocialPlatforms;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class LayoutService
{
    /**
     * Setting keys this service reads and the admin screen offers a typed control for.
     *
     * Named here because the Settings screen has to know which keys its labelled fields
     * own, so the same key is not also offered in a raw key/value box. Two editors for
     * one key means the second one saved wins, and which one that is depends on the
     * order of a foreach.
     *
     * @var list<string>
     */
    public const BUSINESS_KEYS = [
        'address',
        'email',
        'site.phone',
        'social.links',
        'map.iframe',
        'header.logo',
        'footer.logo',
        'site.lang_switch',
    ];

    /**
     * Keys that still exist but no longer drive anything, and are hidden from the admin.
     *
     * `sidebar.icons` held a parallel copy of the social URLs for the floating rail. The
     * rail now reads `social.links` like the footer does, so this key has no runtime
     * consumer. The ROW IS NOT DELETED — the stored value is the only record of what was
     * configured before, and deleting settings data to tidy a form is not a trade worth
     * making. It simply stops being read and stops being shown.
     *
     * @var list<string>
     */
    public const LEGACY_KEYS = ['sidebar.icons'];

    /**
     * Where a logo falls back to when nothing is configured.
     *
     * In PRESENTATION, not in the database. Writing a default path into a settings row to
     * create a fallback makes the seeded value indistinguishable from a deliberate choice,
     * and the next person cannot tell whether an operator picked it.
     *
     * @var array<string, string>
     */
    public const LOGO_FALLBACKS = [
        'header.logo' => 'img/logo.png',
        'footer.logo' => 'img/whitelogo.png',
    ];

    /**
     * The locales the layout cache is partitioned by.
     *
     * Same set the admin Settings controller writes and the `{lang}` route segment
     * constrains. It lives here because this class is what builds the cache keys, so
     * this is also the class that knows how to enumerate them.
     *
     * @var list<string>
     */
    public const LOCALES = ['en', 'ar'];

    public function getHeaderMenu()
    {
        return Cache::rememberForever('menu.header', function () {
            return Menu::where('location', 'header')
                ->where('is_active', true)
                ->with(['items' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->first();
        });
    }

    public function getFooterMenu()
    {
        return Cache::rememberForever('menu.footer', function () {
            return Menu::where('location', 'footer')
                ->where('is_active', true)
                ->with(['items' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->first();
        });
    }

    /**
     * The header and footer's business information, resolved for the current locale.
     *
     * ONE query per request, not one per field: every key is fetched in a single
     * `whereIn`, keyed by key, and read from memory. The result is cached, and the
     * Setting observer drops it on any save.
     *
     * THE CACHE KEY CARRIES THE LOCALE, AND THAT IS NOT DECORATION. `address`, `email`
     * and `site.phone` are translatable, so what this method returns is already-resolved
     * text for one language. A single shared key meant whichever locale rendered first
     * populated the cache and the other locale then served its strings — the Arabic
     * phone number appeared on the English site until something happened to save a
     * setting. It was invisible to the test suite because each test starts with an empty
     * cache and makes one request. App\Observers\SettingObserver clears every locale.
     *
     * @return array{
     *     phone: ?string,
     *     phone_link: ?string,
     *     email: ?string,
     *     address: ?string,
     *     social_links: array<string, string>,
     *     map_embed_url: ?string,
     *     header_logo: array{src: string, alt: string},
     *     footer_logo: array{src: string, alt: string},
     * }
     */
    public function getSettings(): array
    {
        return Cache::rememberForever('layout.settings.' . App::getLocale(), function () {
            $settings = Setting::whereIn('key', self::BUSINESS_KEYS)->get()->keyBy('key');

            $phone = $this->stringValue($settings->get('site.phone'));

            return [
                'phone' => $phone,
                'phone_link' => $this->telHref($phone),
                'email' => $this->emailValue($settings->get('email')),
                'address' => $this->stringValue($settings->get('address')),
                'social_links' => $this->socialLinks($settings->get('social.links')),
                'map_embed_url' => $this->mapEmbedUrl($settings->get('map.iframe')),
                'header_logo' => $this->logo($settings->get('header.logo'), 'header.logo'),
                'footer_logo' => $this->logo($settings->get('footer.logo'), 'footer.logo'),
            ];
        });
    }

    /**
     * A logo's resolved URL and alternative text.
     *
     * The stored shape is `{"image_path": "...", "alt": {"ar": "...", "en": "..."}}`. The
     * path runs through media_path() so an uploaded file under `cms/` and a seeded asset
     * under `frontend/img/` both resolve, and an unset or blank path falls back to the
     * design asset rather than rendering a broken image.
     *
     * @return array{src: string, alt: string}
     */
    private function logo(?Setting $setting, string $key): array
    {
        $stored = $setting?->getTranslations('value') ?? [];

        $path = $stored['image_path'] ?? null;

        if (! is_string($path) || trim($path) === '') {
            $path = self::LOGO_FALLBACKS[$key];
        }

        $alt = $stored['alt'][App::getLocale()] ?? $stored['alt'] ?? '';

        return [
            'src' => asset(media_path(trim($path))),
            'alt' => is_string($alt) ? $alt : '',
        ];
    }

    /**
     * Drop the resolved business information for every locale.
     *
     * Called by App\Observers\SettingObserver on any setting write. Clearing only the
     * editor's own locale would leave the other language serving the previous address
     * and phone number — a staleness bug that reproduces only for the half of the
     * audience the person who made the edit is not looking at.
     */
    public function flushSettingsCache(): void
    {
        foreach (self::LOCALES as $locale) {
            Cache::forget('layout.settings.' . $locale);
        }
    }

    /**
     * A translatable setting's text for the current locale, or null when unset.
     *
     * Empty string and null are collapsed into one absent case so the views can ask a
     * single question. A configured-but-blank field is not a value.
     */
    private function stringValue(?Setting $setting): ?string
    {
        $value = $setting?->value;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * The contact address, only when it is actually an address.
     *
     * A malformed value would otherwise be rendered into a `mailto:` that does nothing
     * when clicked. Failing to render is the behaviour that gets a misconfiguration
     * noticed and fixed.
     */
    private function emailValue(?Setting $setting): ?string
    {
        $email = $this->stringValue($setting);

        if ($email === null || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * The dialable form of the phone number, for a `tel:` href.
     *
     * Two things are going on:
     *
     * ARABIC-INDIC INPUT. An admin editing the Arabic field may type ٩٩٦… because that
     * is what the site shows them. `tel:` is a machine value and must be ASCII, so the
     * digits are mapped back through the one class that owns that mapping.
     *
     * THE TRAILING PLUS. The stored Arabic number is `99654334+`. That is not a typo —
     * it is what a leading `+` looks like after an RTL editor has laid the string out,
     * and it is stored in the order it was typed. Reading the `+` positionally would
     * produce `tel:99654334+`, which is not a number. So the sign is detected anywhere
     * in the string and always re-emitted at the front, where E.164 requires it.
     */
    private function telHref(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $ascii = LocalizedDigits::toAscii($phone);
        $digits = preg_replace('/\D+/', '', $ascii);

        if ($digits === '') {
            return null;
        }

        return (str_contains($ascii, '+') ? '+' : '') . $digits;
    }

    /**
     * Configured social profiles, on canonical keys, in the order they are drawn.
     *
     * The whole job is delegated to App\Support\SocialPlatforms so that the footer, the
     * floating rail, the admin form and the validator cannot disagree about which
     * channels exist, what they are called or what order they come in.
     *
     * A platform with no URL is omitted rather than emitted as `href="#"`. A dead icon is
     * indistinguishable from a broken link to a visitor, and it hides the fact that
     * nobody ever filled the field in.
     *
     * @return array<string, string>
     */
    private function socialLinks(?Setting $setting): array
    {
        return SocialPlatforms::normalise($setting?->getTranslations('value') ?? []);
    }

    /**
     * The map's `src`, extracted from the stored embed and re-validated.
     *
     * The stored shape is a full `<iframe>` string, kept as it was so nothing that reads
     * the setting has to change. What changed is that the footer no longer echoes that
     * string as HTML — it takes this URL and builds its own element.
     *
     * Revalidated on the way OUT as well as on the way in, because rows written before
     * App\Rules\MapEmbedUrl existed have never been checked by it, and a row can also be
     * written by a seeder or by hand.
     */
    private function mapEmbedUrl(?Setting $setting): ?string
    {
        $embed = $setting?->getTranslations('value')['embed'] ?? null;

        if (! is_string($embed) || trim($embed) === '') {
            return null;
        }

        $embed = trim($embed);

        // Either a bare URL or a full element — accept both, since the setting has held
        // each shape at different times.
        $url = $embed;

        if (preg_match('/\ssrc\s*=\s*"([^"]+)"/i', $embed, $matches)) {
            $url = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        }

        return (new MapEmbedUrl())->passes('map', $url) && str_starts_with($url, 'https://')
            ? $url
            : null;
    }
}
