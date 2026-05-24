<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Repositories\ContentRepository;
use App\Services\RateLimitService;

class SearchController extends Controller
{
    public function index(Request $request): void
    {
        $q    = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $page = max(1, (int) $request->query('page', 1));
        $list = ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => 24, 'pages' => 0];
        if ($q !== '') {
            // Rate limit
            if (!RateLimitService::hit('search', (string) hash_ip($request->ip()), 60, 30)) {
                $this->view('frontend.pages.listing', [
                    'title' => 'Too many searches', 'heading' => 'Slow down',
                    'list' => $list, 'baseUrl' => '/search', 'q' => $q, 'rate_limited' => true,
                ]); return;
            }
            $repo = new ContentRepository();
            $list = $repo->listPublic(['search' => $q, 'page' => $page, 'per_page' => 24, 'sort' => 'latest']);
            try {
                Database::getInstance()->insert('search_logs', [
                    'user_id'          => Auth::user()['id'] ?? null,
                    'query_text'       => $q,
                    'normalized_query' => mb_strtolower($q),
                    'result_count'     => $list['total'],
                    'ip_hash'          => hash_ip($request->ip()),
                ]);
            } catch (\Throwable $e) {}
        }
        $this->view('frontend.pages.listing', [
            'title'    => $q !== '' ? 'Search: ' . $q : 'Search',
            'heading'  => $q !== '' ? 'Results for "' . $q . '"' : 'Search',
            'list'     => $list,
            'baseUrl'  => '/search?q=' . rawurlencode($q),
            'q'        => $q,
        ]);
    }
}
