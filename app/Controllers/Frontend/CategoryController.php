<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ContentRepository;

class CategoryController extends Controller
{
    public function show(Request $request): void
    {
        $slug = (string) $request->param('slug');
        $cat = Database::getInstance()->fetch('SELECT * FROM categories WHERE slug = :s AND status = "active" LIMIT 1', [':s' => $slug]);
        if (!$cat) { Response::notFound(); return; }
        $page = max(1, (int) $request->query('page', 1));
        $sort = (string) $request->query('sort', 'latest');
        $list = (new ContentRepository())->listPublic([
            'category_id' => (int) $cat['id'], 'page' => $page, 'sort' => $sort, 'per_page' => 24,
        ]);
        $this->view('frontend.pages.listing', [
            'title' => $cat['name'],
            'heading' => $cat['name'],
            'subheading' => $cat['description'] ?? null,
            'list' => $list,
            'baseUrl' => '/category/' . $slug,
            'sort' => $sort,
        ]);
    }
}
