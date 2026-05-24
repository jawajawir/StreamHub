<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class UserAuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): void
    {
        if (!Auth::check()) {
            Session::instance()->set('_user_intended', $request->path());
            if ($request->isAjax() || $request->isJson()) {
                Response::json(['error' => 'unauthenticated'], 401);
                return;
            }
            Response::redirect('/login');
            return;
        }
        $next($request);
    }
}
