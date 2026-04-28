@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-12 col-xl-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ ($mode ?? 'create') === 'edit' ? 'Edit Timeline' : 'Add Timeline' }}</h5>
                        <a href="{{ url('user/teacher/timelines/list') }}" class="btn btn-sm btn-label-primary">View Timeline List</a>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <form method="post" action="{{ url('user/teacher/timelines/store') }}">
                            @csrf
                            <input type="hidden" name="id" value="{{ old('id', $timeline->id ?? '') }}">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Classroom <span class="text-danger">*</span></label>
                                    <select name="classroom_id" id="timeline-classroom" class="form-select @error('classroom_id') is-invalid @enderror">
                                        <option value="">Select classroom</option>
                                        @foreach ($classrooms ?? [] as $classroom)
                                            <option value="{{ $classroom->id }}" {{ old('classroom_id', $timeline->classroom_id ?? '') == $classroom->id ? 'selected' : '' }}>
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
                                    <select name="batch_id" id="timeline-batch" class="form-select @error('batch_id') is-invalid @enderror">
                                        <option value="">Select batch</option>
                                        @foreach ($batches ?? [] as $batch)
                                            <option
                                                value="{{ $batch->id }}"
                                                data-classroom-id="{{ $batch->classroom_id }}"
                                                {{ old('batch_id', $timeline->batch_id ?? '') == $batch->id ? 'selected' : '' }}>
                                                {{ $batch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('batch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Topic <span class="text-danger">*</span></label>
                                    <input type="text" name="topic" class="form-control @error('topic') is-invalid @enderror"
                                        value="{{ old('topic', $timeline->topic ?? '') }}" placeholder="Enter timeline topic">
                                    @error('topic')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                                        value="{{ old('start_date', $timeline->start_date ?? '') }}">
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                                        value="{{ old('end_date', $timeline->end_date ?? '') }}">
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                                        <option value="0" {{ (string) old('status', $timeline->status ?? '0') === '0' ? 'selected' : '' }}>Inactive</option>
                                        <option value="1" {{ (string) old('status', $timeline->status ?? '') === '1' ? 'selected' : '' }}>Active</option>
                                        <option value="2" {{ (string) old('status', $timeline->status ?? '') === '2' ? 'selected' : '' }}>Completed</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">{{ ($mode ?? 'create') === 'edit' ? 'Update Timeline' : 'Save Timeline' }}</button>
                                @if (($mode ?? 'create') === 'edit')
                                    <a href="{{ url('user/teacher/timelines') }}" class="btn btn-label-secondary">Cancel</a>
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
            const classroomSelect = document.getElementById('timeline-classroom');
            const batchSelect = document.getElementById('timeline-batch');
            if (!classroomSelect || !batchSelect) return;

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

            classroomSelect.addEventListener('change', filterBatches);
            filterBatches();
        })();
    </script>
@endsection
