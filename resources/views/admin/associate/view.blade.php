@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <div class="d-flex gap-2">
                    <a href="{{ url('admin/associate/edit/' . $details->id) }}" class="btn btn-primary btn-sm">
                        <i class="icon-base ti tabler-edit me-1"></i> Edit
                    </a>
                    <a href="{{ url('admin/associate/list') }}" class="btn btn-label-secondary btn-sm">Back to list</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Company Name</label>
                        <p class="mb-0 fw-medium">{{ $details->company_name }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Contact Person</label>
                        <p class="mb-0 fw-medium">{{ $details->contact_person }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Contact Email</label>
                        <p class="mb-0 fw-medium">{{ $details->contact_email }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Contact Mobile</label>
                        <p class="mb-0 fw-medium">{{ $details->contact_mobile }}</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted mb-1">Address</label>
                        <p class="mb-0 fw-medium">{{ $details->address ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Country</label>
                        <p class="mb-0 fw-medium">{{ $details->country->name ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">State</label>
                        <p class="mb-0 fw-medium">{{ $details->state->name ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">City</label>
                        <p class="mb-0 fw-medium">{{ $details->city }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Pincode</label>
                        <p class="mb-0 fw-medium">{{ $details->pincode }}</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted mb-1">Certificate Types</label>
                        @if ($details->certificateTypes->isEmpty())
                            <p class="mb-0 fw-medium">-</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Certificate Type</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($details->certificateTypes as $certificateType)
                                            <tr>
                                                <td>{{ $certificateType->description ?? '-' }}</td>
                                                <td class="text-end">{{ number_format((float) $certificateType->pivot->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
