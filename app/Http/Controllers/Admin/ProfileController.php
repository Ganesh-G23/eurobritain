<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function index()
    {
        $data = [];
        $data['title'] = 'My Profile';
        $data['active_tab'] = 'profile';
        $data['details'] = User::find((int) data_get(session('admin'), 'id'));

        return view('admin.profile', $data);
    }

    public function security()
    {
        $data = [];
        $data['title'] = 'Security';
        $data['active_tab'] = 'security';
        $data['details'] = User::find((int) data_get(session('admin'), 'id'));

        return view('admin.security', $data);
    }

    public function save_profile(Request $request)
    {
        $sessionId = (int) data_get(session('admin'), 'id', 0);
        if ($sessionId <= 0) {
            $this->response['error'] = 'Unauthorized';

            return response()->json($this->response);
        }

        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$sessionId,
            'phone' => 'required|string|max:50',
            'recovery_email' => 'nullable|email|max:255',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $user = User::query()->find($sessionId);
        if (! $user) {
            $this->response['error'] = 'User not found';

            return response()->json($this->response);
        }

        if ($user->email_two_factor_enabled && ! filled($request->input('recovery_email'))) {
            $this->response['error'] = 'Turn off email sign-in codes on Security before removing your recovery email.';

            return response()->json($this->response);
        }

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->phone = $request->input('phone');
        $user->recovery_email = $request->input('recovery_email') ?: null;
        $user->save();

        session()->put('admin', $user->fresh());

        $this->response['status'] = 1;
        $this->response['msg'] = 'Profile updated';
        $this->response['redirect_url'] = url('admin/profile');

        return response()->json($this->response);
    }

    public function save_change_password(Request $request)
    {
        $sessionId = (int) data_get(session('admin'), 'id', 0);
        if ($sessionId <= 0) {
            $this->response['error'] = 'Unauthorized';

            return response()->json($this->response);
        }

        $validation = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password_confirmation' => 'required|min:6',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $user = User::query()->find($sessionId);
        if (! $user) {
            $this->response['error'] = 'User not found';

            return response()->json($this->response);
        }

        $current = $request->input('current_password');
        $ok = Hash::check($current, (string) $user->password);
        if (! $ok && filled($user->p)) {
            $ok = $current === (string) $user->p;
        }

        if (! $ok) {
            $this->response['error'] = 'Current Password is Invalid';

            return response()->json($this->response);
        }

        $plain = $request->input('password');
        $user->password = $plain;
        $user->p = $plain;
        $user->force_password_change = false;
        $user->save();

        session()->put('admin', $user->fresh());

        $this->response['status'] = 1;
        $this->response['msg'] = 'Password changed';
        $this->response['redirect_url'] = url('admin/security');

        return response()->json($this->response);
    }

    public function saveEmailTwoFactor(Request $request)
    {
        $sessionId = (int) data_get(session('admin'), 'id', 0);
        if ($sessionId <= 0) {
            $this->response['error'] = 'Unauthorized';

            return response()->json($this->response);
        }

        $user = User::query()->find($sessionId);
        if (! $user) {
            $this->response['error'] = 'User not found';

            return response()->json($this->response);
        }

        $enabled = $request->boolean('email_two_factor_enabled');

        if ($enabled) {
            if ($user->force_password_change) {
                $this->response['error'] = 'Change your temporary password before enabling email sign-in codes.';

                return response()->json($this->response);
            }
            if (! filled($user->recovery_email)) {
                $this->response['error'] = 'Add a recovery email on your profile before enabling two-factor sign-in.';

                return response()->json($this->response);
            }
        }

        $user->email_two_factor_enabled = $enabled;
        $user->save();

        session()->put('admin', $user->fresh());

        $this->response['status'] = 1;
        $this->response['msg'] = $enabled ? 'Email sign-in codes enabled.' : 'Email sign-in codes disabled.';
        $this->response['redirect_url'] = url('admin/security');

        return response()->json($this->response);
    }
}
