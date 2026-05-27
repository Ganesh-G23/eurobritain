@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <div class="d-flex gap-2">
                    <a href="{{ url('admin/certificate/edit/' . $details->id) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                    <a href="{{ url('admin/certificate/upload/' . $details->id) }}"
                        class="btn btn-sm btn-outline-primary">Add Certificate</a>
                    <a href="{{ url('admin/certificate/list') }}" class="btn btn-label-secondary btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Certificate Number:</strong> {{ $details->certificate_number }}
                    </div>
                    <div class="col-md-6">
                        <strong>Associate:</strong> {{ $details->associate->company_name ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Client:</strong> {{ $details->client->company_name ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Certificate Type:</strong>
                        {{ $details->certificateType->description ?? $details->certificateType->code ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Amount:</strong> {{ number_format((float) $details->amount, 2) }}
                    </div>
                    <div class="col-md-6">
                        <strong>Issue Date:</strong> {{ $details->issue_date?->format('d M Y') ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Initial Certificate Granted On:</strong>
                        {{ $details->initial_certificate_granted_on?->format('d M Y') ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Date of Expiry:</strong> {{ $details->date_of_expiry?->format('d M Y') ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Latest Audit Date:</strong> {{ $details->latest_audit_date?->format('d M Y') ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Application Audit Expiry:</strong>
                        {{ $details->certificateApplication->audit_expiry_date?->format('d M Y') ?? '—' }}
                    </div>
                    <div class="col-12">
                        <strong>Scope:</strong>
                        <p class="mb-0 mt-1">{{ $details->scope ?: '—' }}</p>
                    </div>
                    <div class="col-12">
                        <strong>Admin Note:</strong>
                        <p class="mb-0 mt-1">{{ $details->admin_note ?: '—' }}</p>
                    </div>
                    <div class="col-12">
                        <strong>Certificate Image:</strong>
                        @if ($details->certificate_url)
                            @php
                                $certExt = strtolower(pathinfo($details->certificate, PATHINFO_EXTENSION));
                                $certIsImage = in_array($certExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
                            @endphp
                            @if ($certIsImage)
                                <div class="mt-1">
                                    <a href="{{ $details->certificate_url }}" target="_blank">
                                        <img src="{{ $details->certificate_url }}" alt="Certificate" class="img-thumbnail"
                                            style="max-height:200px; max-width:320px; object-fit:contain;">
                                    </a>
                                </div>
                            @else
                                <p class="mb-0 mt-1">
                                    <a href="{{ $details->certificate_url }}" target="_blank"
                                        class="d-inline-flex align-items-center gap-1">
                                        <i class="icon-base ti tabler-file-text"></i>
                                        <span>View file ({{ strtoupper($certExt) ?: 'file' }})</span>
                                    </a>
                                </p>
                            @endif
                        @else
                            <p class="mb-0 mt-1 text-muted">Not uploaded</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
