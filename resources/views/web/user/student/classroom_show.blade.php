@extends('web.user.student.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 pb-2 pt-0">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
            <div>
                <a href="{{ url('user/student/dashboard') }}" class="btn btn-sm btn-label-secondary mb-2">
                    <i class="icon-base ti tabler-arrow-left me-1"></i> My classrooms
                </a>
                <h4 class="mb-1">{{ $classroom->name }}</h4>
                <p class="text-body-secondary small mb-0">
                    @if ($teacher)
                        Teacher: <span class="text-heading fw-medium">{{ $teacher->name }}</span>
                    @endif
                    @if ($classroom->created_at)
                        <span class="d-none d-sm-inline"> · </span>
                        <span class="d-block d-sm-inline">Since
                            {{ $classroom->created_at->timezone(config('app.timezone'))->format('M j, Y') }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="row g-0">
                <div class="col-md-4 bg-label-primary bg-opacity-10 p-4 d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <div class="avatar avatar-xl mx-auto mb-2">
                            <span class="avatar-initial rounded-circle bg-primary text-white">
                                <i class="icon-base ti tabler-school icon-32px"></i>
                            </span>
                        </div>
                        <span class="badge bg-primary">Classroom</span>
                    </div>
                </div>
                <div class="col-md-8 p-4 p-md-5">
                    <h5 class="mb-3">Overview</h5>
                    <p class="text-body-secondary mb-4">
                        This page lists every batch in this classroom. Select a batch on the left to see its schedule
                        and details. Batches you are enrolled in are marked.
                    </p>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="border rounded p-3 h-100 text-center">
                                <div class="h4 mb-0 text-primary">{{ $classroom->batches->count() }}</div>
                                <small class="text-body-secondary">Batches</small>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="border rounded p-3 h-100 text-center">
                                <div class="h4 mb-0 text-success">{{ $total_students_in_classroom }}</div>
                                <small class="text-body-secondary">Students</small>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="border rounded p-3 h-100 text-center">
                                <div class="h4 mb-0 text-info">{{ $my_batch_count }}</div>
                                <small class="text-body-secondary">Your enrollments</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($classroom->batches->isEmpty())
            <div class="alert alert-info mb-0">
                No batches are set up for this classroom yet.
            </div>
        @else
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header border-0 pb-0">
                            <h6 class="mb-0">Batches</h6>
                            <small class="text-body-secondary">Tap one to view details</small>
                        </div>
                        <div class="card-body pt-3">
                            <div class="list-group list-group-flush rounded-2 border">
                                @foreach ($classroom->batches as $batch)
                                    <button type="button"
                                        class="list-group-item list-group-item-action py-3 student-batch-select {{ $loop->first ? 'active' : '' }}"
                                        data-student-batch-select="{{ $batch->id }}"
                                        aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="min-w-0">
                                                <div class="fw-semibold text-truncate">{{ $batch->name }}</div>
                                                @if ($batch->schedule)
                                                    <small class="text-body-secondary d-block text-truncate">{{ $batch->schedule }}</small>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                                                @if ($batch->is_my_batch)
                                                    <span class="badge bg-label-success">You</span>
                                                @endif
                                                <span class="badge bg-label-secondary">{{ $batch->enrollment_student_count }}</span>
                                            </div>
                                        </div>
                                        @if ($batch->status)
                                            <span class="badge bg-label-primary mt-2">{{ $batch->status }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    @foreach ($classroom->batches as $batch)
                        <div class="card border-0 shadow-sm student-batch-panel {{ $loop->first ? '' : 'd-none' }}"
                            data-student-batch-panel="{{ $batch->id }}">
                            <div class="card-body p-4 p-md-5">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                                    <div>
                                        <h5 class="mb-1">{{ $batch->name }}</h5>
                                        <p class="text-body-secondary small mb-0">Batch in {{ $classroom->name }}</p>
                                    </div>
                                    @if ($batch->is_my_batch)
                                        <span class="badge bg-success rounded-pill align-self-start">You are enrolled</span>
                                    @else
                                        <span class="badge bg-label-secondary rounded-pill align-self-start">Not enrolled</span>
                                    @endif
                                </div>

                                <div class="row g-3 mb-0">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start gap-3 p-3 rounded border">
                                            <span class="avatar avatar-sm">
                                                <span class="avatar-initial rounded bg-label-warning">
                                                    <i class="icon-base ti tabler-calendar-time icon-18px"></i>
                                                </span>
                                            </span>
                                            <div>
                                                <div class="small text-body-secondary text-uppercase fw-medium">Schedule</div>
                                                <div class="fw-medium">{{ $batch->schedule ?: '—' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start gap-3 p-3 rounded border">
                                            <span class="avatar avatar-sm">
                                                <span class="avatar-initial rounded bg-label-info">
                                                    <i class="icon-base ti tabler-flag icon-18px"></i>
                                                </span>
                                            </span>
                                            <div>
                                                <div class="small text-body-secondary text-uppercase fw-medium">Status</div>
                                                <div class="fw-medium">{{ $batch->status ?: '—' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex align-items-start gap-3 p-3 rounded border bg-label-secondary bg-opacity-10">
                                            <span class="avatar avatar-sm">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="icon-base ti tabler-users icon-18px"></i>
                                                </span>
                                            </span>
                                            <div>
                                                <div class="small text-body-secondary text-uppercase fw-medium">Students in
                                                    this batch</div>
                                                <div class="fw-medium">{{ $batch->enrollment_student_count }} total under this
                                                    teacher</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    @if (! $classroom->batches->isEmpty())
        <script>
            (function () {
                document.querySelectorAll('[data-student-batch-select]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var id = btn.getAttribute('data-student-batch-select');
                        document.querySelectorAll('.student-batch-panel').forEach(function (p) {
                            p.classList.add('d-none');
                        });
                        var panel = document.querySelector('[data-student-batch-panel="' + id + '"]');
                        if (panel) {
                            panel.classList.remove('d-none');
                        }
                        document.querySelectorAll('[data-student-batch-select]').forEach(function (b) {
                            b.classList.remove('active');
                            b.setAttribute('aria-pressed', 'false');
                        });
                        btn.classList.add('active');
                        btn.setAttribute('aria-pressed', 'true');
                    });
                });
            })();
        </script>
    @endif
@endsection
