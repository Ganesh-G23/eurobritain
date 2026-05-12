<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = session('admin');
        $level = (int) data_get($admin, 'user_level', 0);

        if (! $admin || $level !== 1) {
            abort(403, 'This action is restricted to super administrators.');
        }

        return $next($request);
    }
}
