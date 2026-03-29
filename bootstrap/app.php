<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // TenantMiddleware runs on every request (web + api) to switch the
        // `tenant` DB connection based on active domain session or X-Domain header.
        $middleware->prepend(\App\Http\Middleware\TenantMiddleware::class);

        $middleware->web(append: [
            \App\Http\Middleware\ApplyRedirects::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            try {
                \App\Models\BrokenLinkLog::log404($request->path(), $request->header('referer'));
            } catch (\Throwable $ignored) {
                // Table may not exist yet on fresh deployment
            }
            return null;
        });
    })->create();
