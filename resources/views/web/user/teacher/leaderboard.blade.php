@extends('web.user.layouts.app')
@php
    $lbView = $leaderboard_view ?? 'test';
    $showAggregateMarksTooltip =
        $lbView === 'batch'
        && in_array($leaderboard_scope ?? '', ['classroom', 'batch'], true);
    $teacherTestSearchReady =
        $lbView === 'test'
        && ($selected_classroom_id ?? 0) > 0
        && ($selected_batch_id ?? 0) > 0
        && ($selected_exam_id ?? 0) > 0;
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
        <div class="row g-6">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $title ?? 'Leaderboard' }}</h5>
                    </div>
                    <div class="card-body">
                        @if ($lbView === 'batch')
                            <form method="get" action="{{ url('user/teacher/leaderboard') }}"
                                id="leaderboard-filter-form" class="row g-3 align-items-end mb-4">
                                <input type="hidden" name="view" value="batch">
                                <div class="col-md-4 col-lg-3">
                                    <label class="form-label" for="lb-classroom">Classroom</label>
                                    <select class="form-select" name="classroom_id" id="lb-classroom">
                                        <option value="">Choose classroom</option>
                                        @foreach ($classrooms ?? [] as $c)
                                            <option value="{{ $c->id }}"
                                                {{ (int) ($selected_classroom_id ?? 0) === (int) $c->id ? 'selected' : '' }}>
                                                {{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 col-lg-3">
                                    <label class="form-label" for="lb-batch">Batch</label>
                                    <select class="form-select" name="batch_id" id="lb-batch"
                                        {{ ($selected_classroom_id ?? 0) > 0 ? '' : 'disabled' }}>
                                        <option value="">Choose batch</option>
                                        @if (($selected_classroom_id ?? 0) > 0)
                                            <option value="0"
                                                {{ (int) ($selected_batch_id ?? 0) === 0 ? 'selected' : '' }}>
                                                All batches in this class</option>
                                        @endif
                                        @foreach ($batches ?? [] as $b)
                                            <option value="{{ $b->id }}"
                                                {{ (int) ($selected_batch_id ?? 0) === (int) $b->id ? 'selected' : '' }}>
                                                {{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 col-lg-3">
                                    <button type="submit" name="search" value="1" class="btn btn-primary">
                                        <i class="icon-base ti tabler-search me-1"></i> Search
                                    </button>
                                </div>
                                <div class="col-12">
                                    <p class="text-body-secondary small mb-0">Select a <strong>classroom</strong> and
                                        Search to rank students by average % across all tests in that class (all batches).
                                        Pick a <strong>batch</strong> to rank within that batch only.</p>
                                </div>
                            </form>
                        @else
                            <form method="get" action="{{ url('user/teacher/leaderboard') }}"
                                id="leaderboard-filter-form" class="row g-3 align-items-end mb-4">
                                <input type="hidden" name="view" value="test">
                                <div class="col-md-4 col-lg-3">
                                    <label class="form-label" for="lb-classroom">Classroom</label>
                                    <select class="form-select" name="classroom_id" id="lb-classroom">
                                        <option value="">Choose classroom</option>
                                        @foreach ($classrooms ?? [] as $c)
                                            <option value="{{ $c->id }}"
                                                {{ (int) ($selected_classroom_id ?? 0) === (int) $c->id ? 'selected' : '' }}>
                                                {{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 col-lg-3">
                                    <label class="form-label" for="lb-batch">Batch</label>
                                    <select class="form-select" name="batch_id" id="lb-batch"
                                        {{ ($selected_classroom_id ?? 0) > 0 ? '' : 'disabled' }}>
                                        <option value="">Choose batch</option>
                                        @foreach ($batches ?? [] as $b)
                                            <option value="{{ $b->id }}"
                                                {{ (int) ($selected_batch_id ?? 0) === (int) $b->id ? 'selected' : '' }}>
                                                {{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 col-lg-3">
                                    <label class="form-label" for="lb-exam">Test</label>
                                    <select class="form-select" name="exam_id" id="lb-exam"
                                        {{ ($selected_batch_id ?? 0) > 0 ? '' : 'disabled' }}>
                                        <option value="">Choose test</option>
                                        @foreach ($exams ?? [] as $e)
                                            <option value="{{ $e->id }}"
                                                {{ (int) ($selected_exam_id ?? 0) === (int) $e->id ? 'selected' : '' }}>
                                                {{ $e->exam_name }}
                                                @if (!empty($e->exam_date))
                                                    ({{ $e->exam_date->format('d M Y') }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 col-lg-3">
                                    <button type="submit" name="search" value="1" id="lb-search-btn"
                                        class="btn btn-primary" @disabled(!$teacherTestSearchReady)>
                                        <i class="icon-base ti tabler-search me-1"></i> Search
                                    </button>
                                </div>
                                <div class="col-12">
                                    <p class="text-body-secondary small mb-0">Select a <strong>classroom</strong>, choose a
                                        <strong>batch</strong> and a <strong>test</strong>, then click <strong>Search</strong>
                                        to see the leaderboard for that test.</p>
                                </div>
                            </form>
                        @endif

                        <div class="mt-1">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title mb-0">
                                        <h5 class="mb-1">
                                            @if (($leaderboard_scope ?? '') === 'exam')
                                                This test — by student
                                            @elseif (($leaderboard_scope ?? '') === 'batch')
                                                This batch — by student
                                            @elseif (($leaderboard_scope ?? '') === 'classroom')
                                                This classroom — by student
                                            @else
                                                Student performance
                                            @endif
                                        </h5>
                                        <p class="card-subtitle text-body-secondary mb-0">
                                            @if ($leaderboard_chart === null)
                                                @if ($lbView === 'batch')
                                                    Pick a classroom, optionally a batch, then Search. Bars show each
                                                    student’s average % across all tests in that scope (whole class or
                                                    one batch).
                                                @else
                                                    Select a <strong>classroom</strong>, <strong>batch</strong>, and
                                                    <strong>test</strong>, then Search. Bars show each student’s score %
                                                    for that test.
                                                @endif
                                            @elseif (($leaderboard_scope ?? '') === 'classroom' && ($leaderboard_classroom_name ?? null))
                                                {{ $leaderboard_classroom_name }} — average % across all tests in this
                                                classroom (per student).
                                            @elseif (($leaderboard_scope ?? '') === 'batch' && ($leaderboard_batch ?? null))
                                                {{ $leaderboard_batch->name }} — average % across all tests in this
                                                batch (per student).
                                            @elseif (($leaderboard_scope ?? '') === 'exam' && ($leaderboard_exam ?? null))
                                                {{ $leaderboard_exam->exam_name }}
                                                @if ($leaderboard_exam->exam_date)
                                                    · {{ $leaderboard_exam->exam_date->format('d M Y') }}
                                                @endif
                                                @php $hdrMax = (float) ($leaderboard_exam->max_marks ?? 0); @endphp
                                                @if ($hdrMax > 0)
                                                    · Max {{ $leaderboard_exam->max_marks }} pts
                                                @endif
                                            @else
                                                Chart
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p id="leaderboard-chart-empty" class="text-body-secondary mb-0 d-none"></p>
                                    <div id="leaderboardStudentPctChart" class="d-none"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="leaderboardBarDetailModal" tabindex="-1" aria-labelledby="leaderboardBarDetailTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="leaderboardBarDetailTitle">Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="leaderboardBarDetailBody"></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            var batchesUrl = @json(url('user/teacher/batches-by-classroom'));
            var examsUrl = @json(url('user/teacher/exams-by-batch'));
            var teacherLbView = @json($lbView);

            function escapeHtml(text) {
                return String(text || '').replace(/[&<>"']/g, function(m) {
                    return ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    })[m];
                });
            }

            function examLabel(ex) {
                var n = escapeHtml(ex.exam_name || '');
                if (ex.exam_date) {
                    var d = String(ex.exam_date);
                    if (d.length >= 10) {
                        d = d.slice(0, 10);
                    }
                    n += ' (' + escapeHtml(d) + ')';
                }
                return n;
            }

            function normalizeBatchSelectValue(selectedId) {
                if (selectedId === 0 || selectedId === '0') {
                    return '0';
                }
                return selectedId ? String(selectedId) : '';
            }

            function loadBatches($batch, classroomId, selectedId, done, includeAllBatchesOption) {
                selectedId = normalizeBatchSelectValue(selectedId);
                if (!classroomId) {
                    $batch.html('<option value="">Choose classroom first</option>').prop('disabled', true);
                    if (typeof done === 'function') done();
                    return;
                }
                $batch.html('<option value="">Loading...</option>').prop('disabled', true);
                $.get(batchesUrl, {
                    classroom_id: classroomId
                }, function(res) {
                    if (res.status == 1 && res.data && res.data.length) {
                        $batch.html('<option value="">Choose batch</option>');
                        if (includeAllBatchesOption) {
                            var selAll = selectedId === '0' ? ' selected' : '';
                            $batch.append('<option value="0"' + selAll + '>All batches in this class</option>');
                        }
                        $.each(res.data, function(_, b) {
                            var sel = String(b.id) === String(selectedId) ? ' selected' : '';
                            $batch.append('<option value="' + b.id + '"' + sel + '>' + escapeHtml(b.name) +
                                '</option>');
                        });
                        $batch.prop('disabled', false);
                    } else {
                        $batch.html('<option value="">No batches found</option>').prop('disabled', true);
                    }
                    if (typeof done === 'function') done();
                }, 'json').fail(function() {
                    $batch.html('<option value="">Could not load batches</option>').prop('disabled', true);
                    if (typeof done === 'function') done();
                });
            }

            function loadExams($exam, batchId, selectedId, done) {
                selectedId = selectedId || '';
                if (!batchId) {
                    $exam.html('<option value="">Choose batch first</option>').prop('disabled', true);
                    if (typeof done === 'function') done();
                    return;
                }
                $exam.html('<option value="">Loading...</option>').prop('disabled', true);
                $.get(examsUrl, {
                    batch_id: batchId
                }, function(res) {
                    if (res.status == 1 && res.data && res.data.length) {
                        $exam.html('<option value="">Choose test</option>');
                        $.each(res.data, function(_, ex) {
                            var sel = String(ex.id) === String(selectedId) ? ' selected' : '';
                            $exam.append('<option value="' + ex.id + '"' + sel + '>' + examLabel(ex) +
                                '</option>');
                        });
                        $exam.prop('disabled', false);
                    } else {
                        $exam.html('<option value="">No tests found</option>').prop('disabled', true);
                    }
                    if (typeof done === 'function') done();
                }, 'json').fail(function() {
                    $exam.html('<option value="">Could not load tests</option>').prop('disabled', true);
                    if (typeof done === 'function') done();
                });
            }

            $(function() {
                var $c = $('#lb-classroom');
                var $b = $('#lb-batch');
                var $e = $('#lb-exam');
                var hasExam = $e.length > 0;
                var initialClassroom = $c.val();
                var initialBatch = $b.val();
                var initialExam = hasExam ? $e.val() : '';
                var batchAll = teacherLbView === 'batch';

                function updateTestSearchBtn() {
                    if (!hasExam) {
                        return;
                    }
                    var $btn = $('#lb-search-btn');
                    if (!$btn.length) {
                        return;
                    }
                    var ok = !!($c.val() && $b.val() && $e.val());
                    $btn.prop('disabled', !ok);
                }

                if (!initialClassroom) {
                    $b.html('<option value="">Choose classroom first</option>').prop('disabled', true);
                    if (hasExam) {
                        $e.html('<option value="">Choose batch first</option>').prop('disabled', true);
                    }
                } else if (!normalizeBatchSelectValue(initialBatch)) {
                    loadBatches($b, initialClassroom, '', function() {
                        if (hasExam) {
                            $e.html('<option value="">Choose batch first</option>').prop('disabled', true);
                            updateTestSearchBtn();
                        }
                    }, batchAll);
                } else if (hasExam && initialBatch && initialBatch !== '0' && !initialExam) {
                    loadExams($e, initialBatch, '', function() {
                        updateTestSearchBtn();
                    });
                }

                if (hasExam) {
                    $e.on('change', updateTestSearchBtn);
                }

                $c.on('change', function() {
                    var cid = $(this).val();
                    if (hasExam) {
                        $e.html('<option value="">Choose batch first</option>').prop('disabled', true);
                    }
                    loadBatches($b, cid, '', function() {
                        if (hasExam) {
                            updateTestSearchBtn();
                        }
                    }, batchAll);
                });

                $b.on('change', function() {
                    if (!hasExam) {
                        return;
                    }
                    var bid = $(this).val();
                    loadExams($e, bid, '', function() {
                        updateTestSearchBtn();
                    });
                });

                $('#leaderboard-filter-form').on('submit', function(ev) {
                    var cid = $c.val();
                    if (!cid) {
                        ev.preventDefault();
                        return false;
                    }
                    if (hasExam) {
                        if (!$b.val() || !$e.val()) {
                            ev.preventDefault();
                            return false;
                        }
                    }
                    $c.prop('disabled', false);
                    $b.prop('disabled', false);
                    if (hasExam) {
                        $e.prop('disabled', false);
                    }
                });

                updateTestSearchBtn();
            });
        })();
    </script>
    <script>
        (function() {
            var metaStudent = {
                series: @json($leaderboard_chart),
                searched: @json($leaderboard_chart !== null),
                rowCount: @json($leaderboard_row_count ?? null),
                scope: @json($leaderboard_scope ?? null),
                viewTab: @json($lbView),
                maxMarks: @json(($leaderboard_scope ?? '') === 'exam' && ($leaderboard_exam ?? null) ? (float) ($leaderboard_exam->max_marks ?? 0) : null),
                examName: @json(($leaderboard_scope ?? '') === 'exam' && ($leaderboard_exam ?? null) ? ($leaderboard_exam->exam_name ?? '') : null),
                examDateLabel: @json(
                    ($leaderboard_scope ?? '') === 'exam' && ($leaderboard_exam ?? null) && !empty($leaderboard_exam->exam_date)
                        ? $leaderboard_exam->exam_date->format('d M Y')
                        : null),
                batchName: @json(($leaderboard_scope ?? '') === 'batch' && ($leaderboard_batch ?? null) ? ($leaderboard_batch->name ?? '') : null),
                classroomName: @json(($leaderboard_scope ?? '') === 'classroom' ? ($leaderboard_classroom_name ?? '') : null),
                showAggregateMarksTooltip: @json($showAggregateMarksTooltip),
            };

            function escapeHtmlLb(text) {
                return String(text || '').replace(/[&<>"']/g, function(m) {
                    return ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    })[m];
                });
            }

            /** Same bar thickness as the one-student chart for every bar (see plotOptions.bar below). */
            var LB_BAR_COLUMN_WIDTH = '8%';
            var LB_BAR_MAX_COLUMN_WIDTH = 18;

            /**
             * ApexCharts v4 often omits dataPointIndex on chart.events.click for column bars.
             * Bars use SVG nodes with a `j` attribute (data point index).
             */
            function leaderboardBarDataIndexFromEvent(chartEl, ev) {
                var node = ev.target;
                while (node && node !== chartEl) {
                    if (node.getAttribute) {
                        var j = node.getAttribute('j');
                        if (j !== null && j !== '') {
                            var parsed = parseInt(j, 10);
                            if (!isNaN(parsed) && parsed >= 0) {
                                return parsed;
                            }
                        }
                    }
                    node = node.parentElement;
                }
                return -1;
            }

            function bindLeaderboardBarClickCapture(chartEl, meta) {
                if (!chartEl) {
                    return;
                }
                if (chartEl._lbBarClickHandler) {
                    chartEl.removeEventListener('click', chartEl._lbBarClickHandler, true);
                }
                chartEl._lbBarClickHandler = function(ev) {
                    if (!ev.target || !ev.target.closest) {
                        return;
                    }
                    if (!chartEl.contains(ev.target)) {
                        return;
                    }
                    if (!ev.target.closest('.apexcharts-inner')) {
                        return;
                    }
                    var idx = leaderboardBarDataIndexFromEvent(chartEl, ev);
                    if (idx < 0 || idx >= meta.series.length) {
                        return;
                    }
                    showLeaderboardBarDetailDebounced(meta, idx);
                };
                chartEl.addEventListener('click', chartEl._lbBarClickHandler, true);
            }


            var _lbBarDetailLast = {
                t: 0,
                idx: -1
            };

            function showLeaderboardBarDetailDebounced(meta, idx) {
                var now = Date.now();
                if (_lbBarDetailLast.idx === idx && now - _lbBarDetailLast.t < 400) {
                    return;
                }
                _lbBarDetailLast = {
                    t: now,
                    idx: idx
                };
                showLeaderboardBarDetail(meta, idx);
            }

            function showLeaderboardBarDetail(meta, idx) {
                var row = meta.series[idx];
                if (!row) {
                    return;
                }
                var titleEl = document.getElementById('leaderboardBarDetailTitle');
                var bodyEl = document.getElementById('leaderboardBarDetailBody');
                var modalEl = document.getElementById('leaderboardBarDetailModal');
                if (!titleEl || !bodyEl || !modalEl) {
                    return;
                }
                var parts = [];
                parts.push('<p class="mb-2"><strong>Student</strong><br>' + escapeHtmlLb(row.name) + '</p>');
                if (meta.scope === 'exam') {
                    titleEl.textContent = 'Test score';
                    var testLine = escapeHtmlLb(meta.examName || 'This test');
                    if (meta.examDateLabel) {
                        testLine += ' · ' + escapeHtmlLb(meta.examDateLabel);
                    }
                    parts.push('<p class="mb-2"><strong>Test</strong><br>' + testLine + '</p>');
                    var mm = meta.maxMarks != null ? Number(meta.maxMarks) : 0;
                    var hasMarks = row.marks !== null && row.marks !== undefined && row.marks !== '' &&
                        !isNaN(Number(row.marks));
                    var mk = hasMarks ? Number(row.marks) : NaN;
                    if (isNaN(mk) && mm > 0 && row.percentage != null && !isNaN(Number(row.percentage))) {
                        mk = Math.round(Number(row.percentage) / 100 * mm * 100) / 100;
                    }
                    if (mm > 0 && !isNaN(mk)) {
                        parts.push('<p class="mb-0"><strong>Mark</strong><br>' + escapeHtmlLb(String(mk)) + ' / ' +
                            escapeHtmlLb(String(mm)) + ' pts</p>');
                    } else {
                        parts.push('<p class="mb-0"><strong>Score</strong><br>' + escapeHtmlLb(String(row.percentage)) +
                            '%</p>');
                    }
                } else if (meta.scope === 'batch') {
                    titleEl.textContent = 'Average';
                    var bn = meta.batchName ? escapeHtmlLb(meta.batchName) : 'this batch';
                    parts.push('<p class="mb-2 text-body-secondary small">Average across all tests in <strong>' + bn +
                        '</strong>.</p>');
                    parts.push('<p class="mb-0"><strong>Average</strong><br>' + escapeHtmlLb(String(row.percentage)) +
                        '%</p>');
                    if (row.total_marks != null && row.marks_obtained != null && !isNaN(Number(row.total_marks))) {
                        parts.push('<p class="mb-0 mt-2 small"><strong>Total marks (max)</strong><br>' +
                            escapeHtmlLb(String(row.total_marks)) + '</p>');
                        parts.push('<p class="mb-0 small"><strong>Obtained marks</strong><br>' +
                            escapeHtmlLb(String(row.marks_obtained)) + '</p>');
                    }
                } else if (meta.scope === 'classroom') {
                    titleEl.textContent = 'Average';
                    var cn = meta.classroomName ? escapeHtmlLb(meta.classroomName) : 'this classroom';
                    parts.push('<p class="mb-2 text-body-secondary small">Average across all tests in <strong>' + cn +
                        '</strong>.</p>');
                    parts.push('<p class="mb-0"><strong>Average</strong><br>' + escapeHtmlLb(String(row.percentage)) +
                        '%</p>');
                    if (row.total_marks != null && row.marks_obtained != null && !isNaN(Number(row.total_marks))) {
                        parts.push('<p class="mb-0 mt-2 small"><strong>Total marks (max)</strong><br>' +
                            escapeHtmlLb(String(row.total_marks)) + '</p>');
                        parts.push('<p class="mb-0 small"><strong>Obtained marks</strong><br>' +
                            escapeHtmlLb(String(row.marks_obtained)) + '</p>');
                    }
                } else {
                    titleEl.textContent = 'Details';
                    parts.push('<p class="mb-0"><strong>Score</strong><br>' + escapeHtmlLb(String(row.percentage)) +
                        '%</p>');
                }
                bodyEl.innerHTML = parts.join('');
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            }

            function initStudentChart() {
                var el = document.getElementById('leaderboardStudentPctChart');
                var emptyEl = document.getElementById('leaderboard-chart-empty');
                if (!el || !emptyEl) {
                    return;
                }

                var meta = metaStudent;
                var emptyMsg = '';
                var showChart = false;

                if (meta.series === null || meta.series === undefined) {
                    emptyMsg = '';
                } else if (meta.searched) {
                    if (Array.isArray(meta.series) && meta.series.length > 0) {
                        showChart = true;
                    } else if (meta.rowCount === 0) {
                        if (meta.scope === 'classroom') {
                            emptyMsg = 'No numeric marks in this classroom yet (tests need max marks set).';
                        } else if (meta.scope === 'batch') {
                            emptyMsg = 'No numeric marks in this batch yet (tests need max marks set).';
                        } else if (meta.scope === 'exam') {
                            emptyMsg = 'No numeric marks recorded for this test yet.';
                        } else {
                            emptyMsg = 'No data to plot for this search.';
                        }
                    } else if (meta.maxMarks !== null && meta.maxMarks <= 0) {
                        emptyMsg =
                            'Set a positive max marks on the test to compute percentages for the chart.';
                    } else {
                        emptyMsg = 'Nothing to plot.';
                    }
                } else if (!emptyMsg) {
                    emptyMsg = '';
                }

                if (!showChart) {
                    emptyEl.textContent = emptyMsg;
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
                var categories = meta.series.map(function(p) {
                    return p.name;
                });
                var data = meta.series.map(function(p) {
                    return p.percentage;
                });
                var rotate = categories.length > 5 ? -50 : categories.length > 3 ? -35 : 0;
                var barBgColors = categories.map(function() {
                    return borderColor;
                });
                var singlePoint = categories.length === 1;
                var chartWidth = singlePoint ? '36%' : '100%';
                var barPlotOptions = {
                    borderRadius: 6,
                    borderRadiusApplication: 'end',
                    columnWidth: LB_BAR_COLUMN_WIDTH,
                    maxColumnWidth: LB_BAR_MAX_COLUMN_WIDTH,
                    horizontal: false,
                    colors: {
                        backgroundBarColors: barBgColors,
                        backgroundBarRadius: 6
                    }
                };

                var seriesLabel = meta.scope === 'exam' ? 'Score % (this test)' :
                    'Avg. % (all tests in scope)';

                var chartTooltip = {
                    y: {
                        formatter: function(v) {
                            return v + '%';
                        }
                    }
                };
                if (meta.showAggregateMarksTooltip || meta.scope === 'exam') {
                    chartTooltip.custom = function(arg) {
                        var idx = arg.dataPointIndex;
                        if (idx == null || idx < 0) {
                            return '';
                        }
                        var row = meta.series[idx];
                        var pctVal = (arg.series && arg.series[0] && arg.series[0][idx] !== undefined) ?
                            arg.series[0][idx] :
                            (row ? row.percentage : '');
                        var nm = row && row.name ? row.name :
                            (arg.w && arg.w.globals && arg.w.globals.labels && arg.w.globals.labels[idx] != null ?
                                arg.w.globals.labels[idx] :
                                '');
                        var html = '<div class="px-3 py-2">';
                        html += '<div style="font-weight:600;margin-bottom:4px">' + escapeHtmlLb(String(nm)) + '</div>';

                        if (meta.scope === 'exam') {
                            html += '<div>Score: <strong>' + escapeHtmlLb(String(pctVal)) + '%</strong></div>';
                            if (meta.examName) {
                                html += '<div class="text-muted" style="font-size:11px;margin-top:6px;max-width:260px;line-height:1.4">' +
                                    escapeHtmlLb(meta.examName) + '</div>';
                            }
                            var mm = meta.maxMarks != null ? Number(meta.maxMarks) : 0;
                            var obtained = row && row.marks;
                            if ((obtained === null || obtained === undefined || obtained === '') && mm > 0 && row &&
                                row.percentage != null && !isNaN(Number(row.percentage))) {
                                obtained = Math.round(Number(row.percentage) / 100 * mm * 100) / 100;
                            }
                            if (mm > 0 && obtained !== null && obtained !== undefined && obtained !== '' &&
                                !isNaN(Number(obtained))) {
                                html += '<div style="margin-top:8px;font-size:13px">Total marks (max): <strong>' +
                                    escapeHtmlLb(String(mm)) + '</strong></div>';
                                html += '<div style="font-size:13px">Obtained marks: <strong>' +
                                    escapeHtmlLb(String(obtained)) + '</strong></div>';
                            }
                            html += '</div>';
                            return html;
                        }

                        html += '<div>Average: <strong>' + escapeHtmlLb(String(pctVal)) + '%</strong></div>';
                        var scopeNote = meta.scope === 'batch' ?
                            'Across all tests in this batch.' :
                            'Across all tests and batches in this class.';
                        if (row && row.total_marks != null && row.marks_obtained != null &&
                            !isNaN(Number(row.total_marks)) && !isNaN(Number(row.marks_obtained))) {
                            html += '<div class="text-muted" style="font-size:11px;margin-top:8px;max-width:260px;line-height:1.4">' +
                                escapeHtmlLb(scopeNote) + '</div>';
                            html += '<div style="margin-top:8px;font-size:13px">Total marks (max): <strong>' +
                                escapeHtmlLb(String(row.total_marks)) + '</strong></div>';
                            html += '<div style="font-size:13px">Obtained marks: <strong>' +
                                escapeHtmlLb(String(row.marks_obtained)) + '</strong></div>';
                        }
                        html += '</div>';
                        return html;
                    };
                }

                var options = {
                    series: [{
                        name: seriesLabel,
                        data: data
                    }],
                    chart: {
                        type: 'bar',
                        height: Math.max(340, 140 + categories.length * 26),
                        width: chartWidth,
                        toolbar: {
                            show: false
                        },
                        parentHeightOffset: 0,
                        events: {
                            dataPointSelection: function(event, chartContext, cfg) {
                                var idx = -1;
                                if (cfg && typeof cfg.dataPointIndex === 'number' && cfg.dataPointIndex >= 0) {
                                    idx = cfg.dataPointIndex;
                                } else if (cfg && cfg.selectedDataPoints && cfg.selectedDataPoints.length) {
                                    var pair = cfg.selectedDataPoints[0];
                                    if (Array.isArray(pair) && typeof pair[1] === 'number') {
                                        idx = pair[1];
                                    }
                                }
                                if (idx < 0 || idx >= meta.series.length) {
                                    return;
                                }
                                showLeaderboardBarDetailDebounced(meta, idx);
                            }
                        }
                    },
                    plotOptions: {
                        bar: barPlotOptions
                    },
                    stroke: {
                        show: true,
                        width: 0,
                        colors: ['transparent']
                    },
                    dataLabels: {
                        enabled: false
                    },
                    colors: [config.colors.primary],
                    xaxis: {
                        categories: categories,
                        labels: {
                            rotate: rotate,
                            rotateAlways: rotate !== 0,
                            maxHeight: 120,
                            trim: true,
                            hideOverlappingLabels: true,
                            style: {
                                colors: labelColor,
                                fontSize: categories.length > 12 ? '10px' : '12px',
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
                        tickAmount: 5,
                        labels: {
                            formatter: function(v) {
                                return v + '%';
                            },
                            style: {
                                colors: labelColor,
                                fontSize: '13px',
                                fontFamily: fontFamily
                            }
                        }
                    },
                    grid: {
                        strokeDashArray: 8,
                        borderColor: borderColor,
                        padding: {
                            bottom: rotate !== 0 ? 8 : 0
                        }
                    },
                    legend: {
                        show: true,
                        position: 'bottom',
                        fontFamily: fontFamily
                    },
                    tooltip: chartTooltip,
                    responsive: [{
                        breakpoint: 480,
                        options: singlePoint ? {
                            chart: {
                                height: Math.max(300, 120 + categories.length * 22),
                                width: '36%'
                            },
                            plotOptions: {
                                bar: Object.assign({}, barPlotOptions)
                            },
                            xaxis: {
                                labels: {
                                    rotate: -50,
                                    rotateAlways: true,
                                    style: {
                                        fontSize: '9px'
                                    }
                                }
                            }
                        } : {
                            chart: {
                                height: Math.max(300, 120 + categories.length * 22),
                                width: '100%'
                            },
                            plotOptions: {
                                bar: Object.assign({}, barPlotOptions)
                            },
                            xaxis: {
                                labels: {
                                    rotate: -50,
                                    rotateAlways: true,
                                    style: {
                                        fontSize: '9px'
                                    }
                                }
                            }
                        }
                    }]
                };

                if (singlePoint) {
                    el.style.display = 'flex';
                    el.style.justifyContent = 'center';
                    el.style.maxWidth = '';
                    el.style.marginLeft = '';
                    el.style.marginRight = '';
                } else {
                    el.style.display = '';
                    el.style.justifyContent = '';
                    el.style.maxWidth = '';
                    el.style.marginLeft = '';
                    el.style.marginRight = '';
                }

                var chart = new ApexCharts(el, options);
                var rendered = chart.render();
                if (rendered && typeof rendered.then === 'function') {
                    rendered.then(function() {
                        bindLeaderboardBarClickCapture(el, meta);
                    });
                } else {
                    setTimeout(function() {
                        bindLeaderboardBarClickCapture(el, meta);
                    }, 0);
                }
            }

            if (typeof jQuery !== 'undefined') {
                jQuery(initStudentChart);
            } else {
                document.addEventListener('DOMContentLoaded', initStudentChart);
            }
        })();
    </script>
@endsection
