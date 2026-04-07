<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    function index(Request $request)
    {
        $data['title'] = 'Dashboard';
        $data['active_tab'] = 'dashboard';

        return view('admin.dashboard', $data);
    }

    function logout()
    {
        session()->forget('admin');
        return redirect('admin/login');
    }
}
