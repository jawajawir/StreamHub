<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ContentRepository;
use App\Services\AccessRuleService;
use App\Services\PlayerService;

class WatchController extends Controller
{
    public function show(Request $request): void
    {
        $slug = (string) $request->param('slug');
        $repo = new ContentRepository();
        $content = $repo->findBySlug($slug);
        if (!$content) {
            // Slug history fallback
            $row = Database::getInstance()->fetch(
                'SELECT entity_id FROM slug_history WHERE entity_type = "content" AND old_slug = :s LIMIT 1',
                [':s' => $slug]
            );
            if ($row) {
                $new = Database::getInstance()->fetch('SELECT slug FROM content_items WHERE id = :id LIMIT 1', [':id' => $row['entity_id']]);
                if ($new) { Response::redirect('/watch/' . $new['slug'], 301); return; }
            }
            Response::notFound(); return;
        }

        $decision = AccessRuleService::decideContentAccess($content);
        $source   = $repo->source((int) $content['id']);
        $player   = $source ? PlayerService::resolve($content, $source) : ['type' => 'error', 'error' => 'no_source'];

        // Direct asset URL (only sent to client when access decision is allow)
        $directUrl = null;
        if ($source && $source['source_type'] === 'direct_mysql' && $decision['decision'] === AccessRuleService::DECISION_ALLOW) {
            $asset = null;
            if (!empty($source['direct_media_asset_id'])) {
                $asset = Database::getInstance()->fetch('SELECT public_url, mime_type FROM media_assets WHERE id = :id LIMIT 1', [':id' => $source['direct_media_asset_id']]);
            }
            $directUrl = $asset['public_url'] ?? ($source['iframe_url'] ?? null);
        }

        // Bump view (only for allowed, do not double-count guests excessively)
        if ($decision['decision'] === AccessRuleService::DECISION_ALLOW) {
            $repo->incrementView((int) $content['id']);
            try {
                Database::getInstance()->insert('content_views', [
                    'content_id'      => $content['id'],
                    'user_id'         => Auth::user()['id'] ?? null,
                    'membership_tier' => Auth::user()['membership_tier'] ?? 'guest',
                    'ip_hash'         => hash_ip($request->ip()),
                ]);
            } catch (\Throwable $e) {}
        }

        $tags        = $repo->tagsOf((int) $content['id']);
        $categories  = $repo->categoriesOf((int) $content['id']);
        $performers  = $repo->performersOf((int) $content['id']);
        $related     = $repo->related($content, 12);
        $reaction    = null;
        if (Auth::check()) {
            $row = Database::getInstance()->fetch(
                'SELECT reaction FROM content_reactions WHERE user_id = :u AND content_id = :c LIMIT 1',
                [':u' => Auth::user()['id'], ':c' => $content['id']]
            );
            $reaction = $row['reaction'] ?? null;
        }
        $favorited = false;
        if (Auth::check()) {
            $favorited = (bool) Database::getInstance()->fetch(
                'SELECT 1 FROM user_favorites WHERE user_id = :u AND content_id = :c LIMIT 1',
                [':u' => Auth::user()['id'], ':c' => $content['id']]
            );
        }

        $countdown = (int) (\App\Services\SettingService::get('player', 'guest_countdown', 5) ?? 5);

        $this->view('frontend.pages.watch', [
            'title'      => $content['title'],
            'metaDesc'   => mb_substr((string) ($content['description'] ?? ''), 0, 280),
            'ogImage'    => $content['thumbnail_url'],
            'content'    => $content,
            'source'     => $source,
            'player'     => $player,
            'directUrl'  => $directUrl,
            'decision'   => $decision,
            'tags'       => $tags,
            'categories' => $categories,
            'performers' => $performers,
            'related'    => $related,
            'reaction'   => $reaction,
            'favorited'  => $favorited,
            'countdown'  => $countdown,
        ]);
    }

    public function reportBroken(Request $request): void
    {
        $this->validateCsrf($request);
        $id = (int) $request->param('id');
        $message = trim((string) $request->post('message', ''));
        if (!\App\Services\RateLimitService::hit('report:' . $id, (string) hash_ip($request->ip()), 3600, 5)) {
            \App\Core\Session::instance()->flash('error', 'Too many reports. Please try later.');
            $this->back('/'); return;
        }
        Database::getInstance()->insert('reports', [
            'reporter_user_id' => Auth::user()['id'] ?? null,
            'report_type'      => 'broken_video',
            'entity_type'      => 'content',
            'entity_id'        => $id,
            'message'          => mb_substr($message, 0, 1000),
        ]);
        Database::getInstance()->query('UPDATE content_items SET report_count = report_count + 1 WHERE id = :id', [':id' => $id]);
        \App\Services\AuditService::log('content.report', 'content', $id);
        \App\Core\Session::instance()->flash('success', 'Thanks. The report was sent to moderators.');
        $this->back('/');
    }

    /**
     * Stream Direct MySQL playback through a signed URL.
     * The token contains content_id + expiry. We verify access here too.
     */
    public function playback(Request $request): void
    {
        $token = (string) $request->param('token');
        $payload = PlayerService::verifyPlaybackToken($token);
        if (!$payload) {
            \App\Services\SecurityService::logEvent('hotlink_blocked', 'warning', $request, ['reason' => 'invalid_token']);
            Response::forbidden('Playback token invalid or expired.'); return;
        }
        $repo = new ContentRepository();
        $content = $repo->findById((int) $payload['cid']);
        if (!$content) { Response::notFound(); return; }
        $decision = AccessRuleService::decideContentAccess($content);
        if ($decision['decision'] !== AccessRuleService::DECISION_ALLOW) {
            Response::forbidden('Access denied.'); return;
        }
        $source = $repo->source((int) $content['id']);
        if (!$source || $source['source_type'] !== 'direct_mysql') { Response::notFound(); return; }
        $url = null;
        if (!empty($source['direct_media_asset_id'])) {
            $asset = Database::getInstance()->fetch('SELECT public_url FROM media_assets WHERE id = :id LIMIT 1', [':id' => $source['direct_media_asset_id']]);
            $url = $asset['public_url'] ?? null;
        }
        if (!$url) { Response::notFound(); return; }
        // 302 to the underlying CDN/asset URL. Anti-hotlink can be enforced at storage level.
        Response::redirect($url, 302);
    }
}
