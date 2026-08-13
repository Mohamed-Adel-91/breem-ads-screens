<?php

namespace Tests\Feature\Web;

use Database\Seeders\ContactUsPageSeeder;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\WhoWeArePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The public website's typeface and its static-asset contract.
 *
 * Thmanyah Sans is the primary typeface for the whole public site, in **both Arabic and
 * English** — one family for both scripts, by explicit product decision. What is pinned
 * here is the part that breaks silently:
 *
 *   - the font is loaded from THIS repository, never a CDN;
 *   - `fonts.css` is registered before the stylesheet that consumes the family, because
 *     the reverse order works on a warm cache and flashes the fallback on a cold one;
 *   - the site's own stylesheet no longer names a legacy family, so a heading cannot
 *     drift back to Cairo while paragraphs stay on Thmanyah;
 *   - the website owns its font binaries under `frontend/`, so the public site never
 *     acquires a dependency on an admin directory;
 *   - and the runtime path contracts — `public/frontend/`, `public/cms/`,
 *     `public/upload/` — are exactly where they were.
 *
 * Deliberately NOT asserted: metrics, pixel positions or the font binaries' contents.
 * Those are design decisions and would break on every stylesheet edit.
 */
class WebTypographyAndAssetsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The public runtime font files, per family, and the weight each is declared at.
     *
     * Three families with three jobs: Display for headings, Text for prose, Sans for
     * interface and numerals. Only the weights the site actually asks for are shipped —
     * the census behind that choice is documented in public/frontend/css/fonts.css.
     *
     * @var array<string, array<string, int>>
     */
    private const FONT_FILES = [
        'Thmanyah Serif Display' => [
            'thmanyah-serif-display-regular.woff2' => 400,
            'thmanyah-serif-display-bold.woff2' => 700,
        ],
        'Thmanyah Serif Text' => [
            'thmanyah-serif-text-regular.woff2' => 400,
            'thmanyah-serif-text-medium.woff2' => 500,
            'thmanyah-serif-text-bold.woff2' => 700,
        ],
        'Thmanyah Sans' => [
            'thmanyah-sans-regular.woff2' => 400,
            'thmanyah-sans-medium.woff2' => 500,
            'thmanyah-sans-bold.woff2' => 700,
        ],
    ];

    /**
     * Flattened file => weight map.
     *
     * @return array<string, int>
     */
    private static function allFontFiles(): array
    {
        return array_merge(...array_values(self::FONT_FILES));
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The public site renders CMS content; without pages the router 404s into the
        // admin-styled error view, which would make every assertion below meaningless.
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

    private function fontsCss(): string
    {
        return file_get_contents($this->publicPath('frontend/css/fonts.css'));
    }

    public static function publicPageProvider(): array
    {
        return [
            'home (ar)' => ['/ar', 'rtl'],
            'home (en)' => ['/en', 'ltr'],
            'who we are (ar)' => ['/ar/whoweare', 'rtl'],
            'who we are (en)' => ['/en/whoweare', 'ltr'],
            'contact us (ar)' => ['/ar/contact-us', 'rtl'],
            'contact us (en)' => ['/en/contact-us', 'ltr'],
        ];
    }

    // ------------------------------------------------------- the font reaches the page

    #[DataProvider('publicPageProvider')]
    public function test_every_public_page_loads_the_local_font_stylesheet(string $path, string $direction): void
    {
        $response = $this->get($path);

        $response->assertOk();
        $response->assertSee('frontend/css/fonts.css', false);
        $response->assertSee('dir="' . $direction . '"', false);
    }

    #[DataProvider('publicPageProvider')]
    public function test_no_public_page_requests_a_font_from_a_remote_host(string $path, string $direction): void
    {
        $html = $this->get($path)->getContent();

        foreach ([
            'font.thmanyah.com',
            'fonts.googleapis.com',
            'fonts.gstatic.com',
        ] as $host) {
            $this->assertStringNotContainsString(
                $host,
                $html,
                "[{$path}] must not load a font from [{$host}] — the binaries are in this repository."
            );
        }

        // Every Thmanyah asset URL must point at THIS application. asset() emits absolute
        // URLs, so "contains http://" proves nothing — what matters is the host.
        preg_match_all('~https?://[^"\'\s]*thmanyah[^"\'\s]*~i', $html, $urls);

        foreach ($urls[0] as $url) {
            $this->assertStringStartsWith(
                rtrim(config('app.url'), '/'),
                $url,
                "[{$path}] loads a Thmanyah asset from another host: {$url}"
            );
        }
    }

    public function test_the_active_web_layout_declares_no_remote_font_source(): void
    {
        foreach (['scripts/css.blade.php', 'scripts/js.blade.php', 'master.blade.php', 'meta/meta.blade.php'] as $partial) {
            $contents = file_get_contents(resource_path('views/web/layouts/' . $partial));

            foreach (['fonts.googleapis', 'fonts.gstatic', 'font.thmanyah.com'] as $needle) {
                $this->assertStringNotContainsString($needle, $contents, "[{$partial}] loads a remote font.");
            }
        }

        // No @import pulling a font either, in the site's own stylesheets.
        foreach (['master.css', 'fonts.css'] as $stylesheet) {
            $css = file_get_contents($this->publicPath('frontend/css/' . $stylesheet));

            $this->assertStringNotContainsString('@import', $css, "[{$stylesheet}] uses @import.");
            $this->assertStringNotContainsString('googleapis', $css);
        }
    }

    public function test_the_font_stylesheet_is_registered_before_the_stylesheet_that_uses_it(): void
    {
        $html = $this->get('/ar')->getContent();

        $fonts = strpos($html, 'frontend/css/fonts.css');
        $master = strpos($html, 'css/master.css');

        $this->assertNotFalse($fonts, 'The public font stylesheet is not loaded at all.');
        $this->assertNotFalse($master, 'The public master stylesheet is not loaded at all.');
        $this->assertLessThan(
            $master,
            $fonts,
            'fonts.css must come first, or master.css asks for a family whose @font-face is not yet registered.'
        );
    }

    public function test_one_face_per_family_is_preloaded_and_no_more(): void
    {
        /*
         * Three preloads, one per family, chosen from what actually paints first:
         *   Serif Text 400     the body default, first face on every page
         *   Serif Display 700  the first heading
         *   Sans 700           the navigation links
         *
         * The other five faces load on discovery. Preloading a face because it exists is
         * how a font system turns into a bandwidth problem, so the count is asserted too.
         */
        $html = $this->get('/ar')->getContent();

        $this->assertStringContainsString('as="font"', $html);
        $this->assertStringContainsString('type="font/woff2"', $html);

        foreach ([
            'thmanyah-serif-text-regular.woff2',
            'thmanyah-serif-display-bold.woff2',
            'thmanyah-sans-bold.woff2',
        ] as $preloaded) {
            $this->assertMatchesRegularExpression(
                '/rel="preload"[^>]*' . preg_quote($preloaded, '/') . '/',
                $html,
                "[{$preloaded}] must be preloaded."
            );
        }

        $this->assertSame(
            3,
            preg_match_all('/rel="preload"[^>]*as="font"/', $html),
            'Exactly three font faces should be preloaded — one per family.'
        );

        foreach ([
            'thmanyah-serif-display-regular.woff2',
            'thmanyah-serif-text-medium.woff2',
            'thmanyah-serif-text-bold.woff2',
            'thmanyah-sans-regular.woff2',
            'thmanyah-sans-medium.woff2',
        ] as $lazy) {
            $this->assertDoesNotMatchRegularExpression(
                '/rel="preload"[^>]*' . preg_quote($lazy, '/') . '/',
                $html,
                "[{$lazy}] is not above the fold and must not be preloaded."
            );
        }
    }

    // ------------------------------------------------------------------ the font files

    public function test_the_website_owns_its_own_font_binaries(): void
    {
        foreach (array_keys(self::allFontFiles()) as $file) {
            $path = $this->publicPath('frontend/fonts/thmanyah/' . $file);

            $this->assertFileExists($path, "The public site is missing [{$file}].");
            $this->assertGreaterThan(1000, filesize($path), "[{$file}] looks truncated.");
        }

        // Nothing unused sits in a public directory.
        $shipped = array_map('basename', glob($this->publicPath('frontend/fonts/thmanyah/') . '*.woff2'));

        $this->assertEqualsCanonicalizing(
            array_keys(self::allFontFiles()),
            $shipped,
            'public/frontend/fonts/thmanyah holds a .woff2 no stylesheet declares, or is missing one it does.'
        );
    }

    public function test_the_two_surfaces_share_binaries_but_not_directories(): void
    {
        // Where a face is used by both surfaces it must be the same bytes — otherwise the
        // two can silently drift. But each surface serves from its own directory, so the
        // public site never depends on an admin path.
        foreach (['thmanyah-sans-regular.woff2', 'thmanyah-sans-medium.woff2', 'thmanyah-sans-bold.woff2', 'thmanyah-serif-display-bold.woff2'] as $shared) {
            $admin = $this->publicPath('admin-assets/fonts/thmanyah/' . $shared);
            $web = $this->publicPath('frontend/fonts/thmanyah/' . $shared);

            $this->assertFileExists($admin);
            $this->assertFileExists($web);
            $this->assertSame(
                md5_file($admin),
                md5_file($web),
                "[{$shared}] differs between the two surfaces; they must be the same binary."
            );
        }
    }

    public function test_every_font_reference_in_the_public_stylesheet_resolves(): void
    {
        $css = $this->fontsCss();

        preg_match_all('/url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/', $css, $matches);

        $this->assertCount(
            count(self::allFontFiles()),
            $matches[1],
            'fonts.css must declare exactly the shipped faces.'
        );

        foreach ($matches[1] as $reference) {
            $this->assertStringNotContainsString('://', $reference, "Remote font reference [{$reference}].");

            // Paths are relative to public/frontend/css/.
            $resolved = realpath($this->publicPath('frontend/css/') . DIRECTORY_SEPARATOR . $reference);

            $this->assertNotFalse($resolved, "fonts.css references [{$reference}], which is not on disk.");
        }
    }

    public function test_all_three_families_are_declared_with_the_right_files_and_weights(): void
    {
        $css = $this->fontsCss();
        $stripped = preg_replace('!/\*.*?\*/!s', '', $css);

        foreach (self::FONT_FILES as $family => $files) {
            $this->assertStringContainsString(
                "font-family: '{$family}'",
                $stripped,
                "[{$family}] has no @font-face at all."
            );

            foreach ($files as $file => $weight) {
                // The file, and the weight it is declared at, in the same block.
                $this->assertMatchesRegularExpression(
                    '/' . preg_quote($file, '/') . '.*?font-weight:\s*' . $weight . '\b/s',
                    $stripped,
                    "[{$file}] must be declared at font-weight {$weight}."
                );
            }
        }

        $this->assertStringContainsString('font-display: swap', $stripped);

        $this->assertSame(
            count(self::allFontFiles()),
            substr_count($stripped, '@font-face'),
            'fonts.css must hold one @font-face per shipped face and nothing else.'
        );
    }

    public function test_no_synthetic_weight_is_faked(): void
    {
        // master.css asks for 600 once and no family ships a 600 face. That must be left
        // to CSS matching, not papered over by declaring the Bold file at `600 700` —
        // which would flatten the two weights to identical rendering.
        $stripped = preg_replace('!/\*.*?\*/!s', '', $this->fontsCss());

        $this->assertDoesNotMatchRegularExpression(
            '/font-weight:\s*\d+\s+\d+/',
            $stripped,
            'A weight range would fake a face the family does not have.'
        );

        preg_match_all('/font-weight:\s*(\d+)/', $stripped, $weights);

        foreach (array_unique($weights[1]) as $declared) {
            $this->assertContains(
                (int) $declared,
                [300, 400, 500, 700, 900],
                "Weight [{$declared}] does not correspond to a supplied Thmanyah face."
            );
        }
    }

    public function test_font_faces_are_declared_in_one_place_only(): void
    {
        // master.css used to carry its own @font-face blocks — that is how the same
        // family ends up loaded twice under two spellings.
        $this->assertStringNotContainsString(
            '@font-face',
            preg_replace('!/\*.*?\*/!s', '', $this->masterCss()),
            'master.css must not declare a font face; fonts.css is the only place.'
        );
    }

    // ------------------------------------------------------------- the typography rule

    public function test_the_three_semantic_tokens_are_defined(): void
    {
        $css = $this->masterCss();

        foreach ([
            '--breem-font-display' => 'Thmanyah Serif Display',
            '--breem-font-text' => 'Thmanyah Serif Text',
            '--breem-font-ui' => 'Thmanyah Sans',
        ] as $token => $family) {
            $this->assertMatchesRegularExpression(
                '/' . preg_quote($token, '/') . ':\s*\'' . preg_quote($family, '/') . '\'/',
                $css,
                "[{$token}] must map to [{$family}]."
            );
        }

        // Fallbacks, so a cold cache shows platform text rather than something arbitrary.
        $this->assertStringContainsString('system-ui', $css);
        $this->assertStringContainsString('sans-serif', $css);
        $this->assertStringContainsString('serif', $css);
    }

    public function test_the_body_default_is_the_reading_face(): void
    {
        // Prose is the majority of a marketing page, so Text is the default and the other
        // two are applied where they belong.
        $this->assertMatchesRegularExpression(
            '/html,\s*body\s*\{[^}]*font-family:\s*var\(--breem-font-text\)/s',
            $this->masterCss(),
            'html/body must default to the reading face.'
        );
    }

    public function test_major_headings_use_the_display_face(): void
    {
        $css = preg_replace('!/\*.*?\*/!s', '', $this->masterCss());

        $this->assertMatchesRegularExpression(
            '/(^|\})\s*h1,\s*h2,\s*h3,\s*\.font-display\s*\{[^}]*font-family:\s*var\(--breem-font-display\)/s',
            $css,
            'h1–h3 must use the display face.'
        );

        // h4–h6 are deliberately absent: on this site they behave as compact labels, and
        // `.who_we h4` is an icon-prefixed feature label rather than a headline.
        $this->assertDoesNotMatchRegularExpression(
            '/(^|\})\s*h1,\s*h2,\s*h3,\s*h4/s',
            $css,
            'h4–h6 must not be swept into the display rule by tag alone.'
        );
    }

    public function test_interface_elements_use_the_sans_face(): void
    {
        $css = preg_replace('!/\*.*?\*/!s', '', $this->masterCss());

        // One grouped rule, so the boundary between reading and operating stays visible.
        $this->assertMatchesRegularExpression(
            '/font-family:\s*var\(--breem-font-ui\)/',
            $css,
            'Nothing uses the UI face.'
        );

        foreach (['header', 'footer', 'nav', 'button', 'label', '\.pagination', '\.badge', '\.breadcrumb'] as $selector) {
            $this->assertMatchesRegularExpression(
                '/(^|,|\})\s*' . $selector . '\s*,[\s\S]{0,600}?font-family:\s*var\(--breem-font-ui\)/s',
                $css,
                "[{$selector}] must be classified as interface."
            );
        }
    }

    public function test_the_statistics_number_uses_the_sans_face(): void
    {
        // A counter is a digital metric, and Sans is where the numerals are designed to
        // live. Serif Text on a counter is explicitly wrong.
        $css = preg_replace('!/\*.*?\*/!s', '', $this->masterCss());

        $this->assertMatchesRegularExpression(
            '/\.media \.box span,\s*\.media \.box \.desc p\s*\{[^}]*font-family:\s*var\(--breem-font-ui\)/s',
            $css,
            'The statistic figure and its caption must both be Sans.'
        );
    }

    public function test_semantic_helper_classes_exist_for_exceptions(): void
    {
        // Defaults handle the common cases; classes exist so a Blade author can override
        // one block without decorating every paragraph on the site.
        $css = preg_replace('!/\*.*?\*/!s', '', $this->masterCss());

        $this->assertStringContainsString('.font-display', $css);
        $this->assertStringContainsString('.font-ui', $css);
        $this->assertStringContainsString('.font-text', $css);
    }

    public function test_the_typography_rule_does_not_use_a_universal_selector(): void
    {
        // `* { font-family: ... }` would also capture icon-font elements and their
        // ::before pseudo-elements.
        $css = preg_replace('!/\*.*?\*/!s', '', $this->masterCss());

        $this->assertDoesNotMatchRegularExpression(
            '/(^|\})\s*\*\s*\{[^}]*font-family/s',
            $css,
            'The public stylesheet must not set font-family with a universal selector.'
        );
    }

    public function test_form_controls_use_the_interface_face(): void
    {
        // Browsers give native controls a platform UI font unless told otherwise, and
        // Bootstrap's reboot — which normally fixes this — is loaded from a CDN here. They
        // are also controls, so they belong to the UI face rather than merely inheriting.
        $css = preg_replace('!/\*.*?\*/!s', '', $this->masterCss());

        foreach (['button', 'input', 'optgroup', 'select', 'option', 'textarea'] as $control) {
            $this->assertMatchesRegularExpression(
                '/(^|,|\})\s*' . $control . '\s*,[\s\S]{0,600}?font-family:\s*var\(--breem-font-ui\)/s',
                $css,
                "[{$control}] must use the interface face."
            );
        }
    }

    public function test_bootstrap_font_variables_are_mapped_to_the_right_faces(): void
    {
        // Bootstrap 5 is loaded from a CDN and reads these. Body copy is prose, so
        // --bs-body-font-family is the reading face; everything else Bootstrap styles is
        // chrome or a control, so --bs-font-sans-serif is the interface face.
        $css = $this->masterCss();

        $this->assertStringContainsString('--bs-body-font-family: var(--breem-font-text)', $css);
        $this->assertStringContainsString('--bs-font-sans-serif: var(--breem-font-ui)', $css);
    }

    public function test_no_legacy_primary_font_remains_active(): void
    {
        // Comments are stripped: the file documents what it replaced, and that prose must
        // not be mistaken for a live declaration.
        $css = preg_replace('!/\*.*?\*/!s', '', $this->masterCss());

        foreach (['cairo', 'Cairo', 'Inter', 'GE SS Two'] as $legacy) {
            $this->assertStringNotContainsString(
                $legacy,
                $css,
                "[{$legacy}] is still declared in the active public stylesheet."
            );
        }

        // And every font-family DECLARATION resolves through one of the three tokens (or
        // is the monospace stack, which is outside the Thmanyah system by design). The
        // lookbehind skips custom-property definitions, which are the source of the values
        // rather than consumers of them.
        preg_match_all('/(?<![-\w])font-family:\s*([^;}]+)/', $css, $matches);

        $this->assertNotEmpty($matches[1], 'master.css declares no font-family at all.');

        foreach ($matches[1] as $value) {
            $value = trim($value);

            $allowed = str_contains($value, '--breem-font-display')
                || str_contains($value, '--breem-font-text')
                || str_contains($value, '--breem-font-ui')
                || str_contains($value, 'monospace');

            $this->assertTrue($allowed, "Unexplained font-family [{$value}] in master.css.");
        }
    }

    // ------------------------------------------------------------- the CTA / hero section

    #[DataProvider('ctaProvider')]
    public function test_the_call_to_action_section_inherits_the_typeface(string $path): void
    {
        // The section the owner pointed at: heading, paragraph and pill button. None of
        // the three may name a family of its own, or one of them drifts.
        $html = $this->get($path)->getContent();

        $this->assertStringContainsString('class="your_ads', $html, 'The CTA section did not render.');

        $css = preg_replace('!/\*.*?\*/!s', '', $this->masterCss());

        foreach (['.your_ads h3', '.your_ads p', '.your_ads .link'] as $selector) {
            if (! preg_match('/' . preg_quote($selector, '/') . '\s*\{([^}]*)\}/s', $css, $block)) {
                continue;
            }

            $this->assertStringNotContainsString(
                'font-family',
                $block[1],
                "[{$selector}] must inherit the family, not restate it."
            );
        }
    }

    public static function ctaProvider(): array
    {
        return ['arabic' => ['/ar'], 'english' => ['/en']];
    }

    // ------------------------------------------------------------------- icon integrity

    public function test_font_awesome_declarations_are_untouched(): void
    {
        // The public site's icon stylesheet was not edited, and must not be: overriding
        // these families turns every icon into a tofu box.
        $fa = file_get_contents($this->publicPath('frontend/css/all.min.css'));

        foreach (['Font Awesome 6 Free', 'Font Awesome 6 Brands', 'FontAwesome'] as $family)
        {
            $this->assertStringContainsString($family, $fa, "Font Awesome lost its [{$family}] declaration.");
        }
    }

    public function test_the_public_site_uses_no_icon_font_in_its_own_markup(): void
    {
        // Recorded as a fact, not an aspiration: the public views use Bootstrap's
        // data-URI toggler and inline <svg>, so the typography change carries no icon
        // risk here. If an icon font is ever introduced, this test should be replaced
        // with one that proves its family survives.
        $html = $this->get('/ar')->getContent();

        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bfa[srlb]?\b[^"]*fa-/', $html);
        $this->assertStringContainsString('navbar-toggler-icon', $html);
    }

    // ------------------------------------------------------------------ path contracts

    public function test_the_public_asset_root_is_unchanged(): void
    {
        // The web layout's <base href> and media_path()'s default prefix both name this
        // directory, so its name is a runtime contract.
        $this->assertDirectoryExists($this->publicPath('frontend'));
        $this->assertFileExists($this->publicPath('frontend/css/master.css'));
        $this->assertFileExists($this->publicPath('frontend/js/main.js'));

        $this->assertSame('frontend/img/logo.png', media_path('img/logo.png'));
        $this->assertSame('frontend/assets/showreel.mp4', media_path('/assets/showreel.mp4'));
        $this->assertSame('cms/example.jpg', media_path('cms/example.jpg'));

        $this->assertStringContainsString(
            "asset('frontend')",
            file_get_contents(resource_path('views/web/layouts/master.blade.php')),
            'The public layout must keep its <base href> on the frontend root.'
        );
    }

    public function test_persistent_media_roots_are_untouched(): void
    {
        $this->assertDirectoryExists($this->publicPath('cms'), 'public/cms is persistent CMS media.');
        $this->assertDirectoryExists($this->publicPath('upload'), 'public/upload is persistent application media.');
        $this->assertFileExists($this->publicPath('images/fallback.png'));
    }

    public function test_the_admin_typeface_is_unaffected(): void
    {
        // This task changed the public site. The admin's own implementation — separate
        // stylesheet, separate font directory — must be exactly as it was.
        $this->assertFileExists($this->publicPath('admin-assets/css/fonts.css'));
        $this->assertStringContainsString(
            '--breem-font-sans',
            file_get_contents($this->publicPath('admin-assets/css/breem-admin.css'))
        );

        foreach (['eot', 'ttf', 'woff', 'svg'] as $format) {
            $this->assertFileExists($this->publicPath("admin-assets/fonts/feather.{$format}"));
        }
    }

    public function test_no_build_pipeline_was_introduced(): void
    {
        $html = $this->get('/en')->getContent();

        $this->assertStringNotContainsString('/build/', $html);
        $this->assertStringNotContainsString('@vite', $html);
        $this->assertDirectoryDoesNotExist($this->publicPath('build'));
    }
}
