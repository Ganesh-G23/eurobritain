<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Associate;
use App\Models\Certificate;
use App\Models\CertificateApplication;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();

        $notUploadedQuery = Certificate::query()->where(function ($q) {
            $q->whereNull('certificate')->orWhere('certificate', '');
        });

        $expiringIn30Days = $this->buildExpiryList($today, $today->copy()->addDays(30), false);
        $expiringIn15Days = $this->buildExpiryList($today, $today->copy()->addDays(15), false);
        $expiredCertificates = $this->buildExpiryList(null, $today, true);

        return view('admin.dashboard', [
            'title' => 'Dashboard',
            'active_tab' => 'dashboard',
            'stats' => [
                'total_associates' => Associate::query()->count(),
                'total_clients' => Client::query()->count(),
                'total_applications' => CertificateApplication::query()->count(),
                'total_certificates' => Certificate::query()->count(),
                'certificates_not_uploaded' => (clone $notUploadedQuery)->count(),
                'total_invoice_amount' => (float) (Invoice::query()->sum('total_amount') ?? 0),
                'total_payment_received' => (float) (Payment::query()->sum('amount') ?? 0),
                'total_payment_dues' => (float) (Invoice::query()->sum('pending_amount') ?? 0),
            ],
            'latestApplications' => CertificateApplication::query()
                ->with([
                    'client:id,company_name',
                    'certificateType:id,description,code',
                ])
                ->orderByDesc('id')
                ->take(5)
                ->get(),
            'latestCertificates' => Certificate::query()
                ->with([
                    'client:id,company_name',
                    'associate:id,company_name',
                    'certificateType:id,description,code',
                ])
                ->orderByDesc('id')
                ->take(5)
                ->get(),
            'latestInvoices' => Invoice::query()
                ->with([
                    'client:id,company_name',
                    'associate:id,company_name',
                ])
                ->orderByDesc('id')
                ->take(5)
                ->get(),
            'latestPayments' => Payment::query()
                ->with([
                    'client:id,company_name',
                    'invoice:id,invoice_number',
                ])
                ->orderByDesc('id')
                ->take(5)
                ->get(),
            'expiringIn30Days' => $expiringIn30Days,
            'expiringIn15Days' => $expiringIn15Days,
            'expiredCertificates' => $expiredCertificates,
        ]);
    }

    public function logout()
    {
        session()->forget('admin');

        return redirect('admin/login');
    }

    /**
     * Build a merged collection of expiring/expired items from certificate_applications.
     *
     * Certificate type rows come from `date_of_expiry`; Audit type rows come from
     * `audit_expiry_date`. The two are combined and sorted by expiry date.
     *
     * @param  \Carbon\Carbon|null  $from  Lower bound (inclusive). When null, no lower bound is applied.
     * @param  \Carbon\Carbon  $to  Upper bound (inclusive for upcoming, exclusive for expired).
     * @param  bool  $expired  When true, returns rows whose expiry is strictly before $to.
     */
    protected function buildExpiryList(?Carbon $from, Carbon $to, bool $expired)
    {
        $relations = [
            'client:id,company_name,associate_id',
            'client.associate:id,company_name',
            'certificateType:id,description,code',
        ];

        $certRows = CertificateApplication::query()
            ->with($relations)
            ->whereNotNull('date_of_expiry')
            ->when($expired,
                fn ($q) => $q->where('date_of_expiry', '<', $to),
                fn ($q) => $q->where('date_of_expiry', '<=', $to)
                    ->when($from, fn ($q2) => $q2->where('date_of_expiry', '>=', $from)),
            )
            ->get()
            ->map(function (CertificateApplication $application) {
                return (object) [
                    'application' => $application,
                    'expiry_type' => 'Certificate',
                    'expiry_date' => $application->date_of_expiry,
                ];
            });

        $auditRows = CertificateApplication::query()
            ->with($relations)
            ->whereNotNull('audit_expiry_date')
            ->when($expired,
                fn ($q) => $q->where('audit_expiry_date', '<', $to),
                fn ($q) => $q->where('audit_expiry_date', '<=', $to)
                    ->when($from, fn ($q2) => $q2->where('audit_expiry_date', '>=', $from)),
            )
            ->get()
            ->map(function (CertificateApplication $application) {
                return (object) [
                    'application' => $application,
                    'expiry_type' => 'Audit',
                    'expiry_date' => $application->audit_expiry_date,
                ];
            });

        $merged = $certRows->concat($auditRows);

        $merged = $expired
            ? $merged->sortByDesc(fn ($row) => $row->expiry_date)
            : $merged->sortBy(fn ($row) => $row->expiry_date);

        return $merged->take(25)->values();
    }
}
