<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Operational data retention
    |--------------------------------------------------------------------------
    |
    | How many days of each kind of operational record to keep. These drive the
    | `prunable()` queries on ScreenLog, PlaybackLog and Report, which the
    | scheduled `model:prune` task executes.
    |
    | RETENTION IS DISABLED BY DEFAULT AND STAYS DISABLED UNTIL SOMEONE SETS A
    | POSITIVE VALUE. A null, zero, empty or negative value means "keep
    | everything" — nothing is deleted. That is deliberate: these tables hold
    | telemetry and commercial proof-of-play, and no arbitrary period may be
    | invented on the project's behalf. There is no authoritative business
    | retention period recorded anywhere in this repository, so the mechanism is
    | shipped and the values are left for the operator to choose.
    |
    | `playback_logs` in particular is proof-of-play evidence. Set it only once
    | the commercial retention requirement is known.
    |
    | Read only through App\Support\Retention — nothing else may interpret these
    | keys or decide what "disabled" means.
    */

    'screen_logs_days' => env('SCREEN_LOG_RETENTION_DAYS'),

    'playback_logs_days' => env('PLAYBACK_LOG_RETENTION_DAYS'),

    'reports_days' => env('REPORT_RETENTION_DAYS'),

];
