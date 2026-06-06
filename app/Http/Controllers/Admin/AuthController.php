<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const PENDING_TTL_SECONDS = 900;

    private const OTP_TTL_SECONDS = 600;

    public function login()
    {
        return view('admin.login');
    }

    public function verifyLogin(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $admin = User::query()->where('email', trim($request->email))->first();

        $validPassword = false;
        if ($admin) {
            $validPassword = Hash::check($request->password, (string) $admin->password);
            if (! $validPassword && filled($admin->p)) {
                $validPassword = $request->password === (string) $admin->p;
            }
        }

        if (! $admin || ! $validPassword) {
            $this->response['error'] = 'Invalid credentials!';

            return response()->json($this->response);
        }

        if (! Hash::check($request->password, (string) $admin->password)) {
            $admin->password = $request->password;
            $admin->p = $request->password;
            $admin->save();
            $admin = $admin->fresh();
        }

        if (! $admin) {
            $this->response['error'] = 'Invalid credentials!';

            return response()->json($this->response);
        }


        $this->finalizeAdminLogin($admin);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Login successful...';
        $this->response['redirect_url'] = $this->redirectAfterLogin($admin);
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
        $rateKey = 'admin-2fa-verify:'.sha1($pendingToken);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->response['error'] = 'Too many attempts. Please sign in again.';

            return response()->json($this->response);
        }

        $pending = Cache::get('admin_2fa_pending:'.$pendingToken);
        if (! is_array($pending) || empty($pending['user_id'])) {
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        $admin = User::query()->whereKey((int) $pending['user_id'])->first();
        if (! $admin) {
            Cache::forget('admin_2fa_pending:'.$pendingToken);
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        if (! $admin->email_two_factor_enabled) {
            Cache::forget('admin_2fa_pending:'.$pendingToken);
            Cache::forget('admin_2fa_code_hash:'.$admin->id);
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        $storedHash = Cache::get('admin_2fa_code_hash:'.$admin->id);
        if (! is_string($storedHash) || ! hash_equals($storedHash, hash('sha256', (string) $request->input('otp')))) {
            RateLimiter::hit($rateKey, self::PENDING_TTL_SECONDS);
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        RateLimiter::clear($rateKey);
        Cache::forget('admin_2fa_pending:'.$pendingToken);
        Cache::forget('admin_2fa_code_hash:'.$admin->id);

        $this->finalizeAdminLogin($admin);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Login successful...';
        $this->response['redirect_url'] = $this->redirectAfterLogin($admin);

        return response()->json($this->response);
    }

    private function finalizeAdminLogin(User $admin): void
    {
        $admin->last_login_at = now()->toDateTimeString();
        $admin->save();
        $fresh = $admin->fresh();
        if ($fresh) {
            session()->put('admin', $fresh);
        }
    }

    private function redirectAfterLogin(User $admin): string
    {
        if ($admin->force_password_change) {
            return url('admin/security');
        }

        if ((int) $admin->user_level === 2) {
            return url('admin/certificate/due-list');
        }

        return url('admin/dashboard');
    }
}
