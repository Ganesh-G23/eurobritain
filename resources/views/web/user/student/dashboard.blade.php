@extends('web.user.student.layouts.app')
@section('content')
    @php
        $ov = $student_dashboard_overview ?? [];
        $attRate = $ov['attendance_rate'] ?? null;
        $marksPct = $ov['overall_marks_pct'] ?? null;
        $marksExamCount = (int) ($ov['overall_marks_exams_count'] ?? 0);
        $upcomingDays = (int) ($ov['upcoming_events_days'] ?? 14);
        $upcomingCount = (int) ($ov['upcoming_events_count'] ?? 0);
        $assignLike = (int) ($ov['upcoming_assignment_like_count'] ?? 0);
    @endphp
    <div class="container-xxl flex-grow-1 pb-2 pt-0">
        @if (session('error'))
            <div class="alert alert-warning">{{ session('error') }}</div>
        @endif
        <div class="row g-4 mb-2">
            <div class="col-12">
                <h5 class="mb-1">Overview</h5>
                <p class="mb-0 text-body-secondary small">
                    @if ($teacher)
                        Quick snapshot for classes with <span class="fw-medium text-heading">{{ $teacher->name }}</span>
                    @else
                        Quick snapshot for your portal
                    @endif
                </p>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border shadow-none">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <p class="mb-1 text-body-secondary small">Overall marks</p>
                                @if ($marksPct !== null)
                                    <h3 class="mb-0 text-heading">{{ $marksPct }}%</h3>
                                    <p class="mb-0 small text-body-secondary">Across {{ $marksExamCount }}
                                        graded {{ $marksExamCount === 1 ? 'item' : 'items' }}</p>
                                @else
                                    <h3 class="mb-0 text-heading">—</h3>
                                    <p class="mb-0 small text-body-secondary">No graded results in your batches yet</p>
                                @endif
                            </div>
                            <span class="badge bg-label-primary rounded p-2">
                                <i class="icon-base ti tabler-chart-bar icon-md"></i>
                            </span>
                        </div>
                        <a href="{{ url('user/student/classrooms') }}" class="btn btn-sm btn-label-primary">View classrooms &amp; tests</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border shadow-none">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <p class="mb-1 text-body-secondary small">Attendance</p>
                                @if ($attRate !== null)
                                    <h3 class="mb-0 text-heading">{{ $attRate }}%</h3>
                                    <p class="mb-0 small text-body-secondary">
                                        {{ (int) ($ov['attendance_present'] ?? 0) }} present ·
                                        {{ (int) ($ov['attendance_late'] ?? 0) }} leave ·
                                        {{ (int) ($ov['attendance_absent'] ?? 0) }} absent
                                    </p>
                                @else
                                    <h3 class="mb-0 text-heading">—</h3>
                                    <p class="mb-0 small text-body-secondary">No attendance rows for your batches yet</p>
                                @endif
                            </div>
                            <span class="badge bg-label-success rounded p-2">
                                <i class="icon-base ti tabler-user-check icon-md"></i>
                            </span>
                        </div>
                        <a href="{{ url('user/student/attendance') }}" class="btn btn-sm btn-label-success">Open attendance</a>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-xl-4">
                <div class="card h-100 border shadow-none">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <p class="mb-1 text-body-secondary small">Calendar (next {{ $upcomingDays }} days)</p>
                                @if ($upcomingCount > 0)
                                    <h3 class="mb-0 text-heading">{{ $upcomingCount }}</h3>
                                    <p class="mb-0 small text-body-secondary">
                                        @if ($assignLike > 0)
                                            Including {{ $assignLike }} that look like assignments, deadlines, or
                                            quizzes
                                        @else
                                            Teacher events &amp; your personal items
                                        @endif
                                    </p>
                                @else
                                    <h3 class="mb-0 text-heading">0</h3>
                                    <p class="mb-0 small text-body-secondary">Nothing scheduled in this window</p>
                                @endif
                            </div>
                            <span class="badge bg-label-info rounded p-2">
                                <i class="icon-base ti tabler-calendar-event icon-md"></i>
                            </span>
                        </div>
                        <a href="{{ url('user/student/events') }}" class="btn btn-sm btn-label-info">Open calendar</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- <div class="row g-6 mb-4">
            <div class="col-12">
                <h4 class="mb-1">{{ $title ?? 'Student Dashboard' }}</h4>
                @if ($teacher)
    <p class="mb-0 text-body-secondary">
                        Selected teacher: <span class="fw-semibold text-heading">{{ $teacher->name }}</span>
                    </p>
    @endif
            </div>
        </div> -->

        <!-- <div class="row g-6 mb-4">
            <div class="col-lg-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base ti tabler-school icon-28px"></i>
                                </span>
                            </div>
                            <h4 class="mb-0">{{ (int) ($total_classrooms ?? 0) }}</h4>
                        </div>
                        <p class="mb-1">Classrooms</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base ti tabler-stack icon-28px"></i>
                                </span>
                            </div>
                            <h4 class="mb-0">{{ (int) ($total_batches ?? 0) }}</h4>
                        </div>
                        <p class="mb-1">Batches</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base ti tabler-users icon-28px"></i>
                                </span>
                            </div>
                            <h4 class="mb-0">{{ (int) ($total_students ?? 0) }}</h4>
                        </div>
                        <p class="mb-1">Students in these classrooms</p>
                    </div>
                </div>
            </div>
        </div> -->
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">My Classrooms</h5>
                        <p class="mb-0 text-body-secondary small">Classrooms available under your selected teacher</p>
                    </div>
                </div>

                <div class="row g-4">
                    @forelse (($student_classrooms ?? collect()) as $classroom)
                        <div class="col-md-6 col-xl-4">
                            <div class="card  h-100 position-relative">
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="avatar me-3 flex-shrink-0">
                                            <span class="avatar-initial rounded bg-label-primary">
                                                <i class="icon-base ti tabler-school icon-28px"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 min-w-0 pt-1">
                                            <h5 class="mb-0 text-heading text-truncate">{{ $classroom->name }}</h5>
                                            <small class="text-body-secondary">Classroom</small>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs me-2">
                                                    <span class="avatar-initial rounded bg-label-success">
                                                        <i class="icon-base ti tabler-stack icon-18px"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0">{{ (int) ($classroom->total_batches ?? 0) }}</h5>
                                                    <small class="text-body-secondary">Batches</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs me-2">
                                                    <span class="avatar-initial rounded bg-label-info">
                                                        <i class="icon-base ti tabler-users icon-18px"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0">{{ (int) ($classroom->total_students ?? 0) }}</h5>
                                                    <small class="text-body-secondary">Students</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ url('user/student/classroom/' . $classroom->id) }}" class="stretched-link"
                                    aria-label="View classroom {{ $classroom->name }}"></a>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded-circle bg-label-secondary">
                                            <i class="icon-base ti tabler-school icon-28px"></i>
                                        </span>
                                    </div>
                                    <h6 class="mb-2">No classrooms available</h6>
                                    <p class="text-body-secondary small mb-0">
                                        You are not mapped to any classroom for this teacher yet.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quick press</h5>
                        <p class="card-subtitle mb-0">Jump to the rest of your portal</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6 col-lg-3">
                                <a href="{{ url('user/student/classrooms') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-school icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Classrooms</span>
                                </a>
                            </div>
                            <div class="col-6 col-lg-3">
                                <a href="{{ url('user/student/events') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-calendar icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Events</span>
                                </a>
                            </div>
                            <div class="col-6 col-lg-3">
                                <a href="{{ url('user/student/leave') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-calendar-off icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Leave</span>
                                </a>
                            </div>
                            <div class="col-6 col-lg-3">
                                <a href="{{ url('user/student/complaints') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-message-report icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Complaints</span>
                                </a>
                            </div>
                            <div class="col-6 col-lg-3">
                                <a href="{{ url('user/student/attendance') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-user-check icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Attendance</span>
                                </a>
                            </div>
                            <div class="col-6 col-lg-3">
                                <a href="{{ url('user/student/report') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-file-analytics icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Reports</span>
                                </a>
                            </div>
                            <div class="col-6 col-lg-3">
                                <a href="{{ url('user/profile') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-user icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Profile</span>
                                </a>
                            </div>
                            <div class="col-6 col-lg-3">
                                <a href="{{ url('user/security') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-shield-lock icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Security</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
