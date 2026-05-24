<?php
/**
 * StreamHub Cron Runner — CLI & URL entrypoint.
 *
 * Usage (CLI):
 *   php /path/to/cron/run.php --token=YOUR_CRON_TOKEN
 *
 * Usage (URL via cPanel cron):
 *   curl -fsS "https://domain.com/cron/run?token=YOUR_CRON_TOKEN"
 *
 * This file bootstraps the app and invokes the CronController directly
 * when called from CLI, bypassing the web router. For URL-based calls,
 * the request goes through public/index.php → routes/api.php → CronController.
 */
declare(strict_types=1);

// Detect CLI mode
if (php_sapi_name() !== 'cli') {
    // If accessed directly via web (not through public/index.php), deny.
    http_response_code(403);
    echo 'Use /cron/run?token=... through the front controller.';
    exit(1);
}

// Parse CLI arguments
$token = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--token=')) {
        $token = substr($arg, 8);
    }
}

if ($token === '') {
    fwrite(STDERR, "Usage: php cron/run.php --token=YOUR_CRON_TOKEN\n");
    exit(1);
}

// Bootstrap
require __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Core\FileStorage;
use App\Core\Request;
use App\Services\MembershipService;
use App\Services\SecurityService;

// Validate token
$expected = (string) config('app.cron_token', '');
if ($expected === '' || !hash_equals($expected, $token)) {
    fwrite(STDERR, "ERROR: Invalid cron token.\n");
    // Log security event (DB may not be available if misconfigured)
    try {
        Database::getInstance();
        SecurityService::logEvent('api_auth_failed', 'warning', null, ['endpoint' => 'cron_cli']);
    } catch (\Throwable $e) {}
    exit(1);
}

// Acquire lock to prevent double-run
$result = FileStorage::lock('cron-runner', function () {
    $started = microtime(true);
    $summary = ['ran_at' => date('c'), 'mode' => 'cli', 'jobs' => []];

    try {
        $db = Database::getInstance();
    } catch (\Throwable $e) {
        fwrite(STDERR, "ERROR: Database connection failed: " . $e->getMessage() . "\n");
        return null;
    }

    // 1) Membership expiry sweep
    try {
        $expired = MembershipService::expireDueMemberships();
        $summary['jobs']['membership_expiry'] = ['expired' => $expired];
        touchSchedule($db, 'membership_expiry', 'success');
    } catch (\Throwable $e) {
        $summary['jobs']['membership_expiry'] = ['error' => $e->getMessage()];
        touchSchedule($db, 'membership_expiry', 'failed');
    }

    // 2) Drain pending queue jobs (batch)
    try {
        $drained = drainQueue($db, 50);
        $summary['jobs']['queue_drain'] = $drained;
        touchSchedule($db, 'queue_drain', 'success');
    } catch (\Throwable $e) {
        $summary['jobs']['queue_drain'] = ['error' => $e->getMessage()];
        touchSchedule($db, 'queue_drain', 'failed');
    }

    // 3) System health check
    try {
        recordHealth($db);
        touchSchedule($db, 'health_check', 'success');
        $summary['jobs']['health_check'] = ['status' => 'ok'];
    } catch (\Throwable $e) {
        $summary['jobs']['health_check'] = ['error' => $e->getMessage()];
        touchSchedule($db, 'health_check', 'failed');
    }

    $summary['duration_ms'] = round((microtime(true) - $started) * 1000, 2);
    return $summary;
});

if ($result === null) {
    fwrite(STDERR, "WARN: Another cron run is in progress (lock held). Skipping.\n");
    exit(0);
}

// Output summary
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit(0);

// ---------------------------------------------------------------------------
// Helper functions
// ---------------------------------------------------------------------------

function touchSchedule(Database $db, string $key, string $status): void
{
    try {
        $db->query(
            'UPDATE cron_schedules SET last_run_at = NOW(), last_status = :s, next_run_at = DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE job_key = :k',
            [':s' => $status, ':k' => $key]
        );
    } catch (\Throwable $e) {}
}

function drainQueue(Database $db, int $max): array
{
    $processed = 0;
    $failed = 0;
    $maxTime = (int) ini_get('max_execution_time');
    $deadline = $maxTime > 0 ? time() + min($maxTime - 5, 55) : time() + 55;

    $rows = $db->fetchAll(
        'SELECT * FROM queue_jobs WHERE status = "pending" AND available_at <= NOW() ORDER BY id ASC LIMIT ' . max(1, $max)
    );

    foreach ($rows as $r) {
        if (time() >= $deadline) break; // Respect execution time limits

        $db->update('queue_jobs', [
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
            'attempts' => (int) $r['attempts'] + 1,
        ], 'id = :id', [':id' => $r['id']]);

        try {
            // Dispatch based on job_type
            $jobResult = dispatchJob($db, $r);
            $db->update('queue_jobs', [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', [':id' => $r['id']]);
            $processed++;
        } catch (\Throwable $e) {
            $attempts = (int) $r['attempts'] + 1;
            $maxAttempts = (int) $r['max_attempts'];
            $newStatus = $attempts >= $maxAttempts ? 'failed' : 'pending';
            $nextAvail = $newStatus === 'pending'
                ? date('Y-m-d H:i:s', time() + min(300, 30 * $attempts)) // Exponential backoff
                : date('Y-m-d H:i:s');

            $db->update('queue_jobs', [
                'status' => $newStatus,
                'failed_at' => $newStatus === 'failed' ? date('Y-m-d H:i:s') : null,
                'available_at' => $nextAvail,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ], 'id = :id', [':id' => $r['id']]);
            $failed++;
        }
    }

    return ['processed' => $processed, 'failed' => $failed, 'total_pending' => count($rows)];
}

function dispatchJob(Database $db, array $job): bool
{
    $type = (string) $job['job_type'];
    $payload = json_decode((string) $job['payload_json'], true) ?: [];

    // Map job_type to handler. Jobs whose modules aren't shipped yet
    // complete as no-op (they won't be enqueued until the module is active).
    switch ($type) {
        case 'membership_expiry':
            MembershipService::expireDueMemberships();
            return true;

        case 'mail_send':
            // Mail sending logic placeholder — will be implemented with Mail Manager module
            // For now, mark as completed so it doesn't block the queue
            return true;

        case 'doodstream_sync':
            // Will be implemented with full Doodstream sync module
            return true;

        case 'sitemap_generate':
            // Will be implemented with SEO Manager sitemap generation
            return true;

        case 'cache_cleanup':
            // Clean old cache files
            $cacheDir = STREAMHUB_BASE . '/storage/cache';
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                $threshold = time() - 86400; // 24h
                foreach ($files ?: [] as $f) {
                    if (is_file($f) && filemtime($f) < $threshold && basename($f) !== '.gitkeep') {
                        @unlink($f);
                    }
                }
            }
            return true;

        default:
            // Unknown job type — log warning but don't fail permanently
            $db->insert('error_logs', [
                'level' => 'warning',
                'channel' => 'queue',
                'message' => 'Unknown job type: ' . $type,
                'context_json' => json_encode(['job_id' => $job['id'], 'payload' => $payload]),
            ]);
            return true;
    }
}

function recordHealth(Database $db): void
{
    $db->insert('system_health_checks', [
        'check_key' => 'cron_runner',
        'check_group' => 'cron',
        'status' => 'ok',
        'message' => 'CLI cron runner executed successfully',
        'details_json' => json_encode([
            'php_version' => PHP_VERSION,
            'memory_peak' => memory_get_peak_usage(true),
            'sapi' => php_sapi_name(),
        ]),
        'checked_at' => date('Y-m-d H:i:s'),
    ]);
}
