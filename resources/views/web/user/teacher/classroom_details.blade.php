@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @if (($classroom->batches ?? collect())->isEmpty())
            <div class="alert alert-info mb-0">
                No batches in this classroom yet.
                <a href="{{ url('user/teacher/batches') }}" class="alert-link">Manage batches</a>
            </div>
        @else
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-body py-3">
                            <div class="nav-align-top mb-0">
                                <ul class="nav nav-pills flex-column flex-md-row mb-0 gap-md-0 gap-2" id="classroomBatchTabs"
                                    role="tablist">
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

                    <div class="tab-content">
                        @foreach ($classroom->batches as $batch)
                            @php
                                $list = $students_by_batch[$batch->id] ?? collect();
                                $batchExams = $exams_by_batch[$batch->id] ?? collect();
                                $marksGrid = $marks_by_exam_student ?? [];
                            @endphp
                            <div class="tab-pane fade @if ($loop->first) show active @endif"
                                id="batch-pane-{{ $batch->id }}" role="tabpanel"
                                aria-labelledby="batch-tab-{{ $batch->id }}" tabindex="0">

                                <ul class="nav nav-pills flex-column flex-sm-row mb-4 gap-2"
                                    id="inner-tabs-{{ $batch->id }}" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button type="button" class="nav-link active"
                                            id="inner-students-tab-{{ $batch->id }}" data-bs-toggle="tab"
                                            data-bs-target="#inner-students-{{ $batch->id }}" role="tab"
                                            aria-controls="inner-students-{{ $batch->id }}" aria-selected="true">
                                            <i class="icon-base ti tabler-users icon-sm me-1"></i> All students
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button type="button" class="nav-link" id="inner-tests-tab-{{ $batch->id }}"
                                            data-bs-toggle="tab" data-bs-target="#inner-tests-{{ $batch->id }}"
                                            role="tab" aria-controls="inner-tests-{{ $batch->id }}"
                                            aria-selected="false">
                                            <i class="icon-base ti tabler-clipboard-list icon-sm me-1"></i> Test
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="inner-students-{{ $batch->id }}"
                                        role="tabpanel" aria-labelledby="inner-students-tab-{{ $batch->id }}">
                                        <div class="mb-3">
                                            <h6 class="mb-1">Students — {{ $batch->name }}</h6>
                                            <small class="text-body-secondary">{{ $list->count() }}
                                                student{{ $list->count() === 1 ? '' : 's' }}</small>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Phone</th>
                                                        <th class="text-end">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($list as $row)
                                                        <tr>
                                                            <td class="fw-medium">{{ $row->name }}</td>
                                                            <td><small>{{ $row->email ?? '—' }}</small></td>
                                                            <td><small>{{ $row->phone ?? '—' }}</small></td>
                                                            <td class="text-end">
                                                                <a href="{{ url('user/teacher/students/view/' . base64_encode((string) $row->id)) }}"
                                                                    class="btn btn-sm btn-icon btn-label-secondary"
                                                                    title="View">
                                                                    <i class="icon-base ti tabler-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4"
                                                                class="text-body-secondary small text-center py-4">
                                                                No students in this batch for your account.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="inner-tests-{{ $batch->id }}" role="tabpanel"
                                        aria-labelledby="inner-tests-tab-{{ $batch->id }}">
                                        <div class="d-flex flex-wrap align-items-start gap-2 mb-4">
                                            <div class="flex-grow-1 min-w-0">
                                                <h6 class="mb-1">Tests — {{ $batch->name }}</h6>
                                                <small class="text-body-secondary d-block">
                                                    @if ($batchExams->isEmpty())
                                                        Add tests to build the marks sheet; each test becomes a column.
                                                    @else
                                                        {{ $batchExams->count() }}
                                                        test{{ $batchExams->count() === 1 ? '' : 's' }}
                                                        · {{ $list->count() }}
                                                        student{{ $list->count() === 1 ? '' : 's' }}
                                                    @endif
                                                </small>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary flex-shrink-0 btn-add-exam"
                                                data-batch-id="{{ $batch->id }}" data-bs-toggle="modal"
                                                data-bs-target="#addExamModal">
                                                <i class="icon-base ti tabler-plus me-1"></i> Add test
                                            </button>
                                        </div>
                                        @if ($batchExams->isEmpty())
                                            <p class="text-body-secondary small mb-0">No tests yet. Use
                                                <strong>Add test</strong> (top right) to create one.
                                            </p>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover align-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-nowrap">ID</th>
                                                            <th class="text-nowrap">Student name</th>
                                                            @foreach ($batchExams as $exam)
                                                                <th class="text-center text-nowrap"
                                                                    title="{{ $exam->exam_date?->timezone(config('app.timezone'))->format('M j, Y') ?? '—' }}">
                                                                    <span class="fw-medium">{{ $exam->exam_name }}({{ $exam->max_marks }})</span>
                                                                    
                                                                </th>
                                                            @endforeach
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($list as $key => $row)
                                                            <tr>
                                                                <td><small
                                                                        class="text-body-secondary">{{ $key + 1 }}</small>
                                                                </td>
                                                                <td class="fw-medium">{{ $row->name }}</td>
                                                                @foreach ($batchExams as $exam)
                                                                    @php
                                                                        $cell =
                                                                            ($marksGrid[$exam->id] ?? [])[$row->id] ??
                                                                            null;
                                                                    @endphp
                                                                    <td class="text-center">
                                                                        <small>{{ $cell !== null && $cell !== '' ? $cell : '—' }}</small>
                                                                    </td>
                                                                @endforeach
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="{{ 2 + $batchExams->count() }}"
                                                                    class="text-body-secondary small text-center py-4">
                                                                    No students in this batch for your account.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            @if (!($marks_table_ready ?? true))
                                                <p class="text-warning small mt-2 mb-0">Run migrations so the
                                                    <code>marks</code> table exists; then marks can be stored per
                                                    student and test.
                                                </p>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="modal fade" id="addExamModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add test</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form id="addExamForm">
                            @csrf
                            <input type="hidden" name="batch_id" id="exam_batch_id" value="">
                            <div class="modal-body">
                                <div class="exam-form-msg mb-3"></div>
                                <div class="mb-3 ajax-field">
                                    <label class="form-label" for="exam_name">Test name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="exam_name" name="exam_name" required
                                        placeholder="e.g. Unit 1">
                                    <span class="text-danger small ajax-error d-block mt-1"></span>
                                </div>
                                <div class="mb-3 ajax-field">
                                    <label class="form-label" for="max_marks">Max marks <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="max_marks" name="max_marks" required
                                        placeholder="e.g. 100">
                                    <span class="text-danger small ajax-error d-block mt-1"></span>
                                </div>
                                <div class="mb-0 ajax-field">
                                    <label class="form-label" for="exam_date">Date &amp; time <span
                                            class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="exam_date" name="exam_date"
                                        required>
                                    <span class="text-danger small ajax-error d-block mt-1"></span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="addExamSubmit">Save test</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    @if (!($classroom->batches ?? collect())->isEmpty())
        <script>
            (function() {
                document.querySelectorAll('.btn-add-exam').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var id = btn.getAttribute('data-batch-id');
                        var input = document.getElementById('exam_batch_id');
                        if (input) {
                            input.value = id;
                        }
                        var modal = document.getElementById('addExamModal');
                        if (modal) {
                            modal.querySelectorAll('.ajax-error').forEach(function(el) {
                                el.textContent = '';
                            });
                            var msg = modal.querySelector('.exam-form-msg');
                            if (msg) {
                                msg.innerHTML = '';
                            }
                        }
                    });
                });

                var examModal = document.getElementById('addExamModal');
                if (examModal) {
                    examModal.addEventListener('hidden.bs.modal', function() {
                        var form = document.getElementById('addExamForm');
                        if (form) {
                            form.reset();
                            document.getElementById('exam_batch_id').value = '';
                        }
                        examModal.querySelectorAll('.ajax-error').forEach(function(el) {
                            el.textContent = '';
                        });
                        var msg = examModal.querySelector('.exam-form-msg');
                        if (msg) {
                            msg.innerHTML = '';
                        }
                    });
                }
            })();
        </script>
        <script>
            $(function() {
                $('#addExamForm').on('submit', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var modal = $('#addExamModal');
                    form.find('.ajax-error').text('');
                    modal.find('.exam-form-msg').html('');
                    var btn = $('#addExamSubmit');
                    btn.prop('disabled', true).text('Saving...');
                    var payload = form.serializeArray();
                    $.post("{{ url('user/teacher/exams/save') }}", payload, function(res) {
                        btn.prop('disabled', false).text('Save test');
                        if (res.status == 1) {
                            window.location.href = res.redirect_url || window.location.href;
                        } else if (res.error_array) {
                            Object.keys(res.error_array).forEach(function(key) {
                                var err = res.error_array[key];
                                var text = Array.isArray(err) ? err[0] : err;
                                form.find('[name="' + key + '"]').closest('.ajax-field').find(
                                    '.ajax-error').text(
                                    text);
                            });
                        } else if (res.error) {
                            modal.find('.exam-form-msg').html(
                                '<div class="alert alert-danger mb-0">' + res.error + '</div>');
                        }
                    }, 'json').fail(function() {
                        btn.prop('disabled', false).text('Save test');
                        modal.find('.exam-form-msg').html(
                            '<div class="alert alert-danger mb-0">Request failed</div>');
                    });
                });
            });
        </script>
    @endif
@endsection
