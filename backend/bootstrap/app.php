<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Enable Sanctum cookie-based SPA authentication
        $middleware->statefulApi();

        // Prevent API requests from redirecting to a login page.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*')
                ? null
                : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (
            InvalidSignatureException $exception,
            Request $request
        ) {
            if ($request->routeIs('verification.verify')) {
                return redirect()->away(
                    rtrim(config('app.frontend_url'), '/')
                    . '/email/verification-result?status=error'
                );
            }

            return null;
        });
    })->create();
