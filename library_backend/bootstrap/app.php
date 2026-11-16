
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
   ->withSchedule(function (Schedule $schedule): void {
    $schedule->command('library:process-overdue')
        ->dailyAt('08:00')
        ->withoutOverlapping();

    $schedule->command('library:process-pending-notifications')
        ->everyMinute()
        ->withoutOverlapping();

    $schedule->command('library:daily-exception-summary')
        ->dailyAt('17:00')
        ->withoutOverlapping();
})
    ->create();
