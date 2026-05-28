@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <div class="d-flex gap-2">
                    <a href="{{ url('admin/certificate-type/edit/' . $details->id) }}" class="btn btn-primary btn-sm">
                        <i class="icon-base ti tabler-edit me-1"></i> Edit
                    </a>
                    <a href="{{ url('admin/certificate-type/list') }}" class="btn btn-label-secondary btn-sm">Back to list</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Name</label>
                        <p class="mb-0 fw-medium">{{ $details->name ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Code</label>
                        <p class="mb-0 fw-medium">{{ $details->code }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Prefix</label>
                        <p class="mb-0 fw-medium">{{ $details->prefix }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Type</label>
                        @php
                            $selectedTypes = (array) ($details->types ?? []);
                            $typeLabels = collect($selectedTypes)->map(function ($type) use ($type_options) {
                                return $type_options[$type] ?? $type;
                            })->all();
                        @endphp
                        <p class="mb-0 fw-medium">{{ !empty($typeLabels) ? implode(', ', $typeLabels) : '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Certificate Type</label>
                        <p class="mb-0 fw-medium">{{ $category_options[$details->category] ?? '—' }}</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted mb-1">Description</label>
                        <p class="mb-0 fw-medium">{{ $details->description ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Audit Period (Years)</label>
                        <p class="mb-0 fw-medium">{{ $details->audit_period ? $details->audit_period.' Year'.((int) $details->audit_period > 1 ? 's' : '') : '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Renewal Period (Years)</label>
                        <p class="mb-0 fw-medium">{{ $details->renewal_period ? $details->renewal_period.' Year'.((int) $details->renewal_period > 1 ? 's' : '') : '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Price</label>
                        <p class="mb-0 fw-medium">{{ number_format((float) $details->price, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
