<?php

namespace Tests\Feature\Web;

use Database\Seeders\ContactUsPageSeeder;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\WhoWeArePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The public website's layout contract: one container, one set of gutters, and a language
 * control that names the language it switches to.
 *
 * What is pinned here is the part that regresses silently:
 *
 *   - there is ONE content-width system, and no section quietly reintroduces its own
 *     max-width or reopens the ≥1600px breakpoint that used to let the page track the
 *     viewport;
 *   - every content wrapper on every public page resolves through that system;
 *   - the language switch offers the OTHER locale, with the OTHER locale's flag, and
 *     lands on the equivalent page rather than the home page;
 *   - the flags are served from this repository, as they are for the typeface;
 *   - the mobile menu has exactly one collapse target, because two elements sharing an
 *     id is how the phone number and language switch became unreachable below 992px.
 *
 * Deliberately NOT asserted: pixel widths, computed heights, breakpoint values inside
 * clamp(). Those are design decisions and would break on every stylesheet edit. Measured
 * behaviour was verified in a real browser; see the task report.
 */
class WebResponsiveLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The public site renders CMS content; without pages the router 404s into the
        // admin-styled error view and every assertion below would be meaningless.
        $this->seed(HomePageSeeder::class);
        $this->seed(WhoWeArePageSeeder::class);
        $this->seed(ContactUsPageSeeder::class);
    }

    private function publicPath(string $relative): string
    {
        return public_path(str_replace('/', DIRECTORY_SEPARATOR, $relative));
    }

    private function masterCss(): string
    {
        return file_get_contents($this->publicPath('frontend/css/master.css'));
    }

    /** master.css with comments stripped — the file documents what it replaced. */
    private function activeCss(): string
    {
        return preg_replace('!/\*.*?\*/!s', '', $this->masterCss());
    }

    /**
     * activeCss() with `@media (...)` conditions blanked out, so a scan for declarations
     * cannot trip over the breakpoint values inside a query.
     */
    private function declarationsOnly(): string
    {
        return preg_replace('/@media[^{]*/', '@media ', $this->activeCss());
    }

    public static function publicPageProvider(): array
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

    // ------------------------------------------------------- one container system

    public function test_the_layout_tokens_are_defined_once(): void
    {
        $css = $this->activeCss();

        foreach (['--site-container-max', '--site-gutter'] as $token) {
            $this->assertStringContainsString(
                $token . ':',
                $css,
                "[{$token}] must be defined — it is the whole point of the container system."
            );
            $this->assertSame(
                1,
                preg_match_all('/(?<![-\w])' . preg_quote($token, '/') . '\s*:/', $css),
                "[{$token}] must be defined exactly once, or two definitions can disagree."
            );
        }

        // Section rhythm, so spacing is not re-invented per section.
        foreach (['--section-space-sm', '--section-space-md', '--section-space-lg'] as $token) {
            $this->assertStringContainsString($token . ':', $css, "[{$token}] is missing.");
        }
    }

    public function test_the_container_is_bounded_and_centred(): void
    {
        $css = $this->activeCss();

        // The canonical class, and Bootstrap's `.container` aliased to it so markup this
        // application does not author still lands on the site's measure.
        $this->assertMatchesRegularExpression(
            '/\.site-container,\s*\.container\s*\{/',
            $css,
            '.site-container and .container must be defined as one rule.'
        );

        $this->assertMatchesRegularExpression(
            '/\.site-container,\s*\.container\s*\{[^}]*width:\s*min\([^)]*var\(--site-gutter\)[^}]*var\(--site-container-max\)/s',
            $css,
            'The container width must derive from both tokens, not a literal.'
        );

        $this->assertMatchesRegularExpression(
            '/\.site-container,\s*\.container\s*\{[^}]*margin-inline:\s*auto/s',
            $css,
            'The container must be centred with a logical margin.'
        );
    }

    public function test_the_large_screen_stretch_override_is_gone(): void
    {
        /*
         * A `@media (min-width: 1600px)` block used to reset `.container` to
         * `max-width: -webkit-fill-available` with a 3rem margin. That single rule is what
         * made the site track the viewport on a large monitor, so it gets its own test.
         */
        $css = $this->activeCss();

        // The specific mechanism: a width reset to the viewport. Checked as a width
        // property so an unrelated `height: -webkit-fill-available` elsewhere is not
        // mistaken for this bug returning.
        $this->assertDoesNotMatchRegularExpression(
            '/(max-)?width:\s*-webkit-fill-available/',
            $css,
            'The container must not be reset to fill the viewport.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/@media[^{]*min-width:\s*1600px[^{]*\{[^}]*\.container/s',
            $css,
            'Nothing may reopen the 1600px breakpoint to widen the container.'
        );
    }

    public function test_no_section_declares_a_competing_content_width(): void
    {
        // One authoritative measure. A stray `max-width: 1200px` on a section is exactly
        // how a site ends up with four slightly different content widths. Media-query
        // conditions are blanked first — a `@media (max-width: 1120px)` breakpoint is not
        // a content container.
        $css = $this->declarationsOnly();

        $this->assertNotEmpty($css, 'The stylesheet scan produced nothing to check.');

        preg_match_all('/max-width:\s*(\d+)px/', $css, $matches);

        // Asserted as a set rather than in a loop, so the test still proves something when
        // there is nothing to iterate.
        $competing = array_values(array_filter(
            $matches[1],
            fn (string $literal): bool => (int) $literal >= 1000
        ));

        $this->assertSame(
            [],
            $competing,
            'These px max-widths look like second content containers; use --site-container-max: '
                . implode('px, ', $competing) . 'px'
        );
    }

    #[DataProvider('publicPageProvider')]
    public function test_every_public_page_wraps_its_content_in_the_canonical_container(string $path): void
    {
        $html = $this->get($path)->getContent();

        $this->assertStringContainsString(
            'class="site-container',
            $html,
            "[{$path}] renders no canonical container at all."
        );

        // And nothing falls back to a bare Bootstrap container in our own markup.
        $this->assertStringNotContainsString(
            'class="container"',
            $html,
            "[{$path}] still uses a bare Bootstrap container; use .site-container."
        );
    }

    #[DataProvider('publicPageProvider')]
    public function test_the_navbar_and_footer_inner_content_use_the_canonical_container(string $path): void
    {
        $html = $this->get($path)->getContent();

        // Navbar: the container is the flex row itself, so the two cannot drift apart.
        $this->assertMatchesRegularExpression(
            '/<div class="site-container site-navbar__inner">/',
            $html,
            "[{$path}] navbar inner wrapper is not the canonical container."
        );

        // Footer: full-width background, contained content.
        $this->assertMatchesRegularExpression(
            '/<section class="footer">.*?class="site-container"/s',
            $html,
            "[{$path}] footer content is not inside the canonical container."
        );

        // The navbar's old double wrapper is gone.
        $this->assertStringNotContainsString(
            'container-fluid',
            $html,
            "[{$path}] still nests a container-fluid, which re-expands to full width."
        );
    }

    public function test_full_width_backgrounds_are_preserved(): void
    {
        // Contained content must not mean boxed-in backgrounds. These three sections paint
        // edge to edge and their inner content is what the container bounds.
        $css = $this->activeCss();

        foreach ([
            '.media' => 'background-image',
            '.Knowmore' => 'background-image',
            'footer .footer' => 'background-image',
        ] as $selector => $property) {
            $this->assertMatchesRegularExpression(
                '/' . preg_quote($selector, '/') . '\s*\{[^}]*' . $property . '/s',
                $css,
                "[{$selector}] must keep its full-width background."
            );
        }

        // The hero is the one section that is deliberately edge to edge.
        $html = $this->get('/ar')->getContent();
        $this->assertMatchesRegularExpression(
            '/<section class="banner">\s*<div class="banner_video">/',
            $html,
            'The hero must stay full-bleed, not be wrapped in a container.'
        );
    }

    // -------------------------------------------------------------- responsive plumbing

    public function test_the_gutter_is_fluid_rather_than_repeated(): void
    {
        $this->assertMatchesRegularExpression(
            '/--site-gutter:\s*clamp\(/',
            $this->activeCss(),
            'The gutter must be a single fluid value, not a media-query ladder.'
        );
    }

    public function test_major_headings_scale_fluidly(): void
    {
        // The display sizes that used to be fixed at their desktop value. Any of these
        // going back to a bare px/rem is what makes a heading overflow a phone.
        $css = $this->activeCss();

        foreach ([
            '.Knowmore h3',
            '.where_us h3',
            '.your_ads h3',
            '.who_we h2',
            '.contact_us h2',
            '.media .box span',
        ] as $selector) {
            $this->assertMatchesRegularExpression(
                '/' . preg_quote($selector, '/') . '\s*\{[^}]*font-size:\s*clamp\(/s',
                $css,
                "[{$selector}] must scale with the viewport."
            );
        }
    }

    public function test_no_fixed_height_remains_on_a_responsive_media_box(): void
    {
        // These were flat pixel heights that were taller than a phone is wide.
        $css = $this->activeCss();

        foreach (['.location-card img', '.banner_image', '.map .back_image'] as $selector) {
            $this->assertMatchesRegularExpression(
                '/' . preg_quote($selector, '/') . '\s*\{[^}]*height:\s*clamp\(/s',
                $css,
                "[{$selector}] must not carry a fixed height."
            );
        }
    }

    public function test_direction_sensitive_rules_use_logical_properties(): void
    {
        /*
         * Each of these was a physical value with a mirrored `[lang="en"]` override — two
         * rules to maintain and one to forget. The `.pages` pair is asserted gone because
         * the navbar now places its lists with one logical margin.
         */
        $css = $this->activeCss();

        $this->assertDoesNotMatchRegularExpression(
            '/\[lang="en"\]\s*\.pages\s*\{/',
            $css,
            'The mirrored .pages margin override should have gone with the navbar rewrite.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\[lang="en"\]\s*\.map\s+\.map_content\s*\{/',
            $css,
            'The map panel should be placed with inset-inline-start, not a mirrored rule.'
        );

        $this->assertMatchesRegularExpression(
            '/\.map \.map_content\s*\{[^}]*inset-inline-start/s',
            $css,
            'The map panel must use a logical inset.'
        );

        // Indents that follow the reading direction.
        $this->assertMatchesRegularExpression(
            '/\.who_we \.bottom-desc p,\s*\.who_we ul\s*\{[^}]*margin-inline-start/s',
            $css,
            'who_we indents must follow the reading edge in both locales.'
        );

        // And no control forces one script's alignment on the other.
        $this->assertDoesNotMatchRegularExpression(
            '/text-align:\s*right\s*!important/',
            $css,
            'A form control must not force right alignment on the English page.'
        );
    }

    // -------------------------------------------------------------- the mobile menu

    #[DataProvider('publicPageProvider')]
    public function test_the_mobile_menu_has_exactly_one_collapse_target(string $path): void
    {
        /*
         * The regression this exists for: two divs both carried id="navbarTogglerDemo03",
         * so Bootstrap resolved the toggler to the first and the phone/language group could
         * never be opened below 992px.
         */
        $html = $this->get($path)->getContent();

        // Scoped to the header. The page carries pre-existing duplicate ids elsewhere —
        // the social sidebar's inlined SVGs repeat their SVGRepo_* group ids, and the four
        // contact forms share a `w3review` textarea id. Both are real defects, both are
        // outside a responsive/layout pass, and neither is what this test is about.
        $this->assertSame(
            1,
            preg_match('/<header.*?<\/header>/s', $html, $header),
            "[{$path}] renders no header."
        );

        preg_match_all('/\sid="([^"]+)"/', $header[0], $ids);
        $duplicates = array_filter(array_count_values($ids[1]), fn ($n) => $n > 1);

        $this->assertSame(
            [],
            $duplicates,
            "[{$path}] header renders a duplicate id: " . implode(', ', array_keys($duplicates))
        );

        $this->assertSame(
            1,
            preg_match_all('/id="breemPrimaryNav"/', $html),
            "[{$path}] must have exactly one collapse target."
        );

        // The toggler must point at it, and describe itself.
        $this->assertStringContainsString('data-bs-target="#breemPrimaryNav"', $html);
        $this->assertStringContainsString('aria-controls="breemPrimaryNav"', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*aria-label="[^"]+"/', $html);
    }

    #[DataProvider('publicPageProvider')]
    public function test_the_collapsed_menu_carries_the_page_links_and_the_meta_group(string $path): void
    {
        // Everything in the navbar has to be reachable on a phone, including the phone
        // number and the language control.
        $html = $this->get($path)->getContent();

        $collapse = null;
        if (preg_match('/id="breemPrimaryNav">(.*?)<\/div>\s*<\/div>\s*<\/nav>/s', $html, $m)) {
            $collapse = $m[1];
        }

        $this->assertNotNull($collapse, "[{$path}] could not locate the collapse contents.");
        $this->assertStringContainsString('site-navbar__pages', $collapse);
        $this->assertStringContainsString('site-navbar__meta', $collapse);
        $this->assertStringContainsString('lang-switch', $collapse, 'The language control must be inside the collapse.');
    }

    public function test_the_two_headers_render_from_one_shared_partial(): void
    {
        // They held two near-identical copies and had already drifted.
        foreach (['transparent-header', 'solid-header'] as $header) {
            $contents = file_get_contents(
                resource_path("views/web/layouts/components/{$header}.blade.php")
            );

            $this->assertStringContainsString(
                "@include('web.layouts.components.navbar'",
                $contents,
                "[{$header}] must delegate to the shared navbar partial."
            );
            $this->assertStringNotContainsString('<nav', $contents, "[{$header}] still holds its own markup.");
        }

        // And the variant still reaches the markup.
        $this->assertStringNotContainsString('secondheader', $this->get('/ar')->getContent());
        $this->assertStringContainsString('class="secondheader"', $this->get('/ar/whoweare')->getContent());
    }

    // ----------------------------------------------------------- the language switch

    public static function switchProvider(): array
    {
        //     path                current  target  flag      label
        return [
            'home en' => ['/en', 'en', 'ar', 'sa.svg', 'العربية', '/ar'],
            'home ar' => ['/ar', 'ar', 'en', 'us.svg', 'English', '/en'],
            'whoweare en' => ['/en/whoweare', 'en', 'ar', 'sa.svg', 'العربية', '/ar/whoweare'],
            'whoweare ar' => ['/ar/whoweare', 'ar', 'en', 'us.svg', 'English', '/en/whoweare'],
            'contact en' => ['/en/contact-us', 'en', 'ar', 'sa.svg', 'العربية', '/ar/contact-us'],
            'contact ar' => ['/ar/contact-us', 'ar', 'en', 'us.svg', 'English', '/en/contact-us'],
        ];
    }

    #[DataProvider('switchProvider')]
    public function test_the_language_switch_offers_the_other_locale_with_that_locales_flag(
        string $path,
        string $current,
        string $target,
        string $flag,
        string $label,
        string $expectedPath
    ): void {
        $html = $this->get($path)->getContent();

        $this->assertSame(
            1,
            preg_match('/<a class="nav-link lang-switch"(.*?)<\/a>/s', $html, $m),
            "[{$path}] must render exactly one language control."
        );

        $control = $m[1];

        // The target language, its flag, and its label — all three must agree.
        $this->assertStringContainsString(
            'hreflang="' . $target . '"',
            $control,
            "[{$path}] must advertise the target locale."
        );
        $this->assertStringContainsString(
            'flags/' . $flag,
            $control,
            "[{$path}] must show the {$target} flag, not the current locale's."
        );
        $this->assertStringContainsString($label, $control, "[{$path}] must be labelled [{$label}].");

        // Never the wrong pairing — the failure mode the brief calls out by name.
        $wrongFlag = $flag === 'sa.svg' ? 'us.svg' : 'sa.svg';
        $this->assertStringNotContainsString(
            'flags/' . $wrongFlag,
            $control,
            "[{$path}] pairs [{$label}] with the wrong flag."
        );
    }

    #[DataProvider('switchProvider')]
    public function test_the_language_switch_lands_on_the_equivalent_page(
        string $path,
        string $current,
        string $target,
        string $flag,
        string $label,
        string $expectedPath
    ): void {
        // Not the home page — the same page in the other language.
        $html = $this->get($path)->getContent();

        preg_match('/<a class="nav-link lang-switch"[^>]*href="([^"]+)"/s', $html, $m);

        $this->assertNotEmpty($m[1] ?? null, "[{$path}] language control has no href.");
        $this->assertSame(
            $expectedPath,
            parse_url($m[1], PHP_URL_PATH),
            "[{$path}] must switch to [{$expectedPath}]."
        );

        // And the destination is a real page, still in the expected direction.
        $this->get($expectedPath)
            ->assertOk()
            ->assertSee('dir="' . ($target === 'ar' ? 'rtl' : 'ltr') . '"', false);
    }

    public function test_the_language_switch_preserves_the_query_string(): void
    {
        $html = $this->get('/en?utm_source=news&page=2')->getContent();

        preg_match('/<a class="nav-link lang-switch"[^>]*href="([^"]+)"/s', $html, $m);
        $query = parse_url(html_entity_decode($m[1]), PHP_URL_QUERY);

        parse_str($query ?? '', $params);

        $this->assertSame('news', $params['utm_source'] ?? null, 'The query string must survive the switch.');
        $this->assertSame('2', $params['page'] ?? null);
    }

    public function test_the_language_switch_is_a_real_link_with_an_accessible_name(): void
    {
        // A real <a>, not a JS-only control, and named in the language the reader is
        // currently reading.
        foreach (['/en' => 'Switch language to Arabic', '/ar' => 'التبديل إلى الإنجليزية'] as $path => $expected) {
            $html = $this->get($path)->getContent();

            $this->assertMatchesRegularExpression(
                '/<a class="nav-link lang-switch"[^>]*aria-label="' . preg_quote($expected, '/') . '"/s',
                $html,
                "[{$path}] language control must be named [{$expected}]."
            );
        }

        // The flag is decorative — the label already names the language, so the country
        // name must not be announced on top of the aria-label.
        $html = $this->get('/en')->getContent();
        preg_match('/<a class="nav-link lang-switch"(.*?)<\/a>/s', $html, $m);

        $this->assertMatchesRegularExpression('/alt=""/', $m[1], 'The flag must be decorative.');
        $this->assertStringContainsString('aria-hidden="true"', $m[1]);
        $this->assertStringNotContainsString('alt="Saudi', $m[1]);
    }

    // ------------------------------------------------------------------ flag assets

    public function test_the_flags_are_served_from_this_repository(): void
    {
        foreach (['sa.svg' => 'Saudi Arabia', 'us.svg' => 'United States'] as $file => $country) {
            $path = $this->publicPath('frontend/img/flags/' . $file);

            $this->assertFileExists($path, "The public site is missing [{$file}].");

            $svg = file_get_contents($path);

            // Lightweight, and a real SVG rather than a renamed raster.
            $this->assertLessThan(20000, filesize($path), "[{$file}] is heavier than a UI flag needs to be.");
            $this->assertStringContainsString('<svg', $svg);
            $this->assertStringContainsString('viewBox', $svg, "[{$file}] must scale.");
            $this->assertStringContainsString($country, $svg, "[{$file}] should identify itself.");

            /*
             * No remote reference smuggled inside the asset. The SVG namespace URI is not
             * one — it is an identifier the parser matches literally and never fetches —
             * so the check targets the constructs that do cause a request.
             */
            $this->assertDoesNotMatchRegularExpression(
                '/(xlink:)?href\s*=\s*"https?:/i',
                $svg,
                "[{$file}] links to a remote document."
            );
            $this->assertStringNotContainsString('<image', $svg, "[{$file}] embeds a raster image.");
            $this->assertDoesNotMatchRegularExpression(
                '/url\(\s*[\'"]?https?:/i',
                $svg,
                "[{$file}] references a remote resource."
            );
        }
    }

    #[DataProvider('publicPageProvider')]
    public function test_no_page_loads_a_flag_from_a_remote_host_or_an_emoji(string $path): void
    {
        $html = $this->get($path)->getContent();

        preg_match_all('~src="([^"]*flags/[^"]+)"~', $html, $urls);

        $this->assertNotEmpty($urls[1], "[{$path}] renders no flag at all.");

        foreach ($urls[1] as $url) {
            $this->assertStringStartsWith(
                rtrim(config('app.url'), '/'),
                $url,
                "[{$path}] loads a flag from another host: {$url}"
            );
        }

        foreach (['flagcdn', 'flagicons', 'twemoji', 'country-flags'] as $host) {
            $this->assertStringNotContainsString($host, $html, "[{$path}] uses a remote flag service.");
        }

        // Emoji flags render differently per platform, which is why files are used.
        $this->assertStringNotContainsString('🇸🇦', $html, "[{$path}] uses an emoji flag.");
        $this->assertStringNotContainsString('🇺🇸', $html, "[{$path}] uses an emoji flag.");
    }

    public function test_the_flags_belong_to_the_public_surface(): void
    {
        // Public-site assets live under frontend/; no cross-surface dependency.
        $this->assertDirectoryExists($this->publicPath('frontend/img/flags'));
        $this->assertDirectoryDoesNotExist($this->publicPath('admin-assets/img/flags'));

        $this->assertStringContainsString(
            "asset('frontend/img/flags/",
            file_get_contents(resource_path('views/web/layouts/components/language-switch.blade.php')),
            'Flags must resolve through the public asset root.'
        );
    }

    public function test_the_flag_is_sized_as_a_label_ornament(): void
    {
        // Small enough not to dominate the control, and rounded as the brief specifies.
        $css = $this->activeCss();

        $this->assertMatchesRegularExpression(
            '/\.lang-switch__flag\s*\{[^}]*width:\s*20px/s',
            $css,
            'The flag should render at about 20px wide.'
        );
        $this->assertMatchesRegularExpression(
            '/\.lang-switch__flag\s*\{[^}]*border-radius/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/\.lang-switch\s*\{[^}]*display:\s*inline-flex/s',
            $css,
            'Flag and label must sit on one inline row.'
        );
    }

    // ---------------------------------------------------------- the contact forms

    public function test_the_service_cards_equalise_without_a_percentage_height(): void
    {
        /*
         * The bug this guards: the card carried `margin: 3rem 0` AND a full height.
         * `-webkit-fill-available` subtracts margins, `height: 100%` does not, so every
         * column overflowed by 6rem and the cards were drawn over the map section below.
         * The card must therefore own no height at all — the column stretches, the card
         * grows into it.
         */
        $css = $this->activeCss();

        $this->assertSame(
            1,
            preg_match('/\.contact_us \.contact_box\s*\{([^}]*)\}/s', $css, $block),
            'The service card rule is missing.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/(?<![-\w])height:/',
            $block[1],
            'The card must not set a height; the column stretch equalises it.'
        );
        $this->assertMatchesRegularExpression('/flex:\s*1/', $block[1], 'The card must grow into its column.');
        $this->assertMatchesRegularExpression(
            '/margin:\s*0/',
            $block[1],
            'The card margin moved to the row as a gap; as a margin it double-counted against the height.'
        );

        // The row spaces the cards, and is scoped to the direct child row so the modals'
        // own field rows are untouched.
        $this->assertMatchesRegularExpression(
            '/\.contact_us > \.site-container > \.row\s*\{[^}]*row-gap/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/\.contact_us > \.site-container > \.row > \[class\*="col-"\]\s*\{[^}]*display:\s*flex/s',
            $css
        );
    }

    public function test_each_contact_form_field_id_is_unique_across_the_page(): void
    {
        // All four modals live on the same document. They shared `id="w3review"` on their
        // details textarea, which is invalid and makes any future `for=`/JS hook ambiguous.
        $html = $this->get('/ar/contact-us')->getContent();

        preg_match_all('/<(?:input|select|textarea)[^>]*\sid="([^"]+)"/', $html, $ids);

        $duplicates = array_filter(array_count_values($ids[1]), fn ($n) => $n > 1);

        $this->assertSame(
            [],
            $duplicates,
            'Duplicate form field id: ' . implode(', ', array_keys($duplicates))
        );

        // Every label's target exists exactly once, so clicking a label selects its own
        // control rather than an identically-named one in another modal.
        preg_match_all('/<label[^>]*\sfor="([^"]+)"/', $html, $fors);

        foreach (array_unique($fors[1]) as $target) {
            $this->assertSame(
                1,
                preg_match_all('/\sid="' . preg_quote($target, '/') . '"/', $html),
                "label[for={$target}] must resolve to exactly one control."
            );
        }
    }

    public function test_the_radio_options_can_wrap_and_stay_beside_their_control(): void
    {
        // On a narrow modal `d-flex gap-3` squeezed two options into columns one word
        // wide, and the radio drifted away from the label it belongs to.
        foreach (['ads-subscribe', 'screens-subscribe'] as $partial) {
            $contents = file_get_contents(
                resource_path("views/web/pages/contact-forms/{$partial}.blade.php")
            );

            $this->assertMatchesRegularExpression(
                '/<div class="d-flex gap-3 flex-wrap">/',
                $contents,
                "[{$partial}] radio row must be allowed to wrap."
            );
        }

        $css = $this->activeCss();

        $this->assertSame(
            1,
            preg_match('/\.contact_us \.form-check\s*\{([^}]*)\}/s', $css, $block),
            'The option rule is missing.'
        );

        $this->assertMatchesRegularExpression('/align-items:\s*center/', $block[1]);
        $this->assertMatchesRegularExpression('/min-height:\s*44px/', $block[1], 'An option is a tap target.');

        // The control must not be squeezed when the row narrows.
        $this->assertMatchesRegularExpression(
            '/\.contact_us \.form-check \.form-check-input\s*\{[^}]*flex:\s*0 0 auto/s',
            $css
        );
    }

    public function test_the_modal_dismiss_control_sits_on_the_trailing_corner(): void
    {
        // `.end-0` is a physical `right: 0` in the LTR Bootstrap build this site loads, so
        // on the Arabic page it put the close button in the corner the eye starts from.
        foreach (glob(resource_path('views/web/pages/contact-forms/*.blade.php')) as $partial) {
            $contents = file_get_contents($partial);

            if (! str_contains($contents, 'btn-close')) {
                continue;
            }

            $this->assertStringContainsString(
                'modal-close-corner',
                $contents,
                basename($partial) . ' must position its close button logically.'
            );
            $this->assertStringNotContainsString(
                'top-0 end-0',
                $contents,
                basename($partial) . ' still uses physical position utilities.'
            );
        }

        $this->assertMatchesRegularExpression(
            '/\.contact_us \.modal-close-corner\s*\{[^}]*inset-inline-end:\s*0/s',
            $this->activeCss()
        );
    }

    // ------------------------------------------------------- contracts left alone

    #[DataProvider('publicPageProvider')]
    public function test_arabic_numerals_are_still_localised(string $path): void
    {
        /*
         * The header phone used to be located by matching `<a class="nav-link" href="#">`,
         * because that is what the navbar rendered. When the number became dialable that
         * selector stopped matching — and because the miss branch called
         * markTestSkipped(), this test went GREEN-BUT-SKIPPED rather than red. Six data
         * sets quietly stopped asserting anything about localisation.
         *
         * So the selector is now the contract itself: a phone link is an anchor with a
         * `tel:` href, and its absence is a failure rather than a reason to skip. The
         * seeders configure a number on every page this provider covers.
         */
        $isArabic = str_starts_with($path, '/ar');
        $html = $this->get($path)->getContent();

        $this->assertSame(
            1,
            preg_match('/<a class="nav-link site-navbar__phone"\s+href="tel:([^"]+)">\s*([^<]+?)\s*<\/a>/s', $html, $m),
            "[{$path}] renders no dialable header phone."
        );

        // The machine value, first: ASCII only, whatever the visible text does.
        $this->assertDoesNotMatchRegularExpression(
            '/[\x{0660}-\x{0669}]/u',
            $m[1],
            "[{$path}] put localized digits in a tel: target."
        );

        $phone = trim($m[2]);

        if ($isArabic) {
            $this->assertMatchesRegularExpression(
                '/[\x{0660}-\x{0669}]/u',
                $phone,
                'The Arabic header must show Arabic-Indic digits.'
            );
        } else {
            $this->assertDoesNotMatchRegularExpression(
                '/[\x{0660}-\x{0669}]/u',
                $phone,
                'The English header must show Western digits.'
            );
        }

        // Presentation only: no localised digit may reach a URL.
        preg_match_all('/(?:href|src)="([^"]*)"/', $html, $urls);
        foreach ($urls[1] as $url) {
            $this->assertDoesNotMatchRegularExpression(
                '/[\x{0660}-\x{0669}]/u',
                $url,
                "A localized digit reached a machine URL: {$url}"
            );
        }
    }

    public function test_the_typography_system_is_untouched_by_the_layout_pass(): void
    {
        // The layout pass must not have moved a single family assignment.
        $css = $this->masterCss();

        foreach ([
            '--breem-font-display' => 'Thmanyah Serif Display',
            '--breem-font-text' => 'Thmanyah Serif Text',
            '--breem-font-ui' => 'Thmanyah Sans',
        ] as $token => $family) {
            $this->assertStringContainsString("{$token}: '{$family}'", $css);
        }

        // And nothing the layout pass added names a family directly.
        preg_match_all('/(?<![-\w])font-family:\s*([^;}]+)/', $this->activeCss(), $matches);

        foreach ($matches[1] as $value) {
            $allowed = str_contains($value, '--breem-font-')
                || str_contains($value, 'monospace');

            $this->assertTrue($allowed, "Unexplained font-family [{$value}] in master.css.");
        }
    }

    public function test_the_runtime_asset_and_media_contracts_are_unchanged(): void
    {
        $this->assertDirectoryExists($this->publicPath('frontend'));
        $this->assertDirectoryExists($this->publicPath('cms'), 'public/cms is persistent CMS media.');
        $this->assertDirectoryExists($this->publicPath('upload'), 'public/upload is persistent application media.');

        $this->assertSame('frontend/img/logo.png', media_path('img/logo.png'));
        $this->assertSame('cms/example.jpg', media_path('cms/example.jpg'));

        $this->assertStringContainsString(
            "asset('frontend')",
            file_get_contents(resource_path('views/web/layouts/master.blade.php')),
            'The public layout must keep its <base href> on the frontend root.'
        );
    }

    public function test_the_layout_pass_introduced_no_build_step(): void
    {
        $html = $this->get('/en')->getContent();

        foreach (['@vite', '/build/', 'tailwind', 'x-data'] as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $html,
                "The public site must not depend on [{$needle}]."
            );
        }
    }
}
