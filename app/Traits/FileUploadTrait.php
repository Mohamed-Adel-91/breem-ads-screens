<?php

namespace App\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait FileUploadTrait
{

    public function uploadFile(array $files, array $folders, array $attributes = [], Model $model = null)
    {
        try {

            $fileNames = [];

            foreach ($files as $key => $file) {
                if (isset($file)) {

                    $fileName = time() . Str::random(20) . '.' . $file->getClientOriginalExtension();
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
