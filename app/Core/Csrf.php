<?php
declare(strict_types=1);

namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        $session = Session::instance();
        $token = $session->get('_csrf');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $session->set('_csrf', $token);
        }
        return (string) $token;
    }

    public static function check(?string $supplied): bool
    {
        if ($supplied === null || $supplied === '') return false;
        $token = Session::instance()->get('_csrf');
        if (!$token) return false;
        return hash_equals((string) $token, (string) $supplied);
    }

    public static function rotate(): void
    {
        Session::instance()->forget('_csrf');
        self::token();
    }
}
