<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Associate;
use App\Models\Certificate;
use App\Models\CertificateApplication;
use App\Models\CertificateType;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CertificateController extends Controller
{
    public const DUE_WINDOW_DAYS = 1100;

    public function dueList(Request $request)
    {
        $associateId = (int) $request->input('associate_id', 0);
        $clientId = (int) $request->input('client_id', 0);
        $certificateTypeId = (int) $request->input('certificate_type_id', 0);
        $per_page = 10;
        $page = max(1, (int) $request->input('page', 1));
        $threshold = Carbon::today()->addDays(self::DUE_WINDOW_DAYS);

        $query = CertificateApplication::query()
            ->with([
                'client:id,company_name,associate_id',
                'client.associate:id,company_name',
                'certificateType:id,description,code,renewal_period,audit_period',
                'certificates:id,certificate_application_id,type',
            ])
            ->where(function ($q) use ($threshold) {
                $q->whereNull('date_of_expiry')
                    ->orWhere('date_of_expiry', '<=', $threshold)
                    ->orWhere('audit_expiry_date', '<=', $threshold);
            })
            ->when($associateId > 0, function ($query) use ($associateId) {
                $query->whereHas('client', fn ($c) => $c->where('associate_id', $associateId));
            })
            ->when($clientId > 0, function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->when($certificateTypeId > 0, function ($query) use ($certificateTypeId) {
                $query->where('certificate_type_id', $certificateTypeId);
            });

        $total = (clone $query)->count();
        $rows = $query
            ->orderByRaw('COALESCE(LEAST(date_of_expiry, audit_expiry_date), date_of_expiry, audit_expiry_date) ASC')
            ->skip(($page - 1) * $per_page)
            ->take($per_page)
            ->get();

        $rows->each(function (CertificateApplication $row) {
            $row->due_info = $this->deriveDueInfo($row);
        });

        $queryParams = $request->except('page');
        $pageUrl = '?'.(empty($queryParams) ? '' : http_build_query($queryParams).'&');

        return view('admin.certificate.due_list', [
            'title' => 'Due List',
            'active_tab' => 'certificate',
            'sub_active_tab' => 'due_list',
            'rows' => $rows,
            'associates' => Associate::query()->orderBy('company_name')->get(['id', 'company_name']),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
            'certificateTypes' => CertificateType::query()->orderBy('description')->get(['id', 'description', 'code']),
            'associate_id' => $associateId,
            'client_id' => $clientId,
            'certificate_type_id' => $certificateTypeId,
            'pagination' => pagination($total, $per_page, $page, $pageUrl),
            'serial_start' => ($page - 1) * $per_page,
        ]);
    }

    public function list(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $type = trim((string) $request->input('type', ''));
        $associateId = (int) $request->input('associate_id', 0);
        $clientId = (int) $request->input('client_id', 0);
        $certificateTypeId = (int) $request->input('certificate_type_id', 0);
        $uploaded = trim((string) $request->input('uploaded', ''));
        $per_page = 10;
        $page = max(1, (int) $request->input('page', 1));

        $query = Certificate::query()
            ->with([
                'associate:id,company_name',
                'client:id,company_name',
                'certificateType:id,description,code',
                'certificateApplication:id,application_number'
            ])
            ->when(in_array($type, [Certificate::TYPE_CERTIFICATE, Certificate::TYPE_AUDIT], true), function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($uploaded !== '', function ($query) use ($uploaded) {
                if ($uploaded === 'yes') {
                    $query->whereNotNull('certificate')->where('certificate', '!=', '');
                } elseif ($uploaded === 'no') {
                    $query->where(function ($sub) {
                        $sub->whereNull('certificate')->orWhere('certificate', '');
                    });
                }
            })
            ->when($associateId > 0, function ($query) use ($associateId) {
                $query->where('associate_id', $associateId);
            })
            ->when($clientId > 0, function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->when($certificateTypeId > 0, function ($query) use ($certificateTypeId) {
                $query->where('certificate_type_id', $certificateTypeId);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('certificate_number', 'like', '%'.$q.'%')
                        ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', '%'.$q.'%'))
                        ->orWhereHas('associate', fn ($a) => $a->where('company_name', 'like', '%'.$q.'%'));
                });
            });

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('id')
            ->skip(($page - 1) * $per_page)
            ->take($per_page)
            ->get();

        $applicationIds = $rows->pluck('certificate_application_id')->filter()->unique()->values();
        if ($applicationIds->isNotEmpty()) {
            $applicationsById = CertificateApplication::query()
                ->with(['certificates' => fn ($q) => $q->orderBy('id')])
                ->whereIn('id', $applicationIds)
                ->get(['id'])
                ->keyBy('id');

            $rows->each(function (Certificate $row) use ($applicationsById) {
                $application = $applicationsById->get($row->certificate_application_id);
                $row->stage_info = $application
                    ? $this->deriveCertificateStage($row, $application)
                    : ['label' => ucfirst((string) $row->type), 'badge_class' => 'bg-label-secondary'];
            });
        }

        $queryParams = $request->except('page');
        $pageUrl = '?'.(empty($queryParams) ? '' : http_build_query($queryParams).'&');

        return view('admin.certificate.list', [
            'title' => 'Certificate List',
            'active_tab' => 'certificate_list',
            'rows' => $rows,
            'q' => $q,
            'type' => $type,
            'uploaded' => $uploaded,
            'associates' => Associate::query()->orderBy('company_name')->get(['id', 'company_name']),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
            'certificateTypes' => CertificateType::query()->orderBy('description')->get(['id', 'description', 'code']),
            'associate_id' => $associateId,
            'client_id' => $clientId,
            'certificate_type_id' => $certificateTypeId,
            'pagination' => pagination($total, $per_page, $page, $pageUrl),
            'serial_start' => ($page - 1) * $per_page,
        ]);
    }

    public function addForm(Request $request)
    {
        $applicationId = (int) $request->input('application_id', 0);
        if ($applicationId <= 0) {
            abort(404);
        }

        $application = CertificateApplication::query()
            ->with([
                'client.associate:id,company_name',
                'certificateType:id,description,code,prefix,renewal_period,audit_period,price',
                'certificates' => fn ($q) => $q->orderBy('id'),
            ])
            ->findOrFail($applicationId);

        $client = $application->client;
        $certificateType = $application->certificateType;
        $renewalYears = $this->parsePeriodYears($certificateType->renewal_period, 'renewal_period');
        $auditYears = $this->parsePeriodYears($certificateType->audit_period, 'audit_period');

        $certMode = is_null($application->date_of_expiry) && is_null($application->audit_expiry_date)
            ? 'first_issue'
            : 'renewal';

        $firstCert = $application->certificates
            ->where('type', Certificate::TYPE_CERTIFICATE)
            ->sortBy('id')
            ->first();

        $initialGrantedDefault = $firstCert
            ? ($firstCert->initial_certificate_granted_on?->format('Y-m-d')
                ?? $firstCert->issue_date?->format('Y-m-d'))
            : null;

        $title = match ($certMode) {
            'first_issue' => 'First Issue Certificate',
            'renewal' => 'Renew Certificate',
            default => 'Add Certificate',
        };

        $this->logExpiryCalculation('add_form_load', [
            'application_id' => $applicationId,
            'cert_mode' => $certMode,
            'certificate_type_id' => $certificateType->id,
            'certificate_type_code' => $certificateType->code,
            'renewal_period_raw' => $certificateType->renewal_period,
            'audit_period_raw' => $certificateType->audit_period,
            'renewal_years_for_js' => $renewalYears,
            'audit_years_for_js' => $auditYears,
            'client_formula' => 'date_of_expiry = issue_date + renewal_years; audit_expiry_date = latest_audit_date + audit_years',
        ]);

        return view('admin.certificate.form', [
            'title' => $title,
            'active_tab' => 'certificate',
            'sub_active_tab' => 'due_list',
            'mode' => 'add',
            'cert_mode' => $certMode,
            'initial_granted_default' => $initialGrantedDefault,
            'application' => $application,
            'details' => new Certificate(),
            'associate' => $client->associate,
            'client' => $client,
            'certificateType' => $certificateType,
            'issue_date' => '',
            'date_of_expiry' => '',
            'audit_expiry_date' => '',
            'renewal_years' => $renewalYears,
            'audit_years' => $auditYears,
        ]);
    }

    public function save(Request $request)
    {
        $validation = $this->certificateValidationRules($request);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $application = CertificateApplication::query()
            ->with(['client.associate', 'certificateType'])
            ->find((int) $request->input('certificate_application_id'));

        if (! $application) {
            $this->response['error'] = 'Application not found.';

            return response()->json($this->response);
        }

        $client = $application->client;
        if (! $client || ! $client->associate) {
            $this->response['error'] = 'Client or associate not found for this application.';

            return response()->json($this->response);
        }

        $certificateType = $application->certificateType;
        $issueDate = Carbon::parse($request->input('issue_date'));
        $renewalYears = $this->parsePeriodYears($certificateType->renewal_period, 'renewal_period');
        $dateOfExpiry = $issueDate->copy()->addYears($renewalYears);
        $applicationUpdate = $this->buildApplicationExpiryUpdateForCertificateSave(
            $request,
            $certificateType,
            $dateOfExpiry
        );

        $this->logExpiryCalculation('save_certificate', [
            'certificate_application_id' => $application->id,
            'certificate_type_id' => $certificateType->id,
            'request_issue_date' => $request->input('issue_date'),
            'request_latest_audit_date' => $request->input('latest_audit_date'),
            'renewal_period_raw' => $certificateType->renewal_period,
            'audit_period_raw' => $certificateType->audit_period,
            'renewal_years' => $renewalYears,
            'calculated_date_of_expiry' => $dateOfExpiry->format('Y-m-d'),
            'calculated_audit_expiry_date' => $applicationUpdate['audit_expiry_date'] ?? null,
            'application_update_keys' => array_keys($applicationUpdate),
            'formula_certificate_expiry' => 'issue_date + renewal_years',
            'formula_audit_expiry' => $request->filled('latest_audit_date')
                ? 'latest_audit_date + audit_period'
                : 'skipped (renewal — latest_audit_date empty)',
        ]);

        try {
            DB::transaction(function () use ($request, $application, $client, $certificateType, $issueDate, $dateOfExpiry, $applicationUpdate) {
                $certificateNumber = $this->generateCertificateNumber($certificateType->prefix);

                Certificate::query()->create([
                    'certificate_application_id' => $application->id,
                    'certificate_number' => $certificateNumber,
                    'type' => Certificate::TYPE_CERTIFICATE,
                    'associate_id' => $client->associate_id,
                    'client_id' => $client->id,
                    'certificate_type_id' => $certificateType->id,
                    'amount' => $request->input('amount'),
                    'issue_date' => $issueDate->format('Y-m-d'),
                    'initial_certificate_granted_on' => $request->input('initial_certificate_granted_on'),
                    'date_of_expiry' => $dateOfExpiry->format('Y-m-d'),
                    'latest_audit_date' => $request->filled('latest_audit_date') ? $request->input('latest_audit_date') : null,
                    'scope' => $request->input('scope'),
                    'admin_note' => $request->input('admin_note'),
                ]);

                $application->update($applicationUpdate);
            });
        } catch (\Throwable $e) {
            $this->response['error'] = 'Failed to save certificate. Please try again.';

            return response()->json($this->response);
        }

        $this->response['status'] = 1;
        $this->response['msg'] = 'Certificate saved successfully.';
        $this->response['redirect_url'] = url('admin/certificate/list');

        return response()->json($this->response);
    }

    public function edit($id)
    {
        $details = Certificate::query()
            ->with([
                'certificateApplication.client.associate',
                'associate',
                'client',
                'certificateType',
            ])
            ->findOrFail($id);

        $application = $details->certificateApplication;
        $certificateType = $details->certificateType;
        $renewalYears = $this->parsePeriodYears($certificateType->renewal_period, 'renewal_period');
        $auditYears = $this->parsePeriodYears($certificateType->audit_period, 'audit_period');

        $this->logExpiryCalculation('edit_form_load', [
            'certificate_id' => $details->id,
            'certificate_type_id' => $certificateType->id,
            'renewal_period_raw' => $certificateType->renewal_period,
            'audit_period_raw' => $certificateType->audit_period,
            'renewal_years_for_js' => $renewalYears,
            'audit_years_for_js' => $auditYears,
            'stored_date_of_expiry' => $details->date_of_expiry?->format('Y-m-d'),
            'stored_issue_date' => $details->issue_date?->format('Y-m-d'),
        ]);

        return view('admin.certificate.form', [
            'title' => 'Edit Certificate',
            'active_tab' => 'certificate',
            'sub_active_tab' => 'list',
            'mode' => 'edit',
            'cert_mode' => 'edit',
            'initial_granted_default' => null,
            'application' => $application,
            'details' => $details,
            'associate' => $details->associate,
            'client' => $details->client,
            'certificateType' => $certificateType,
            'issue_date' => $details->issue_date?->format('Y-m-d') ?? '',
            'date_of_expiry' => $details->date_of_expiry?->format('Y-m-d') ?? '',
            'audit_expiry_date' => $application?->audit_expiry_date?->format('Y-m-d') ?? '',
            'renewal_years' => $renewalYears,
            'audit_years' => $auditYears,
        ]);
    }

    public function update(Request $request, $id)
    {
        $certificate = Certificate::query()
            ->with(['certificateApplication', 'certificateType'])
            ->find($id);

        if (! $certificate) {
            $this->response['error'] = 'Certificate not found.';

            return response()->json($this->response);
        }

        $validation = $this->certificateValidationRules($request, false, true);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $issueDate = Carbon::parse($request->input('issue_date'));
        $certificateType = $certificate->certificateType;
        $renewalYears = $this->parsePeriodYears($certificateType->renewal_period, 'renewal_period');
        $dateOfExpiry = $issueDate->copy()->addYears($renewalYears);
        $applicationUpdate = $this->buildApplicationExpiryUpdateForCertificateSave(
            $request,
            $certificateType,
            $dateOfExpiry
        );

        $this->logExpiryCalculation('update_certificate', [
            'certificate_id' => $certificate->id,
            'request_issue_date' => $request->input('issue_date'),
            'request_latest_audit_date' => $request->input('latest_audit_date'),
            'renewal_period_raw' => $certificateType->renewal_period,
            'audit_period_raw' => $certificateType->audit_period,
            'renewal_years' => $renewalYears,
            'calculated_date_of_expiry' => $dateOfExpiry->format('Y-m-d'),
            'calculated_audit_expiry_date' => $applicationUpdate['audit_expiry_date'] ?? null,
            'application_update_keys' => array_keys($applicationUpdate),
            'formula_audit_expiry' => $request->filled('latest_audit_date')
                ? 'latest_audit_date + audit_period'
                : 'skipped (latest_audit_date empty)',
        ]);

        $certificate->update([
            'amount' => $request->input('amount'),
            'issue_date' => $issueDate->format('Y-m-d'),
            'initial_certificate_granted_on' => $request->input('initial_certificate_granted_on'),
            'date_of_expiry' => $dateOfExpiry->format('Y-m-d'),
            'latest_audit_date' => $request->filled('latest_audit_date') ? $request->input('latest_audit_date') : null,
            'scope' => $request->input('scope'),
            'admin_note' => $request->input('admin_note'),
        ]);

        $certificate->certificateApplication?->update($applicationUpdate);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Certificate updated successfully.';
        $this->response['redirect_url'] = url('admin/certificate/list');

        return response()->json($this->response);
    }

    public function view($id)
    {
        $details = Certificate::query()
            ->with([
                'associate:id,company_name',
                'client:id,company_name,contact_email,contact_mobile',
                'certificateType:id,description,code,prefix',
                'certificateApplication',
            ])
            ->findOrFail($id);

        return view('admin.certificate.view', [
            'title' => 'View Certificate',
            'active_tab' => 'certificate_list',
            'sub_active_tab' => 'list',
            'details' => $details,
        ]);
    }

    public function uploadCertificate($id)
    {
        $details = Certificate::query()
            ->with(['client:id,company_name'])
            ->findOrFail($id);

        return view('admin.certificate.upload', [
            'title' => 'Upload Certificate',
            'active_tab' => 'certificate_list',
            'sub_active_tab' => 'list',
            'details' => $details,
        ]);
    }

    public function saveCertificateImage(Request $request, $id)
    {
        $certificate = Certificate::query()->find($id);
        if (! $certificate) {
            $this->response['error'] = 'Certificate not found.';

            return response()->json($this->response);
        }

        $validation = Validator::make($request->all(), [
            'certificate' => 'required|string|max:255',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $certificate->update([
            'certificate' => $request->input('certificate'),
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Certificate image saved successfully.';
        $this->response['redirect_url'] = url('admin/certificate/list');

        return response()->json($this->response);
    }

    public function auditForm(Request $request)
    {
        $applicationId = (int) $request->input('application_id', 0);
        if ($applicationId <= 0) {
            abort(404);
        }

        $application = $this->loadApplicationForForm($applicationId);
        $certificateType = $application->certificateType;
        $auditYears = $this->parsePeriodYears($certificateType->audit_period, 'audit_period');
        $latestCertificate = $application->certificates()->orderByDesc('id')->first();

        $this->logExpiryCalculation('audit_form_load', [
            'application_id' => $applicationId,
            'certificate_type_id' => $certificateType->id,
            'audit_period_raw' => $certificateType->audit_period,
            'renewal_period_raw' => $certificateType->renewal_period,
            'audit_years_for_js' => $auditYears,
            'client_formula' => 'audit_expiry_date = latest_audit_date + audit_years_for_js',
        ]);

        return view('admin.certificate.audit_form', [
            'title' => 'Surveillance Audit',
            'active_tab' => 'certificate',
            'sub_active_tab' => 'due_list',
            'application' => $application,
            'associate' => $application->client->associate,
            'client' => $application->client,
            'certificateType' => $certificateType,
            'issue_date' => '',
            'audit_expiry_date' => '',
            'audit_years' => $auditYears,
            'latest_certificate' => $latestCertificate,
        ]);
    }

    public function saveAudit(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'certificate_application_id' => 'required|exists:certificate_applications,id',
            'issue_date' => 'required|date',
            'latest_audit_date' => 'required|date',
            'scope' => 'nullable|string|max:5000',
            'admin_note' => 'nullable|string|max:5000',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $application = CertificateApplication::query()
            ->with(['certificateType', 'certificates'])
            ->find((int) $request->input('certificate_application_id'));

        if (! $application) {
            $this->response['error'] = 'Application not found.';

            return response()->json($this->response);
        }

        if ($application->certificates()->count() === 0) {
            $this->response['error'] = 'Generate a certificate before updating audit.';

            return response()->json($this->response);
        }

        $certificateType = $application->certificateType;
        $auditYears = $this->parsePeriodYears($certificateType->audit_period, 'audit_period');
        $latestAuditDate = Carbon::parse($request->input('latest_audit_date'));
        $auditExpiryDate = $latestAuditDate->copy()->addYears($auditYears);

        $this->logExpiryCalculation('save_audit', [
            'certificate_application_id' => $application->id,
            'request_latest_audit_date' => $request->input('latest_audit_date'),
            'audit_period_raw' => $certificateType->audit_period,
            'audit_years' => $auditYears,
            'calculated_audit_expiry_date' => $auditExpiryDate->format('Y-m-d'),
            'formula_audit_expiry' => 'latest_audit_date + audit_years',
        ]);

        $client = $application->client;
        if (! $client) {
            $this->response['error'] = 'Client not found for this application.';

            return response()->json($this->response);
        }

        $issueDate = Carbon::parse($request->input('issue_date'));

        try {
            DB::transaction(function () use ($request, $application, $client, $certificateType, $issueDate, $auditExpiryDate) {
                $latestCertificate = $application->certificates()->orderByDesc('id')->first();
                if (! $latestCertificate) {
                    throw new \RuntimeException('No certificate found for this application.');
                }

                $certificateNumber = $this->generateCertificateNumber($certificateType->prefix);

                Certificate::query()->create([
                    'certificate_application_id' => $application->id,
                    'certificate_number' => $certificateNumber,
                    'type' => Certificate::TYPE_AUDIT,
                    'associate_id' => $latestCertificate->associate_id,
                    'client_id' => $latestCertificate->client_id,
                    'certificate_type_id' => $latestCertificate->certificate_type_id,
                    'amount' => $latestCertificate->amount,
                    'issue_date' => $issueDate->format('Y-m-d'),
                    'initial_certificate_granted_on' => $latestCertificate->initial_certificate_granted_on?->format('Y-m-d'),
                    'date_of_expiry' => $latestCertificate->date_of_expiry?->format('Y-m-d'),
                    'latest_audit_date' => $request->input('latest_audit_date'),
                    'scope' => $request->input('scope'),
                    'admin_note' => $request->input('admin_note'),
                    'certificate' => $latestCertificate->certificate,
                ]);

                $application->update([
                    'audit_expiry_date' => $auditExpiryDate->format('Y-m-d'),
                ]);
            });
        } catch (\Throwable $e) {
            $this->response['error'] = 'Failed to save audit update. Please try again.';

            return response()->json($this->response);
        }

        $this->response['status'] = 1;
        $this->response['msg'] = 'Audit updated successfully.';
        $this->response['redirect_url'] = url('admin/certificate/due-list');

        return response()->json($this->response);
    }

    /**
     * @return array{kind: string, label: string, due_date: ?Carbon, action_url: string, action_label: string, badge_class: string}
     */
    protected function deriveDueInfo(CertificateApplication $app): array
    {
        $certificates = $app->relationLoaded('certificates')
            ? $app->certificates
            : $app->certificates()->get(['id', 'certificate_application_id', 'type']);

        $certRows = $certificates->where('type', Certificate::TYPE_CERTIFICATE);
        $certCount = $certRows->count();
        $latestCert = $certRows->sortByDesc('id')->first();
        $latestCertId = $latestCert?->id;

        $auditCountInCurrentCycle = $latestCertId
            ? $certificates
                ->where('type', Certificate::TYPE_AUDIT)
                ->where('id', '>', $latestCertId)
                ->count()
            : 0;

        $certificateType = $app->certificateType;
        $renewalYears = $this->parsePeriodYears($certificateType?->renewal_period, 'renewal_period');
        $auditYears = $this->parsePeriodYears($certificateType?->audit_period, 'audit_period');
        $surveillancesPerCycle = $auditYears > 0
            ? max(0, intdiv($renewalYears, $auditYears) - 1)
            : 0;

        if ($certCount === 0) {
            $kind = 'first_issue';
            $label = 'First Issue';
            $dueDate = null;
            $actionUrl = url('admin/certificate/add?application_id='.$app->id);
            $actionLabel = 'Certificate';
            $badgeClass = 'bg-label-warning';
        } elseif ($auditCountInCurrentCycle < $surveillancesPerCycle) {
            $kind = 'surveillance';
            $n = $auditCountInCurrentCycle + 1;
            $label = $this->ordinal($n).' Surveillance';
            $dueDate = $app->audit_expiry_date;
            $actionUrl = url('admin/certificate/audit?application_id='.$app->id);
            $actionLabel = 'Audit';
            $badgeClass = 'bg-label-info';
        } else {
            $kind = 'renewal';
            $n = $certCount;
            $label = $this->ordinal($n).' Renewal';
            $dueDate = $app->date_of_expiry;
            $actionUrl = url('admin/certificate/add?application_id='.$app->id);
            $actionLabel = 'Certificate';
            $badgeClass = 'bg-label-primary';
        }

        return [
            'kind' => $kind,
            'label' => $label,
            'due_date' => $dueDate,
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
            'badge_class' => $badgeClass,
        ];
    }

    protected function ordinal(int $n): string
    {
        if ($n % 100 >= 11 && $n % 100 <= 13) {
            return $n.'th';
        }

        return match ($n % 10) {
            1 => $n.'st',
            2 => $n.'nd',
            3 => $n.'rd',
            default => $n.'th',
        };
    }

    /**
     * Compute the cycle-stage label for a single Certificate row inside its application's history.
     * Cert-type rows: first cert = First Issue, second cert = 1st Renewal, third cert = 2nd Renewal, ...
     * Audit-type rows: counted within the current cycle (i.e., since the latest preceding cert-type row).
     *
     * @return array{label: string, badge_class: string, kind: string}
     */
    protected function deriveCertificateStage(Certificate $row, CertificateApplication $app): array
    {
        $certificates = $app->relationLoaded('certificates')
            ? $app->certificates->sortBy('id')->values()
            : $app->certificates()->orderBy('id')->get();

        if ($row->type === Certificate::TYPE_CERTIFICATE) {
            $certIndex = $certificates
                ->where('type', Certificate::TYPE_CERTIFICATE)
                ->values()
                ->search(fn (Certificate $c) => $c->id === $row->id);

            if ($certIndex === false || $certIndex === 0) {
                return [
                    'label' => 'First Issue',
                    'badge_class' => 'bg-label-warning',
                    'kind' => 'first_issue',
                ];
            }

            return [
                'label' => $this->ordinal($certIndex).' Renewal',
                'badge_class' => 'bg-label-primary',
                'kind' => 'renewal',
            ];
        }

        if ($row->type === Certificate::TYPE_AUDIT) {
            $previousCertId = $certificates
                ->where('type', Certificate::TYPE_CERTIFICATE)
                ->where('id', '<', $row->id)
                ->max('id') ?? 0;

            $surveillanceNumber = $certificates
                ->where('type', Certificate::TYPE_AUDIT)
                ->where('id', '>', $previousCertId)
                ->where('id', '<=', $row->id)
                ->count();

            if ($surveillanceNumber < 1) {
                $surveillanceNumber = 1;
            }

            return [
                'label' => $this->ordinal($surveillanceNumber).' Surveillance',
                'badge_class' => 'bg-label-info',
                'kind' => 'surveillance',
            ];
        }

        return [
            'label' => ucfirst((string) $row->type),
            'badge_class' => 'bg-label-secondary',
            'kind' => $row->type,
        ];
    }

    protected function loadApplicationForForm(int $applicationId): CertificateApplication
    {
        return CertificateApplication::query()
            ->with([
                'client.associate:id,company_name',
                'certificateType:id,description,code,prefix,renewal_period,audit_period,price',
                'certificates' => fn ($q) => $q->orderByDesc('id')->limit(1),
            ])
            ->findOrFail($applicationId);
    }

    /**
     * Application expiry fields updated from Due List certificate save / edit.
     * Always sets date_of_expiry; sets audit_expiry_date only when latest_audit_date is provided (first issue).
     */
    protected function buildApplicationExpiryUpdateForCertificateSave(
        Request $request,
        CertificateType $certificateType,
        Carbon $dateOfExpiry
    ): array {
        $applicationUpdate = [
            'date_of_expiry' => $dateOfExpiry->format('Y-m-d'),
        ];

        if ($request->filled('latest_audit_date')) {
            $auditYears = $this->parsePeriodYears($certificateType->audit_period, 'audit_period');
            $applicationUpdate['audit_expiry_date'] = Carbon::parse($request->input('latest_audit_date'))
                ->addYears($auditYears)
                ->format('Y-m-d');
        }

        return $applicationUpdate;
    }

    protected function certificateValidationRules(Request $request, bool $requireApplication = true, bool $isUpdate = false)
    {
        $rules = [
            'amount' => 'required|numeric|min:0',
            'issue_date' => 'required|date',
            'initial_certificate_granted_on' => 'nullable|date',
            'latest_audit_date' => 'nullable|date',
            'scope' => 'nullable|string|max:5000',
            'admin_note' => 'nullable|string|max:5000',
        ];

        if ($requireApplication) {
            $rules['certificate_application_id'] = 'required|exists:certificate_applications,id';
        }

        $certMode = $request->input('cert_mode', 'edit');
        if (! $isUpdate && in_array($certMode, ['first_issue', 'renewal'], true)) {
            $rules['latest_audit_date'] = 'required|date';
        }

        return Validator::make($request->all(), $rules);
    }

    public function logClientExpiryCalc(Request $request)
    {
        $this->logExpiryCalculation('client_form_recalc', [
            'source' => $request->input('source', 'certificate_form'),
            'issue_date' => $request->input('issue_date'),
            'latest_audit_date' => $request->input('latest_audit_date'),
            'renewal_period_raw' => $request->input('renewal_period_raw'),
            'audit_period_raw' => $request->input('audit_period_raw'),
            'renewal_years_used_in_js' => $request->input('renewal_years_used_in_js'),
            'audit_years_used_in_js' => $request->input('audit_years_used_in_js'),
            'calculated_date_of_expiry' => $request->input('calculated_date_of_expiry'),
            'calculated_audit_expiry_date' => $request->input('calculated_audit_expiry_date'),
            'client_formula' => $request->input('client_formula'),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['logged' => true]);
    }

    protected function logExpiryCalculation(string $stage, array $data): void
    {
        Log::info('[CertificateExpiry] '.$stage, $data);
    }

    protected function parsePeriodYears(?string $period, string $label = 'period'): int
    {
        $raw = $period === null ? null : trim($period);
        $parsed = 1;
        $regexMatched = false;
        $regexCapture = null;

        if ($raw === null || $raw === '') {
            $parsed = 1;
        } elseif (preg_match('/(\d+)/', $raw, $matches)) {
            $regexMatched = true;
            $regexCapture = $matches[1];
            $parsed = max(1, (int) $matches[1]);
        }

        $this->logExpiryCalculation('parse_period_years', [
            'label' => $label,
            'raw_value' => $period,
            'trimmed_value' => $raw,
            'regex_matched' => $regexMatched,
            'regex_first_number' => $regexCapture,
            'parsed_years' => $parsed,
            'warning' => $label === 'renewal_period' && $period !== null && str_contains(strtolower((string) $period), 'audit')
                ? 'renewal_period string contains word audit — check certificate type fields are not swapped'
                : null,
        ]);

        return $parsed;
    }

    protected function generateCertificateNumber(string $prefix): string
    {
        $cleanPrefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prefix) ?: 'CERT');

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $random = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $number = 'EB/'.$cleanPrefix.'/'.$random;

            if (! Certificate::query()->where('certificate_number', $number)->exists()) {
                return $number;
            }
        }

        throw new \RuntimeException('Unable to generate unique certificate number.');
    }
}
