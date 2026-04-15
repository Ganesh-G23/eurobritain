@extends('web.user.student.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @if (($classroom->batches ?? collect())->isEmpty())
            <div class="alert alert-info mb-0">
                No batches in this classroom yet. Your teacher will add them when ready.
            </div>
        @else
            @if (session('import_marks_success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('import_marks_success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('import_marks_error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('import_marks_error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12">
                    <strong>
                        <h5>{{ $classroom->name }}</h5>
                    </strong>
                    <div class="card mb-4 mt-3">
                        <div class="card-body py-3">
                            <div
                                class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
                                <div class="nav-align-top mb-0 flex-grow-1">
                                    <ul class="nav nav-pills flex-column flex-md-row mb-0 gap-md-0 gap-2"
                                        id="classroomBatchTabs" role="tablist">
                                        @foreach ($classroom->batches as $batch)
                                            <li class="nav-item" role="presentation">
                                                <button type="button"
                                                    class="nav-link @if ($loop->first) active @endif"
                                                    id="batch-tab-{{ $batch->id }}" data-bs-toggle="tab"
                                                    data-bs-target="#batch-pane-{{ $batch->id }}" role="tab"
                                                    aria-controls="batch-pane-{{ $batch->id }}"
                                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                    <i class="icon-base ti tabler-folders icon-sm me-1_5"></i>
                                                    {{ $batch->name }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="demo-inline-spacing mt-4">
                            <div class="list-group list-group-horizontal-md text-md-center">
                                <a class="list-group-item list-group-item-action active" id="home-list-item"
                                    data-bs-toggle="list" href="#horizontal-home">Tests</a>
                            </div>
                            <div class="tab-content px-0 mt-0">
                                <div class="tab-pane fade show active" id="horizontal-home">

                                    @foreach ($classroom->batches as $batch)
                                        {{-- Show only active batch --}}
                                        <div class="batch-tests @if ($batch->id != $default_batch_id) d-none @endif"
                                            id="batch-tests-{{ $batch->id }}">

                                            @if ($batch->exams->isEmpty())
                                                <div class="alert alert-info">
                                                    No tests available in this batch.
                                                </div>
                                            @else
                                                <div class="row g-3">
                                                    @foreach ($batch->exams as $exam)
                                                        @php
                                                            $obtained = $exam->student_marks_obtained;
                                                            $isAbsent = ! empty($exam->student_is_absent);
                                                            $absentDisplay = $exam->student_absent_display ?? null;
                                                            $hasMark =
                                                                ! $isAbsent && $obtained !== null && $obtained !== '';
                                                            $highlight = $highlight_exam_id == $exam->id;
                                                        @endphp

                                                        <div class="col-lg-4 col-md-6" id="exam-{{ $exam->id }}">

                                                            <div
                                                                class="card h-100 shadow-sm 
                            {{ $highlight ? 'border border-primary' : '' }}">

                                                                <div class="card-body">

                                                                    {{-- Header --}}
                                                                    <div
                                                                        class="d-flex justify-content-between align-items-center mb-2">
                                                                        <h5 class="mb-0 text-truncate">
                                                                            {{ $exam->exam_name }}
                                                                        </h5>

                                                                        <span class="badge bg-label-primary">
                                                                            @if ($isAbsent && $absentDisplay)
                                                                                {{ $absentDisplay }}
                                                                            @elseif ($hasMark)
                                                                                {{ $obtained }}
                                                                            @else
                                                                                —
                                                                            @endif
                                                                            @if ($exam->max_marks)
                                                                                / {{ $exam->max_marks }}
                                                                            @endif
                                                                        </span>
                                                                    </div>

                                                                    {{-- Details --}}
                                                                    <ul class="list-unstyled mb-2 small">
                                                                        <li>
                                                                            <strong>Teacher:</strong>
                                                                            {{ $teacher->name ?? '—' }}
                                                                        </li>

                                                                        <li>
                                                                            <strong>Test:</strong>
                                                                            {{ $exam->exam_name }}
                                                                        </li>

                                                                        <li>
                                                                            <strong>Max Marks:</strong>
                                                                            {{ $exam->max_marks ?? '—' }}
                                                                        </li>

                                                                        <li>
                                                                            <strong>Total Marks:</strong>
                                                                            @if ($isAbsent && $absentDisplay)
                                                                                {{ $absentDisplay }}
                                                                            @else
                                                                                {{ $hasMark ? $obtained : 'Not entered' }}
                                                                            @endif
                                                                        </li>
                                                                    </ul>

                                                                    {{-- Date --}}
                                                                    @if ($exam->exam_date)
                                                                        <div class="text-muted small border-top pt-2">
                                                                            <i class="ti tabler-calendar me-1"></i>
                                                                            {{ $exam->exam_date->format('M d, Y') }}
                                                                        </div>
                                                                    @endif

                                                                </div>
                                                            </div>

                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {

    const defaultBatch = "{{ $default_batch_id }}";

    function showBatch(batchId) {
        document.querySelectorAll('.batch-tests').forEach(el => {
            el.classList.add('d-none');
        });

        let active = document.getElementById('batch-tests-' + batchId);
        if (active) {
            active.classList.remove('d-none');
        }
    }

    // Load default batch
    if (defaultBatch) {
        showBatch(defaultBatch);
    }

    // On tab click
    document.querySelectorAll('[id^="batch-tab-"]').forEach(btn => {
        btn.addEventListener('click', function () {
            let batchId = this.id.replace('batch-tab-', '');
            showBatch(batchId);
        });
    });

    // Scroll to highlighted exam
    let highlightExam = "{{ $highlight_exam_id }}";
    if (highlightExam) {
        setTimeout(() => {
            let el = document.getElementById('exam-' + highlightExam);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 400);
    }

});
</script>

@endsection
