<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Delete expired files every minute (for testing)
        // In production, change to ->hourly() or ->daily()
        $schedule->command('files:delete-expired')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        $this->load(__DIR__.'/../Modules/Notification/Console');
        $this->load(__DIR__.'/../Modules/Scheduler/Console');

        require base_path('routes/console.php');
    }
}
