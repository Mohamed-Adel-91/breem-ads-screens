<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The admin typeface and the static-asset contract.
 *
 * Two things are pinned here, and they fail for different reasons:
 *
 *   1. **Thmanyah Sans is served from this repository.** The temptation with a webfont is
 *      always to paste the vendor's CDN <link> and move on. That would add an external
 *      dependency to every admin page view, leak the admin's traffic to a third party,
 *      and take the admin's typography down with someone else's outage. The files are
 *      here; they must be loaded from here.
 *
 *   2. **Asset paths that were reorganised stay reorganised.** A moved static file is
 *      invisible until someone opens the page — no exception, no failing request, just a
 *      missing logo. These tests are the thing that notices.
 *
 * Deliberately NOT asserted: specific CSS declarations, pixel metrics, or the contents of
 * the font binaries. Those are design decisions and would make this file break on every
 * stylesheet edit.
 */
class AdminTypographyAndAssetsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The runtime font files, and the weights fonts.css declares for them.
     *
     * @var array<string, int>
     */
    private const FONT_FILES = [
        'thmanyah-sans-light.woff2' => 300,
        'thmanyah-sans-regular.woff2' => 400,
        'thmanyah-sans-medium.woff2' => 500,
        'thmanyah-sans-bold.woff2' => 700,
    ];

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['reports.view', 'reports.generate'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Typography',
            'last_name' => 'Tester',
            'email' => 'admin-typography@example.com',
            'password' => 'password',
            'mobile' => '9900000001',
        ]);
        $this->admin->givePermissionTo(['reports.view', 'reports.generate']);
    }

    private function publicPath(string $relative): string
    {
        return public_path(str_replace('/', DIRECTORY_SEPARATOR, $relative));
    }

    // ------------------------------------------------------------- font is loaded

    public static function adminSurfaceProvider(): array
    {
        return [
            // The two admin layouts: master (authenticated) and auth (login).
            'authenticated admin' => ['reports', true],
            'login page' => ['login', false],
        ];
    }

    #[DataProvider('adminSurfaceProvider')]
    public function test_every_admin_surface_loads_the_local_font_stylesheet(string $surface, bool $authenticated): void
    {
        $response = $this->visit($surface, $authenticated);

        $response->assertOk();
        $response->assertSee('admin-assets/css/fonts.css', false);
    }

    #[DataProvider('adminSurfaceProvider')]
    public function test_no_admin_surface_requests_a_font_from_a_remote_host(string $surface, bool $authenticated): void
    {
        $html = $this->visit($surface, $authenticated)->getContent();

        foreach ([
            'font.thmanyah.com',
            'fonts.googleapis.com',
            'fonts.gstatic.com',
            'cdn.jsdelivr.net',
            'cdnjs.cloudflare.com',
            'unpkg.com',
        ] as $host) {
            $this->assertStringNotContainsString(
                $host,
                $html,
                "The admin must not load anything from [{$host}] — every asset it needs is in this repository."
            );
        }
    }

    public function test_the_font_is_preloaded_rather_than_discovered_late(): void
    {
        // Without a preload the browser only learns it needs the font after parsing the
        // 300 KB theme stylesheet, so the admin renders in the fallback face and reflows.
        $html = $this->visit('reports', true)->getContent();

        $this->assertStringContainsString('rel="preload"', $html);
        $this->assertStringContainsString('thmanyah-sans-regular.woff2', $html);
        $this->assertStringContainsString('as="font"', $html);
    }

    // ------------------------------------------------------------- font files exist

    public function test_every_font_file_declared_by_the_stylesheet_exists(): void
    {
        $cssPath = $this->publicPath('admin-assets/css/fonts.css');

        $this->assertFileExists($cssPath, 'The admin font stylesheet is missing.');

        $css = file_get_contents($cssPath);

        preg_match_all('/url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/', $css, $matches);

        $this->assertNotEmpty($matches[1], 'fonts.css declares no font files at all.');

        foreach ($matches[1] as $reference) {
            $this->assertStringNotContainsString(
                '://',
                $reference,
                "fonts.css must not reference a remote font [{$reference}]."
            );

            // Paths are relative to public/admin-assets/css/.
            $resolved = realpath($this->publicPath('admin-assets/css/') . DIRECTORY_SEPARATOR . $reference);

            $this->assertNotFalse(
                $resolved,
                "fonts.css references [{$reference}], which does not exist on disk."
            );
        }
    }

    public function test_the_declared_weights_are_the_ones_shipped(): void
    {
        $css = file_get_contents($this->publicPath('admin-assets/css/fonts.css'));

        foreach (self::FONT_FILES as $file => $weight) {
            $this->assertFileExists($this->publicPath('admin-assets/fonts/thmanyah/' . $file));
            $this->assertStringContainsString($file, $css, "[{$file}] is shipped but never declared.");
            $this->assertMatchesRegularExpression(
                '/' . preg_quote($file, '/') . '.*?font-weight:\s*' . $weight . '\b/s',
                $css,
                "[{$file}] must be declared at font-weight {$weight}."
            );
        }

        // Nothing unused sits in a public directory.
        $shipped = glob($this->publicPath('admin-assets/fonts/thmanyah/') . '*.woff2');

        $this->assertCount(
            count(self::FONT_FILES),
            $shipped,
            'public/admin-assets/fonts/thmanyah holds a .woff2 no stylesheet declares.'
        );
    }

    public function test_the_font_licence_travels_with_the_font(): void
    {
        // Removing a licence file as "cleanup" is not cleanup.
        $this->assertFileExists($this->publicPath('admin-assets/fonts/thmanyah/LICENSE.pdf'));
        $this->assertFileExists($this->publicPath('admin-assets/fonts/thmanyah/README.md'));
    }

    // --------------------------------------------------------------- typography rule

    public function test_the_typeface_is_applied_once_centrally(): void
    {
        $css = file_get_contents($this->publicPath('admin-assets/css/breem-admin.css'));

        $this->assertStringContainsString('--breem-font-sans', $css);
        $this->assertStringContainsString("'Thmanyah Sans'", $css);

        // A fallback stack, so a cache miss shows platform UI text rather than a serif.
        $this->assertStringContainsString('system-ui', $css);
        $this->assertStringContainsString('sans-serif', $css);

        // The previous stack must be gone, or the admin silently keeps rendering in it.
        $this->assertStringNotContainsString(
            'font-family: Tahoma',
            $css,
            'The old Tahoma stack still wins somewhere in breem-admin.css.'
        );
    }

    // -------------------------------------------------------------------- icon fonts

    public function test_the_icon_font_is_still_loaded_and_still_owns_its_selector(): void
    {
        $response = $this->visit('reports', true);

        $response->assertOk();
        $response->assertSee('admin-assets/css/feather.css', false);

        $feather = file_get_contents($this->publicPath('admin-assets/css/feather.css'));

        // `.fe` must keep the icon family. A global font-family sweep that caught this
        // selector would turn every icon in the admin into a tofu box.
        $this->assertMatchesRegularExpression(
            '/\.fe\s*\{[^}]*font-family:\s*"feather"\s*!important/s',
            $feather
        );

        // The icon font binaries live beside the Thmanyah directory, not inside it, and
        // feather.css reaches them with ../fonts/feather.* — so they must stay put.
        foreach (['eot', 'ttf', 'woff', 'svg'] as $format) {
            $this->assertFileExists($this->publicPath("admin-assets/fonts/feather.{$format}"));
        }
    }

    public function test_the_admin_still_renders_icon_markup(): void
    {
        $response = $this->visit('reports', true);

        $response->assertOk();
        // Feather icons are used through the `fe fe-*` classes; if the markup stopped
        // emitting them the stylesheet check above would pass while the UI lost its icons.
        $this->assertMatchesRegularExpression('/class="[^"]*\bfe\b[^"]*fe-/', $response->getContent());
    }

    // ------------------------------------------------------------ both locales render

    public static function localeProvider(): array
    {
        return [
            'english' => ['en', 'ltr'],
            'arabic' => ['ar', 'rtl'],
        ];
    }

    #[DataProvider('localeProvider')]
    public function test_the_admin_renders_in_both_locales_with_the_font(string $locale, string $direction): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index', ['lang' => $locale]));

        $response->assertOk();
        $response->assertSee('dir="' . $direction . '"', false);
        $response->assertSee('admin-assets/css/fonts.css', false);
    }

    /**
     * Every admin surface an operator actually works in, in both locales.
     *
     * The font is applied by a single rule in a stylesheet the shared layout loads, so in
     * principle one page proves it. In practice a page that quietly uses a different
     * layout — as `404.blade.php` does — is exactly how a typeface ends up missing from
     * one screen, so the list is walked rather than argued about.
     */
    public static function adminPageProvider(): array
    {
        $pages = [
            'dashboard' => 'admin.dashboard',
            'ads' => 'admin.ads.index',
            'screens' => 'admin.screens.index',
            'places' => 'admin.places.index',
            'monitoring' => 'admin.monitoring.index',
            'reports' => 'admin.reports.index',
        ];

        $cases = [];

        foreach ($pages as $label => $route) {
            foreach (['en' => 'ltr', 'ar' => 'rtl'] as $locale => $direction) {
                $cases["{$label} ({$locale})"] = [$route, $locale, $direction];
            }
        }

        return $cases;
    }

    #[DataProvider('adminPageProvider')]
    public function test_the_font_reaches_every_admin_page_in_both_locales(string $route, string $locale, string $direction): void
    {
        foreach ([
            'ads.view', 'screens.view', 'places.view', 'monitoring.view', 'reports.view',
        ] as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        $this->admin->givePermissionTo(['ads.view', 'screens.view', 'places.view', 'monitoring.view']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->actingAs($this->admin, 'admin')->get(route($route, ['lang' => $locale]));

        $response->assertOk();
        $response->assertSee('admin-assets/css/fonts.css', false);
        $response->assertSee('dir="' . $direction . '"', false);

        // The typography rule is scoped to this body class; without it the page renders in
        // whatever the vendor theme asks for instead.
        $response->assertSee('breem-admin', false);
    }

    public function test_arabic_loads_the_rtl_stylesheet_after_the_font(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index', ['lang' => 'ar']))
            ->getContent();

        $fontPosition = strpos($html, 'admin-assets/css/fonts.css');
        $rtlPosition = strpos($html, 'admin-assets/css/app-rtl.css');

        $this->assertNotFalse($fontPosition);
        $this->assertNotFalse($rtlPosition, 'Arabic must still load the RTL stylesheet.');
        $this->assertLessThan(
            $rtlPosition,
            $fontPosition,
            'The @font-face declarations must be registered before the rules that use them.'
        );
    }

    // ------------------------------------------------------- moved and removed assets

    public static function relocatedAssetProvider(): array
    {
        return [
            'admin logo' => ['admin-assets/images/breem-logo.png'],
            'admin favicon' => ['admin-assets/images/favicon.ico'],
            'default avatar' => ['admin-assets/images/default-avatar.jpg'],
        ];
    }

    #[DataProvider('relocatedAssetProvider')]
    public function test_a_relocated_admin_asset_exists_at_its_new_path(string $relative): void
    {
        $this->assertFileExists($this->publicPath($relative));
    }

    public function test_the_admin_references_its_logo_and_favicon_at_the_new_paths(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index', ['lang' => 'en']))
            ->assertOk()
            ->assertSee('admin-assets/images/breem-logo.png', false)
            ->assertSee('admin-assets/images/favicon.ico', false);
    }

    public function test_no_view_points_at_a_removed_static_path(): void
    {
        $removed = [
            // Flattened into admin-assets/images/.
            'admin-assets/assets',
            // Proven-dead duplicate admin theme.
            'new-panel-assets',
            // Archive-style font package directory, normalised to fonts/thmanyah/.
            'Thmanyah-Font-Family',
        ];

        $offenders = [];

        $views = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($views as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            foreach ($removed as $path) {
                if (str_contains($contents, $path)) {
                    $offenders[] = $file->getPathname() . ' -> ' . $path;
                }
            }
        }

        $this->assertSame([], $offenders, "A view still points at a removed static path:\n" . implode("\n", $offenders));
    }

    // ------------------------------------------------------------- persistent media

    public function test_persistent_media_roots_are_untouched(): void
    {
        // These hold user and client data and are addressed by paths stored in the
        // database. An asset-organisation task must never move them.
        $this->assertDirectoryExists($this->publicPath('cms'), 'public/cms is persistent CMS media.');
        $this->assertDirectoryExists($this->publicPath('upload'), 'public/upload is persistent application media.');

        // The ads fallback creative is served to devices from config('ads.fallback.image').
        // The configured value may carry a leading slash, so compare on the resolved
        // public path rather than on the raw string.
        $configured = ltrim((string) config('ads.fallback.image'), '/');

        $this->assertSame('images/fallback.png', $configured);
        $this->assertFileExists(
            $this->publicPath($configured),
            'The configured ads fallback creative must remain reachable.'
        );
    }

    public function test_the_public_website_asset_root_is_untouched(): void
    {
        // The web layout sets <base href=".../frontend/"> and media_path() prefixes bare
        // stored paths with "frontend/", so this directory name is a runtime contract.
        $this->assertDirectoryExists($this->publicPath('frontend'));
        $this->assertFileExists($this->publicPath('frontend/css/master.css'));
        $this->assertSame('frontend/img/logo.png', media_path('img/logo.png'));
        $this->assertSame('frontend/assets/showreel.mp4', media_path('/assets/showreel.mp4'));
        $this->assertSame('cms/example.jpg', media_path('cms/example.jpg'));
    }

    // ------------------------------------------------------- public site is untouched

    /**
     * Asserted against the web layout's own partials rather than a rendered page.
     *
     * Rendering `/en` needs seeded CMS pages, and without them the router falls through
     * to `404.blade.php` — which legitimately uses the ADMIN layout and therefore does
     * contain `admin-assets`. Testing the rendered 404 would report the opposite of the
     * truth. The partials are what actually changed, so they are what is pinned.
     */
    public function test_the_public_website_never_loads_admin_assets(): void
    {
        /*
         * SUPERSEDED ASSERTION, KEPT AS THE INVARIANT THAT SURVIVED IT.
         *
         * This test used to assert that the public website did NOT use Thmanyah — the
         * admin was the only surface that had it. The project owner has since made
         * Thmanyah Sans the primary typeface for the public site too, in Arabic and
         * English, so asserting the two differ would now pin a rule that no longer
         * exists. The test was not deleted, because what it was really protecting is
         * still true and still worth protecting: **the two surfaces are independent.**
         *
         * Each owns its own runtime font copies — the admin under
         * `admin-assets/fonts/thmanyah/`, the website under `frontend/fonts/thmanyah/` —
         * so the public site never acquires a semantic dependency on an admin directory,
         * and reorganising one surface's assets cannot break the other. The public side
         * is covered in full by Tests\Feature\Web\WebTypographyAndAssetsTest.
         */
        foreach (['scripts/css.blade.php', 'master.blade.php', 'scripts/js.blade.php'] as $partial) {
            $contents = file_get_contents(resource_path('views/web/layouts/' . $partial));

            $this->assertStringNotContainsString(
                'admin-assets',
                $contents,
                "[{$partial}] must not load anything from the admin asset root."
            );
        }

        // The website's typeface comes from its own directory, not the admin's. Comments
        // are stripped: both public stylesheets document the separation in prose, and that
        // prose names `admin-assets` precisely to say it is not used.
        $stripComments = static fn (string $css): string => preg_replace('!/\*.*?\*/!s', '', $css);

        $master = $stripComments(file_get_contents($this->publicPath('frontend/css/master.css')));
        $fonts = $stripComments(file_get_contents($this->publicPath('frontend/css/fonts.css')));

        $this->assertStringContainsString('Thmanyah Sans', $master, 'The public site should be set in Thmanyah Sans.');
        $this->assertStringNotContainsString('admin-assets', $master);

        $this->assertStringContainsString('../fonts/thmanyah/', $fonts);
        $this->assertStringNotContainsString('admin-assets', $fonts);
    }

    public function test_the_public_website_favicon_is_generated_with_the_asset_helper(): void
    {
        // It used to be a relative href, resolved against <base href=".../frontend/">
        // into /frontend/img/global/logo.ico — a directory that does not exist — so the
        // site served a 404 and overrode the working absolute favicon from meta.blade.php.
        $css = file_get_contents(resource_path('views/web/layouts/scripts/css.blade.php'));

        $this->assertStringContainsString("asset('favicon.ico')", $css);
        $this->assertStringNotContainsString('href="img/global/logo.ico"', $css);

        $this->assertFileExists($this->publicPath('favicon.ico'));
    }

    // ----------------------------------------------------------- css url() integrity

    public function test_every_url_in_the_active_admin_stylesheets_resolves(): void
    {
        $cssDir = $this->publicPath('admin-assets/css');
        $missing = [];

        foreach (glob($cssDir . DIRECTORY_SEPARATOR . '*.css') as $file) {
            $css = file_get_contents($file);

            preg_match_all('/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i', $css, $matches);

            foreach (array_unique($matches[2]) as $reference) {
                $reference = trim($reference);

                // data: URIs carry their own payload; there is nothing to resolve.
                if ($reference === '' || preg_match('~^(data:|https?:|//|\#)~i', $reference)) {
                    continue;
                }

                // Vendor stylesheets append cache-busting queries and SVG fragments.
                $clean = preg_replace('/[?\#].*$/', '', $reference);

                if ($clean === '' || realpath($cssDir . DIRECTORY_SEPARATOR . $clean) !== false) {
                    continue;
                }

                $missing[] = basename($file) . ' -> ' . $reference;
            }
        }

        $this->assertSame([], $missing, "A stylesheet points at a file that is not there:\n" . implode("\n", $missing));
    }

    // ------------------------------------------------------------- Laravel scaffold

    public static function scaffoldProvider(): array
    {
        return [
            ['package.json'],
            ['vite.config.js'],
            ['postcss.config.js'],
            ['tailwind.config.js'],
            ['resources/js/app.js'],
            ['resources/js/bootstrap.js'],
            ['resources/css/app.css'],
        ];
    }

    /**
     * Standard Laravel scaffold is retained even with zero runtime consumers. Asset
     * reorganisation is exactly the kind of task that would otherwise sweep it away.
     */
    #[DataProvider('scaffoldProvider')]
    public function test_the_laravel_scaffold_file_is_still_present(string $relative): void
    {
        $path = base_path(str_replace('/', DIRECTORY_SEPARATOR, $relative));

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path), "[{$relative}] must not be emptied.");
    }

    public function test_the_admin_is_not_wired_to_a_build_pipeline(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index', ['lang' => 'en']))
            ->getContent();

        // No Vite, no build manifest: the admin is static assets served from public/.
        $this->assertStringNotContainsString('/build/', $html);
        $this->assertStringNotContainsString('@vite', $html);
        $this->assertDirectoryDoesNotExist($this->publicPath('build'));
    }

    // -------------------------------------------------------------------- helper

    private function visit(string $surface, bool $authenticated)
    {
        if ($surface === 'login') {
            return $this->get(route('admin.login', ['lang' => 'en']));
        }

        $request = $authenticated ? $this->actingAs($this->admin, 'admin') : $this;

        return $request->get(route('admin.reports.index', ['lang' => 'en']));
    }
}
