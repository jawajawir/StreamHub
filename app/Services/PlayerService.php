<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Encryption;

/**
 * Resolves the correct player payload for a content item.
 * Doodstream → iframe (validated against allowlist).
 * Direct MySQL → MP4 / HLS via Video.js (+ HLS.js when needed) using a signed URL.
 */
class PlayerService
{
    /**
     * @param array $content content_items row
     * @param array|null $source content_sources row (or null if missing)
     * @return array{type:string, error?:string, embed_url?:string, mp4_url?:string, hls_url?:string, poster?:string, signed?:string}
     */
    public static function resolve(array $content, ?array $source): array
    {
        if (!$source) {
            return ['type' => 'error', 'error' => 'no_source'];
        }
        if ($source['source_type'] === 'doodstream') {
            $url = SecurityService::validateIframeUrl(
                $source['iframe_url'] ?? $source['doodstream_embed_url'] ?? null
            );
            if (!$url && !empty($source['doodstream_file_code'])) {
                // Build embed from file_code using configured base
                $base = (string) (config('doodstream.embed_base') ?? 'https://dood.li/e/');
                $url  = SecurityService::validateIframeUrl(rtrim($base, '/') . '/' . $source['doodstream_file_code']);
            }
            if (!$url) return ['type' => 'error', 'error' => 'invalid_embed_domain'];
            return ['type' => 'iframe', 'embed_url' => $url];
        }
        if ($source['source_type'] === 'direct_mysql') {
            $signed = self::signPlaybackToken((int) $content['id']);
            $payload = ['type' => 'direct', 'signed' => $signed];
            // public_url comes from content_sources or media_assets via repository
            return $payload;
        }
        return ['type' => 'error', 'error' => 'unknown_source'];
    }

    public static function signPlaybackToken(int $contentId, ?int $userId = null, ?int $ttl = null): string
    {
        $ttl = $ttl ?: (int) (config('security.signed_url_ttl') ?? 3600);
        $payload = json_encode([
            'cid' => $contentId,
            'uid' => $userId,
            'exp' => time() + $ttl,
            'nonce' => bin2hex(random_bytes(8)),
        ]);
        return Encryption::encrypt($payload);
    }

    /** @return array{cid:int, uid:?int, exp:int, nonce:string}|null */
    public static function verifyPlaybackToken(string $token): ?array
    {
        try {
            $raw = Encryption::decrypt($token);
            $data = json_decode($raw, true);
            if (!is_array($data) || !isset($data['cid'], $data['exp'])) return null;
            if ((int) $data['exp'] < time()) return null;
            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
