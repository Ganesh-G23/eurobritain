<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $adminLevel = (int) data_get(session('admin'), 'user_level', 0);

        if ($adminLevel !== 1) {
            return redirect('admin/certificate/due-list');
        }

        return $next($request);
    }
}
