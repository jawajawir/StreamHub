<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Services\AgeGateService;

/**
 * Server-side age gate enforcement.
 * Bypassed for: admin paths, age-gate routes themselves, sitemap/robots/RSS, embed,
 * legal/static pages, asset routes.
 */
class AgeGateMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): void
    {
        if (!AgeGateService::isEnabled()) {
            $next($request);
            return;
        }
        $path = $request->path();
        $bypass = [
            '/age-gate', '/login', '/register', '/forgot-password', '/reset-password',
            '/sitemap.xml', '/video-sitemap.xml', '/rss.xml', '/robots.txt',
            '/maintenance', '/403', '/404', '/embed',
        ];
        foreach ($bypass as $b) {
            if ($path === $b || str_starts_with($path, $b . '/')) { $next($request); return; }
        }
        if (str_starts_with($path, '/page/')) { $next($request); return; }

        // Logged-in users can be exempted (most adult sites still show once)
        if (\App\Core\Auth::check() && AgeGateService::isPassed($request)) {
            $next($request);
            return;
        }
        if (AgeGateService::isPassed($request)) {
            $next($request);
            return;
        }
        // Remember intended URL and redirect to gate.
        \App\Core\Session::instance()->set('_age_gate_intended', $path);
        Response::redirect('/age-gate');
    }
}
