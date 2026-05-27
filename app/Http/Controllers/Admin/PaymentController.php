<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Associate;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function list(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $associateId = (int) $request->input('associate_id', 0);
        $clientId = (int) $request->input('client_id', 0);
        $status = trim((string) $request->input('status', ''));
        $per_page = 10;
        $page = max(1, (int) $request->input('page', 1));

        $query = Payment::query()
            ->with([
                'invoice:id,invoice_number,amount',
                'associate:id,company_name',
                'client:id,company_name',
            ])
            ->when($associateId > 0, function ($query) use ($associateId) {
                $query->where('associate_id', $associateId);
            })
            ->when($clientId > 0, function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->when(in_array($status, [Payment::STATUS_PENDING, Payment::STATUS_DONE], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->whereHas('invoice', fn ($inv) => $inv->where('invoice_number', 'like', '%'.$q.'%'))
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

        return view('admin.payment.list', [
            'title' => 'Payment List',
            'active_tab' => 'payment',
            'sub_active_tab' => 'list',
            'rows' => $rows,
            'q' => $q,
            'associates' => Associate::query()->orderBy('company_name')->get(['id', 'company_name']),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name']),
            'associate_id' => $associateId,
            'client_id' => $clientId,
            'status' => $status,
            'statuses' => Payment::$statuses,
            'pagination' => pagination($total, $per_page, $page, $pageUrl),
            'serial_start' => ($page - 1) * $per_page,
        ]);
    }

    public function add(Request $request)
    {
        $prefilledInvoice = null;
        $invoiceId = (int) $request->input('invoice_id', 0);

        if ($invoiceId > 0) {
            $prefilledInvoice = Invoice::query()
                ->with([
                    'associate:id,company_name',
                    'client:id,company_name',
                ])
                ->find($invoiceId);

            if ($prefilledInvoice && in_array((int) $prefilledInvoice->id, $this->getPaidInvoiceIds(), true)) {
                return redirect('admin/invoice/list')->with('error', 'A payment already exists for invoice '.$prefilledInvoice->invoice_number.'.');
            }
        }

        return view('admin.payment.form', [
            'title' => 'Add Payment',
            'active_tab' => 'payment',
            'sub_active_tab' => 'add',
            'mode' => 'add',
            'details' => new Payment(['status' => Payment::STATUS_PENDING]),
            'associates' => Associate::query()->orderBy('company_name')->get(['id', 'company_name']),
            'prefilledInvoice' => $prefilledInvoice,
            'payment_date' => Carbon::today()->format('Y-m-d'),
            'statuses' => Payment::$statuses,
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

    public function getInvoices(Request $request)
    {
        $clientId = (int) $request->input('client_id', 0);
        if ($clientId <= 0) {
            return response()->json([]);
        }

        $editingPaymentId = (int) $request->input('payment_id', 0);
        $paidInvoiceIds = $this->getPaidInvoiceIds($editingPaymentId > 0 ? $editingPaymentId : null);

        $invoices = Invoice::query()
            ->where('client_id', $clientId)
            ->whereNotIn('id', $paidInvoiceIds)
            ->orderByDesc('id')
            ->get(['id', 'invoice_number', 'invoice_date', 'amount']);

        $payload = $invoices->map(function (Invoice $invoice) {
            $dateLabel = $invoice->invoice_date?->format('d M Y') ?? '—';

            return [
                'id' => $invoice->id,
                'label' => trim($invoice->invoice_number.' — '.$dateLabel.' — '.number_format((float) $invoice->amount, 2)),
                'amount' => (float) $invoice->amount,
            ];
        })->values();

        return response()->json($payload);
    }

    public function save(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'associate_id' => 'required|exists:associates,id',
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'status' => 'required|in:pending,done',
            'admin_note' => 'nullable|string|max:5000',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $invoiceError = $this->validateInvoiceSelection(
            (int) $request->input('invoice_id'),
            (int) $request->input('client_id'),
            (int) $request->input('associate_id')
        );

        if ($invoiceError !== null) {
            $this->response['error'] = $invoiceError;

            return response()->json($this->response);
        }

        if (in_array((int) $request->input('invoice_id'), $this->getPaidInvoiceIds(), true)) {
            $this->response['error'] = 'A payment already exists for this invoice.';

            return response()->json($this->response);
        }

        Payment::query()->create([
            'invoice_id' => $request->input('invoice_id'),
            'associate_id' => $request->input('associate_id'),
            'client_id' => $request->input('client_id'),
            'amount' => $request->input('amount'),
            'payment_date' => $request->input('payment_date'),
            'status' => $request->input('status'),
            'admin_note' => $request->input('admin_note'),
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Payment saved successfully.';
        $this->response['redirect_url'] = url('admin/payment/list');

        return response()->json($this->response);
    }

    public function edit($id)
    {
        $details = Payment::query()->findOrFail($id);

        return view('admin.payment.form', [
            'title' => 'Edit Payment',
            'active_tab' => 'payment',
            'sub_active_tab' => 'list',
            'mode' => 'edit',
            'details' => $details,
            'associates' => Associate::query()->orderBy('company_name')->get(['id', 'company_name']),
            'prefilledInvoice' => null,
            'payment_date' => $details->payment_date?->format('Y-m-d'),
            'statuses' => Payment::$statuses,
        ]);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::query()->findOrFail($id);

        $validation = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'associate_id' => 'required|exists:associates,id',
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'status' => 'required|in:pending,done',
            'admin_note' => 'nullable|string|max:5000',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $invoiceError = $this->validateInvoiceSelection(
            (int) $request->input('invoice_id'),
            (int) $request->input('client_id'),
            (int) $request->input('associate_id')
        );

        if ($invoiceError !== null) {
            $this->response['error'] = $invoiceError;

            return response()->json($this->response);
        }

        if (in_array((int) $request->input('invoice_id'), $this->getPaidInvoiceIds((int) $payment->id), true)) {
            $this->response['error'] = 'A payment already exists for this invoice.';

            return response()->json($this->response);
        }

        $payment->update([
            'invoice_id' => $request->input('invoice_id'),
            'associate_id' => $request->input('associate_id'),
            'client_id' => $request->input('client_id'),
            'amount' => $request->input('amount'),
            'payment_date' => $request->input('payment_date'),
            'status' => $request->input('status'),
            'admin_note' => $request->input('admin_note'),
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Payment updated successfully.';
        $this->response['redirect_url'] = url('admin/payment/list');

        return response()->json($this->response);
    }

    public function view($id)
    {
        $details = Payment::query()
            ->with([
                'invoice:id,invoice_number,amount',
                'associate:id,company_name',
                'client:id,company_name',
            ])
            ->findOrFail($id);

        return view('admin.payment.view', [
            'title' => 'Payment #'.$details->id,
            'active_tab' => 'payment',
            'sub_active_tab' => 'list',
            'details' => $details,
            'statuses' => Payment::$statuses,
        ]);
    }

    /**
     * @return array<int, int>
     */
    protected function getPaidInvoiceIds(?int $excludePaymentId = null): array
    {
        return Payment::query()
            ->when($excludePaymentId, fn ($q) => $q->where('id', '!=', $excludePaymentId))
            ->pluck('invoice_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function validateInvoiceSelection(int $invoiceId, int $clientId, int $associateId): ?string
    {
        $invoice = Invoice::query()->find($invoiceId);

        if (! $invoice) {
            return 'Invoice not found.';
        }

        if ((int) $invoice->client_id !== $clientId) {
            return 'Invoice does not belong to the selected client.';
        }

        if ((int) $invoice->associate_id !== $associateId) {
            return 'Invoice does not belong to the selected associate.';
        }

        $client = Client::query()->find($clientId);
        if (! $client || (int) $client->associate_id !== $associateId) {
            return 'Client does not belong to the selected associate.';
        }

        return null;
    }
}
