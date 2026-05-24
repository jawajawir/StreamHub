<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

class AdminAuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): void
    {
        if (!Auth::checkAdmin()) {
            $loginUrl = admin_url('login');
            // Remember intended URL
            \App\Core\Session::instance()->set('_admin_intended', $request->path());
            Response::redirect($loginUrl);
            return;
        }
        $next($request);
    }
}
