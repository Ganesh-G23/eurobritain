<?php

namespace App\Http\Middleware;

use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_array(session(PortalSession::KEY_STUDENT))) {
            return redirect()->route('web.login');
        }

        return $next($request);
    }
}
