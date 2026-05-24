<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Services\AuditService;
use App\Services\RateLimitService;
use App\Services\SecurityService;

class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        $this->view('admin.pages.auth.login', ['title' => 'Admin sign in']);
    }

    public function login(Request $request): void
    {
        $this->validateCsrf($request);
        $identifier = trim((string) $request->post('identifier', ''));
        $password   = (string) $request->post('password', '');
        $ipHash     = (string) hash_ip($request->ip());
        $uaHash     = (string) hash_ua($request->userAgent());

        $maxAttempts = (int) (config('security.admin_login_max') ?? 4);
        $window      = (int) (config('security.login_window') ?? 900);
        if ($identifier !== '' && RateLimitService::recentFailedLoginCount($identifier, 'superadmin', $window) >= $maxAttempts) {
            SecurityService::logEvent('login_blocked', 'critical', $request, ['identifier' => $identifier, 'scope' => 'admin']);
            $this->flashErrors(['identifier' => 'Too many failed attempts. Try again later.']);
            $this->redirect(admin_url('login'));
            return;
        }

        $v = Validator::make($request->post())
            ->field('identifier', ['required', 'max:190'])
            ->field('password',   ['required']);
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->post());
            $this->redirect(admin_url('login')); return;
        }

        $admin = Database::getInstance()->fetch(
            'SELECT * FROM admin_users WHERE (username = :i OR email = :i) LIMIT 1',
            [':i' => $identifier]
        );
        $generic = ['identifier' => 'Invalid credentials.'];
        if (!$admin || !password_verify($password, (string) $admin['password_hash']) || $admin['status'] !== 'active') {
            RateLimitService::loginAttempt($identifier, 'superadmin', false, $admin ? 'status_or_password' : 'no_user', $ipHash, $uaHash);
            $this->flashErrors($generic, $request->post());
            $this->redirect(admin_url('login')); return;
        }

        RateLimitService::loginAttempt($identifier, 'superadmin', true, null, $ipHash, $uaHash);
        Auth::loginAdmin((int) $admin['id']);
        AuditService::log('admin.login', 'admin_user', (int) $admin['id']);

        $intended = Session::instance()->pull('_admin_intended', admin_url(''));
        $this->redirect($intended);
    }

    public function logout(Request $request): void
    {
        $this->validateCsrf($request);
        $admin = Auth::admin();
        AuditService::log('admin.logout', 'admin_user', $admin['id'] ?? null);
        Auth::logoutAdmin();
        $this->redirect(admin_url('login'));
    }
}
