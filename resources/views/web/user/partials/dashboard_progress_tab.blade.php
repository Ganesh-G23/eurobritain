@php
    $progressRows = $dashboard_progress_by_classroom ?? [];
    $suffix = $progress_chart_suffix ?? 'portal';
@endphp
@once
    @push('page_styles')
        <style>
            /* Light: plot area matches card. Dark: dedicated surface behind the chart. */
            .progress-apex-host {
                background: transparent;
            }

            [data-bs-theme='dark'] .progress-apex-host {
                background-color: rgb(var(--bs-tertiary-bg-rgb));
                padding: 0.75rem;
            }

            /* Match .text-body subtitle: Apex sometimes ignores label style.colors in light mode. */
            .progress-apex-host .apexcharts-xaxis text {
                fill: var(--bs-body-color) !important;
                text-anchor: middle !important;
            }

            .progress-apex-host .apexcharts-yaxis text,
            .progress-apex-host .apexcharts-legend-text {
                fill: var(--bs-body-color) !important;
            }

            .progress-apex-host .apexcharts-toolbar svg path,
            .progress-apex-host .apexcharts-toolbar svg line,
            .progress-apex-host .apexcharts-toolbar svg circle,
            .progress-apex-host .apexcharts-toolbar svg polyline {
                stroke: var(--bs-body-color) !important;
            }
        </style>
    @endpush
@endonce
<div class="row g-4 mb-3">
    <div class="col-12">
        <p class="mb-0 text-body-secondary small">
            Choose a classroom to load exam history for that class (only batches this account is enrolled in). Summary
            totals follow the same rules as the portal mark overview. Filter by date to study a window in time.
        </p>
    </div>
    @if (count($progressRows) === 0)
        <div class="col-12">
            <div class="alert alert-secondary mb-0" role="status">
                No classroom batches with assessments are linked yet, so there is nothing to chart here.
            </div>
        </div>
    @else
        <div class="col-md-6 col-xl-4">
            <label class="form-label small mb-1" for="progress-classroom-{{ $suffix }}">Classroom</label>
            <select class="form-select" id="progress-classroom-{{ $suffix }}">
                @foreach ($progressRows as $idx => $cr)
                    <option value="{{ (int) $idx }}">{{ $cr['classroom_name'] ?? 'Classroom' }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <label class="form-label small mb-1" for="progress-from-{{ $suffix }}">From</label>
            <input type="date" class="form-control" id="progress-from-{{ $suffix }}" />
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <label class="form-label small mb-1" for="progress-to-{{ $suffix }}">To</label>
            <input type="date" class="form-control" id="progress-to-{{ $suffix }}" />
        </div>
        <div class="col-12 col-md-6 col-xl-4 d-flex align-items-end gap-2">
            <button type="button" class="btn btn-label-secondary btn-sm" id="progress-reset-{{ $suffix }}">Reset dates</button>
        </div>

        <div class="col-6 col-md-3 col-xl-3">
            <div class="card h-100 border shadow-none">
                <div class="card-body py-3">
                    <p class="mb-1 text-body-secondary small">Overall % (included items)</p>
                    <h4 class="mb-0 text-heading" id="progress-overall-pct-{{ $suffix }}">—</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-3">
            <div class="card h-100 border shadow-none">
                <div class="card-body py-3">
                    <p class="mb-1 text-body-secondary small">Marks scored</p>
                    <h4 class="mb-0 text-heading" id="progress-marks-got-{{ $suffix }}">—</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-3">
            <div class="card h-100 border shadow-none">
                <div class="card-body py-3">
                    <p class="mb-1 text-body-secondary small">Total possible</p>
                    <h4 class="mb-0 text-heading" id="progress-marks-max-{{ $suffix }}">—</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-3">
            <div class="card h-100 border shadow-none">
                <div class="card-body py-3">
                    <p class="mb-1 text-body-secondary small">Included assessments</p>
                    <h4 class="mb-0 text-heading" id="progress-included-n-{{ $suffix }}">—</h4>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border shadow-none">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 text-heading">Performance trend</h6>
                    <p id="progress-chart-caption-{{ $suffix }}" class="card-subtitle mb-0 small text-body">Line: % score · Columns: marks obtained (same period)</p>
                </div>
                <div class="card-body pt-2 pb-3">
                    <div class="progress-apex-host rounded-3" id="progress-apex-host-{{ $suffix }}">
                        <div id="progress-apex-chart-{{ $suffix }}" class="w-100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border shadow-none">
                <div class="card-header border-bottom">
                    <h6 class="mb-0">Exam history</h6>
                    <p class="card-subtitle mb-0 small text-body-secondary">Assessments in this classroom for the enrolled
                        batches only</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="progress-exam-table-{{ $suffix }}">
                            <thead>
                                <tr>
                                    <th>Assessment</th>
                                    <th>Date</th>
                                    <th>Batch</th>
                                    <th class="text-end">Score</th>
                                    <th class="text-end">%</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@if (count($progressRows) > 0)
    @push('scripts')
        <script>
            (function() {
                const suffix = @json($suffix);
                const payload = @json($progressRows);
                let chart = null;

                function fmtNum(n) {
                    if (n === null || n === undefined || Number.isNaN(Number(n))) return '—';
                    const s = Number(n).toFixed(2).replace(/\.?0+$/, '');
                    return s;
                }

                function examInRange(ex, fromVal, toVal) {
                    if (!fromVal && !toVal) return true;
                    const d = ex.exam_date;
                    if (!d) return false;
                    if (fromVal && d < fromVal) return false;
                    if (toVal && d > toVal) return false;
                    return true;
                }

                function statusLabel(st) {
                    if (st === 'graded') return 'Graded';
                    if (st === 'absent_zero') return 'Absent (0)';
                    if (st === 'absent_excluded') return 'Absent (excluded)';
                    if (st === 'pending') return 'Pending';
                    return st || '—';
                }

                function scoreCell(ex) {
                    if (ex.status === 'pending' || ex.status === 'absent_excluded') {
                        return '—';
                    }
                    if (ex.marks_obtained === null || ex.marks_obtained === undefined) return '—';
                    return fmtNum(ex.marks_obtained) + ' / ' + fmtNum(ex.max_marks);
                }

                function pctCell(ex) {
                    if (ex.pct === null || ex.pct === undefined) return '—';
                    return ex.pct + '%';
                }

                function currentClassroomBlock() {
                    const sel = document.getElementById('progress-classroom-' + suffix);
                    const idx = sel ? parseInt(sel.value, 10) : 0;
                    return payload[idx] || null;
                }

                function filteredExams(block) {
                    const fromEl = document.getElementById('progress-from-' + suffix);
                    const toEl = document.getElementById('progress-to-' + suffix);
                    const fromVal = fromEl && fromEl.value ? fromEl.value : '';
                    const toVal = toEl && toEl.value ? toEl.value : '';
                    const exams = (block && block.exams) ? block.exams : [];
                    return exams.filter(function(ex) {
                        return examInRange(ex, fromVal, toVal);
                    });
                }

                function includedTotalsForFiltered(block, filtered) {
                    let sumGot = 0;
                    let sumMax = 0;
                    let n = 0;
                    filtered.forEach(function(ex) {
                        if (ex.status === 'graded') {
                            sumGot += Number(ex.marks_obtained || 0);
                            sumMax += Number(ex.max_marks || 0);
                            n++;
                        } else if (ex.status === 'absent_zero') {
                            sumMax += Number(ex.max_marks || 0);
                            n++;
                        }
                    });
                    const pct = sumMax > 0 ? Math.round((100 * sumGot / sumMax) * 10) / 10 : null;
                    return {
                        sumGot,
                        sumMax,
                        n,
                        pct
                    };
                }

                function renderSummary(block, filtered) {
                    const t = includedTotalsForFiltered(block, filtered);
                    const overallEl = document.getElementById('progress-overall-pct-' + suffix);
                    const gotEl = document.getElementById('progress-marks-got-' + suffix);
                    const maxEl = document.getElementById('progress-marks-max-' + suffix);
                    const nEl = document.getElementById('progress-included-n-' + suffix);
                    if (overallEl) overallEl.textContent = t.pct !== null ? t.pct + '%' : '—';
                    if (gotEl) gotEl.textContent = t.sumMax > 0 ? fmtNum(t.sumGot) : '—';
                    if (maxEl) maxEl.textContent = t.sumMax > 0 ? fmtNum(t.sumMax) : '—';
                    if (nEl) nEl.textContent = t.n > 0 ? String(t.n) : '0';
                }

                function renderTable(filtered) {
                    const tbody = document.querySelector('#progress-exam-table-' + suffix + ' tbody');
                    if (!tbody) return;
                    tbody.replaceChildren();
                    filtered.forEach(function(ex) {
                        const tr = document.createElement('tr');
                        const tdName = document.createElement('td');
                        tdName.className = 'fw-medium';
                        tdName.textContent = ex.exam_name || '—';
                        const tdDate = document.createElement('td');
                        tdDate.className = 'small text-nowrap';
                        tdDate.textContent = ex.exam_date || '—';
                        const tdBatch = document.createElement('td');
                        tdBatch.className = 'small';
                        tdBatch.textContent = ex.batch_name || '';
                        const tdScore = document.createElement('td');
                        tdScore.className = 'text-end text-nowrap';
                        tdScore.textContent = scoreCell(ex);
                        const tdPct = document.createElement('td');
                        tdPct.className = 'text-end';
                        tdPct.textContent = pctCell(ex);
                        const tdSt = document.createElement('td');
                        tdSt.className = 'small';
                        tdSt.textContent = statusLabel(ex.status);
                        tr.appendChild(tdName);
                        tr.appendChild(tdDate);
                        tr.appendChild(tdBatch);
                        tr.appendChild(tdScore);
                        tr.appendChild(tdPct);
                        tr.appendChild(tdSt);
                        tbody.appendChild(tr);
                    });
                }

                function chartableRows(filtered) {
                    return filtered.filter(function(ex) {
                        return ex.chart_pct !== null && ex.chart_pct !== undefined;
                    });
                }

                function buildProgressXCategory(examName, examDate, maxNameChars) {
                    const d = examDate || '—';
                    let name = String(examName || 'Exam').trim();
                    if (name.length > maxNameChars) {
                        name = name.slice(0, Math.max(1, maxNameChars - 1)) + '\u2026';
                    }
                    return name + '\n' + d;
                }

                function getProgressChartTheme() {
                    const r = document.documentElement;
                    const s = getComputedStyle(r);
                    const pick = function(name, fallback) {
                        const v = s.getPropertyValue(name).trim();
                        return v || fallback;
                    };
                    const rgb = function(name, fb) {
                        const v = pick(name, '').replace(/\s+/g, '');
                        return v ? 'rgb(' + v + ')' : fb;
                    };
                    const isDark = (r.getAttribute('data-bs-theme') || '') === 'dark';
                    const cap = document.getElementById('progress-chart-caption-' + suffix);
                    let chartText = '';
                    if (cap) {
                        chartText = (getComputedStyle(cap).color || '').trim();
                    }
                    if (!chartText) {
                        chartText = pick('--bs-body-color', '#5d596c');
                    }
                    return {
                        chartText: chartText,
                        border: pick('--bs-border-color', '#dfe3e8'),
                        primary: rgb('--bs-primary-rgb', '#7367f0'),
                        line: rgb('--bs-info-rgb', '#00bac9'),
                        tooltipTheme: isDark ? 'dark' : 'light'
                    };
                }

                function renderChart(filtered) {
                    const el = document.getElementById('progress-apex-chart-' + suffix);
                    if (!el || typeof ApexCharts === 'undefined') return;
                    const rows = chartableRows(filtered);
                    const n = rows.length;
                    /* Approx. width per category slot so labels wrap instead of colliding (x-axis only). */
                    const labelMaxWidthPx = n > 0 ? Math.max(48, Math.min(120, Math.floor(560 / n) - 10)) : 120;
                    const maxNameChars = n > 0 ? Math.max(6, Math.min(28, Math.floor(labelMaxWidthPx / 6.5))) : 22;
                    const categories = rows.map(function(ex) {
                        return buildProgressXCategory(ex.exam_name, ex.exam_date, maxNameChars);
                    });
                    const pctData = rows.map(function(ex) {
                        return Number(ex.chart_pct);
                    });
                    const marksData = rows.map(function(ex) {
                        return ex.marks_obtained !== null && ex.marks_obtained !== undefined ? Number(ex
                            .marks_obtained) : 0;
                    });

                    const th = getProgressChartTheme();

                    const options = {
                        chart: {
                            height: 320,
                            type: 'line',
                            background: 'transparent',
                            foreColor: th.chartText,
                            toolbar: {
                                show: true,
                                tools: {
                                    download: true,
                                    selection: true,
                                    zoom: true,
                                    zoomin: true,
                                    zoomout: true,
                                    pan: true,
                                    reset: true
                                }
                            },
                            zoom: {
                                enabled: true
                            },
                            fontFamily: 'inherit'
                        },
                        stroke: {
                            width: [0, 4],
                            curve: 'smooth'
                        },
                        series: [{
                                name: 'Marks obtained',
                                type: 'column',
                                data: marksData
                            },
                            {
                                name: '% score',
                                type: 'line',
                                data: pctData
                            }
                        ],
                        dataLabels: {
                            enabled: false
                        },
                        xaxis: {
                            categories: categories,
                            labels: {
                                rotate: 0,
                                rotateAlways: false,
                                hideOverlappingLabels: false,
                                trim: false,
                                maxWidth: labelMaxWidthPx,
                                style: {
                                    colors: th.chartText,
                                    fontSize: n > 6 ? '11px' : '12px',
                                    fontWeight: 500
                                }
                            },
                            axisBorder: {
                                show: true,
                                color: th.border
                            },
                            axisTicks: {
                                show: true,
                                color: th.border
                            }
                        },
                        yaxis: [{
                                seriesName: 'Marks obtained',
                                title: {
                                    text: 'Marks',
                                    style: {
                                        color: th.chartText,
                                        fontSize: '13px',
                                        fontWeight: 600
                                    }
                                },
                                labels: {
                                    style: {
                                        colors: th.chartText,
                                        fontSize: '12px',
                                        fontWeight: 500
                                    },
                                    formatter: function(val) {
                                        return val != null ? Math.round(val) : '';
                                    }
                                }
                            },
                            {
                                seriesName: '% score',
                                opposite: true,
                                min: 0,
                                max: 100,
                                title: {
                                    text: '% Score',
                                    style: {
                                        color: th.chartText,
                                        fontSize: '13px',
                                        fontWeight: 600
                                    }
                                },
                                labels: {
                                    style: {
                                        colors: th.chartText,
                                        fontSize: '12px',
                                        fontWeight: 500
                                    },
                                    formatter: function(val) {
                                        return val != null ? Math.round(val) + '%' : '';
                                    }
                                }
                            }
                        ],
                        colors: [th.primary, th.line],
                        legend: {
                            position: 'top',
                            horizontalAlign: 'left',
                            fontSize: '14px',
                            fontWeight: 600,
                            labels: {
                                colors: th.chartText,
                                useSeriesColors: false
                            },
                            markers: {
                                width: 12,
                                height: 12,
                                radius: 3
                            }
                        },
                        markers: {
                            size: 5,
                            strokeWidth: 2,
                            strokeColors: '#fff',
                            hover: {
                                size: 7
                            }
                        },
                        grid: {
                            borderColor: th.border,
                            strokeDashArray: 4,
                            padding: {
                                right: 14,
                                left: 10,
                                bottom: n > 7 ? 36 : n > 5 ? 32 : 28,
                                top: 12
                            }
                        },
                        plotOptions: {
                            bar: {
                                columnWidth: '58%',
                                borderRadius: 4,
                                dataLabels: {
                                    position: 'top'
                                }
                            }
                        },
                        tooltip: {
                            shared: true,
                            intersect: false,
                            theme: th.tooltipTheme,
                            style: {
                                fontSize: '13px'
                            },
                            x: {
                                show: true
                            },
                            y: {
                                formatter: function(val, opts) {
                                    if (opts && opts.seriesIndex === 0) {
                                        return val != null && val !== '' ? val + ' marks' : '';
                                    }
                                    return val != null && val !== '' ? val + '%' : '';
                                }
                            }
                        }
                    };

                    if (chart) {
                        chart.destroy();
                        chart = null;
                    }
                    if (rows.length === 0) {
                        el.innerHTML =
                            '<p class="text-body-secondary small mb-0 py-5 text-center">No graded assessments in this date range to plot.</p>';
                        return;
                    }
                    chart = new ApexCharts(el, options);
                    chart.render();
                }

                function refreshAll() {
                    const block = currentClassroomBlock();
                    const filtered = filteredExams(block);
                    renderSummary(block, filtered);
                    renderTable(filtered);
                    renderChart(filtered);
                }

                document.addEventListener('DOMContentLoaded', function() {
                    const sel = document.getElementById('progress-classroom-' + suffix);
                    const fromEl = document.getElementById('progress-from-' + suffix);
                    const toEl = document.getElementById('progress-to-' + suffix);
                    const resetBtn = document.getElementById('progress-reset-' + suffix);
                    if (sel) sel.addEventListener('change', refreshAll);
                    if (fromEl) fromEl.addEventListener('change', refreshAll);
                    if (toEl) toEl.addEventListener('change', refreshAll);
                    if (resetBtn) resetBtn.addEventListener('click', function() {
                        if (fromEl) fromEl.value = '';
                        if (toEl) toEl.value = '';
                        refreshAll();
                    });
                    refreshAll();
                });
            })();
        </script>
    @endpush
@endif
