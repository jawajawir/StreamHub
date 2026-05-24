<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;

/**
 * Centralized server-side access decisions.
 * Used by WatchController, EmbedController, AccountController, etc.
 */
class AccessRuleService
{
    public const DECISION_ALLOW              = 'allow';
    public const DECISION_REQUIRE_LOGIN      = 'require_login';
    public const DECISION_REQUIRE_MEMBERSHIP = 'require_membership';
    public const DECISION_BLOCK              = 'block';

    /**
     * Evaluate playback access for a content item.
     *
     * @param array $content content_items row (must contain access_level, lifecycle_status, visibility_level)
     * @return array{decision:string, required_tier?:string, reason?:string}
     */
    public static function decideContentAccess(array $content): array
    {
        $status = $content['lifecycle_status'] ?? 'draft';
        if (!in_array($status, ['published', 'scheduled'], true)) {
            return ['decision' => self::DECISION_BLOCK, 'reason' => 'unavailable'];
        }
        if ($status === 'scheduled') {
            $when = $content['scheduled_at'] ?? null;
            if ($when && strtotime($when) > time()) {
                return ['decision' => self::DECISION_BLOCK, 'reason' => 'scheduled'];
            }
        }
        if (($content['visibility_level'] ?? 'visible') === 'hidden') {
            return ['decision' => self::DECISION_BLOCK, 'reason' => 'hidden'];
        }

        $required = $content['access_level'] ?? 'public';
        $tier     = self::userTier();

        if ($required === 'public') {
            return ['decision' => self::DECISION_ALLOW];
        }
        if ($required === 'registered') {
            return Auth::check()
                ? ['decision' => self::DECISION_ALLOW]
                : ['decision' => self::DECISION_REQUIRE_LOGIN];
        }
        if ($required === 'premium') {
            if (!Auth::check()) return ['decision' => self::DECISION_REQUIRE_LOGIN];
            return in_array($tier, ['premium', 'vip'], true)
                ? ['decision' => self::DECISION_ALLOW]
                : ['decision' => self::DECISION_REQUIRE_MEMBERSHIP, 'required_tier' => 'premium'];
        }
        if ($required === 'vip') {
            if (!Auth::check()) return ['decision' => self::DECISION_REQUIRE_LOGIN];
            return $tier === 'vip'
                ? ['decision' => self::DECISION_ALLOW]
                : ['decision' => self::DECISION_REQUIRE_MEMBERSHIP, 'required_tier' => 'vip'];
        }
        return ['decision' => self::DECISION_BLOCK, 'reason' => 'unknown_access_level'];
    }

    public static function userTier(): string
    {
        $u = Auth::user();
        if (!$u) return 'guest';
        return $u['membership_tier'] ?? 'free';
    }

    /** Tier for ads decisions (guest-> guest, otherwise membership_tier). */
    public static function adsTier(): string
    {
        return self::userTier();
    }
}
