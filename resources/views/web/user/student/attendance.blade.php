@extends('web.user.student.layouts.app')

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
        @if (!($attendance_table_ready ?? false))
            <div class="card">
                <div class="card-body">
                    <p class="text-body-secondary mb-0">Attendance storage is not available yet.</p>
                </div>
            </div>
        @elseif(($attendance_batches ?? collect())->isEmpty())
            <div class="card">
                <div class="card-body">
                    <p class="text-body-secondary mb-0">You are not mapped to any batch for this teacher yet.</p>
                </div>
            </div>
        @else
            @php
                $batches = $attendance_batches;
                $tabCount = $batches->count();
            @endphp
            <div class="card">
                @if ($tabCount > 1)
                    <div class="card-header border-bottom-0 pb-0">
                        <ul class="nav nav-tabs card-header-tabs flex-nowrap overflow-auto" role="tablist">
                            @foreach ($batches as $ix => $batch)
                                @php
                                    $bid = (int) $batch->id;
                                    $tabLabel = \Illuminate\Support\Str::limit(trim($batch->classroom_name . ' — ' . $batch->batch_name), 42);
                                @endphp
                                <li class="nav-item" role="presentation">
                                    <button type="button" class="nav-link text-nowrap {{ $ix === 0 ? 'active' : '' }}"
                                        id="student-att-tab-{{ $bid }}" data-bs-toggle="tab"
                                        data-bs-target="#student-att-pane-{{ $bid }}" role="tab"
                                        aria-controls="student-att-pane-{{ $bid }}"
                                        aria-selected="{{ $ix === 0 ? 'true' : 'false' }}">
                                        {{ $tabLabel }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="card-body pt-3 {{ $tabCount > 1 ? 'pt-xl-3' : '' }}">
                    <div class="tab-content">
                        @foreach ($batches as $ix => $batch)
                            @php
                                $bid = (int) $batch->id;
                                $attDates = collect($batch->dates ?? [])->values();
                                $cells = is_array($batch->cells ?? null) ? $batch->cells : (array) ($batch->cells ?? []);
                                $attFilterFrom = \Illuminate\Support\Carbon::now()->startOfMonth()->format('Y-m-d');
                                $attFilterTo = \Illuminate\Support\Carbon::now()->endOfMonth()->format('Y-m-d');
                            @endphp
                            <div class="tab-pane fade {{ $ix === 0 ? 'show active' : '' }}"
                                id="student-att-pane-{{ $bid }}" role="tabpanel"
                                aria-labelledby="student-att-tab-{{ $bid }}" tabindex="0">
                                @if ($tabCount === 1)
                                    <p class="text-body-secondary small mb-3">{{ $batch->classroom_name }} —
                                        {{ $batch->batch_name }}</p>
                                @endif
                                {{-- Only date filters + attendance list (read-only) --}}
                                <div class="row g-3 mb-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label" for="attendance-filter-from-{{ $bid }}">From
                                            date</label>
                                        <input type="date" class="form-control js-attendance-date-from"
                                            id="attendance-filter-from-{{ $bid }}" data-batch-id="{{ $bid }}"
                                            data-default-date="{{ $attFilterFrom }}" value="{{ $attFilterFrom }}"
                                            max="2099-12-31">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="attendance-filter-to-{{ $bid }}">To date</label>
                                        <input type="date" class="form-control js-attendance-date-to"
                                            id="attendance-filter-to-{{ $bid }}" data-batch-id="{{ $bid }}"
                                            data-default-date="{{ $attFilterTo }}" value="{{ $attFilterTo }}"
                                            max="2099-12-31">
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
                                    <p class="text-body-secondary small mb-0">No attendance dates recorded for this batch
                                        yet.</p>
                                @else
                                    <div class="table-responsive attendance-date-scroll">
                                        <table class="table table-bordered table-striped align-middle text-nowrap"
                                            id="attendance-table-{{ $bid }}" data-batch-id="{{ $bid }}">
                                            <thead>
                                                <tr>
                                                    <th class="attendance-sticky-col attendance-sticky-col--num">#
                                                    </th>
                                                    <th class="attendance-sticky-col attendance-sticky-col--name">Student
                                                        Name</th>
                                                    @foreach ($attDates as $d)
                                                        <th class="text-center small align-top attendance-date-head"
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
                                                    <td class="attendance-sticky-col attendance-sticky-col--num">1</td>
                                                    <td
                                                        class="fw-medium attendance-sticky-col attendance-sticky-col--name">
                                                        {{ $student_display_name !== '' ? $student_display_name : 'Student' }}
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
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
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
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('input.js-attendance-date-from[data-batch-id]').forEach(function(inp) {
                    var bid = inp.getAttribute('data-batch-id');
                    if (bid) {
                        applyAttendanceDateRangeFilter(bid);
                    }
                });
            });

            document.addEventListener('shown.bs.tab', function(e) {
                var btn = e.target;
                if (!btn || !btn.getAttribute('data-bs-target')) {
                    return;
                }
                if (!String(btn.getAttribute('id') || '').startsWith('student-att-tab-')) {
                    return;
                }
                var pane = document.querySelector(btn.getAttribute('data-bs-target'));
                if (!pane) {
                    return;
                }
                var table = pane.querySelector('table[id^="attendance-table-"]');
                if (!table || !table.id) {
                    return;
                }
                var m = table.id.match(/^attendance-table-(\d+)$/);
                if (m && m[1]) {
                    applyAttendanceDateRangeFilter(m[1]);
                }
            });
        })();
    </script>
@endsection
