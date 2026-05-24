<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class SettingService
{
    private static array $cache = [];
    private static bool $loaded = false;

    public static function preload(): void
    {
        if (self::$loaded) return;
        try {
            $rows = Database::getInstance()->fetchAll('SELECT setting_group, setting_key, setting_value, value_type FROM settings');
            foreach ($rows as $r) {
                self::$cache[$r['setting_group'] . '.' . $r['setting_key']] = self::cast($r['setting_value'], $r['value_type']);
            }
            self::$loaded = true;
        } catch (\Throwable $e) {
            // schema not yet migrated; safe to ignore during installer
        }
    }

    public static function get(string $group, string $key, $default = null)
    {
        self::preload();
        return self::$cache[$group . '.' . $key] ?? $default;
    }

    public static function set(string $group, string $key, $value, string $type = 'string', ?int $adminId = null): void
    {
        $db = Database::getInstance();
        $stored = self::stringify($value, $type);
        $existing = $db->fetch('SELECT id FROM settings WHERE setting_group = :g AND setting_key = :k LIMIT 1', [':g' => $group, ':k' => $key]);
        if ($existing) {
            $db->update('settings', [
                'setting_value' => $stored,
                'value_type'    => $type,
                'updated_by_admin_id' => $adminId,
            ], 'id = :id', [':id' => $existing['id']]);
        } else {
            $db->insert('settings', [
                'setting_group' => $group,
                'setting_key'   => $key,
                'setting_value' => $stored,
                'value_type'    => $type,
                'updated_by_admin_id' => $adminId,
            ]);
        }
        self::$cache[$group . '.' . $key] = self::cast($stored, $type);
    }

    private static function cast($value, string $type)
    {
        return match ($type) {
            'int'  => $value === null ? null : (int) $value,
            'bool' => in_array((string) $value, ['1', 'true', 'on', 'yes'], true),
            'json' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    private static function stringify($value, string $type): string
    {
        return match ($type) {
            'bool' => $value ? '1' : '0',
            'json' => is_string($value) ? $value : (string) json_encode($value),
            default => (string) $value,
        };
    }
}
