<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    function login(){
        return view('admin.login');
    }

    function verifyLogin(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if (!$validation->fails()) {
            $admin = User::where(['email' => $request->email])->first();
          
            if ($admin && Hash::check($request->password, $admin['password'])){
                $admin->last_login_at = date('Y-m-d H:i:s');
                $admin->save();

                session()->put('admin', $admin);

                $this->response['status'] = 1;
                $this->response['msg'] = "Login successful...";
                $this->response['redirect_url'] = url('admin/dashboard');
            } else {
                $this->response['error'] = "Invalid credentials!";
            }
        } else {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }

    
    
}
