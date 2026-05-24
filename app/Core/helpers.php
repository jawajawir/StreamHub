<?php
declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        $val = getenv($key);
        return $val === false ? $default : $val;
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null) {
        static $cache = [];
        $parts = explode('.', $key);
        $file  = array_shift($parts);
        if (!isset($cache[$file])) {
            $path = STREAMHUB_BASE . '/config/' . $file . '.php';
            if (!is_file($path)) {
                return $default;
            }
            $cache[$file] = require $path;
        }
        $value = $cache[$file];
        foreach ($parts as $p) {
            if (!is_array($value) || !array_key_exists($p, $value)) {
                return $default;
            }
            $value = $value[$p];
        }
        return $value;
    }
}

if (!function_exists('e')) {
    function e($value): string {
        if ($value === null) return '';
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = []): string {
        return \App\Core\View::render($name, $data);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to, int $status = 302): void {
        if (!headers_sent()) {
            header('Location: ' . $to, true, $status);
        }
        exit;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return \App\Core\Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '') {
        $session = \App\Core\Session::instance();
        $old = $session->get('_old_input', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('error_for')) {
    function error_for(string $key): ?string {
        $session = \App\Core\Session::instance();
        $errors = $session->get('_errors', []);
        return $errors[$key] ?? null;
    }
}

if (!function_exists('flash')) {
    function flash(?string $type = null) {
        $session = \App\Core\Session::instance();
        $flash = $session->pull('_flash', []);
        if ($type === null) return $flash;
        return $flash[$type] ?? null;
    }
}

if (!function_exists('hash_ip')) {
    function hash_ip(?string $ip): ?string {
        if (!$ip) return null;
        $key = config('app.key', '');
        return hash_hmac('sha256', $ip, (string) $key);
    }
}

if (!function_exists('hash_ua')) {
    function hash_ua(?string $ua): ?string {
        if (!$ua) return null;
        $key = config('app.key', '');
        return hash_hmac('sha256', $ua, (string) $key);
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string {
        $text = trim($text);
        $text = preg_replace('~[^\\pL\\d]+~u', '-', $text) ?? $text;
        $text = trim($text, '-');
        if (function_exists('iconv')) {
            $converted = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
            if ($converted !== false) $text = $converted;
        }
        $text = strtolower($text);
        $text = preg_replace('~[^-a-z0-9]+~', '', $text) ?? $text;
        return $text === '' ? 'n-a' : $text;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        $appUrl = (string) config('app.url', '');
        return ($appUrl !== '' ? $appUrl : '') . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string {
        $base = '/' . trim((string) config('app.admin_path', 'admin'), '/');
        return $base . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $appUrl = (string) config('app.url', '');
        return ($appUrl !== '' ? $appUrl : '') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('current_url')) {
    function current_url(): string {
        return ($_SERVER['REQUEST_URI'] ?? '/');
    }
}

if (!function_exists('feature_enabled')) {
    function feature_enabled(string $featureKey): bool {
        return \App\Services\FeatureToggleService::enabled($featureKey);
    }
}

if (!function_exists('setting')) {
    function setting(string $group, string $key, $default = null) {
        return \App\Services\SettingService::get($group, $key, $default);
    }
}
