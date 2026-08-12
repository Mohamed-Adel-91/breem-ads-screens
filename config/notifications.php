<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Operational notification recipient
    |--------------------------------------------------------------------------
    |
    | Where fleet alerts go: a screen dropping offline, an advertisement about to
    | expire. This is an OPERATIONS address, not a customer or CMS contact address,
    | and it is deliberately a single configured mailbox rather than "every admin
    | account" — the admins table is an authentication list, not a distribution
    | list, and mailing all of it would turn every new account into an alert
    | subscriber.
    |
    | When this is unset, App\Support\OperationsRecipients falls back to
    | `config('admin.email')` (ADMIN_EMAIL), which is what the jobs read today, so an
    | existing deployment keeps working unchanged. The fallback is applied at READ
    | time, not baked in here by a nested env() call — otherwise anything that
    | overrode `admin.email` after boot would be silently ignored.
    |
    | Set OPS_NOTIFICATION_EMAIL to separate operational alerting from the seeded
    | administrator account.
    |
    | Leaving both unset does not break anything: offline detection still runs and
    | still records the transition. The delivery attempt is skipped and a warning is
    | logged — see App\Support\OperationsRecipients. `php artisan ops:status`
    | reports whether this is configured.
    |
    | Read only through App\Support\OperationsRecipients.
    */

    'operations' => [
        'email' => env('OPS_NOTIFICATION_EMAIL'),
    ],

];
