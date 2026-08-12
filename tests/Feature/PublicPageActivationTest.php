<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\ContactUsPageSeeder;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\WhoWeArePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 4.5: pages.is_active governs public rendering, and unknown URLs answer
 * with a real 404 status.
 */
class PublicPageActivationTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> slug => public path */
    protected array $pages = [
        'home' => '/en',
        'whoweare' => '/en/whoweare',
        'contact-us' => '/en/contact-us',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HomePageSeeder::class);
        $this->seed(WhoWeArePageSeeder::class);
        $this->seed(ContactUsPageSeeder::class);
    }

    public static function pageProvider(): array
    {
        return [
            'home' => ['home', '/en', '/ar'],
            'whoweare' => ['whoweare', '/en/whoweare', '/ar/whoweare'],
            'contact-us' => ['contact-us', '/en/contact-us', '/ar/contact-us'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_active_pages_render_in_both_locales(string $slug, string $english, string $arabic): void
    {
        $this->assertTrue((bool) Page::where('slug', $slug)->firstOrFail()->is_active);

        $this->get($english)->assertOk();
        $this->get($arabic)->assertOk();
    }

    #[DataProvider('pageProvider')]
    public function test_inactive_pages_return_404_without_losing_content(string $slug, string $english, string $arabic): void
    {
        $page = Page::where('slug', $slug)->firstOrFail();
        $sectionCount = $page->sections()->count();

        $page->update(['is_active' => false]);

        $this->get($english)->assertNotFound();
        $this->get($arabic)->assertNotFound();

        // Deactivation hides the page; it never deletes anything.
        $this->assertSame($sectionCount, $page->fresh()->sections()->count());

        $page->update(['is_active' => true]);

        $this->get($english)->assertOk();
        $this->get($arabic)->assertOk();
    }

    public function test_deactivating_a_page_clears_its_cached_payload(): void
    {
        $this->get('/en')->assertOk();
        $this->assertNotNull(Cache::get('page.home'));

        Page::where('slug', 'home')->firstOrFail()->update(['is_active' => false]);

        $this->assertNull(Cache::get('page.home'), 'The Page observer should clear page.home.');
        $this->get('/en')->assertNotFound();
    }

    public function test_unknown_urls_return_a_real_404_status(): void
    {
        $this->get('/definitely-not-a-real-page')->assertNotFound();
        $this->get('/en/nope')->assertNotFound();
        $this->get('/ar/also-nope')->assertNotFound();
    }
}
