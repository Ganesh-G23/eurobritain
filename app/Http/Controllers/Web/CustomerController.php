<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\PortalRememberFromCookie;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function login(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => 'required|in:teacher,student,parent',
            'remember' => 'nullable|in:0,1',
            'terms' => 'required|accepted',
        ]);

        

        if (!$validation->fails()) {
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
                $validPassword = Hash::check($request->password, (string)$user->password);
                if (!$validPassword && !empty($user->p)) {
                    $validPassword = $request->password === (string)$user->p;
                }
            }

            if ($user && $validPassword) {
                if (!Hash::check($request->password, (string)$user->password)) {
                    $user->password = Hash::make($request->password);
                    $user->p = $request->password;
                }

                if ($request->has('remember')) {
                    $token = Str::random(60);
                    $user->remember_token = $token;
                    cookie()->queue(PortalRememberFromCookie::COOKIE_NAME, $token, 43200);
                } else {
                    $user->remember_token = null;
                    Cookie::queue(Cookie::forget(PortalRememberFromCookie::COOKIE_NAME));
                }

                
                $user->save();
                $user = $user->fresh();
                
                session()->forget('admin');
                session()->put('portal_user', $user->toArray());
                if ((int)($user->role ?? 0) === 1) {
                    session()->put('teacher', $user->toArray());
                } else {
                    session()->forget('teacher');
                }
                session()->put('show_teacher_password_popup', (int)($user->is_password_changed ?? 0) === 0 ? 1 : 0);

                $this->response['status'] = 1;
                $this->response['msg'] = "Login successful...";
                $this->response['redirect_url'] = url('user');
            } else {
                $this->response['error'] = "Invalid credentials!";
            }
        } else {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        return response()->json($this->response);
    }
}
