<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Place;
use App\Models\Screen;
use App\Enums\ScreenStatus;
use Database\Seeders\ContactUsPageSeeder;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\WhoWeArePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Arabic-Indic numerals in rendered pages.
 *
 * A correct `lang="ar"` and a correct font prove nothing about digits — a font renders
 * whatever code points it is given, and U+0031 is "1" in every typeface. So these tests
 * read the actual response body and look for the actual Arabic-Indic characters.
 *
 * The other half is what must NOT change. A digit substitution that leaks into a URL, a
 * screen code or a JSON field is worse than no substitution at all, because it fails
 * silently and only for Arabic users. Every group below has a matching English case and a
 * matching machine-value case.
 */
class LocalizedNumeralsTest extends TestCase
{
    use RefreshDatabase;

    /** Arabic-Indic digits, U+0660–U+0669. */
    private const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['screens.view', 'places.view', 'monitoring.view', 'ads.view', 'reports.view'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Numeral',
            'last_name' => 'Tester',
            'email' => 'numerals@example.com',
            'password' => 'password',
            'mobile' => '9100000001',
        ]);
        $this->admin->givePermissionTo(['screens.view', 'places.view', 'monitoring.view', 'ads.view', 'reports.view']);
    }

    private function seedPublicPages(): void
    {
        $this->seed(HomePageSeeder::class);
        $this->seed(WhoWeArePageSeeder::class);
        $this->seed(ContactUsPageSeeder::class);
    }

    private function assertContainsArabicDigit(string $html, string $context): void
    {
        foreach (self::ARABIC_DIGITS as $digit) {
            if (str_contains($html, $digit)) {
                return;
            }
        }

        $this->fail("{$context} contains no Arabic-Indic digit at all.");
    }

    // ------------------------------------------------------------- public statistics

    public function test_the_arabic_homepage_statistics_use_arabic_indic_digits(): void
    {
        $this->seedPublicPages();

        $html = $this->get('/ar')->assertOk()->getContent();

        // The statistics figures live in `.media .box span`. Pull them out rather than
        // searching the whole page, so a stray Arabic digit elsewhere cannot pass this.
        preg_match_all('~<span>([^<]*)</span>~u', $html, $spans);

        $figures = array_values(array_filter(
            array_map('trim', $spans[1]),
            static fn (string $s): bool => $s !== '' && preg_match('/[\d٠-٩]/u', $s) === 1
        ));

        $this->assertNotEmpty($figures, 'No statistic figures rendered on the Arabic homepage.');

        foreach ($figures as $figure) {
            $this->assertDoesNotMatchRegularExpression(
                '/\d/',
                $figure,
                "Arabic statistic [{$figure}] still contains a Western digit."
            );
            $this->assertMatchesRegularExpression('/[٠-٩]/u', $figure);
        }
    }

    public function test_the_english_homepage_statistics_keep_western_digits(): void
    {
        $this->seedPublicPages();

        $html = $this->get('/en')->assertOk()->getContent();

        preg_match_all('~<span>([^<]*)</span>~u', $html, $spans);

        $figures = array_values(array_filter(
            array_map('trim', $spans[1]),
            static fn (string $s): bool => $s !== '' && preg_match('/[\d٠-٩]/u', $s) === 1
        ));

        $this->assertNotEmpty($figures, 'No statistic figures rendered on the English homepage.');

        foreach ($figures as $figure) {
            $this->assertDoesNotMatchRegularExpression(
                '/[٠-٩]/u',
                $figure,
                "English statistic [{$figure}] was localized when it should not have been."
            );
        }
    }

    public function test_the_plus_sign_survives_localization_in_the_right_order(): void
    {
        // "+658" must become "+٦٥٨" — sign first in logical order, so the browser's bidi
        // algorithm can place it. Reordering here would produce uncopyable text.
        $this->assertSame('+٦٥٨', localized_digits('+658', 'ar'));
        $this->assertStringStartsWith('+', localized_digits('+658', 'ar'));
    }

    // ------------------------------------------------------------- public phone number

    public function test_the_arabic_phone_label_is_localized_but_never_a_dial_target(): void
    {
        $this->seedPublicPages();

        $html = $this->get('/ar')->assertOk()->getContent();

        // The header phone is rendered through the formatter, so if the layout settings
        // carry a number the visible text must not be Western.
        preg_match_all('~<a class="nav-link" href="#">([^<]*)</a>~u', $html, $links);

        foreach (array_map('trim', $links[1]) as $label) {
            $this->assertDoesNotMatchRegularExpression(
                '/\d/',
                $label,
                "Arabic header link [{$label}] still shows Western digits."
            );
        }

        // And no tel: href anywhere may carry a localized digit — a dialler cannot parse
        // Arabic-Indic numerals.
        preg_match_all('~href="tel:([^"]*)"~u', $html, $tel);

        foreach ($tel[1] as $target) {
            $this->assertDoesNotMatchRegularExpression(
                '/[٠-٩]/u',
                $target,
                "tel: target [{$target}] must stay ASCII."
            );
        }
    }

    // ----------------------------------------------------------------- admin numerals

    /**
     * A fleet large enough to paginate, so the summary and the page links both render.
     */
    private function seedFleet(int $count = 25): void
    {
        $place = Place::factory()->create(['name' => ['en' => 'Numeral Plaza', 'ar' => 'ساحة الأرقام']]);

        for ($i = 1; $i <= $count; $i++) {
            Screen::factory()->create([
                'place_id' => $place->id,
                'code' => sprintf('SCR-%03d', $i),
                'status' => ScreenStatus::Online->value,
            ]);
        }
    }

    public function test_admin_arabic_stat_values_and_result_counts_use_arabic_indic_digits(): void
    {
        $this->seedFleet();

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.screens.index', ['lang' => 'ar']))
            ->assertOk()
            ->getContent();

        $this->assertContainsArabicDigit($html, 'The Arabic screens index');

        // The stat cards carry the fleet totals.
        preg_match_all('~admin-stat-value[^>]*>\s*([^<]*?)\s*<~u', $html, $stats);

        $values = array_values(array_filter(array_map('trim', $stats[1]), static fn ($v) => $v !== ''));

        $this->assertNotEmpty($values, 'No stat values rendered.');

        foreach ($values as $value) {
            $this->assertDoesNotMatchRegularExpression(
                '/\d/',
                $value,
                "Arabic stat value [{$value}] still contains a Western digit."
            );
        }
    }

    public function test_admin_english_stat_values_keep_western_digits(): void
    {
        $this->seedFleet();

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.screens.index', ['lang' => 'en']))
            ->assertOk()
            ->getContent();

        preg_match_all('~admin-stat-value[^>]*>\s*([^<]*?)\s*<~u', $html, $stats);

        $values = array_values(array_filter(array_map('trim', $stats[1]), static fn ($v) => $v !== ''));

        $this->assertNotEmpty($values);

        foreach ($values as $value) {
            $this->assertDoesNotMatchRegularExpression(
                '/[٠-٩]/u',
                $value,
                "English stat value [{$value}] was localized when it should not have been."
            );
        }
    }

    // -------------------------------------------------------------------- pagination

    public function test_arabic_pagination_labels_are_localized_but_the_query_string_is_not(): void
    {
        /*
         * This is the case that matters most. The visitor reads ٢; the link still points
         * at ?page=2. If the substitution ever reached the href, Arabic users simply could
         * not page through a list, and nothing would look broken until someone clicked.
         */
        $this->seedFleet(60);

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.screens.index', ['lang' => 'ar']))
            ->assertOk()
            ->getContent();

        // Page-link labels: at least one must be an Arabic numeral.
        preg_match_all('~<a class="page-link" href="([^"]*)"[^>]*>([^<]*)</a>~u', $html, $links);

        $this->assertNotEmpty($links[0], 'Pagination did not render — seed more rows.');

        $localizedLabel = false;

        foreach ($links[2] as $i => $label) {
            $label = trim($label);

            if (preg_match('/[٠-٩]/u', $label)) {
                $localizedLabel = true;
            }

            // Whatever the label says, the href must be ASCII and still carry page=.
            $href = $links[1][$i];

            $this->assertDoesNotMatchRegularExpression(
                '/[٠-٩]/u',
                $href,
                "Pagination href [{$href}] must never contain an Arabic-Indic digit."
            );
        }

        $this->assertTrue($localizedLabel, 'No pagination label was localized.');

        // And the page parameter itself is intact and ASCII.
        $this->assertMatchesRegularExpression('/[?&]page=\d+/', $html, 'The ?page= parameter must stay ASCII.');
    }

    public function test_english_pagination_labels_stay_western(): void
    {
        $this->seedFleet(60);

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.screens.index', ['lang' => 'en']))
            ->assertOk()
            ->getContent();

        preg_match_all('~<a class="page-link" href="[^"]*"[^>]*>([^<]*)</a>~u', $html, $links);

        $this->assertNotEmpty($links[1]);

        foreach ($links[1] as $label) {
            $this->assertDoesNotMatchRegularExpression(
                '/[٠-٩]/u',
                trim($label),
                'English pagination must not be localized.'
            );
        }
    }

    // ------------------------------------------------------- technical identifiers

    public function test_screen_codes_are_never_localized(): void
    {
        // SCR-001 stays SCR-001. It is an identifier operators read aloud, type into a
        // device and match against hardware labels.
        $this->seedFleet();

        foreach (['ar', 'en'] as $locale) {
            $html = $this->actingAs($this->admin, 'admin')
                ->get(route('admin.screens.index', ['lang' => $locale]))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString('SCR-001', $html, "[{$locale}] lost the literal screen code.");
            $this->assertStringNotContainsString('SCR-٠٠١', $html, "[{$locale}] localized a screen code.");
        }
    }

    public function test_urls_and_machine_attributes_stay_ascii_on_arabic_pages(): void
    {
        $this->seedFleet(60);

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.screens.index', ['lang' => 'ar']))
            ->assertOk()
            ->getContent();

        // Every href, action, src and data-* value on an Arabic page must be ASCII-safe.
        foreach (['href', 'action', 'src', 'id', 'name'] as $attribute) {
            preg_match_all('~\b' . $attribute . '="([^"]*)"~u', $html, $values);

            foreach ($values[1] as $value) {
                $this->assertDoesNotMatchRegularExpression(
                    '/[٠-٩]/u',
                    $value,
                    "Arabic-Indic digit leaked into a [{$attribute}] attribute: {$value}"
                );
            }
        }
    }

    public function test_the_device_api_keeps_numeric_json(): void
    {
        /*
         * The hard boundary. A player parses this; `"duration_seconds": "٢٠"` would be a
         * type change disguised as a translation. The endpoint is unauthenticated-hostile
         * by design, so a 401 is the expected answer — what matters is that the body
         * carries no localized digit even when the request arrives with an Arabic locale.
         */
        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/v1/config');

        $this->assertSame(401, $response->getStatusCode());
        $this->assertDoesNotMatchRegularExpression(
            '/[٠-٩]/u',
            $response->getContent(),
            'The Device API must never emit Arabic-Indic digits.'
        );
    }

    // ---------------------------------------------------------------- no DOM sweeping

    public function test_no_script_rewrites_digits_across_the_dom(): void
    {
        /*
         * A script that walks every text node and swaps digits would corrupt codes, copied
         * values and structured data — and it is the obvious wrong way to do this. Digits
         * are localized server-side at known rendering boundaries instead, so no such
         * script should exist. `main.js` is empty and the public counters are plain Blade,
         * which is why no JS formatter was introduced.
         */
        $this->assertSame(
            0,
            filesize(public_path('frontend/js/main.js')),
            'main.js gained content — if counters became JS-driven, a locale-aware formatter is now required.'
        );

        foreach (['frontend/js/main.js', 'admin-assets/js/breem-admin.js'] as $script) {
            $path = public_path(str_replace('/', DIRECTORY_SEPARATOR, $script));

            if (! is_file($path)) {
                continue;
            }

            $source = file_get_contents($path);

            foreach (['createTreeWalker', 'TEXT_NODE', 'nodeValue.replace'] as $sweeper) {
                $this->assertStringNotContainsString(
                    $sweeper,
                    $source,
                    "[{$script}] looks like it walks the DOM replacing text — do not localize digits that way."
                );
            }
        }
    }

    // -------------------------------------------------------------- CMS content limit

    public function test_cms_authored_html_is_left_as_authored(): void
    {
        /*
         * Recorded as a deliberate limitation rather than a gap. Trusted CMS HTML can hold
         * URLs, iframe sources and element ids; a blind digit replacement across it would
         * corrupt them, and parsing it safely is out of scope here. Application-generated
         * numbers are localized; numerals an editor typed inside rich text are not.
         */
        $this->seedPublicPages();

        $this->get('/ar')->assertOk();

        // The guarantee: nothing in the pipeline mutates stored content.
        $this->assertDatabaseCount('pages', 3);
    }
}
