<?php
declare(strict_types=1);

namespace App\Core;

class Session
{
    private static ?Session $instance = null;
    private bool $started = false;

    public static function instance(): Session
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->start();
        }
        return self::$instance;
    }

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }
        $name     = (string) config('security.session_name', 'streamhub_session');
        $lifetime = (int) config('security.session_lifetime', 7200);
        $secure   = (bool) config('security.session_secure', false);
        $samesite = (string) config('security.session_samesite', 'Lax');

        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => $samesite,
        ]);
        session_start();
        $this->started = true;
    }

    public function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void { $_SESSION[$key] = $value; }
    public function has(string $key): bool { return isset($_SESSION[$key]); }
    public function forget(string $key): void { unset($_SESSION[$key]); }
    public function pull(string $key, $default = null) {
        $v = $this->get($key, $default);
        $this->forget($key);
        return $v;
    }

    public function flash(string $type, string $message): void
    {
        $bag = $this->get('_flash', []);
        $bag[$type] = $message;
        $this->set('_flash', $bag);
    }

    public function flashErrors(array $errors): void { $this->set('_errors', $errors); }
    public function flashOldInput(array $input): void {
        unset($input['password'], $input['password_confirmation'], $input['_csrf']);
        $this->set('_old_input', $input);
    }

    public function regenerate(bool $deleteOld = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOld);
        }
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
        }
    }

    public function clearOneShot(): void
    {
        $this->forget('_old_input');
        $this->forget('_errors');
    }
}
