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

class UserManagerController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::getInstance();
        $q       = trim((string) $request->query('q', ''));
        $tier    = (string) $request->query('tier', '');
        $status  = (string) $request->query('status', '');
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        $where = ['deleted_at IS NULL'];
        $bind  = [];
        if ($q !== '')                       { $where[] = '(username LIKE :q OR email LIKE :q OR display_name LIKE :q)'; $bind[':q'] = '%' . $q . '%'; }
        if (in_array($tier, ['free','premium','vip'], true))                  { $where[] = 'membership_tier = :tier'; $bind[':tier'] = $tier; }
        if (in_array($status, ['active','pending_email','suspended','banned'], true)) { $where[] = 'status = :status'; $bind[':status'] = $status; }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $total = (int) $db->fetchValue('SELECT COUNT(*) FROM users ' . $whereSql, $bind);
        $rows  = $db->fetchAll(
            'SELECT id, username, email, display_name, status, membership_tier, created_at, last_login_at FROM users ' .
            $whereSql . ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $bind
        );
        $this->view('admin.pages.users.index', [
            'title' => 'User Manager',
            'rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage,
            'pages' => (int) ceil($total / $perPage),
            'q' => $q, 'tier' => $tier, 'status' => $status,
        ]);
    }

    public function show(Request $request): void
    {
        $id = (int) $request->param('id');
        $db = Database::getInstance();
        $user = $db->fetch('SELECT * FROM users WHERE id = :id LIMIT 1', [':id' => $id]);
        if (!$user) { Response::notFound(); return; }
        $memberships = $db->fetchAll(
            'SELECT um.*, mp.code AS plan_code, mp.name AS plan_name FROM user_memberships um
             JOIN membership_plans mp ON mp.id = um.plan_id
             WHERE um.user_id = :u ORDER BY um.created_at DESC LIMIT 20', [':u' => $id]);
        $sessions = $db->fetchAll('SELECT * FROM user_sessions WHERE user_id = :u ORDER BY last_seen_at DESC LIMIT 10', [':u' => $id]);
        $audit    = $db->fetchAll('SELECT * FROM audit_logs WHERE entity_type = "user" AND entity_id = :id ORDER BY created_at DESC LIMIT 20', [':id' => $id]);
        $plans    = MembershipService::plans();
        $this->view('admin.pages.users.show', [
            'title' => 'User · ' . $user['username'],
            'user' => $user, 'memberships' => $memberships, 'sessions' => $sessions, 'audit' => $audit, 'plans' => $plans,
        ]);
    }

    public function updateMembership(Request $request): void
    {
        $this->validateCsrf($request);
        $id = (int) $request->param('id');
        $code = (string) $request->post('plan', '');
        $expires = trim((string) $request->post('expires_at', ''));
        $note    = trim((string) $request->post('note', ''));
        $db = Database::getInstance();
        $user = $db->fetch('SELECT * FROM users WHERE id = :id', [':id' => $id]);
        if (!$user) { Response::notFound(); return; }
        $expiresAt = $expires !== '' ? date('Y-m-d H:i:s', strtotime($expires)) : null;
        $admin = Auth::admin();
        if (!MembershipService::activate($id, $code, (int) $admin['id'], $expiresAt, $note ?: null)) {
            Session::instance()->flash('error', 'Unable to update membership.');
            $this->redirect(admin_url('users/' . $id)); return;
        }
        // Mark related membership_request approved if any
        $req = $db->fetch('SELECT * FROM membership_requests WHERE user_id = :u AND status IN ("new","reviewing") ORDER BY id DESC LIMIT 1', [':u' => $id]);
        if ($req) {
            $db->update('membership_requests', [
                'status' => 'approved', 'admin_note' => $note ?: null,
                'resolved_by_admin_id' => $admin['id'], 'resolved_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', [':id' => $req['id']]);
        }
        AuditService::log('user.membership_update', 'user', $id, ['from' => $user['membership_tier']], ['to' => $code, 'expires_at' => $expiresAt]);
        Session::instance()->flash('success', 'Membership updated.');
        $this->redirect(admin_url('users/' . $id));
    }

    public function updateStatus(Request $request): void
    {
        $this->validateCsrf($request);
        $id = (int) $request->param('id');
        $status = (string) $request->post('status', '');
        if (!in_array($status, ['active', 'suspended', 'banned'], true)) {
            Session::instance()->flash('error', 'Invalid status.');
            $this->redirect(admin_url('users/' . $id)); return;
        }
        $db = Database::getInstance();
        $user = $db->fetch('SELECT id, status FROM users WHERE id = :id', [':id' => $id]);
        if (!$user) { Response::notFound(); return; }
        $db->update('users', ['status' => $status], 'id = :id', [':id' => $id]);
        if ($status !== 'active') {
            $db->update('user_sessions', ['is_active' => 0], 'user_id = :u', [':u' => $id]);
        }
        AuditService::log('user.status_update', 'user', $id, ['from' => $user['status']], ['to' => $status]);
        Session::instance()->flash('success', 'User status updated.');
        $this->redirect(admin_url('users/' . $id));
    }

    public function ban(Request $request): void
    {
        $this->validateCsrf($request);
        $id = (int) $request->param('id');
        $reason = trim((string) $request->post('reason', ''));
        $db = Database::getInstance();
        $user = $db->fetch('SELECT * FROM users WHERE id = :id', [':id' => $id]);
        if (!$user) { Response::notFound(); return; }
        $db->transaction(function () use ($db, $id, $reason, $user) {
            $db->update('users', ['status' => 'banned'], 'id = :id', [':id' => $id]);
            $db->update('user_sessions', ['is_active' => 0], 'user_id = :u', [':u' => $id]);
            $db->insert('bans', [
                'ban_type' => 'user', 'ban_value' => (string) $id, 'reason' => $reason,
                'created_by_admin_id' => Auth::admin()['id'],
            ]);
        });
        AuditService::log('user.ban', 'user', $id, ['status' => $user['status']], ['banned' => true, 'reason' => $reason]);
        Session::instance()->flash('success', 'User banned.');
        $this->redirect(admin_url('users/' . $id));
    }
}
