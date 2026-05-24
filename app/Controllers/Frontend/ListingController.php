<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\ContentRepository;

class ListingController extends Controller
{
    public function latest(Request $request): void   { $this->render($request, 'latest',      'Latest'); }
    public function trending(Request $request): void { $this->render($request, 'trending',    'Trending'); }
    public function mostViewed(Request $request): void { $this->render($request, 'most_viewed','Most Viewed'); }

    private function render(Request $request, string $sort, string $title): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $repo = new ContentRepository();
        $data = $repo->listPublic(['sort' => $sort, 'page' => $page, 'per_page' => 24]);
        $this->view('frontend.pages.listing', [
            'title'    => $title,
            'heading'  => $title,
            'list'     => $data,
            'baseUrl'  => '/' . match ($sort) {
                'latest' => 'latest', 'trending' => 'trending', 'most_viewed' => 'most-viewed',
                default => 'latest',
            },
        ]);
    }
}
