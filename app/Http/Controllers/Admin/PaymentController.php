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
        $per_page = 10;
        $page = max(1, (int) $request->input('page', 1));

        $query = Payment::query()
            ->with([
                'invoice:id,invoice_number,total_amount',
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

            if ($prefilledInvoice && (float) $prefilledInvoice->pending_amount <= 0) {
                return redirect('admin/invoice/list')->with('error', 'Invoice '.$prefilledInvoice->invoice_number.' is already fully paid.');
            }
        }

        return view('admin.payment.form', [
            'title' => 'Add Payment',
            'active_tab' => 'payment',
            'sub_active_tab' => 'add',
            'mode' => 'add',
            'details' => new Payment(),
            'associates' => Associate::query()->orderBy('company_name')->get(['id', 'company_name']),
            'prefilledInvoice' => $prefilledInvoice,
            'payment_date' => Carbon::today()->format('Y-m-d'),
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
        $editingInvoiceIds = [];

        if ($editingPaymentId > 0) {
            $payment = Payment::find($editingPaymentId);
            if ($payment) {
                $editingInvoiceIds[] = $payment->invoice_id;
            }
        }

        $invoices = Invoice::query()
            ->where('client_id', $clientId)
            ->where(function ($q) use ($editingInvoiceIds) {
                $q->where('pending_amount', '>', 0)
                  ->orWhereIn('id', $editingInvoiceIds);
            })
            ->orderByDesc('id')
            ->get(['id', 'invoice_number', 'invoice_date', 'total_amount', 'pending_amount']);

        $payload = $invoices->map(function (Invoice $invoice) use ($editingPaymentId, $editingInvoiceIds) {
            $dateLabel = $invoice->invoice_date?->format('d M Y') ?? '—';
            $displayPending = $invoice->pending_amount;

            if (in_array($invoice->id, $editingInvoiceIds)) {
                $payment = Payment::find($editingPaymentId);
                if ($payment) {
                    $displayPending += $payment->amount;
                }
            }

            return [
                'id' => $invoice->id,
                'label' => trim($invoice->invoice_number.' — '.$dateLabel.' — Pending: '.number_format((float) $displayPending, 2)),
                'amount' => (float) $displayPending,
            ];
        })->values();

        return response()->json($payload);
    }

    public function save(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'required|exists:invoices,id',
            'associate_id' => 'required|exists:associates,id',
            'client_id' => 'required|exists:clients,id',
            'payment_date' => 'required|date',
            'admin_note' => 'nullable|string|max:5000',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $clientId = (int) $request->input('client_id');
        $associateId = (int) $request->input('associate_id');
        $invoiceIds = array_values(array_map('intval', (array) $request->input('invoice_ids', [])));

        $invoices = Invoice::query()
            ->whereIn('id', $invoiceIds)
            ->where('client_id', $clientId)
            ->where('associate_id', $associateId)
            ->get();

        if ($invoices->count() !== count($invoiceIds)) {
            $this->response['error'] = 'One or more selected invoices are invalid.';

            return response()->json($this->response);
        }

        foreach ($invoices as $invoice) {
            if ($invoice->pending_amount <= 0) {
                $this->response['error'] = 'Invoice ' . $invoice->invoice_number . ' is already fully paid.';

                return response()->json($this->response);
            }
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $invoices) {
                foreach ($invoices as $invoice) {
                    Payment::query()->create([
                        'invoice_id' => $invoice->id,
                        'associate_id' => $request->input('associate_id'),
                        'client_id' => $request->input('client_id'),
                        'amount' => $invoice->pending_amount,
                        'payment_date' => $request->input('payment_date'),
                        'admin_note' => $request->input('admin_note'),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            $this->response['error'] = 'Failed to save payment. Please try again.';

            return response()->json($this->response);
        }

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
        ]);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::query()->findOrFail($id);

        $validation = Validator::make($request->all(), [
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'required|exists:invoices,id',
            'associate_id' => 'required|exists:associates,id',
            'client_id' => 'required|exists:clients,id',
            'payment_date' => 'required|date',
            'admin_note' => 'nullable|string|max:5000',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $clientId = (int) $request->input('client_id');
        $associateId = (int) $request->input('associate_id');
        $invoiceIds = array_values(array_map('intval', (array) $request->input('invoice_ids', [])));

        $invoiceId = $invoiceIds[0];

        $invoice = Invoice::query()
            ->where('id', $invoiceId)
            ->where('client_id', $clientId)
            ->where('associate_id', $associateId)
            ->first();

        if (! $invoice) {
            $this->response['error'] = 'Selected invoice is invalid.';

            return response()->json($this->response);
        }

        $availableAmount = $invoice->pending_amount;
        if ((int) $payment->invoice_id === (int) $invoice->id) {
            $availableAmount += $payment->amount;
        }

        if ($availableAmount <= 0) {
            $this->response['error'] = 'Selected invoice is already fully paid.';

            return response()->json($this->response);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $request, $invoice, $availableAmount) {
                $payment->update([
                    'invoice_id' => $invoice->id,
                    'associate_id' => $request->input('associate_id'),
                    'client_id' => $request->input('client_id'),
                    'amount' => $availableAmount,
                    'payment_date' => $request->input('payment_date'),
                    'admin_note' => $request->input('admin_note'),
                ]);
            });
        } catch (\Throwable $e) {
            $this->response['error'] = 'Failed to update payment.';

            return response()->json($this->response);
        }

        $this->response['status'] = 1;
        $this->response['msg'] = 'Payment updated successfully.';
        $this->response['redirect_url'] = url('admin/payment/list');

        return response()->json($this->response);
    }

    public function view($id)
    {
        $details = Payment::query()
            ->with([
                'invoice:id,invoice_number,total_amount',
                'associate:id,company_name',
                'client:id,company_name',
            ])
            ->findOrFail($id);

        return view('admin.payment.view', [
            'title' => 'Payment #'.$details->id,
            'active_tab' => 'payment',
            'sub_active_tab' => 'list',
            'details' => $details,
        ]);
    }
}
