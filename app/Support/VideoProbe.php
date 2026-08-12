<?php

namespace App\Support;

class VideoProbe
{
    public static function durationSeconds(?string $filePath): ?int
    {
        if (!$filePath) {
            return null;
        }

        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            return null;
        }

        // Same physical root the uploader wrote to, so a freshly stored creative
        // is probed where it actually landed. Production default is unchanged.
        $absolutePath = UploadPath::to($filePath);

        if (!$absolutePath || !is_file($absolutePath)) {
            return null;
        }

        $binary = config('ads.ffprobe_bin');

        if (!is_string($binary) || trim($binary) === '') {
            return null;
        }

        if (!function_exists('shell_exec')) {
            return null;
        }

        $escapedPath = escapeshellarg($absolutePath);

        $command = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
            escapeshellcmd(trim($binary)),
            $escapedPath
        );

        $output = @shell_exec($command);

        if (!is_string($output)) {
            return null;
        }

        $output = trim($output);

        if ($output === '' || !is_numeric($output)) {
            return null;
        }

        return (int) round((float) $output);
    }
}
