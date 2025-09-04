<?php

namespace App\Traits;

trait DeleteFileTrait
{

    public function deleteFile(string $filename,string $folder): void
    {
        if ($filename) {
            $filePath = public_path($folder . '/' . $filename);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

}
