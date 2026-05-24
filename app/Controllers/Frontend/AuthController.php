<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Services\AuditService;
use App\Services\FeatureToggleService;
use App\Services\RateLimitService;
use App\Services\SecurityService;

class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) { $this->redirect('/account'); return; }
        $this->view('frontend.pages.auth.login', ['title' => 'Sign in']);
    }

    public function login(Request $request): void
    {
        $this->validateCsrf($request);
        if (Auth::check()) { $this->redirect('/account'); return; }

        $identifier = trim((string) $request->post('identifier', ''));
        $password   = (string) $request->post('password', '');
        $ipHash     = (string) hash_ip($request->ip());
        $uaHash     = (string) hash_ua($request->userAgent());

        // Rate limit per identifier
        $maxAttempts = (int) (config('security.login_max') ?? 5);
        $window      = (int) (config('security.login_window') ?? 900);
        if ($identifier !== '' && RateLimitService::recentFailedLoginCount($identifier, 'frontend_user', $window) >= $maxAttempts) {
            SecurityService::logEvent('login_blocked', 'warning', $request, ['identifier' => $identifier]);
            $this->flashErrors(['identifier' => 'Too many failed attempts. Try again later.']);
            $this->redirect('/login');
            return;
        }

        $v = Validator::make($request->post())
            ->field('identifier', ['required', 'max:190'])
            ->field('password',   ['required', 'min:1']);
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->post());
            $this->redirect('/login'); return;
        }

        $db = Database::getInstance();
        $user = $db->fetch(
            'SELECT id, username, email, password_hash, status, email_verified_at, membership_tier
             FROM users WHERE (email = :i OR username = :i) AND deleted_at IS NULL LIMIT 1',
            [':i' => $identifier]
        );
        $genericError = ['identifier' => 'Invalid credentials.'];

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            RateLimitService::loginAttempt($identifier, 'frontend_user', false, 'invalid_credentials', $ipHash, $uaHash);
            $this->flashErrors($genericError, $request->post());
            $this->redirect('/login'); return;
        }
        if ($user['status'] === 'suspended' || $user['status'] === 'banned' || $user['status'] === 'deleted') {
            RateLimitService::loginAttempt($identifier, 'frontend_user', false, 'status_' . $user['status'], $ipHash, $uaHash);
            SecurityService::logEvent('login_blocked', 'warning', $request, ['user_id' => $user['id'], 'status' => $user['status']]);
            $this->flashErrors(['identifier' => 'Account is not available.']);
            $this->redirect('/login'); return;
        }

        RateLimitService::loginAttempt($identifier, 'frontend_user', true, null, $ipHash, $uaHash);
        Auth::loginUser((int) $user['id']);

        $intended = Session::instance()->pull('_user_intended', '/account');
        $this->redirect($intended);
    }

    public function showRegister(Request $request): void
    {
        if (!FeatureToggleService::enabled('registration')) {
            $this->view('frontend.pages.auth.disabled', ['title' => 'Registration disabled']); return;
        }
        if (Auth::check()) { $this->redirect('/account'); return; }
        $this->view('frontend.pages.auth.register', ['title' => 'Create an account']);
    }

    public function register(Request $request): void
    {
        $this->validateCsrf($request);
        if (!FeatureToggleService::enabled('registration')) {
            $this->redirect('/register'); return;
        }
        $username = trim((string) $request->post('username', ''));
        $email    = trim((string) $request->post('email', ''));
        $password = (string) $request->post('password', '');
        $confirm  = (string) $request->post('password_confirmation', '');

        $v = Validator::make($request->post())
            ->field('username', ['required', 'min:3', 'max:80', 'alpha_dash'])
            ->field('email',    ['required', 'email', 'max:190'])
            ->field('password', ['required', 'min:10', 'max:255', 'confirmed']);
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->post());
            $this->redirect('/register'); return;
        }
        $db = Database::getInstance();
        if ($db->fetch('SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1', [':u' => $username, ':e' => $email])) {
            $this->flashErrors(['username' => 'Username or email already in use.'], $request->post());
            $this->redirect('/register'); return;
        }

        // Rate limit registration per IP
        if (!\App\Services\RateLimitService::hit('register', (string) hash_ip($request->ip()), 3600, 5)) {
            $this->flashErrors(['username' => 'Too many registrations from this network.']);
            $this->redirect('/register'); return;
        }

        $userId = $db->insert('users', [
            'username' => $username,
            'email'    => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'display_name' => $username,
            'status'   => 'pending_email',
            'membership_tier' => 'free',
        ]);
        // Initialize preferences row
        $db->insert('user_preferences', ['user_id' => $userId]);

        // Generate email verify token (token plain shown via email later; here we just store hash)
        $token = bin2hex(random_bytes(32));
        $db->insert('auth_tokens', [
            'user_id'    => $userId,
            'token_type' => 'email_verify',
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + 86400),
        ]);
        // Queue verification email (best effort - mail driver may not be configured yet).
        try {
            $url = url('verify-email/' . $token);
            $template = $db->fetch("SELECT * FROM email_templates WHERE template_key='email_verify' AND status='active' AND language_code='default' LIMIT 1");
            if ($template) {
                $subject = strtr($template['subject'], ['{{site_name}}' => (string) config('app.name', 'StreamHub')]);
                $bodyH = strtr((string) $template['body_html'], [
                    '{{username}}' => e($username),
                    '{{site_name}}' => e((string) config('app.name', 'StreamHub')),
                    '{{verify_url}}' => $url,
                ]);
                $bodyT = strtr((string) $template['body_text'], [
                    '{{username}}' => $username,
                    '{{site_name}}' => (string) config('app.name', 'StreamHub'),
                    '{{verify_url}}' => $url,
                ]);
                $db->insert('mail_queue', [
                    'recipient_email' => $email,
                    'recipient_user_id' => $userId,
                    'subject' => $subject,
                    'body_html' => $bodyH,
                    'body_text' => $bodyT,
                    'scheduled_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) { /* best effort */ }

        Session::instance()->flash('success', 'Account created. Please verify your email to activate it.');
        $this->redirect('/login');
    }

    public function logout(Request $request): void
    {
        $this->validateCsrf($request);
        Auth::logoutUser();
        $this->redirect('/');
    }

    public function showForgotPassword(Request $request): void
    {
        $this->view('frontend.pages.auth.forgot', ['title' => 'Forgot password']);
    }

    public function sendPasswordReset(Request $request): void
    {
        $this->validateCsrf($request);
        $email = trim((string) $request->post('email', ''));
        // Always answer success to avoid account enumeration.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::instance()->flash('success', 'If that email exists, a reset link has been sent.');
            $this->redirect('/forgot-password'); return;
        }
        if (!\App\Services\RateLimitService::hit('forgot', (string) hash_ip($request->ip()), 3600, 5)) {
            Session::instance()->flash('success', 'If that email exists, a reset link has been sent.');
            $this->redirect('/forgot-password'); return;
        }
        $db = Database::getInstance();
        $user = $db->fetch('SELECT id, username FROM users WHERE email = :e AND deleted_at IS NULL LIMIT 1', [':e' => $email]);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $db->insert('auth_tokens', [
                'user_id'    => $user['id'],
                'token_type' => 'password_reset',
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ]);
            // Queue reset email
            try {
                $url = url('reset-password/' . $token);
                $tpl = $db->fetch("SELECT * FROM email_templates WHERE template_key='password_reset' AND status='active' AND language_code='default' LIMIT 1");
                if ($tpl) {
                    $bodyH = strtr((string) $tpl['body_html'], ['{{username}}' => e($user['username']), '{{reset_url}}' => $url]);
                    $bodyT = strtr((string) $tpl['body_text'], ['{{username}}' => $user['username'],   '{{reset_url}}' => $url]);
                    $db->insert('mail_queue', [
                        'recipient_email'  => $email,
                        'recipient_user_id' => $user['id'],
                        'subject'   => strtr($tpl['subject'], ['{{site_name}}' => (string) config('app.name', 'StreamHub')]),
                        'body_html' => $bodyH,
                        'body_text' => $bodyT,
                        'scheduled_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            } catch (\Throwable $e) {}
            AuditService::log('user.password_reset_requested', 'user', (int) $user['id']);
        }
        Session::instance()->flash('success', 'If that email exists, a reset link has been sent.');
        $this->redirect('/forgot-password');
    }

    public function showResetPassword(Request $request): void
    {
        $token = (string) $request->param('token');
        $hash  = hash('sha256', $token);
        $row = Database::getInstance()->fetch(
            'SELECT * FROM auth_tokens WHERE token_hash = :h AND token_type = "password_reset" AND used_at IS NULL AND expires_at >= NOW() LIMIT 1',
            [':h' => $hash]
        );
        if (!$row) {
            $this->view('frontend.pages.auth.reset_invalid', ['title' => 'Reset link invalid']); return;
        }
        $this->view('frontend.pages.auth.reset', ['title' => 'Reset password', 'token' => $token]);
    }

    public function resetPassword(Request $request): void
    {
        $this->validateCsrf($request);
        $token = (string) $request->post('token', '');
        $hash  = hash('sha256', $token);
        $password = (string) $request->post('password', '');
        $v = Validator::make($request->post())->field('password', ['required', 'min:10', 'confirmed']);
        if ($v->fails()) {
            $this->flashErrors($v->errors());
            $this->redirect('/reset-password/' . urlencode($token)); return;
        }
        $db = Database::getInstance();
        $row = $db->fetch(
            'SELECT id, user_id FROM auth_tokens WHERE token_hash = :h AND token_type = "password_reset" AND used_at IS NULL AND expires_at >= NOW() LIMIT 1',
            [':h' => $hash]
        );
        if (!$row) { $this->redirect('/forgot-password'); return; }
        $db->transaction(function () use ($db, $row, $password) {
            $db->update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', [':id' => $row['user_id']]);
            $db->update('auth_tokens', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $row['id']]);
            // Revoke active sessions for this user
            $db->update('user_sessions', ['is_active' => 0], 'user_id = :u', [':u' => $row['user_id']]);
        });
        AuditService::log('user.password_reset', 'user', (int) $row['user_id']);
        Session::instance()->flash('success', 'Password updated. Please sign in.');
        $this->redirect('/login');
    }

    public function verifyEmail(Request $request): void
    {
        $token = (string) $request->param('token');
        $hash  = hash('sha256', $token);
        $db = Database::getInstance();
        $row = $db->fetch(
            'SELECT id, user_id FROM auth_tokens WHERE token_hash = :h AND token_type = "email_verify" AND used_at IS NULL AND expires_at >= NOW() LIMIT 1',
            [':h' => $hash]
        );
        if (!$row) {
            $this->view('frontend.pages.auth.verify_invalid', ['title' => 'Verification link invalid']); return;
        }
        $db->transaction(function () use ($db, $row) {
            $db->update('users', ['email_verified_at' => date('Y-m-d H:i:s'), 'status' => 'active'], 'id = :id', [':id' => $row['user_id']]);
            $db->update('auth_tokens', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $row['id']]);
        });
        Session::instance()->flash('success', 'Email verified. You may now sign in.');
        $this->redirect('/login');
    }
}
