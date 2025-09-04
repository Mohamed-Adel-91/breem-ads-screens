<?php

namespace App\Services;

use App\Contracts\FileServiceInterface;
use App\Traits\DeleteFileTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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
}
