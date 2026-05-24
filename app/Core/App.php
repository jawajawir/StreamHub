<?php
declare(strict_types=1);

namespace App\Core;

class App
{
    public static function boot(): void
    {
        Session::instance();
        // Send default security headers
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            $request = Request::fromGlobals();
            if ($request->isHttps()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
            // Watch/embed pages need a relaxed CSP set per-route. Default policy here is conservative.
            // Frame ancestors will be relaxed for /embed routes individually.
            $appHost = parse_url((string) config('app.url', ''), PHP_URL_HOST) ?? '';
            header('X-Frame-Options: SAMEORIGIN');
        }

        // Share globals for views
        View::share('auth_user',  Auth::user());
        View::share('auth_admin', Auth::admin());
        View::share('csrf',       Csrf::token());
        View::share('app_name',   (string) config('app.name', 'StreamHub'));
        View::share('app_url',    (string) config('app.url', ''));
        View::share('admin_path', '/' . trim((string) config('app.admin_path', 'admin'), '/'));
    }
}
