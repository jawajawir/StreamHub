<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\MembershipService;

class MembershipRequestController extends Controller
{
    public function index(Request $request): void
    {
        $status = (string) $request->query('status', '');
        $db = Database::getInstance();
        $where = [];
        $bind  = [];
        if (in_array($status, ['new','reviewing','approved','rejected','cancelled'], true)) {
            $where[] = 'mr.status = :s'; $bind[':s'] = $status;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $rows = $db->fetchAll(
            'SELECT mr.*, mp.name AS plan_name, mp.code AS plan_code, u.username AS user_username
             FROM membership_requests mr
             JOIN membership_plans mp ON mp.id = mr.requested_plan_id
             LEFT JOIN users u ON u.id = mr.user_id
             ' . $whereSql . ' ORDER BY mr.created_at DESC LIMIT 100', $bind
        );
        $this->view('admin.pages.membership_requests.index', [
            'title' => 'Membership requests', 'rows' => $rows, 'status' => $status,
        ]);
    }

    public function show(Request $request): void
    {
        $id = (int) $request->param('id');
        $db = Database::getInstance();
        $row = $db->fetch(
            'SELECT mr.*, mp.name AS plan_name, mp.code AS plan_code, u.username AS user_username, u.email AS user_email
             FROM membership_requests mr
             JOIN membership_plans mp ON mp.id = mr.requested_plan_id
             LEFT JOIN users u ON u.id = mr.user_id
             WHERE mr.id = :id LIMIT 1', [':id' => $id]
        );
        if (!$row) { Response::notFound(); return; }
        $this->view('admin.pages.membership_requests.show', ['title' => 'Membership request', 'row' => $row]);
    }

    public function approve(Request $request): void
    {
        $this->validateCsrf($request);
        $id = (int) $request->param('id');
        $expires = trim((string) $request->post('expires_at', ''));
        $note = trim((string) $request->post('admin_note', ''));
        $db = Database::getInstance();
        $row = $db->fetch('SELECT * FROM membership_requests WHERE id = :id', [':id' => $id]);
        if (!$row) { Response::notFound(); return; }
        if (empty($row['user_id'])) {
            Session::instance()->flash('error', 'This request has no associated user account.');
            $this->redirect(admin_url('membership-requests/' . $id)); return;
        }
        $plan = $db->fetch('SELECT code FROM membership_plans WHERE id = :id', [':id' => $row['requested_plan_id']]);
        if (!$plan) { Session::instance()->flash('error', 'Plan missing.'); $this->redirect(admin_url('membership-requests/' . $id)); return; }
        $expiresAt = $expires !== '' ? date('Y-m-d H:i:s', strtotime($expires)) : null;
        $admin = Auth::admin();
        MembershipService::activate((int) $row['user_id'], (string) $plan['code'], (int) $admin['id'], $expiresAt, $note ?: null);
        $db->update('membership_requests', [
            'status' => 'approved', 'admin_note' => $note ?: null,
            'resolved_by_admin_id' => $admin['id'], 'resolved_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $id]);
        AuditService::log('membership.approve', 'membership_request', $id, null, ['plan' => $plan['code'], 'expires_at' => $expiresAt]);
        Session::instance()->flash('success', 'Membership request approved.');
        $this->redirect(admin_url('membership-requests/' . $id));
    }

    public function reject(Request $request): void
    {
        $this->validateCsrf($request);
        $id = (int) $request->param('id');
        $note = trim((string) $request->post('admin_note', ''));
        $db = Database::getInstance();
        $row = $db->fetch('SELECT * FROM membership_requests WHERE id = :id', [':id' => $id]);
        if (!$row) { Response::notFound(); return; }
        $admin = Auth::admin();
        $db->update('membership_requests', [
            'status' => 'rejected', 'admin_note' => $note ?: null,
            'resolved_by_admin_id' => $admin['id'], 'resolved_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $id]);
        AuditService::log('membership.reject', 'membership_request', $id);
        Session::instance()->flash('success', 'Request rejected.');
        $this->redirect(admin_url('membership-requests/' . $id));
    }
}
