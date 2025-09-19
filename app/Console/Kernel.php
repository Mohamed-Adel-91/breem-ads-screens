<?php

namespace App\Console;

use App\Jobs\CheckExpiringAdsJob;
use App\Jobs\CheckScreenHealthJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new CheckScreenHealthJob())
            ->cron('* * * * *')
            ->withoutOverlapping();

        $schedule->job(new CheckExpiringAdsJob())
            ->cron('0 9 * * *')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
