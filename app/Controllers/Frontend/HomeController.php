<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Repositories\ContentRepository;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $repo = new ContentRepository();

        // Build sections defined in homepage_sections (status=active)
        $sections = Database::getInstance()->fetchAll(
            'SELECT * FROM homepage_sections WHERE status = "active" ORDER BY sort_order ASC, id ASC'
        );
        $rendered = [];
        foreach ($sections as $sec) {
            $items = match ($sec['section_type']) {
                'featured'    => Database::getInstance()->fetchAll(
                                    'SELECT id, slug, title, thumbnail_url, runtime_seconds, view_count
                                     FROM content_items
                                     WHERE is_featured = 1 AND lifecycle_status = "published" AND visibility_level = "visible" AND deleted_at IS NULL
                                     ORDER BY COALESCE(published_at, created_at) DESC LIMIT 12'),
                'latest'      => $repo->listPublic(['sort' => 'latest',      'per_page' => 12])['items'],
                'trending'    => $repo->listPublic(['sort' => 'trending',    'per_page' => 12])['items'],
                'most_viewed' => $repo->listPublic(['sort' => 'most_viewed', 'per_page' => 12])['items'],
                'most_liked'  => $repo->listPublic(['sort' => 'most_liked',  'per_page' => 12])['items'],
                'tag'         => Database::getInstance()->fetchAll(
                                    'SELECT id, name, slug, content_count FROM tags
                                     WHERE status = "active" ORDER BY content_count DESC, name ASC LIMIT 30'),
                default       => [],
            };
            if (!$items) continue;
            $rendered[] = ['section' => $sec, 'items' => $items];
        }

        $this->view('frontend.pages.home', [
            'title'    => null,
            'sections' => $rendered,
        ]);
    }
}
