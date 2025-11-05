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
        $schedule->command('visitor:backup')
            ->weeklyOn(0, '3:00')
            ->timezone('Africa/Lagos');

        // Run every Sunday at 2:00 AM
        $schedule->command('backup:weekly-incremental')
            ->weeklyOn(0, '2:00')
            ->timezone(config('app.timezone'));

        // Spatie backup cleanup: daily at 3:30 AM
        $schedule->command('backup:clean')
            ->dailyAt('3:30')
            ->timezone(config('app.timezone'))
            ->withoutOverlapping();

        // Spatie backup monitor health: every morning at 8:00 AM
        $schedule->command('backup:monitor')
            ->dailyAt('8:00')
            ->timezone(config('app.timezone'))
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
