@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- Section A: KPI grid (4 x 2) --}}
        @php
            $kpiCards = [
                [
                    'label' => 'Total Associates',
                    'value' => number_format($stats['total_associates']),
                    'icon' => 'tabler-users',
                    'badge' => 'bg-label-primary',
                    'url' => url('admin/associate/list'),
                ],
                [
                    'label' => 'Total Clients',
                    'value' => number_format($stats['total_clients']),
                    'icon' => 'tabler-building',
                    'badge' => 'bg-label-info',
                    'url' => url('admin/client/list'),
                ],
                [
                    'label' => 'Total Application',
                    'value' => number_format($stats['total_applications']),
                    'icon' => 'tabler-file-certificate',
                    'badge' => 'bg-label-success',
                    'url' => url('admin/certificate-application/list'),
                ],
                [
                    'label' => 'Total Certificate',
                    'value' => number_format($stats['total_certificates']),
                    'icon' => 'tabler-award',
                    'badge' => 'bg-label-warning',
                    'url' => url('admin/certificate/list'),
                ],
                [
                    'label' => 'Generated, Not Uploaded',
                    'value' => number_format($stats['certificates_not_uploaded']),
                    'icon' => 'tabler-cloud-upload',
                    'badge' => 'bg-label-secondary',
                    'url' => url('admin/certificate/list?uploaded=no'),
                ],
                [
                    'label' => 'Total Invoice',
                    'value' => '₹ ' . number_format($stats['total_invoice_amount'], 2),
                    'icon' => 'tabler-file-invoice',
                    'badge' => 'bg-label-primary',
                    'url' => url('admin/invoice/list'),
                ],
                [
                    'label' => 'Total Payment Received',
                    'value' => '₹ ' . number_format($stats['total_payment_received'], 2),
                    'icon' => 'tabler-cash',
                    'badge' => 'bg-label-success',
                    'url' => url('admin/payment/list'),
                ],
                [
                    'label' => 'Total Payment Dues',
                    'value' => '₹ ' . number_format($stats['total_payment_dues'], 2),
                    'icon' => 'tabler-alert-triangle',
                    'badge' => 'bg-label-danger',
                    'url' => url('admin/invoice/list'),
                ],
            ];
        @endphp
        <div class="row g-4 mb-4">
            @foreach ($kpiCards as $card)
                <div class="col-12 col-sm-6 col-xl-3">
                    <a href="{{ $card['url'] }}" class="text-reset text-decoration-none d-block h-100">
                        <div class="card h-100 dashboard-kpi-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <span class="d-block mb-1 text-body-secondary">{{ $card['label'] }}</span>
                                        <h4 class="mb-0">{{ $card['value'] }}</h4>
                                    </div>
                                    <span class="badge {{ $card['badge'] }} rounded p-2">
                                        <i class="icon-base ti {{ $card['icon'] }} icon-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Section B: 2 x 2 top-5 lists --}}
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">New Application List</h5>
                        <a href="{{ url('admin/certificate-application/list') }}" class="btn btn-sm btn-label-primary">View all</a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Application</th>
                                        <th>Company</th>
                                        <th>Certificate Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($latestApplications as $row)
                                        <tr>
                                            <td>{{ $row->application_number ?: '—' }}</td>
                                            <td>{{ $row->company_name ?: ($row->client->company_name ?? '—') }}</td>
                                            <td>{{ $row->certificateType->description ?? $row->certificateType->code ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">No applications yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">New Certificate List</h5>
                        <a href="{{ url('admin/certificate/list') }}" class="btn btn-sm btn-label-primary">View all</a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Certificate</th>
                                        <th>Client</th>
                                        <th>Issue Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($latestCertificates as $row)
                                        <tr>
                                            <td>{{ $row->certificate_number }}</td>
                                            <td>{{ $row->client->company_name ?? '—' }}</td>
                                            <td>{{ $row->issue_date?->format('d M Y') ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">No certificates yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">New Invoice Created</h5>
                        <a href="{{ url('admin/invoice/list') }}" class="btn btn-sm btn-label-primary">View all</a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Client</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($latestInvoices as $row)
                                        <tr>
                                            <td>{{ $row->invoice_number }}</td>
                                            <td>{{ $row->client->company_name ?? '—' }}</td>
                                            <td>₹ {{ number_format((float) $row->total_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">No invoices yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">New Payment Added</h5>
                        <a href="{{ url('admin/payment/list') }}" class="btn btn-sm btn-label-primary">View all</a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($latestPayments as $row)
                                        <tr>
                                            <td>{{ $row->invoice->invoice_number ?? '—' }}</td>
                                            <td>{{ $row->client->company_name ?? '—' }}</td>
                                            <td>₹ {{ number_format((float) $row->amount, 2) }}</td>
                                            <td>{{ $row->payment_date?->format('d M Y') ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">No payments yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section C: Expiry tabs --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Certificate Expiry</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#expiry-30"
                            role="tab" aria-controls="expiry-30" aria-selected="true">
                            Expired in 30 Days
                            <span class="badge rounded-pill bg-label-info ms-1">{{ $expiringIn30Days->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#expiry-15"
                            role="tab" aria-controls="expiry-15" aria-selected="false">
                            Expired in 15 Days
                            <span class="badge rounded-pill bg-label-warning ms-1">{{ $expiringIn15Days->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#expired"
                            role="tab" aria-controls="expired" aria-selected="false">
                            Expired Certificates
                            <span class="badge rounded-pill bg-label-danger ms-1">{{ $expiredCertificates->count() }}</span>
                        </button>
                    </li>
                </ul>
                <div class="tab-content pt-4">
                    @foreach ([
                        ['id' => 'expiry-30', 'active' => true, 'rows' => $expiringIn30Days, 'dateBadge' => 'bg-label-info'],
                        ['id' => 'expiry-15', 'active' => false, 'rows' => $expiringIn15Days, 'dateBadge' => 'bg-label-warning'],
                        ['id' => 'expired', 'active' => false, 'rows' => $expiredCertificates, 'dateBadge' => 'bg-label-danger'],
                    ] as $tab)
                        <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['id'] }}" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Application</th>
                                            <th>Company</th>
                                            <th>Associate</th>
                                            <th>Certificate Type</th>
                                            <th>Type</th>
                                            <th>Expiry</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($tab['rows'] as $row)
                                            @php $application = $row->application; @endphp
                                            <tr>
                                                <td>{{ $application->application_number ?: '—' }}</td>
                                                <td>{{ $application->company_name ?: ($application->client->company_name ?? '—') }}</td>
                                                <td>{{ $application->client->associate->company_name ?? '—' }}</td>
                                                <td>{{ $application->certificateType->description ?? $application->certificateType->code ?? '—' }}</td>
                                                <td>
                                                    @if ($row->expiry_type === 'Audit')
                                                        <span class="badge bg-label-warning">Audit</span>
                                                    @else
                                                        <span class="badge bg-label-primary">Certificate</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge {{ $tab['dateBadge'] }}">
                                                        {{ $row->expiry_date?->format('d M Y') ?? '—' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ url('admin/certificate-application/view/' . $application->id) }}"
                                                        class="btn btn-sm btn-outline-info">View</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">No records.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <style>
        .dashboard-kpi-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        a:hover > .dashboard-kpi-card {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
        }
    </style>
@endsection
