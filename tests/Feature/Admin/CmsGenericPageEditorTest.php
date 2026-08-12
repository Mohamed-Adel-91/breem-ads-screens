<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Page;
use App\Models\PageSection;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 4.5: the generic page editor is routed, and its multipart save reaches
 * the controller (POST + _method=PATCH, the way the browser now sends it).
 */
class CmsGenericPageEditorTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(HomePageSeeder::class);

        $this->admin = Admin::create([
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'email' => 'editor@example.com',
            'password' => 'password',
            'mobile' => '1234567892',
        ]);

        $this->admin->givePermissionTo('cms.manage');
    }

    protected function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
    }

    public function test_generic_editor_is_reachable_for_a_page(): void
    {
        $response = $this->actingAsAdmin()
            ->get(route('admin.cms.pages.sections', ['lang' => 'en', 'page' => 'home']));

        $response->assertOk();
        $response->assertViewIs('admin.cms.pages.edit');
        $response->assertViewHas('page', fn (Page $page) => $page->slug === 'home');
    }

    public function test_generic_editor_rejects_an_unknown_page(): void
    {
        $this->actingAsAdmin()
            ->get(route('admin.cms.pages.sections', ['lang' => 'en', 'page' => 'nope']))
            ->assertNotFound();
    }

    public function test_generic_editor_requires_authentication(): void
    {
        $this->get(route('admin.cms.pages.sections', ['lang' => 'en', 'page' => 'home']))
            ->assertRedirect();
    }

    public function test_legacy_edit_url_still_redirects_curated_pages(): void
    {
        $this->actingAsAdmin()
            ->get(route('admin.cms.pages.edit', ['lang' => 'en', 'slug' => 'home']))
            ->assertRedirect(route('admin.cms.home.edit', ['lang' => 'en']));
    }

    public function test_section_data_scalar_fields_reach_the_controller(): void
    {
        $section = PageSection::where('type', 'about')->firstOrFail();

        // Exactly what cms-admin.js posts: multipart body, spoofed PATCH.
        $response = $this->actingAsAdmin()->post(
            route('admin.cms.sections.update', ['lang' => 'en', 'section' => $section->id]),
            [
                '_method' => 'PATCH',
                'section_data' => [
                    'title' => 'Updated title',
                    'desc' => 'Updated description',
                ],
            ]
        );

        $response->assertOk()->assertJson(['ok' => true]);

        $stored = $section->fresh()->getTranslation('section_data', 'en', true);
        $this->assertSame('Updated title', $stored['title']);
        $this->assertSame('Updated description', $stored['desc']);
    }

    public function test_translated_section_data_is_written_for_the_requested_locale(): void
    {
        $section = PageSection::where('type', 'about')->firstOrFail();
        $arabicBefore = $section->getTranslation('section_data', 'ar', true);

        $this->actingAsAdmin()->post(
            route('admin.cms.sections.update', ['lang' => 'en', 'section' => $section->id]),
            ['_method' => 'PATCH', 'section_data' => ['title' => 'English only']]
        )->assertOk();

        $fresh = $section->fresh();
        $this->assertSame('English only', $fresh->getTranslation('section_data', 'en', true)['title']);
        $this->assertSame($arabicBefore, $fresh->getTranslation('section_data', 'ar', true));
    }

    public function test_uploads_reach_the_controller_and_are_stored(): void
    {
        Storage::fake('public');

        $section = PageSection::where('type', 'about')->firstOrFail();

        $response = $this->actingAsAdmin()->post(
            route('admin.cms.sections.update', ['lang' => 'en', 'section' => $section->id]),
            [
                '_method' => 'PATCH',
                'section_data' => ['title' => 'With media'],
                'uploads' => ['image_path' => UploadedFile::fake()->image('hero.png')],
            ]
        );

        $response->assertOk()->assertJson(['ok' => true]);

        $stored = $section->fresh()->getTranslation('section_data', 'en', true);

        $this->assertArrayHasKey('image_path', $stored);
        $this->assertStringStartsWith('storage/cms/', $stored['image_path']);

        Storage::disk('public')->assertExists(substr($stored['image_path'], strlen('storage/')));
    }

    public function test_json_response_contract_is_preserved(): void
    {
        $section = PageSection::where('type', 'about')->firstOrFail();

        $response = $this->actingAsAdmin()->patchJson(
            route('admin.cms.sections.update', ['lang' => 'en', 'section' => $section->id]),
            ['order' => 7]
        );

        $response->assertOk()
            ->assertJsonStructure(['ok', 'section' => ['id', 'type', 'order', 'is_active']]);

        $this->assertSame(7, $section->fresh()->order);
    }

    public function test_section_toggle_and_delete_still_work(): void
    {
        $section = PageSection::where('type', 'about')->firstOrFail();
        $this->assertTrue((bool) $section->is_active);

        $this->actingAsAdmin()
            ->patchJson(route('admin.cms.sections.toggle', ['lang' => 'en', 'section' => $section->id]))
            ->assertOk()
            ->assertJson(['ok' => true, 'is_active' => false]);

        $this->assertFalse((bool) $section->fresh()->is_active);

        $this->actingAsAdmin()
            ->deleteJson(route('admin.cms.sections.destroy', ['lang' => 'en', 'section' => $section->id]))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNull(PageSection::find($section->id));
    }

    public function test_item_order_update_and_delete_still_work(): void
    {
        $item = PageSection::where('type', 'stats')->firstOrFail()->items()->orderBy('order')->firstOrFail();

        $this->actingAsAdmin()
            ->patchJson(route('admin.cms.items.update', ['lang' => 'en', 'item' => $item->id]), ['order' => 42])
            ->assertOk()
            ->assertJsonStructure(['ok', 'item' => ['id', 'order']]);

        $this->assertSame(42, $item->fresh()->order);

        $this->actingAsAdmin()
            ->deleteJson(route('admin.cms.items.destroy', ['lang' => 'en', 'item' => $item->id]))
            ->assertOk();

        $this->assertNull($item->fresh());
    }
}
