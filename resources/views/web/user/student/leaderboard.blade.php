@extends('web.user.student.layouts.app')
@section('title', $title ?? 'Leaderboard')
@once
    @push('page_styles')
        <style>
            .st-lb-meta-tile {
                min-height: 4.25rem;
            }
        </style>
    @endpush
@endonce
@section('content')
    @php
        $lbView = $leaderboard_view ?? 'aggregate';
        $lbBatchesInClass = $leaderboard_batches_in_class_json ?? [];
        $examsByBatch = $leaderboard_exams_by_batch_json ?? [];
        $defaultClassroomId = (int) ($leaderboard_default_classroom_id ?? 0);
        $defaultBatchId = (int) ($leaderboard_default_batch_id ?? 0);
    @endphp
    <div class="container-xxl flex-grow-1 pb-2 pt-0">
        <div class="row g-3 mb-2">
            <div class="col-12">
                <h4 class="mb-1 fs-5">Leaderboard</h4>
                <p class="mb-0 text-body-secondary small">
                    @if ($teacher)
                        @if ($lbView === 'test')
                            Change the batch and test filter to see ranking within the same batch.
                        @else
                            Change the batch filter to see rankings across all batches in your class, or within a single
                            batch only.
                        @endif
                    @else
                        Select a teacher context to view rankings.
                    @endif
                </p>
            </div>
        </div>

        @if ($lbView === 'test')
            <div class="row g-2 mb-2">
                <div class="col-md-6 col-lg-4">
                    <label class="form-label small mb-1" for="st-lb-batch-test">Batch</label>
                    <select class="form-select" id="st-lb-batch-test"
                        @if ($defaultClassroomId <= 0 || count($lbBatchesInClass) === 0) disabled @endif>
                        @forelse ($lbBatchesInClass as $b)
                            <option value="{{ (int) $b['id'] }}" @selected($defaultBatchId === (int) $b['id'])>
                                {{ $b['name'] }}</option>
                        @empty
                            <option value="">No batch linked</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label small mb-1" for="st-lb-exam-test">Test</label>
                    <select class="form-select" id="st-lb-exam-test" disabled>
                        <option value="">Select batch first</option>
                    </select>
                </div>
            </div>
        @endif

        <div class="card border shadow-none">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom py-2">
                <div>
                    <h5 class="mb-1 text-heading fs-6">
                        @if ($lbView === 'test')
                            Test-wise ranking
                        @else
                            Class / batch — overall ranking
                        @endif
                    </h5>
                    <p class="mb-0 small text-body-secondary" id="st-lb-scope-label">—</p>
                </div>
                @if ($lbView !== 'test')
                    <div class="d-flex flex-wrap align-items-center gap-2 ms-auto justify-content-end">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label small mb-0 text-nowrap" for="st-lb-batch-ag">Batch</label>
                            <select class="form-select form-select-sm" id="st-lb-batch-ag" style="min-width: 220px;"
                                @if ($defaultClassroomId <= 0 || count($lbBatchesInClass) === 0) disabled @endif>
                                <option value="0">All batches in this class</option>
                                @foreach ($lbBatchesInClass as $b)
                                    <option value="{{ (int) $b['id'] }}">{{ $b['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>
            <div class="card-body py-3">
                <div class="row g-2 mb-2 pt-2" id="st-lb-meta-row">
                    <div class="col-6 col-md-3">
                        <div
                            class="st-lb-meta-tile border rounded px-2 py-2 h-100 d-flex flex-column justify-content-center">
                            <p class="small text-body-secondary mb-0">Your rank</p>
                            <h6 class="mb-0 text-heading mt-1" id="st-lb-your-rank">—</h6>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div
                            class="st-lb-meta-tile border rounded px-2 py-2 h-100 d-flex flex-column justify-content-center">
                            <p class="small text-body-secondary mb-0">Your %</p>
                            <h6 class="mb-0 text-heading mt-1" id="st-lb-your-pct">—</h6>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div
                            class="st-lb-meta-tile border rounded px-2 py-2 h-100 d-flex flex-column justify-content-center">
                            <p class="small text-body-secondary mb-0">Marks obtained</p>
                            <h6 class="mb-0 text-heading mt-1" id="st-lb-marks-got">—</h6>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div
                            class="st-lb-meta-tile border rounded px-2 py-2 h-100 d-flex flex-column justify-content-center">
                            <p class="small text-body-secondary mb-0">Total possible</p>
                            <h6 class="mb-0 text-heading mt-1" id="st-lb-marks-max">—</h6>
                        </div>
                    </div>
                </div>

                <p id="st-lb-empty" class="text-body-secondary mb-0 d-none"></p>
                <div id="st-lb-chart" class="d-none"></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            var chartDataUrl = @json($leaderboard_chart_data_url ?? url('user/student/leaderboard/chart-data'));
            var lbView = @json($lbView);
            var examsByBatch = @json($examsByBatch);
            var defaultClassroomId = @json($defaultClassroomId);
            var defaultBatchId = @json($defaultBatchId);
            var chartInstance = null;

            function escapeHtml(t) {
                return String(t || '').replace(/[&<>"']/g, function(m) {
                    return ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    })[m];
                });
            }

            function setMeta(meta) {
                var scope = (meta && meta.scope_label) ? String(meta.scope_label) : '—';
                document.getElementById('st-lb-scope-label').textContent = scope;

                var tr = meta && meta.total_ranked != null ? Number(meta.total_ranked) : 0;
                var yr = meta && meta.your_rank != null ? Number(meta.your_rank) : null;
                document.getElementById('st-lb-your-rank').textContent =
                    yr != null && !isNaN(yr) && tr > 0 ? (yr + ' / ' + tr) : (tr === 0 ? '—' : 'Not ranked');

                var yp = meta && meta.your_percentage != null ? meta.your_percentage : null;
                document.getElementById('st-lb-your-pct').textContent =
                    yp != null && !isNaN(Number(yp)) ? (Number(yp) + '%') : '—';

                document.getElementById('st-lb-marks-got').textContent =
                    meta && meta.obtained_marks != null ? String(meta.obtained_marks) : '—';
                document.getElementById('st-lb-marks-max').textContent =
                    meta && meta.total_marks != null ? String(meta.total_marks) : '—';
            }

            function destroyChart() {
                if (chartInstance && typeof chartInstance.destroy === 'function') {
                    chartInstance.destroy();
                }
                chartInstance = null;
            }

            function renderChart(points, view) {
                var el = document.getElementById('st-lb-chart');
                var emptyEl = document.getElementById('st-lb-empty');
                if (!el || !emptyEl) {
                    return;
                }
                destroyChart();
                el.innerHTML = '';

                if (!Array.isArray(points) || points.length === 0) {
                    emptyEl.textContent =
                        'No numeric marks in this scope yet. Your teacher must record marks and set positive max marks on tests.';
                    emptyEl.classList.remove('d-none');
                    el.classList.add('d-none');
                    return;
                }

                emptyEl.classList.add('d-none');
                emptyEl.textContent = '';
                el.classList.remove('d-none');

                if (typeof ApexCharts === 'undefined' || typeof config === 'undefined') {
                    emptyEl.textContent = 'Chart library is not available.';
                    emptyEl.classList.remove('d-none');
                    el.classList.add('d-none');
                    return;
                }

                var labelColor = config.colors.textMuted;
                var borderColor = config.colors.borderColor;
                var fontFamily = config.fontFamily;
                var primary = config.colors.primary;
                var selfColor = config.colors.success || primary;

                var categories = points.map(function(p) {
                    var n = escapeHtml(p.name || '');
                    if (p.is_self) {
                        n += ' (you)';
                    }
                    return n;
                });
                var data = points.map(function(p) {
                    return p.percentage;
                });
                var colors = points.map(function(p) {
                    return p.is_self ? selfColor : primary;
                });
                var rotate = categories.length > 5 ? -45 : categories.length > 3 ? -35 : 0;
                var n = categories.length;
                var chartHeight = Math.min(420, Math.max(240, 140 + n * 14));
                var barColumnWidth = n <= 2 ? '24%' : n <= 6 ? '32%' : '40%';

                var seriesLabel = view === 'test' ? 'Score % (this test)' : 'Overall % (included tests)';

                var options = {
                    series: [{
                        name: seriesLabel,
                        data: data
                    }],
                    chart: {
                        type: 'bar',
                        height: chartHeight,
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 4,
                            borderRadiusApplication: 'end',
                            columnWidth: barColumnWidth,
                            maxColumnWidth: 48,
                            distributed: true,
                            horizontal: false
                        }
                    },
                    colors: colors,
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        show: true,
                        width: 0,
                        colors: ['transparent']
                    },
                    xaxis: {
                        categories: categories,
                        labels: {
                            rotate: rotate,
                            rotateAlways: rotate !== 0,
                            maxHeight: 72,
                            trim: true,
                            style: {
                                colors: labelColor,
                                fontSize: categories.length > 14 ? '9px' : '11px',
                                fontFamily: fontFamily
                            }
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        }
                    },
                    yaxis: {
                        min: 0,
                        max: 100,
                        tickAmount: 4,
                        labels: {
                            formatter: function(v) {
                                return v + '%';
                            },
                            style: {
                                colors: labelColor,
                                fontSize: '11px',
                                fontFamily: fontFamily
                            }
                        }
                    },
                    grid: {
                        strokeDashArray: 6,
                        borderColor: borderColor,
                        padding: {
                            top: 4,
                            bottom: rotate !== 0 ? 4 : 2
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(v, opts) {
                                var i = opts.dataPointIndex;
                                var p = points[i];
                                if (!p) {
                                    return v + '%';
                                }
                                var lines = [v + '%', 'Rank: ' + (p.rank || '—')];
                                if (p.marks != null && view === 'test') {
                                    lines.push('Marks: ' + p.marks);
                                }
                                return lines.join(' · ');
                            }
                        }
                    }
                };

                chartInstance = new ApexCharts(el, options);
                chartInstance.render();
            }

            function loadChart() {
                var url = chartDataUrl;
                var emptyEl = document.getElementById('st-lb-empty');
                var chartEl = document.getElementById('st-lb-chart');
                var params = {
                    view: lbView === 'test' ? 'test' : 'aggregate'
                };
                if (lbView === 'test') {
                    var b = document.getElementById('st-lb-batch-test');
                    var e = document.getElementById('st-lb-exam-test');
                    if (!b || !e) {
                        return;
                    }
                    if (b.disabled || !b.value) {
                        if (emptyEl) {
                            emptyEl.textContent = 'No batches in this classroom for your enrollment.';
                            emptyEl.classList.remove('d-none');
                        }
                        if (chartEl) {
                            chartEl.classList.add('d-none');
                        }
                        destroyChart();
                        return;
                    }
                    params.batch_id = b.value;
                    params.exam_id = e.value;
                } else {
                    if (!defaultClassroomId) {
                        if (emptyEl) {
                            emptyEl.textContent = 'No class is linked to your account for this teacher.';
                            emptyEl.classList.remove('d-none');
                        }
                        if (chartEl) {
                            chartEl.classList.add('d-none');
                        }
                        destroyChart();
                        return;
                    }
                    var ag = document.getElementById('st-lb-batch-ag');
                    if (ag) {
                        params.batch_id = ag.value || '0';
                    }
                }

                if (lbView === 'test' && (!params.batch_id || !params.exam_id)) {
                    if (emptyEl) {
                        emptyEl.textContent = 'Select a batch and a test to load the chart.';
                        emptyEl.classList.remove('d-none');
                    }
                    if (chartEl) {
                        chartEl.classList.add('d-none');
                    }
                    destroyChart();
                    return;
                }

                if (emptyEl) {
                    emptyEl.textContent = 'Loading…';
                    emptyEl.classList.remove('d-none');
                }
                if (chartEl) {
                    chartEl.classList.add('d-none');
                }

                $.get(url, params, function(res) {
                    if (!res || res.status != 1) {
                        if (emptyEl) {
                            emptyEl.textContent = (res && res.error) ? res.error : 'Could not load data.';
                            emptyEl.classList.remove('d-none');
                        }
                        destroyChart();
                        return;
                    }
                    setMeta(res.meta || {});
                    var tr = res.meta && res.meta.total_ranked != null ? Number(res.meta.total_ranked) : 0;
                    if (tr === 0 || !res.points || !res.points.length) {
                        if (emptyEl) {
                            emptyEl.textContent =
                                'No ranked students in this scope yet (needs numeric marks with positive max marks).';
                            emptyEl.classList.remove('d-none');
                        }
                        if (chartEl) {
                            chartEl.classList.add('d-none');
                        }
                        destroyChart();
                        return;
                    }
                    renderChart(res.points, res.view || lbView);
                }, 'json').fail(function() {
                    if (emptyEl) {
                        emptyEl.textContent = 'Could not load leaderboard data.';
                        emptyEl.classList.remove('d-none');
                    }
                    destroyChart();
                });
            }

            function repopulateExams(batchId, selectedExamId) {
                var $exam = $('#st-lb-exam-test');
                var list = examsByBatch[String(batchId)] || examsByBatch[batchId] || [];
                if (!Array.isArray(list) || !list.length) {
                    $exam.html('<option value="">No tests in this batch</option>').prop('disabled', true);
                    return;
                }
                var html = '';
                list.forEach(function(ex) {
                    var sel = String(ex.id) === String(selectedExamId) ? ' selected' : '';
                    var lab = escapeHtml(ex.exam_name || 'Test');
                    if (ex.exam_date) {
                        lab += ' (' + escapeHtml(String(ex.exam_date).slice(0, 10)) + ')';
                    }
                    html += '<option value="' + ex.id + '"' + sel + '>' + lab + '</option>';
                });
                $exam.html(html).prop('disabled', false);
            }

            $(function() {
                if (lbView === 'test') {
                    var $batchTest = $('#st-lb-batch-test');
                    if (!$batchTest.length || $batchTest.prop('disabled')) {
                        var emptyNo = document.getElementById('st-lb-empty');
                        if (emptyNo) {
                            emptyNo.textContent =
                                'No batch is linked for your class with this teacher, so test-wise ranking is not available.';
                            emptyNo.classList.remove('d-none');
                        }
                        return;
                    }
                    $batchTest.on('change', function() {
                        repopulateExams($(this).val(), null);
                        var firstE = $('#st-lb-exam-test option:first').val();
                        if (firstE) {
                            $('#st-lb-exam-test').val(firstE);
                        }
                        loadChart();
                    });
                    $('#st-lb-exam-test').on('change', function() {
                        loadChart();
                    });
                    repopulateExams($batchTest.val(), null);
                    var $eo = $('#st-lb-exam-test option:first');
                    if ($eo.length && $eo.val()) {
                        $('#st-lb-exam-test').val($eo.val());
                    }
                    loadChart();
                } else {
                    if (!defaultClassroomId) {
                        var emptyAg = document.getElementById('st-lb-empty');
                        if (emptyAg) {
                            emptyAg.textContent =
                                'No class is linked to your account for this teacher, so the leaderboard is not available.';
                            emptyAg.classList.remove('d-none');
                        }
                        return;
                    }
                    $('#st-lb-batch-ag').on('change', function() {
                        loadChart();
                    });
                    loadChart();
                }
            });
        })();
    </script>
@endsection
