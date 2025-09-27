<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUrl
{
    public static function resolve(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $path = (string) $path;

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $relativePath = ltrim($path, '/');
        $disk = config('filesystems.default') ?: env('FILESYSTEM_DISK', 'public');
        $diskConfig = config("filesystems.disks.{$disk}", []);

        if (($diskConfig['driver'] ?? null) === 's3') {
            $storage = Storage::disk($disk);

            if ($storage->exists($relativePath)) {
                return $storage->url($relativePath);
            }

            $baseUrl = $diskConfig['url'] ?? config('filesystems.disks.s3.url');

            if ($baseUrl) {
                return rtrim($baseUrl, '/') . '/' . $relativePath;
            }

            return $storage->url($relativePath);
        }

        return asset($relativePath);
    }
}
