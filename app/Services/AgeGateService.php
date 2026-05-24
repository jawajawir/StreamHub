<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Core\Session;

class AgeGateService
{
    private const COOKIE = 'sh_age';

    public static function isEnabled(): bool
    {
        return (bool) SettingService::get('age_gate', 'enabled', true);
    }

    public static function expiryDays(): int
    {
        return (int) (SettingService::get('age_gate', 'expiry_days', 30) ?? 30);
    }

    public static function isPassed(Request $request): bool
    {
        if (Session::instance()->get('_age_gate_passed') === 1) return true;
        if (($request->cookie(self::COOKIE) ?? '') !== '') {
            // Verify against DB record so cookie alone cannot bypass an admin reset.
            return self::recordExists($request);
        }
        return self::recordExists($request);
    }

    private static function recordExists(Request $request): bool
    {
        try {
            $row = Database::getInstance()->fetch(
                'SELECT id FROM age_gate_records
                 WHERE ip_hash = :ip AND (user_agent_hash = :ua OR user_agent_hash IS NULL)
                   AND (expires_at IS NULL OR expires_at >= NOW())
                 ORDER BY passed_at DESC LIMIT 1',
                [':ip' => hash_ip($request->ip()) ?? '', ':ua' => hash_ua($request->userAgent())]
            );
            return $row !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function pass(Request $request): void
    {
        $days = self::expiryDays();
        $expires = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        try {
            $db = Database::getInstance();
            $ip = hash_ip($request->ip()) ?? '';
            $ua = hash_ua($request->userAgent());
            $existing = $db->fetch(
                'SELECT id FROM age_gate_records WHERE ip_hash = :ip AND (user_agent_hash <=> :ua) LIMIT 1',
                [':ip' => $ip, ':ua' => $ua]
            );
            if ($existing) {
                $db->update('age_gate_records', [
                    'passed_at' => date('Y-m-d H:i:s'),
                    'expires_at' => $expires,
                    'source'    => 'cookie',
                ], 'id = :id', [':id' => $existing['id']]);
            } else {
                $db->insert('age_gate_records', [
                    'ip_hash'         => $ip,
                    'user_agent_hash' => $ua,
                    'passed_at'       => date('Y-m-d H:i:s'),
                    'expires_at'      => $expires,
                    'source'          => 'cookie',
                ]);
            }
        } catch (\Throwable $e) {}

        Session::instance()->set('_age_gate_passed', 1);
        $secure   = (bool) config('security.session_secure', false);
        $samesite = (string) config('security.session_samesite', 'Lax');
        if (!headers_sent()) {
            setcookie(self::COOKIE, '1', [
                'expires'  => time() + $days * 86400,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => $samesite,
            ]);
        }
    }
}
