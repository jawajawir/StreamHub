<?php
declare(strict_types=1);

namespace App\Core;

class FileStorage
{
    public static function ensureDir(string $relative): string
    {
        $abs = STREAMHUB_BASE . '/storage/' . trim($relative, '/');
        if (!is_dir($abs)) {
            @mkdir($abs, 0775, true);
        }
        return $abs;
    }

    public static function uploads(): string { return self::ensureDir('uploads'); }
    public static function imports(): string { return self::ensureDir('imports'); }
    public static function tmp(): string     { return self::ensureDir('tmp'); }
    public static function cache(): string   { return self::ensureDir('cache'); }
    public static function logs(): string    { return self::ensureDir('logs'); }

    public static function safeFilename(string $original): string
    {
        $base = pathinfo($original, PATHINFO_FILENAME);
        $ext  = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $base = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $base) ?? 'file';
        return sprintf('%s-%s.%s', substr($base, 0, 60), bin2hex(random_bytes(6)), $ext ?: 'bin');
    }

    public static function lock(string $name, callable $callback)
    {
        $file = self::tmp() . '/' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $name) . '.lock';
        $fp = @fopen($file, 'c+');
        if (!$fp) return $callback(); // fail-open
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return null;
        }
        try {
            return $callback();
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
