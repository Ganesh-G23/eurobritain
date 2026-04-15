<?php

namespace App\Http\Middleware;

use App\Models\PortalUser;
use App\Support\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalRememberFromCookie
{
    public const COOKIE_NAME = 'remember_token';

    public function handle(Request $request, Closure $next): Response
    {
        if (! PortalSession::anyRoleLoggedIn()) {
            $token = $request->cookie(self::COOKIE_NAME);
            if (is_string($token) && $token !== '') {
                $user = PortalUser::where('remember_token', $token)->first();
                if ($user) {
                    PortalSession::putRoleUser((int) ($user->role ?? 0), $user->toArray());
                    session()->put('show_teacher_password_popup', (int) ($user->is_password_changed ?? 0) === 0 ? 1 : 0);
                }
            }
        }

        return $next($request);
    }
}
