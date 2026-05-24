<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class FeatureToggleService
{
    private static array $cache = [];
    private static bool $loaded = false;

    public static function preload(): void
    {
        if (self::$loaded) return;
        try {
            $rows = Database::getInstance()->fetchAll('SELECT feature_key, is_enabled FROM feature_toggles');
            foreach ($rows as $r) {
                self::$cache[$r['feature_key']] = (int) $r['is_enabled'] === 1;
            }
            self::$loaded = true;
        } catch (\Throwable $e) {
            // schema not yet migrated
        }
    }

    public static function enabled(string $key): bool
    {
        self::preload();
        return self::$cache[$key] ?? false;
    }

    public static function set(string $key, bool $enabled, ?int $adminId = null): void
    {
        $db = Database::getInstance();
        $db->query('UPDATE feature_toggles SET is_enabled = :v, updated_by_admin_id = :a WHERE feature_key = :k', [
            ':v' => $enabled ? 1 : 0, ':a' => $adminId, ':k' => $key,
        ]);
        self::$cache[$key] = $enabled;
    }

    public static function all(): array
    {
        try {
            return Database::getInstance()->fetchAll('SELECT * FROM feature_toggles ORDER BY feature_group, feature_name');
        } catch (\Throwable $e) {
            return [];
        }
    }
}
