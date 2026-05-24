<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\FeatureToggleService;

class AccountController extends Controller
{
    private function requireUser(Request $request): array
    {
        if (!Auth::check()) {
            if ($request->isAjax() || $request->isJson()) {
                Response::json(['error' => 'unauthenticated'], 401);
                exit;
            }
            Response::redirect('/login'); exit;
        }
        return Auth::user();
    }

    public function dashboard(Request $request): void
    {
        $user = $this->requireUser($request);
        $db = Database::getInstance();
        $stats = [
            'favorites' => (int) $db->fetchValue('SELECT COUNT(*) FROM user_favorites WHERE user_id = :u', [':u' => $user['id']]),
            'history'   => (int) $db->fetchValue('SELECT COUNT(*) FROM watch_history WHERE user_id = :u', [':u' => $user['id']]),
            'liked'     => (int) $db->fetchValue('SELECT COUNT(*) FROM content_reactions WHERE user_id = :u AND reaction = "like"', [':u' => $user['id']]),
        ];
        $continue = $db->fetchAll(
            'SELECT c.id, c.slug, c.title, c.thumbnail_url, c.runtime_seconds, wh.progress_percent, wh.last_watched_at
             FROM watch_history wh JOIN content_items c ON c.id = wh.content_id
             WHERE wh.user_id = :u AND wh.completed = 0 AND c.lifecycle_status = "published"
             ORDER BY wh.last_watched_at DESC LIMIT 8',
            [':u' => $user['id']]
        );
        $prefs = $db->fetch('SELECT * FROM user_preferences WHERE user_id = :u', [':u' => $user['id']]) ?: [];
        $this->view('frontend.pages.account.dashboard', [
            'title' => 'Account', 'stats' => $stats, 'continue' => $continue, 'prefs' => $prefs,
        ]);
    }

    public function history(Request $request): void
    {
        $user = $this->requireUser($request);
        $rows = Database::getInstance()->fetchAll(
            'SELECT c.id, c.slug, c.title, c.thumbnail_url, c.runtime_seconds, wh.progress_percent, wh.last_watched_at
             FROM watch_history wh JOIN content_items c ON c.id = wh.content_id
             WHERE wh.user_id = :u ORDER BY wh.last_watched_at DESC LIMIT 60',
            [':u' => $user['id']]
        );
        $this->view('frontend.pages.account.history', ['title' => 'Watch history', 'rows' => $rows]);
    }

    public function clearHistory(Request $request): void
    {
        $this->validateCsrf($request);
        $user = $this->requireUser($request);
        Database::getInstance()->delete('watch_history', 'user_id = :u', [':u' => $user['id']]);
        Session::instance()->flash('success', 'Watch history cleared.');
        $this->redirect('/account/history');
    }

    public function pauseHistory(Request $request): void
    {
        $this->validateCsrf($request);
        $user = $this->requireUser($request);
        $value = $request->post('pause') ? 1 : 0;
        Database::getInstance()->update('user_preferences', ['pause_watch_history' => $value], 'user_id = :u', [':u' => $user['id']]);
        Session::instance()->flash('success', $value ? 'History paused.' : 'History resumed.');
        $this->redirect('/account/history');
    }

    public function favorites(Request $request): void
    {
        $user = $this->requireUser($request);
        $rows = Database::getInstance()->fetchAll(
            'SELECT c.id, c.slug, c.title, c.thumbnail_url, c.runtime_seconds, c.view_count
             FROM user_favorites f JOIN content_items c ON c.id = f.content_id
             WHERE f.user_id = :u AND c.lifecycle_status = "published" ORDER BY f.created_at DESC LIMIT 60',
            [':u' => $user['id']]
        );
        $this->view('frontend.pages.account.favorites', ['title' => 'Favorites', 'rows' => $rows]);
    }

    public function updatePrivacy(Request $request): void
    {
        $this->validateCsrf($request);
        $user = $this->requireUser($request);
        Database::getInstance()->update('user_preferences', [
            'private_mode'       => $request->post('private_mode') ? 1 : 0,
            'show_favorites'     => in_array($request->post('show_favorites'), ['private','members','public'], true) ? $request->post('show_favorites') : 'private',
            'email_notifications'=> $request->post('email_notifications') ? 1 : 0,
            'newsletter_opt_in'  => $request->post('newsletter_opt_in') ? 1 : 0,
        ], 'user_id = :u', [':u' => $user['id']]);
        Session::instance()->flash('success', 'Privacy preferences updated.');
        $this->redirect('/account');
    }

    public function logoutAllDevices(Request $request): void
    {
        $this->validateCsrf($request);
        $user = $this->requireUser($request);
        Database::getInstance()->update('user_sessions', ['is_active' => 0], 'user_id = :u', [':u' => $user['id']]);
        Auth::logoutUser();
        $this->redirect('/login');
    }

    public function likeContent(Request $request): void
    {
        $this->validateCsrf($request);
        if (!FeatureToggleService::enabled('like_dislike')) { Response::notFound(); return; }
        $user = $this->requireUser($request);
        $this->react($user, (int) $request->param('id'), 'like');
    }

    public function dislikeContent(Request $request): void
    {
        $this->validateCsrf($request);
        if (!FeatureToggleService::enabled('like_dislike')) { Response::notFound(); return; }
        $user = $this->requireUser($request);
        $this->react($user, (int) $request->param('id'), 'dislike');
    }

    private function react(array $user, int $contentId, string $reaction): void
    {
        $db = Database::getInstance();
        $content = $db->fetch('SELECT id FROM content_items WHERE id = :id AND lifecycle_status = "published" LIMIT 1', [':id' => $contentId]);
        if (!$content) { Response::notFound(); return; }
        $existing = $db->fetch('SELECT id, reaction FROM content_reactions WHERE user_id = :u AND content_id = :c LIMIT 1', [':u' => $user['id'], ':c' => $contentId]);
        $db->transaction(function () use ($db, $existing, $contentId, $user, $reaction) {
            if (!$existing) {
                $db->insert('content_reactions', ['user_id' => $user['id'], 'content_id' => $contentId, 'reaction' => $reaction]);
                $db->query('UPDATE content_items SET ' . ($reaction === 'like' ? 'like_count' : 'dislike_count') . ' = ' . ($reaction === 'like' ? 'like_count' : 'dislike_count') . ' + 1 WHERE id = :id', [':id' => $contentId]);
            } elseif ($existing['reaction'] === $reaction) {
                $db->delete('content_reactions', 'id = :id', [':id' => $existing['id']]);
                $db->query('UPDATE content_items SET ' . ($reaction === 'like' ? 'like_count' : 'dislike_count') . ' = GREATEST(' . ($reaction === 'like' ? 'like_count' : 'dislike_count') . ' - 1, 0) WHERE id = :id', [':id' => $contentId]);
            } else {
                $db->update('content_reactions', ['reaction' => $reaction], 'id = :id', [':id' => $existing['id']]);
                $db->query('UPDATE content_items SET like_count = ' . ($reaction === 'like' ? 'like_count + 1' : 'GREATEST(like_count - 1, 0)') . ', dislike_count = ' . ($reaction === 'dislike' ? 'dislike_count + 1' : 'GREATEST(dislike_count - 1, 0)') . ' WHERE id = :id', [':id' => $contentId]);
            }
        });
        Session::instance()->flash('success', 'Reaction saved.');
        $this->back('/');
    }

    public function toggleFavorite(Request $request): void
    {
        $this->validateCsrf($request);
        if (!FeatureToggleService::enabled('favorites')) { Response::notFound(); return; }
        $user = $this->requireUser($request);
        $contentId = (int) $request->param('id');
        $db = Database::getInstance();
        $exists = $db->fetch('SELECT 1 FROM user_favorites WHERE user_id = :u AND content_id = :c', [':u' => $user['id'], ':c' => $contentId]);
        if ($exists) {
            $db->delete('user_favorites', 'user_id = :u AND content_id = :c', [':u' => $user['id'], ':c' => $contentId]);
            $db->query('UPDATE content_items SET favorite_count = GREATEST(favorite_count - 1, 0) WHERE id = :id', [':id' => $contentId]);
            Session::instance()->flash('success', 'Removed from favorites.');
        } else {
            $db->query('INSERT IGNORE INTO user_favorites (user_id, content_id) VALUES (:u, :c)', [':u' => $user['id'], ':c' => $contentId]);
            $db->query('UPDATE content_items SET favorite_count = favorite_count + 1 WHERE id = :id', [':id' => $contentId]);
            Session::instance()->flash('success', 'Saved to favorites.');
        }
        $this->back('/');
    }
}
