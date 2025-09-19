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
}
