<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function html(string $body, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        if (!self::headerSent('Content-Type')) {
            header('Content-Type: text/html; charset=utf-8');
        }
        foreach ($headers as $k => $v) {
            header($k . ': ' . $v);
        }
        echo $body;
    }

    public static function json($data, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($headers as $k => $v) {
            header($k . ': ' . $v);
        }
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function redirect(string $to, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $to);
    }

    public static function notFound(string $message = 'Not Found'): void
    {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        $template = STREAMHUB_BASE . '/resources/views/frontend/pages/error_404.php';
        if (is_file($template)) {
            $request = Request::fromGlobals();
            include $template;
        } else {
            echo '<h1>404</h1><p>' . e($message) . '</p>';
        }
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        http_response_code(403);
        $template = STREAMHUB_BASE . '/resources/views/frontend/pages/error_403.php';
        if (is_file($template)) {
            include $template;
        } else {
            echo '<h1>403</h1><p>' . e($message) . '</p>';
        }
    }

    public static function xml(string $body, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/xml; charset=utf-8');
        echo $body;
    }

    public static function plain(string $body, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo $body;
    }

    private static function headerSent(string $name): bool
    {
        foreach (headers_list() as $h) {
            if (stripos($h, $name . ':') === 0) return true;
        }
        return false;
    }
}
