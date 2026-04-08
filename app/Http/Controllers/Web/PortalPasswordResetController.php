<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class PortalPasswordResetController extends Controller
{
    public function showForgotPasswordForm()
    {
        return view('web.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ], [], [
            'email' => 'email',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $email = trim((string) $request->input('email'));
        $user = PortalUser::where('email', $email)->first();

        if (! $user || $user->email === null || $user->email === '') {
            $this->response['error'] = 'No account found with this email address.';

            return response()->json($this->response);
        }

        try {
            $status = Password::broker('portal_users')->sendResetLink(['email' => $email]);
        } catch (\Throwable $e) {
            Log::error('Portal password reset send failed: '.$e->getMessage());

            $this->response['error'] = 'Unable to send reset email. Please try again later.';

            return response()->json($this->response);
        }

        if ($status === Password::RESET_LINK_SENT) {
            $this->response['status'] = 1;
            $this->response['msg'] = 'We have sent a password reset link to your email.';

            return response()->json($this->response);
        }

        if ($status === Password::RESET_THROTTLED) {
            $this->response['error'] = 'Please wait before requesting another reset link.';

            return response()->json($this->response);
        }

        $this->response['error'] = 'Unable to send reset link. Please try again later.';

        return response()->json($this->response);
    }

    public function showResetForm(Request $request, string $token)
    {
        $email = $request->query('email');
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            abort(403, 'Invalid reset link.');
        }

        return view('web.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [], [
            'email' => 'email',
        ]);

        $status = Password::broker('portal_users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (PortalUser $user, string $password) {
                $user->password = Hash::make($password);
                $user->p = $password;
                $user->is_password_changed = 1;
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('web.login')->with('password_reset_ok', true);
        }

        $messages = [
            Password::INVALID_TOKEN => 'This reset link is invalid or has expired. Please request a new one.',
            Password::INVALID_USER => 'We could not find an account for this email.',
            Password::RESET_THROTTLED => 'Please wait before trying again.',
        ];

        return back()->withInput($request->only('email'))->withErrors([
            'email' => $messages[$status] ?? 'Unable to reset password. Please try again.',
        ]);
    }
}
