<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ContentRepository;

class TagController extends Controller
{
    public function show(Request $request): void
    {
        $slug = (string) $request->param('slug');
        $row = Database::getInstance()->fetch('SELECT * FROM tags WHERE slug = :s AND status = "active" LIMIT 1', [':s' => $slug]);
        if (!$row) { Response::notFound(); return; }
        $page = max(1, (int) $request->query('page', 1));
        $list = (new ContentRepository())->listPublic(['tag_id' => (int) $row['id'], 'page' => $page]);
        $this->view('frontend.pages.listing', [
            'title' => '#' . $row['name'],
            'heading' => '#' . $row['name'],
            'list' => $list,
            'baseUrl' => '/tag/' . $slug,
        ]);
    }
}
