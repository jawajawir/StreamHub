<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $name, array $data = []): void
    {
        Response::html(View::render($name, $data));
    }

    protected function json($data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function redirect(string $to, int $status = 302): void
    {
        Response::redirect($to, $status);
    }

    protected function back(string $fallback = '/'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        $host = $_SERVER['HTTP_HOST'] ?? '';
        // Only allow same-host referers to avoid open-redirect surprises.
        $parsed = parse_url($referer);
        if (!$parsed || ($parsed['host'] ?? $host) !== $host) {
            $referer = $fallback;
        }
        $this->redirect($referer);
    }

    protected function flashErrors(array $errors, array $oldInput = []): void
    {
        $session = Session::instance();
        $session->flashErrors($errors);
        $session->flashOldInput($oldInput);
    }

    protected function validateCsrf(Request $request): void
    {
        $token = $request->post('_csrf') ?? $request->server('HTTP_X_CSRF_TOKEN');
        if (!Csrf::check((string) $token)) {
            \App\Services\SecurityService::logEvent('csrf_failed', 'warning', $request);
            http_response_code(419);
            Response::html('<h1>419</h1><p>CSRF token mismatch. Please refresh and try again.</p>');
            exit;
        }
    }
}
