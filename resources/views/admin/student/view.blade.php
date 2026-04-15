@extends('admin.layouts.app')
@section('content')
    @php
        $tz = config('app.timezone', 'Asia/Kolkata');
        $student = $portal_user;
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-4 col-md-5 d-flex">
                <div class="card mb-4 w-100">
                    <div class="card-body text-center">
                        <div class="avatar avatar-xl mb-3 mx-auto">
                            <span class="avatar-initial rounded-circle bg-label-primary"
                                style="font-size: 3rem; width: 80px; height: 80px; line-height: 80px; display: flex; align-items: center; justify-content: center;">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </span>
                        </div>
                        <h4 class="mb-2">{{ $student->name }}</h4>
                        <p class="mb-4">
                            <span class="badge bg-label-primary">Student</span>
                        </p>
                    </div>
                    <hr class="my-0">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold d-block mb-2">
                                <i class="icon-base ti tabler-mail me-1"></i> Email
                            </label>
                            <p class="mb-0">{{ $student->email ?? '-' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold d-block mb-2">
                                <i class="icon-base ti tabler-phone me-1"></i> Phone
                            </label>
                            <p class="mb-0">{{ $student->phone ?? '-' }}</p>
                        </div>
                        @if($student->teachers->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block mb-2">
                                    <i class="icon-base ti tabler-school me-1"></i> Teachers
                                </label>
                                <p class="mb-0">{{ $student->teachers->pluck('name')->filter()->implode(', ') }}</p>
                            </div>
                        @endif
                        <div class="mb-0">
                            <p class="mb-1">
                                <span class="fw-semibold">Created At :</span>
                                {{ \Carbon\Carbon::parse($student->created_at)->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-grid gap-2">
                            <a href="{{ url('admin/student/edit?id=' . base64_encode($student->id)) }}" class="btn btn-primary">
                                <i class="icon-base ti tabler-edit me-1"></i> Edit Student
                            </a>
                            <a href="{{ url('admin/student') }}" class="btn btn-label-secondary">
                                <i class="icon-base ti tabler-arrow-left me-1"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-md-7 d-flex flex-column">
                <div class="card tab-panel-card flex-grow-1 d-flex flex-column">
                    <div class="card-header border-bottom">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview"
                                    role="tab" aria-selected="true">
                                    <i class="icon-base ti tabler-dashboard me-1"></i> Overview
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#teachers"
                                    role="tab" aria-selected="false">
                                    <i class="icon-base ti tabler-chalkboard me-1"></i> Teachers
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#classrooms"
                                    role="tab" aria-selected="false">
                                    <i class="icon-base ti tabler-building me-1"></i> Classrooms
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#batches"
                                    role="tab" aria-selected="false">
                                    <i class="icon-base ti tabler-users-group me-1"></i> Batches
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tests"
                                    role="tab" aria-selected="false">
                                    <i class="icon-base ti tabler-users-group me-1"></i> Tests
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content flex-grow-1">
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="card bg-label-primary">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">Total Teachers</h5>
                                                        <h3 class="mb-0">{{ $total_teachers ?? 0 }}</h3>
                                                    </div>
                                                    <div class="avatar avatar-lg">
                                                        <span class="avatar-initial rounded-circle bg-primary">
                                                            <i class="icon-base ti tabler-school"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-label-info">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">Total Classrooms</h5>
                                                        <h3 class="mb-0">{{ $total_classrooms ?? 0 }}</h3>
                                                    </div>
                                                    <div class="avatar avatar-lg">
                                                        <span class="avatar-initial rounded-circle bg-info">
                                                            <i class="icon-base ti tabler-building"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-label-success">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">Total Batches</h5>
                                                        <h3 class="mb-0">{{ $total_batches ?? 0 }}</h3>
                                                    </div>
                                                    <div class="avatar avatar-lg">
                                                        <span class="avatar-initial rounded-circle bg-success">
                                                            <i class="icon-base ti tabler-users-group"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-label-success">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">Total Tests</h5>
                                                        <h3 class="mb-0">{{ $test_count ?? 0 }}</h3>
                                                    </div>
                                                    <div class="avatar avatar-lg">
                                                        <span class="avatar-initial rounded-circle bg-success">
                                                            <i class="icon-base ti tabler-users-group"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="teachers" role="tabpanel">
                            <div class="card-body">
                                <div class="row g-4">
                                    @if(count($view_teachers ?? []) > 0)
                                        @foreach($view_teachers as $teacher)
                                            <div class="col-md-6 col-lg-4">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-start gap-3 mb-3">
                                                            <div class="avatar flex-shrink-0">
                                                                <span class="avatar-initial rounded-circle bg-label-primary"
                                                                    style="width: 3rem; height: 3rem; font-size: 1.25rem; display: flex; align-items: center; justify-content: center;">
                                                                    {{ strtoupper(substr($teacher->name, 0, 1)) }}
                                                                </span>
                                                            </div>
                                                            <div class="flex-grow-1 min-w-0 mt-2">
                                                                <h5 class="card-title mb-1 text-truncate">{{ $teacher->name }}</h5>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2 small">
                                                            <div class="d-flex align-items-start mb-1">
                                                                <i class="icon-base ti tabler-mail me-2 text-primary"></i>
                                                                <span class="text-muted text-break">{{ $teacher->email ?? '—' }}</span>
                                                            </div>
                                                            <div class="d-flex align-items-start mb-1">
                                                                <i class="icon-base ti tabler-phone me-2 text-primary"></i>
                                                                <span class="text-muted">{{ $teacher->phone ?? '—' }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-3">
                                                            <i class="icon-base ti tabler-calendar me-2 text-info"></i>
                                                            <span class="text-muted small">
                                                                {{ \Carbon\Carbon::parse($teacher->created_at)->setTimezone($tz)->format('d-m-Y') }}
                                                            </span>
                                                        </div>
                                                        <a href="{{ url('admin/teacher/view?id=' . base64_encode($teacher->id)) }}"
                                                            class="btn btn-sm btn-label-primary w-100">
                                                            <i class="icon-base ti tabler-eye me-1"></i> View teacher
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="col-12">
                                            <div class="text-center py-5">
                                                <i class="icon-base ti tabler-chalkboard text-muted" style="font-size: 3rem;"></i>
                                                <p class="text-muted mt-3 mb-0">No teachers linked to this student</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="classrooms" role="tabpanel">
                            <div class="card-body">
                                <div class="row g-4">
                                    @if(count($view_classrooms) > 0)
                                        @foreach($view_classrooms as $classroom)
                                            <div class="col-md-6 col-lg-4">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                                            <h5 class="card-title mb-0">{{ $classroom->name }}</h5>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="icon-base ti tabler-calendar me-2 text-info"></i>
                                                            <span class="text-muted">
                                                                {{ \Carbon\Carbon::parse($classroom->created_at)->setTimezone($tz)->format('d-m-Y') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="col-12">
                                            <div class="text-center py-5">
                                                <i class="icon-base ti tabler-building text-muted" style="font-size: 3rem;"></i>
                                                <p class="text-muted mt-3">No classrooms found</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="batches" role="tabpanel">
                            <div class="card-body">
                                <div class="row g-3">
                                    @if(count($view_batches) > 0)
                                        @foreach($view_batches as $batch)
                                            <div class="col-auto">
                                                <div class="card batch-card">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="card-title mb-0 small"><strong>{{ $batch->name }}</strong></h6>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="mb-1">
                                                                <strong>Classroom:</strong>
                                                                {{ optional($batch->classroom)->name ?? '-' }}
                                                            </div>
                                                            <small class="text-muted d-block mb-1">
                                                                <strong>Status:</strong>
                                                                <span
                                                                    class="badge bg-label-{{ ($batch->status ?? '') == 'active' ? 'success' : (($batch->status ?? '') == 'pending' ? 'warning' : 'secondary') }} badge-sm">
                                                                    {{ $batch->status ? ucfirst((string) $batch->status) : '—' }}
                                                                </span>
                                                            </small>
                                                            @if($batch->schedule)
                                                                <small class="text-muted d-block">
                                                                    <i class="icon-base ti tabler-calendar me-1"></i>
                                                                    {{ $batch->schedule }}
                                                                </small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="col-12">
                                            <div class="card h-100 border-dashed">
                                                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center"
                                                    style="min-height: 220px;">
                                                    <div class="mb-3">
                                                        <i class="icon-base ti tabler-users-group text-muted" style="font-size: 3rem;"></i>
                                                    </div>
                                                    <h6 class="mb-2">No batches found</h6>
                                                    <p class="text-muted small mb-0">No batches assigned to this student.</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tests" role="tabpanel">
                            <div class="card-body">
                                <div class="row g-3">
                                    @if(count($view_tests) > 0)
                                        @foreach($view_tests as $test)
                                            <div class="col-auto">
                                                <div class="card batch-card">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="card-title mb-0 small"><strong>{{ $test->batch->name }}</strong></h6>
                                                        </div>
                                                        <div class="mb-2">
                                                            <div class="mb-1">
                                                                <strong>Test Name:</strong>
                                                                {{ $test->exam_name ?? '-' }}
                                                            </div>
                                                            @php
                                                                $markRow = $test->marks->first();
                                                                $absRow = $test->markAbsences->first();
                                                                $maxM = $test->max_marks ?? '—';
                                                                if ($absRow) {
                                                                    $mode = (int) ($absRow->value !== null && $absRow->value !== ''
                                                                        ? $absRow->value
                                                                        : \App\Models\TeacherSetting::COUNT_AS_ZERO);
                                                                    $marksLabel =
                                                                        $mode === \App\Models\TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE
                                                                            ? 'A'
                                                                            : 'A(0)';
                                                                } elseif ($markRow && $markRow->marks !== null && $markRow->marks !== '') {
                                                                    $marksLabel = $markRow->marks;
                                                                } else {
                                                                    $marksLabel = '—';
                                                                }
                                                            @endphp
                                                            <small class="text-muted d-block mb-1">
                                                                <strong>Marks:</strong>
                                                                <span class="badge bg-label-active badge-sm">
                                                                    {{ $marksLabel }}/{{ $maxM }}
                                                                </span>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="col-12">
                                            <div class="card h-100 border-dashed">
                                                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center"
                                                    style="min-height: 220px;">
                                                    <div class="mb-3">
                                                        <i class="icon-base ti tabler-users-group text-muted" style="font-size: 3rem;"></i>
                                                    </div>
                                                    <h6 class="mb-2">No tests found</h6>
                                                    <p class="text-muted small mb-0">No tests and marks assigned to this student.</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .border-dashed {
            border: 2px dashed var(--bs-border-color) !important;
        }

        .avatar-initial {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 1.125rem 0 rgba(47, 43, 61, 0.16);
        }

        .tab-panel-card {
            max-height: none;
            overflow: visible;
        }

        .tab-panel-card .tab-content {
            max-height: none;
            overflow: visible;
        }

        .tab-pane {
            min-height: auto;
        }

        .tab-content > .tab-pane {
            display: block;
        }

        .tab-content > .tab-pane:not(.active) {
            display: none;
        }

        .batch-card {
            width: 200px;
            height: 220px;
            min-width: 200px;
            min-height: 220px;
        }

        .batch-card .card-body {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
    </style>
@endsection
