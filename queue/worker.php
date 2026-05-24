<?php
/**
 * StreamHub Queue Worker — CLI entrypoint for dedicated queue processing.
 *
 * Usage:
 *   php queue/worker.php --token=YOUR_CRON_TOKEN [--max=100] [--timeout=60]
 *
 * On shared hosting without a daemon supervisor, this is typically
 * invoked by the cron runner. On VPS/dedicated servers, it can be
 * run via supervisor/systemd for continuous processing.
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$token = '';
$maxJobs = 100;
$timeout = 55; // seconds

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--token=')) $token = substr($arg, 8);
    if (str_starts_with($arg, '--max=')) $maxJobs = max(1, (int) substr($arg, 6));
    if (str_starts_with($arg, '--timeout=')) $timeout = max(5, (int) substr($arg, 10));
}

if ($token === '') {
    fwrite(STDERR, "Usage: php queue/worker.php --token=YOUR_CRON_TOKEN [--max=100] [--timeout=60]\n");
    exit(1);
}

require __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Core\FileStorage;
use App\Services\SecurityService;

$expected = (string) config('app.cron_token', '');
if ($expected === '' || !hash_equals($expected, $token)) {
    fwrite(STDERR, "ERROR: Invalid token.\n");
    try { Database::getInstance(); SecurityService::logEvent('api_auth_failed', 'warning', null, ['endpoint' => 'queue_worker']); } catch (\Throwable $e) {}
    exit(1);
}

$result = FileStorage::lock('queue-worker', function () use ($maxJobs, $timeout) {
    $started = time();
    $processed = 0;
    $failed = 0;

    try {
        $db = Database::getInstance();
    } catch (\Throwable $e) {
        fwrite(STDERR, "DB error: " . $e->getMessage() . "\n");
        return null;
    }

    $deadline = $started + $timeout;

    while ($processed + $failed < $maxJobs && time() < $deadline) {
        $job = $db->fetch(
            'SELECT * FROM queue_jobs WHERE status = "pending" AND available_at <= NOW() ORDER BY id ASC LIMIT 1'
        );
        if (!$job) break;

        $db->update('queue_jobs', [
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
            'attempts' => (int) $job['attempts'] + 1,
        ], 'id = :id', [':id' => $job['id']]);

        try {
            // Same dispatch logic as cron/run.php
            // For now, mark completed (modules will register real handlers)
            $db->update('queue_jobs', [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', [':id' => $job['id']]);
            $processed++;
        } catch (\Throwable $e) {
            $attempts = (int) $job['attempts'] + 1;
            $maxAttempts = (int) $job['max_attempts'];
            $newStatus = $attempts >= $maxAttempts ? 'failed' : 'pending';
            $db->update('queue_jobs', [
                'status' => $newStatus,
                'failed_at' => $newStatus === 'failed' ? date('Y-m-d H:i:s') : null,
                'available_at' => date('Y-m-d H:i:s', time() + min(300, 30 * $attempts)),
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ], 'id = :id', [':id' => $job['id']]);
            $failed++;
        }
    }

    return [
        'processed' => $processed,
        'failed' => $failed,
        'duration_s' => time() - $started,
    ];
});

if ($result === null) {
    fwrite(STDERR, "WARN: Another worker is running (lock held).\n");
    exit(0);
}

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
exit(0);
