@extends('web.user.layouts.app')

@push('page_styles')
    <style>
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
@endpush

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="mb-3">
            <a href="{{ url('user/parent/classrooms') }}" class="btn btn-sm btn-label-secondary">
                <i class="icon-base ti tabler-arrow-left me-1"></i>
                Back to classrooms
            </a>
        </div>
        @if (($classroom->batches ?? collect())->isEmpty())
            <div class="alert alert-info mb-0">
                No batches in this classroom yet. The teacher will add them when ready.
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
            <div class="row">
                <div class="col-md-12">
                    <strong>
                        <h5>{{ $classroom->name }}</h5>
                    </strong>
                    @php
                        $childLabel = optional($selected_student ?? null)->name;
                        if ($childLabel === null || $childLabel === '') {
                            $childLabel = ($student_display_name ?? '') !== '' ? $student_display_name : null;
                        }
                    @endphp
                    <div class="card mb-4 mt-3">
                        <div class="card-body py-3">
                            <div
                                class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
                                <div class="nav-align-top mb-0 flex-grow-1">
                                    <ul class="nav nav-pills flex-column flex-md-row mb-0 gap-md-0 gap-2"
                                        id="classroomBatchTabs" role="tablist">
                                        @foreach ($classroom->batches as $batch)
                                            @php
                                                $isDefaultBatchTab =
                                                    (int) $batch->id === (int) ($default_batch_id ?? 0);
                                            @endphp
                                            <li class="nav-item" role="presentation">
                                                <button type="button"
                                                    class="nav-link @if ($isDefaultBatchTab) active @endif"
                                                    id="batch-tab-{{ $batch->id }}" data-bs-toggle="tab"
                                                    data-bs-target="#batch-pane-{{ $batch->id }}" role="tab"
                                                    aria-controls="batch-pane-{{ $batch->id }}"
                                                    aria-selected="{{ $isDefaultBatchTab ? 'true' : 'false' }}">
                                                    <i class="icon-base ti tabler-folders icon-sm me-1_5"></i>
                                                    {{ $batch->name }}
                                                    @if (! empty($batch->is_past_batch_tab))
                                                        <span class="badge bg-label-secondary ms-1">Previous</span>
                                                    @endif
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="demo-inline-spacing mt-4">
                        <div class="list-group list-group-horizontal-md text-md-center" role="tablist">
                            <a class="list-group-item list-group-item-action active" id="home-list-item"
                                data-bs-toggle="list" href="#horizontal-home" role="tab">Tests</a>
                            <a class="list-group-item list-group-item-action" id="attendance-list-item"
                                data-bs-toggle="list" href="#horizontal-attendance" role="tab">Attendance</a>
                        </div>
                        <div class="tab-content px-0 mt-0">
                            <div class="tab-pane fade show active" id="horizontal-home" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-6">

                                        @foreach ($classroom->batches as $batch)
                                            <div class="batch-tests @if ((int) $batch->id !== (int) ($default_batch_id ?? 0)) d-none @endif"
                                                id="batch-tests-{{ $batch->id }}">

                                                @if ($batch->exams->isEmpty())
                                                    <div class="alert alert-info">
                                                        No tests available in this batch.
                                                    </div>
                                                @else
                                                    <div class="row g-3">
                                                        @foreach ($batch->exams as $exam)
                                                            @php
                                                                $obtained = $exam->student_marks_obtained;
                                                                $isAbsent = !empty($exam->student_is_absent);
                                                                $absentDisplay = $exam->student_absent_display ?? null;
                                                                $hasMark =
                                                                    !$isAbsent && $obtained !== null && $obtained !== '';
                                                                $highlight = $highlight_exam_id == $exam->id;
                                                            @endphp

                                                            <div class="col-lg-4 col-md-6" id="exam-{{ $exam->id }}">
                                                                <div
                                                                    class="card h-100 shadow-sm {{ $highlight ? 'border border-primary' : '' }}">
                                                                    <div class="card-body">

                                                                        <div
                                                                            class="d-flex justify-content-between align-items-center mb-2">
                                                                            <h5 class="mb-0 text-truncate">
                                                                                {{ $exam->exam_name }}
                                                                            </h5>

                                                                            <span class="badge bg-label-primary">
                                                                                @if ($isAbsent && $absentDisplay)
                                                                                    {{ $absentDisplay }}
                                                                                @elseif ($hasMark)
                                                                                    {{ $obtained }}
                                                                                @else
                                                                                    —
                                                                                @endif
                                                                                @if ($exam->max_marks)
                                                                                    / {{ $exam->max_marks }}
                                                                                @endif
                                                                            </span>
                                                                        </div>

                                                                        <ul class="list-unstyled mb-2 small">
                                                                            <li>
                                                                                <strong>Teacher:</strong>
                                                                                {{ $teacher->name ?? '—' }}
                                                                            </li>

                                                                            <li>
                                                                                <strong>Test:</strong>
                                                                                {{ $exam->exam_name }}
                                                                            </li>

                                                                            <li>
                                                                                <strong>Max Marks:</strong>
                                                                                {{ $exam->max_marks ?? '—' }}
                                                                            </li>

                                                                            <li>
                                                                                <strong>Total Marks:</strong>
                                                                                @if ($isAbsent && $absentDisplay)
                                                                                    {{ $absentDisplay }}
                                                                                @else
                                                                                    {{ $hasMark ? $obtained : 'Not entered' }}
                                                                                @endif
                                                                            </li>
                                                                        </ul>

                                                                        @if ($exam->exam_date)
                                                                            <div
                                                                                class="text-muted small border-top pt-2">
                                                                                <i class="ti tabler-calendar me-1"></i>
                                                                                {{ $exam->exam_date->format('M d, Y') }}
                                                                            </div>
                                                                        @endif

                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach

                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="horizontal-attendance" role="tabpanel"
                                aria-labelledby="attendance-list-item">
                                @if (!($attendance_table_ready ?? false))
                                    <div class="alert alert-info mb-0">Attendance storage is not available yet.</div>
                                @elseif(!$classroom->batches->contains(fn($b) => $b->is_my_batch))
                                    <div class="alert alert-info mb-0">This student is not enrolled in any batch in this
                                        classroom.</div>
                                    @else
                                        @foreach ($classroom->batches as $batch)
                                            @php
                                                $bid = (int) $batch->id;
                                            $attDates = collect($batch->attendance_dates ?? [])->values();
                                            $cells = is_array($batch->attendance_cells ?? null)
                                                ? $batch->attendance_cells
                                                : (array) ($batch->attendance_cells ?? []);
                                            $attFilterFrom = \Illuminate\Support\Carbon::now()
                                                ->startOfMonth()
                                                ->format('Y-m-d');
                                            $attFilterTo = \Illuminate\Support\Carbon::now()
                                                ->endOfMonth()
                                                ->format('Y-m-d');
                                        @endphp
                                        <div class="batch-attendance @if ((int) $batch->id !== (int) ($default_batch_id ?? 0)) d-none @endif"
                                            id="batch-attendance-{{ $bid }}">
                                            <p class="text-body-secondary small mb-3">{{ $batch->name }}</p>
                                            <div class="row g-3 mb-3 align-items-end">
                                                <div class="col-md-4">
                                                    <label class="form-label"
                                                        for="classroom-attendance-filter-from-{{ $bid }}">From
                                                        date</label>
                                                    <input type="date" class="form-control js-attendance-date-from"
                                                        id="classroom-attendance-filter-from-{{ $bid }}"
                                                        data-batch-id="{{ $bid }}"
                                                        data-default-date="{{ $attFilterFrom }}"
                                                        value="{{ $attFilterFrom }}" max="2099-12-31">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label"
                                                        for="classroom-attendance-filter-to-{{ $bid }}">To date</label>
                                                    <input type="date" class="form-control js-attendance-date-to"
                                                        id="classroom-attendance-filter-to-{{ $bid }}"
                                                        data-batch-id="{{ $bid }}"
                                                        data-default-date="{{ $attFilterTo }}"
                                                        value="{{ $attFilterTo }}" max="2099-12-31">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label d-block">&nbsp;</label>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <button type="button"
                                                            class="btn btn-primary btn-lg-2 js-attendance-date-search-btn"
                                                            data-batch-id="{{ $bid }}">
                                                            Search
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-label-secondary btn-lg-2 js-attendance-date-reset-btn"
                                                            data-batch-id="{{ $bid }}">
                                                            Reset
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($attDates->isEmpty())
                                                <p class="text-body-secondary small mb-0">No attendance dates recorded
                                                    for this batch yet.</p>
                                            @else
                                                <div class="table-responsive attendance-date-scroll">
                                                    <table
                                                        class="table table-bordered table-striped align-middle text-nowrap"
                                                        id="attendance-table-{{ $bid }}" data-batch-id="{{ $bid }}">
                                                        <thead>
                                                            <tr>
                                                                <th
                                                                    class="attendance-sticky-col attendance-sticky-col--num">
                                                                    #</th>
                                                                <th
                                                                    class="attendance-sticky-col attendance-sticky-col--name">
                                                                    Student Name</th>
                                                                @foreach ($attDates as $d)
                                                                    <th
                                                                        class="text-center small align-top attendance-date-head"
                                                                        data-attendance-date="{{ $d }}"
                                                                        data-batch-id="{{ $bid }}">
                                                                        <div class="fw-semibold">
                                                                            {{ \Illuminate\Support\Carbon::parse($d)->format('d M Y') }}
                                                                        </div>
                                                                    </th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr class="attendance-student-row"
                                                                data-student-id="{{ (int) ($student_id ?? 0) }}">
                                                                <td
                                                                    class="attendance-sticky-col attendance-sticky-col--num">
                                                                    1</td>
                                                                <td
                                                                    class="fw-medium attendance-sticky-col attendance-sticky-col--name">
                                                                    {{ ($student_display_name ?? '') !== '' ? $student_display_name : 'Student' }}
                                                                </td>
                                                                @foreach ($attDates as $d)
                                                                    @php
                                                                        $attCell = $cells[$d] ?? '';
                                                                        $attCellDisplay = $attCell !== '' ? $attCell : '—';
                                                                    @endphp
                                                                    <td class="text-center attendance-date-cell p-1 align-middle"
                                                                        data-attendance-date="{{ $d }}"
                                                                        data-batch-id="{{ $bid }}">
                                                                        <span
                                                                            class="attendance-cell-display d-inline-block py-1">{{ $attCellDisplay }}</span>
                                                                    </td>
                                                                @endforeach
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const defaultBatch = "{{ $default_batch_id }}";

            function applyAttendanceDateRangeFilter(batchId) {
                var b = String(batchId);
                var fromEl = document.getElementById('classroom-attendance-filter-from-' + b) ||
                    document.getElementById('attendance-filter-from-' + b);
                var toEl = document.getElementById('classroom-attendance-filter-to-' + b) ||
                    document.getElementById('attendance-filter-to-' + b);
                var table = document.getElementById('attendance-table-' + b);
                if (!table || !fromEl || !toEl) {
                    return;
                }
                var fromVal = fromEl.value ? String(fromEl.value).trim() : '';
                var toVal = toEl.value ? String(toEl.value).trim() : '';
                if (fromVal && toVal && fromVal > toVal) {
                    var tmp = fromVal;
                    fromVal = toVal;
                    toVal = tmp;
                    fromEl.value = fromVal;
                    toEl.value = toVal;
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

            function showBatch(batchId) {
                document.querySelectorAll('#classroomBatchTabs .nav-link').forEach(function(btn) {
                    var id = btn.id || '';
                    var isActive = id === ('batch-tab-' + batchId);
                    btn.classList.toggle('active', isActive);
                    btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                document.querySelectorAll('.batch-tests').forEach(el => {
                    el.classList.add('d-none');
                });
                document.querySelectorAll('.batch-attendance').forEach(el => {
                    el.classList.add('d-none');
                });

                let activeTests = document.getElementById('batch-tests-' + batchId);
                if (activeTests) {
                    activeTests.classList.remove('d-none');
                }
                let activeAtt = document.getElementById('batch-attendance-' + batchId);
                if (activeAtt) {
                    activeAtt.classList.remove('d-none');
                }
                applyAttendanceDateRangeFilter(batchId);
            }

            if (defaultBatch) {
                showBatch(defaultBatch);
            }

            document.querySelectorAll('[id^="batch-tab-"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    let batchId = this.id.replace('batch-tab-', '');
                    showBatch(batchId);
                });
            });

            document.addEventListener('click', function(e) {
                var rangeSearchBtn = e.target.closest('.js-attendance-date-search-btn');
                if (rangeSearchBtn) {
                    e.preventDefault();
                    var bid = rangeSearchBtn.getAttribute('data-batch-id');
                    if (bid) {
                        applyAttendanceDateRangeFilter(bid);
                    }
                    return;
                }
                var rangeResetBtn = e.target.closest('.js-attendance-date-reset-btn');
                if (rangeResetBtn) {
                    e.preventDefault();
                    var rb = rangeResetBtn.getAttribute('data-batch-id');
                    if (!rb) {
                        return;
                    }
                    var fromReset = document.getElementById('classroom-attendance-filter-from-' + rb) ||
                        document.getElementById('attendance-filter-from-' + rb);
                    var toReset = document.getElementById('classroom-attendance-filter-to-' + rb) ||
                        document.getElementById('attendance-filter-to-' + rb);
                    if (fromReset) {
                        fromReset.value = fromReset.getAttribute('data-default-date') || '';
                    }
                    if (toReset) {
                        toReset.value = toReset.getAttribute('data-default-date') || '';
                    }
                    applyAttendanceDateRangeFilter(rb);
                }
            });

            function getCurrentBatchId() {
                var active = document.querySelector('#classroomBatchTabs .nav-link.active[id^="batch-tab-"]');
                if (!active || !active.id) {
                    return defaultBatch ? String(defaultBatch) : '';
                }
                return active.id.replace('batch-tab-', '');
            }

            var attendanceListItem = document.getElementById('attendance-list-item');
            if (attendanceListItem) {
                attendanceListItem.addEventListener('click', function() {
                    setTimeout(function() {
                        var bid = getCurrentBatchId();
                        if (bid) {
                            applyAttendanceDateRangeFilter(bid);
                        }
                    }, 10);
                });
            }

            let highlightExam = "{{ $highlight_exam_id }}";
            if (highlightExam) {
                setTimeout(() => {
                    let el = document.getElementById('exam-' + highlightExam);
                    if (el) {
                        el.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }, 400);
            }

        });
    </script>
@endsection
