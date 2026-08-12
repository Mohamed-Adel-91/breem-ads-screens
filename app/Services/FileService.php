<?php

namespace App\Services;

use App\Contracts\FileServiceInterface;
use App\Support\UploadPath;
use App\Traits\DeleteFileTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FileService implements FileServiceInterface
{
    use FileUploadTrait, DeleteFileTrait;

    /**
     * Helper to build the full path to the model's folder.
     */
    private function buildModelFolder(string $baseFolder): string
    {
        $folder = UploadPath::to($baseFolder);
        if (!is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }
        return $folder;
    }

    /**
     * Store uploaded files for the given model.
     *
     * @param  Model   $model
     * @param  Request $request
     * @param  array   $fields
     * @param  string  $baseFolder
     * @return void
     */
    public function storeFiles(Model $model, Request $request, array $fields, string $baseFolder): void
    {
        $folder = $this->buildModelFolder($baseFolder);

        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $uploaded = $this->uploadFile([
                    $request->file($field)
                ], [
                    $folder
                ], [
                    $field
                ]);

                if (!empty($uploaded[0])) {
                    $model->{$field} = $uploaded[0];
                }
            }
        }

        $model->save();
    }

    /**
     * Update files for the given model, replacing existing files if new ones are uploaded.
     *
     * @param  Model   $model
     * @param  Request $request
     * @param  array   $fields
     * @param  string  $baseFolder
     * @return void
     */
    public function updateFiles(Model $model, Request $request, array $fields, string $baseFolder): void
    {
        $folder = $this->buildModelFolder($baseFolder);

        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $uploaded = $this->uploadFile([
                    $request->file($field)
                ], [
                    $folder
                ], [
                    $field
                ], $model);

                if (!empty($uploaded[0])) {
                    $model->{$field} = $uploaded[0];
                }
            } elseif ($request->filled('old_' . $field)) {
                $model->{$field} = $request->input('old_' . $field);
            }
        }

        $model->save();
    }

    /**
     * Delete files for the given model if they exist.
     *
     * @param  Model  $model
     * @param  array  $fields
     * @param  string $baseFolder
     * @return void
     */
    public function deleteFiles(Model $model, array $fields, string $baseFolder): void
    {
        $folder = $this->buildModelFolder($baseFolder);

        foreach ($fields as $field) {
            $files = $model->{$field} ?? null;
            if (empty($files)) {
                continue;
            }

            foreach ((array) $files as $file) {
                $this->deleteFile($file, $folder);
            }
        }
    }

    /**
     * Absolute paths of files written during the current request that must be
     * removed if the surrounding database work fails.
     *
     * @var string[]
     */
    private array $pendingUploads = [];

    /**
     * Absolute paths of superseded files that may only be removed once the
     * surrounding database work has succeeded.
     *
     * @var string[]
     */
    private array $pendingDeletions = [];

    public function uploadSingle(Request $request, string $field, string $baseFolder, ?string $existing = null, ?string $extension = null): ?string
    {
        if (!$request->hasFile($field)) {
            return $existing;
        }

        $folder = $this->buildModelFolder($baseFolder);

        // The replaced file is NOT deleted here: the caller's transaction may
        // still roll back, and the database would then point at a file that no
        // longer exists. Deletion is deferred to commitReplacedFiles().
        $uploaded = $this->uploadFile([
            $request->file($field)
        ], [
            $folder
        ], [
            'path'
        ], null, [
            // Null keeps the client extension, for callers that have no trusted
            // alternative. Ad creatives pass one derived from the detected MIME type.
            $extension,
        ]);

        if (empty($uploaded[0])) {
            return $existing;
        }

        $storedPath = trim($baseFolder, '/') . '/' . $uploaded[0];
        $this->pendingUploads[] = $folder . '/' . $uploaded[0];

        $supersededPath = $this->resolveReplaceablePath($existing, $folder);

        if ($supersededPath !== null) {
            $this->pendingDeletions[] = $supersededPath;
        }

        return $storedPath;
    }

    /**
     * Confirm the uploads: delete the files they replaced.
     *
     * Call this only after the database work has committed.
     */
    public function commitReplacedFiles(): void
    {
        foreach ($this->pendingDeletions as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->resetPendingFiles();
    }

    /**
     * Abandon the uploads: delete the newly written files and keep the ones
     * they would have replaced.
     *
     * Call this when the database work failed.
     */
    public function discardUploadedFiles(): void
    {
        foreach ($this->pendingUploads as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->resetPendingFiles();
    }

    private function resetPendingFiles(): void
    {
        $this->pendingUploads = [];
        $this->pendingDeletions = [];
    }

    /**
     * Decide whether a stored path points at a managed file this service may
     * delete once it has been replaced.
     *
     * Only files living inside the destination folder qualify. Seeded and
     * shared assets (frontend/…, storage/…, remote URLs) are left alone,
     * because other records may still reference them.
     */
    private function resolveReplaceablePath(?string $existing, string $folder): ?string
    {
        if (!$existing || Str::startsWith($existing, ['http://', 'https://'])) {
            return null;
        }

        $candidate = $folder . '/' . basename($existing);

        if (!is_file($candidate)) {
            return null;
        }

        // basename() alone would match a same-named file in a different
        // directory, so compare the resolved directories too.
        $existingDirectory = UploadPath::to(dirname(ltrim($existing, '/')));

        if (realpath($existingDirectory) !== realpath($folder)) {
            return null;
        }

        return $candidate;
    }
}

