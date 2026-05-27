@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ url('admin/invoice/edit/' . $details->id) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                    <a href="{{ url('admin/invoice/pdf/' . $details->id) }}" target="_blank"
                        class="btn btn-sm btn-outline-primary">PDF</a>
                    <a href="{{ url('admin/payment/add?invoice_id=' . $details->id) }}"
                        class="btn btn-sm btn-outline-success">Add Payment</a>
                    <a href="{{ url('admin/invoice/list') }}" class="btn btn-label-secondary btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <strong>Invoice Number:</strong> {{ $details->invoice_number }}
                    </div>
                    <div class="col-md-6">
                        <strong>Invoice Date:</strong> {{ $details->invoice_date?->format('d M Y') ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Associate:</strong> {{ $details->associate->company_name ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Client:</strong> {{ $details->client->company_name ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Total Amount:</strong> {{ number_format((float) $details->total_amount, 2) }}
                    </div>
                    <div class="col-md-6">
                        <strong>Paid Amount:</strong> {{ number_format((float) $details->paid_amount, 2) }}
                    </div>
                    <div class="col-md-6">
                        <strong>Pending Amount:</strong> {{ number_format((float) $details->pending_amount, 2) }}
                    </div>
                    <div class="col-12">
                        <strong>Admin Note:</strong>
                        <p class="mb-0 mt-1">{{ $details->admin_note ?: '—' }}</p>
                    </div>
                </div>

                <h6 class="mb-3">Certificates</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Certificate Number</th>
                                <th>Certificate Type</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($certificates as $index => $cert)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $cert->certificate_number }}</td>
                                    <td>{{ $cert->certificateType->description ?? $cert->certificateType->code ?? '—' }}</td>
                                    <td class="text-end">{{ number_format((float) $cert->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No certificates linked.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($certificates->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total Amount</th>
                                    <th class="text-end">{{ number_format((float) $details->total_amount, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Paid Amount</th>
                                    <th class="text-end">{{ number_format((float) $details->paid_amount, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Pending Amount</th>
                                    <th class="text-end">{{ number_format((float) $details->pending_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
