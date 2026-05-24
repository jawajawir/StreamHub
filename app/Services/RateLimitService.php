<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * DB-backed sliding-window rate limiter.
 * Uses rate_limit_hits table.
 */
class RateLimitService
{
    public static function hit(string $key, string $subjectHash, int $windowSeconds, int $maxHits): bool
    {
        try {
            $db = Database::getInstance();
            $now = time();
            $windowStart = date('Y-m-d H:i:s', $now - ($now % $windowSeconds));
            $windowEnd   = date('Y-m-d H:i:s', $now - ($now % $windowSeconds) + $windowSeconds);

            $row = $db->fetch(
                'SELECT * FROM rate_limit_hits WHERE limiter_key = :k AND subject_hash = :s AND window_starts_at = :ws LIMIT 1',
                [':k' => $key, ':s' => $subjectHash, ':ws' => $windowStart]
            );
            if (!$row) {
                $db->insert('rate_limit_hits', [
                    'limiter_key'      => $key,
                    'subject_hash'     => $subjectHash,
                    'hit_count'        => 1,
                    'window_starts_at' => $windowStart,
                    'window_ends_at'   => $windowEnd,
                ]);
                return true;
            }
            if ((int) $row['hit_count'] >= $maxHits) {
                $db->update('rate_limit_hits',
                    ['hit_count' => (int) $row['hit_count'] + 1, 'blocked_until' => $windowEnd],
                    'id = :id', [':id' => $row['id']]
                );
                return false;
            }
            $db->update('rate_limit_hits',
                ['hit_count' => (int) $row['hit_count'] + 1],
                'id = :id', [':id' => $row['id']]
            );
            return true;
        } catch (\Throwable $e) {
            // Fail-open: rather not break the site if rate-limit table has issues.
            return true;
        }
    }

    public static function loginAttempt(string $identifier, string $userType, bool $success, ?string $reason, string $ipHash, string $uaHash): void
    {
        try {
            Database::getInstance()->insert('login_attempts', [
                'identifier'      => substr($identifier, 0, 190),
                'user_type'       => $userType,
                'ip_hash'         => $ipHash,
                'user_agent_hash' => $uaHash,
                'success'         => $success ? 1 : 0,
                'failure_reason'  => $reason,
            ]);
        } catch (\Throwable $e) {}
    }

    public static function recentFailedLoginCount(string $identifier, string $userType, int $windowSeconds): int
    {
        try {
            $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
            $row = Database::getInstance()->fetch(
                'SELECT COUNT(*) AS c FROM login_attempts
                 WHERE identifier = :i AND user_type = :t AND success = 0 AND created_at >= :c',
                [':i' => $identifier, ':t' => $userType, ':c' => $cutoff]
            );
            return (int) ($row['c'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
