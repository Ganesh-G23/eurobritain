<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\PortalRememberFromCookie;
use App\Models\PortalUser;
use App\Support\PortalSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    private const PENDING_TTL_SECONDS = 900;

    private const OTP_TTL_SECONDS = 600;

    public function login(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => 'required|in:teacher,student,parent',
            'remember' => 'nullable|in:0,1',
            'terms' => 'required|accepted',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $roleMap = [
            'teacher' => 1,
            'student' => 2,
            'parent' => 3,
        ];
        $targetRole = $roleMap[$request->role] ?? 0;

        $user = PortalUser::where('role', $targetRole)
            ->where('email', trim($request->email))
            ->first();

        $validPassword = false;
        if ($user) {
            $validPassword = Hash::check($request->password, (string) $user->password);
            if (! $validPassword && ! empty($user->p)) {
                $validPassword = $request->password === (string) $user->p;
            }
        }

        if (! $user || ! $validPassword) {
            $this->response['error'] = 'Invalid credentials!';

            return response()->json($this->response);
        }

        if (! Hash::check($request->password, (string) $user->password)) {
            $user->password = Hash::make($request->password);
            $user->p = $request->password;
        }

        $loggedRole = (int) ($user->role ?? 0);
        $remember = $request->has('remember');

        if (in_array($loggedRole, [1, 2], true) && $user->email_two_factor_enabled) {
            if (! filled($user->email)) {
                $this->response['error'] = 'Your account has two-factor security enabled but no email address. Please contact support.';

                return response()->json($this->response);
            }

            $user->save();
            $user = $user->fresh();
            if (! $user) {
                $this->response['error'] = 'Invalid credentials!';

                return response()->json($this->response);
            }

            $pendingToken = Str::random(64);
            $otp = (string) random_int(100000, 999999);

            Cache::put(
                'portal_2fa_pending:'.$pendingToken,
                [
                    'user_id' => (int) $user->id,
                    'remember' => $remember,
                    'role' => $loggedRole,
                ],
                now()->addSeconds(self::PENDING_TTL_SECONDS),
            );
            Cache::put(
                'portal_2fa_code_hash:'.$user->id,
                hash('sha256', $otp),
                now()->addSeconds(self::OTP_TTL_SECONDS),
            );

            try {
                Mail::send('web.emails.portal_login_otp', [
                    'user' => $user,
                    'code' => $otp,
                ], function ($message) use ($user) {
                    $message->to((string) $user->email)
                        ->subject('Your EliteGrade sign-in code');
                });
            } catch (\Throwable $e) {
                Log::error('Portal login OTP email failed: '.$e->getMessage());
                Cache::forget('portal_2fa_pending:'.$pendingToken);
                Cache::forget('portal_2fa_code_hash:'.$user->id);
                $this->response['error'] = 'Could not send verification email. Please try again later.';

                return response()->json($this->response);
            }

            $this->response['status'] = 1;
            $this->response['requires_otp'] = true;
            $this->response['pending_token'] = $pendingToken;
            $this->response['msg'] = 'Enter the verification code sent to your email.';

            return response()->json($this->response);
        }

        $this->finalizePortalLogin($user, $remember);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Login successful...';
        $this->response['redirect_url'] = $this->redirectUrlForRole((int) ($user->role ?? 0));

        return response()->json($this->response);
    }

    public function verifyLoginOtp(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'pending_token' => 'required|string|size:64',
            'otp' => ['required', 'regex:/^[0-9]{6}$/'],
        ]);

        if ($validation->fails()) {
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        $pendingToken = $request->input('pending_token');
        $rateKey = 'portal-2fa-verify:'.sha1($pendingToken);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->response['error'] = 'Too many attempts. Please sign in again.';

            return response()->json($this->response);
        }

        $pending = Cache::get('portal_2fa_pending:'.$pendingToken);
        if (! is_array($pending) || empty($pending['user_id'])) {
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        $user = PortalUser::query()->whereKey((int) $pending['user_id'])->first();
        if (! $user) {
            Cache::forget('portal_2fa_pending:'.$pendingToken);
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        $expectedRole = (int) ($pending['role'] ?? 0);
        $actualRole = (int) ($user->role ?? 0);
        if (! in_array($actualRole, [1, 2], true) || $actualRole !== $expectedRole) {
            Cache::forget('portal_2fa_pending:'.$pendingToken);
            Cache::forget('portal_2fa_code_hash:'.$user->id);
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        $storedHash = Cache::get('portal_2fa_code_hash:'.$user->id);
        if (! is_string($storedHash) || ! hash_equals($storedHash, hash('sha256', (string) $request->input('otp')))) {
            RateLimiter::hit($rateKey, self::PENDING_TTL_SECONDS);
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        RateLimiter::clear($rateKey);
        Cache::forget('portal_2fa_pending:'.$pendingToken);
        Cache::forget('portal_2fa_code_hash:'.$user->id);

        $remember = (bool) ($pending['remember'] ?? false);
        $this->finalizePortalLogin($user, $remember);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Login successful...';
        $this->response['redirect_url'] = $this->redirectUrlForRole((int) ($user->role ?? 0));

        return response()->json($this->response);
    }

    private function finalizePortalLogin(PortalUser $user, bool $remember): void
    {
        if ($remember) {
            $token = Str::random(60);
            $user->remember_token = $token;
            cookie()->queue(PortalRememberFromCookie::COOKIE_NAME, $token, 43200);
        } else {
            $user->remember_token = null;
            Cookie::queue(Cookie::forget(PortalRememberFromCookie::COOKIE_NAME));
        }

        $user->save();
        $fresh = $user->fresh();
        if (! $fresh) {
            return;
        }

        $loggedRole = (int) ($fresh->role ?? 0);
        PortalSession::forgetOtherRoleBuckets($loggedRole);
        PortalSession::putRoleUser($loggedRole, $fresh->toArray());
        session()->put('show_teacher_password_popup', (int) ($fresh->is_password_changed ?? 0) === 0 ? 1 : 0);
    }

    private function redirectUrlForRole(int $role): string
    {
        return match ($role) {
            1 => url('user/dashboard'),
            2 => url('user/student/dashboard'),
            3 => url('user/parent/dashboard'),
            default => url('user'),
        };
    }
}
