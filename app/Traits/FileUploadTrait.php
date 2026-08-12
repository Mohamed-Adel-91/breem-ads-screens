<?php

namespace App\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait FileUploadTrait
{

    /**
     * @param  array<int, string|null>  $extensions  Per-file extension overrides. When
     *   an entry is present the stored file uses it instead of the client-supplied
     *   extension. Callers that can determine the type from the file's own contents
     *   should always pass one: the client filename is attacker-controlled, so
     *   copying its extension is how a non-image ends up on disk with an executable
     *   suffix inside a web-served directory. Omitting the argument keeps the
     *   previous behaviour for existing callers.
     */
    public function uploadFile(array $files, array $folders, array $attributes = [], Model $model = null, array $extensions = [])
    {
        try {

            $fileNames = [];

            foreach ($files as $key => $file) {
                if (isset($file)) {

                    $extension = $extensions[$key] ?? $file->getClientOriginalExtension();
                    $fileName = time() . Str::random(20) . '.' . $extension;
                    $file->move($folders[$key], $fileName);
                    $fileNames[] = $fileName;

                    if (isset($model)) {
                        if ($model->{$attributes[$key]} !== null) {
                            if (file_exists($folders[$key] . '/' . $model->{$attributes[$key]})) {
                                unlink($folders[$key] . '/' . $model->{$attributes[$key]});
                            }
                        }
                    }

                }

            }

            return $fileNames;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}
