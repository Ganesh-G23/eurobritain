<?php

namespace App\Http\Controllers\Associate;

use App\Http\Controllers\Controller;
use App\Models\Associate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    private const OTP_TTL_SECONDS = 600;

    public function login()
    {
        return view('associate.login');
    }

    public function sendOtp(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $email = trim((string) $request->input('email'));
        $rateKey = 'associate-login-send:'.sha1(strtolower($email).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->response['error'] = 'Too many attempts. Please try again later.';

            return response()->json($this->response);
        }

        $associate = Associate::query()
            ->where('contact_email', $email)
            ->first();

        if (! $associate) {
            RateLimiter::hit($rateKey, 60);
            $this->response['error'] = 'Email not registered.';

            return response()->json($this->response);
        }

        $otp = (string) random_int(100000, 999999);
        $pendingToken = Str::random(64);
        $cacheKey = 'associate_login_otp:'.$pendingToken;
        Cache::put($cacheKey, [
            'associate_id' => (int) $associate->id,
            'otp_hash' => hash('sha256', $otp),
        ], self::OTP_TTL_SECONDS);

        try {
            Mail::raw(
                'Your login OTP is '.$otp.'. It is valid for 10 minutes.',
                function ($message) use ($email): void {
                    $message->to($email)->subject(config('app.name').' Associate Login OTP');
                }
            );
        } catch (Throwable $e) {
            Cache::forget($cacheKey);
            $this->response['error'] = 'Unable to send OTP. Please try again.';

            return response()->json($this->response);
        }

        RateLimiter::hit($rateKey, 60);
        $this->response['status'] = 1;
        $this->response['msg'] = 'OTP sent successfully to your email.';
        $this->response['pending_token'] = $pendingToken;

        return response()->json($this->response);
    }

    public function verifyOtp(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'pending_token' => 'required|string|size:64',
            'otp' => ['required', 'regex:/^[0-9]{6}$/'],
        ]);

        if ($validation->fails()) {
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        $pendingToken = (string) $request->input('pending_token');
        $otp = (string) $request->input('otp');
        $rateKey = 'associate-login-verify:'.sha1($pendingToken.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->response['error'] = 'Too many attempts. Please sign in again.';

            return response()->json($this->response);
        }

        $cacheKey = 'associate_login_otp:'.$pendingToken;
        $pending = Cache::get($cacheKey);
        if (! is_array($pending) || empty($pending['associate_id']) || empty($pending['otp_hash'])) {
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        if (! hash_equals((string) $pending['otp_hash'], hash('sha256', $otp))) {
            RateLimiter::hit($rateKey, self::OTP_TTL_SECONDS);
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        $associate = Associate::query()->find((int) $pending['associate_id']);
        if (! $associate) {
            Cache::forget($cacheKey);
            $this->response['error'] = 'Invalid or expired code.';

            return response()->json($this->response);
        }

        Cache::forget($cacheKey);
        RateLimiter::clear($rateKey);

        session()->put('associate', $associate->fresh() ?? $associate);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Login successful...';
        $this->response['redirect_url'] = url('dashboard');

        return response()->json($this->response);
    }

    public function logout()
    {
        session()->forget('associate');

        return redirect('login');
    }
}
