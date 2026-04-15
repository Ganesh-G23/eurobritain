<?php

namespace App\Http\Middleware;

use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PortalSession::anyRoleLoggedIn()) {
            return redirect()->route('web.login');
        }

        return $next($request);
    }
}
