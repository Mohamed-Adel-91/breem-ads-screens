<?php

namespace App\Console;

use App\Jobs\CheckExpiringAdsJob;
use App\Jobs\CheckScreenHealthJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * INERT — retained as project structure, but nothing binds this class.
 *
 * Laravel 12 resolves Illuminate\Foundation\Console\Kernel directly and there is
 * no binding anywhere in bootstrap/app.php or the providers that swaps in this
 * subclass. The schedule() method below therefore never runs, which is why
 * CheckScreenHealthJob went undispatched for the whole life of the project.
 *
 * The live schedule is routes/console.php, registered through the `commands:`
 * argument in bootstrap/app.php and visible in `php artisan schedule:list`.
 * Add schedule entries there, not here.
 */
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * Never invoked — see the class docblock.
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
