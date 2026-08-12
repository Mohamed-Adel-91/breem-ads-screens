<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Absolute temporary upload root used by the test currently running.
     */
    protected ?string $temporaryUploadRoot = null;

    /**
     * Redirect every managed upload to a throwaway directory.
     *
     * The upload chain (FileService, DeleteFileTrait, VideoProbe) writes with
     * raw filesystem calls rather than the Storage facade, so Storage::fake()
     * cannot intercept it. Overriding media.upload_root centrally here means no
     * test can write into the real public/ tree, whether or not its author
     * remembered to isolate it.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryUploadRoot = storage_path(
            'framework/testing/uploads/' . str_replace('\\', '_', static::class) . '_' . bin2hex(random_bytes(6))
        );

        if (!is_dir($this->temporaryUploadRoot)) {
            mkdir($this->temporaryUploadRoot, 0775, true);
        }

        config(['media.upload_root' => $this->temporaryUploadRoot]);
    }

    /**
     * Absolute path inside the temporary upload root, mirroring UploadPath::to().
     */
    protected function uploadPath(string $relative = ''): string
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');

        return $relative === ''
            ? $this->temporaryUploadRoot
            : $this->temporaryUploadRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    protected function tearDown(): void
    {
        $root = $this->temporaryUploadRoot;
        $this->temporaryUploadRoot = null;

        parent::tearDown();

        if ($root && is_dir($root)) {
            $this->deleteDirectory($root);
        }
    }

    private function deleteDirectory(string $directory): void
    {
        $items = @scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            is_dir($path) && !is_link($path)
                ? $this->deleteDirectory($path)
                : @unlink($path);
        }

        @rmdir($directory);
    }
}
