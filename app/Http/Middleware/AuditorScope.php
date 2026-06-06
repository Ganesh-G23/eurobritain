<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditorScope
{
    /**
     * @var list<string>
     */
    protected array $allowedPatterns = [
        'admin/certificate/due-list',
        'admin/certificate/audit',
        'admin/certificate/save-audit',
        'admin/certificate/log-expiry-calc',
        'admin/profile',
        'admin/profile/save_profile',
        'admin/profile/email-two-factor',
        'admin/security',
        'admin/security/save_change_password',
        'admin/logout',
        'admin/common/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $adminLevel = (int) data_get(session('admin'), 'user_level', 0);

        if ($adminLevel !== 2) {
            return $next($request);
        }

        $path = trim($request->path(), '/');

        if ($this->isAllowedPath($path)) {
            return $next($request);
        }

        return redirect('admin/certificate/due-list');
    }

    protected function isAllowedPath(string $path): bool
    {
        foreach ($this->allowedPatterns as $pattern) {
            $pattern = trim($pattern, '/');

            if ($pattern === $path) {
                return true;
            }

            if (str_ends_with($pattern, '/*')) {
                $prefix = rtrim(substr($pattern, 0, -2), '/');
                if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                    return true;
                }
            }
        }

        return false;
    }
}
