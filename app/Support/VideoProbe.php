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

        $absolutePath = public_path(ltrim($filePath, '/'));

        if (!$absolutePath || !is_file($absolutePath)) {
            return null;
        }

        $commandTemplate = config('ads.ffprobe.command');

        if (!is_string($commandTemplate) || trim($commandTemplate) === '') {
            return null;
        }

        if (!function_exists('shell_exec')) {
            return null;
        }

        $escapedPath = escapeshellarg($absolutePath);

        if (str_contains($commandTemplate, '{path}')) {
            $command = str_replace('{path}', $escapedPath, $commandTemplate);
        } elseif (str_contains($commandTemplate, '%s')) {
            $command = sprintf($commandTemplate, $escapedPath);
        } else {
            $command = $commandTemplate . ' ' . $escapedPath;
        }

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
