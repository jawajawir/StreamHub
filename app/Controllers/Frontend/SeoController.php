<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class SeoController extends Controller
{
    public function robots(Request $request): void
    {
        $rules = Database::getInstance()->fetchAll(
            'SELECT * FROM robots_rules WHERE status = "active" ORDER BY user_agent ASC, sort_order ASC, id ASC'
        );
        $base = rtrim((string) config('app.url', ''), '/');
        $lines = [];
        $currentUa = null;
        foreach ($rules as $r) {
            if ($r['user_agent'] !== $currentUa) {
                if ($currentUa !== null) $lines[] = '';
                $currentUa = $r['user_agent'];
                $lines[] = 'User-agent: ' . $currentUa;
            }
            if ($r['directive'] === 'sitemap') {
                $val = $r['rule_value'];
                if (str_starts_with($val, '/')) $val = $base . $val;
                $lines[] = 'Sitemap: ' . $val;
            } elseif ($r['directive'] === 'crawl_delay') {
                $lines[] = 'Crawl-delay: ' . $r['rule_value'];
            } else {
                $lines[] = ucfirst($r['directive']) . ': ' . $r['rule_value'];
            }
        }
        if (empty($lines)) {
            $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /account', 'Sitemap: ' . $base . '/sitemap.xml'];
        }
        Response::plain(implode("\n", $lines) . "\n");
    }
}
