<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Associate;
use App\Models\AuditType;
use App\Models\CertificateApplication;
use App\Models\CertificateType;
use App\Models\Client;
use App\Models\ClientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CertificateApplicationController extends Controller
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

    public function add()
    {
        $data = [
            'title' => 'Certification Applications',
            'active_tab' => 'certificate_application',
            'sub_active_tab' => 'add',
            'associates' => Associate::query()->orderBy('company_name')->get(['id', 'company_name']),
            'typeOptions' => $this->typeOptions(),
            'categoryOptions' => $this->categoryOptions(),
        ];

        return view('admin.certificate_application.add', $data);
    }

    public function getCertificateTypes(Request $request)
    {
        $associateId = (int) $request->input('associate_id', 0);
        $type = trim((string) $request->input('type', ''));
        $category = trim((string) $request->input('category', ''));

        if ($associateId <= 0 || $type === '' || $category === '') {
            return response()->json([]);
        }

        $allowedTypes = array_keys($this->typeOptions());
        $allowedCategories = array_keys($this->categoryOptions());

        if (! in_array($type, $allowedTypes, true) || ! in_array($category, $allowedCategories, true)) {
            return response()->json([]);
        }

        $types = CertificateType::query()
            ->whereHas('associates', fn ($q) => $q->where('associates.id', $associateId))
            ->where('category', $category)
            ->whereJsonContains('types', $type)
            ->orderBy('description')
            ->get(['id', 'description']);

        return response()->json($types);
    }

    public function list(Request $request)
    {
        $clientId = (int) $request->input('client_id', 0);
        $email = trim((string) $request->input('email', ''));
        $phoneNumber = trim((string) $request->input('phone_number', ''));
        $per_page = 10;
        $page = max(1, (int) $request->input('page', 1));

        $query = CertificateApplication::query()
            ->with([
                'client:id,company_name,contact_email,contact_mobile,address',
                'certificateType:id,description',
            ])
            ->when($clientId > 0, function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->when($email !== '', function ($query) use ($email) {
                $query->where('contact_email', 'like', '%'.$email.'%');
            })
            ->when($phoneNumber !== '', function ($query) use ($phoneNumber) {
                $query->where('contact_mobile', 'like', '%'.$phoneNumber.'%');
            });

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('id')
            ->skip(($page - 1) * $per_page)
            ->take($per_page)
            ->get();

        $queryParams = $request->except('page');
        $pageUrl = '?'.(empty($queryParams) ? '' : http_build_query($queryParams).'&');

        $data = [
            'title' => 'Certification Applications List',
            'active_tab' => 'certificate_application',
            'sub_active_tab' => 'list',
            'rows' => $rows,
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
            'client_id' => $clientId,
            'email' => $email,
            'phone_number' => $phoneNumber,
            'pagination' => pagination($total, $per_page, $page, $pageUrl),
            'serial_start' => ($page - 1) * $per_page,
        ];

        return view('admin.certificate_application.list', $data);
    }

    public function getClients(Request $request)
    {
        $associateId = (int) $request->input('associate_id', 0);
        if ($associateId <= 0) {
            return response()->json([]);
        }

        $clients = Client::query()
            ->where('associate_id', $associateId)
            ->orderBy('company_name')
            ->get(['id', 'company_name']);

        return response()->json($clients);
    }

    public function checkClient(Request $request)
    {
        $clientId = (int) $request->input('client_id', 0);
        if ($clientId <= 0) {
            return response()->json(['error' => 'Invalid client.'], 422);
        }

        $client = Client::query()->find($clientId);
        if (! $client) {
            return response()->json(['error' => 'Client not found.'], 404);
        }

        $hasDocuments = ClientDocument::query()
            ->where('client_id', $clientId)
            ->exists();

        return response()->json([
            'is_new' => ! $hasDocuments,
            'company_name' => $client->company_name,
        ]);
    }

    public function saveDocuments(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'legal_proof' => 'required|string|max:255',
            'pan' => 'required|string|max:255',
            'purchase_bills' => 'required|string|max:255',
            'msme_udyog_aadhar' => 'nullable|string|max:255',
            'gstn' => 'nullable|string|max:255',
            'factory_registration' => 'nullable|string|max:255',
            'sales_bills' => 'nullable|string|max:255',
            'staff_biodata' => 'nullable|string|max:255',
            'electricity_bill' => 'nullable|string|max:255',
            'product_inspection' => 'nullable|string|max:255',
            'employee_competence_matrix' => 'nullable|string|max:255',
            'previous_iso_ce_certificate' => 'nullable|string|max:255',
            'suppliers_list' => 'nullable|string|max:255',
            'product_catalogue_brochure' => 'nullable|string|max:255',
            'pollution_clearance_certificate' => 'nullable|string|max:255',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $clientId = (int) $request->input('client_id');

        if (ClientDocument::query()->where('client_id', $clientId)->exists()) {
            $this->response['error'] = 'Documents already exist for this client.';

            return response()->json($this->response);
        }

        ClientDocument::query()->create([
            'client_id' => $clientId,
            'legal_proof' => $request->input('legal_proof'),
            'pan' => $request->input('pan'),
            'msme_udyog_aadhar' => $request->input('msme_udyog_aadhar'),
            'gstn' => $request->input('gstn'),
            'factory_registration' => $request->input('factory_registration'),
            'purchase_bills' => $request->input('purchase_bills'),
            'sales_bills' => $request->input('sales_bills'),
            'staff_biodata' => $request->input('staff_biodata'),
            'electricity_bill' => $request->input('electricity_bill'),
            'product_inspection' => $request->input('product_inspection'),
            'employee_competence_matrix' => $request->input('employee_competence_matrix'),
            'previous_iso_ce_certificate' => $request->input('previous_iso_ce_certificate'),
            'suppliers_list' => $request->input('suppliers_list'),
            'product_catalogue_brochure' => $request->input('product_catalogue_brochure'),
            'pollution_clearance_certificate' => $request->input('pollution_clearance_certificate'),
        ]);

        $certificateTypeId = (int) $request->input('certificate_type_id');

        $this->response['status'] = 1;
        $this->response['msg'] = 'Documents saved successfully.';
        $this->response['redirect_url'] = url('admin/certificate-application/form?client_id='.$clientId.'&certificate_type_id='.$certificateTypeId);

        return response()->json($this->response);
    }

    public function resolveOldClient(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'certificate_type_id' => 'required|exists:certificate_types,id',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $clientId = (int) $request->input('client_id');

        if (! ClientDocument::query()->where('client_id', $clientId)->exists()) {
            $this->response['error'] = 'No documents found for this client. Please upload documents first.';

            return response()->json($this->response);
        }

        $certificateTypeId = (int) $request->input('certificate_type_id');

        $this->response['status'] = 1;
        $this->response['msg'] = 'Proceeding to certificate application.';
        $this->response['redirect_url'] = url('admin/certificate-application/form?client_id='.$clientId.'&certificate_type_id='.$certificateTypeId);

        return response()->json($this->response);
    }

    public function applicationForm(Request $request)
    {
        $clientId = (int) $request->input('client_id', 0);
        $certificateTypeId = (int) $request->input('certificate_type_id', 0);

        if ($clientId <= 0 || $certificateTypeId <= 0) {
            abort(404);
        }

        $client = Client::query()->findOrFail($clientId);
        $certificateType = CertificateType::query()->findOrFail($certificateTypeId);

        $data = [
            'title' => 'Add / Edit Certification Applications',
            'active_tab' => 'certificate_application',
            'sub_active_tab' => 'add',
            'mode' => 'add',
            'details' => new CertificateApplication(),
            'client' => $client,
            'certificateType' => $certificateType,
            'client_id' => $clientId,
            'certificate_type_id' => $certificateTypeId,
            'auditTypes' => AuditType::query()->orderBy('id')->get(['id', 'name']),
        ];

        return view('admin.certificate_application.form', $data);
    }

    public function saveApplication(Request $request)
    {
        $validation = $this->applicationValidationRules($request);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $client = Client::query()->findOrFail((int) $request->input('client_id'));

        $application = CertificateApplication::query()->create(
            $this->buildApplicationPayload($request, $client)
        );

        $application->update([
            'application_number' => 'APP-'.str_pad($application->id, 4, '0', STR_PAD_LEFT),
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Application saved successfully.';
        $this->response['redirect_url'] = url('admin/certificate-application/list');

        return response()->json($this->response);
    }

    public function edit($id)
    {
        $details = CertificateApplication::query()
            ->with(['client', 'certificateType:id,description'])
            ->findOrFail($id);

        $data = [
            'title' => 'Add / Edit Certification Applications',
            'active_tab' => 'certificate_application',
            'sub_active_tab' => 'list',
            'mode' => 'edit',
            'details' => $details,
            'client' => $details->client,
            'certificateType' => $details->certificateType,
            'client_id' => $details->client_id,
            'certificate_type_id' => $details->certificate_type_id,
            'auditTypes' => AuditType::query()->orderBy('id')->get(['id', 'name']),
        ];

        return view('admin.certificate_application.form', $data);
    }

    public function update(Request $request, $id)
    {
        $application = CertificateApplication::query()->find($id);
        if (! $application) {
            $this->response['error'] = 'Application not found.';

            return response()->json($this->response);
        }

        $validation = $this->applicationValidationRules($request);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $client = Client::query()->findOrFail((int) $request->input('client_id'));

        $application->update(
            $this->buildApplicationPayload($request, $client)
        );

        $this->response['status'] = 1;
        $this->response['msg'] = 'Application updated successfully.';
        $this->response['redirect_url'] = url('admin/certificate-application/list');

        return response()->json($this->response);
    }

    public function view($id)
    {
        $details = CertificateApplication::query()
            ->with(['client', 'certificateType:id,description,code'])
            ->findOrFail($id);

        $auditTypeNames = [];
        if (! empty($details->service_request_audit_type)) {
            $auditTypeNames = AuditType::query()
                ->whereIn('id', $details->service_request_audit_type)
                ->orderBy('id')
                ->pluck('name')
                ->all();
        }

        $data = [
            'title' => 'View Certification Application',
            'active_tab' => 'certificate_application',
            'sub_active_tab' => 'list',
            'details' => $details,
            'auditTypeNames' => $auditTypeNames,
        ];

        return view('admin.certificate_application.view', $data);
    }

    public function pdf($id)
    {
        $details = CertificateApplication::query()
            ->with(['certificateType:id,description,code'])
            ->findOrFail($id);

        $auditTypeNames = [];
        if (! empty($details->service_request_audit_type)) {
            $auditTypeNames = AuditType::query()
                ->whereIn('id', $details->service_request_audit_type)
                ->orderBy('id')
                ->pluck('name')
                ->all();
        }

        $logoPath = public_path('admin_theme/assets/img/logo.png');
        if (! file_exists($logoPath)) {
            $logoPath = '';
        }

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $html = view('admin.certificate_application.pdf', [
            'details' => $details,
            'auditTypeNames' => $auditTypeNames,
            'logoPath' => $logoPath,
        ])->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 14,
            'tempDir' => $tempDir,
        ]);

        $pdfTitle = trim('Certification Application Form'.($details->company_name ? ' - '.$details->company_name : ''));
        $mpdf->SetTitle($pdfTitle);
        $mpdf->SetAuthor('Eurobritain Certifications Limited');
        $mpdf->SetCreator('Eurobritain Certifications Limited');

        $mpdf->WriteHTML($html);

        $safeCompany = $details->company_name
            ? preg_replace('/[^A-Za-z0-9_-]+/', '_', $details->company_name)
            : 'application';
        $filename = $safeCompany.'-certification-application.pdf';

        return response(
            $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    public function documents($id)
    {
        $application = CertificateApplication::query()->findOrFail($id);
        $clientDocument = ClientDocument::query()
            ->where('client_id', $application->client_id)
            ->first();

        $data = [
            'title' => 'Client Documents',
            'active_tab' => 'certificate_application',
            'sub_active_tab' => 'list',
            'application' => $application,
            'clientDocument' => $clientDocument,
        ];

        return view('admin.certificate_application.documents', $data);
    }

    public function uploadDocument($id)
    {
        $application = CertificateApplication::query()->findOrFail($id);

        $data = [
            'title' => 'Upload Application Document',
            'active_tab' => 'certificate_application',
            'sub_active_tab' => 'list',
            'application' => $application,
        ];

        return view('admin.certificate_application.upload_document', $data);
    }

    public function saveDocument(Request $request, $id)
    {
        $application = CertificateApplication::query()->find($id);
        if (! $application) {
            $this->response['error'] = 'Application not found.';

            return response()->json($this->response);
        }

        $validation = Validator::make($request->all(), [
            'application_document' => 'required|string|max:255',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $application->update([
            'application_document' => $request->input('application_document'),
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Document uploaded successfully.';
        $this->response['redirect_url'] = url('admin/certificate-application/list');

        return response()->json($this->response);
    }

    protected function applicationValidationRules(Request $request)
    {
        return Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'scope' => 'required|string|max:5000',
            'communication_person' => 'required|string|max:255',
            'fax_number' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'management_representative' => 'nullable|string|max:255',
            'top_manager' => 'nullable|string|max:255',
            'top_management_mobile' => 'nullable|string|max:30',
            'subcontractor' => 'nullable|string|max:255',
            'in_main_process' => 'nullable|string|max:255',
            'executive_personnel' => 'nullable|string|max:255',
            'in_design' => 'nullable|string|max:255',
            'director_details' => 'required|array',
            'director_details.first_name' => 'required|string|max:255',
            'director_details.middle_name' => 'nullable|string|max:255',
            'director_details.last_name' => 'nullable|string|max:255',
            'employee_details' => 'nullable|array',
            'employee_details.employee_number' => 'nullable|string|max:255',
            'employee_details.full_time' => 'nullable|string|max:255',
            'employee_details.part_time' => 'nullable|string|max:255',
            'address_shift_details' => 'required|array|min:1',
            'address_shift_details.*.address' => 'required|string|max:2000',
            'address_shift_details.*.shifts' => 'required|array|min:1',
            'address_shift_details.*.shifts.*.from' => 'required|string|max:20',
            'address_shift_details.*.shifts.*.to' => 'required|string|max:20',
            'service_request_audit_type' => 'required|array|min:1',
            'service_request_audit_type.*' => 'integer|exists:audit_types,id',
            'trademark_name' => 'nullable|string|max:255',
            'trademark_application_number' => 'nullable|string|max:255',
            'trademark_image' => 'nullable|string|max:255',
        ]);
    }

    protected function buildApplicationPayload(Request $request, Client $client): array
    {
        $auditTypeIds = array_values(array_map('intval', (array) $request->input('service_request_audit_type', [])));

        $addressShiftDetails = collect($request->input('address_shift_details', []))
            ->map(function ($item) {
                $shifts = collect($item['shifts'] ?? [])
                    ->map(fn ($shift) => [
                        'from' => $shift['from'] ?? '',
                        'to' => $shift['to'] ?? '',
                    ])
                    ->filter(fn ($shift) => $shift['from'] !== '' || $shift['to'] !== '')
                    ->values()
                    ->all();

                return [
                    'address' => $item['address'] ?? '',
                    'shifts' => $shifts,
                ];
            })
            ->filter(fn ($item) => $item['address'] !== '')
            ->values()
            ->all();

        return [
            'client_id' => (int) $request->input('client_id'),
            'certificate_type_id' => (int) $request->input('certificate_type_id'),
            'company_name' => $client->company_name,
            'address' => $client->address,
            'contact_mobile' => $client->contact_mobile,
            'contact_email' => $client->contact_email,
            'scope' => $request->input('scope'),
            'fax_number' => $request->input('fax_number'),
            'website' => $request->input('website'),
            'communication_person' => $request->input('communication_person'),
            'management_representative' => $request->input('management_representative'),
            'top_manager' => $request->input('top_manager'),
            'top_management_mobile' => $request->input('top_management_mobile'),
            'director_details' => [
                'first_name' => data_get($request->input('director_details'), 'first_name', ''),
                'middle_name' => data_get($request->input('director_details'), 'middle_name', ''),
                'last_name' => data_get($request->input('director_details'), 'last_name', ''),
            ],
            'employee_details' => [
                'employee_number' => data_get($request->input('employee_details'), 'employee_number', ''),
                'full_time' => data_get($request->input('employee_details'), 'full_time', ''),
                'part_time' => data_get($request->input('employee_details'), 'part_time', ''),
            ],
            'address_shift_details' => $addressShiftDetails,
            'subcontractor' => $request->input('subcontractor'),
            'in_main_process' => $request->input('in_main_process'),
            'executive_personnel' => $request->input('executive_personnel'),
            'in_design' => $request->input('in_design'),
            'service_request_audit_type' => $auditTypeIds,
            'trademark_name' => $request->input('trademark_name'),
            'trademark_application_number' => $request->input('trademark_application_number'),
            'trademark_image' => $request->input('trademark_image'),
        ];
    }
}
