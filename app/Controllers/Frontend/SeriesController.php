<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ContentRepository;

class SeriesController extends Controller
{
    public function show(Request $request): void
    {
        $slug = (string) $request->param('slug');
        $row = Database::getInstance()->fetch('SELECT * FROM series_collections WHERE slug = :s AND status = "active" LIMIT 1', [':s' => $slug]);
        if (!$row) { Response::notFound(); return; }
        $page = max(1, (int) $request->query('page', 1));
        $list = (new ContentRepository())->listPublic(['series_id' => (int) $row['id'], 'page' => $page]);
        $this->view('frontend.pages.entity', [
            'title' => $row['name'],
            'heading' => $row['name'],
            'subheading' => $row['description'] ?? '',
            'cover' => $row['cover_url'],
            'avatar' => null,
            'list' => $list,
            'baseUrl' => '/series/' . $slug,
        ]);
    }
}
