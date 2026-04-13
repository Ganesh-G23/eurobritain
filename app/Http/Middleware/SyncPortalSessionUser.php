<?php

namespace App\Http\Middleware;

use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncPortalSessionUser
{
    public function handle(Request $request, Closure $next): Response
    {
        PortalSession::syncPortalUserFromRequest($request);

        return $next($request);
    }
}
