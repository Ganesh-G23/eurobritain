@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-4 align-items-stretch">
            <!-- Left - Student profile (same pattern as teacher view) -->
            <div class="col-lg-4 col-md-5 d-flex">
                <div class="card mb-0 w-100 h-100 d-flex flex-column">
                    <div class="card-body text-center">
                        <div class="avatar avatar-xl mb-3 mx-auto">
                            <span class="avatar-initial rounded-circle bg-label-primary"
                                style="font-size: 3rem; width: 80px; height: 80px; line-height: 80px; display: flex; align-items: center; justify-content: center;">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </span>
                        </div>
                        <h4 class="mb-2">{{ $student->name }}</h4>
                        <p class="mb-0">
                            <span class="badge bg-label-primary">Student</span>
                        </p>
                    </div>
                    <hr class="my-0">
                    <div class="card-body flex-grow-1">
                        <div class="mb-3">
                            <label class="form-label fw-semibold d-block mb-2">
                                <i class="icon-base ti tabler-mail me-1"></i> Email
                            </label>
                            <p class="mb-0">{{ $student->email ?? '-' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold d-block mb-2">
                                <i class="icon-base ti tabler-phone me-1"></i> Phone Number
                            </label>
                            <p class="mb-0">{{ $student->phone ?? '-' }}</p>
                        </div>
                        @php
                            $maps = isset($student_classroom_maps) ? $student_classroom_maps : collect();
                        @endphp
                        @if($maps->count() > 0)
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block mb-2">
                                    <i class="icon-base ti tabler-school me-1"></i> Classrooms &amp; batches
                                </label>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($maps as $row)
                                        <li class="mb-1">
                                            <span class="text-body">{{ optional($row->classroom)->name ?? '—' }}</span>
                                            <span class="text-muted"> · </span>
                                            <span class="text-body">{{ optional($row->batch)->name ?? '—' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block mb-2">
                                    <i class="icon-base ti tabler-school me-1"></i> Classroom
                                </label>
                                <p class="mb-0">{{ $student->classroom->name ?? ($student->batch->classroom->name ?? '-') }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block mb-2">
                                    <i class="icon-base ti tabler-stack me-1"></i> Batch
                                </label>
                                <p class="mb-0">{{ $student->batch->name ?? '-' }}</p>
                            </div>
                        @endif

                    </div>
                    <div class="card-body pt-0 mt-auto">
                        <div class="d-grid gap-2">
                            @if ($teacher)
                                <a href="{{ url('admin/teacher/view?id=' . base64_encode($teacher->id)) }}"
                                    class="btn btn-primary">
                                    <i class="icon-base ti tabler-arrow-left me-1"></i> Back to Teacher
                                </a>
                            @else
                                <a href="{{ url('admin/teacher') }}" class="btn btn-primary">
                                    <i class="icon-base ti tabler-arrow-left me-1"></i> Back to Teachers
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right - Parent details (stretches to match left column height) -->
            <div class="col-lg-8 col-md-7 d-flex flex-column">
                <div class="card mb-0 w-100 h-100 flex-grow-1 d-flex flex-column">
                    <div class="card-header">
                        <h5 class="mb-0">Parent Details</h5>
                    </div>
                    @php
                        $parent = null;
                        if (!empty($student->parent_id)) {
                            $parent = \App\Models\PortalUser::find($student->parent_id);
                        }
                    @endphp
                    <div class="card-body flex-grow-1">
                        <div class="row g-4">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold d-block mb-2">
                                    <i class="icon-base ti tabler-hash me-1"></i> Parent ID
                                </label>
                                <p class="mb-0">{{ $parent->id ?? '-' }}</p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold d-block mb-2">
                                    <i class="icon-base ti tabler-user me-1"></i> Name
                                </label>
                                <p class="mb-0">{{ $parent->name ?? '-' }}</p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold d-block mb-2">
                                    <i class="icon-base ti tabler-mail me-1"></i> Email
                                </label>
                                <p class="mb-0">{{ $parent->email ?? '-' }}</p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold d-block mb-2">
                                    <i class="icon-base ti tabler-phone me-1"></i> Phone
                                </label>
                                <p class="mb-0">{{ $parent->phone ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
