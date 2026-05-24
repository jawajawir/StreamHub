<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;

class SecurityService
{
    public static function logEvent(string $eventType, string $severity, ?Request $request = null, array $context = []): void
    {
        try {
            $request ??= Request::fromGlobals();
            $admin = Auth::admin();
            $user  = Auth::user();
            Database::getInstance()->insert('security_events', [
                'event_type'      => $eventType,
                'severity'        => $severity,
                'user_id'         => $user['id']  ?? null,
                'admin_id'        => $admin['id'] ?? null,
                'ip_hash'         => hash_ip($request->ip()),
                'user_agent_hash' => hash_ua($request->userAgent()),
                'request_path'    => substr($request->path(), 0, 500),
                'context_json'    => $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : null,
            ]);
        } catch (\Throwable $e) { /* swallow */ }
    }

    /**
     * Validate an iframe URL against the configured domain allowlist.
     * Returns sanitized URL or null if rejected.
     */
    public static function validateIframeUrl(?string $url): ?string
    {
        if (!$url) return null;
        $url = trim($url);
        if ($url === '') return null;

        // Reject protocols
        $lower = strtolower($url);
        foreach (['javascript:', 'data:', 'vbscript:', 'file:'] as $bad) {
            if (str_starts_with($lower, $bad)) return null;
        }
        if (!preg_match('#^https?://#i', $url)) return null;

        // FILTER_VALIDATE_URL is very permissive but better than nothing.
        if (!filter_var($url, FILTER_VALIDATE_URL)) return null;

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if ($host === '') return null;

        $allow = self::iframeAllowlist();
        foreach ($allow as $allowed) {
            $allowed = strtolower(trim($allowed));
            if ($allowed === '') continue;
            if ($host === $allowed) return $url;
            if (str_ends_with($host, '.' . $allowed)) return $url;
        }
        return null;
    }

    /** @return string[] */
    public static function iframeAllowlist(): array
    {
        $base = (array) (config('security.iframe_allowlist_default') ?? []);
        $extra = (string) (SettingService::get('iframe', 'allowlist_extra', '') ?? '');
        if ($extra !== '') {
            $extras = array_filter(array_map('trim', preg_split('/[,\s]+/', $extra) ?: []));
            $base = array_unique(array_merge($base, $extras));
        }
        return $base;
    }

    public static function recentEvents(int $limit = 20): array
    {
        try {
            return Database::getInstance()->fetchAll(
                'SELECT * FROM security_events ORDER BY created_at DESC LIMIT ' . max(1, min(200, $limit))
            );
        } catch (\Throwable $e) {
            return [];
        }
    }
}
