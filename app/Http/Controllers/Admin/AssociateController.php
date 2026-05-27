<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Associate;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssociateController extends Controller
{
    public function list(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $per_page = 10;
        $page = max(1, (int) $request->input('page', 1));

        $query = Associate::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('company_name', 'like', '%'.$q.'%')
                        ->orWhere('contact_person', 'like', '%'.$q.'%')
                        ->orWhere('contact_email', 'like', '%'.$q.'%')
                        ->orWhere('contact_mobile', 'like', '%'.$q.'%')
                        ->orWhere('city', 'like', '%'.$q.'%');
                });
            });

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('id')
            ->skip(($page - 1) * $per_page)
            ->take($per_page)
            ->get();

        $queryParams = $request->except('page');
        $pageUrl = '?'.(empty($queryParams) ? '' : http_build_query($queryParams).'&');

        $data = [
            'title' => 'Associate List',
            'active_tab' => 'associate',
            'sub_active_tab' => 'list',
            'rows' => $rows,
            'q' => $q,
            'pagination' => pagination($total, $per_page, $page, $pageUrl),
            'serial_start' => ($page - 1) * $per_page,
        ];

        return view('admin.associate.list', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Associate',
            'active_tab' => 'associate',
            'sub_active_tab' => 'add',
            'mode' => 'add',
            'details' => new Associate(),
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'preselectedStates' => collect(),
        ];

        return view('admin.associate.form', $data);
    }

    public function save(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255|unique:associates,contact_email',
            'contact_mobile' => 'required|string|max:30',
            'address' => 'nullable|string|max:1000',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city' => 'required|string|max:120',
            'pincode' => 'required|string|max:20',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        Associate::query()->create([
            'company_name' => $request->input('company_name'),
            'contact_person' => $request->input('contact_person'),
            'contact_email' => $request->input('contact_email'),
            'contact_mobile' => $request->input('contact_mobile'),
            'address' => $request->input('address'),
            'country_id' => $request->input('country_id'),
            'state_id' => $request->input('state_id'),
            'city' => $request->input('city'),
            'pincode' => $request->input('pincode'),
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Associate saved successfully.';
        $this->response['redirect_url'] = url('admin/associate/list');

        return response()->json($this->response);
    }

    public function edit($id)
    {
        $details = Associate::query()->findOrFail($id);

        $data = [
            'title' => 'Edit Associate',
            'active_tab' => 'associate',
            'sub_active_tab' => 'add',
            'mode' => 'edit',
            'details' => $details,
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'preselectedStates' => State::query()
                ->where('country_id', $details->country_id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];

        return view('admin.associate.form', $data);
    }

    public function update(Request $request, $id)
    {
        $associate = Associate::query()->find($id);
        if (! $associate) {
            $this->response['error'] = 'Associate not found.';

            return response()->json($this->response);
        }

        $validation = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255|unique:associates,contact_email,'.(int) $id,
            'contact_mobile' => 'required|string|max:30',
            'address' => 'nullable|string|max:1000',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city' => 'required|string|max:120',
            'pincode' => 'required|string|max:20',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $associate->update([
            'company_name' => $request->input('company_name'),
            'contact_person' => $request->input('contact_person'),
            'contact_email' => $request->input('contact_email'),
            'contact_mobile' => $request->input('contact_mobile'),
            'address' => $request->input('address'),
            'country_id' => $request->input('country_id'),
            'state_id' => $request->input('state_id'),
            'city' => $request->input('city'),
            'pincode' => $request->input('pincode'),
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Associate updated successfully.';
        $this->response['redirect_url'] = url('admin/associate/list');

        return response()->json($this->response);
    }

    public function view($id)
    {
        $details = Associate::query()
            ->with(['country:id,name', 'state:id,name'])
            ->findOrFail($id);

        $data = [
            'title' => 'View Associate',
            'active_tab' => 'associate',
            'sub_active_tab' => 'list',
            'details' => $details,
        ];

        return view('admin.associate.view', $data);
    }
}
