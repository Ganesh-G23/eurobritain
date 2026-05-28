<?php

namespace App\Http\Controllers\Associate;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function list(Request $request)
    {
        $associateId = (int) data_get(session('associate'), 'id', 0);
        $q = trim((string) $request->input('q', ''));
        $per_page = 10;
        $page = max(1, (int) $request->input('page', 1));

        $query = Client::query()
            ->where('associate_id', $associateId)
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

        return view('associate.client.list', [
            'title' => 'Client List',
            'active_tab' => 'client',
            'sub_active_tab' => 'list',
            'rows' => $rows,
            'q' => $q,
            'pagination' => pagination($total, $per_page, $page, $pageUrl),
            'serial_start' => ($page - 1) * $per_page,
        ]);
    }

    public function add()
    {
        return view('associate.client.form', [
            'title' => 'Add Client',
            'active_tab' => 'client',
            'sub_active_tab' => 'add',
            'mode' => 'add',
            'details' => new Client(),
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'preselectedStates' => collect(),
        ]);
    }

    public function save(Request $request)
    {
        $associateId = (int) data_get(session('associate'), 'id', 0);
        if ($associateId <= 0) {
            $this->response['error'] = 'Unauthorized request.';

            return response()->json($this->response);
        }

        $validation = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255|unique:clients,contact_email',
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

        Client::query()->create([
            'associate_id' => $associateId,
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
        $this->response['msg'] = 'Client saved successfully.';
        $this->response['redirect_url'] = url('client/list');

        return response()->json($this->response);
    }

    public function edit($id)
    {
        $associateId = (int) data_get(session('associate'), 'id', 0);
        $details = Client::query()
            ->where('associate_id', $associateId)
            ->findOrFail($id);

        return view('associate.client.form', [
            'title' => 'Edit Client',
            'active_tab' => 'client',
            'sub_active_tab' => 'add',
            'mode' => 'edit',
            'details' => $details,
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'preselectedStates' => State::query()
                ->where('country_id', $details->country_id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, $id)
    {
        $associateId = (int) data_get(session('associate'), 'id', 0);
        $client = Client::query()
            ->where('associate_id', $associateId)
            ->find($id);

        if (! $client) {
            $this->response['error'] = 'Client not found.';

            return response()->json($this->response);
        }

        $validation = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255|unique:clients,contact_email,'.(int) $id,
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

        $client->update([
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
        $this->response['msg'] = 'Client updated successfully.';
        $this->response['redirect_url'] = url('client/list');

        return response()->json($this->response);
    }

    public function view($id)
    {
        $associateId = (int) data_get(session('associate'), 'id', 0);
        $details = Client::query()
            ->where('associate_id', $associateId)
            ->with(['country:id,name', 'state:id,name'])
            ->findOrFail($id);

        return view('associate.client.view', [
            'title' => 'View Client',
            'active_tab' => 'client',
            'sub_active_tab' => 'list',
            'details' => $details,
        ]);
    }
}
