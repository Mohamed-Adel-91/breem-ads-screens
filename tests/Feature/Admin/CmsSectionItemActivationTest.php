<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\PageSection;
use App\Models\SectionItem;
use Database\Seeders\HomePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase 4.5: section item activation is backed by a real column and is
 * honoured by the public renderer.
 */
class CmsSectionItemActivationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HomePageSeeder::class);

        $this->admin = Admin::create([
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'email' => 'items@example.com',
            'password' => 'password',
            'mobile' => '1234567891',
        ]);
    }

    protected function statsItems()
    {
        return PageSection::where('type', 'stats')->firstOrFail()
            ->items()->orderBy('order')->get();
    }

    /** The public home page HTML, with the page cache cleared first. */
    protected function publicHome(): string
    {
        Cache::forget('page.home');

        return $this->get('/en')->assertOk()->getContent();
    }

    public function test_items_are_active_by_default(): void
    {
        $this->assertGreaterThan(0, SectionItem::count());
        $this->assertSame(0, SectionItem::where('is_active', false)->count());

        $created = SectionItem::create([
            'section_id' => PageSection::where('type', 'stats')->firstOrFail()->id,
            'order' => 99,
            'data' => ['en' => ['label' => 'Fresh'], 'ar' => ['label' => 'جديد']],
        ]);

        $this->assertTrue($created->fresh()->is_active);
    }

    public function test_toggle_flips_the_database_column_and_leaves_translations_alone(): void
    {
        $item = $this->statsItems()->first();
        $originalData = $item->getAttributes()['data'];

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.cms.items.toggle', ['lang' => 'en', 'item' => $item->id]))
            ->assertOk()
            ->assertJson(['ok' => true, 'is_active' => false]);

        $this->assertFalse($item->fresh()->is_active);
        $this->assertSame($originalData, $item->fresh()->getAttributes()['data']);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.cms.items.toggle', ['lang' => 'en', 'item' => $item->id]))
            ->assertOk()
            ->assertJson(['is_active' => true]);

        $this->assertTrue($item->fresh()->is_active);
    }

    public function test_inactive_item_disappears_from_the_public_page_and_returns_when_reactivated(): void
    {
        $item = $this->statsItems()->firstWhere(
            fn ($candidate) => ($candidate->getTranslation('data', 'en', true)['label'] ?? null) === 'Social Ads'
        );

        $this->assertNotNull($item, 'Expected the seeded "Social Ads" stat.');

        $this->assertStringContainsString('Social Ads', $this->publicHome());

        $item->update(['is_active' => false]);
        $withoutItem = $this->publicHome();
        $this->assertStringNotContainsString('Social Ads', $withoutItem);
        // The siblings are untouched.
        $this->assertStringContainsString('Ad Screens', $withoutItem);

        $item->update(['is_active' => true]);
        $this->assertStringContainsString('Social Ads', $this->publicHome());
    }

    public function test_deactivating_an_item_preserves_the_order_of_the_rest(): void
    {
        $items = $this->statsItems();
        $items[1]->update(['is_active' => false]);

        $html = $this->publicHome();

        $positions = [];
        foreach (['Ad Screens', 'Ad Production', 'Websites Development'] as $label) {
            $positions[$label] = strpos($html, $label);
            $this->assertNotFalse($positions[$label], "Missing stat: {$label}");
        }

        $this->assertTrue(
            $positions['Ad Screens'] < $positions['Ad Production']
            && $positions['Ad Production'] < $positions['Websites Development'],
            'Remaining items should keep their relative order.'
        );
    }

    public function test_toggle_invalidates_the_page_cache(): void
    {
        $this->get('/en')->assertOk();
        $this->assertNotNull(Cache::get('page.home'));

        $item = $this->statsItems()->first();

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.cms.items.toggle', ['lang' => 'en', 'item' => $item->id]))
            ->assertOk();

        $this->assertNull(Cache::get('page.home'), 'The SectionItem observer should clear page.home.');
    }

    public function test_admin_screens_still_list_deactivated_items(): void
    {
        $section = PageSection::where('type', 'stats')->firstOrFail();
        $total = $section->items()->count();

        $section->items()->orderBy('order')->first()->update(['is_active' => false]);

        // Only the public renderer filters; the admin keeps full visibility.
        $this->assertSame($total, $section->fresh()->items()->count());

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.cms.home.edit', ['lang' => 'en']))
            ->assertOk();
    }
}
