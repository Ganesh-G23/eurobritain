@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @if (($classroom->batches ?? collect())->isEmpty())
            <div class="alert alert-info mb-0">
                No batches in this classroom yet.
                <a href="{{ url('user/teacher/batches') }}" class="alert-link">Manage batches</a>
            </div>
        @else
            @if (session('import_marks_success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('import_marks_success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('import_marks_error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('import_marks_error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('import_attendance_success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('import_attendance_success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('import_attendance_error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('import_attendance_error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12">
                    <strong>
                        <h5>{{ $classroom->name }}</h5>
                    </strong>
                    <div class="card mb-4 mt-3">
                        <div class="card-body py-3">
                            <div
                                class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
                                <div class="nav-align-top mb-0 flex-grow-1">
                                    <ul class="nav nav-pills flex-column flex-md-row mb-0 gap-md-0 gap-2"
                                        id="classroomBatchTabs" role="tablist">
                                        @foreach ($classroom->batches as $batch)
                                            <li class="nav-item" role="presentation">
                                                <button type="button"
                                                    class="nav-link @if ($loop->first) active @endif"
                                                    id="batch-tab-{{ $batch->id }}" data-bs-toggle="tab"
                                                    data-bs-target="#batch-pane-{{ $batch->id }}" role="tab"
                                                    aria-controls="batch-pane-{{ $batch->id }}"
                                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                    <i class="icon-base ti tabler-folders icon-sm me-1_5"></i>
                                                    {{ $batch->name }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content">
                        @foreach ($classroom->batches as $batch)
                            @php
                                $list = $students_by_batch[$batch->id] ?? collect();
                                $batchExams = $exams_by_batch[$batch->id] ?? collect();
                                $marksGrid = $marks_by_exam_student ?? [];
                                $absGrid = $mark_absences_by_exam_student ?? [];
                                $marksReady = $marks_table_ready ?? false;
                                $excludeAbsentDisplay =
                                    (int) ($teacher_mark_display_setting ??
                                        \App\Models\TeacherSetting::COUNT_AS_ZERO) ===
                                    \App\Models\TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE;
                            @endphp
                            <div class="tab-pane fade @if ($loop->first) show active @endif"
                                id="batch-pane-{{ $batch->id }}" role="tabpanel"
                                aria-labelledby="batch-tab-{{ $batch->id }}">
                                <div class="demo-inline-spacing mt-4">
                                    <div class="list-group list-group-horizontal-md text-md-center mb-3"
                                        id="batch-sublist-tab-{{ $batch->id }}" role="tablist">
                                        <a class="list-group-item list-group-item-action active"
                                            id="batch-{{ $batch->id }}-students-tab" data-bs-toggle="list"
                                            href="#batch-{{ $batch->id }}-students-pane" role="tab"
                                            aria-controls="batch-{{ $batch->id }}-students-pane"
                                            aria-selected="true">All Students</a>
                                        <a class="list-group-item list-group-item-action"
                                            id="batch-{{ $batch->id }}-tests-tab" data-bs-toggle="list"
                                            href="#batch-{{ $batch->id }}-tests-pane" role="tab"
                                            aria-controls="batch-{{ $batch->id }}-tests-pane"
                                            aria-selected="false">Tests</a>
                                        <a class="list-group-item list-group-item-action"
                                            id="batch-{{ $batch->id }}-attendance-tab" data-bs-toggle="list"
                                            href="#batch-{{ $batch->id }}-attendance-pane" role="tab"
                                            aria-controls="batch-{{ $batch->id }}-attendance-pane"
                                            aria-selected="false">Attendance</a>
                                    </div>
                                    <div class="tab-content px-0 mt-0" id="batch-sublist-content-{{ $batch->id }}">
                                        <div class="tab-pane fade show active" id="batch-{{ $batch->id }}-students-pane"
                                            role="tabpanel" aria-labelledby="batch-{{ $batch->id }}-students-tab">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Student Name</th>
                                                            <th>Student Email</th>
                                                            <th>Student Phone</th>
                                                            <th>Parent Name</th>
                                                            <th>Parent Email</th>
                                                            <th>Parent Phone</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($list as $index => $student)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>{{ $student->name }}</td>
                                                                <td>{{ $student->email ?? '—' }}</td>
                                                                <td>{{ $student->phone ?? '—' }}</td>
                                                                <td>{{ $student->parent_name ?? '—' }}</td>
                                                                <td>{{ $student->parent_email ?? '—' }}</td>
                                                                <td>{{ $student->parent_phone ?? '—' }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="7"
                                                                    class="text-center text-body-secondary py-4">No
                                                                    students in this batch yet.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="batch-{{ $batch->id }}-tests-pane"
                                            role="tabpanel" aria-labelledby="batch-{{ $batch->id }}-tests-tab">
                                            <div class="marks-save-msg mb-2" data-batch-id="{{ $batch->id }}"></div>
                                            <div
                                                class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                                <span class="text-body-secondary small">Click <i
                                                        class="icon-base ti tabler-edit icon-sm"></i> on a test column to
                                                    enter or update marks.</span>
                                                <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                                                    @if ($batchExams->isNotEmpty())
                                                        <button type="button"
                                                            class="btn btn-label-secondary btn-sm js-export-marks-btn"
                                                            data-export-url="{{ url('user/teacher/classrooms/details/' . $classroom->id . '/export') }}"
                                                            data-batch-id="{{ $batch->id }}">
                                                            <i class="icon-base ti tabler-download me-1"></i> Export
                                                        </button>
                                                    @endif
                                                    @if ($marks_table_ready ?? false)
                                                        <button type="button" class="btn btn-label-secondary btn-sm"
                                                            data-bs-toggle="modal" data-bs-target="#importMarksModal">
                                                            <i class="icon-base ti tabler-upload me-1"></i> Import marks
                                                        </button>
                                                    @endif
                                                    <button type="button" class="btn btn-primary btn-sm btn-add-exam"
                                                        data-bs-toggle="modal" data-bs-target="#addExamModal"
                                                        data-batch-id="{{ $batch->id }}">
                                                        <i class="icon-base ti tabler-plus me-1"></i> Add test
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-4">
                                                    <label class="form-label"
                                                        for="marks-filter-{{ $batch->id }}">Search</label>
                                                    <input type="text" id="marks-filter-{{ $batch->id }}"
                                                        class="form-control js-marks-student-filter"
                                                        placeholder="Student Name" autocomplete="off"
                                                        data-marks-table="#marks-table-{{ $batch->id }}">
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table
                                                    class="table table-bordered table-striped align-middle text-nowrap marks-grid-table"
                                                    id="marks-table-{{ $batch->id }}"
                                                    data-batch-id="{{ $batch->id }}"
                                                    data-absent-display-exclude="{{ $excludeAbsentDisplay ? '1' : '0' }}">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Student Name</th>

                                                            @php
                                                                $totalMaxMarks = 0;
                                                            @endphp

                                                            @foreach ($batchExams as $exam)
                                                                @php
                                                                    $examMax = is_numeric($exam->max_marks)
                                                                        ? $exam->max_marks
                                                                        : 0;
                                                                    $totalMaxMarks += $examMax;
                                                                @endphp

                                                                <th class="text-center small align-top marks-exam-head"
                                                                    data-exam-id="{{ $exam->id }}"
                                                                    data-batch-id="{{ $batch->id }}"
                                                                    data-max-marks="{{ $examMax }}">

                                                                    <div class="fw-semibold">
                                                                        {{ $exam->exam_name }} ({{ $examMax }} pts)
                                                                    </div>

                                                                    <div class="text-body-secondary fw-normal">
                                                                        {{ optional($exam->exam_date)->format('d-m-Y') }}

                                                                        <input
                                                                            class="form-check-input js-export-exam-cb flex-shrink-0"
                                                                            type="checkbox" value="{{ $exam->id }}"
                                                                            id="export-exam-{{ $batch->id }}-{{ $exam->id }}"
                                                                            checked>
                                                                    </div>

                                                                    @if ($marksReady)
                                                                        <div
                                                                            class="d-flex align-items-center justify-content-center gap-1 mt-1 flex-wrap">
                                                                            <button type="button"
                                                                                class="btn btn-sm btn-icon btn-label-secondary js-edit-marks-col"
                                                                                title="Edit marks"
                                                                                data-exam-id="{{ $exam->id }}"
                                                                                data-batch-id="{{ $batch->id }}">
                                                                                <i class="icon-base ti tabler-edit"></i>
                                                                            </button>

                                                                            <div
                                                                                class="js-marks-col-actions d-none align-items-center gap-1 flex-wrap justify-content-center">
                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-primary js-save-marks-col">Save</button>

                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-label-secondary js-cancel-marks-col">Cancel</button>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </th>
                                                            @endforeach

                                                            {{-- ✅ TOTAL COLUMN --}}
                                                            <th class="text-center small align-top">
                                                                <div class="fw-semibold">
                                                                    TOTAL ({{ $totalMaxMarks }} pts)
                                                                </div>
                                                            </th>

                                                        </tr>
                                                    </thead>


                                                    <tbody>
                                                        @forelse ($list as $index => $student)
                                                            @php
                                                                $studentSearchHaystack = Str::lower(
                                                                    trim(
                                                                        ($student->name ?? '') .
                                                                            ' ' .
                                                                            ($student->email ?? '') .
                                                                            ' ' .
                                                                            ($student->phone ?? '') .
                                                                            ' ' .
                                                                            ($student->parent_name ?? '') .
                                                                            ' ' .
                                                                            ($student->parent_email ?? '') .
                                                                            ' ' .
                                                                            ($student->parent_phone ?? ''),
                                                                    ),
                                                                );

                                                                // ✅ initialize total
                                                                $totalMarks = 0;
                                                            @endphp

                                                            <tr class="marks-student-row"
                                                                data-student-id="{{ $student->id }}"
                                                                data-student-name="{{ Str::lower($student->name) }}"
                                                                data-student-search="{{ $studentSearchHaystack }}">

                                                                <td>{{ $index + 1 }}</td>

                                                                <td class="fw-medium">
                                                                    {{ $student->name }}
                                                                </td>

                                                                @foreach ($batchExams as $exam)
                                                                    @php
                                                                        $eid = (int) $exam->id;
                                                                        $sid = (int) $student->id;

                                                                        $marksForExam = $marksGrid[$eid] ?? [];
                                                                        $absForExam = $absGrid[$eid] ?? [];

                                                                        $isAbsent =
                                                                            $marksReady &&
                                                                            array_key_exists($sid, $absForExam);
                                                                        $hasMark =
                                                                            $marksReady &&
                                                                            array_key_exists($sid, $marksForExam);

                                                                        if ($isAbsent) {
                                                                            $storedMode = $absForExam[$sid];
                                                                            $effectiveMode =
                                                                                $storedMode !== null
                                                                                    ? (int) $storedMode
                                                                                    : (int) ($teacher_mark_display_setting ??
                                                                                        \App\Models\TeacherSetting::COUNT_AS_ZERO);
                                                                            $effectiveMode =
                                                                                (int) $effectiveMode ===
                                                                                \App\Models\TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE
                                                                                    ? \App\Models\TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE
                                                                                    : \App\Models\TeacherSetting::COUNT_AS_ZERO;
                                                                            $cell =
                                                                                $effectiveMode ===
                                                                                \App\Models\TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE
                                                                                    ? 'A'
                                                                                    : 'A(0)';
                                                                            $inputVal = 'A';
                                                                            // ❌ do NOT add in total
                                                                        } elseif ($hasMark) {
                                                                            $cell = $marksForExam[$sid];
                                                                            $inputVal = $marksForExam[$sid];

                                                                            // ✅ add only numeric marks
                                                                            if (is_numeric($cell)) {
                                                                                $totalMarks += $cell;
                                                                            }
                                                                        } else {
                                                                            $cell = '—';
                                                                            $inputVal = '';
                                                                        }
                                                                    @endphp

                                                                    <td class="text-center marks-exam-cell p-1 align-middle"
                                                                        data-exam-id="{{ $exam->id }}"
                                                                        data-student-id="{{ $student->id }}">

                                                                        <span
                                                                            class="marks-cell-display d-inline-block py-1">
                                                                            {{ $cell }}
                                                                        </span>

                                                                        @if ($marksReady)
                                                                            <input type="text"
                                                                                class="form-control form-control-sm text-center marks-cell-input d-none mx-auto"
                                                                                style="max-width: 5.5rem;"
                                                                                value="{{ $inputVal }}"
                                                                                inputmode="decimal" autocomplete="off">
                                                                        @endif
                                                                    </td>
                                                                @endforeach
                                                                <td class="text-center fw-semibold">
                                                                    {{ $totalMarks > 0 ? $totalMarks : '—' }}
                                                                </td>

                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="{{ 2 + $batchExams->count() }}"
                                                                    class="text-center text-body-secondary py-4">No
                                                                    students in this batch yet.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            @if ($batchExams->isEmpty())
                                                <p class="text-body-secondary small mt-2 mb-0">No tests yet. Use
                                                    <strong>Add test</strong> to create one.
                                                </p>
                                            @endif
                                            @if (!$marksReady)
                                                <p class="text-body-secondary small mt-2 mb-0">Marks table is not
                                                    available; cells show placeholders until marks are enabled.</p>
                                            @endif
                                        </div>

                                        <div class="tab-pane fade" id="batch-{{ $batch->id }}-attendance-pane"
                                            role="tabpanel" aria-labelledby="batch-{{ $batch->id }}-attendance-tab">
                                            @php
                                                $attDates = collect(
                                                    $attendance_dates_by_batch[$batch->id] ?? [],
                                                )->values();
                                                $attGridBatch =
                                                    $attendance_status_by_batch_date_student[$batch->id] ?? [];
                                                $attReady = $attendance_table_ready ?? false;
                                                $attColCount = max(2, 2 + $attDates->count());
                                                $attFilterFrom = \Illuminate\Support\Carbon::now()
                                                    ->startOfMonth()
                                                    ->format('Y-m-d');
                                                $attFilterTo = \Illuminate\Support\Carbon::now()
                                                    ->endOfMonth()
                                                    ->format('Y-m-d');
                                            @endphp
                                            <div class="attendance-save-msg mb-2" data-batch-id="{{ $batch->id }}"></div>
                                            <div
                                                class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                                <span class="text-body-secondary small">Tick date columns to include
                                                    them in the CSV, then click <strong>Export</strong></span>
                                                <div
                                                    class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                                                    @if ($attReady && $attDates->isNotEmpty())
                                                        <button type="button"
                                                            class="btn btn-label-secondary btn-sm js-export-attendance-btn"
                                                            data-export-url="{{ url('user/teacher/classrooms/details/' . $classroom->id . '/export-attendance') }}"
                                                            data-batch-id="{{ $batch->id }}">
                                                            <i class="icon-base ti tabler-download me-1"></i> Export
                                                        </button>
                                                    @endif
                                                    @if ($attReady)
                                                        <button type="button"
                                                            class="btn btn-label-secondary btn-sm"
                                                            data-bs-toggle="modal" data-bs-target="#importAttendanceModal">
                                                            <i class="icon-base ti tabler-upload me-1"></i> Import
                                                            attendance
                                                        </button>
                                                    @endif
                                                    <button type="button"
                                                        class="btn btn-primary btn-sm btn-open-attendance-modal"
                                                        data-bs-toggle="modal" data-bs-target="#addAttendanceModal"
                                                        data-batch-id="{{ $batch->id }}"
                                                        @if (!$attReady) disabled @endif>
                                                        <i class="icon-base ti tabler-plus me-1"></i> Add attendance
                                                    </button>
                                                </div>
                                            </div>
                                            @if (!$attReady)
                                                <p class="text-body-secondary small">Attendance storage is not available
                                                    yet.</p>
                                            @else
                                                <div class="row g-3 mb-3 align-items-end">
                                                    <div class="col-md-3">
                                                        <label class="form-label"
                                                            for="attendance-filter-{{ $batch->id }}">Search</label>
                                                        <input type="text" id="attendance-filter-{{ $batch->id }}"
                                                            class="form-control js-attendance-student-filter"
                                                            placeholder="Student name" autocomplete="off"
                                                            data-attendance-table="#attendance-table-{{ $batch->id }}">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label"
                                                            for="attendance-filter-from-{{ $batch->id }}">From
                                                            date</label>
                                                        <input type="date"
                                                            class="form-control js-attendance-date-from"
                                                            id="attendance-filter-from-{{ $batch->id }}"
                                                            data-batch-id="{{ $batch->id }}"
                                                            data-default-date="{{ $attFilterFrom }}"
                                                            value="{{ $attFilterFrom }}"
                                                            max="2099-12-31">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label"
                                                            for="attendance-filter-to-{{ $batch->id }}">To date</label>
                                                        <input type="date"
                                                            class="form-control js-attendance-date-to"
                                                            id="attendance-filter-to-{{ $batch->id }}"
                                                            data-batch-id="{{ $batch->id }}"
                                                            data-default-date="{{ $attFilterTo }}"
                                                            value="{{ $attFilterTo }}"
                                                            max="2099-12-31">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label d-block">&nbsp;</label>
                                                        <div class="d-flex gap-2">
                                                            <button type="button"
                                                                class="btn btn-primary btn-lg-2 js-attendance-date-search-btn"
                                                                data-batch-id="{{ $batch->id }}">
                                                                Search
                                                            </button>
                                                            <button type="button"
                                                                class="btn btn-label-secondary btn-lg-2 js-attendance-date-reset-btn"
                                                                data-batch-id="{{ $batch->id }}">
                                                                Reset
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="table-responsive attendance-date-scroll">
                                                    <table
                                                        class="table table-bordered table-striped align-middle text-nowrap"
                                                        id="attendance-table-{{ $batch->id }}"
                                                        data-batch-id="{{ $batch->id }}">
                                                        <thead>
                                                            <tr>
                                                                <th
                                                                    class="attendance-sticky-col attendance-sticky-col--num">
                                                                    #</th>
                                                                <th
                                                                    class="attendance-sticky-col attendance-sticky-col--name">
                                                                    Student Name</th>
                                                                @foreach ($attDates as $d)
                                                                    <th class="text-center small align-top attendance-date-head"
                                                                        data-attendance-date="{{ $d }}"
                                                                        data-batch-id="{{ $batch->id }}">
                                                                        <div class="fw-semibold">
                                                                            {{ \Illuminate\Support\Carbon::parse($d)->format('d M Y') }}
                                                                        </div>
                                                                        <div
                                                                            class="text-body-secondary fw-normal d-flex align-items-center justify-content-center gap-1 mt-1">
                                                                            <input
                                                                                class="form-check-input js-export-attendance-date-cb flex-shrink-0"
                                                                                type="checkbox" value="{{ $d }}"
                                                                                id="export-att-{{ $batch->id }}-{{ str_replace('-', '_', $d) }}"
                                                                                checked
                                                                                title="Include in export">
                                                                        </div>
                                                                        <div
                                                                            class="d-flex align-items-center justify-content-center gap-1 mt-1 flex-wrap">
                                                                            <button type="button"
                                                                                class="btn btn-sm btn-icon btn-label-secondary js-edit-attendance-col"
                                                                                title="Edit attendance"
                                                                                data-attendance-date="{{ $d }}"
                                                                                data-batch-id="{{ $batch->id }}">
                                                                                <i class="icon-base ti tabler-edit"></i>
                                                                            </button>
                                                                            <div
                                                                                class="js-attendance-col-actions d-none align-items-center gap-1 flex-wrap justify-content-center">
                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-primary js-save-attendance-col">Save</button>
                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-label-secondary js-cancel-attendance-col">Cancel</button>
                                                                            </div>
                                                                        </div>
                                                                    </th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse ($list as $index => $student)
                                                                @php
                                                                    $studentSearchHaystack = Str::lower(
                                                                        trim(
                                                                            ($student->name ?? '') .
                                                                                ' ' .
                                                                                ($student->email ?? '') .
                                                                                ' ' .
                                                                                ($student->phone ?? '') .
                                                                                ' ' .
                                                                                ($student->parent_name ?? '') .
                                                                                ' ' .
                                                                                ($student->parent_email ?? '') .
                                                                                ' ' .
                                                                                ($student->parent_phone ?? ''),
                                                                        ),
                                                                    );
                                                                    $sid = (int) $student->id;
                                                                @endphp
                                                                <tr class="attendance-student-row"
                                                                    data-student-id="{{ $student->id }}"
                                                                    data-student-name="{{ Str::lower($student->name) }}"
                                                                    data-student-search="{{ $studentSearchHaystack }}">
                                                                    <td
                                                                        class="attendance-sticky-col attendance-sticky-col--num">
                                                                        {{ $index + 1 }}</td>
                                                                    <td
                                                                        class="fw-medium attendance-sticky-col attendance-sticky-col--name">
                                                                        {{ $student->name }}</td>
                                                                    @foreach ($attDates as $d)
                                                                        @php
                                                                            $attCell = ($attGridBatch[$d] ?? [])[$sid] ?? '';
                                                                            $attCellDisplay = $attCell !== '' ? $attCell : '—';
                                                                        @endphp
                                                                        <td class="text-center attendance-date-cell p-1 align-middle"
                                                                            data-attendance-date="{{ $d }}"
                                                                            data-batch-id="{{ $batch->id }}"
                                                                            data-student-id="{{ $student->id }}">
                                                                            <span
                                                                                class="attendance-cell-display d-inline-block py-1">{{ $attCellDisplay }}</span>
                                                                            <input type="text"
                                                                                class="form-control form-control-sm text-center attendance-cell-input d-none mx-auto text-uppercase"
                                                                                maxlength="1" inputmode="text"
                                                                                autocomplete="off"
                                                                                aria-label="Attendance P, A, or L"
                                                                                placeholder="P/A/L"
                                                                                style="max-width: 3.25rem;"
                                                                                value="{{ $attCell === 'P' || $attCell === 'A' || $attCell === 'L' ? $attCell : '' }}">
                                                                        </td>
                                                                    @endforeach
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="{{ $attColCount }}"
                                                                        class="text-center text-body-secondary py-4">No
                                                                        students in this batch yet.</td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @if ($attDates->isEmpty() && $list->isNotEmpty())
                                                    <p class="text-body-secondary small mt-2 mb-0">No attendance dates yet.
                                                        Use <strong>Add attendance</strong> to add the first day.</p>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if ($marks_table_ready ?? false)
                <div class="modal fade" id="importMarksModal" tabindex="-1" aria-hidden="true"
                    data-export-base="{{ url('user/teacher/classrooms/details/' . $classroom->id . '/export') }}">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Import marks (CSV)</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <form method="post"
                                action="{{ url('user/teacher/classrooms/details/' . $classroom->id . '/import-marks') }}"
                                enctype="multipart/form-data" id="importMarksForm">
                                @csrf
                                <input type="hidden" name="batch_id" id="import_marks_batch_id" value="">
                                <input type="hidden" name="exam_ids[]" id="import_marks_exam_id_hidden" value="">
                                <div class="modal-body">
                                    <p class="small text-body-secondary mb-3">Choose the test → download template → fill
                                        marks in each test column. The first two rows are test info; do not delete them.
                                        <strong>Total max</strong> and <strong>Total marks</strong> columns (if present) are
                                        ignored on import. Student names must match this batch (duplicate names are not
                                        supported for this template).
                                    </p>
                                    <div class="mb-3">
                                        <label class="form-label" for="import_marks_test_select">Test</label>
                                        <select class="form-select form-select-sm" id="import_marks_test_select" required>
                                            @php
                                                $importHasTests = false;
                                            @endphp
                                            @foreach ($classroom->batches as $b)
                                                @php
                                                    $batchExamsForImport = $exams_by_batch[$b->id] ?? collect();
                                                @endphp
                                                @if ($batchExamsForImport->isNotEmpty())
                                                    @php $importHasTests = true; @endphp
                                                    <optgroup label="{{ $b->name }}">
                                                        @foreach ($batchExamsForImport as $e)
                                                            <option value="{{ $e->id }}"
                                                                data-batch-id="{{ $b->id }}">{{ $e->exam_name }}
                                                                ({{ $e->max_marks }} pts —
                                                                {{ optional($e->exam_date)->format('Y-m-d') }})
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                            @endforeach
                                            @if (!$importHasTests)
                                                <option value="" disabled selected>No tests in this classroom
                                                </option>
                                            @endif
                                        </select>
                                        <div class="form-text">Batch is taken from the test you pick (group labels).</div>
                                    </div>
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            id="import_marks_download_tpl">
                                            <i class="icon-base ti tabler-download me-1"></i> Download template CSV
                                        </button>
                                        <span class="text-body-secondary small ms-2">Matches the selected test
                                            columns.</span>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label" for="import_marks_file">CSV file</label>
                                        <input type="file" class="form-control form-control-sm" name="csv_file"
                                            id="import_marks_file" accept=".csv,.txt,text/csv,text/plain" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary" id="import_marks_submit">Import
                                        data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            @if ($attendance_table_ready ?? false)
                <div class="modal fade" id="importAttendanceModal" tabindex="-1" aria-hidden="true"
                    data-export-attendance-base="{{ url('user/teacher/classrooms/details/' . $classroom->id . '/export-attendance') }}">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Import attendance (CSV)</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <form method="post"
                                action="{{ url('user/teacher/classrooms/details/' . $classroom->id . '/import-attendance') }}"
                                enctype="multipart/form-data" id="importAttendanceForm">
                                @csrf
                                <div class="modal-body">
                                    <p class="small text-body-secondary mb-3">Choose the <strong>batch</strong> and
                                        <strong>attendance date</strong> first, then download the template. The sheet lists
                                        every student in that batch (same idea as marks import). Enter <strong>P</strong>,
                                        <strong>A</strong>, or <strong>L</strong> in the Status column (leave blank to
                                        clear). Keep the header row as downloaded; use the same date here as for the
                                        template.</p>
                                    <div class="mb-3">
                                        <label class="form-label" for="import_attendance_batch_select">Batch</label>
                                        <select class="form-select form-select-sm" name="batch_id"
                                            id="import_attendance_batch_select" required>
                                            @foreach ($classroom->batches as $b)
                                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="import_attendance_date">Attendance date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" name="attendance_date"
                                            id="import_attendance_date" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            id="import_attendance_download_tpl">
                                            <i class="icon-base ti tabler-download me-1"></i> Download template CSV
                                        </button>
                                        <span class="text-body-secondary small ms-2">Uses the batch and date above.</span>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label" for="import_attendance_file">CSV file</label>
                                        <input type="file" class="form-control form-control-sm" name="csv_file"
                                            id="import_attendance_file" accept=".csv,.txt,text/csv,text/plain" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary" id="import_attendance_submit">Save
                                        attendance</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="modal fade" id="addExamModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add test</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form id="addExamForm">
                            @csrf
                            <input type="hidden" name="batch_id" id="exam_batch_id" value="">
                            <div class="modal-body">
                                <div class="exam-form-msg mb-3"></div>
                                <div class="mb-3 ajax-field">
                                    <label class="form-label" for="exam_name">Test name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="exam_name" name="exam_name"
                                        placeholder="e.g. Unit 1">
                                    <span class="text-danger small ajax-error d-block mt-1"></span>
                                </div>
                                <div class="mb-3 ajax-field">
                                    <label class="form-label" for="max_marks">Max marks <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="max_marks" name="max_marks"
                                        placeholder="e.g. 100">
                                    <span class="text-danger small ajax-error d-block mt-1"></span>
                                </div>
                                <div class="mb-0 ajax-field">
                                    <label class="form-label" for="exam_date">Date<span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="exam_date" name="exam_date">
                                    <span class="text-danger small ajax-error d-block mt-1"></span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="addExamSubmit">Save test</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="addAttendanceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add attendance</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form id="addAttendanceForm" method="POST"
                            action="{{ portal_same_origin_path('user/teacher/attendance/save-day') }}">
                            @csrf
                            <input type="hidden" name="batch_id" id="attendance_modal_batch_id" value="">
                            <input type="hidden" name="return_classroom_id" value="{{ $classroom->id }}">
                            <div class="modal-body">
                                <div class="col-12 ajax-msg mb-3"></div>
                                <div class="mb-3 ajax-field">
                                    <label class="form-label" for="attendance_modal_date">Date <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="attendance_modal_date"
                                        name="date" required>
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="mb-3 ajax-field">
                                    <label class="form-label" for="attendance_modal_student_id">Student <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" name="student_id" id="attendance_modal_student_id">
                                        <option value="">Select student</option>
                                    </select>
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                                <div class="mb-0 ajax-field">
                                    <label class="form-label" for="attendance_modal_status">Attendance <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" name="attendance_status" id="attendance_modal_status">
                                        <option value="P">P — Present</option>
                                        <option value="A">A — Absent</option>
                                        <option value="L">L — Leave</option>
                                    </select>
                                    <span class="ajax-error" style="color: red;"></span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary submit-button">Save attendance</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script type="application/json" id="attendanceStudentsByBatchJson">
                {!! json_encode($attendance_modal_students_by_batch ?? []) !!}
            </script>
            <script type="application/json" id="attendanceStatusGridJson">
                {!! json_encode($attendance_status_by_batch_date_student ?? []) !!}
            </script>
        @endif
    </div>
@endsection

@section('scripts')
    @if (!($classroom->batches ?? collect())->isEmpty())
        <script>
            (function() {
                function syncImportMarksFromTestSelect() {
                    var ts = document.getElementById('import_marks_test_select');
                    var batchH = document.getElementById('import_marks_batch_id');
                    var examH = document.getElementById('import_marks_exam_id_hidden');
                    if (!ts || !batchH || !examH) {
                        return;
                    }
                    var opt = ts.options[ts.selectedIndex];
                    if (!opt || !opt.value) {
                        batchH.value = '';
                        examH.value = '';
                        return;
                    }
                    batchH.value = opt.getAttribute('data-batch-id') || '';
                    examH.value = opt.value;
                }

                var importModal = document.getElementById('importMarksModal');
                if (importModal) {
                    importModal.addEventListener('show.bs.modal', function() {
                        var ts = document.getElementById('import_marks_test_select');
                        if (!ts || ts.options.length === 0) {
                            return;
                        }
                        var active = document.querySelector(
                            '#classroomBatchTabs button.nav-link.active[data-bs-target]');
                        var batchId = '';
                        if (active) {
                            var target = active.getAttribute('data-bs-target') || '';
                            var m = target.match(/batch-pane-(\d+)/);
                            if (m) {
                                batchId = m[1];
                            }
                        }
                        if (batchId) {
                            for (var i = 0; i < ts.options.length; i++) {
                                if (ts.options[i].getAttribute('data-batch-id') === batchId && ts.options[i]
                                    .value) {
                                    ts.selectedIndex = i;
                                    break;
                                }
                            }
                        }
                        syncImportMarksFromTestSelect();
                    });
                    var ts = document.getElementById('import_marks_test_select');
                    if (ts) {
                        ts.addEventListener('change', syncImportMarksFromTestSelect);
                    }
                    var dlTpl = document.getElementById('import_marks_download_tpl');
                    if (dlTpl) {
                        dlTpl.addEventListener('click', function() {
                            syncImportMarksFromTestSelect();
                            var base = importModal.getAttribute('data-export-base');
                            var bid = document.getElementById('import_marks_batch_id').value;
                            var eid = document.getElementById('import_marks_exam_id_hidden').value;
                            if (!base || !bid || !eid) {
                                window.alert('Select a test from the list (add tests to the batch if empty).');
                                return;
                            }
                            var params = new URLSearchParams();
                            params.set('batch_id', bid);
                            params.append('exam_ids[]', eid);
                            window.location.href = base + '?' + params.toString();
                        });
                    }
                    var importForm = document.getElementById('importMarksForm');
                    if (importForm) {
                        importForm.addEventListener('submit', function() {
                            syncImportMarksFromTestSelect();
                        });
                    }
                }

                var importAttModal = document.getElementById('importAttendanceModal');
                if (importAttModal) {
                    importAttModal.addEventListener('show.bs.modal', function() {
                        var sel = document.getElementById('import_attendance_batch_select');
                        if (!sel || sel.options.length === 0) {
                            return;
                        }
                        var active = document.querySelector(
                            '#classroomBatchTabs button.nav-link.active[data-bs-target]');
                        var batchId = '';
                        if (active) {
                            var target = active.getAttribute('data-bs-target') || '';
                            var m = target.match(/batch-pane-(\d+)/);
                            if (m) {
                                batchId = m[1];
                            }
                        }
                        if (batchId) {
                            for (var j = 0; j < sel.options.length; j++) {
                                if (sel.options[j].value === batchId) {
                                    sel.selectedIndex = j;
                                    break;
                                }
                            }
                        }
                    });
                    var attDl = document.getElementById('import_attendance_download_tpl');
                    if (attDl) {
                        attDl.addEventListener('click', function() {
                            var base = importAttModal.getAttribute('data-export-attendance-base');
                            var bid = document.getElementById('import_attendance_batch_select');
                            var dEl = document.getElementById('import_attendance_date');
                            var bidVal = bid ? bid.value : '';
                            var dVal = dEl ? dEl.value : '';
                            if (!base || !bidVal || !dVal) {
                                window.alert('Select a batch and attendance date first.');
                                return;
                            }
                            var q = new URLSearchParams();
                            q.set('batch_id', bidVal);
                            q.set('attendance_date', dVal);
                            window.location.href = base + '?' + q.toString();
                        });
                    }
                }

                document.querySelectorAll('.btn-add-exam').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var id = btn.getAttribute('data-batch-id');
                        var input = document.getElementById('exam_batch_id');
                        if (input) {
                            input.value = id;
                        }
                        var modal = document.getElementById('addExamModal');
                        if (modal) {
                            modal.querySelectorAll('.ajax-error').forEach(function(el) {
                                el.textContent = '';
                            });
                            var msg = modal.querySelector('.exam-form-msg');
                            if (msg) {
                                msg.innerHTML = '';
                            }
                        }
                    });
                });

                var examModal = document.getElementById('addExamModal');
                if (examModal) {
                    examModal.addEventListener('hidden.bs.modal', function() {
                        var form = document.getElementById('addExamForm');
                        if (form) {
                            form.reset();
                            document.getElementById('exam_batch_id').value = '';
                        }
                        examModal.querySelectorAll('.ajax-error').forEach(function(el) {
                            el.textContent = '';
                        });
                        var msg = examModal.querySelector('.exam-form-msg');
                        if (msg) {
                            msg.innerHTML = '';
                        }
                    });
                }
            })();
        </script>
        <script>
            $(function() {
                $('#addExamForm').on('submit', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var modal = $('#addExamModal');
                    form.find('.ajax-error').text('');
                    modal.find('.exam-form-msg').html('');
                    var btn = $('#addExamSubmit');
                    btn.prop('disabled', true).text('Saving...');
                    var payload = form.serializeArray();
                    $.post("{{ portal_same_origin_path('user/teacher/exams/save') }}", payload, function(
                        res) {
                        btn.prop('disabled', false).text('Save test');
                        if (res.status == 1) {
                            window.location.href = res.redirect_url || window.location.href;
                        } else if (res.error_array) {
                            Object.keys(res.error_array).forEach(function(key) {
                                var err = res.error_array[key];
                                var text = Array.isArray(err) ? err[0] : err;
                                form.find('[name="' + key + '"]').closest('.ajax-field').find(
                                    '.ajax-error').text(
                                    text);
                            });
                        } else if (res.error) {
                            modal.find('.exam-form-msg').html(
                                '<div class="alert alert-danger mb-0">' + res.error + '</div>');
                        }
                    }, 'json').fail(function() {
                        btn.prop('disabled', false).text('Save test');
                        modal.find('.exam-form-msg').html(
                            '<div class="alert alert-danger mb-0">Request failed</div>');
                    });
                });
            });
        </script>
        <script>
            (function() {
                var marksSaveUrl = "{{ portal_same_origin_path('user/teacher/exams/marks/save') }}";
                var csrf = "{{ csrf_token() }}";
                var activeColumn = null;

                function marksCellDisplayText(raw, absentDisplayExclude) {
                    var v = (raw || '').trim();
                    if (v === '') {
                        return '—';
                    }
                    var u = v.toUpperCase();
                    if (u === 'A' || u === 'ABSENT') {
                        return absentDisplayExclude ? 'A' : 'A(0)';
                    }
                    return v;
                }

                function exitEdit(pane, examId) {
                    if (!pane || !examId) {
                        return;
                    }
                    pane.querySelectorAll('th.marks-exam-head[data-exam-id="' + examId + '"]').forEach(function(th) {
                        var eb = th.querySelector('.js-edit-marks-col');
                        var act = th.querySelector('.js-marks-col-actions');
                        if (eb) {
                            eb.classList.remove('d-none');
                        }
                        if (act) {
                            act.classList.add('d-none');
                            act.classList.remove('d-flex');
                        }
                    });
                    pane.querySelectorAll('td.marks-exam-cell[data-exam-id="' + examId + '"]').forEach(function(td) {
                        var span = td.querySelector('.marks-cell-display');
                        var inp = td.querySelector('.marks-cell-input');
                        if (span) {
                            span.classList.remove('d-none');
                        }
                        if (inp) {
                            inp.classList.add('d-none');
                        }
                    });
                }

                function enterEdit(pane, examId) {
                    pane.querySelectorAll('th.marks-exam-head[data-exam-id="' + examId + '"]').forEach(function(th) {
                        var eb = th.querySelector('.js-edit-marks-col');
                        var act = th.querySelector('.js-marks-col-actions');
                        if (eb) {
                            eb.classList.add('d-none');
                        }
                        if (act) {
                            act.classList.remove('d-none');
                            act.classList.add('d-flex');
                        }
                    });
                    pane.querySelectorAll('td.marks-exam-cell[data-exam-id="' + examId + '"]').forEach(function(td) {
                        var span = td.querySelector('.marks-cell-display');
                        var inp = td.querySelector('.marks-cell-input');
                        if (!inp) {
                            return;
                        }
                        if (span) {
                            span.classList.add('d-none');
                        }
                        inp.classList.remove('d-none');
                        inp.dataset.markOriginal = inp.value;
                    });
                }

                function clearMsg(pane) {
                    var box = pane.querySelector('.marks-save-msg');
                    if (box) {
                        box.innerHTML = '';
                    }
                }

                function showMsg(pane, html, isError) {
                    var box = pane.querySelector('.marks-save-msg');
                    if (!box) {
                        return;
                    }
                    var cls = isError ? 'alert alert-danger py-2 mb-0' : 'alert alert-success py-2 mb-0';
                    box.innerHTML = '<div class="' + cls + '">' + html + '</div>';
                }

                document.addEventListener('click', function(e) {
                    var editBtn = e.target.closest('.js-edit-marks-col');
                    if (editBtn) {
                        e.preventDefault();
                        var examId = editBtn.getAttribute('data-exam-id');
                        var batchId = editBtn.getAttribute('data-batch-id');
                        var pane = document.getElementById('batch-' + batchId + '-tests-pane');
                        if (!pane || !examId) {
                            return;
                        }
                        clearMsg(pane);
                        if (activeColumn) {
                            if (activeColumn.pane !== pane || activeColumn.examId !== examId) {
                                cancelValues(activeColumn.pane, activeColumn.examId);
                                exitEdit(activeColumn.pane, activeColumn.examId);
                            }
                        }
                        enterEdit(pane, examId);
                        activeColumn = {
                            examId: examId,
                            batchId: batchId,
                            pane: pane
                        };
                        return;
                    }

                    var cancelBtn = e.target.closest('.js-cancel-marks-col');
                    if (cancelBtn) {
                        e.preventDefault();
                        var th = cancelBtn.closest('th.marks-exam-head');
                        if (!th) {
                            return;
                        }
                        var examId = th.getAttribute('data-exam-id');
                        var batchId = th.getAttribute('data-batch-id');
                        var pane = document.getElementById('batch-' + batchId + '-tests-pane');
                        if (!pane) {
                            return;
                        }
                        cancelValues(pane, examId);
                        exitEdit(pane, examId);
                        clearMsg(pane);
                        activeColumn = null;
                        return;
                    }

                    var saveBtn = e.target.closest('.js-save-marks-col');
                    if (saveBtn) {
                        e.preventDefault();
                        var th = saveBtn.closest('th.marks-exam-head');
                        if (!th) {
                            return;
                        }
                        var examId = th.getAttribute('data-exam-id');
                        var batchId = th.getAttribute('data-batch-id');
                        var pane = document.getElementById('batch-' + batchId + '-tests-pane');
                        if (!pane || !examId) {
                            return;
                        }
                        var marksTable = document.getElementById('marks-table-' + batchId);
                        var absentDisplayExclude = marksTable &&
                            marksTable.getAttribute('data-absent-display-exclude') === '1';
                        clearMsg(pane);
                        var marks = {};
                        pane.querySelectorAll('td.marks-exam-cell[data-exam-id="' + examId + '"]').forEach(function(
                            td) {
                            var sid = td.getAttribute('data-student-id');
                            var inp = td.querySelector('.marks-cell-input');
                            if (sid && inp) {
                                marks[sid] = inp.value;
                            }
                        });
                        var payload = {
                            _token: csrf,
                            exam_id: examId,
                            batch_id: batchId
                        };
                        Object.keys(marks).forEach(function(sid) {
                            payload['marks[' + sid + ']'] = marks[sid];
                        });
                        var btn = saveBtn;
                        btn.disabled = true;
                        $.post(marksSaveUrl, payload, function(res) {
                            btn.disabled = false;
                            if (res.status == 1) {
                                pane.querySelectorAll('td.marks-exam-cell[data-exam-id="' + examId + '"]')
                                    .forEach(
                                        function(td) {
                                            var inp = td.querySelector('.marks-cell-input');
                                            var span = td.querySelector('.marks-cell-display');
                                            if (!inp || !span) {
                                                return;
                                            }
                                            var v = inp.value.trim();
                                            span.textContent = marksCellDisplayText(v,
                                                absentDisplayExclude);
                                            inp.dataset.markOriginal = v;
                                        });
                                exitEdit(pane, examId);
                                activeColumn = null;
                                showMsg(pane, res.msg || 'Marks saved', false);
                            } else if (res.error) {
                                showMsg(pane, res.error, true);
                            } else if (res.error_array) {
                                showMsg(pane, 'Validation failed', true);
                            }
                        }, 'json').fail(function() {
                            btn.disabled = false;
                            showMsg(pane, 'Request failed', true);
                        });
                    }

                    var exportBtn = e.target.closest('.js-export-marks-btn');
                    if (exportBtn) {
                        e.preventDefault();
                        var batchId = exportBtn.getAttribute('data-batch-id');
                        var action = exportBtn.getAttribute('data-export-url');
                        var table = document.getElementById('marks-table-' + batchId);
                        if (!table || !action) {
                            return;
                        }
                        var examCheckboxes = table.querySelectorAll(
                            'thead input.js-export-exam-cb[type="checkbox"]'
                        );
                        if (!examCheckboxes.length) {
                            window.alert('No tests in this batch to export.');
                            return;
                        }
                        var examIds = [];
                        for (var i = 0; i < examCheckboxes.length; i++) {
                            if (examCheckboxes[i].checked) {
                                examIds.push(examCheckboxes[i].value);
                            }
                        }
                        if (!examIds.length) {
                            for (var j = 0; j < examCheckboxes.length; j++) {
                                examIds.push(examCheckboxes[j].value);
                            }
                        }
                        var studentIds = [];
                        table.querySelectorAll('tbody tr.marks-student-row').forEach(function(tr) {
                            if (tr.style.display === 'none') {
                                return;
                            }
                            var sid = tr.getAttribute('data-student-id');
                            if (sid) {
                                studentIds.push(sid);
                            }
                        });
                        if (!studentIds.length) {
                            window.alert(
                                'No students to export. Clear the search filter or add students to this batch.'
                            );
                            return;
                        }
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = action;
                        form.style.display = 'none';
                        var token = document.createElement('input');
                        token.type = 'hidden';
                        token.name = '_token';
                        token.value = csrf;
                        form.appendChild(token);
                        var bi = document.createElement('input');
                        bi.type = 'hidden';
                        bi.name = 'batch_id';
                        bi.value = batchId;
                        form.appendChild(bi);
                        examIds.forEach(function(id) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = 'exam_ids[]';
                            inp.value = id;
                            form.appendChild(inp);
                        });
                        studentIds.forEach(function(id) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = 'student_ids[]';
                            inp.value = id;
                            form.appendChild(inp);
                        });
                        document.body.appendChild(form);
                        form.submit();
                        document.body.removeChild(form);
                    }
                });

                function cancelValues(pane, examId) {
                    pane.querySelectorAll('td.marks-exam-cell[data-exam-id="' + examId + '"]').forEach(function(td) {
                        var inp = td.querySelector('.marks-cell-input');
                        if (inp && inp.dataset.markOriginal !== undefined) {
                            inp.value = inp.dataset.markOriginal;
                        }
                    });
                }

                document.addEventListener('input', function(e) {
                    if (!e.target.classList.contains('js-marks-student-filter')) {
                        return;
                    }
                    var sel = e.target.getAttribute('data-marks-table');
                    var table = sel ? document.querySelector(sel) : null;
                    if (!table) {
                        return;
                    }
                    var q = (e.target.value || '').trim().toLowerCase();
                    table.querySelectorAll('tbody tr.marks-student-row').forEach(function(tr) {
                        var hay = tr.getAttribute('data-student-search') || tr.getAttribute(
                            'data-student-name') || '';
                        tr.style.display = !q || hay.indexOf(q) !== -1 ? '' : 'none';
                    });
                });

                document.addEventListener('input', function(e) {
                    if (!e.target.classList.contains('js-attendance-student-filter')) {
                        return;
                    }
                    var sel = e.target.getAttribute('data-attendance-table');
                    var table = sel ? document.querySelector(sel) : null;
                    if (!table) {
                        return;
                    }
                    var q = (e.target.value || '').trim().toLowerCase();
                    table.querySelectorAll('tbody tr.attendance-student-row').forEach(function(tr) {
                        var hay = tr.getAttribute('data-student-search') || tr.getAttribute(
                            'data-student-name') || '';
                        tr.style.display = !q || hay.indexOf(q) !== -1 ? '' : 'none';
                    });
                });
            })();
        </script>
        <script>
            (function() {
                var attendanceColSaveUrl =
                    "{{ portal_same_origin_path('user/teacher/attendance/save-column') }}";
                var csrfAtt = "{{ csrf_token() }}";
                var activeAttendanceColumn = null;

                function attClearMsg(pane) {
                    var box = pane.querySelector('.attendance-save-msg');
                    if (box) {
                        box.innerHTML = '';
                    }
                }

                function attShowMsg(pane, html, isError) {
                    var box = pane.querySelector('.attendance-save-msg');
                    if (!box) {
                        return;
                    }
                    var cls = isError ? 'alert alert-danger py-2 mb-0' : 'alert alert-success py-2 mb-0';
                    box.innerHTML = '<div class="' + cls + '">' + html + '</div>';
                }

                function attendanceCellDisplay(val) {
                    var v = (val || '').trim();
                    return v === '' ? '—' : v;
                }

                /** Returns '', 'P','A','L', or null if invalid (non-empty garbage). */
                function normalizeAttendanceInput(raw) {
                    var s = (raw || '').trim();
                    if (s === '' || s === '—') {
                        return '';
                    }
                    s = s.charAt(0).toUpperCase();
                    if (s === 'P' || s === 'A' || s === 'L') {
                        return s;
                    }
                    return null;
                }

                document.addEventListener('input', function(e) {
                    var inp = e.target.closest('.attendance-cell-input');
                    if (!inp) {
                        return;
                    }
                    var v = inp.value.replace(/[^pPaAlL]/g, '');
                    if (v.length > 1) {
                        v = v.slice(0, 1);
                    }
                    if (v) {
                        v = v.toUpperCase();
                    }
                    if (inp.value !== v) {
                        inp.value = v;
                    }
                });

                function exitEditAttendance(pane, batchId, dateStr) {
                    if (!pane || !batchId || !dateStr) {
                        return;
                    }
                    pane.querySelectorAll('th.attendance-date-head[data-batch-id="' + batchId +
                        '"][data-attendance-date="' + dateStr + '"]').forEach(function(th) {
                        var eb = th.querySelector('.js-edit-attendance-col');
                        var act = th.querySelector('.js-attendance-col-actions');
                        if (eb) {
                            eb.classList.remove('d-none');
                        }
                        if (act) {
                            act.classList.add('d-none');
                            act.classList.remove('d-flex');
                        }
                    });
                    pane.querySelectorAll('td.attendance-date-cell[data-batch-id="' + batchId +
                        '"][data-attendance-date="' + dateStr + '"]').forEach(function(td) {
                        var span = td.querySelector('.attendance-cell-display');
                        var inp = td.querySelector('.attendance-cell-input');
                        if (span) {
                            span.classList.remove('d-none');
                        }
                        if (inp) {
                            inp.classList.add('d-none');
                        }
                    });
                }

                function enterEditAttendance(pane, batchId, dateStr) {
                    pane.querySelectorAll('th.attendance-date-head[data-batch-id="' + batchId +
                        '"][data-attendance-date="' + dateStr + '"]').forEach(function(th) {
                        var eb = th.querySelector('.js-edit-attendance-col');
                        var act = th.querySelector('.js-attendance-col-actions');
                        if (eb) {
                            eb.classList.add('d-none');
                        }
                        if (act) {
                            act.classList.remove('d-none');
                            act.classList.add('d-flex');
                        }
                    });
                    pane.querySelectorAll('td.attendance-date-cell[data-batch-id="' + batchId +
                        '"][data-attendance-date="' + dateStr + '"]').forEach(function(td) {
                        var span = td.querySelector('.attendance-cell-display');
                        var inp = td.querySelector('.attendance-cell-input');
                        if (!inp) {
                            return;
                        }
                        if (span) {
                            span.classList.add('d-none');
                        }
                        inp.classList.remove('d-none');
                        var t = (span && span.textContent) ? span.textContent.trim() : '';
                        if (t === '—') {
                            t = '';
                        }
                        var letter = (t === 'P' || t === 'A' || t === 'L') ? t : '';
                        inp.value = letter;
                        inp.dataset.attendanceOriginal = letter;
                    });
                }

                function cancelAttendanceValues(pane, batchId, dateStr) {
                    pane.querySelectorAll('td.attendance-date-cell[data-batch-id="' + batchId +
                        '"][data-attendance-date="' + dateStr + '"]').forEach(function(td) {
                        var inp = td.querySelector('.attendance-cell-input');
                        if (inp && inp.dataset.attendanceOriginal !== undefined) {
                            inp.value = inp.dataset.attendanceOriginal;
                        }
                    });
                }

                function applyAttendanceDateRangeFilter(batchId) {
                    var b = String(batchId);
                    var fromEl = document.getElementById('attendance-filter-from-' + b);
                    var toEl = document.getElementById('attendance-filter-to-' + b);
                    var table = document.getElementById('attendance-table-' + b);
                    if (!table) {
                        return;
                    }
                    var fromVal = fromEl && fromEl.value ? String(fromEl.value).trim() : '';
                    var toVal = toEl && toEl.value ? String(toEl.value).trim() : '';
                    if (fromVal && toVal && fromVal > toVal) {
                        var tmp = fromVal;
                        fromVal = toVal;
                        toVal = tmp;
                        if (fromEl) {
                            fromEl.value = fromVal;
                        }
                        if (toEl) {
                            toEl.value = toVal;
                        }
                    }
                    table.querySelectorAll('[data-attendance-date]').forEach(function(el) {
                        if (!el.classList.contains('attendance-date-head') && !el.classList.contains(
                                'attendance-date-cell')) {
                            return;
                        }
                        var d = el.getAttribute('data-attendance-date') || '';
                        if (!d) {
                            return;
                        }
                        var show = true;
                        if (fromVal && d < fromVal) {
                            show = false;
                        }
                        if (toVal && d > toVal) {
                            show = false;
                        }
                        el.classList.toggle('d-none', !show);
                    });
                }

                document.addEventListener('click', function(e) {
                    var rangeSearchBtn = e.target.closest('.js-attendance-date-search-btn');
                    if (rangeSearchBtn) {
                        e.preventDefault();
                        var searchBatchId = rangeSearchBtn.getAttribute('data-batch-id');
                        if (searchBatchId) {
                            applyAttendanceDateRangeFilter(searchBatchId);
                        }
                        return;
                    }

                    var rangeResetBtn = e.target.closest('.js-attendance-date-reset-btn');
                    if (rangeResetBtn) {
                        e.preventDefault();
                        var resetBatchId = rangeResetBtn.getAttribute('data-batch-id');
                        if (!resetBatchId) {
                            return;
                        }
                        var fromReset = document.getElementById('attendance-filter-from-' + resetBatchId);
                        var toReset = document.getElementById('attendance-filter-to-' + resetBatchId);
                        if (fromReset) {
                            fromReset.value = fromReset.getAttribute('data-default-date') || '';
                        }
                        if (toReset) {
                            toReset.value = toReset.getAttribute('data-default-date') || '';
                        }
                        applyAttendanceDateRangeFilter(resetBatchId);
                        return;
                    }

                    var exportAttBtn = e.target.closest('.js-export-attendance-btn');
                    if (exportAttBtn) {
                        e.preventDefault();
                        var batchId = exportAttBtn.getAttribute('data-batch-id');
                        var action = exportAttBtn.getAttribute('data-export-url');
                        var table = document.getElementById('attendance-table-' + batchId);
                        if (!table || !action) {
                            return;
                        }
                        var dateCbs = table.querySelectorAll(
                            'thead input.js-export-attendance-date-cb[type="checkbox"]'
                        );
                        if (!dateCbs.length) {
                            window.alert('No attendance dates in this batch to export.');
                            return;
                        }
                        var attendanceDates = [];
                        for (var i = 0; i < dateCbs.length; i++) {
                            var thHead = dateCbs[i].closest('th.attendance-date-head');
                            if (thHead && thHead.classList.contains('d-none')) {
                                continue;
                            }
                            if (dateCbs[i].checked) {
                                attendanceDates.push(dateCbs[i].value);
                            }
                        }
                        if (!attendanceDates.length) {
                            for (var j = 0; j < dateCbs.length; j++) {
                                var th2 = dateCbs[j].closest('th.attendance-date-head');
                                if (th2 && th2.classList.contains('d-none')) {
                                    continue;
                                }
                                attendanceDates.push(dateCbs[j].value);
                            }
                        }
                        if (!attendanceDates.length) {
                            window.alert(
                                'No attendance columns are visible for the current From / To range. Widen the date range.'
                            );
                            return;
                        }
                        var studentIds = [];
                        table.querySelectorAll('tbody tr.attendance-student-row').forEach(function(tr) {
                            if (tr.style.display === 'none') {
                                return;
                            }
                            var sid = tr.getAttribute('data-student-id');
                            if (sid) {
                                studentIds.push(sid);
                            }
                        });
                        if (!studentIds.length) {
                            window.alert(
                                'No students to export. Clear the search filter or add students to this batch.'
                            );
                            return;
                        }
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = action;
                        form.style.display = 'none';
                        var token = document.createElement('input');
                        token.type = 'hidden';
                        token.name = '_token';
                        token.value = csrfAtt;
                        form.appendChild(token);
                        var bi = document.createElement('input');
                        bi.type = 'hidden';
                        bi.name = 'batch_id';
                        bi.value = batchId;
                        form.appendChild(bi);
                        attendanceDates.forEach(function(d) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = 'attendance_dates[]';
                            inp.value = d;
                            form.appendChild(inp);
                        });
                        studentIds.forEach(function(id) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = 'student_ids[]';
                            inp.value = id;
                            form.appendChild(inp);
                        });
                        document.body.appendChild(form);
                        form.submit();
                        document.body.removeChild(form);
                        return;
                    }

                    var editBtn = e.target.closest('.js-edit-attendance-col');
                    if (editBtn) {
                        e.preventDefault();
                        var dateStr = editBtn.getAttribute('data-attendance-date');
                        var batchId = editBtn.getAttribute('data-batch-id');
                        var pane = document.getElementById('batch-' + batchId + '-attendance-pane');
                        if (!pane || !dateStr) {
                            return;
                        }
                        attClearMsg(pane);
                        if (activeAttendanceColumn) {
                            if (activeAttendanceColumn.pane !== pane || activeAttendanceColumn.dateStr !==
                                dateStr || activeAttendanceColumn.batchId !== batchId) {
                                cancelAttendanceValues(activeAttendanceColumn.pane, activeAttendanceColumn
                                    .batchId, activeAttendanceColumn.dateStr);
                                exitEditAttendance(activeAttendanceColumn.pane, activeAttendanceColumn
                                    .batchId, activeAttendanceColumn.dateStr);
                            }
                        }
                        enterEditAttendance(pane, batchId, dateStr);
                        activeAttendanceColumn = {
                            dateStr: dateStr,
                            batchId: batchId,
                            pane: pane
                        };
                        return;
                    }

                    var cancelBtn = e.target.closest('.js-cancel-attendance-col');
                    if (cancelBtn) {
                        e.preventDefault();
                        var th = cancelBtn.closest('th.attendance-date-head');
                        if (!th) {
                            return;
                        }
                        var dateStr = th.getAttribute('data-attendance-date');
                        var batchId = th.getAttribute('data-batch-id');
                        var pane = document.getElementById('batch-' + batchId + '-attendance-pane');
                        if (!pane) {
                            return;
                        }
                        cancelAttendanceValues(pane, batchId, dateStr);
                        exitEditAttendance(pane, batchId, dateStr);
                        attClearMsg(pane);
                        activeAttendanceColumn = null;
                        return;
                    }

                    var saveBtn = e.target.closest('.js-save-attendance-col');
                    if (saveBtn) {
                        e.preventDefault();
                        var th = saveBtn.closest('th.attendance-date-head');
                        if (!th) {
                            return;
                        }
                        var dateStr = th.getAttribute('data-attendance-date');
                        var batchId = th.getAttribute('data-batch-id');
                        var pane = document.getElementById('batch-' + batchId + '-attendance-pane');
                        if (!pane || !dateStr) {
                            return;
                        }
                        attClearMsg(pane);
                        var payload = {
                            _token: csrfAtt,
                            batch_id: batchId,
                            attendance_date: dateStr
                        };
                        var cells = pane.querySelectorAll('td.attendance-date-cell[data-batch-id="' + batchId +
                            '"][data-attendance-date="' + dateStr + '"]');
                        var invalid = false;
                        for (var ci = 0; ci < cells.length; ci++) {
                            var td = cells[ci];
                            var sid = td.getAttribute('data-student-id');
                            var inp = td.querySelector('.attendance-cell-input');
                            if (!sid || !inp) {
                                continue;
                            }
                            var norm = normalizeAttendanceInput(inp.value);
                            if (norm === null) {
                                invalid = true;
                                break;
                            }
                            inp.value = norm;
                            payload['statuses[' + sid + ']'] = norm;
                        }
                        if (invalid) {
                            attShowMsg(pane, 'Each cell must be P, A, or L (or leave blank to clear).', true);
                            return;
                        }
                        var btn = saveBtn;
                        btn.disabled = true;
                        $.post(attendanceColSaveUrl, payload, function(res) {
                            btn.disabled = false;
                            if (res.status == 1) {
                                pane.querySelectorAll('td.attendance-date-cell[data-batch-id="' +
                                    batchId + '"][data-attendance-date="' + dateStr + '"]').forEach(function(
                                    td) {
                                    var inp = td.querySelector('.attendance-cell-input');
                                    var span = td.querySelector('.attendance-cell-display');
                                    if (!inp || !span) {
                                        return;
                                    }
                                    span.textContent = attendanceCellDisplay(inp.value);
                                    inp.dataset.attendanceOriginal = inp.value;
                                });
                                exitEditAttendance(pane, batchId, dateStr);
                                activeAttendanceColumn = null;
                                attShowMsg(pane, res.msg || 'Attendance saved', false);
                            } else if (res.error) {
                                attShowMsg(pane, res.error, true);
                            } else if (res.error_array) {
                                attShowMsg(pane, 'Validation failed', true);
                            }
                        }, 'json').fail(function() {
                            btn.disabled = false;
                            attShowMsg(pane, 'Request failed', true);
                        });
                    }
                });

                document.querySelectorAll('input.js-attendance-date-from[data-batch-id]').forEach(function(inp) {
                    var bid = inp.getAttribute('data-batch-id');
                    if (bid) {
                        applyAttendanceDateRangeFilter(bid);
                    }
                });
            })();
        </script>

        <script>
            $(function() {
                var elS = document.getElementById('attendanceStudentsByBatchJson');
                var elG = document.getElementById('attendanceStatusGridJson');
                if (!elS) {
                    return;
                }
                var attendanceStudentsByBatch = {};
                var attendanceStatusGrid = {};
                try {
                    attendanceStudentsByBatch = JSON.parse(elS.textContent.trim() || '{}');
                } catch (err) {
                    attendanceStudentsByBatch = {};
                }
                if (elG) {
                    try {
                        attendanceStatusGrid = JSON.parse(elG.textContent.trim() || '{}');
                    } catch (err2) {
                        attendanceStatusGrid = {};
                    }
                }

                function escapeHtml(s) {
                    var d = document.createElement('div');
                    d.textContent = s == null ? '' : String(s);
                    return d.innerHTML;
                }

                function studentsForBatch(batchId) {
                    var b = String(batchId);
                    return attendanceStudentsByBatch[batchId] || attendanceStudentsByBatch[b] || [];
                }

                function fillAttendanceStudentSelect(batchId) {
                    var sel = document.getElementById('attendance_modal_student_id');
                    if (!sel || !batchId) {
                        return;
                    }
                    var students = studentsForBatch(batchId);
                    sel.innerHTML = '<option value="">Select student</option>';
                    students.forEach(function(s) {
                        var opt = document.createElement('option');
                        opt.value = String(s.id);
                        opt.textContent = s.name;
                        sel.appendChild(opt);
                    });
                }

                function syncAttendanceStatusFromGrid() {
                    var batchId = parseInt(document.getElementById('attendance_modal_batch_id').value, 10);
                    var dateInput = document.getElementById('attendance_modal_date');
                    var studentSel = document.getElementById('attendance_modal_student_id');
                    var statusSel = document.getElementById('attendance_modal_status');
                    if (!dateInput || !studentSel || !statusSel || !batchId) {
                        return;
                    }
                    var dateStr = dateInput.value || '';
                    var sid = parseInt(studentSel.value, 10);
                    if (!dateStr || !sid) {
                        return;
                    }
                    var gridB = attendanceStatusGrid[batchId] || attendanceStatusGrid[String(batchId)] || {};
                    var gridD = gridB[dateStr] || {};
                    var saved = gridD[String(sid)] || gridD[sid];
                    if (saved === 'P' || saved === 'A' || saved === 'L') {
                        statusSel.value = saved;
                    } else {
                        statusSel.value = 'P';
                    }
                }

                document.querySelectorAll('.btn-open-attendance-modal').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var bid = parseInt(btn.getAttribute('data-batch-id'), 10);
                        document.getElementById('attendance_modal_batch_id').value = bid;
                        var dateInput = document.getElementById('attendance_modal_date');
                        if (dateInput && !dateInput.value) {
                            dateInput.value = new Date().toISOString().slice(0, 10);
                        }
                        fillAttendanceStudentSelect(bid);
                        var statusSel = document.getElementById('attendance_modal_status');
                        if (statusSel) {
                            statusSel.value = 'P';
                        }
                        var modal = document.getElementById('addAttendanceModal');
                        if (modal) {
                            if (typeof clearAjaxErrors === 'function') {
                                clearAjaxErrors('#addAttendanceModal');
                            } else {
                                modal.querySelectorAll('.ajax-error').forEach(function(el) {
                                    el.textContent = '';
                                });
                                var msgEl = modal.querySelector('.ajax-msg');
                                if (msgEl) {
                                    msgEl.innerHTML = '';
                                }
                            }
                        }
                        syncAttendanceStatusFromGrid();
                    });
                });

                var attDateInput = document.getElementById('attendance_modal_date');
                if (attDateInput) {
                    attDateInput.addEventListener('change', syncAttendanceStatusFromGrid);
                }
                var attStudentSel = document.getElementById('attendance_modal_student_id');
                if (attStudentSel) {
                    attStudentSel.addEventListener('change', syncAttendanceStatusFromGrid);
                }

                var attModal = document.getElementById('addAttendanceModal');
                if (attModal) {
                    attModal.addEventListener('hidden.bs.modal', function() {
                        var f = document.getElementById('addAttendanceForm');
                        if (f) {
                            f.reset();
                        }
                        var h = document.getElementById('attendance_modal_batch_id');
                        if (h) {
                            h.value = '';
                        }
                        var sel = document.getElementById('attendance_modal_student_id');
                        if (sel) {
                            sel.innerHTML = '<option value="">Select student</option>';
                        }
                        if (typeof clearAjaxErrors === 'function') {
                            clearAjaxErrors('#addAttendanceModal');
                        } else {
                            attModal.querySelectorAll('.ajax-error').forEach(function(el) {
                                el.textContent = '';
                            });
                            var msg = attModal.querySelector('.ajax-msg');
                            if (msg) {
                                msg.innerHTML = '';
                            }
                        }
                    });
                }

            });
        </script>
        <script>
            $(document).on('submit', '#addAttendanceForm', function(e) {
                e.preventDefault();
                clearAjaxErrors('#addAttendanceModal');

                const _this = $(this);
                _this.find('.submit-button').attr('disabled', 'disabled');
                _this.find('.submit-button').text('Saving...');

                const url = _this.attr('action');
                const data = _this.serialize();

                $.post(url, data, function(res) {
                    _this.find('.submit-button').removeAttr('disabled');
                    _this.find('.submit-button').text('Save attendance');
                    processAjaxResponse(res, 1000, '#addAttendanceModal', 'no');
                }, 'json').fail(function() {
                    _this.find('.submit-button').removeAttr('disabled');
                    _this.find('.submit-button').text('Save attendance');
                    $('#addAttendanceModal').find('.ajax-msg').html(
                        '<div class="alert alert-danger mb-0">Request failed</div>');
                });
            });
        </script>

        <style>
            /* Wider # track + same value for name `left` avoids date columns sliding under sticky labels */
            .attendance-date-scroll {
                --att-sticky-num-w: 4.5rem;
                -webkit-overflow-scrolling: touch;
            }

            .attendance-date-scroll .attendance-sticky-col {
                position: sticky;
                background-clip: padding-box;
                box-sizing: border-box;
            }

            .attendance-date-scroll thead .attendance-sticky-col {
                vertical-align: top;
                background-color: var(--bs-table-bg, var(--bs-body-bg, #fff));
            }

            .attendance-date-scroll tbody .attendance-sticky-col {
                background-color: var(--bs-table-bg, var(--bs-body-bg, #fff));
            }

            .attendance-date-scroll table.table-striped>tbody>tr:nth-of-type(odd)>td.attendance-sticky-col {
                background-color: var(--bs-table-striped-bg, rgba(0, 0, 0, 0.05));
            }

            .attendance-date-scroll table.table-striped>tbody>tr:nth-of-type(even)>td.attendance-sticky-col {
                background-color: var(--bs-table-bg, var(--bs-body-bg, #fff));
            }

            /* Sticky stacks above scrolling date cells (later DOM siblings otherwise paint on top) */
            .attendance-date-scroll thead th.attendance-date-head,
            .attendance-date-scroll tbody td.attendance-date-cell {
                position: relative;
                z-index: 1;
            }

            .attendance-date-scroll tbody .attendance-sticky-col--num {
                z-index: 11;
            }

            .attendance-date-scroll tbody .attendance-sticky-col--name {
                z-index: 10;
            }

            .attendance-date-scroll thead .attendance-sticky-col--num {
                z-index: 13;
            }

            .attendance-date-scroll thead .attendance-sticky-col--name {
                z-index: 12;
            }

            .attendance-date-scroll .attendance-sticky-col--num {
                left: 0;
                min-width: var(--att-sticky-num-w);
                width: var(--att-sticky-num-w);
                max-width: var(--att-sticky-num-w);
            }

            .attendance-date-scroll .attendance-sticky-col--name {
                left: var(--att-sticky-num-w);
                min-width: 12rem;
                max-width: 18rem;
                box-shadow: 4px 0 6px -3px rgba(33, 37, 41, 0.12);
            }
        </style>
    @endif
@endsection
