<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

/** Used on /login, /register to redirect already-authed users to /account. */
class GuestMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): void
    {
        if (Auth::check()) {
            Response::redirect('/account');
            return;
        }
        $next($request);
    }
}
