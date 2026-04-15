<?php

namespace App\Http\Middleware;

use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalTeacher
{
    public function handle(Request $request, Closure $next): Response
    {
        $u = session(PortalSession::KEY_TEACHER);
        if (! is_array($u) || (int) ($u['role'] ?? 0) !== 1) {
            return redirect()->route('web.login');
        }

        return $next($request);
    }
}
