@extends('admin.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Fees</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ url('admin/fees') }}" class="mb-4">
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">Teacher</label>
                                    <select name="teacher_id" class="form-select">
                                        <option value="">All teachers</option>
                                        @foreach ($teachers ?? [] as $teacher)
                                            <option value="{{ $teacher->id }}"
                                                {{ (int) ($teacher_id ?? 0) === (int) $teacher->id ? 'selected' : '' }}>
                                                {{ $teacher->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">Student</label>
                                    <select name="student_id" class="form-select">
                                        <option value="">All students</option>
                                        @foreach ($students ?? [] as $student)
                                            <option value="{{ $student->id }}"
                                                {{ (int) ($student_id ?? 0) === (int) $student->id ? 'selected' : '' }}>
                                                {{ $student->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label class="form-label">From date</label>
                                    <input type="date" name="from_date" class="form-control"
                                        value="{{ $from_date ?? '' }}">
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label class="form-label">To date</label>
                                    <input type="date" name="to_date" class="form-control" value="{{ $to_date ?? '' }}">
                                </div>
                                <div class="col-lg-2 col-md-12 d-flex gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-primary">Apply</button>
                                    <a href="{{ url('admin/fees') }}" class="btn btn-label-secondary">Reset</a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Teacher</th>
                                        <th>Student</th>
                                        <th>Classroom</th>
                                        <th>Batch</th>
                                        <th>Payment Mode</th>
                                        <th>Amount</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($fees ?? [] as $index => $fee)
                                        <tr>
                                            <td>{{ (($page ?? 1) - 1) * ($per_page ?? 50) + $index + 1 }}</td>
                                            <td>{{ optional($fee->created_at)->setTimezone(config('app.timezone'))->format('Y-m-d h:i A') }}</td>
                                            <td>{{ $fee->teacher->name ?? '—' }}</td>
                                            <td>{{ $fee->student->name ?? '—' }}</td>
                                            <td>{{ $fee->classroom->name ?? '—' }}</td>
                                            <td>{{ $fee->batch->name ?? '—' }}</td>
                                            <td>{{ $fee->payment_mode ?? '—' }}</td>
                                            <td>{{ number_format((float) $fee->amount, 2) }}</td>
                                            <td>{{ $fee->remark ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">No fee records found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @php
                            $perPageVal = max(1, (int) ($per_page ?? 50));
                            $totalRows = (int) ($num_rows ?? 0);
                            $totalPages = $totalRows > 0 ? (int) ceil($totalRows / $perPageVal) : 1;
                            $currentPage = max(1, (int) ($page ?? 1));
                            $fromRow = $totalRows > 0 ? (($currentPage - 1) * $perPageVal) + 1 : 0;
                            $toRow = min($currentPage * $perPageVal, $totalRows);
                        @endphp

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                            <p class="text-body-secondary small mb-0">
                                @if ($totalRows > 0)
                                    Showing {{ $fromRow }}–{{ $toRow }} of {{ $totalRows }} record(s)
                                @else
                                    Showing 0 record(s)
                                @endif
                            </p>

                            @include('partials.compact_pagination', [
                                'baseUrl' => url('admin/fees'),
                                'currentPage' => $currentPage,
                                'totalPages' => $totalPages,
                                'queryParams' => $query_params ?? [],
                                'perPageVal' => $perPageVal,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
