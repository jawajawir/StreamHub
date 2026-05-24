<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

/**
 * Verifies CSRF token on all state-changing requests.
 * Logs failures to security_events.
 */
class CsrfMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): void
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->post('_csrf') ?? $request->server('HTTP_X_CSRF_TOKEN');
            if (!Csrf::check((string) $token)) {
                \App\Services\SecurityService::logEvent('csrf_failed', 'warning', $request);
                http_response_code(419);
                if ($request->isAjax() || $request->isJson()) {
                    Response::json(['error' => 'csrf_token_mismatch'], 419);
                } else {
                    Response::html('<!doctype html><meta charset="utf-8"><title>419</title>'
                        . '<style>body{font:16px system-ui;max-width:520px;margin:80px auto;padding:0 20px;color:#222}</style>'
                        . '<h1>Session expired</h1><p>Please refresh the page and try again.</p>');
                }
                return;
            }
        }
        $next($request);
    }
}
