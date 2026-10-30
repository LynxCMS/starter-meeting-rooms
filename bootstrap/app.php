<?php

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
    // Listeners are registered in the service providers, so none is discovered (and run) twice.
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware): void {
        // In production the site sits behind a proxy (Traefik, Cloudflare) that ends HTTPS, and
        // only that proxy can reach the container, so its forwarded scheme and host are trusted.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
