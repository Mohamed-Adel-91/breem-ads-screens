<?php

namespace Tests\Feature\Admin;

use App\Contracts\FileServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Phase 4.5: replacing CMS media never destroys the previous file before the
 * database has committed, and never leaves the new file behind when it has not.
 *
 * FileService writes into public/, so these tests use a dedicated folder and
 * clean it up afterwards.
 */
class CmsMediaReplacementTest extends TestCase
{
    protected string $folder = 'cms/phpunit-media';

    protected function tearDown(): void
    {
        $absolute = public_path($this->folder);

        if (is_dir($absolute)) {
            foreach (glob($absolute . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($absolute);
        }

        parent::tearDown();
    }

    protected function service(): FileServiceInterface
    {
        return app(FileServiceInterface::class);
    }

    protected function uploadRequest(string $field, UploadedFile $file): Request
    {
        return Request::create('/upload', 'POST', [], [], [$field => $file]);
    }

    /** Stores a first file and returns its stored path. */
    protected function seedExistingFile(): string
    {
        $service = $this->service();
        $path = $service->uploadSingle(
            $this->uploadRequest('media', UploadedFile::fake()->image('original.png')),
            'media',
            $this->folder
        );

        $service->commitReplacedFiles();

        $this->assertNotNull($path);
        $this->assertFileExists(public_path($path));

        return $path;
    }

    public function test_upload_returns_the_existing_path_when_no_file_is_sent(): void
    {
        $result = $this->service()->uploadSingle(
            Request::create('/upload', 'POST'),
            'media',
            $this->folder,
            'cms/existing/keep-me.png'
        );

        $this->assertSame('cms/existing/keep-me.png', $result);
    }

    public function test_successful_replacement_removes_the_old_file_only_after_commit(): void
    {
        $old = $this->seedExistingFile();
        $service = $this->service();

        $new = $service->uploadSingle(
            $this->uploadRequest('media', UploadedFile::fake()->image('replacement.png')),
            'media',
            $this->folder,
            $old
        );

        $this->assertNotSame($old, $new);

        // Before commit both files still exist: the database may still roll back.
        $this->assertFileExists(public_path($old));
        $this->assertFileExists(public_path($new));

        $service->commitReplacedFiles();

        $this->assertFileDoesNotExist(public_path($old));
        $this->assertFileExists(public_path($new));
    }

    public function test_failed_database_work_discards_the_new_file_and_keeps_the_old_one(): void
    {
        $old = $this->seedExistingFile();
        $service = $this->service();

        $new = $service->uploadSingle(
            $this->uploadRequest('media', UploadedFile::fake()->image('replacement.png')),
            'media',
            $this->folder,
            $old
        );

        $service->discardUploadedFiles();

        $this->assertFileDoesNotExist(public_path($new), 'The orphaned upload should be removed.');
        $this->assertFileExists(public_path($old), 'The previous media must survive a failed save.');
    }

    public function test_shared_assets_outside_the_target_folder_are_never_deleted(): void
    {
        $sharedDirectory = public_path('cms/phpunit-shared');
        @mkdir($sharedDirectory, 0775, true);
        $sharedFile = $sharedDirectory . '/shared.png';
        file_put_contents($sharedFile, 'shared');

        $service = $this->service();

        $service->uploadSingle(
            $this->uploadRequest('media', UploadedFile::fake()->image('replacement.png')),
            'media',
            $this->folder,
            // A seeded asset living in a different directory.
            'cms/phpunit-shared/shared.png'
        );

        $service->commitReplacedFiles();

        $this->assertFileExists($sharedFile, 'Assets outside the upload folder must be left alone.');

        @unlink($sharedFile);
        @rmdir($sharedDirectory);
    }

    public function test_remote_urls_are_never_treated_as_deletable_files(): void
    {
        $service = $this->service();

        $service->uploadSingle(
            $this->uploadRequest('media', UploadedFile::fake()->image('replacement.png')),
            'media',
            $this->folder,
            'https://cdn.example.com/logo.png'
        );

        // Nothing to delete, and committing must not raise.
        $service->commitReplacedFiles();

        $this->assertTrue(true);
    }

    public function test_commit_is_idempotent(): void
    {
        $old = $this->seedExistingFile();
        $service = $this->service();

        $service->uploadSingle(
            $this->uploadRequest('media', UploadedFile::fake()->image('replacement.png')),
            'media',
            $this->folder,
            $old
        );

        $service->commitReplacedFiles();
        $service->commitReplacedFiles();

        $this->assertFileDoesNotExist(public_path($old));
    }
}
