<?php

return [
    'try_ffprobe' => (bool) env('ADS_TRY_FFPROBE', false),

    'fallback' => [
        'type' => env('ADS_FALLBACK_TYPE', 'image'),
        'url' => env('ADS_FALLBACK_URL', 'https://example.com/fallback/default.png'),
        'duration' => (int) env('ADS_FALLBACK_DURATION', 30),
    ],
];
