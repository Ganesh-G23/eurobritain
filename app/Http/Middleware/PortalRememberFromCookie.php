<?php

namespace App\Http\Middleware;

use App\Models\PortalUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalRememberFromCookie
{
    public const COOKIE_NAME = 'remember_token';

    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('portal_user')) {
            $token = $request->cookie(self::COOKIE_NAME);
            if (is_string($token) && $token !== '') {
                $user = PortalUser::where('remember_token', $token)->first();
                if ($user) {
                    session()->forget('admin');
                    session()->put('portal_user', $user->toArray());
                    if ((int) ($user->role ?? 0) === 1) {
                        session()->put('teacher', $user->toArray());
                    } else {
                        session()->forget('teacher');
                    }
                    session()->put('show_teacher_password_popup', (int) ($user->is_password_changed ?? 0) === 0 ? 1 : 0);
                }
            }
        }

        return $next($request);
    }
}
