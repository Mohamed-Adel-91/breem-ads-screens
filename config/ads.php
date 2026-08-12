<?php

return [
    'try_ffprobe' => (bool) env('ADS_TRY_FFPROBE', false),

    'ffprobe_bin' => env('FFPROBE_BIN', 'ffprobe'),

    'fallback' => [
        'type' => env('ADS_FALLBACK_TYPE', 'image'),
        'image' => env('ADS_FALLBACK_URL', 'images/fallback.png'),
        'duration' => (int) env('ADS_FALLBACK_DURATION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Creative upload limits
    |--------------------------------------------------------------------------
    |
    | Maximum accepted creative size per media category, in kilobytes. There was
    | previously no `max:` rule on the creative at all, so the only ceiling was
    | whatever PHP happened to allow.
    |
    | These are an application ceiling, not a replacement for one: an upload is
    | still bounded by PHP's `upload_max_filesize` / `post_max_size` and by any
    | web-server body limit, whichever is smallest. Raising a value here does
    | nothing unless the platform allows it too.
    |
    | Read only through App\Support\CreativeMedia — nothing else may interpret
    | these keys.
    */
    'upload' => [
        'image_max_kb' => (int) env('ADS_IMAGE_MAX_KB', 5120),    // 5 MB
        'gif_max_kb' => (int) env('ADS_GIF_MAX_KB', 10240),       // 10 MB
        'video_max_kb' => (int) env('ADS_VIDEO_MAX_KB', 153600),  // 150 MB
    ],
];
