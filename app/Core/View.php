<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    private static array $shared = [];

    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function shared(): array
    {
        return self::$shared;
    }

    public static function render(string $name, array $data = []): string
    {
        $path = STREAMHUB_BASE . '/resources/views/' . str_replace('.', '/', $name) . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('View not found: ' . $name);
        }
        $vars = array_merge(self::$shared, $data);
        extract($vars, EXTR_SKIP);
        ob_start();
        try {
            include $path;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    public static function partial(string $name, array $data = []): void
    {
        echo self::render($name, $data);
    }
}
