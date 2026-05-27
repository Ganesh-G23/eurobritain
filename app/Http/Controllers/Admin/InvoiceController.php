<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Associate;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function list(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $associateId = (int) $request->input('associate_id', 0);
        $clientId = (int) $request->input('client_id', 0);
        $per_page = 10;
        $page = max(1, (int) $request->input('page', 1));

        $query = Invoice::query()
            ->with([
                'associate:id,company_name',
                'client:id,company_name',
            ])
            ->when($associateId > 0, function ($query) use ($associateId) {
                $query->where('associate_id', $associateId);
            })
            ->when($clientId > 0, function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('invoice_number', 'like', '%'.$q.'%')
                        ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', '%'.$q.'%'))
                        ->orWhereHas('associate', fn ($a) => $a->where('company_name', 'like', '%'.$q.'%'));
                });
            });

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('id')
            ->skip(($page - 1) * $per_page)
            ->take($per_page)
            ->get();

        $queryParams = $request->except('page');
        $pageUrl = '?'.(empty($queryParams) ? '' : http_build_query($queryParams).'&');

        return view('admin.invoice.list', [
            'title' => 'Invoice List',
            'active_tab' => 'invoice',
            'sub_active_tab' => 'list',
            'rows' => $rows,
            'q' => $q,
            'associates' => Associate::query()->orderBy('company_name')->get(['id', 'company_name']),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
            'associate_id' => $associateId,
            'client_id' => $clientId,
            'pagination' => pagination($total, $per_page, $page, $pageUrl),
            'serial_start' => ($page - 1) * $per_page,
        ]);
    }

    public function add()
    {
        return view('admin.invoice.form', [
            'title' => 'Add Invoice',
            'active_tab' => 'invoice',
            'sub_active_tab' => 'add',
            'mode' => 'add',
            'details' => new Invoice(),
            'associates' => Associate::query()->orderBy('company_name')->get(['id', 'company_name']),
            'invoice_date' => Carbon::today()->format('Y-m-d'),
        ]);
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

    public function getCertificates(Request $request)
    {
        $clientId = (int) $request->input('client_id', 0);
        if ($clientId <= 0) {
            return response()->json([]);
        }

        $editingInvoiceId = (int) $request->input('invoice_id', 0);
        $billedIds = $this->getBilledCertificateIds($editingInvoiceId > 0 ? $editingInvoiceId : null);

        $certificates = Certificate::query()
            ->with('certificateType:id,description,code')
            ->where('client_id', $clientId)
            ->whereNotIn('id', $billedIds)
            ->orderByDesc('id')
            ->get(['id', 'certificate_number', 'certificate_type_id', 'amount']);

        $payload = $certificates->map(function (Certificate $cert) {
            $typeLabel = $cert->certificateType->description ?? $cert->certificateType->code ?? '—';

            return [
                'id' => $cert->id,
                'label' => trim($cert->certificate_number.' — '.$typeLabel),
                'amount' => (float) $cert->amount,
            ];
        })->values();

        return response()->json($payload);
    }

    public function save(Request $request)
    {
        $certificateIds = $this->normalizeCertificateIds($request->input('certificate_ids', []));
        $clientId = (int) $request->input('client_id', 0);

        $validation = Validator::make($request->all(), [
            'associate_id' => 'required|exists:associates,id',
            'client_id' => 'required|exists:clients,id',
            'certificate_ids' => 'required|array|min:1',
            'certificate_ids.*' => 'required|integer|distinct',
            'amount' => 'required|numeric|min:0',
            'invoice_date' => 'required|date',
            'admin_note' => 'nullable|string|max:5000',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $client = Client::query()->find($clientId);
        if (! $client || (int) $client->associate_id !== (int) $request->input('associate_id')) {
            $this->response['error'] = 'Client does not belong to the selected associate.';

            return response()->json($this->response);
        }

        $certError = $this->validateCertificateSelection($clientId, $certificateIds);
        if ($certError !== null) {
            $this->response['error'] = $certError;

            return response()->json($this->response);
        }

        try {
            DB::transaction(function () use ($request, $certificateIds) {
                Invoice::query()->create([
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'associate_id' => $request->input('associate_id'),
                    'client_id' => $request->input('client_id'),
                    'certificate_ids' => $certificateIds,
                    'invoice_date' => $request->input('invoice_date'),
                    'total_amount' => $request->input('amount'),
                    'paid_amount' => 0,
                    'pending_amount' => $request->input('amount'),
                    'admin_note' => $request->input('admin_note'),
                ]);
            });
        } catch (\Throwable $e) {
            $this->response['error'] = 'Unable to save invoice. Please try again.';

            return response()->json($this->response);
        }

        $this->response['status'] = 1;
        $this->response['msg'] = 'Invoice saved successfully.';
        $this->response['redirect_url'] = url('admin/invoice/list');

        return response()->json($this->response);
    }

    public function edit($id)
    {
        $details = Invoice::query()->findOrFail($id);

        return view('admin.invoice.form', [
            'title' => 'Edit Invoice',
            'active_tab' => 'invoice',
            'sub_active_tab' => 'list',
            'mode' => 'edit',
            'details' => $details,
            'associates' => Associate::query()->orderBy('company_name')->get(['id', 'company_name']),
            'invoice_date' => $details->invoice_date?->format('Y-m-d'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::query()->findOrFail($id);
        $certificateIds = $this->normalizeCertificateIds($request->input('certificate_ids', []));
        $clientId = (int) $request->input('client_id', 0);

        $validation = Validator::make($request->all(), [
            'associate_id' => 'required|exists:associates,id',
            'client_id' => 'required|exists:clients,id',
            'certificate_ids' => 'required|array|min:1',
            'certificate_ids.*' => 'required|integer|distinct',
            'amount' => 'required|numeric|min:0',
            'invoice_date' => 'required|date',
            'admin_note' => 'nullable|string|max:5000',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $client = Client::query()->find($clientId);
        if (! $client || (int) $client->associate_id !== (int) $request->input('associate_id')) {
            $this->response['error'] = 'Client does not belong to the selected associate.';

            return response()->json($this->response);
        }

        $certError = $this->validateCertificateSelection($clientId, $certificateIds, (int) $invoice->id);
        if ($certError !== null) {
            $this->response['error'] = $certError;

            return response()->json($this->response);
        }

        $totalAmount = $request->input('amount');
        $paidAmount = Payment::query()->where('invoice_id', $invoice->id)->sum('amount');

        $invoice->update([
            'associate_id' => $request->input('associate_id'),
            'client_id' => $request->input('client_id'),
            'certificate_ids' => $certificateIds,
            'invoice_date' => $request->input('invoice_date'),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'pending_amount' => max(0, $totalAmount - $paidAmount),
            'admin_note' => $request->input('admin_note'),
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Invoice updated successfully.';
        $this->response['redirect_url'] = url('admin/invoice/list');

        return response()->json($this->response);
    }

    public function view($id)
    {
        $details = Invoice::query()
            ->with([
                'associate:id,company_name,contact_person,contact_email,contact_mobile,address,city',
                'client:id,company_name,contact_person,contact_email,contact_mobile,address,city,country_id,state_id',
                'client.country:id,name',
                'client.state:id,name',
            ])
            ->findOrFail($id);

        $certificates = $details->certificates();

        return view('admin.invoice.view', [
            'title' => 'Invoice: '.$details->invoice_number,
            'active_tab' => 'invoice',
            'sub_active_tab' => 'list',
            'details' => $details,
            'certificates' => $certificates,
        ]);
    }

    public function pdf($id)
    {
        $details = Invoice::query()
            ->with([
                'associate:id,company_name,contact_person,contact_email,contact_mobile,address,city',
                'client:id,company_name,contact_person,contact_email,contact_mobile,address,city,country_id,state_id',
                'client.country:id,name',
                'client.state:id,name',
            ])
            ->findOrFail($id);

        $certificates = $details->certificates();

        $logoPath = public_path('admin_theme/assets/img/logo.png');
        if (! file_exists($logoPath)) {
            $logoPath = '';
        }

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $html = view('admin.invoice.pdf', [
            'details' => $details,
            'certificates' => $certificates,
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

        $mpdf->SetTitle('Invoice '.$details->invoice_number);
        $mpdf->SetAuthor('Eurobritain Certifications Limited');
        $mpdf->SetCreator('Eurobritain Certifications Limited');
        $mpdf->WriteHTML($html);

        $filename = preg_replace('/[^A-Za-z0-9_-]+/', '_', $details->invoice_number).'.pdf';

        return response(
            $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    /**
     * @return array<int, int>
     */
    protected function getBilledCertificateIds(?int $excludeInvoiceId = null): array
    {
        return Invoice::query()
            ->when($excludeInvoiceId, fn ($q) => $q->where('id', '!=', $excludeInvoiceId))
            ->pluck('certificate_ids')
            ->flatten()
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $certificateIds
     */
    protected function validateCertificateSelection(int $clientId, array $certificateIds, ?int $excludeInvoiceId = null): ?string
    {
        if ($certificateIds === []) {
            return 'Please select at least one certificate.';
        }

        $billedIds = $this->getBilledCertificateIds($excludeInvoiceId);

        $validCerts = Certificate::query()
            ->where('client_id', $clientId)
            ->whereIn('id', $certificateIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($validCerts) !== count($certificateIds)) {
            return 'One or more selected certificates are invalid for this client.';
        }

        foreach ($certificateIds as $certId) {
            if (in_array($certId, $billedIds, true)) {
                return 'One or more selected certificates are already billed on another invoice.';
            }
        }

        return null;
    }

    /**
     * @param  mixed  $raw
     * @return array<int, int>
     */
    protected function normalizeCertificateIds($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw))));
    }

    protected function generateInvoiceNumber(): string
    {
        $lastId = (int) (Invoice::withTrashed()->max('id') ?? 0);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = 'INV-'.str_pad((string) ($lastId + 1 + $attempt), 4, '0', STR_PAD_LEFT);

            if (! Invoice::withTrashed()->where('invoice_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Unable to generate unique invoice number.');
    }
}
