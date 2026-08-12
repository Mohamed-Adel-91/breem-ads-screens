<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\PageSection;
use App\Support\UploadPath;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\WhoWeArePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Depends;
use Tests\TestCase;

/**
 * Pre-Phase 8 — proves that running the suite never writes into the real
 * public/ media tree.
 *
 * The upload chain (FileService + FileUploadTrait) moves files with raw
 * filesystem calls instead of the Storage facade, so Storage::fake() could not
 * intercept it and CMS tests were leaving dozens of fake images under
 * public/cms/**. Tests\TestCase now points `media.upload_root` at a throwaway
 * directory for every test, and this file guards that seam.
 *
 * The assertions are delta-based: they compare a snapshot taken before the
 * upload with the state after it, so pre-existing legitimate media is irrelevant.
 */
class UploadIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HomePageSeeder::class);
        $this->seed(WhoWeArePageSeeder::class);

        $this->admin = Admin::create([
            'first_name' => 'Upload',
            'last_name' => 'Isolation',
            'email' => 'upload-isolation@example.com',
            'password' => 'password',
            'mobile' => '8100000001',
        ]);
    }

    /**
     * Every file currently living under the real public/cms tree.
     *
     * @return array<int, string>
     */
    private function realCmsSnapshot(): array
    {
        $root = public_path('cms');

        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    public function test_the_upload_root_is_redirected_away_from_public_during_tests(): void
    {
        $this->assertNotSame(
            rtrim(public_path(), '/\\'),
            UploadPath::root(),
            'Tests must never write uploads into the real public/ directory.'
        );

        $this->assertStringContainsString('framework', UploadPath::root());
        $this->assertDirectoryExists(UploadPath::root());
    }

    public function test_a_cms_upload_creates_no_new_file_under_the_real_public_cms_tree(): void
    {
        $before = $this->realCmsSnapshot();

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.cms.whoweare.update', ['lang' => 'en']),
            [
                'banner' => ['image' => UploadedFile::fake()->image('isolation-banner.png')],
                'port' => ['image' => UploadedFile::fake()->image('isolation-port.png')],
            ]
        );

        $response->assertRedirect();

        $after = $this->realCmsSnapshot();

        $this->assertSame(
            [],
            array_values(array_diff($after, $before)),
            'A CMS upload must not create files under the real public/cms tree.'
        );
    }

    public function test_the_uploaded_file_lands_in_the_isolated_test_root(): void
    {
        $this->actingAs($this->admin, 'admin')->put(
            route('admin.cms.home.update', ['lang' => 'en']),
            [
                'cta' => [
                    'image' => UploadedFile::fake()->image('isolation-cta.png'),
                    'en' => ['title' => 'CTA EN'],
                    'ar' => ['title' => 'CTA AR'],
                ],
            ]
        );

        $cta = PageSection::where('type', 'cta')->first();
        $storedPath = $cta->getTranslation('section_data', 'en', true)['image_path'] ?? null;

        $this->assertNotNull($storedPath, 'The upload should have been recorded.');

        // Stored paths stay relative — the public URL contract is unchanged.
        $this->assertStringStartsNotWith('/', $storedPath);
        $this->assertStringContainsString('cms/home/cta', $storedPath);

        // The bytes live in the isolated root, not in public/.
        $this->assertFileExists($this->uploadPath($storedPath));
        $this->assertFileDoesNotExist(public_path($storedPath));
    }

    public function test_the_temporary_upload_root_is_removed_after_each_test(): void
    {
        // Captured here and asserted by the companion test below, because a test
        // cannot observe its own tearDown.
        static::$lastRoot = UploadPath::root();

        $this->actingAs($this->admin, 'admin')->put(
            route('admin.cms.whoweare.update', ['lang' => 'en']),
            ['banner' => ['image' => UploadedFile::fake()->image('cleanup-check.png')]]
        );

        $this->assertDirectoryExists(static::$lastRoot);
    }

    #[Depends('test_the_temporary_upload_root_is_removed_after_each_test')]
    public function test_the_previous_tests_temporary_root_no_longer_exists(): void
    {
        $this->assertNotNull(static::$lastRoot);
        $this->assertDirectoryDoesNotExist(
            static::$lastRoot,
            'The temporary upload root must be cleaned up in tearDown.'
        );
    }

    protected static ?string $lastRoot = null;
}
