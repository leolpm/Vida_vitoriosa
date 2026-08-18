<?php

use App\Exceptions\PrintFlowAccessException;
use App\Http\Middleware\EnsureAdminUser;
use App\Http\Middleware\ResolveCurrentEvent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            ResolveCurrentEvent::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdminUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PrintFlowAccessException $exception) {
            return response()->view('print-flows.blocked', [
                'message' => $exception->getMessage(),
            ], $exception->status);
        });
    })->create();
