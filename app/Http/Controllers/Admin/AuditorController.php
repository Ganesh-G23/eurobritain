<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuditorController extends Controller
{
    public function list(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $per_page = 10;
        $page = max(1, (int) $request->input('page', 1));

        $query = User::query()
            ->where('user_level', 2)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%')
                        ->orWhere('phone', 'like', '%'.$q.'%');
                });
            });

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('id')
            ->skip(($page - 1) * $per_page)
            ->take($per_page)
            ->get(['id', 'name', 'email', 'phone', 'last_login_at', 'created_at']);

        $queryParams = $request->except('page');
        $pageUrl = '?'.(empty($queryParams) ? '' : http_build_query($queryParams).'&');

        return view('admin.auditor.list', [
            'title' => 'Auditor List',
            'active_tab' => 'auditor',
            'sub_active_tab' => 'list',
            'rows' => $rows,
            'q' => $q,
            'pagination' => pagination($total, $per_page, $page, $pageUrl),
            'serial_start' => ($page - 1) * $per_page,
        ]);
    }

    public function add()
    {
        return view('admin.auditor.form', [
            'title' => 'Add Auditor',
            'active_tab' => 'auditor',
            'sub_active_tab' => 'add',
            'mode' => 'add',
            'details' => new User(),
        ]);
    }

    public function save(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:6|max:255',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        User::query()->create([
            'user_level' => 2,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'password' => $request->input('password'),
            'p' => $request->input('password'),
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Auditor saved successfully.';
        $this->response['redirect_url'] = url('admin/auditor/list');

        return response()->json($this->response);
    }

    public function edit($id)
    {
        $details = User::query()
            ->where('user_level', 2)
            ->findOrFail($id);

        return view('admin.auditor.form', [
            'title' => 'Edit Auditor',
            'active_tab' => 'auditor',
            'sub_active_tab' => 'add',
            'mode' => 'edit',
            'details' => $details,
        ]);
    }

    public function view($id)
    {
        $details = User::query()
            ->where('user_level', 2)
            ->findOrFail($id);

        return view('admin.auditor.view', [
            'title' => 'View Auditor',
            'active_tab' => 'auditor',
            'sub_active_tab' => 'list',
            'details' => $details,
        ]);
    }

    public function update(Request $request, $id)
    {
        $auditor = User::query()
            ->where('user_level', 2)
            ->find($id);

        if (! $auditor) {
            $this->response['error'] = 'Auditor not found.';

            return response()->json($this->response);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.(int) $id,
            'phone' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:6|max:255',
        ];

        $validation = Validator::make($request->all(), $rules);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $payload = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ];

        if ($request->filled('password')) {
            $payload['password'] = $request->input('password');
            $payload['p'] = $request->input('password');
        }

        $auditor->update($payload);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Auditor updated successfully.';
        $this->response['redirect_url'] = url('admin/auditor/list');

        return response()->json($this->response);
    }
}
