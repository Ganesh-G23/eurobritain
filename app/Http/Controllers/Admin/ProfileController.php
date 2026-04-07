<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    function index()
    {
        $data = [];
        $data['title'] = 'My Profile';
        $data['active_tab'] = 'profile';
        $data['details'] = User::find(session('admin')['id']);
        return view('admin.profile', $data);
    }

    function security()
    {
        $data = [];
        $data['title'] = 'Security';
        $data['active_tab'] = 'security';
        return view('admin.security', $data);
    }

    function save_profile(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
        ]);
        if (!$validation->fails()) {
            $user = new User();
            if ($request->id) {
                $user = User::find($request->id);
            }
           
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->save();
            

            $this->response['status'] = 1;
            $this->response['msg'] = 'Profile updated';
            $this->response['redirect_url'] = url('admin/profile');
        } else {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }

    function save_change_password(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'current_password' => 'required',
            'password_confirmation' => 'required|min:6',
            'password' => 'required|confirmed|min:6',
        ]);

        if (!$validation->fails()) {
            $user = User::find(session('admin')['id']);
            $chk = $user->where('id', session('admin')['id'])->where('p', $request->current_password)->count();
            if ($chk == 1) {
                $user->password = bcrypt($request->password);
                $user->p = $request->password;
                $user->save();

                $this->response['status'] = 1;
                $this->response['msg'] = 'Password changed';
                $this->response['redirect_url'] = url('admin/security');
            } else {
                $this->response['error'] = 'Current Password is Invalid';
            }
        } else {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }
}
