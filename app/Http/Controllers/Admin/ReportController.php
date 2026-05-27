<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Associate;
use App\Models\Client;
use App\Models\Payment;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function associate(Request $request)
    {
        $selectedAssociateId = (int) $request->input('associate_id', 0);

        $associateOptions = Associate::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name']);

        $rows = Associate::query()
            ->withCount(['clients', 'certificates'])
            ->withSum('invoices as total_payments', 'total_amount')
            ->withSum('invoices as completed_payments', 'paid_amount')
            ->withSum('invoices as due_payments', 'pending_amount')
            ->when($selectedAssociateId > 0, fn ($q) => $q->where('id', $selectedAssociateId))
            ->orderBy('company_name')
            ->get();

        return view('admin.report.associate', [
            'title' => 'Associate Wise Report',
            'active_tab' => 'report',
            'sub_active_tab' => 'associate',
            'rows' => $rows,
            'associateOptions' => $associateOptions,
            'selectedAssociateId' => $selectedAssociateId,
        ]);
    }

    public function client(Request $request)
    {
        $selectedClientId = (int) $request->input('client_id', 0);

        $clientOptions = Client::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name']);

        $rows = Client::query()
            ->with(['associate:id,company_name'])
            ->withCount(['certificates'])
            ->withSum('invoices as total_payments', 'total_amount')
            ->withSum('invoices as completed_payments', 'paid_amount')
            ->withSum('invoices as due_payments', 'pending_amount')
            ->when($selectedClientId > 0, fn ($q) => $q->where('id', $selectedClientId))
            ->orderBy('company_name')
            ->get();

        return view('admin.report.client', [
            'title' => 'Client Wise Report',
            'active_tab' => 'report',
            'sub_active_tab' => 'client',
            'rows' => $rows,
            'clientOptions' => $clientOptions,
            'selectedClientId' => $selectedClientId,
        ]);
    }
}
