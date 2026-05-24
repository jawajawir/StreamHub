<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function user(): ?array
    {
        $session = Session::instance();
        $id = $session->get('_user_id');
        if (!$id) return null;
        $row = Database::getInstance()->fetch(
            'SELECT id, username, email, display_name, avatar_path, status, membership_tier, email_verified_at FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        );
        return $row ?: null;
    }

    public static function admin(): ?array
    {
        $session = Session::instance();
        $id = $session->get('_admin_id');
        if (!$id) return null;
        $row = Database::getInstance()->fetch(
            'SELECT id, username, email, role, status FROM admin_users WHERE id = :id AND status = "active" LIMIT 1',
            [':id' => $id]
        );
        return $row ?: null;
    }

    public static function loginUser(int $userId): void
    {
        $session = Session::instance();
        $session->regenerate();
        $session->set('_user_id', $userId);
        Csrf::rotate();
        Database::getInstance()->update('users',
            ['last_login_at' => date('Y-m-d H:i:s'), 'last_login_ip_hash' => hash_ip(Request::fromGlobals()->ip())],
            'id = :id', [':id' => $userId]
        );
    }

    public static function loginAdmin(int $adminId): void
    {
        $session = Session::instance();
        $session->regenerate();
        $session->set('_admin_id', $adminId);
        Csrf::rotate();
        Database::getInstance()->update('admin_users',
            ['last_login_at' => date('Y-m-d H:i:s'), 'last_login_ip_hash' => hash_ip(Request::fromGlobals()->ip())],
            'id = :id', [':id' => $adminId]
        );
    }

    public static function logoutUser(): void
    {
        $session = Session::instance();
        $session->forget('_user_id');
        $session->regenerate();
    }

    public static function logoutAdmin(): void
    {
        $session = Session::instance();
        $session->forget('_admin_id');
        $session->regenerate();
    }

    public static function check(): bool { return self::user() !== null; }
    public static function checkAdmin(): bool { return self::admin() !== null; }

    public static function membershipTier(): string
    {
        $u = self::user();
        return $u['membership_tier'] ?? 'guest';
    }
}
