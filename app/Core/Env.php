<?php
declare(strict_types=1);

namespace App\Core;

class Env
{
    public static function load(string $file): void
    {
        if (!is_readable($file)) {
            return;
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            if ($k === '') continue;
            // strip surrounding quotes
            if (strlen($v) >= 2) {
                $first = $v[0];
                $last  = $v[strlen($v) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $v = substr($v, 1, -1);
                }
            }
            $_ENV[$k] = $v;
            putenv("$k=$v");
        }
    }

    public static function write(string $file, array $values): bool
    {
        $existing = [];
        if (is_file($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
            foreach ($lines as $line) {
                if ($line === '' || str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                    $existing[] = $line;
                    continue;
                }
                [$k] = explode('=', $line, 2);
                $k = trim($k);
                if (array_key_exists($k, $values)) {
                    $existing[] = $k . '=' . self::escape((string) $values[$k]);
                    unset($values[$k]);
                } else {
                    $existing[] = $line;
                }
            }
        }
        foreach ($values as $k => $v) {
            $existing[] = $k . '=' . self::escape((string) $v);
        }
        return file_put_contents($file, implode("\n", $existing) . "\n") !== false;
    }

    private static function escape(string $value): string
    {
        if ($value === '' || preg_match('/[\s"\']/', $value)) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }
        return $value;
    }
}
