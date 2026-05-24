<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

/**
 * If maintenance mode is active for the relevant scope (frontend_only/full_site),
 * non-admin requests are short-circuited to the maintenance page.
 */
class MaintenanceMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): void
    {
        $window = $this->activeWindow();
        if ($window === null) { $next($request); return; }

        $path = $request->path();
        $adminBase = '/' . trim((string) config('app.admin_path', 'admin'), '/');
        $isAdminPath = str_starts_with($path, $adminBase);

        $allowAdminAccess = (int) ($window['allow_admin_access'] ?? 1) === 1;
        $mode = (string) $window['mode'];

        // Admin path with allow_admin_access stays alive
        if ($isAdminPath && $allowAdminAccess) { $next($request); return; }

        // full_site shuts everything except logged-in admins (if allowed)
        if ($mode === 'full_site') {
            if ($allowAdminAccess && Auth::checkAdmin()) { $next($request); return; }
            $this->renderMaintenance($window);
            return;
        }

        // frontend_only blocks frontend
        if ($mode === 'frontend_only' && !$isAdminPath) {
            $this->renderMaintenance($window);
            return;
        }

        // admin_only blocks admin (rare)
        if ($mode === 'admin_only' && $isAdminPath) {
            $this->renderMaintenance($window);
            return;
        }

        $next($request);
    }

    private function activeWindow(): ?array
    {
        try {
            $now = date('Y-m-d H:i:s');
            return \App\Core\Database::getInstance()->fetch(
                'SELECT * FROM maintenance_windows WHERE status = "active" AND starts_at <= :n AND (ends_at IS NULL OR ends_at >= :n) ORDER BY starts_at DESC LIMIT 1',
                [':n' => $now]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function renderMaintenance(array $window): void
    {
        http_response_code(503);
        header('Retry-After: 600');
        $template = STREAMHUB_BASE . '/resources/views/frontend/pages/maintenance.php';
        if (is_file($template)) {
            include $template;
        } else {
            echo '<h1>' . e($window['title']) . '</h1><p>' . e($window['message']) . '</p>';
        }
    }
}
