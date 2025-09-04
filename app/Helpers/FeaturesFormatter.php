<?php

namespace App\Helpers;

class FeaturesFormatter
{
    public static function normalize($features, bool $addEmptyRow = true): array
    {
        if (is_string($features)) {
            $decoded = json_decode($features, true);
            $features = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($features)) {
            $features = [];
        }

        $norm = collect($features)->map(function ($f) {
            if (is_string($f)) {
                return ['name' => $f, 'value' => ''];
            }
            return [
                'name'  => is_array($f) ? (string)($f['name']  ?? '') : '',
                'value' => is_array($f) ? (string)($f['value'] ?? '') : '',
            ];
        })
        ->filter(fn ($f) => $f['name'] !== '' || $f['value'] !== '')
        ->values()
        ->all();

        if ($addEmptyRow && empty($norm)) {
            $norm = [['name' => '', 'value' => '']];
        }

        return $norm;
    }

    public static function sanitize($features): array
    {
        if (is_string($features)) {
            $features = json_decode($features, true);
        }
        if (!is_array($features)) {
            return [];
        }

        return collect($features)->map(fn ($f) => [
            'name'  => isset($f['name'])  ? trim((string)$f['name'])  : '',
            'value' => isset($f['value']) ? trim((string)$f['value']) : '',
        ])
        ->filter(fn ($f) => $f['name'] !== '' || $f['value'] !== '')
        ->values()
        ->all();
    }
}
