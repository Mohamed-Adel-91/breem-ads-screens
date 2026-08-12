<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface FileServiceInterface
{
    public function storeFiles(Model $model, Request $request, array $fields, string $baseFolder): void;

    public function updateFiles(Model $model, Request $request, array $fields, string $baseFolder): void;

    public function deleteFile(string $filename, string $folder): void;

    public function uploadSingle(Request $request, string $field, string $baseFolder, ?string $existing = null): ?string;

    /**
     * Delete the files superseded by this request's uploads.
     * Only safe once the surrounding database work has committed.
     */
    public function commitReplacedFiles(): void;

    /**
     * Delete this request's uploads and keep the files they would have
     * replaced. Use when the surrounding database work failed.
     */
    public function discardUploadedFiles(): void;
}
