<?php

namespace App\Services;

use App\Contracts\FileServiceInterface;
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
        $folder = public_path(trim($baseFolder, '/'));
        if (!is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }
        return $folder;
    }

    /**
     * Store uploaded files for the given model.
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
     * Handle single file uploads related to CMS section data arrays.
     */
    public function uploadCmsFile(Request $request, string $field, string $baseFolder, ?string $currentPath = null, ?string $oldField = null): ?string
    {
        $oldField = $oldField ?? ('old_' . $field);
        $folder = $this->buildModelFolder($baseFolder);
        $file = $request->file($field);

        if ($file) {
            $fileName = time() . Str::random(20) . '.' . $file->getClientOriginalExtension();
            $file->move($folder, $fileName);

            $this->deleteExistingCmsPath($currentPath);

            return trim($baseFolder, '/') . '/' . $fileName;
        }

        $oldValue = data_get($request->all(), $this->normalizeFieldKey($oldField));
        return $oldValue ?: $currentPath;
    }

    private function normalizeFieldKey(string $field): string
    {
        return str_replace(['[', ']'], ['.', ''], $field);
    }

    private function deleteExistingCmsPath(?string $path): void
    {
        if (!$path) {
            return;
        }

        $normalized = trim(str_replace('\\', '/', $path), '/');
        if ($normalized === '' || !Str::startsWith($normalized, ['upload/', 'storage/'])) {
            return;
        }

        $folder = trim(dirname($normalized), '/.');
        $filename = basename($normalized);

        if ($folder === '' || $folder === '.' || $filename === '') {
            return;
        }

        $this->deleteFile($filename, $folder);
    }
}

