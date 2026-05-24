<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class PageController extends Controller
{
    public function show(Request $request): void
    {
        $slug = (string) $request->param('slug');
        $row = Database::getInstance()->fetch(
            'SELECT * FROM pages WHERE slug = :s AND status = "published" LIMIT 1',
            [':s' => $slug]
        );
        if (!$row) { Response::notFound(); return; }
        $this->view('frontend.pages.page', [
            'title'    => $row['title'],
            'metaDesc' => mb_substr(strip_tags((string) $row['sanitized_body']), 0, 280),
            'page'     => $row,
        ]);
    }
}
