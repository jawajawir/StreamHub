<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Authenticated encryption using AES-256-GCM with the project's ENCRYPTION_KEY.
 * Falls back to AES-256-CBC + HMAC-SHA256 if GCM is unavailable.
 */
class Encryption
{
    private static function key(): string
    {
        $key = (string) config('security.encryption_key', '');
        if ($key === '') {
            throw new \RuntimeException('ENCRYPTION_KEY is not set.');
        }
        // Allow base64-encoded keys; otherwise hash to 32 bytes
        $decoded = base64_decode($key, true);
        if ($decoded !== false && strlen($decoded) === 32) {
            return $decoded;
        }
        return hash('sha256', $key, true);
    }

    public static function encrypt(string $plaintext): string
    {
        $key = self::key();
        if (in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            $iv = random_bytes(12);
            $tag = '';
            $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            if ($cipher === false) {
                throw new \RuntimeException('Encryption failed.');
            }
            return 'gcm.' . base64_encode($iv . $tag . $cipher);
        }
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new \RuntimeException('Encryption failed.');
        }
        $mac = hash_hmac('sha256', $iv . $cipher, $key, true);
        return 'cbc.' . base64_encode($iv . $mac . $cipher);
    }

    public static function decrypt(string $payload): string
    {
        $key = self::key();
        if (str_starts_with($payload, 'gcm.')) {
            $raw = base64_decode(substr($payload, 4), true);
            if ($raw === false || strlen($raw) < 28) throw new \RuntimeException('Invalid payload.');
            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $cipher = substr($raw, 28);
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
            if ($plain === false) throw new \RuntimeException('Decryption failed.');
            return $plain;
        }
        if (str_starts_with($payload, 'cbc.')) {
            $raw = base64_decode(substr($payload, 4), true);
            if ($raw === false || strlen($raw) < 48) throw new \RuntimeException('Invalid payload.');
            $iv = substr($raw, 0, 16);
            $mac = substr($raw, 16, 32);
            $cipher = substr($raw, 48);
            $expected = hash_hmac('sha256', $iv . $cipher, $key, true);
            if (!hash_equals($expected, $mac)) throw new \RuntimeException('MAC mismatch.');
            $plain = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            if ($plain === false) throw new \RuntimeException('Decryption failed.');
            return $plain;
        }
        throw new \RuntimeException('Unknown encryption payload.');
    }

    public static function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    public static function maskSecret(string $secret): string
    {
        $len = strlen($secret);
        if ($len <= 6) return str_repeat('*', max($len, 1));
        return substr($secret, 0, 4) . str_repeat('*', max(4, $len - 8)) . substr($secret, -4);
    }
}
