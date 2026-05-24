<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::getInstance();

        $totalContent     = (int) $db->fetchValue('SELECT COUNT(*) FROM content_items WHERE deleted_at IS NULL');
        $publishedContent = (int) $db->fetchValue('SELECT COUNT(*) FROM content_items WHERE lifecycle_status = "published" AND deleted_at IS NULL');
        $brokenContent    = (int) $db->fetchValue('SELECT COUNT(*) FROM content_items WHERE lifecycle_status IN ("broken","disabled") AND deleted_at IS NULL');
        $totalUsers       = (int) $db->fetchValue('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL');
        $premiumUsers     = (int) $db->fetchValue('SELECT COUNT(*) FROM users WHERE membership_tier = "premium" AND deleted_at IS NULL');
        $vipUsers         = (int) $db->fetchValue('SELECT COUNT(*) FROM users WHERE membership_tier = "vip" AND deleted_at IS NULL');
        $pendingRequests  = (int) $db->fetchValue('SELECT COUNT(*) FROM membership_requests WHERE status IN ("new","reviewing")');

        $viewsSeries = $db->fetchAll(
            'SELECT DATE(created_at) AS d, COUNT(*) AS c FROM content_views
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
             GROUP BY DATE(created_at) ORDER BY d ASC'
        );
        $viewsByDay = [];
        for ($i = 13; $i >= 0; $i--) $viewsByDay[date('Y-m-d', strtotime("-$i day"))] = 0;
        foreach ($viewsSeries as $r) $viewsByDay[$r['d']] = (int) $r['c'];

        $tierDist = ['free' => 0, 'premium' => 0, 'vip' => 0];
        foreach ($db->fetchAll('SELECT membership_tier, COUNT(*) AS c FROM users WHERE deleted_at IS NULL GROUP BY membership_tier') as $r) {
            $tierDist[$r['membership_tier']] = (int) $r['c'];
        }

        $topContent = $db->fetchAll(
            'SELECT id, slug, title, view_count, like_count FROM content_items
             WHERE lifecycle_status = "published" AND deleted_at IS NULL
             ORDER BY view_count DESC LIMIT 5'
        );

        $recentAudit    = \App\Services\AuditService::recent(8);
        $recentSecurity = \App\Services\SecurityService::recentEvents(5);

        $lastSync = $db->fetch('SELECT * FROM doodstream_sync_logs ORDER BY created_at DESC LIMIT 1');
        $doodCred = \App\Services\DoodstreamService::record();

        $queuePending = (int) $db->fetchValue('SELECT COUNT(*) FROM queue_jobs WHERE status = "pending"');
        $queueFailed  = (int) $db->fetchValue('SELECT COUNT(*) FROM queue_jobs WHERE status = "failed"');
        $crons        = $db->fetchAll('SELECT job_key, last_run_at, last_status, next_run_at FROM cron_schedules ORDER BY job_key ASC');
        $healthLatest = $db->fetchAll(
            'SELECT * FROM system_health_checks WHERE id IN (
                SELECT MAX(id) FROM system_health_checks GROUP BY check_key
             ) ORDER BY checked_at DESC LIMIT 8'
        );

        $this->view('admin.pages.dashboard', [
            'title' => 'Dashboard',
            'totalContent' => $totalContent, 'publishedContent' => $publishedContent, 'brokenContent' => $brokenContent,
            'totalUsers' => $totalUsers, 'premiumUsers' => $premiumUsers, 'vipUsers' => $vipUsers,
            'pendingRequests' => $pendingRequests,
            'viewsByDay' => $viewsByDay, 'tierDist' => $tierDist,
            'topContent' => $topContent,
            'recentAudit' => $recentAudit, 'recentSecurity' => $recentSecurity,
            'lastSync' => $lastSync, 'doodCred' => $doodCred,
            'queuePending' => $queuePending, 'queueFailed' => $queueFailed,
            'crons' => $crons, 'healthLatest' => $healthLatest,
        ]);
    }
}
