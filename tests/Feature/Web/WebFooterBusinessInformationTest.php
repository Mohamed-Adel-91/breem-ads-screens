<?php

namespace Tests\Feature\Web;

use App\Models\Admin;
use App\Models\Setting;
use App\Services\Config\DeviceConfigService;
use App\Services\LayoutService;
use App\Support\SocialPlatforms;
use Database\Seeders\ContactUsPageSeeder;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\WhoWeArePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The public footer's business information contract.
 *
 * The footer used to hold the company's address, phone number and email address as
 * literal text in the Blade template — Arabic only, with no English counterpart and no
 * way for an operator to change any of it. The map was worse: the setting stored a
 * complete `<iframe>` element and the template echoed it with `{!! !!}`, so whatever an
 * admin pasted became markup on every page of the site.
 *
 * What is pinned here:
 *
 *   - every business value the footer shows comes from Settings, in both languages;
 *   - the Arabic and English addresses are independent, and one locale's cache cannot
 *     serve the other's text;
 *   - a phone number is shown in the reader's digits and dialled in ASCII;
 *   - an unconfigured channel is ABSENT, not a dead `href="#"` icon;
 *   - the map is built from a validated URL and never from stored HTML;
 *   - saving in the admin changes what the public site says.
 *
 * Deliberately NOT asserted: the footer's layout, spacing, colours or icon artwork. This
 * task bound data to an existing design and none of it moved.
 */
class WebFooterBusinessInformationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    /**
     * The footer's phone anchor.
     *
     * `.footer-contact-link` is part of the pattern on purpose: the class is what keeps
     * the link off Bootstrap's default blue, so a phone link rendered without it is a
     * styling regression even though the href would still work.
     */
    private const TEL_LINK = '/<a class="footer-contact-link" href="tel:([^"]+)">\s*([^<]+?)\s*<\/a>/s';

    protected function setUp(): void
    {
        parent::setUp();

        // The public site renders CMS content; without pages the router 404s and every
        // assertion below would be reading an error page.
        $this->seed(HomePageSeeder::class);
        $this->seed(WhoWeArePageSeeder::class);
        $this->seed(ContactUsPageSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['settings.view', 'settings.edit', 'settings.update'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Settings',
            'last_name' => 'Tester',
            'email' => 'settings-tester@example.com',
            'password' => 'password',
            'mobile' => '1000000009',
        ]);

        $this->admin->givePermissionTo(['settings.view', 'settings.edit', 'settings.update']);
    }

    protected function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
    }

    /**
     * Write a setting through Eloquent so App\Observers\SettingObserver fires.
     *
     * A raw `DB::table()->update()` would leave the layout cache holding the old value,
     * which is exactly the staleness these tests exist to catch — so the helper models
     * the real write path rather than the convenient one.
     */
    private function putSetting(string $key, array $value): void
    {
        $setting = Setting::firstOrNew(['key' => $key]);
        // replaceTranslations(), not setTranslations(): the latter merges key by key, so a
        // key absent from $value would survive from whatever the seeder put there and the
        // test would be asserting against a state it never set up.
        $setting->replaceTranslations('value', $value);
        $setting->save();
    }

    /** master.css with comments stripped — the file documents what it replaced. */
    private function masterCss(): string
    {
        $css = file_get_contents(
            public_path(str_replace('/', DIRECTORY_SEPARATOR, 'frontend/css/master.css'))
        );

        return preg_replace('!/\*.*?\*/!s', '', $css);
    }

    private function footerOf(string $path): string
    {
        $html = $this->get($path)->getContent();

        $this->assertSame(
            1,
            preg_match('/<footer>.*?<\/footer>/s', $html, $matches),
            "[{$path}] renders no footer."
        );

        return $matches[0];
    }

    public static function localeProvider(): array
    {
        return [
            'arabic' => ['/ar'],
            'english' => ['/en'],
        ];
    }

    /** Home, Who We Are and Contact all share the layout, so the footer is on all three. */
    public static function everyPageProvider(): array
    {
        return [
            'home (ar)' => ['/ar'],
            'home (en)' => ['/en'],
            'who we are (ar)' => ['/ar/whoweare'],
            'who we are (en)' => ['/en/whoweare'],
            'contact us (ar)' => ['/ar/contact-us'],
            'contact us (en)' => ['/en/contact-us'],
        ];
    }

    // --------------------------------------------------------------------- address

    public function test_the_arabic_footer_shows_the_arabic_address_from_settings(): void
    {
        $this->putSetting('address', ['ar' => 'عنوان عربي للاختبار', 'en' => 'An English test address']);

        $footer = $this->footerOf('/ar');

        $this->assertStringContainsString('عنوان عربي للاختبار', $footer);
        $this->assertStringNotContainsString('An English test address', $footer);
    }

    public function test_the_english_footer_shows_the_english_address_from_settings(): void
    {
        $this->putSetting('address', ['ar' => 'عنوان عربي للاختبار', 'en' => 'An English test address']);

        $footer = $this->footerOf('/en');

        $this->assertStringContainsString('An English test address', $footer);
        $this->assertStringNotContainsString('عنوان عربي للاختبار', $footer);
    }

    public function test_one_locale_cache_cannot_serve_the_other_locales_address(): void
    {
        /*
         * The regression this exists for: LayoutService cached its resolved values under
         * a single key. The values are translatable, so whichever language was requested
         * first populated the cache and the other language then served ITS text — the
         * Arabic address appeared on the English site. It was invisible to the suite
         * because each test starts with an empty cache and made one request.
         */
        $this->putSetting('address', ['ar' => 'العنوان بالعربية', 'en' => 'The address in English']);

        // Arabic first, deliberately: that is the order that used to poison the cache.
        $this->assertStringContainsString('العنوان بالعربية', $this->footerOf('/ar'));

        $english = $this->footerOf('/en');
        $this->assertStringContainsString('The address in English', $english);
        $this->assertStringNotContainsString('العنوان بالعربية', $english);
    }

    #[DataProvider('localeProvider')]
    public function test_an_unset_address_renders_nothing_rather_than_the_old_hardcoded_one(string $path): void
    {
        Setting::where('key', 'address')->delete();

        $footer = $this->footerOf($path);

        // The literal that used to live in the template. Its return would mean a stale
        // contact detail is being served as though it were configuration.
        $this->assertStringNotContainsString('شارع بني تميم', $footer);
        $this->assertStringNotContainsString('12282', $footer);
    }

    // ----------------------------------------------------------------------- phone

    public function test_the_arabic_footer_shows_arabic_indic_digits_and_dials_in_ascii(): void
    {
        $footer = $this->footerOf('/ar');

        $this->assertSame(
            1,
            preg_match(self::TEL_LINK, $footer, $matches),
            'The Arabic footer must render exactly one dialable phone link.'
        );

        [, $href, $visible] = $matches;

        // Visible: the reader's digits.
        $this->assertMatchesRegularExpression(
            '/[\x{0660}-\x{0669}]/u',
            $visible,
            'The Arabic footer must show Arabic-Indic digits.'
        );

        // Machine: ASCII, and a `+` at the front where E.164 puts it. The stored Arabic
        // value is `99654334+` — a leading plus as an RTL editor lays it out — so a
        // positional read would have produced `tel:99654334+`.
        $this->assertSame('+99654334', $href);
    }

    public function test_the_english_footer_shows_western_digits(): void
    {
        $footer = $this->footerOf('/en');

        preg_match(self::TEL_LINK, $footer, $matches);

        $this->assertNotEmpty($matches, 'The English footer must render a phone link.');
        $this->assertDoesNotMatchRegularExpression(
            '/[\x{0660}-\x{0669}]/u',
            $matches[2],
            'The English footer must show Western digits.'
        );
        $this->assertSame('+99654334', $matches[1]);
    }

    public function test_a_phone_number_typed_in_arabic_digits_still_dials(): void
    {
        // An admin editing the Arabic field types what the site shows them.
        $this->putSetting('site.phone', ['ar' => '٩٦٦٥٠٠١١٢٢٣٣+', 'en' => '+966500112233']);

        preg_match('/href="tel:([^"]+)"/', $this->footerOf('/ar'), $matches);

        $this->assertSame('+966500112233', $matches[1] ?? null);
    }

    #[DataProvider('everyPageProvider')]
    public function test_no_localized_digit_ever_reaches_a_footer_href(string $path): void
    {
        preg_match_all('/href="([^"]*)"/', $this->footerOf($path), $urls);

        foreach ($urls[1] as $url) {
            $this->assertDoesNotMatchRegularExpression(
                '/[\x{0660}-\x{0669}]/u',
                $url,
                "A localized digit reached a machine URL: {$url}"
            );
        }
    }

    #[DataProvider('localeProvider')]
    public function test_an_unset_phone_renders_no_link(string $path): void
    {
        Setting::where('key', 'site.phone')->delete();

        $this->assertStringNotContainsString('tel:', $this->footerOf($path));
    }

    // ----------------------------------------------------------------------- email

    #[DataProvider('localeProvider')]
    public function test_the_email_is_shown_and_linked_from_settings(string $path): void
    {
        $this->putSetting('email', ['ar' => 'hello@breem.test', 'en' => 'hello@breem.test']);

        $footer = $this->footerOf($path);

        $this->assertStringContainsString('href="mailto:hello@breem.test"', $footer);
        $this->assertStringContainsString('hello@breem.test', $footer);
    }

    #[DataProvider('localeProvider')]
    public function test_an_unset_email_renders_no_link(string $path): void
    {
        Setting::where('key', 'email')->delete();

        $footer = $this->footerOf($path);

        $this->assertStringNotContainsString('mailto:', $footer);
        $this->assertStringNotContainsString('info@breem.com', $footer);
    }

    public function test_a_malformed_email_is_not_rendered_as_a_dead_mailto(): void
    {
        $this->putSetting('email', ['ar' => 'not-an-email', 'en' => 'not-an-email']);

        $this->assertStringNotContainsString('mailto:', $this->footerOf('/en'));
    }

    // ---------------------------------------------------------------------- social

    /** Every supported channel, with a distinguishable URL per platform. */
    private function configureEverySocialChannel(): array
    {
        $links = [
            'facebook' => 'https://facebook.com/breemtest',
            'instagram' => 'https://instagram.com/breemtest',
            'x' => 'https://x.com/breemtest',
            'linkedin' => 'https://linkedin.com/company/breemtest',
            'youtube' => 'https://youtube.com/@breemtest',
            'tiktok' => 'https://tiktok.com/@breemtest',
            'snapchat' => 'https://snapchat.com/add/breemtest',
            'whatsapp' => 'https://wa.me/966500112233',
        ];

        $this->putSetting('social.links', $links);

        return $links;
    }

    #[DataProvider('localeProvider')]
    public function test_every_configured_social_channel_is_rendered_with_its_icon(string $path): void
    {
        $links = $this->configureEverySocialChannel();

        $footer = $this->footerOf($path);

        foreach ($links as $platform => $url) {
            $this->assertStringContainsString(
                'href="' . $url . '"',
                $footer,
                "[{$platform}] is configured but its URL is not in the footer."
            );

            // The icon is an inline SVG keyed by platform, not a per-platform PNG. The
            // public site loads no icon font, so a glyph has to be inline or absent.
            $this->assertStringContainsString(
                'social-link--' . $platform,
                $footer,
                "[{$platform}] renders no icon."
            );
        }

        // Eight channels, eight anchors — nothing collapsed or duplicated.
        $this->assertSame(8, preg_match_all('/social-link--/', $footer));
    }

    public function test_the_footer_draws_social_channels_in_the_registry_order(): void
    {
        // Storage order is whatever an administrator happened to fill in first. Left to
        // associative-array insertion order the footer silently rearranges itself.
        $this->putSetting('social.links', [
            'whatsapp' => 'https://wa.me/966500112233',
            'youtube' => 'https://youtube.com/@breemtest',
            'facebook' => 'https://facebook.com/breemtest',
        ]);

        preg_match_all('/social-link--([a-z]+)/', $this->footerOf('/en'), $matches);

        $this->assertSame(['facebook', 'youtube', 'whatsapp'], $matches[1]);
    }

    public function test_an_empty_social_url_is_omitted_rather_than_linked_to_nothing(): void
    {
        $this->putSetting('social.links', [
            'facebook' => 'https://facebook.com/breemtest',
            'x' => '',
            'youtube' => null,
            'linkedin' => '   ',
        ]);

        $footer = $this->footerOf('/en');

        $this->assertStringContainsString('social-link--facebook', $footer);

        // The three unconfigured channels must leave no trace at all.
        foreach (['x', 'youtube', 'linkedin'] as $platform) {
            $this->assertStringNotContainsString('social-link--' . $platform, $footer);
        }

        $this->assertSame(1, preg_match_all('/social-link--/', $footer));
    }

    /**
     * Each newly supported channel, hidden when blank and shown when filled.
     *
     * Parameterised rather than written out four times because "configured appears,
     * unconfigured disappears" is the same contract for every platform, and the ones this
     * task added are exactly the ones with no history of being rendered.
     */
    public static function newChannelProvider(): array
    {
        return [
            'instagram' => ['instagram', 'https://instagram.com/breemtest'],
            'tiktok' => ['tiktok', 'https://tiktok.com/@breemtest'],
            'snapchat' => ['snapchat', 'https://snapchat.com/add/breemtest'],
            'whatsapp' => ['whatsapp', 'https://wa.me/966500112233'],
        ];
    }

    #[DataProvider('newChannelProvider')]
    public function test_a_channel_appears_only_once_it_is_configured(string $platform, string $url): void
    {
        // Unset: no icon, no anchor, nothing.
        $this->putSetting('social.links', [$platform => '']);

        $footer = $this->footerOf('/en');
        $this->assertStringNotContainsString('social-link--' . $platform, $footer);
        $this->assertStringNotContainsString('href="#"', $footer);

        // Configured: it appears, with its own URL.
        $this->putSetting('social.links', [$platform => $url]);

        $footer = $this->footerOf('/en');
        $this->assertStringContainsString('social-link--' . $platform, $footer);
        $this->assertStringContainsString('href="' . $url . '"', $footer);
    }

    public function test_a_url_stored_under_the_legacy_twitter_key_still_renders_as_x(): void
    {
        /*
         * `twitter` was the original key and production data still holds it. Dropping the
         * old key on rename would have silently removed a working link from the footer, so
         * it is mapped onto the canonical `x` on read.
         */
        $this->putSetting('social.links', ['twitter' => 'https://x.com/legacyhandle']);

        $footer = $this->footerOf('/en');

        $this->assertStringContainsString('social-link--x', $footer);
        $this->assertStringContainsString('href="https://x.com/legacyhandle"', $footer);
    }

    public function test_the_canonical_key_wins_over_the_legacy_one(): void
    {
        // Once an administrator has saved an `x` URL, a stale `twitter` row must never
        // resurrect itself over the top of it.
        $this->putSetting('social.links', [
            'twitter' => 'https://x.com/stale',
            'x' => 'https://x.com/current',
        ]);

        $footer = $this->footerOf('/en');

        $this->assertStringContainsString('https://x.com/current', $footer);
        $this->assertStringNotContainsString('https://x.com/stale', $footer);
    }

    #[DataProvider('everyPageProvider')]
    public function test_the_footer_never_renders_a_dead_social_link(string $path): void
    {
        $this->putSetting('social.links', array_fill_keys(SocialPlatforms::keys(), ''));

        $footer = $this->footerOf($path);

        // `href="#"` is the failure this replaced: a visitor cannot tell a placeholder
        // from a broken link, and it hides that nobody filled the field in.
        $this->assertStringNotContainsString('href="#"', $footer);
        $this->assertStringNotContainsString('social-link--', $footer);
    }

    public function test_every_social_link_opens_safely_in_a_new_tab_and_is_named(): void
    {
        $this->configureEverySocialChannel();

        $footer = $this->footerOf('/en');

        preg_match_all('/<a href="(https:\/\/[^"]+)"([^>]*)>/', $footer, $anchors, PREG_SET_ORDER);

        $external = array_values(array_filter(
            $anchors,
            fn (array $anchor): bool => str_contains($anchor[2], 'social-link--')
        ));

        $this->assertCount(8, $external, 'Every configured channel must render an anchor.');

        foreach ($external as $anchor) {
            // An external target without `noopener` hands the opened page a handle back to
            // this one.
            $this->assertStringContainsString('target="_blank"', $anchor[2]);
            $this->assertStringContainsString('rel="noopener noreferrer"', $anchor[2]);

            // The glyph is aria-hidden, so the anchor is the only accessible name there is.
            $this->assertMatchesRegularExpression(
                '/aria-label="[^"]+"/',
                $anchor[2],
                "[{$anchor[1]}] has no accessible name."
            );
        }
    }

    public function test_the_social_icons_are_inline_svg_rather_than_an_icon_font(): void
    {
        // The public site loads no icon font in its own markup and no external icon CDN is
        // permitted, so the glyphs have to be inline.
        $this->configureEverySocialChannel();

        $footer = $this->footerOf('/en');

        $this->assertSame(8, preg_match_all('/<svg class="social-icon"/', $footer));
        $this->assertStringContainsString('fill="currentColor"', $footer);

        // No Font Awesome, and no remote request for a glyph.
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bfa[srlb]?\b[^"]*fa-/', $footer);
        $this->assertDoesNotMatchRegularExpression('/<img[^>]+social/i', $footer);
    }

    // ------------------------------------------------------- the floating social rail

    public function test_the_floating_rail_reads_the_same_urls_as_the_footer(): void
    {
        /*
         * THE BUG THIS EXISTS FOR. The rail used to hold four hand-written SVGs wrapped in
         * `href="#"` while a `sidebar.icons` setting sat beside it holding the real URLs
         * that nothing read. There is one source now, so a link configured once appears in
         * both places.
         */
        $links = $this->configureEverySocialChannel();

        $html = $this->get('/en')->getContent();

        $this->assertSame(1, preg_match('/<div class="sidebar">.*?<\/div>\s*<\/div>|<div class="sidebar">.*?<\/ul>\s*<\/div>/s', $html, $matches));

        $rail = $matches[0];

        foreach (SocialPlatforms::SIDEBAR_PLATFORMS as $platform) {
            $this->assertStringContainsString(
                'href="' . $links[$platform] . '"',
                $rail,
                "The rail's [{$platform}] link does not match the configured URL."
            );
        }
    }

    public function test_the_floating_rail_hides_an_unconfigured_channel(): void
    {
        $this->putSetting('social.links', [
            'facebook' => 'https://facebook.com/breemtest',
            'youtube' => 'https://youtube.com/@breemtest',
        ]);

        $html = $this->get('/en')->getContent();

        preg_match('/<div class="sidebar">.*?<\/ul>/s', $html, $matches);
        $rail = $matches[0] ?? '';

        $this->assertStringContainsString('sidebar__item--facebook', $rail);
        $this->assertStringContainsString('sidebar__item--youtube', $rail);

        // X and TikTok are in the rail's design subset but unconfigured.
        $this->assertStringNotContainsString('sidebar__item--x', $rail);
        $this->assertStringNotContainsString('sidebar__item--tiktok', $rail);
    }

    public function test_the_rail_names_each_platform_rather_than_relying_on_position(): void
    {
        /*
         * master.css used to colour each hover state with `:nth-child(n)`, which encoded
         * "Facebook is always first". Driven by Settings that is no longer true — leaving
         * Facebook blank handed its brand blue to whichever platform moved up into slot
         * one. The class on the <li> is what makes the colour follow the platform.
         */
        $this->configureEverySocialChannel();

        $html = $this->get('/en')->getContent();
        $css = $this->masterCss();

        foreach (SocialPlatforms::SIDEBAR_PLATFORMS as $platform) {
            $this->assertStringContainsString('sidebar__item--' . $platform, $html);
            $this->assertStringContainsString('.sidebar__item--' . $platform . ':hover', $css);
        }

        $this->assertDoesNotMatchRegularExpression(
            '/\.sidebar ul li:nth-child\(\d\):hover/',
            $css,
            'Brand hover colours must not depend on an icon\'s position.'
        );
    }

    public function test_the_rail_disappears_entirely_when_nothing_is_configured(): void
    {
        // Better than an empty white rail floating over the page.
        $this->putSetting('social.links', array_fill_keys(SocialPlatforms::keys(), ''));

        $this->assertStringNotContainsString('class="sidebar"', $this->get('/en')->getContent());
    }

    // ------------------------------------------------------------------------- map

    #[DataProvider('localeProvider')]
    public function test_the_map_is_rendered_from_the_configured_url(string $path): void
    {
        $footer = $this->footerOf($path);

        $this->assertSame(
            1,
            preg_match('/<iframe src="([^"]+)"/', $footer, $matches),
            'The footer must render exactly one map frame.'
        );

        $this->assertStringStartsWith('https://www.google.com/maps/embed', $matches[1]);

        // Named, or a screen reader announces it as "frame".
        $this->assertMatchesRegularExpression('/<iframe[^>]*title="[^"]+"/', $footer);
    }

    public function test_stored_html_is_never_echoed_into_the_page(): void
    {
        /*
         * The setting still holds an `<iframe>` string — that shape is unchanged so
         * nothing which reads the key breaks. What changed is that the footer extracts
         * the `src` and builds its own element, so a payload smuggled into the stored
         * markup has nowhere to land.
         */
        $this->putSetting('map.iframe', [
            'embed' => '<iframe src="https://www.google.com/maps/embed?pb=safe"></iframe>'
                . '<script>alert(1)</script>',
        ]);

        $footer = $this->footerOf('/en');

        $this->assertStringNotContainsString('<script>', $footer);
        $this->assertStringNotContainsString('alert(1)', $footer);
        $this->assertStringContainsString('https://www.google.com/maps/embed?pb=safe', $footer);
    }

    public static function unsafeMapProvider(): array
    {
        return [
            'javascript scheme' => ['javascript:alert(1)'],
            'data scheme' => ['data:text/html,<script>alert(1)</script>'],
            'plain http' => ['http://www.google.com/maps/embed?pb=1'],
            'lookalike host' => ['https://google.com.evil.test/maps/embed?pb=1'],
            'another host entirely' => ['https://evil.test/maps/embed?pb=1'],
            'not an embed path' => ['https://www.google.com/maps/place/Riyadh'],
        ];
    }

    #[DataProvider('unsafeMapProvider')]
    public function test_an_unsafe_map_url_is_not_rendered(string $url): void
    {
        $this->putSetting('map.iframe', ['embed' => '<iframe src="' . $url . '"></iframe>']);

        $footer = $this->footerOf('/en');

        $this->assertStringNotContainsString('<iframe', $footer, "[{$url}] must not be embedded.");
        $this->assertStringNotContainsString('alert(1)', $footer);
    }

    public function test_an_unset_map_renders_no_frame(): void
    {
        Setting::where('key', 'map.iframe')->delete();

        $this->assertStringNotContainsString('<iframe', $this->footerOf('/en'));
    }

    // ------------------------------------------------------- the template itself

    public function test_the_footer_template_holds_no_business_data_and_no_queries(): void
    {
        $blade = file_get_contents(
            resource_path('views/web/layouts/components/footer.blade.php')
        );

        // No contact detail may live in the template. The logo path is the one literal
        // left, and it is reported rather than bound — see the audit document.
        foreach (['info@breem.com', 'شارع بني تميم', '12282', 'facebook.com/', 'x.com/', 'linkedin.com/'] as $literal) {
            $this->assertStringNotContainsString(
                $literal,
                $blade,
                "The footer template still hardcodes [{$literal}]."
            );
        }

        // A phone number, in either script.
        $this->assertDoesNotMatchRegularExpression(
            '/[\x{0660}-\x{0669}\x{06F0}-\x{06F9}]{4,}/u',
            $blade,
            'The footer template still hardcodes a phone number.'
        );

        // And the view does not reach for the database itself.
        foreach (['DB::', 'Setting::', '->get()', 'query()'] as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $blade,
                "The footer template must not query the database ({$needle})."
            );
        }

        // Raw output is how the map became an HTML injection point.
        $this->assertStringNotContainsString('{!!', $blade, 'The footer must escape everything it prints.');
    }

    // ------------------------------------------------------------------ efficiency

    #[DataProvider('localeProvider')]
    public function test_the_footer_costs_one_query_for_all_of_its_business_data(string $path): void
    {
        /*
         * Six values — address, email, phone, three social URLs and the map — read from
         * five setting keys. Reading them one key at a time would be five queries on
         * every page of the site, and the footer is on every page.
         */
        DB::enableQueryLog();
        $this->get($path);
        $queries = DB::getRawQueryLog();
        DB::disableQueryLog();

        $settingsQueries = array_values(array_filter(
            $queries,
            fn (array $query): bool => str_contains($query['raw_query'], 'from "settings"')
        ));

        $this->assertCount(
            1,
            $settingsQueries,
            'The layout must read every setting in one query, not one per field. Saw: '
                . implode(' | ', array_column($settingsQueries, 'raw_query'))
        );
    }

    public function test_the_business_keys_are_read_in_one_place(): void
    {
        // The service is the only reader, so the cache key and the key list cannot drift
        // apart between the header, the footer, the rail and the admin form.
        $this->assertSame(
            [
                'address',
                'email',
                'site.phone',
                'social.links',
                'map.iframe',
                'header.logo',
                'footer.logo',
                'site.lang_switch',
            ],
            LayoutService::BUSINESS_KEYS
        );

        // The superseded key is named, so nothing quietly starts reading it again.
        $this->assertSame(['sidebar.icons'], LayoutService::LEGACY_KEYS);
    }

    // ---------------------------------------------------------- contact link styling

    public function test_the_footer_contact_links_are_not_left_on_the_default_link_colour(): void
    {
        /*
         * THE DEFECT THIS PINS. Bootstrap styles every bare `a` with `--bs-link-color`,
         * and that beats the `color: #fff` the footer sets on its container — inheritance
         * does not reach an anchor that has a colour of its own. So the moment the phone
         * and email became real `tel:`/`mailto:` anchors they rendered in link blue on the
         * dark teal panel.
         *
         * The class, not a literal colour, is what is asserted: which shade the footer
         * uses is a design decision that may change, but a contact link with no rule at
         * all is always the bug coming back.
         */
        $footer = $this->footerOf('/en');

        $this->assertSame(
            2,
            preg_match_all('/class="footer-contact-link"/', $footer),
            'The phone and the email must both carry the footer contact link class.'
        );

        $css = $this->masterCss();

        $this->assertSame(
            1,
            preg_match('/footer \.footer \.footer-contact-link\s*\{([^}]*)\}/s', $css, $block),
            'The footer contact link rule is missing from master.css.'
        );

        // `color: inherit` is the fix — deliberately not a hardcoded #fff, so the links
        // follow the footer if its text colour is ever retuned.
        $this->assertMatchesRegularExpression('/color:\s*inherit/', $block[1]);

        // Interactive states exist and are not hover-only.
        $this->assertMatchesRegularExpression(
            '/\.footer-contact-link:hover,\s*footer \.footer \.footer-contact-link:focus-visible\s*\{/s',
            $css,
            'Hover and keyboard focus must be styled together.'
        );

        // And no global anchor override was introduced to achieve it. A bare `a { … }` at
        // the start of a line is what that would look like; a descendant selector ending in
        // `a` is scoped and fine.
        $this->assertDoesNotMatchRegularExpression(
            '/^\s*a\s*\{[^}]*color:/m',
            $css,
            'Link colour must be fixed in the footer scope, not globally.'
        );
    }

    public function test_the_footer_social_icons_are_styled_as_tap_targets(): void
    {
        $css = $this->masterCss();

        $this->assertSame(
            1,
            preg_match('/footer \.footer \.footer-social\s*\{([^}]*)\}/s', $css, $block),
            'The footer social rule is missing.'
        );

        $this->assertMatchesRegularExpression('/min-height:\s*44px/', $block[1], 'An icon is a tap target.');
        $this->assertMatchesRegularExpression('/color:\s*#fff/i', $block[1], 'The glyphs are drawn with currentColor.');

        // Eight icons must be able to wrap rather than overflow the column.
        $this->assertStringContainsString('footer-socials d-flex flex-wrap', $this->footerOf('/en'));
    }

    // --------------------------------------------------------------- admin round trip

    public function test_saving_in_the_admin_changes_what_the_public_footer_says(): void
    {
        $response = $this->actingAsAdmin()->put(
            route('admin.settings.update', ['lang' => 'en']),
            [
                'email' => 'newdesk@breem.test',
                'phone' => '+966511223344',
                'address' => ['ar' => 'العنوان الجديد', 'en' => 'The new address'],
                'facebook' => 'https://facebook.com/breem-new',
                'instagram' => 'https://instagram.com/breem-new',
                'x' => 'https://x.com/breem-new',
                'linkedin' => '',
                'youtube' => 'https://youtube.com/@breem-new',
                'tiktok' => 'https://tiktok.com/@breem-new',
                'snapchat' => '',
                'whatsapp' => 'https://wa.me/966511223344',
                'location' => 'https://www.google.com/maps/embed?pb=updated',
            ]
        );

        $response->assertRedirect(route('admin.settings.edit', ['lang' => 'en']));

        // English side.
        $english = $this->footerOf('/en');
        $this->assertStringContainsString('The new address', $english);
        $this->assertStringContainsString('href="mailto:newdesk@breem.test"', $english);
        $this->assertStringContainsString('href="tel:+966511223344"', $english);
        $this->assertStringContainsString('https://www.google.com/maps/embed?pb=updated', $english);

        // Every saved channel reaches the footer with EXACTLY the URL that was entered.
        foreach ([
            'facebook' => 'https://facebook.com/breem-new',
            'instagram' => 'https://instagram.com/breem-new',
            'x' => 'https://x.com/breem-new',
            'youtube' => 'https://youtube.com/@breem-new',
            'tiktok' => 'https://tiktok.com/@breem-new',
            'whatsapp' => 'https://wa.me/966511223344',
        ] as $platform => $url) {
            $this->assertStringContainsString(
                'href="' . $url . '"',
                $english,
                "[{$platform}] saved in the admin but the footer shows a different URL."
            );
        }

        // The two cleared channels are gone, not dead.
        foreach (['linkedin', 'snapchat'] as $platform) {
            $this->assertStringNotContainsString('social-link--' . $platform, $english);
        }
        $this->assertStringNotContainsString('href="#"', $english);

        // And the floating rail reflects the same save, from the same source.
        preg_match('/<div class="sidebar">.*?<\/ul>/s', $this->get('/en')->getContent(), $matches);
        $rail = $matches[0] ?? '';

        $this->assertStringContainsString('href="https://facebook.com/breem-new"', $rail);
        $this->assertStringContainsString('href="https://x.com/breem-new"', $rail);
        $this->assertStringContainsString('href="https://youtube.com/@breem-new"', $rail);
        $this->assertStringContainsString('href="https://tiktok.com/@breem-new"', $rail);

        // Arabic side gets its own address and the same machine values.
        $arabic = $this->footerOf('/ar');
        $this->assertStringContainsString('العنوان الجديد', $arabic);
        $this->assertStringNotContainsString('The new address', $arabic);
        $this->assertStringContainsString('href="tel:+966511223344"', $arabic);
    }

    public function test_saving_migrates_a_legacy_twitter_url_onto_the_canonical_key(): void
    {
        $this->putSetting('social.links', ['twitter' => 'https://x.com/legacyhandle']);

        // The form is loaded with the legacy value carried into the `x` field, and saved
        // back unchanged — the everyday case, not a special migration step.
        $this->actingAsAdmin()
            ->get(route('admin.settings.edit', ['lang' => 'en']))
            ->assertSee('value="https://x.com/legacyhandle"', false);

        $this->actingAsAdmin()->put(route('admin.settings.update', ['lang' => 'en']), [
            'x' => 'https://x.com/legacyhandle',
        ]);

        $stored = Setting::key('social.links')->first()->getTranslations('value');

        $this->assertSame('https://x.com/legacyhandle', $stored['x'] ?? null);
        $this->assertArrayNotHasKey(
            'twitter',
            $stored,
            'The retired key must not linger beside the canonical one.'
        );
    }

    public function test_the_settings_form_offers_every_business_field(): void
    {
        $response = $this->actingAsAdmin()->get(route('admin.settings.edit', ['lang' => 'en']));

        $response->assertOk();

        foreach ([
            'name="email"',
            'name="phone"',
            'name="address[ar]"',
            'name="address[en]"',
            'name="location"',
            'name="header_logo"',
            'name="footer_logo"',
            'name="lang_switch[ar]"',
            'name="lang_switch[en]"',
        ] as $field) {
            $response->assertSee($field, false);
        }

        // Every supported channel gets its own labelled box — no JSON, no raw key.
        foreach (SocialPlatforms::keys() as $platform) {
            $response->assertSee('name="' . $platform . '"', false);
            $response->assertSee(SocialPlatforms::label($platform), false);
        }

        // Always savable, even before any generic setting row exists.
        $response->assertSee('id="submit"', false);
    }

    public function test_the_settings_screen_exposes_no_raw_setting_keys(): void
    {
        /*
         * The screen used to render one text input per settings row, labelled with the key
         * itself — "Sidebar Icons / sidebar.icons / Plain text value". That asked an
         * operator to know the storage schema, and gave no way to tell a live setting from
         * a dead one.
         */
        $response = $this->actingAsAdmin()->get(route('admin.settings.edit', ['lang' => 'en']));

        foreach (array_merge(LayoutService::BUSINESS_KEYS, LayoutService::LEGACY_KEYS) as $key) {
            // Not as an input name...
            $response->assertDontSee('name="settings[' . $key . ']"', false);
            // ...and not as a visible technical label either.
            $response->assertDontSee('<code class="ml-1">' . $key . '</code>', false);
        }
    }

    public function test_a_superseded_key_is_neither_read_nor_shown(): void
    {
        // `sidebar.icons` held a parallel copy of the social URLs. The row is left alone —
        // deleting settings data to tidy a form is not a trade worth making — but nothing
        // reads it and the operator is not asked to maintain it.
        $this->putSetting('sidebar.icons', [['url' => 'https://facebook.com/from-legacy-key']]);

        $this->actingAsAdmin()
            ->get(route('admin.settings.edit', ['lang' => 'en']))
            ->assertDontSee('sidebar.icons', false);

        $this->assertStringNotContainsString(
            'from-legacy-key',
            $this->get('/en')->getContent(),
            'The superseded key must not drive anything on the public site.'
        );

        $this->assertNotNull(
            Setting::key('sidebar.icons')->first(),
            'The stored row must survive — it is the only record of what was configured.'
        );
    }

    public function test_a_managed_key_cannot_be_written_through_the_generic_bucket(): void
    {
        $this->actingAsAdmin()
            ->put(route('admin.settings.update', ['lang' => 'en']), [
                'settings' => ['map.iframe' => '<iframe src="javascript:alert(1)"></iframe>'],
            ])
            ->assertSessionHasErrors('settings.map.iframe');

        $this->assertStringNotContainsString('javascript:', $this->footerOf('/en'));
    }

    // ------------------------------------------------------------------ validation

    public static function rejectedInputProvider(): array
    {
        return [
            'javascript in a social url' => ['facebook', 'javascript:alert(1)'],
            'data uri in a social url' => ['facebook', 'data:text/html,<script>alert(1)</script>'],
            'file uri in a social url' => ['tiktok', 'file:///etc/passwd'],
            'plain http social url' => ['facebook', 'http://facebook.com/breem'],
            'not a url at all' => ['facebook', 'facebook.com/breem'],
            'javascript in snapchat' => ['snapchat', 'javascript:alert(1)'],
            'plain http instagram' => ['instagram', 'http://instagram.com/breem'],
            // WhatsApp is a click-to-chat endpoint, not a profile page.
            'whatsapp on the wrong host' => ['whatsapp', 'https://example.test/966500000000'],
            'whatsapp with no number' => ['whatsapp', 'https://wa.me/'],
            'whatsapp with a handle' => ['whatsapp', 'https://wa.me/breem'],
            'javascript in whatsapp' => ['whatsapp', 'javascript:alert(1)'],
            'a map url from another host' => ['location', 'https://evil.test/maps/embed?pb=1'],
            'a map share link' => ['location', 'https://www.google.com/maps/place/Riyadh'],
            'a malformed email' => ['email', 'not-an-email'],
            'letters in a phone number' => ['phone', 'call us'],
        ];
    }

    public static function acceptedWhatsAppProvider(): array
    {
        return [
            'wa.me short link' => ['https://wa.me/966500112233'],
            'api.whatsapp.com send link' => ['https://api.whatsapp.com/send?phone=966500112233'],
            'group invite' => ['https://chat.whatsapp.com/AbCdEfGhIjK'],
        ];
    }

    #[DataProvider('acceptedWhatsAppProvider')]
    public function test_the_documented_whatsapp_formats_are_accepted(string $url): void
    {
        $this->actingAsAdmin()
            ->put(route('admin.settings.update', ['lang' => 'en']), ['whatsapp' => $url])
            ->assertSessionHasNoErrors();

        $this->assertStringContainsString('href="' . $url . '"', $this->footerOf('/en'));
    }

    #[DataProvider('rejectedInputProvider')]
    public function test_unsafe_or_malformed_business_input_is_rejected(string $field, string $value): void
    {
        $this->actingAsAdmin()
            ->put(route('admin.settings.update', ['lang' => 'en']), [$field => $value])
            ->assertSessionHasErrors($field);
    }

    public function test_an_arabic_phone_number_is_accepted_by_the_form(): void
    {
        // The admin sees Arabic-Indic digits on the site, so they may well type them.
        $this->actingAsAdmin()
            ->put(route('admin.settings.update', ['lang' => 'ar']), ['phone' => '٩٦٦٥٠٠١١٢٢٣٣+'])
            ->assertSessionHasNoErrors();
    }

    // -------------------------------------------------------------------- branding

    /**
     * Both logos, uploaded through the admin and rendered on the public site.
     *
     * The upload goes to the temporary root TestCase installs, so no real media is
     * touched — see config('media.upload_root').
     */
    public static function logoProvider(): array
    {
        //     form field      setting key      the markup that carries it
        return [
            'header' => ['header_logo', 'header.logo', '/<a class="navbar-brand[^>]*>\s*<img src="([^"]+)"/s'],
            'footer' => ['footer_logo', 'footer.logo', '/<div class="logo-footer">\s*<img src="([^"]+)"/s'],
        ];
    }

    #[DataProvider('logoProvider')]
    public function test_a_logo_uploaded_in_the_admin_appears_on_the_public_site(
        string $field,
        string $key,
        string $pattern
    ): void {
        $this->actingAsAdmin()
            ->put(route('admin.settings.update', ['lang' => 'en']), [
                $field => UploadedFile::fake()->image('new-brand.png', 400, 120),
            ])
            ->assertSessionHasNoErrors();

        $stored = Setting::key($key)->first()->getTranslations('value');

        $this->assertStringStartsWith(
            'cms/branding/',
            $stored['image_path'] ?? '',
            'The upload must land in the managed media folder, not at an arbitrary path.'
        );

        preg_match($pattern, $this->get('/en')->getContent(), $matches);

        $this->assertNotEmpty($matches[1] ?? null, 'The logo is not rendered.');
        $this->assertStringContainsString($stored['image_path'], $matches[1]);
    }

    #[DataProvider('logoProvider')]
    public function test_an_unconfigured_logo_falls_back_to_the_design_asset(
        string $field,
        string $key,
        string $pattern
    ): void {
        // No stored row at all — the site must still draw a logo rather than a broken
        // image, and the fallback must NOT have been written into the database to achieve
        // it.
        Setting::where('key', $key)->delete();

        preg_match($pattern, $this->get('/en')->getContent(), $matches);

        $this->assertNotEmpty($matches[1] ?? null, 'No logo rendered at all.');
        $this->assertStringContainsString(
            basename(LayoutService::LOGO_FALLBACKS[$key]),
            $matches[1]
        );

        $this->assertNull(
            Setting::key($key)->first(),
            'The fallback belongs in presentation; it must not create a settings row.'
        );
    }

    public function test_replacing_a_logo_preserves_its_alternative_text(): void
    {
        // The alt is the company name in each language. It is not on the form, because
        // asking an operator to retype it every time they change a picture is how it ends
        // up blank.
        $this->putSetting('header.logo', [
            'image_path' => 'img/logo.png',
            'alt' => ['ar' => 'بريم', 'en' => 'Breem'],
        ]);

        $this->actingAsAdmin()->put(route('admin.settings.update', ['lang' => 'en']), [
            'header_logo' => UploadedFile::fake()->image('replacement.png'),
        ]);

        $stored = Setting::key('header.logo')->first()->getTranslations('value');

        $this->assertSame(['ar' => 'بريم', 'en' => 'Breem'], $stored['alt']);
        $this->assertStringContainsString('cms/branding/', $stored['image_path']);

        $this->assertStringContainsString('alt="Breem"', $this->get('/en')->getContent());
        $this->assertStringContainsString('alt="بريم"', $this->get('/ar')->getContent());
    }

    public static function rejectedLogoProvider(): array
    {
        return [
            // An SVG is a script-carrying document; every logo this project ships is raster.
            'svg' => ['payload.svg', 'image/svg+xml'],
            'html pretending to be an image' => ['payload.html', 'text/html'],
            'php' => ['shell.php', 'application/x-php'],
        ];
    }

    #[DataProvider('rejectedLogoProvider')]
    public function test_a_logo_upload_rejects_a_file_that_is_not_a_raster_image(
        string $filename,
        string $mime
    ): void {
        $before = Setting::key('header.logo')->first()->getTranslations('value');

        $this->actingAsAdmin()
            ->put(route('admin.settings.update', ['lang' => 'en']), [
                'header_logo' => UploadedFile::fake()->create($filename, 8, $mime),
            ])
            ->assertSessionHasErrors('header_logo');

        // The previous logo is still the one on the site — a rejected upload must not
        // disturb what was working.
        $this->assertSame(
            $before,
            Setting::key('header.logo')->first()->getTranslations('value')
        );
    }

    // ---------------------------------------------------------------- device settings

    public function test_the_device_language_label_is_editable_and_keeps_its_shape(): void
    {
        /*
         * `site.lang_switch` is NOT website content — it is on the Device API's
         * allow-list, so it reaches every paired screen through GET /api/v1/config. It was
         * previously shown as an unexplained "Site Lang Switch = EN" text box. It is now
         * labelled and grouped under Screen devices, and its stored shape — a plain
         * locale => label map — is unchanged, because the device contract reads it.
         */
        $this->actingAsAdmin()
            ->put(route('admin.settings.update', ['lang' => 'en']), [
                'lang_switch' => ['ar' => 'ع', 'en' => 'EN'],
            ])
            ->assertSessionHasNoErrors();

        $stored = Setting::key('site.lang_switch')->first()->getTranslations('value');

        // A flat locale => label map, which is the shape the device contract reads. Key
        // order in the stored JSON is not part of it.
        $this->assertSame('ع', $stored['ar'] ?? null);
        $this->assertSame('EN', $stored['en'] ?? null);
        $this->assertSame(['ar', 'en'], collect(array_keys($stored))->sort()->values()->all());

        $this->assertContains(
            'site.lang_switch',
            DeviceConfigService::ALLOWED_SETTING_KEYS,
            'This setting is only worth an admin control because devices receive it.'
        );
    }

    // --------------------------------------------------------------- authorization

    public function test_the_settings_screen_requires_the_settings_permission(): void
    {
        $viewer = Admin::create([
            'first_name' => 'Read',
            'last_name' => 'Only',
            'email' => 'read-only@example.com',
            'password' => 'password',
            'mobile' => '1000000010',
        ]);

        // `settings.view` alone is what the seeded `viewer` role carries. It must not be
        // enough to change what the public website says.
        $viewer->givePermissionTo('settings.view');

        $this->actingAs($viewer, 'admin')
            ->get(route('admin.settings.edit', ['lang' => 'en']))
            ->assertForbidden();

        $this->actingAs($viewer, 'admin')
            ->put(route('admin.settings.update', ['lang' => 'en']), ['email' => 'x@y.test'])
            ->assertForbidden();
    }

    public function test_the_settings_screen_is_closed_to_guests(): void
    {
        $this->get(route('admin.settings.edit', ['lang' => 'en']))->assertRedirect();
    }
}
