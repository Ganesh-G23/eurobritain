@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="row g-6">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Fees List</h5>
                        <a href="{{ url('user/teacher/fees') }}" class="btn btn-sm btn-primary">Add Fee</a>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ url('user/teacher/fees/list') }}" class="mb-4"
                            id="fees-list-filters">
                            <div class="row g-2 align-items-end flex-nowrap overflow-x-auto pb-1">
                                <div class="col">
                                    <label class="form-label small mb-1">Classroom</label>
                                    <select name="classroom_id" id="fees-filter-classroom"
                                        class="form-select form-select-sm">
                                        <option value="">All classrooms</option>
                                        @foreach ($classrooms ?? [] as $classroom)
                                            <option value="{{ $classroom->id }}"
                                                {{ (int) ($classroom_id ?? 0) === (int) $classroom->id ? 'selected' : '' }}>
                                                {{ $classroom->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col">
                                    <label class="form-label small mb-1">Batch</label>
                                    <select name="batch_id" id="fees-filter-batch" class="form-select form-select-sm">
                                        <option value="">All batches</option>
                                        @foreach ($all_batches ?? $batches ?? [] as $batch)
                                            <option value="{{ $batch->id }}"
                                                data-classroom-id="{{ $batch->classroom_id }}"
                                                {{ (int) ($batch_id ?? 0) === (int) $batch->id ? 'selected' : '' }}>
                                                {{ $batch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col">
                                    <label class="form-label small mb-1">Student</label>
                                    <select name="student_id" id="fees-filter-student" class="form-select form-select-sm">
                                        <option value="">All students</option>
                                        @foreach ($students ?? [] as $student)
                                            <option value="{{ $student->id }}"
                                                {{ (int) ($student_id ?? 0) === (int) $student->id ? 'selected' : '' }}>
                                                {{ $student->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col">
                                    <label class="form-label small mb-1">From date</label>
                                    <input type="date" name="from_date" class="form-control form-control-sm"
                                        value="{{ $from_date ?? '' }}">
                                </div>
                                <div class="col">
                                    <label class="form-label small mb-1">To date</label>
                                    <input type="date" name="to_date" class="form-control form-control-sm"
                                        value="{{ $to_date ?? '' }}">
                                </div>
                                <div class="col-auto flex-shrink-0 d-flex gap-2">
                                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                                    <a href="{{ url('user/teacher/fees/list') }}"
                                        class="btn btn-sm btn-label-secondary">Reset</a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Classroom</th>
                                        <th>Batch</th>
                                        <th>Amount</th>
                                        <th>Mode of payment</th>
                                        <th>Remark</th>
                                        <th>Added at</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($fees ?? [] as $fee)
                                        <tr>
                                            <td>
                                                <div>{{ $fee->student->name ?? '-' }}</div>
                                                @if (!empty($fee->student->email))
                                                    <small class="text-body-secondary">{{ $fee->student->email }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $fee->classroom->name ?? '-' }}</td>
                                            <td>{{ $fee->batch->name ?? '-' }}</td>
                                            <td>{{ number_format((float) $fee->amount, 2) }}</td>
                                            <td>{{ $fee->payment_mode }}</td>
                                            <td class="text-break" style="max-width: 220px;">{{ $fee->remark ?: '—' }}
                                            </td>
                                            <td>{{ $fee->created_at ? $fee->created_at->format('d M Y H:i') : '—' }}</td>
                                            <td>
                                                <div class="d-flex justify-content-end">
                                                    <a href="{{ url('user/teacher/fees/edit/' . $fee->id) }}"
                                                        class="btn btn-sm btn-icon btn-label-primary" title="Edit">
                                                        <i class="icon-base ti tabler-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-body-secondary py-4">No fees
                                                recorded yet.</td>
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
                                'baseUrl' => url('user/teacher/fees/list'),
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

@section('scripts')
    <script>
        (function() {
            const allBatches = @json($all_batches_for_js ?? []);
            const studentsByBatch = @json($students_by_batch ?? []);
            const studentsByClassroom = @json($students_by_classroom ?? []);
            const allStudents = @json($students_for_js ?? []);
            const classroomSelect = document.getElementById('fees-filter-classroom');
            const batchSelect = document.getElementById('fees-filter-batch');
            const studentSelect = document.getElementById('fees-filter-student');
            if (!classroomSelect || !batchSelect || !studentSelect) {
                return;
            }

            const selectedBatchId = batchSelect.value;
            const selectedStudentId = studentSelect.value;

            function lookupList(map, key) {
                if (!key) {
                    return [];
                }
                return map[key] || map[String(key)] || [];
            }

            function fillBatchOptions(classroomId, keepBatchId) {
                const current = keepBatchId || batchSelect.value;
                batchSelect.innerHTML = '<option value="">All batches</option>';
                allBatches.forEach(function(batch) {
                    if (classroomId && String(batch.classroom_id) !== String(classroomId)) {
                        return;
                    }
                    const opt = document.createElement('option');
                    opt.value = batch.id;
                    opt.textContent = batch.name;
                    opt.setAttribute('data-classroom-id', batch.classroom_id);
                    if (String(batch.id) === String(current)) {
                        opt.selected = true;
                    }
                    batchSelect.appendChild(opt);
                });
            }

            function resolveStudentList(classroomId, batchId) {
                if (batchId) {
                    return lookupList(studentsByBatch, batchId);
                }
                if (classroomId) {
                    return lookupList(studentsByClassroom, classroomId);
                }
                return allStudents;
            }

            function fillStudentOptions(classroomId, batchId, keepStudentId) {
                const current = keepStudentId !== undefined ? keepStudentId : studentSelect.value;
                const list = resolveStudentList(classroomId, batchId);
                studentSelect.innerHTML = '<option value="">All students</option>';
                list.forEach(function(student) {
                    const opt = document.createElement('option');
                    opt.value = student.id;
                    opt.textContent = student.name;
                    if (String(student.id) === String(current)) {
                        opt.selected = true;
                    }
                    studentSelect.appendChild(opt);
                });
            }

            classroomSelect.addEventListener('change', function() {
                const classroomId = classroomSelect.value;
                fillBatchOptions(classroomId, '');
                fillStudentOptions(classroomId, '', '');
            });

            batchSelect.addEventListener('change', function() {
                fillStudentOptions(classroomSelect.value, batchSelect.value, '');
            });

            fillBatchOptions(classroomSelect.value, selectedBatchId);
            fillStudentOptions(classroomSelect.value, batchSelect.value, selectedStudentId);
        })();
    </script>
@endsection
