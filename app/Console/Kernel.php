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
        // Fast SMS polling for urgent orders (popular services, recent orders) every 30 seconds
        $schedule->command('sms:poll-active-orders --fast --limit=20')
                 ->everyThirtySeconds()
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // Regular SMS polling for all orders every 2 minutes
        $schedule->command('sms:poll-active-orders --limit=50')
                 ->everyTwoMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // Refresh popular SMS services every 15 minutes
        $schedule->command('sms:refresh-popular-services')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();

        // HOT refresh: keep hot countries warm every 2 minutes (cache-first UX)
        $schedule->command('sms:refresh-catalog --mode=hot')
                 ->everyTwoMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();

        // FULL refresh hourly to cover long tail
        $schedule->command('sms:refresh-catalog --mode=full')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // Check processing electricity transactions every 5 minutes
        $schedule->command('electricity:check-processing')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // Clean expired meter cache daily at 3 AM
        $schedule->command('meters:clean-expired')
                 ->daily()
                 ->at('03:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');

        // Ensure debug command is registered in non-production too
        if (class_exists(\App\Console\Commands\DebugDassyPrices::class)) {
            $this->commands([
                \App\Console\Commands\DebugDassyPrices::class,
            ]);
        }
    }
}
