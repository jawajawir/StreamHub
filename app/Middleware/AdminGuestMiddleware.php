<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

/** Used on /admin/login to send already-authed admins to dashboard. */
class AdminGuestMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): void
    {
        if (Auth::checkAdmin()) {
            Response::redirect(admin_url(''));
            return;
        }
        $next($request);
    }
}
