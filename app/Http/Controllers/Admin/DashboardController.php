<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = 'Dashboard';
        $data['active_tab'] = 'dashboard';
        $data['studentCount'] = PortalUser::query()->where('role', 2)->count();
        $data['teacherCount'] = PortalUser::query()->where('role', 1)->count();
        $data['adminCount'] = User::query()->count();
        $shortcuts = [
            ['label' => 'Teachers', 'url' => url('admin/teacher'), 'icon' => 'tabler-users'],
            ['label' => 'Add teacher', 'url' => url('admin/teacher/form'), 'icon' => 'tabler-user-plus'],
            ['label' => 'Students', 'url' => url('admin/student'), 'icon' => 'tabler-school'],
            ['label' => 'Add student', 'url' => url('admin/student/add'), 'icon' => 'tabler-circle-plus'],
            ['label' => 'My profile', 'url' => url('admin/profile'), 'icon' => 'tabler-user'],
            ['label' => 'Security', 'url' => url('admin/security'), 'icon' => 'tabler-lock'],
        ];
        if ((int) data_get(session('admin'), 'user_level', 0) === 1) {
            array_unshift($shortcuts, ['label' => 'Administrators', 'url' => url('admin/admins'), 'icon' => 'tabler-users-group']);
        }
        $data['shortcuts'] = $shortcuts;

        return view('admin.dashboard', $data);
    }

    public function logout()
    {
        session()->forget('admin');

        return redirect('admin/login');
    }
}
