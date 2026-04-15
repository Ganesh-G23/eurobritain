<?php

namespace App\Support;

use App\Http\Middleware\PortalRememberFromCookie;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Keeps teacher / student / parent portal logins in separate session keys so one role
 * does not overwrite another in the same browser. {@see SyncPortalSessionUser} mirrors
 * the correct account into legacy {@code portal_user} for each request.
 */
final class PortalSession
{
    public const KEY_TEACHER = 'portal_user_teacher';

    public const KEY_STUDENT = 'portal_user_student';

    public const KEY_PARENT = 'portal_user_parent';

    public const KEY_ACTIVE_CONTEXT = 'portal_active_context';

    public static function keyForRole(int $role): ?string
    {
        return match ($role) {
            1 => self::KEY_TEACHER,
            2 => self::KEY_STUDENT,
            3 => self::KEY_PARENT,
            default => null,
        };
    }

    public static function putRoleUser(int $role, array $userArray): void
    {
        $k = self::keyForRole($role);
        if ($k !== null) {
            session()->put($k, $userArray);
        }
        session()->put('portal_last_login_role', $role);

        $otherRoleLoggedIn = false;
        foreach ([1, 2, 3] as $r) {
            if ($r === $role) {
                continue;
            }
            $otherKey = self::keyForRole($r);
            if ($otherKey !== null && is_array(session($otherKey))) {
                $otherRoleLoggedIn = true;
                break;
            }
        }
        // Only one role in the session → set UI context. Adding a 2nd role must NOT switch context,
        // otherwise shared URLs like user/dashboard show the wrong panel across browser tabs.
        if (!$otherRoleLoggedIn) {
            session()->put(self::KEY_ACTIVE_CONTEXT, $role);
        }

        session()->put('portal_user', $userArray);
        if ($role === 1) {
            session()->put('teacher', $userArray);
        } else {
            session()->forget('teacher');
        }
    }

    /**
     * Update stored user for a role (e.g. after profile save). Refreshes {@code portal_user}
     * only when that role is the active UI context.
     */
    public static function updateRoleUserArray(int $role, array $userArray): void
    {
        $k = self::keyForRole($role);
        if ($k !== null) {
            session()->put($k, $userArray);
        }
        if ((int) session(self::KEY_ACTIVE_CONTEXT, 0) === $role) {
            session()->put('portal_user', $userArray);
            if ($role === 1) {
                session()->put('teacher', $userArray);
            } else {
                session()->forget('teacher');
            }
        }
    }

    public static function anyRoleLoggedIn(): bool
    {
        return is_array(session(self::KEY_TEACHER))
            || is_array(session(self::KEY_STUDENT))
            || is_array(session(self::KEY_PARENT));
    }

    public static function multipleRolesLoggedIn(): bool
    {
        $n = 0;
        foreach ([self::KEY_TEACHER, self::KEY_STUDENT, self::KEY_PARENT] as $key) {
            if (is_array(session($key))) {
                $n++;
            }
        }

        return $n > 1;
    }

    public static function syncPortalUserFromRequest(Request $request): void
    {
        $path = ltrim($request->path(), '/');

        // Normalize bare "user" (redirect target) like other ambiguous paths
        if ($path === 'user') {
            $path = 'user/dashboard';
        }

        if (str_starts_with($path, 'user/teacher')) {
            $u = session(self::KEY_TEACHER);
            if (is_array($u)) {
                session()->put(self::KEY_ACTIVE_CONTEXT, 1);
                session()->put('portal_user', $u);
                session()->put('teacher', $u);

                return;
            }
            session()->forget('portal_user');
            session()->forget('teacher');

            return;
        }

        if (str_starts_with($path, 'user/student') || str_starts_with($path, 'user/select-teacher')) {
            $u = session(self::KEY_STUDENT);
            if (is_array($u)) {
                session()->put(self::KEY_ACTIVE_CONTEXT, 2);
                session()->put('portal_user', $u);
                session()->forget('teacher');

                return;
            }
            session()->forget('portal_user');
            session()->forget('teacher');

            return;
        }

        if (str_starts_with($path, 'user/parent') || str_starts_with($path, 'user/select-student')) {
            $u = session(self::KEY_PARENT);
            if (is_array($u)) {
                session()->put(self::KEY_ACTIVE_CONTEXT, 3);
                session()->put('portal_user', $u);
                session()->forget('teacher');

                return;
            }
            session()->forget('portal_user');
            session()->forget('teacher');

            return;
        }

        // Shared "home" URL: prefer teacher, then student, then parent so the stats dashboard works
        // when more than one portal account is stored in the same browser session.
        if (($path === 'user/dashboard' || $path === 'user') && self::multipleRolesLoggedIn()) {
            foreach ([1, 2, 3] as $r) {
                $key = self::keyForRole($r);
                if ($key !== null && is_array(session($key))) {
                    $u = session($key);
                    session()->put(self::KEY_ACTIVE_CONTEXT, $r);
                    session()->put('portal_user', $u);
                    if ($r === 1) {
                        session()->put('teacher', $u);
                    } else {
                        session()->forget('teacher');
                    }

                    return;
                }
            }
        }

        $ctx = (int) session(self::KEY_ACTIVE_CONTEXT, (int) session('portal_last_login_role', 0));
        if ($ctx === 1) {
            $u = session(self::KEY_TEACHER);
        } elseif ($ctx === 2) {
            $u = session(self::KEY_STUDENT);
        } elseif ($ctx === 3) {
            $u = session(self::KEY_PARENT);
        } else {
            $u = null;
        }

        if (is_array($u)) {
            session()->put('portal_user', $u);
            if ((int) ($u['role'] ?? 0) === 1) {
                session()->put('teacher', $u);
            } else {
                session()->forget('teacher');
            }

            return;
        }

        foreach ([[1, self::KEY_TEACHER], [2, self::KEY_STUDENT], [3, self::KEY_PARENT]] as [$r, $key]) {
            $u = session($key);
            if (is_array($u)) {
                session()->put(self::KEY_ACTIVE_CONTEXT, $r);
                session()->put('portal_user', $u);
                if ($r === 1) {
                    session()->put('teacher', $u);
                } else {
                    session()->forget('teacher');
                }

                return;
            }
        }

        session()->forget('portal_user');
        session()->forget('teacher');
    }

    public static function forgetRoleBucket(int $role): void
    {
        $k = self::keyForRole($role);
        if ($k !== null) {
            session()->forget($k);
        }
    }

    /**
     * Drop every portal role bucket except {@code $keepRole} (1=teacher, 2=student, 3=parent).
     * Call before {@see putRoleUser} on login so only one portal identity exists per session.
     */
    public static function forgetOtherRoleBuckets(int $keepRole): void
    {
        foreach ([1, 2, 3] as $r) {
            if ($r !== $keepRole) {
                self::forgetRoleBucket($r);
            }
        }
    }

    public static function logoutCurrentContext(): void
    {
        $portal = session('portal_user');
        $role = (int) ($portal['role'] ?? session(self::KEY_ACTIVE_CONTEXT, 0));
        if ($role >= 1 && $role <= 3) {
            $key = self::keyForRole($role);
            $u = $key !== null ? session($key) : null;
            if (is_array($u) && !empty($u['id'])) {
                PortalUser::whereKey((int) $u['id'])->update(['remember_token' => null]);
            }
            self::forgetRoleBucket($role);
        }

        Cookie::queue(Cookie::forget(PortalRememberFromCookie::COOKIE_NAME));
        session()->forget('portal_user');
        session()->forget('teacher');
        session()->forget('show_teacher_password_popup');
        session()->forget('acting_teacher_id');

        if (self::anyRoleLoggedIn()) {
            self::syncPortalUserFromRequest(request());
        } else {
            session()->forget(self::KEY_ACTIVE_CONTEXT);
            session()->forget('portal_last_login_role');
        }
    }
}
