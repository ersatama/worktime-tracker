<?php

use App\Exceptions\TimerException;
use App\Http\Middleware\HandleInertiaRequests;
use App\Support\ClientTimezone;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [HandleInertiaRequests::class]);
        // Written by the page's own script, so it is plain text.
        $middleware->encryptCookies(except: [ClientTimezone::COOKIE]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // An answer to the user, not a failure: keep it out of the error log.
        $exceptions->dontReport(TimerException::class);
        $exceptions->render(fn (TimerException $e, Request $request) => $request->expectsJson()
            ? response()->json(['message' => $e->getMessage()], 409)
            : back()->withErrors(['timer' => $e->getMessage()]));
    })->create();
