<?php

return [
    'try_ffprobe' => (bool) env('ADS_TRY_FFPROBE', false),

    'ffprobe_bin' => env('FFPROBE_BIN', 'ffprobe'),

    'fallback' => [
        'type' => env('ADS_FALLBACK_TYPE', 'image'),
        'image' => env('ADS_FALLBACK_URL', 'images/fallback.png'),
        'duration' => (int) env('ADS_FALLBACK_DURATION', 30),
    ],
];
