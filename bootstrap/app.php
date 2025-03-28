<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web([
            HandleInertiaRequests::class
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdminMiddleware::class,
            'event-owner' => \App\Http\Middleware\VerifyEventOwnerMiddleware::class,
            'ticket-owner' => \App\Http\Middleware\TicketOwnerMiddleware::class,
        ]);
    })->withSchedule(function (Schedule $schedule) {
        $schedule->command('tickvent:send-reminder')
            ->withoutOverlapping()
            ->dailyAt("19:10:00");
    })->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
