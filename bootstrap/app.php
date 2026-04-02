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
        // Public React API: reliable CORS (incl. OPTIONS preflight for X-Domain) before the stack.
        $middleware->prepend(\App\Http\Middleware\HandlePublicApiCors::class);

        // Tenant must run after StartSession on web — global prepend ran before session,
        // so session('active_domain_id') was empty and `tenant` fell back to master DB_*.
        $middleware->web(append: [
            \App\Http\Middleware\TenantMiddleware::class,
            \App\Http\Middleware\ApplyRedirects::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Public API (no session): resolve tenant from X-Domain header.
        $middleware->api(append: [
            \App\Http\Middleware\TenantMiddleware::class,
        ]);

        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
            'active.domain' => \App\Http\Middleware\EnsureActiveDomain::class,
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
