@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <div class="d-flex gap-2">
                    <a href="{{ url('admin/auditor/edit/' . $details->id) }}" class="btn btn-primary btn-sm">
                        <i class="icon-base ti tabler-edit me-1"></i> Edit
                    </a>
                    <a href="{{ url('admin/auditor/list') }}" class="btn btn-label-secondary btn-sm">Back to list</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Name</label>
                        <p class="mb-0 fw-medium">{{ $details->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Email</label>
                        <p class="mb-0 fw-medium">{{ $details->email }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Phone</label>
                        <p class="mb-0 fw-medium">{{ $details->phone ?: '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Last Login</label>
                        <p class="mb-0 fw-medium">
                            @if ($details->last_login_at)
                                {{ \Carbon\Carbon::parse($details->last_login_at)->format('d M Y, h:i A') }}
                            @else
                                Never
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted mb-1">Created On</label>
                        <p class="mb-0 fw-medium">
                            {{ $details->created_at ? $details->created_at->format('d M Y, h:i A') : '—' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
