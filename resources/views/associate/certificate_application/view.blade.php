@extends('associate.layouts.app')
@php
    $director = $details->director_details ?? [];
    $employee = $details->employee_details ?? [];
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <div class="d-flex gap-2">
                    <a href="{{ url('certificate-application/edit/' . $details->id) }}" class="btn btn-primary btn-sm">Edit</a>
                    <a href="{{ url('certificate-application/list') }}" class="btn btn-label-secondary btn-sm">Back to list</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Application</label>
                        <p class="mb-0 fw-medium">{{ $details->application_number ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Company Name</label>
                        <p class="mb-0 fw-medium">{{ $details->company_name }}</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted mb-1">Address</label>
                        <p class="mb-0 fw-medium">{{ $details->address ?: '—' }}</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted mb-1">Scope (In English)</label>
                        <p class="mb-0 fw-medium">{{ $details->scope ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Phone Number</label>
                        <p class="mb-0 fw-medium">{{ $details->contact_mobile ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Fax Number</label>
                        <p class="mb-0 fw-medium">{{ $details->fax_number ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Email</label>
                        <p class="mb-0 fw-medium">{{ $details->contact_email ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Website</label>
                        <p class="mb-0 fw-medium">{{ $details->website ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Communication Person</label>
                        <p class="mb-0 fw-medium">{{ $details->communication_person ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Management Representative</label>
                        <p class="mb-0 fw-medium">{{ $details->management_representative ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Top Manager</label>
                        <p class="mb-0 fw-medium">{{ $details->top_manager ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Top Management Mobile</label>
                        <p class="mb-0 fw-medium">{{ $details->top_management_mobile ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Certificate Type</label>
                        <p class="mb-0 fw-medium">{{ $details->certificateType->description ?? '—' }}</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted mb-1">Director / Partner / Proprietor</label>
                        <p class="mb-0 fw-medium">
                            {{ trim(($director['first_name'] ?? '') . ' ' . ($director['middle_name'] ?? '') . ' ' . ($director['last_name'] ?? '')) ?: '—' }}
                        </p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted mb-1">Employee Number</label>
                        <p class="mb-0 fw-medium">{{ $employee['employee_number'] ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted mb-1">Full Time</label>
                        <p class="mb-0 fw-medium">{{ $employee['full_time'] ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted mb-1">Part Time</label>
                        <p class="mb-0 fw-medium">{{ $employee['part_time'] ?? '—' }}</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted mb-1">Sites & Shifts</label>
                        @forelse ($details->address_shift_details ?? [] as $site)
                            <div class="border rounded p-3 mb-2">
                                <strong>{{ $site['address'] ?? '—' }}</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($site['shifts'] ?? [] as $shift)
                                        <li>{{ $shift['from'] ?? '' }} — {{ $shift['to'] ?? '' }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @empty
                            <p class="mb-0 fw-medium">—</p>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Subcontractor</label>
                        <p class="mb-0 fw-medium">{{ $details->subcontractor ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">In main Process</label>
                        <p class="mb-0 fw-medium">{{ $details->in_main_process ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Executive Personnel</label>
                        <p class="mb-0 fw-medium">{{ $details->executive_personnel ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">In Design</label>
                        <p class="mb-0 fw-medium">{{ $details->in_design ?: '—' }}</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted mb-1">Service Requested Audit Type</label>
                        <p class="mb-0 fw-medium">{{ !empty($auditTypeNames) ? implode(', ', $auditTypeNames) : '—' }}</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted mb-1">Application Document</label>
                        @if ($details->application_document_url)
                            @php
                                $appDocExt = strtolower(pathinfo($details->application_document, PATHINFO_EXTENSION));
                                $appDocIsImage = in_array($appDocExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
                            @endphp
                            @if ($appDocIsImage)
                                <div>
                                    <a href="{{ $details->application_document_url }}" target="_blank">
                                        <img src="{{ $details->application_document_url }}" alt="Application Document" class="img-thumbnail" style="max-height:200px; max-width:320px; object-fit:contain;">
                                    </a>
                                </div>
                            @else
                                <p class="mb-0">
                                    <a href="{{ $details->application_document_url }}" target="_blank" class="d-inline-flex align-items-center gap-1">
                                        <i class="icon-base ti tabler-file-text"></i>
                                        <span>View file ({{ strtoupper($appDocExt) ?: 'file' }})</span>
                                    </a>
                                </p>
                            @endif
                        @else
                            <p class="mb-0 fw-medium">—</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
