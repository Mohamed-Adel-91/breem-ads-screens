<?php

namespace App\Traits;

use App\Support\UploadPath;

trait DeleteFileTrait
{

    public function deleteFile(string $filename, string $folder): void
    {
        if ($filename) {
            // Resolved through UploadPath so deletions target the same physical
            // root that uploads were written to. Production default is unchanged.
            $filePath = UploadPath::to($folder . '/' . $filename);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

}
