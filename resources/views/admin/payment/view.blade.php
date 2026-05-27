@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <div class="d-flex gap-2">
                    <a href="{{ url('admin/payment/edit/' . $details->id) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                    <a href="{{ url('admin/payment/list') }}" class="btn btn-label-secondary btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Invoice:</strong>
                        @if ($details->invoice)
                            <a href="{{ url('admin/invoice/view/' . $details->invoice_id) }}">
                                {{ $details->invoice->invoice_number }}
                            </a>
                        @else
                            —
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Associate:</strong> {{ $details->associate->company_name ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Client:</strong> {{ $details->client->company_name ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Amount:</strong> {{ number_format((float) $details->amount, 2) }}
                    </div>
                    <div class="col-md-6">
                        <strong>Payment Date:</strong> {{ $details->payment_date?->format('d M Y') ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Recorded On:</strong> {{ $details->created_at?->format('d M Y H:i') ?? '—' }}
                    </div>
                    <div class="col-12">
                        <strong>Admin Note:</strong>
                        <p class="mb-0 mt-1">{{ $details->admin_note ?: '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
