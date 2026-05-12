<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAdminMustChangePassword
{
    private function allowedPath(Request $request): bool
    {
        if ($request->is('admin/security') && $request->isMethod('GET')) {
            return true;
        }
        if ($request->is('admin/security/save_change_password') && $request->isMethod('POST')) {
            return true;
        }
        if ($request->is('admin/logout') && $request->isMethod('GET')) {
            return true;
        }

        return false;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $admin = session('admin');
        if (! $admin) {
            return $next($request);
        }

        $id = is_object($admin) ? (int) $admin->id : (int) ($admin['id'] ?? 0);
        if ($id <= 0) {
            return $next($request);
        }

        $user = User::query()->find($id);
        if (! $user || ! $user->force_password_change) {
            return $next($request);
        }

        if ($this->allowedPath($request)) {
            return $next($request);
        }

        return redirect()->to(url('admin/security'));
    }
}
