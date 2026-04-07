@extends('web.user.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-4">
            <div class="col-lg-6 col-md-6">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="avatar avatar-xl mb-3 mx-auto">
                            <span class="avatar-initial rounded-circle bg-label-primary"
                                style="font-size: 2rem; width: 72px; height: 72px; line-height: 72px; display: flex; align-items: center; justify-content: center;">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </span>
                        </div>
                        <h5 class="mb-1">{{ $student->name }}</h5>
                        <span class="badge bg-label-primary">Student</span>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Student ID</label>
                                <div class="fw-semibold">#{{ $student->id }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Email</label>
                                <div class="fw-semibold">{{ $student->email ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Phone</label>
                                <div class="fw-semibold">{{ $student->phone ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Classroom</label>
                                <div class="fw-semibold">
                                    {{ $student->classroom->name ?? ($student->batch->classroom->name ?? '-') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Batch</label>
                                <div class="fw-semibold">{{ $student->batch->name ?? '-' }}</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label text-muted mb-1">Mapped Teachers</label>
                                <div class="fw-semibold">
                                    @if(isset($student_teachers) && count($student_teachers) > 0)
                                        {{ $student_teachers->pluck('name')->implode(', ') }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Created At</label>
                                <div class="fw-semibold">{{ \Carbon\Carbon::parse($student->created_at)->format('d-m-Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Parent Details</h5>
                        <a href="{{ url('user/teacher/students') }}" class="btn btn-label-secondary btn-sm">
                            <i class="icon-base ti tabler-arrow-left me-1"></i> Back to Students
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @php
                                $parent = null;
                                if (!empty($student->parent_id)) {
                                    $parent = \App\Models\PortalUser::find($student->parent_id);
                                }
                            @endphp
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Parent ID</label>
                                <div class="fw-semibold">{{ $parent->id ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Name</label>
                                <div class="fw-semibold">{{ $parent->name ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Email</label>
                                <div class="fw-semibold">{{ $parent->email ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Phone</label>
                                <div class="fw-semibold">{{ $parent->phone ?? '-' }}</div>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
