<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Encryption;

/**
 * Doodstream API helper.
 * - API key is read from api_credentials (encrypted, never exposed to frontend).
 * - All HTTP requests go server-side via cURL.
 */
class DoodstreamService
{
    public static function setApiKey(string $apiKey, ?int $adminId): void
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new \InvalidArgumentException('API key cannot be empty.');
        }
        $db = Database::getInstance();
        $existing = $db->fetch("SELECT id FROM api_credentials WHERE provider='doodstream' AND credential_name='primary' LIMIT 1");
        $payload = [
            'provider'        => 'doodstream',
            'credential_name' => 'primary',
            'encrypted_value' => Encryption::encrypt($apiKey),
            'masked_value'    => Encryption::maskSecret($apiKey),
            'status'          => 'active',
            'updated_by_admin_id' => $adminId,
        ];
        if ($existing) {
            $db->update('api_credentials', $payload, 'id = :id', [':id' => $existing['id']]);
        } else {
            $db->insert('api_credentials', $payload);
        }
    }

    public static function getApiKey(): ?string
    {
        $row = self::record();
        if (!$row) return null;
        try {
            return Encryption::decrypt($row['encrypted_value']);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function maskedApiKey(): ?string
    {
        $row = self::record();
        return $row['masked_value'] ?? null;
    }

    public static function record(): ?array
    {
        try {
            return Database::getInstance()->fetch(
                "SELECT * FROM api_credentials WHERE provider='doodstream' AND credential_name='primary' LIMIT 1"
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Test API connectivity using the /account/info endpoint. */
    public static function testConnection(): array
    {
        $key = self::getApiKey();
        if (!$key) return ['ok' => false, 'message' => 'API key is not configured.'];
        $base = rtrim((string) config('doodstream.api_base', 'https://doodapi.com/api'), '/');
        $url = $base . '/account/info?key=' . urlencode($key);
        $resp = self::httpGet($url);
        if (!$resp['ok']) {
            self::recordTestResult('failed', $resp['error'] ?? 'request_failed');
            return ['ok' => false, 'message' => $resp['error'] ?? 'Request failed'];
        }
        $body = json_decode($resp['body'], true);
        if (!is_array($body) || ((int) ($body['status'] ?? 0)) !== 200) {
            $msg = is_array($body) ? (string) ($body['msg'] ?? 'Unknown response') : 'Invalid response';
            self::recordTestResult('failed', $msg);
            return ['ok' => false, 'message' => $msg];
        }
        self::recordTestResult('success', 'OK');
        return ['ok' => true, 'message' => 'Connection OK', 'data' => $body['result'] ?? []];
    }

    private static function recordTestResult(string $status, string $message): void
    {
        $row = self::record();
        if (!$row) return;
        Database::getInstance()->update('api_credentials', [
            'last_test_status'  => $status,
            'last_test_message' => mb_substr($message, 0, 1000),
            'last_tested_at'    => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $row['id']]);
    }

    /** @return array{ok:bool, body?:string, error?:string, status?:int} */
    public static function httpGet(string $url): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'cURL is not available on this server.'];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => (int) (config('doodstream.request_timeout') ?? 20),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'StreamHubBot/1.0',
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['ok' => false, 'error' => $err ?: 'curl_failed'];
        }
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => "HTTP $code", 'status' => $code, 'body' => $body];
        }
        return ['ok' => true, 'body' => $body, 'status' => $code];
    }
}
