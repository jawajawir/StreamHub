<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class SitemapController extends Controller
{
    public function index(Request $request): void
    {
        $base = rtrim((string) config('app.url', ''), '/');
        $items = [['loc' => $base . '/', 'priority' => '1.0']];

        $db = Database::getInstance();
        // Static pages that are published and indexable
        $pages = $db->fetchAll('SELECT slug FROM pages WHERE status = "published"');
        foreach ($pages as $p) $items[] = ['loc' => $base . '/page/' . $p['slug'], 'priority' => '0.5'];

        // Categories (active)
        $cats = $db->fetchAll('SELECT slug FROM categories WHERE status = "active"');
        foreach ($cats as $c) $items[] = ['loc' => $base . '/category/' . $c['slug'], 'priority' => '0.6'];

        // Tags (active)
        $tags = $db->fetchAll('SELECT slug FROM tags WHERE status = "active" AND content_count > 0');
        foreach ($tags as $t) $items[] = ['loc' => $base . '/tag/' . $t['slug'], 'priority' => '0.4'];

        // Published, indexable content (cap to a reasonable number for one file)
        $contents = $db->fetchAll(
            'SELECT slug, COALESCE(published_at, created_at) AS lastmod FROM content_items
             WHERE lifecycle_status = "published" AND visibility_level = "visible" AND seo_indexable = 1
             ORDER BY COALESCE(published_at, created_at) DESC LIMIT 5000'
        );
        foreach ($contents as $c) {
            $items[] = ['loc' => $base . '/watch/' . $c['slug'], 'lastmod' => date('c', strtotime((string) $c['lastmod'])), 'priority' => '0.7'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($items as $it) {
            $xml .= '<url><loc>' . htmlspecialchars($it['loc']) . '</loc>';
            if (!empty($it['lastmod'])) $xml .= '<lastmod>' . htmlspecialchars($it['lastmod']) . '</lastmod>';
            if (!empty($it['priority'])) $xml .= '<priority>' . $it['priority'] . '</priority>';
            $xml .= '</url>';
        }
        $xml .= '</urlset>';
        Response::xml($xml);
    }
}
