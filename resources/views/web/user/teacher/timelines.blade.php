@extends('web.user.layouts.app')

@push('page_styles')
    <style>
        #timeline-batches-wrap:not(.is-ready) select#timeline-batch {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        #timeline-batches-wrap:not(.is-ready) .select2-container {
            display: none !important;
        }

        #timeline-batches-wrap:not(.is-ready) {
            min-height: 2.375rem;
        }
    </style>
@endpush

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
                            @php
                                $selectedBatchIds = collect(old('batch_ids', $timeline->batch_ids ?? []))
                                    ->map(fn ($id) => (int) $id)
                                    ->filter(fn ($id) => $id > 0)
                                    ->values()
                                    ->all();
                                $initialClassroomId = (int) old('classroom_id', $timeline->classroom_id ?? 0);
                            @endphp
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Classroom <span class="text-danger">*</span></label>
                                    <select name="classroom_id" id="timeline-classroom" class="form-select @error('classroom_id') is-invalid @enderror">
                                        <option value="">Select classroom</option>
                                        @foreach ($classrooms ?? [] as $classroom)
                                            <option value="{{ $classroom->id }}" {{ $initialClassroomId === (int) $classroom->id ? 'selected' : '' }}>
                                                {{ $classroom->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('classroom_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 select2-primary" id="timeline-batches-wrap">
                                    <label class="form-label">Batches <span class="text-danger">*</span></label>
                                    <select name="batch_ids[]" id="timeline-batch" class="form-select @error('batch_ids') is-invalid @enderror" multiple disabled></select>
                                    <div class="form-text">Choose a classroom first, then select one or more batches.</div>
                                    @error('batch_ids')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('batch_ids.*')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Topic <span class="text-danger">*</span></label>
                                    <input type="text" name="topic" class="form-control @error('topic') is-invalid @enderror"
                                        value="{{ old('topic', $timeline->topic ?? '') }}" placeholder="Enter topic to cover">
                                    @error('topic')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                        value="{{ old('date', isset($timeline->date) ? $timeline->date->format('Y-m-d') : '') }}">
                                    @error('date')
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
            var batchesUrl = @json(url('user/teacher/batches-by-classroom'));
            var initialClassroomId = @json($initialClassroomId > 0 ? $initialClassroomId : null);
            var initialBatchIds = @json($selectedBatchIds);
            var $classroom = $('#timeline-classroom');
            var $batch = $('#timeline-batch');
            var $wrap = $('#timeline-batches-wrap');

            if (!$classroom.length || !$batch.length) {
                return;
            }

            function markBatchesReady() {
                if ($wrap.length) {
                    $wrap.addClass('is-ready');
                }
            }

            function normalizeSelectedIds(ids) {
                return (ids || []).map(function(id) {
                    return String(id);
                });
            }

            function initBatchSelect2(placeholder, disabled, batches, selectedIds) {
                selectedIds = normalizeSelectedIds(selectedIds || []);

                if ($batch.data('select2')) {
                    $batch.select2('destroy');
                }

                $batch.empty();
                (batches || []).forEach(function(batch) {
                    var id = String(batch.id);
                    $batch.append(
                        $('<option></option>')
                            .attr('value', batch.id)
                            .prop('selected', selectedIds.indexOf(id) !== -1)
                            .text(batch.name)
                    );
                });

                $batch.prop('disabled', false);
                $batch.select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%'
                });

                if (disabled) {
                    $batch.prop('disabled', true);
                }

                markBatchesReady();
            }

            function loadBatches(classroomId, selectedIds) {
                if (!classroomId) {
                    initBatchSelect2('Select classroom first', true, [], []);
                    return;
                }

                $.get(batchesUrl, { classroom_id: classroomId }, function(res) {
                    if (res.status == 1 && res.data && res.data.length) {
                        initBatchSelect2('Select batches', false, res.data, selectedIds);
                    } else {
                        initBatchSelect2('No batches in this classroom', true, [], []);
                    }
                }, 'json').fail(function() {
                    initBatchSelect2('Could not load batches', true, [], []);
                });
            }

            $classroom.on('change', function() {
                $wrap.removeClass('is-ready');
                loadBatches($(this).val(), []);
            });

            $classroom.closest('form').on('submit', function() {
                $batch.prop('disabled', false);
            });

            if (initialClassroomId) {
                loadBatches(initialClassroomId, initialBatchIds);
            } else {
                initBatchSelect2('Select classroom first', true, [], []);
            }
        })();
    </script>
@endsection
