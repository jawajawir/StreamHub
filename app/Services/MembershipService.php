<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class MembershipService
{
    /** @return array<int, array<string,mixed>> */
    public static function plans(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
    }

    public static function planByCode(string $code): ?array
    {
        return Database::getInstance()->fetch(
            'SELECT * FROM membership_plans WHERE code = :c AND is_active = 1 LIMIT 1',
            [':c' => $code]
        );
    }

    /** Apply / change a user's membership tier and create a user_memberships row. */
    public static function activate(int $userId, string $code, ?int $adminId, ?string $expiresAt = null, ?string $note = null): bool
    {
        $plan = self::planByCode($code);
        if (!$plan) return false;
        $db = Database::getInstance();
        $db->transaction(function () use ($db, $userId, $plan, $adminId, $expiresAt, $note) {
            $db->update('users', ['membership_tier' => $plan['code']], 'id = :id', [':id' => $userId]);
            $db->insert('user_memberships', [
                'user_id'  => $userId,
                'plan_id'  => $plan['id'],
                'status'   => 'active',
                'starts_at'=> date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt,
                'activated_by_admin_id' => $adminId,
                'activation_note' => $note,
            ]);
        });
        return true;
    }

    public static function expireDueMemberships(): int
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            'SELECT um.id, um.user_id FROM user_memberships um
             WHERE um.status = "active" AND um.expires_at IS NOT NULL AND um.expires_at <= NOW()'
        );
        $count = 0;
        foreach ($rows as $r) {
            $db->update('user_memberships', ['status' => 'expired'], 'id = :id', [':id' => $r['id']]);
            $db->update('users', ['membership_tier' => 'free'], 'id = :id', [':id' => $r['user_id']]);
            $count++;
        }
        return $count;
    }
}
