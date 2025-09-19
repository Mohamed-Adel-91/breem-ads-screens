<?php

return [
    'try_ffprobe' => (bool) env('ADS_TRY_FFPROBE', false),

    'fallback' => [
        'type' => env('ADS_FALLBACK_TYPE', 'image'),
        'url' => env('ADS_FALLBACK_URL', 'https://example.com/fallback/default.png'),
        'duration' => (int) env('ADS_FALLBACK_DURATION', 30),
    ],
    'ffprobe_command' => env('ADS_FFPROBE_COMMAND', 'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1'),
];
