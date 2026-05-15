<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report_title ?? 'Academic Report' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #1a1a2e; }
        h2 { font-size: 13px; margin: 18px 0 8px; color: #444; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta { font-size: 10px; color: #666; margin-bottom: 14px; }
        .meta strong { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; }
        th { background: #f0f2f5; font-weight: bold; }
        .summary-box { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; margin-bottom: 12px; }
        .summary-box span { margin-right: 16px; }
        .batch-title { font-weight: bold; margin: 10px 0 4px; font-size: 11px; }
        .text-center { text-align: center; }
        .text-muted { color: #888; }
        .logo { max-height: 36px; margin-bottom: 8px; }
    </style>
</head>
<body>
    @php
        $generatedAt = $generated_at ?? now();
        $student = $student ?? [];
        $teacher = $teacher ?? null;
        $attendance = $attendance ?? ['summary' => [], 'batches' => []];
        $tests = $tests ?? ['summary' => [], 'exams' => []];
    @endphp

    <img class="logo" src="{{ public_path('admin_theme/assets/img/logo.png') }}" alt="EliteGrade">

    <h1>{{ $report_title ?? 'Academic Report' }}</h1>
    <div class="meta">
        <div><strong>Student:</strong> {{ $student['name'] ?? '—' }}@if(!empty($student['email'])) &middot; {{ $student['email'] }}@endif</div>
        @if($teacher)
            <div><strong>Teacher:</strong> {{ $teacher['name'] }}@if(!empty($teacher['email'])) &middot; {{ $teacher['email'] }}@endif</div>
        @elseif(!empty($scope_label))
            <div><strong>Scope:</strong> {{ $scope_label }}</div>
        @endif
        <div><strong>Generated:</strong> {{ $generatedAt->format('d M Y, H:i') }}</div>
    </div>

    @if(!empty($include_attendance))
        <h2>Attendance</h2>
        @php $attSummary = $attendance['summary'] ?? []; @endphp
        <div class="summary-box">
            <span><strong>Present:</strong> {{ $attSummary['present'] ?? 0 }}</span>
            <span><strong>Absent:</strong> {{ $attSummary['absent'] ?? 0 }}</span>
            <span><strong>Late:</strong> {{ $attSummary['late'] ?? 0 }}</span>
            <span><strong>Total marked:</strong> {{ $attSummary['total'] ?? 0 }}</span>
            @if(isset($attSummary['rate_pct']))
                <span><strong>Attendance rate:</strong> {{ $attSummary['rate_pct'] }}%</span>
            @endif
        </div>

        @forelse($attendance['batches'] ?? [] as $batch)
            <div class="batch-title">{{ $batch['classroom_name'] ?? '' }} — {{ $batch['batch_name'] ?? '' }}</div>
            @if(count($batch['rows'] ?? []) === 0)
                <p class="text-muted">No attendance records.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batch['rows'] as $row)
                            <tr>
                                <td>{{ $row['date'] ?? '—' }}</td>
                                <td>{{ $row['label'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @empty
            <p class="text-muted">No attendance data available.</p>
        @endforelse
    @endif

    @if(!empty($include_tests))
        <h2>Tests &amp; Marks</h2>
        @php $testSummary = $tests['summary'] ?? []; @endphp
        <div class="summary-box">
            @if(isset($testSummary['overall_pct']))
                <span><strong>Overall percentage:</strong> {{ $testSummary['overall_pct'] }}%</span>
            @endif
            <span><strong>Exams included:</strong> {{ $testSummary['exam_count'] ?? 0 }}</span>
        </div>

        @if(count($tests['exams'] ?? []) === 0)
            <p class="text-muted">No test records available.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Test</th>
                        <th>Date</th>
                        <th>Classroom</th>
                        <th>Batch</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tests['exams'] as $exam)
                        <tr>
                            <td>{{ $exam['exam_name'] ?? '—' }}</td>
                            <td>{{ $exam['exam_date'] ?? '—' }}</td>
                            <td>{{ $exam['classroom_name'] ?? '—' }}</td>
                            <td>{{ $exam['batch_name'] ?? '—' }}</td>
                            <td>{{ $exam['display'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <p class="text-center text-muted" style="margin-top: 24px;">EliteGrade — Academic Report</p>
</body>
</html>
