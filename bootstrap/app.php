<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
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
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Without this, tripping the login/register rate limiter renders Laravel's
        // generic unstyled 429 page — on a retry after a couple of mistyped
        // passwords or a double-tap submit on mobile, that reads as "the app is
        // broken" rather than "you're being rate limited." Show it inline instead.
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            if ($request->is('login') || $request->is('register')) {
                return back()->withErrors([
                    'email' => 'Too many attempts. Please wait a minute and try again.',
                ])->onlyInput('email');
            }
        });
    })->create();
