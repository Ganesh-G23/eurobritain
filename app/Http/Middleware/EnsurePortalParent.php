<?php

namespace App\Http\Middleware;

use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalParent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_array(session(PortalSession::KEY_PARENT))) {
            return redirect()->route('web.login');
        }

        return $next($request);
    }
}
