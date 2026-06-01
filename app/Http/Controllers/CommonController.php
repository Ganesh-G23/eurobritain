<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CommonController extends Controller
{
    function upload_files(Request $request)
    {
        $data = [];
        $destinationPath = 'uploads/temp';

        if (! $request->hasFile('files')) {
            $this->response['error'] = 'No file selected.';

            return response()->json($this->response);
        }

        foreach ($request->file('files') as $file) {
            if (! $file->isValid()) {
                $this->response['error'] = 'Uploaded file is invalid.';

                return response()->json($this->response);
            }

            $path = $file->store($destinationPath);
            $data[] = ['fileName' => $file->hashName(), 'filePath' => url('storage/app/' . $path)];
        }

        $this->response['status'] = 1;
        $this->response['data'] = $data;

        return response()->json($this->response);
    }

    public function clientsByAssociate(Request $request)
    {
        $associateId = (int) $request->input('associate_id', 0);
        $query = Client::query()->orderBy('company_name');

        if ($associateId > 0) {
            $query->where('associate_id', $associateId);
        }

        return response()->json($query->get(['id', 'company_name']));
    }

    public function getStates(Request $request)
    {
        $countryId = (int) $request->input('country_id', 0);
        if ($countryId <= 0) {
            return response()->json([]);
        }

        $states = State::query()
            ->where('country_id', $countryId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($states);
    }

    public function uploadCkeditorImage(Request $request)
    {
        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/uploads/ckeditor', $filename);

            $url = asset('storage/uploads/ckeditor/' . $filename);

            return response()->json([
                'uploaded' => 1,
                'fileName' => $filename,
                'url' => $url
            ]);
        }
        return response()->json([
            'uploaded' => 0,
            'error' => ['message' => 'No file uploaded.']
        ]);
    }

}
