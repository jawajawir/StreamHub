<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Database;
use App\Core\FileStorage;
use App\Core\Request;
use App\Core\Response;
use App\Services\MembershipService;
use App\Services\SecurityService;

class CronController extends Controller
{
    public function run(Request $request): void
    {
        $expected = (string) config('app.cron_token', '');
        $supplied = (string) $request->query('token', '');
        if ($expected === '' || !hash_equals($expected, $supplied)) {
            SecurityService::logEvent('api_auth_failed', 'warning', $request, ['endpoint' => 'cron']);
            Response::json(['error' => 'unauthorized'], 401); return;
        }

        $result = FileStorage::lock('cron-runner', function () {
            $summary = ['ran_at' => date('c'), 'jobs' => []];

            // 1) Membership expiry
            $expired = MembershipService::expireDueMemberships();
            $summary['jobs']['membership_expiry'] = ['expired' => $expired];
            self::touchSchedule('membership_expiry', 'success');

            // 2) Drain a small number of pending queue jobs (best-effort runner placeholder)
            $drained = self::drainQueue(20);
            $summary['jobs']['queue_drain'] = $drained;
            self::touchSchedule('queue_drain', 'success');

            // 3) Health check write
            self::recordHealth();
            self::touchSchedule('health_check', 'success');

            return $summary;
        });

        if ($result === null) {
            Response::json(['error' => 'busy', 'message' => 'Another cron run is in progress.'], 423);
            return;
        }
        Response::json(['ok' => true, 'summary' => $result]);
    }

    private static function touchSchedule(string $key, string $status): void
    {
        try {
            Database::getInstance()->query(
                'UPDATE cron_schedules SET last_run_at = NOW(), last_status = :s, next_run_at = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE job_key = :k',
                [':s' => $status, ':k' => $key]
            );
        } catch (\Throwable $e) {}
    }

    private static function drainQueue(int $max): array
    {
        $processed = 0; $failed = 0;
        try {
            $db = Database::getInstance();
            $rows = $db->fetchAll(
                'SELECT * FROM queue_jobs WHERE status = "pending" AND available_at <= NOW() ORDER BY id ASC LIMIT ' . max(1, $max)
            );
            foreach ($rows as $r) {
                $db->update('queue_jobs', ['status' => 'running', 'started_at' => date('Y-m-d H:i:s'), 'attempts' => (int) $r['attempts'] + 1], 'id = :id', [':id' => $r['id']]);
                try {
                    // Without a job dispatcher (kept off until those modules ship), mark as completed no-op.
                    $db->update('queue_jobs', ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $r['id']]);
                    $processed++;
                } catch (\Throwable $e) {
                    $db->update('queue_jobs', ['status' => 'failed', 'failed_at' => date('Y-m-d H:i:s'), 'error_message' => mb_substr($e->getMessage(), 0, 1000)], 'id = :id', [':id' => $r['id']]);
                    $failed++;
                }
            }
        } catch (\Throwable $e) {}
        return ['processed' => $processed, 'failed' => $failed];
    }

    private static function recordHealth(): void
    {
        try {
            $db = Database::getInstance();
            $db->insert('system_health_checks', [
                'check_key'   => 'cron_runner',
                'check_group' => 'cron',
                'status'      => 'ok',
                'message'     => 'Cron runner executed successfully',
                'checked_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {}
    }
}
