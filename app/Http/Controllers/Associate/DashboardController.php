<?php

namespace App\Http\Controllers\Associate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return view('associate.dashboard', [
            'title' => 'Dashboard',
            'active_tab' => 'dashboard',
            'associate' => session('associate'),
        ]);
    }
}
