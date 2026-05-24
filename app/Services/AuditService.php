<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;

class AuditService
{
    /**
     * Write an audit log row.
     * @param array|null $before Previous state (PII / secrets must be redacted by caller).
     * @param array|null $after  New state.
     */
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, ?array $before = null, ?array $after = null): void
    {
        try {
            $request = Request::fromGlobals();
            $admin = Auth::admin();
            $user  = Auth::user();
            Database::getInstance()->insert('audit_logs', [
                'admin_id'        => $admin['id'] ?? null,
                'user_id'         => $user['id']  ?? null,
                'action'          => substr($action, 0, 160),
                'entity_type'     => $entityType,
                'entity_id'       => $entityId,
                'before_json'     => $before !== null ? json_encode(self::redact($before), JSON_UNESCAPED_SLASHES) : null,
                'after_json'      => $after  !== null ? json_encode(self::redact($after),  JSON_UNESCAPED_SLASHES) : null,
                'ip_hash'         => hash_ip($request->ip()),
                'user_agent_hash' => hash_ua($request->userAgent()),
            ]);
        } catch (\Throwable $e) {
            // Never break the request because audit logging failed.
        }
    }

    private static function redact(array $data): array
    {
        $sensitive = ['password', 'password_hash', 'token', 'token_hash', 'api_key', 'encrypted_value', 'remember_token_hash'];
        foreach ($data as $k => $v) {
            if (is_array($v)) { $data[$k] = self::redact($v); continue; }
            if (in_array(strtolower((string) $k), $sensitive, true)) {
                $data[$k] = '[REDACTED]';
            }
        }
        return $data;
    }

    /** Recent audit log entries for the dashboard / system page. */
    public static function recent(int $limit = 20): array
    {
        try {
            return Database::getInstance()->fetchAll(
                'SELECT a.*, u.username AS admin_username
                 FROM audit_logs a
                 LEFT JOIN admin_users u ON u.id = a.admin_id
                 ORDER BY a.created_at DESC LIMIT ' . max(1, min(200, $limit))
            );
        } catch (\Throwable $e) {
            return [];
        }
    }
}
