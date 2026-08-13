<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum report period
    |--------------------------------------------------------------------------
    |
    | The longest window, in days, a single report may be generated over.
    |
    | WHY THERE IS A CEILING AT ALL. `from_date` and `to_date` were unbounded, and
    | the two report types do not cost the same to build. A playback report is SQL
    | aggregation and stays flat whatever the period. A screen-uptime report is a
    | *timeline* measurement: App\Services\Monitoring\ScreenAvailabilityService walks
    | the log stream per screen and reconstructs every online/offline/maintenance
    | segment in the window. Asking for `from_date=1970-01-01` therefore asked the
    | server to walk sixty years of stream once per screen, in a web request, and an
    | authenticated operator could do it by typing a date.
    |
    | The bound is on the period an operator ASKS for, not on the data that happens
    | to exist, so it cannot be defeated by an empty log table.
    |
    | 366 days is one full calendar year including a leap year: it covers monthly,
    | quarterly and annual reporting, which is every period this product describes,
    | while keeping a single request bounded. It is a DEFAULT, not a product
    | requirement — no reporting-period requirement is recorded anywhere in this
    | repository. Raise it with REPORT_MAX_PERIOD_DAYS if the business needs a longer
    | window, and expect a longer request.
    |
    | A null, zero, negative or non-numeric value means "no ceiling" and restores the
    | previous unbounded behaviour. That is a deliberate escape hatch for a one-off
    | historical report; it is not a good permanent setting.
    |
    | Read only through App\Support\ReportPeriod — nothing else may interpret this
    | key or decide what "no ceiling" means.
    */

    'max_period_days' => env('REPORT_MAX_PERIOD_DAYS', 366),

];
