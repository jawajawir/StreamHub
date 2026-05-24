<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ContentRepository;
use App\Services\FeatureToggleService;

class StudioController extends Controller
{
    public function show(Request $request): void
    {
        if (!FeatureToggleService::enabled('public_performer_studio_page')) { Response::notFound(); return; }
        $slug = (string) $request->param('slug');
        $row = Database::getInstance()->fetch('SELECT * FROM studios WHERE slug = :s AND status = "active" LIMIT 1', [':s' => $slug]);
        if (!$row) { Response::notFound(); return; }
        $page = max(1, (int) $request->query('page', 1));
        $list = (new ContentRepository())->listPublic(['studio_id' => (int) $row['id'], 'page' => $page]);
        $this->view('frontend.pages.entity', [
            'title' => $row['name'],
            'heading' => $row['name'],
            'subheading' => $row['bio'] ?? '',
            'cover' => null,
            'avatar' => $row['logo_url'],
            'list' => $list,
            'baseUrl' => '/studio/' . $slug,
        ]);
    }
}
