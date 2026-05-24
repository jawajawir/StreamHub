<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

class SystemController extends Controller
{
    public function auditLogs(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 50;
        $total = (int) Database::getInstance()->fetchValue('SELECT COUNT(*) FROM audit_logs');
        $rows  = Database::getInstance()->fetchAll(
            'SELECT a.*, au.username AS admin_username FROM audit_logs a
             LEFT JOIN admin_users au ON au.id = a.admin_id
             ORDER BY a.created_at DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage)
        );
        $this->view('admin.pages.system.audit_logs', [
            'title' => 'Audit Logs',
            'rows' => $rows, 'page' => $page, 'pages' => (int) ceil($total / $perPage), 'total' => $total,
        ]);
    }

    public function securityEvents(Request $request): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 50;
        $total = (int) Database::getInstance()->fetchValue('SELECT COUNT(*) FROM security_events');
        $rows  = Database::getInstance()->fetchAll(
            'SELECT * FROM security_events ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage)
        );
        $this->view('admin.pages.system.security_events', [
            'title' => 'Security Events',
            'rows' => $rows, 'page' => $page, 'pages' => (int) ceil($total / $perPage), 'total' => $total,
        ]);
    }
}
