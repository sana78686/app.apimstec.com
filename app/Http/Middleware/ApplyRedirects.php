<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyRedirects
{
    /**
     * Apply 301 redirects from the redirects table (SEO URL manager).
     * Only runs for GET/HEAD and non-admin, non-api paths.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = $request->path();
        if ($path === '' || str_starts_with($path, 'api/') || str_starts_with($path, 'seo/')
            || str_starts_with($path, 'dashboard') || str_starts_with($path, 'pages/')
            || str_starts_with($path, 'blogs/') || str_starts_with($path, 'users/')
            || str_starts_with($path, 'roles/') || str_starts_with($path, 'media')
            || str_starts_with($path, 'content-manager') || str_starts_with($path, 'profile')
            || str_starts_with($path, 'login') || str_starts_with($path, 'register')
            || str_starts_with($path, 'password') || str_starts_with($path, 'email')
            || str_starts_with($path, 'sanctum') || str_starts_with($path, '_')) {
            return $next($request);
        }

        $redirect = Redirect::where('from_path', $path)->first();
        if ($redirect) {
            $to = $redirect->to_path;
            if (! str_starts_with($to, '/') && ! str_starts_with($to, 'http')) {
                $to = '/'.$to;
            }
            return redirect($to, (int) $redirect->status_code);
        }

        return $next($request);
    }
}
