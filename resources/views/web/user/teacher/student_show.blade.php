@extends('web.user.layouts.app')

@section('content')
    @php
        $att = $stats_attendance ?? ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0, 'rate_pct' => null];
        $fees = $stats_fees ?? ['table_ready' => false, 'total_paid' => 0.0, 'payment_count' => 0, 'last_paid_at' => null];
        $overallPct = $stats_overall_marks_pct ?? null;
        $examRows = isset($exam_history) ? collect($exam_history)->take(5) : collect();
        $chartLabels = $performance_chart_labels ?? [];
        $chartSeries = $performance_chart_series ?? [];
    @endphp
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
                                <label class="form-label text-muted mb-1">Mapped Teachers</label>
                                <div class="fw-semibold">
                                    @if (isset($student_teachers) && count($student_teachers) > 0)
                                        {{ $student_teachers->pluck('name')->implode(', ') }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                            @php
                                $maps = isset($student_classroom_maps) ? $student_classroom_maps : collect();
                            @endphp
                            @if ($maps->count() > 0)
                                <div class="col-md-6">
                                    <label class="form-label text-muted mb-1">Classrooms &amp; batches</label>
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach ($maps as $row)
                                            <li class="mb-1">
                                                <span
                                                    class="fw-semibold">{{ optional($row->classroom)->name ?? 'â€”' }}</span>
                                                <span class="text-muted"> Â· </span>
                                                <span class="fw-semibold">{{ optional($row->batch)->name ?? 'â€”' }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <div class="col-md-6">
                                    <label class="form-label text-muted mb-1">Classroom</label>
                                    <div class="fw-semibold">
                                        {{ $student->classroom->name ?? ($student->batch->classroom->name ?? '-') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted mb-1">Batch</label>
                                    <div class="fw-semibold">{{ $student->batch->name ?? '-' }}</div>
                                </div>
                            @endif

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


        @php
            $teacherReportDownloadBase = url('user/teacher/students/' . base64_encode((string) $student->id) . '/report/download');
        @endphp
        <div class="row g-4 mt-0">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Academic Reports</h5>
                    </div>
                    <div class="card-body pt-2">
                        @include('web.user.partials.academic_reports_cards', [
                            'download_base_url' => $teacherReportDownloadBase,
                        ])
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-0">
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="d-block text-body-secondary mb-1">Overall marks</span>
                        <h3 class="mb-0 text-heading">
                            @if ($overallPct !== null)
                                {{ $overallPct }}<small class="fs-6 fw-normal text-body-secondary">%</small>
                            @else
                                <span class="fs-5 text-body-secondary">â€”</span>
                            @endif
                        </h3>
                        <small class="text-body-secondary">Average across graded exams (excludes absent)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="d-block text-body-secondary mb-1">Attendance</span>
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-3">
                            <div>
                                <h3 class="mb-0 text-heading">
                                    @if ($att['rate_pct'] !== null)
                                        {{ $att['rate_pct'] }}<small
                                            class="fs-6 fw-normal text-body-secondary">%</small>
                                    @else
                                        <span class="fs-5 text-body-secondary">â€”</span>
                                    @endif
                                </h3>
                                <small class="text-body-secondary d-block">Present + leave vs total sessions</small>
                            </div>
                            <ul class="list-unstyled mb-0 small text-sm-end">
                                <li><span class="text-success">Present</span> Â· {{ (int) $att['present'] }}</li>
                                <li><span class="text-danger">Absent</span> Â· {{ (int) $att['absent'] }}</li>
                                <li><span class="text-warning">Leave</span> Â· {{ (int) $att['late'] }}</li>
                                <li class="text-body-secondary mt-1">Total Â· {{ (int) $att['total'] }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="d-block text-body-secondary mb-1">Fee status</span>
                        @if (!empty($fees['table_ready']))
                            <h3 class="mb-0 text-heading">{{ number_format((float) $fees['total_paid'], 2) }}</h3>
                            <small class="text-body-secondary d-block">
                                {{ (int) $fees['payment_count'] }} payment record(s)
                                @if (!empty($fees['last_paid_at']))
                                    Â· Last {{ $fees['last_paid_at']->format('d M Y') }}
                                @endif
                            </small>
                            <a href="{{ url('user/teacher/fees/list') }}" class="btn btn-sm btn-label-primary mt-2">Fees
                                list</a>
                        @else
                            <p class="mb-0 text-body-secondary small">Fees module not available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-0">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Score by test</h5>
                        <small class="text-body-secondary">Each bar is score as % of max marks (by exam date)</small>
                    </div>
                    <div class="card-body">
                        <div id="teacherStudentPerformanceChart"
                            class="{{ count($chartLabels) ? '' : 'd-none' }}" style="min-height: 340px;"></div>
                        @if (count($chartLabels) === 0)
                            <p class="text-body-secondary mb-0 mt-2 small">No graded exam marks yet for this student in
                                your batches.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-0">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Exam history</h5>
                            <small class="text-body-secondary">Recent 5 exams only (newest first)</small>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="position-sticky top-0 bg-body z-1">
                                <tr>
                                    <th>Exam</th>
                                    <th>Date</th>
                                    <th>Marks</th>
                                    <th>Max</th>
                                    <th>%</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($examRows as $row)
                                    <tr>
                                        <td class="fw-medium">{{ $row['exam_name'] }}</td>
                                        <td>
                                            @if (!empty($row['exam_date']))
                                                {{ $row['exam_date']->format('d M Y') }}
                                            @else
                                                â€”
                                            @endif
                                        </td>
                                        <td>{{ $row['is_absent'] ? 'â€”' : $row['marks_raw'] }}</td>
                                        <td>{{ $row['max_marks'] ?? 'â€”' }}</td>
                                        <td>
                                            @if ($row['percentage'] !== null)
                                                {{ $row['percentage'] }}%
                                            @else
                                                â€”
                                            @endif
                                        </td>
                                        <td>
                                            @if ($row['is_absent'])
                                                <span class="badge bg-label-warning">Absent</span>
                                            @elseif ($row['percentage'] !== null)
                                                <span class="badge bg-label-success">Graded</span>
                                            @else
                                                <span class="badge bg-label-secondary">â€”</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-body-secondary py-4">No marks recorded
                                            for this student.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page_styles')
    <style>
        /* ApexCharts draws axis & data labels as SVG text; force readable theme colors */
        #teacherStudentPerformanceChart .apexcharts-xaxis text,
        #teacherStudentPerformanceChart .apexcharts-yaxis text {
            fill: var(--bs-body-color) !important;
        }

        #teacherStudentPerformanceChart .apexcharts-datalabels text {
            fill: var(--bs-body-color) !important;
        }

        #teacherStudentPerformanceChart .apexcharts-xaxis-title-text,
        #teacherStudentPerformanceChart .apexcharts-yaxis-title-text {
            fill: var(--bs-body-color) !important;
        }
    </style>
@endpush

@section('scripts')
    <script>
        (function() {
            var labels = @json($chartLabels);
            var series = @json($chartSeries);
            var el = document.querySelector('#teacherStudentPerformanceChart');
            if (!el || typeof ApexCharts === 'undefined') {
                return;
            }
            if (!labels.length) {
                el.innerHTML = '';
                return;
            }

            function cssVar(name, fallback) {
                var raw = getComputedStyle(document.documentElement).getPropertyValue(name);
                var v = raw && raw.trim ? raw.trim() : '';
                return v || fallback;
            }

            var labelColor = cssVar('--bs-body-color', '#435971');
            var tickColor = cssVar('--bs-secondary-color', '#697a8d');
            var borderRgb = cssVar('--bs-border-color-rgb', '67, 89, 113');
            var labelColorsArr = labels.map(function() {
                return labelColor;
            });

            var chartHeight = Math.max(400, 140 + labels.length * 28);

            var options = {
                chart: {
                    type: 'bar',
                    height: chartHeight,
                    toolbar: {
                        show: false
                    },
                    parentHeightOffset: 0,
                    fontFamily: 'inherit'
                },
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        borderRadiusApplication: 'end',
                        columnWidth: labels.length > 10 ? '72%' : '56%',
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                series: [{
                    name: 'Score %',
                    data: series
                }],
                xaxis: {
                    categories: labels,
                    labels: {
                        rotate: -35,
                        rotateAlways: labels.length > 4,
                        hideOverlappingLabels: false,
                        trim: false,
                        maxHeight: 160,
                        style: {
                            colors: labelColorsArr,
                            fontSize: '13px',
                            fontWeight: 600
                        }
                    },
                    axisBorder: {
                        show: true,
                        color: 'rgba(' + borderRgb + ', 0.55)'
                    },
                    axisTicks: {
                        show: true,
                        color: 'rgba(' + borderRgb + ', 0.55)'
                    }
                },
                yaxis: {
                    min: 0,
                    max: 100,
                    tickAmount: 5,
                    labels: {
                        formatter: function(v) {
                            return Math.round(Number(v)) + '%';
                        },
                        style: {
                            colors: tickColor,
                            fontSize: '12px',
                            fontWeight: 600
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    offsetY: -22,
                    style: {
                        fontSize: '12px',
                        fontWeight: 700,
                        colors: [labelColor]
                    },
                    formatter: function(val) {
                        return val != null ? val + '%' : '';
                    }
                },
                stroke: {
                    show: true,
                    width: 0,
                    colors: ['transparent']
                },
                fill: {
                    opacity: 1
                },
                colors: ['var(--bs-primary)'],
                grid: {
                    borderColor: 'rgba(' + borderRgb + ', 0.4)',
                    strokeDashArray: 4,
                    padding: {
                        top: 16,
                        right: 12,
                        bottom: 56,
                        left: 10
                    }
                },
                tooltip: {
                    theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light',
                    y: {
                        formatter: function(val) {
                            return val + '%';
                        }
                    }
                },
                legend: {
                    show: false
                }
            };
            var chart = new ApexCharts(el, options);
            chart.render();
        })();
    </script>
@endsection
