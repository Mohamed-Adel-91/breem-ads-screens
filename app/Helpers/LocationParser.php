<?php

namespace App\Helpers;

class LocationParser
{
    /**
     * Parse latitude and longitude from a location string.
     *
     * @param string|null $location
     * @return array{0: ?string, 1: ?string}
     */
    public static function parse(?string $location): array
    {
        if (!$location) {
            return [null, null];
        }

        if (str_contains($location, '!2d') && str_contains($location, '!3d')) {
            if (preg_match('/!2d(-?\\d+(?:\\.\\d+)?)!3d(-?\\d+(?:\\.\\d+)?)/', $location, $matches)) {
                return [$matches[2], $matches[1]];
            }
        }

        if (preg_match('/(-?\\d+(?:\\.\\d+)?)[,\\s]+(-?\\d+(?:\\.\\d+)?)/', $location, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return [null, null];
    }
}
