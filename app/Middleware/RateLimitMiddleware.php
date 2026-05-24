<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Services\RateLimitService;

/**
 * Generic IP-based rate limiter. Configure per-route by subclassing or
 * using app code through RateLimitService directly.
 *
 * Default: 60 requests per 60 seconds, keyed on IP + path.
 */
class RateLimitMiddleware implements Middleware
{
    protected string $key       = 'global';
    protected int    $window    = 60;
    protected int    $maxHits   = 60;

    public function handle(Request $request, callable $next): void
    {
        $hashedSubject = (string) hash_ip($request->ip());
        $key = $this->key . ':' . trim($request->path(), '/');
        $allowed = RateLimitService::hit($key, $hashedSubject, $this->window, $this->maxHits);
        if (!$allowed) {
            \App\Services\SecurityService::logEvent('rate_limited', 'warning', $request, [
                'limiter' => $key,
            ]);
            http_response_code(429);
            if ($request->isAjax() || $request->isJson()) {
                Response::json(['error' => 'rate_limited'], 429);
            } else {
                Response::html('<!doctype html><title>429</title><h1>Too many requests</h1><p>Please slow down and try again shortly.</p>');
            }
            return;
        }
        $next($request);
    }
}
