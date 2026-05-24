<?php
declare(strict_types=1);

/**
 * StreamHub bootstrap.
 * Loaded by public/index.php, cron runner and queue worker.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . basename(__DIR__));
}
// Re-anchor BASE_PATH if bootstrap is called from elsewhere
if (!is_dir(BASE_PATH)) {
    define('SH_BASE', __DIR__);
} else {
    define('SH_BASE', __DIR__);
}

// Always anchor on this file's directory
define('STREAMHUB_BASE', __DIR__);

require __DIR__ . '/app/Core/helpers.php';

// .env loader
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    \App\Core\Env::load($envFile);
}

// Error / exception handling
$debug = env('APP_DEBUG', 'false') === 'true';
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

ini_set('log_errors', '1');
$logDir = __DIR__ . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('error_log', $logDir . '/php-error.log');

// Timezone
date_default_timezone_set((string) (env('APP_TIMEZONE', 'UTC') ?: 'UTC'));

// Lightweight PSR-4-like autoloader for /app
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = STREAMHUB_BASE . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Global exception handler — never leak details in production
set_exception_handler(function (\Throwable $e): void {
    $debug = env('APP_DEBUG', 'false') === 'true';
    $logFile = STREAMHUB_BASE . '/storage/logs/app.log';
    @file_put_contents(
        $logFile,
        sprintf("[%s] %s: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ),
        FILE_APPEND
    );

    // Try to log to error_logs table if DB is available
    try {
        if (\App\Core\Database::hasInstance()) {
            \App\Core\Database::getInstance()->insert('error_logs', [
                'level'     => 'error',
                'channel'   => 'app',
                'message'   => $e->getMessage(),
                'file_path' => $e->getFile(),
                'line_number' => $e->getLine(),
                'context_json' => json_encode([
                    'class' => get_class($e),
                    'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 8),
                ], JSON_UNESCAPED_SLASHES),
            ]);
        }
    } catch (\Throwable $ignored) { /* swallow */ }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    if ($debug) {
        echo '<pre style="background:#111;color:#eee;padding:20px;font:14px monospace">';
        echo htmlspecialchars((string) $e, ENT_QUOTES);
        echo '</pre>';
    } else {
        $template = STREAMHUB_BASE . '/resources/views/frontend/pages/error_500.php';
        if (is_file($template)) {
            include $template;
        } else {
            echo '<h1>500 — Something went wrong</h1>';
        }
    }
});
