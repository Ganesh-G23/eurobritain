<?php

namespace App\Http\Controllers;

use App\Services\SlideExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CommonController extends Controller
{
    function upload_files(Request $request)
    {
        $data = [];
        $destinationPath = 'uploads/temp';
        if ($request->file('files')) {
            foreach ($request->file('files') as $key => $file) {
                $path = $file->store($destinationPath);
                // $data[] = url('storage/app/' . $path);
                $data[] = ['fileName' => $file->hashName(), 'filePath' => url('storage/app/' . $path)];
            }

            $this->response['status'] = 1;
            $this->response['data'] = $data;
        }

        echo json_encode($this->response);
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

    public function upload_ppt(Request $request)
    {
        $this->response = [
            'status' => 0,
            'msg' => "",
            'error' => "",
            'error_array' => [],
            'data' => [],
        ];

        // Validate file upload
        $validator = Validator::make($request->all(), [
            'ppt' => 'required|file|mimes:ppt,pptx|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            $this->response['error'] = 'Invalid file. Please upload a PPT or PPTX file (max 5MB).';
            $this->response['error_array'] = $validator->errors()->toArray();
            echo json_encode($this->response);
            return;
        }

        try {
            $file = $request->file('ppt');
            $destinationPath = 'uploads/temp';

            // Store the PPT file
            $path = $file->store($destinationPath);
            $fileName = $file->hashName();
            $fullPath = Storage::path($path);

            // Extract slides as images
            $slideExtractionService = new SlideExtractionService();
            $slidesDir = storage_path('app/uploads/temp/slides_' . pathinfo($fileName, PATHINFO_FILENAME));
            $slides = $slideExtractionService->extractSlidesAsImages($fullPath, $slidesDir);

            // Prepare response data
            $data = [
                'fileName' => $fileName,
                'filePath' => url('storage/app/' . $path),
                'originalName' => $file->getClientOriginalName(),
                'slides' => []
            ];

            // Add slide images to response
            if (!empty($slides)) {
                foreach ($slides as $slide) {
                    // Get relative storage path
                    $relativePath = $slideExtractionService->getStoragePath($slide['path']);
                    $data['slides'][] = [
                        'slide_number' => $slide['slide_number'],
                        'image' => $slide['image'],
                        'path' => $relativePath,
                        'url' => url('storage/app/' . $relativePath)
                    ];
                }
            }

            $this->response['status'] = 1;
            $this->response['data'] = [$data];
            $this->response['msg'] = 'PPT uploaded successfully' . (!empty($slides) ? ' (' . count($slides) . ' slides extracted)' : '');
        } catch (\Exception $e) {
            $this->response['error'] = 'Error uploading file: ' . $e->getMessage();
            // Clean up uploaded file on error
            if (isset($path) && Storage::exists($path)) {
                Storage::delete($path);
            }
        }

        echo json_encode($this->response);
    }
}
