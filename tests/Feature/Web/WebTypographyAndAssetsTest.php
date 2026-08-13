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
     * The public runtime font files and the weight each is declared at.
     *
     * @var array<string, int>
     */
    private const FONT_FILES = [
        'thmanyah-sans-light.woff2' => 300,
        'thmanyah-sans-regular.woff2' => 400,
        'thmanyah-sans-medium.woff2' => 500,
        'thmanyah-sans-bold.woff2' => 700,
    ];

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

    public function test_the_two_common_weights_are_preloaded(): void
    {
        // Regular carries body copy and Bold carries navigation and headings. Light and
        // Medium are used rarely enough that preloading them would just compete for
        // bandwidth on first paint.
        $html = $this->get('/ar')->getContent();

        $this->assertStringContainsString('rel="preload"', $html);
        $this->assertStringContainsString('as="font"', $html);
        $this->assertStringContainsString('type="font/woff2"', $html);
        $this->assertStringContainsString('thmanyah-sans-regular.woff2', $html);
        $this->assertStringContainsString('thmanyah-sans-bold.woff2', $html);
    }

    // ------------------------------------------------------------------ the font files

    public function test_the_website_owns_its_own_font_binaries(): void
    {
        foreach (array_keys(self::FONT_FILES) as $file) {
            $path = $this->publicPath('frontend/fonts/thmanyah/' . $file);

            $this->assertFileExists($path, "The public site is missing [{$file}].");
            $this->assertGreaterThan(1000, filesize($path), "[{$file}] looks truncated.");
        }

        // Independent of the admin copies on purpose: the public site must not depend on
        // an admin directory. Identical bytes, separate ownership.
        foreach (array_keys(self::FONT_FILES) as $file) {
            $this->assertFileExists($this->publicPath('admin-assets/fonts/thmanyah/' . $file));
            $this->assertSame(
                md5_file($this->publicPath('admin-assets/fonts/thmanyah/' . $file)),
                md5_file($this->publicPath('frontend/fonts/thmanyah/' . $file)),
                "[{$file}] differs between the two surfaces; they must be the same binary."
            );
        }
    }

    public function test_every_font_reference_in_the_public_stylesheet_resolves(): void
    {
        $css = $this->fontsCss();

        preg_match_all('/url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/', $css, $matches);

        $this->assertCount(count(self::FONT_FILES), $matches[1], 'fonts.css must declare exactly the shipped weights.');

        foreach ($matches[1] as $reference) {
            $this->assertStringNotContainsString('://', $reference, "Remote font reference [{$reference}].");

            // Paths are relative to public/frontend/css/.
            $resolved = realpath($this->publicPath('frontend/css/') . DIRECTORY_SEPARATOR . $reference);

            $this->assertNotFalse($resolved, "fonts.css references [{$reference}], which is not on disk.");
        }
    }

    public function test_each_weight_is_declared_against_the_right_file(): void
    {
        $css = $this->fontsCss();

        foreach (self::FONT_FILES as $file => $weight) {
            $this->assertMatchesRegularExpression(
                '/' . preg_quote($file, '/') . '.*?font-weight:\s*' . $weight . '\b/s',
                $css,
                "[{$file}] must be declared at font-weight {$weight}."
            );
        }

        $this->assertStringContainsString('font-display: swap', $css);

        // Comments stripped: the file's own header explains that it is the only place
        // @font-face is declared, and that prose is not a declaration.
        $this->assertSame(
            count(self::FONT_FILES),
            substr_count(preg_replace('!/\*.*?\*/!s', '', $css), '@font-face'),
            'fonts.css must hold one @font-face per shipped weight and nothing else.'
        );
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

    public function test_the_global_typography_rule_uses_thmanyah_sans(): void
    {
        $css = $this->masterCss();

        $this->assertStringContainsString('--breem-font-family', $css);
        $this->assertStringContainsString("'Thmanyah Sans'", $css);
        $this->assertMatchesRegularExpression(
            '/html,\s*body\s*\{[^}]*font-family:\s*var\(--breem-font-family\)/s',
            $css,
            'The site-wide rule must set the family on html/body from the variable.'
        );

        // A fallback stack, so a cold cache shows platform UI text rather than a serif.
        $this->assertStringContainsString('system-ui', $css);
        $this->assertStringContainsString('sans-serif', $css);
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

    public function test_form_controls_inherit_the_site_typeface(): void
    {
        // Browsers give native controls a platform UI font unless told otherwise, and
        // Bootstrap's reboot — which normally fixes this — is loaded from a CDN here.
        $css = preg_replace('!/\*.*?\*/!s', '', $this->masterCss());

        $this->assertMatchesRegularExpression(
            '/button,\s*input,\s*optgroup,\s*select,\s*option,\s*textarea\s*\{[^}]*font-family:\s*inherit/s',
            $css,
            'Public form controls must inherit the site typeface.'
        );
    }

    public function test_bootstrap_font_variables_are_overridden(): void
    {
        // Bootstrap 5 is loaded from a CDN and reads these; overriding them catches every
        // vendor rule that uses them instead of chasing each one.
        $css = $this->masterCss();

        $this->assertStringContainsString('--bs-body-font-family: var(--breem-font-family)', $css);
        $this->assertStringContainsString('--bs-font-sans-serif: var(--breem-font-family)', $css);
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

        // And every font-family DECLARATION left in the file resolves through the one
        // variable. The lookbehind skips custom-property definitions such as
        // `--breem-font-family:` and `--bs-body-font-family:`, which are the source of
        // the value rather than a consumer of it.
        preg_match_all('/(?<![-\w])font-family:\s*([^;}]+)/', $css, $matches);

        $this->assertNotEmpty($matches[1], 'master.css declares no font-family at all.');

        foreach ($matches[1] as $value) {
            $value = trim($value);

            $this->assertTrue(
                str_contains($value, '--breem-font-family') || $value === 'inherit',
                "Unexplained font-family [{$value}] in master.css."
            );
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
