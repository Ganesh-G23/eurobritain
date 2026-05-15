@php
    $student = $student ?? null;
    $ov = $parent_student_overview ?? [];
    $attRate = $ov['attendance_rate'] ?? null;
    $marksPct = $ov['overall_marks_pct'] ?? null;
    $marksExamCount = (int) ($ov['overall_marks_exams_count'] ?? 0);
    $upcomingDays = (int) ($ov['upcoming_events_days'] ?? 14);
    $upcomingCount = (int) ($ov['upcoming_events_count'] ?? 0);
    $assignLike = (int) ($ov['upcoming_assignment_like_count'] ?? 0);
    $classroomBatchRows = $parent_classroom_batch_rows ?? collect();
    $recentExams = $parent_recent_graded_exams ?? collect();
@endphp
@extends('web.user.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
        <div class="row g-4 mb-2">
            <div class="col-12">
                <h5 class="mb-1">Overview</h5>
                <p class="mb-0 text-body-secondary small">
                    @if ($student)
                        Overiew for <span class="fw-medium text-heading">{{ $student->name }}</span>
                        — linked classrooms and batches, attendance, and assessments across their teachers.
                    @else
                        Parent dashboard
                    @endif
                </p>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base ti tabler-school icon-28px"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ (int) ($student_classroom_count ?? 0) }}</h4>
                                <p class="mb-0 text-body-secondary small">Classrooms</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base ti tabler-stack icon-28px"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ (int) ($student_batch_count ?? 0) }}</h4>
                                <p class="mb-0 text-body-secondary small">Batches</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-3">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base ti tabler-users icon-28px"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ (int) ($parent_student_count ?? 0) }}</h4>
                                <p class="mb-0 text-body-secondary small">Students on your account</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-3">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="icon-base ti tabler-calendar-event icon-28px"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ $upcomingCount }}</h4>
                                <p class="mb-0 text-body-secondary small">Upcoming events ({{ $upcomingDays }}d)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border shadow-none">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <p class="mb-1 text-body-secondary small">Overall marks (all classes)</p>
                                @if ($marksPct !== null)
                                    <h3 class="mb-0 text-heading">{{ $marksPct }}%</h3>
                                    <p class="mb-0 small text-body-secondary">Across {{ $marksExamCount }}
                                        graded {{ $marksExamCount === 1 ? 'item' : 'items' }}</p>
                                @else
                                    <h3 class="mb-0 text-heading">—</h3>
                                    <p class="mb-0 small text-body-secondary">No graded results in their batches yet</p>
                                @endif
                            </div>
                            <span class="badge bg-label-primary rounded p-2">
                                <i class="icon-base ti tabler-chart-bar icon-md"></i>
                            </span>
                        </div>
                        <a href="{{ url('user/parent/classrooms') }}" class="btn btn-sm btn-label-primary">View classrooms &amp; tests</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border shadow-none">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <p class="mb-1 text-body-secondary small">Attendance (all batches)</p>
                                @if ($attRate !== null)
                                    <h3 class="mb-0 text-heading">{{ $attRate }}%</h3>
                                    <p class="mb-0 small text-body-secondary">
                                        {{ (int) ($ov['attendance_present'] ?? 0) }} present ·
                                        {{ (int) ($ov['attendance_late'] ?? 0) }} leave (planned) ·
                                        {{ (int) ($ov['attendance_absent'] ?? 0) }} absent
                                    </p>
                                @else
                                    <h3 class="mb-0 text-heading">—</h3>
                                    <p class="mb-0 small text-body-secondary">No attendance recorded for their batches yet</p>
                                @endif
                            </div>
                            <span class="badge bg-label-success rounded p-2">
                                <i class="icon-base ti tabler-user-check icon-md"></i>
                            </span>
                        </div>
                        <p class="mb-2 small text-body-secondary">Open a classroom below for a full batch calendar and test
                            breakdown.</p>
                        <a href="{{ url('user/parent/classrooms') }}" class="btn btn-sm btn-label-success">Open classrooms</a>
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
                                            Including {{ $assignLike }} that look like assignments, deadlines, or quizzes
                                        @else
                                            Teacher events &amp; personal items for this student
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
                        <a href="{{ url('user/parent/events') }}" class="btn btn-sm btn-label-info">Open calendar</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h5 class="card-title mb-0">Classrooms &amp; batches</h5>
                            <p class="card-subtitle mb-0">Where this student is enrolled</p>
                        </div>
                        <a href="{{ url('user/parent/classrooms') }}" class="btn btn-sm btn-label-primary">Full list</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Classroom</th>
                                        <th>Batch</th>
                                        <th>Teacher</th>
                                        <th class="text-end">Open</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($classroomBatchRows as $row)
                                        <tr>
                                            <td class="fw-medium">{{ $row->classroom_name }}</td>
                                            <td>{{ $row->batch_name ?? '—' }}</td>
                                            <td class="text-body-secondary small">{{ $row->teacher_name }}</td>
                                            <td class="text-end">
                                                <a href="{{ url('user/parent/classroom/' . (int) $row->classroom_id) }}"
                                                    class="btn btn-sm btn-text-secondary">View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-body-secondary py-4">No classroom
                                                enrollments yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($recentExams->isNotEmpty())
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Recent graded tests</h5>
                            <p class="card-subtitle mb-0">Latest scores entered for this student</p>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Assessment</th>
                                            <th>Class / batch</th>
                                            <th>Date</th>
                                            <th class="text-end">Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentExams as $ex)
                                            <tr>
                                                <td class="fw-medium">{{ $ex->exam_name }}</td>
                                                <td class="small text-body-secondary">
                                                    {{ $ex->classroom_name }}
                                                    @if ($ex->batch_name !== '')
                                                        <span class="text-body-tertiary">·</span> {{ $ex->batch_name }}
                                                    @endif
                                                </td>
                                                <td class="small">{{ $ex->exam_date ?? '—' }}</td>
                                                <td class="text-end text-nowrap">
                                                    {{ rtrim(rtrim(number_format($ex->marks, 2, '.', ''), '0'), '.') }}
                                                    /
                                                    {{ rtrim(rtrim(number_format($ex->max_marks, 2, '.', ''), '0'), '.') }}
                                                    <span class="text-body-secondary small">({{ $ex->pct }}%)</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Shortcuts</h5>
                        <p class="card-subtitle mb-0">Jump to a page</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/parent/classrooms') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-school icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Classrooms</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/parent/events') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-calendar icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Events</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/parent/leave') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-calendar-off icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Student leave</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/parent/complaints') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-message-report icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Complaints</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/parent/fees') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-receipt icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Fees</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/profile') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-user icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Profile</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
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
