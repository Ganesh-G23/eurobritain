@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-12 col-xl-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ ($mode ?? 'create') === 'edit' ? 'Edit Fee' : 'Add Fee' }}</h5>
                        <a href="{{ url('user/teacher/fees/list') }}" class="btn btn-sm btn-label-primary">View Fees List</a>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <form method="post" action="{{ url('user/teacher/fees/store') }}">
                            @csrf
                            <input type="hidden" name="id" value="{{ old('id', $fee?->id ?? '') }}">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Classroom <span class="text-danger">*</span></label>
                                    <select name="classroom_id" id="fee-classroom" class="form-select @error('classroom_id') is-invalid @enderror">
                                        <option value="">Select classroom</option>
                                        @foreach ($classrooms ?? [] as $classroom)
                                            <option value="{{ $classroom->id }}" {{ (string) old('classroom_id', $fee?->classroom_id ?? '') === (string) $classroom->id ? 'selected' : '' }}>
                                                {{ $classroom->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('classroom_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Batch <span class="text-danger">*</span></label>
                                    <select name="batch_id" id="fee-batch" class="form-select @error('batch_id') is-invalid @enderror">
                                        <option value="">Select batch</option>
                                        @foreach ($batches ?? [] as $batch)
                                            <option
                                                value="{{ $batch->id }}"
                                                data-classroom-id="{{ $batch->classroom_id }}"
                                                {{ (string) old('batch_id', $fee?->batch_id ?? '') === (string) $batch->id ? 'selected' : '' }}>
                                                {{ $batch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('batch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Student <span class="text-danger">*</span></label>
                                    <select name="student_id" id="fee-student" class="form-select @error('student_id') is-invalid @enderror">
                                        <option value="">Select student</option>
                                    </select>
                                    @error('student_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-body-secondary d-block mt-1">Students shown are enrolled in the selected batch.</small>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror"
                                        value="{{ old('amount', $fee?->amount !== null ? $fee->amount : '') }}" placeholder="0.00">
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Mode of payment <span class="text-danger">*</span></label>
                                    <select name="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                        <option value="">Select mode</option>
                                        @foreach ($payment_modes ?? [] as $value => $label)
                                            <option value="{{ $value }}" {{ old('payment_mode', $fee?->payment_mode ?? '') === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('payment_mode')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Remark</label>
                                    <textarea name="remark" class="form-control @error('remark') is-invalid @enderror" rows="3"
                                        placeholder="Optional note">{{ old('remark', $fee?->remark ?? '') }}</textarea>
                                    @error('remark')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">{{ ($mode ?? 'create') === 'edit' ? 'Update Fee' : 'Save Fee' }}</button>
                                @if (($mode ?? 'create') === 'edit')
                                    <a href="{{ url('user/teacher/fees') }}" class="btn btn-label-secondary">Cancel</a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            const studentsByBatch = @json($students_by_batch ?? []);
            const initialBatchId = @json((string) old('batch_id', $fee?->batch_id ?? ''));
            const initialStudentId = @json((string) old('student_id', $fee?->student_id ?? ''));
            const classroomSelect = document.getElementById('fee-classroom');
            const batchSelect = document.getElementById('fee-batch');
            const studentSelect = document.getElementById('fee-student');
            if (!classroomSelect || !batchSelect || !studentSelect) return;

            function filterBatches() {
                const classroomId = classroomSelect.value;
                let selectedBatchStillVisible = false;

                Array.from(batchSelect.options).forEach((option, index) => {
                    if (index === 0) {
                        option.hidden = false;
                        return;
                    }
                    const optionClassroomId = option.getAttribute('data-classroom-id');
                    const shouldShow = !classroomId || optionClassroomId === classroomId;
                    option.hidden = !shouldShow;
                    if (shouldShow && option.value === batchSelect.value) {
                        selectedBatchStillVisible = true;
                    }
                });

                if (!selectedBatchStillVisible) {
                    batchSelect.value = '';
                }
            }

            function rebuildStudentOptions() {
                const batchId = batchSelect.value;
                const list = studentsByBatch[batchId] || [];
                const prev = studentSelect.value;
                let keep = '';
                if (batchId && initialStudentId && batchId === initialBatchId) {
                    keep = String(initialStudentId);
                } else if (prev && list.some(function(s) {
                        return String(s.id) === prev;
                    })) {
                    keep = prev;
                }

                studentSelect.innerHTML = '';
                const emptyOpt = document.createElement('option');
                emptyOpt.value = '';
                emptyOpt.textContent = list.length ? 'Select student' : 'No students in this batch';
                studentSelect.appendChild(emptyOpt);

                list.forEach(function(s) {
                    const opt = document.createElement('option');
                    opt.value = String(s.id);
                    opt.textContent = s.email ? s.name + ' (' + s.email + ')' : s.name;
                    if (keep && String(s.id) === keep) {
                        opt.selected = true;
                    }
                    studentSelect.appendChild(opt);
                });

                if (keep && !Array.from(studentSelect.options).some(function(o) {
                        return o.value === keep;
                    })) {
                    const opt = document.createElement('option');
                    opt.value = keep;
                    opt.textContent = 'Current selection (#' + keep + ')';
                    opt.selected = true;
                    studentSelect.appendChild(opt);
                }
            }

            classroomSelect.addEventListener('change', function() {
                filterBatches();
                rebuildStudentOptions();
            });
            batchSelect.addEventListener('change', rebuildStudentOptions);

            filterBatches();
            rebuildStudentOptions();
        })();
    </script>
@endsection
