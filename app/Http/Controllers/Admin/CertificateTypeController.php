<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CertificateTypeController extends Controller
{
    private function typeOptions(): array
    {
        return [
            'iaf' => 'IAF (Registration)',
            'noiaf' => 'NOIAF (Compliance)',
        ];
    }

    private function categoryOptions(): array
    {
        return [
            'system_certificate' => 'System Certificate',
            'product_certificate' => 'Product Certificate',
        ];
    }

    public function list(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $per_page = 10;
        $page = max(1, (int) $request->input('page', 1));
        $type_options = $this->typeOptions();
        $category_options = $this->categoryOptions();

        $query = CertificateType::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', '%'.$q.'%')
                        ->orWhere('code', 'like', '%'.$q.'%')
                        ->orWhere('prefix', 'like', '%'.$q.'%')
                        ->orWhere('description', 'like', '%'.$q.'%');
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
            'title' => 'Certificate Type List',
            'active_tab' => 'certificate_type',
            'sub_active_tab' => 'list',
            'rows' => $rows,
            'q' => $q,
            'type_options' => $type_options,
            'category_options' => $category_options,
            'pagination' => pagination($total, $per_page, $page, $pageUrl),
            'serial_start' => ($page - 1) * $per_page,
        ];

        return view('admin.certificate_type.list', $data);
    }

    public function add()
    {
        $data = [
            'title' => 'Add Certificate Type',
            'active_tab' => 'certificate_type',
            'sub_active_tab' => 'add',
            'mode' => 'add',
            'details' => new CertificateType(),
            'type_options' => $this->typeOptions(),
            'category_options' => $this->categoryOptions(),
        ];

        return view('admin.certificate_type.form', $data);
    }

    public function save(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'types' => 'required|array|min:1',
            'types.*' => 'required|in:iaf,noiaf',
            'category' => 'required|in:system_certificate,product_certificate',
            'code' => 'required|string|max:50',
            'prefix' => 'required|string|max:50',
            'description' => 'nullable|string|max:2000',
            'audit_period' => 'required|integer|min:1|max:50',
            'renewal_period' => 'required|integer|min:1|max:50',
            'price' => 'required|numeric|min:0',
            'certificate_template' => 'nullable|string|max:255',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        CertificateType::query()->create([
            'name' => $request->input('name'),
            'types' => array_values($request->input('types', [])),
            'category' => $request->input('category'),
            'code' => $request->input('code'),
            'prefix' => $request->input('prefix'),
            'description' => $request->input('description'),
            'audit_period' => $request->input('audit_period'),
            'renewal_period' => $request->input('renewal_period'),
            'price' => $request->input('price'),
            'certificate_template' => $request->input('certificate_template'),
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Certificate type saved successfully.';
        $this->response['redirect_url'] = url('admin/certificate-type/list');

        return response()->json($this->response);
    }

    public function edit($id)
    {
        $details = CertificateType::query()->findOrFail($id);

        $data = [
            'title' => 'Edit Certificate Type',
            'active_tab' => 'certificate_type',
            'sub_active_tab' => 'add',
            'mode' => 'edit',
            'details' => $details,
            'type_options' => $this->typeOptions(),
            'category_options' => $this->categoryOptions(),
        ];

        return view('admin.certificate_type.form', $data);
    }

    public function update(Request $request, $id)
    {
        $certificateType = CertificateType::query()->find($id);
        if (! $certificateType) {
            $this->response['error'] = 'Certificate type not found.';

            return response()->json($this->response);
        }

        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'types' => 'required|array|min:1',
            'types.*' => 'required|in:iaf,noiaf',
            'category' => 'required|in:system_certificate,product_certificate',
            'code' => 'required|string|max:50',
            'prefix' => 'required|string|max:50',
            'description' => 'nullable|string|max:2000',
            'audit_period' => 'required|integer|min:1|max:50',
            'renewal_period' => 'required|integer|min:1|max:50',
            'price' => 'required|numeric|min:0',
            'certificate_template' => 'nullable|string|max:255',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $certificateType->update([
            'name' => $request->input('name'),
            'types' => array_values($request->input('types', [])),
            'category' => $request->input('category'),
            'code' => $request->input('code'),
            'prefix' => $request->input('prefix'),
            'description' => $request->input('description'),
            'audit_period' => $request->input('audit_period'),
            'renewal_period' => $request->input('renewal_period'),
            'price' => $request->input('price'),
            'certificate_template' => $request->input('certificate_template'),
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Certificate type updated successfully.';
        $this->response['redirect_url'] = url('admin/certificate-type/list');

        return response()->json($this->response);
    }

    public function view($id)
    {
        $details = CertificateType::query()->findOrFail($id);

        $data = [
            'title' => 'View Certificate Type',
            'active_tab' => 'certificate_type',
            'sub_active_tab' => 'list',
            'details' => $details,
            'type_options' => $this->typeOptions(),
            'category_options' => $this->categoryOptions(),
        ];

        return view('admin.certificate_type.view', $data);
    }

    public function templateCoords($id)
    {
        $details = CertificateType::query()->findOrFail($id);

        if (empty($details->certificate_template)) {
            return redirect(url('admin/certificate-type/edit/'.$details->id))
                ->with('error', 'Upload a certificate template image first.');
        }

        $extension = strtolower(pathinfo($details->certificate_template, PATHINFO_EXTENSION));
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);

        if (! $isImage) {
            return redirect(url('admin/certificate-type/edit/'.$details->id))
                ->with('error', 'Template field positions can only be configured for image templates (PNG / JPG).');
        }

        return view('admin.certificate_type.template_coords', [
            'title' => 'Configure Template Fields — '.$details->name,
            'active_tab' => 'certificate_type',
            'sub_active_tab' => 'list',
            'details' => $details,
        ]);
    }

    public function saveTemplateCoords(Request $request, $id)
    {
        $certificateType = CertificateType::query()->find($id);
        if (! $certificateType) {
            $this->response['error'] = 'Certificate type not found.';

            return response()->json($this->response);
        }

        $validation = Validator::make($request->all(), [
            'template_coords' => 'nullable|string',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $templateCoords = $this->parseTemplateCoords($request->input('template_coords'));
        if ($templateCoords === false) {
            $this->response['error'] = 'Invalid template field positions.';

            return response()->json($this->response);
        }

        $certificateType->update([
            'template_coords' => $templateCoords,
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Template field positions saved.';
        $this->response['redirect_url'] = url('admin/certificate-type/list');

        return response()->json($this->response);
    }

    /**
     * @return array<string, array<string, mixed>>|null|false
     */
    private function parseTemplateCoords(?string $raw): array|null|false
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return false;
        }

        $allowedAlign = ['left', 'center', 'right'];
        $allowedValign = ['top', 'middle', 'bottom'];
        $normalized = [];

        foreach ($decoded as $field => $config) {
            if (! is_array($config)) {
                return false;
            }

            if (! isset($config['x'], $config['y']) || ! is_numeric($config['x']) || ! is_numeric($config['y'])) {
                return false;
            }

            $entry = [
                'x' => (int) $config['x'],
                'y' => (int) $config['y'],
            ];

            if (isset($config['w']) && is_numeric($config['w'])) {
                $entry['w'] = max(20, (int) $config['w']);
            }

            if (isset($config['h']) && is_numeric($config['h'])) {
                $entry['h'] = max(12, (int) $config['h']);
            }

            if (isset($config['size']) && is_numeric($config['size'])) {
                $entry['size'] = max(6, (int) $config['size']);
            }

            if (isset($config['align']) && in_array($config['align'], $allowedAlign, true)) {
                $entry['align'] = $config['align'];
            }

            if (isset($config['valign']) && in_array($config['valign'], $allowedValign, true)) {
                $entry['valign'] = $config['valign'];
            }

            if (isset($config['color']) && is_string($config['color']) && preg_match('/^#[0-9A-Fa-f]{3,6}$/', $config['color'])) {
                $entry['color'] = $config['color'];
            }

            if (isset($config['bold'])) {
                $entry['bold'] = filter_var($config['bold'], FILTER_VALIDATE_BOOLEAN);
            }

            $normalized[(string) $field] = $entry;
        }

        return $normalized;
    }
}
