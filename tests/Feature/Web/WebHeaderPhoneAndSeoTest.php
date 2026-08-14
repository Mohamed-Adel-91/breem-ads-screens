<?php

namespace Tests\Feature\Web;

use App\Models\Admin;
use App\Models\SeoMeta;
use App\Models\Setting;
use Database\Seeders\ContactUsPageSeeder;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\WhoWeArePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The last two public-site defects: an undialable header phone, and a duplicate
 * placeholder meta description.
 *
 * THE PHONE. The header showed the company's number and linked it to `href="#"` — the one
 * place on the site a visitor could read the number and not call it. The value was already
 * coming from the right place (`site.phone`, via the layout composer) and was already
 * localised; only the href was wrong, and the element rendered even with nothing to show.
 *
 * THE DESCRIPTION. All three page views pushed
 * `<meta name="description" content="description">` — the literal word — on top of the
 * real tag the layout renders from `seo_metas`. Every page shipped two, the second
 * meaningless.
 *
 * Deliberately NOT asserted: which description text is correct (that is CMS content), or
 * the navbar's layout and spacing (pinned by WebResponsiveLayoutTest).
 */
class WebHeaderPhoneAndSeoTest extends TestCase
{
    use RefreshDatabase;

    /** A header phone link: the class is the contract, the two captures are href and label. */
    private const HEADER_PHONE = '/<a class="nav-link site-navbar__phone"\s+href="tel:([^"]+)">\s*([^<]+?)\s*<\/a>/s';

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HomePageSeeder::class);
        $this->seed(WhoWeArePageSeeder::class);
        $this->seed(ContactUsPageSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['settings.edit', 'settings.update'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Phone',
            'last_name' => 'Tester',
            'email' => 'phone-tester@example.com',
            'password' => 'password',
            'mobile' => '1000000011',
        ]);

        $this->admin->givePermissionTo(['settings.edit', 'settings.update']);
    }

    /** Write through Eloquent so App\Observers\SettingObserver invalidates the layout cache. */
    private function putPhone(array $value): void
    {
        $setting = Setting::firstOrNew(['key' => 'site.phone']);
        $setting->replaceTranslations('value', $value);
        $setting->save();
    }

    public static function everyPageProvider(): array
    {
        return [
            'home (ar)' => ['/ar', 'ar'],
            'home (en)' => ['/en', 'en'],
            'who we are (ar)' => ['/ar/whoweare', 'ar'],
            'who we are (en)' => ['/en/whoweare', 'en'],
            'contact us (ar)' => ['/ar/contact-us', 'ar'],
            'contact us (en)' => ['/en/contact-us', 'en'],
        ];
    }

    // ------------------------------------------------------------------ header phone

    #[DataProvider('everyPageProvider')]
    public function test_the_header_phone_is_dialable_on_every_page(string $path, string $locale): void
    {
        $html = $this->get($path)->assertOk()->getContent();

        $this->assertSame(
            1,
            preg_match(self::HEADER_PHONE, $html, $matches),
            "[{$path}] renders no dialable header phone."
        );

        // Seeded as `+99654334` in both locales.
        $this->assertSame('+99654334', $matches[1], "[{$path}] dials the wrong number.");

        if ($locale === 'ar') {
            $this->assertMatchesRegularExpression(
                '/[\x{0660}-\x{0669}]/u',
                $matches[2],
                "[{$path}] must show Arabic-Indic digits."
            );
            $this->assertDoesNotMatchRegularExpression('/[0-9]/', $matches[2]);
        } else {
            $this->assertDoesNotMatchRegularExpression(
                '/[\x{0660}-\x{0669}]/u',
                $matches[2],
                "[{$path}] must show Western digits."
            );
            $this->assertSame('+99654334', $matches[2]);
        }
    }

    #[DataProvider('everyPageProvider')]
    public function test_no_page_leaves_a_dead_header_phone_link(string $path): void
    {
        $html = $this->get($path)->getContent();

        // The defect this replaced, and the two ways it could come back.
        $this->assertStringNotContainsString('class="nav-link" href="#"', $html);
        $this->assertStringNotContainsString('href="tel:"', $html);
    }

    public function test_the_header_and_the_footer_dial_the_same_number(): void
    {
        // One business phone, not a header copy and a footer copy.
        $html = $this->get('/ar')->getContent();

        preg_match_all('/href="tel:([^"]+)"/', $html, $targets);

        $this->assertCount(2, $targets[1], 'Expected exactly one header phone and one footer phone.');
        $this->assertSame($targets[1][0], $targets[1][1], 'The header and footer dial different numbers.');
    }

    /**
     * A `+` typed at the visual end of an RTL field still dials.
     *
     * The normalisation lives in LayoutService::telHref() and is shared, so this asserts
     * the header inherits it rather than reimplementing it.
     */
    public function test_a_plus_sign_stored_at_the_end_still_dials_correctly(): void
    {
        $this->putPhone(['ar' => '99654334+', 'en' => '99654334+']);

        foreach (['/ar', '/en'] as $path) {
            preg_match(self::HEADER_PHONE, $this->get($path)->getContent(), $matches);

            $this->assertSame('+99654334', $matches[1] ?? null, "[{$path}] mis-parsed a trailing plus.");
        }
    }

    public function test_a_number_typed_in_arabic_digits_still_dials(): void
    {
        $this->putPhone(['ar' => '٩٦٦٥٠٠١١٢٢٣٣+', 'en' => '+966500112233']);

        preg_match(self::HEADER_PHONE, $this->get('/ar')->getContent(), $matches);

        $this->assertSame('+966500112233', $matches[1] ?? null);
    }

    #[DataProvider('everyPageProvider')]
    public function test_an_unconfigured_phone_hides_the_header_item_entirely(string $path): void
    {
        Setting::where('key', 'site.phone')->delete();

        $html = $this->get($path)->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(self::HEADER_PHONE, $html);
        $this->assertStringNotContainsString('site-navbar__phone', $html);
        $this->assertStringNotContainsString('href="tel:"', $html);

        // The language switch is still reachable and still inside the collapse, so the
        // meta group has not collapsed along with the phone.
        $this->assertStringContainsString('site-navbar__meta', $html);
        $this->assertSame(1, preg_match_all('/class="nav-link lang-switch"/', $html));
    }

    public function test_changing_the_phone_in_the_admin_updates_the_header_and_the_footer(): void
    {
        $this->putPhone(['ar' => '+966500000001', 'en' => '+966500000001']);

        $html = $this->get('/en')->getContent();
        $this->assertStringContainsString('href="tel:+966500000001"', $html);

        // Through the real admin route, so the cache invalidation path is the one an
        // operator actually triggers.
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.update', ['lang' => 'en']), ['phone' => '+966500000002'])
            ->assertSessionHasNoErrors();

        $html = $this->get('/en')->getContent();

        $this->assertStringNotContainsString('+966500000001', $html, 'A stale phone survived the update.');
        $this->assertSame(
            2,
            preg_match_all('/href="tel:\+966500000002"/', $html),
            'Both the header and the footer must reflect the new number.'
        );
    }

    public function test_the_header_phone_costs_no_extra_query(): void
    {
        // The layout already loads every setting in one bounded query; the navbar reads the
        // resolved array and must not reach for the database itself.
        DB::enableQueryLog();
        $this->get('/en');
        $queries = DB::getRawQueryLog();
        DB::disableQueryLog();

        $settingsQueries = array_values(array_filter(
            $queries,
            fn (array $query): bool => str_contains($query['raw_query'], 'from "settings"')
        ));

        $this->assertCount(1, $settingsQueries, 'The navbar phone must not add a settings query.');
    }

    public function test_the_navbar_template_holds_no_phone_literal_and_no_query(): void
    {
        $blade = file_get_contents(
            resource_path('views/web/layouts/components/navbar.blade.php')
        );

        // No business phone number in the template, in either script.
        $this->assertDoesNotMatchRegularExpression(
            '/[\x{0660}-\x{0669}\x{06F0}-\x{06F9}0-9]{6,}/u',
            $blade,
            'The navbar template hardcodes a phone number.'
        );

        foreach (['DB::', 'Setting::', 'query()'] as $needle) {
            $this->assertStringNotContainsString($needle, $blade);
        }
    }

    // -------------------------------------------------------------- meta description

    #[DataProvider('everyPageProvider')]
    public function test_every_page_renders_exactly_one_meta_description(string $path): void
    {
        $html = $this->get($path)->assertOk()->getContent();

        $this->assertSame(
            1,
            preg_match_all('/<meta[^>]+name="description"/i', $html),
            "[{$path}] must render exactly one meta description."
        );

        // And exactly one title, while we are counting.
        $this->assertSame(1, preg_match_all('/<title[^>]*>/i', $html), "[{$path}] must render one title.");
        $this->assertSame(
            1,
            preg_match_all('/<link[^>]+rel="canonical"/i', $html),
            "[{$path}] must render one canonical link."
        );
    }

    #[DataProvider('everyPageProvider')]
    public function test_no_page_renders_the_placeholder_description(string $path): void
    {
        $html = $this->get($path)->getContent();

        // The literal word "description" as the description. Asserted on the rendered
        // attribute so an unrelated occurrence of the word in copy cannot trip it.
        $this->assertDoesNotMatchRegularExpression(
            '/<meta[^>]+name="description"[^>]+content="description"/i',
            $html,
            "[{$path}] still renders the placeholder description."
        );
    }

    public function test_no_active_public_view_pushes_a_description(): void
    {
        // The layout owns the description. A page view pushing its own is how the
        // duplicate appeared, so the pattern is pinned out rather than only its symptom.
        foreach (glob(resource_path('views/web/pages/*.blade.php')) as $view) {
            // Blade comments stripped first: these files now DOCUMENT the tag they must
            // not emit, and a naive scan would match the explanation.
            $contents = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($view));

            $this->assertDoesNotMatchRegularExpression(
                '/<meta\s+name="description"/i',
                $contents,
                basename($view) . ' declares its own meta description; the layout owns it.'
            );
        }
    }

    /**
     * A bilingual SEO record for one route.
     *
     * Created here rather than seeded: NOTHING in database/seeders creates `seo_metas`, so
     * a test that relied on seed data would be asserting against the layout's fallback
     * branch and would pass without proving the binding works at all.
     */
    private function putSeoMeta(string $page, string $arabic, string $english): void
    {
        $meta = SeoMeta::firstOrNew(['page' => $page]);
        $meta->replaceTranslations('description', ['ar' => $arabic, 'en' => $english]);
        $meta->replaceTranslations('title', ['ar' => 'عنوان', 'en' => 'Title']);
        $meta->save();
    }

    #[DataProvider('everyPageProvider')]
    public function test_the_description_comes_from_the_seo_record_for_that_locale(string $path, string $locale): void
    {
        $route = match (true) {
            str_contains($path, 'whoweare') => 'web.whoweare',
            str_contains($path, 'contact-us') => 'web.contactUs',
            default => 'web.home',
        };

        $this->putSeoMeta($route, "وصف {$route} بالعربية", "The {$route} description in English");

        $expected = $locale === 'ar' ? "وصف {$route} بالعربية" : "The {$route} description in English";

        preg_match(
            '/<meta[^>]+name="description"[^>]+content="([^"]*)"/i',
            $this->get($path)->getContent(),
            $matches
        );

        $this->assertSame(
            e($expected),
            $matches[1] ?? null,
            "[{$path}] does not render the {$locale} description from its seo_metas record."
        );
    }

    public function test_the_two_locales_do_not_share_one_description(): void
    {
        /*
         * The layout memoises the SEO lookup per route in the container. That is safe for
         * the ROW — a route name cannot change within a request — but the description is
         * resolved per locale from it, so this pins that the Arabic page cannot serve the
         * English text: the same failure mode the layout settings cache once had.
         */
        $this->putSeoMeta('web.home', 'الوصف بالعربية', 'The description in English');

        preg_match(
            '/<meta[^>]+name="description"[^>]+content="([^"]*)"/i',
            $this->get('/ar')->getContent(),
            $ar
        );
        preg_match(
            '/<meta[^>]+name="description"[^>]+content="([^"]*)"/i',
            $this->get('/en')->getContent(),
            $en
        );

        $this->assertSame(e('الوصف بالعربية'), $ar[1] ?? null);
        $this->assertSame(e('The description in English'), $en[1] ?? null);
        $this->assertNotSame($ar[1], $en[1], 'Both locales served the same description.');
    }

    public function test_a_page_without_an_seo_record_falls_back_to_one_generic_description(): void
    {
        /*
         * DOCUMENTING EXISTING BEHAVIOUR, not changing it. No seeder creates `seo_metas`,
         * so this is what a fresh installation actually serves: the layout's `@else`
         * branch. What matters for this task is that it is exactly ONE tag and that it is
         * not the literal word "description".
         *
         * That the fallback is a hardcoded English string on an Arabic page is reported as
         * SEO debt rather than fixed here — choosing what an unconfigured page should say
         * is a content decision.
         */
        SeoMeta::query()->delete();

        foreach (['/ar', '/en'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertSame(
                1,
                preg_match_all('/<meta[^>]+name="description"/i', $html),
                "[{$path}] fallback must still be a single description."
            );
            $this->assertDoesNotMatchRegularExpression(
                '/name="description"[^>]+content="description"/i',
                $html,
                "[{$path}] fell back to the placeholder."
            );
        }
    }
}
