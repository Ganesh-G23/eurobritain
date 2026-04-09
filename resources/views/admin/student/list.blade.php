@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">Students</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ url('admin/student/add') }}" class="btn btn-sm btn-primary">
                                <i class="icon-base ti tabler-user-plus me-1"></i> Add student
                            </a>
                            <button type="button" class="btn btn-sm btn-label-primary" data-bs-toggle="modal"
                                data-bs-target="#importStudentsModal">
                                <i class="icon-base ti tabler-upload me-1"></i> Import students
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ url('admin/student') }}" class="mb-4">
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">Search</label>
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Name, email or phone" value="{{ $search ?? '' }}">
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label class="form-label">Teacher</label>
                                    <select name="teacher_id" class="form-select">
                                        <option value="">All teachers</option>
                                        @foreach ($teachers ?? [] as $t)
                                            <option value="{{ $t->id }}"
                                                {{ (int) ($teacher_id ?? 0) === (int) $t->id ? 'selected' : '' }}>
                                                {{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label class="form-label">Classroom</label>
                                    <select name="classroom_id" class="form-select">
                                        <option value="">All classrooms</option>
                                        @foreach ($classrooms ?? [] as $c)
                                            <option value="{{ $c->id }}"
                                                {{ (int) ($classroom_id ?? 0) === (int) $c->id ? 'selected' : '' }}>
                                                {{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label class="form-label">Batch</label>
                                    <select name="batch_id" class="form-select">
                                        <option value="">All batches</option>
                                        @foreach ($batches ?? [] as $b)
                                            <option value="{{ $b->id }}"
                                                {{ (int) ($batch_id ?? 0) === (int) $b->id ? 'selected' : '' }}>
                                                {{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-12 d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        Apply
                                    </button>
                                    <a href="{{ url('admin/student') }}" class="btn btn-label-secondary">Reset</a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th class="text-center">Teachers</th>
                                        <th class="text-center">Classrooms</th>
                                        <th class="text-center">Batches</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($student_list ?? [] as $key => $student)
                                        @php
                                            $primaryTeacher = $student->teachers->first();
                                            $maps = $student->studentClassroomMaps;
                                            $teacherCount = $student->teachers->count();
                                            if ($maps->isNotEmpty()) {
                                                $classroomCount = $maps->pluck('classroom_id')->filter()->unique()->count();
                                                $batchCount = $maps->pluck('batch_id')->filter()->unique()->count();
                                            } else {
                                                $classroomCount = $student->classroom_id ? 1 : 0;
                                                $batchCount = $student->batch_id ? 1 : 0;
                                            }
                                            $mapsForPrimary = $primaryTeacher
                                                ? $maps->where('teacher_id', $primaryTeacher->id)->map(static function ($m) {
                                                    return [
                                                        'classroom_id' => (int) $m->classroom_id,
                                                        'batch_id' => (int) $m->batch_id,
                                                    ];
                                                })->values()
                                                : collect();
                                        @endphp
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td class="fw-medium">{{ $student->name }}</td>
                                            <td>{{ $student->email ?? '—' }}</td>
                                            <td>{{ $student->phone ?? '—' }}</td>
                                            <td class="text-center">{{ $teacherCount }}</td>
                                            <td class="text-center">{{ $classroomCount }}</td>
                                            <td class="text-center">{{ $batchCount }}</td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                                    @if ($primaryTeacher)
                                                        <button type="button"
                                                            class="btn btn-sm btn-icon btn-label-secondary open-student-enrollments-list"
                                                            data-student-id="{{ base64_encode($student->id) }}"
                                                            data-teacher-id="{{ $primaryTeacher->id }}"
                                                            data-maps='@json($mapsForPrimary)'
                                                            title="Classrooms &amp; batches">
                                                            <i class="icon-base ti tabler-school"></i>
                                                        </button>
                                                    @else
                                                        <span class="btn btn-sm btn-icon btn-label-secondary disabled"
                                                            title="Assign a teacher first (edit student)">
                                                            <i class="icon-base ti tabler-school"></i>
                                                        </span>
                                                    @endif
                                                    <a href="{{ url('admin/student/edit?id=' . base64_encode($student->id)) }}"
                                                        class="btn btn-sm btn-icon btn-label-primary" title="Edit">
                                                        <i class="icon-base ti tabler-edit"></i>
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-sm btn-icon btn-label-danger delete-student-btn"
                                                        data-id="{{ base64_encode($student->id) }}" title="Delete">
                                                        <i class="icon-base ti tabler-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No students found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (($num_rows ?? 0) > ($per_page ?? 50))
                            @php
                                $totalPages = (int) ceil($num_rows / $per_page);
                            @endphp
                            <div class="mt-3">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center mb-0">
                                        @for ($i = 1; $i <= $totalPages; $i++)
                                            <li class="page-item {{ (int) ($page ?? 1) === $i ? 'active' : '' }}">
                                                <a class="page-link"
                                                    href="{{ url('admin/student') }}?{{ http_build_query(array_merge($query_params ?? [], ['page' => $i])) }}">{{ $i }}</a>
                                            </li>
                                        @endfor
                                    </ul>
                                </nav>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Classrooms & batches (same behavior as teacher student list) -->
    <div class="modal fade" id="studentListEnrollmentsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Classrooms &amp; batches</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="studentListEnrollmentsForm">
                        <input type="hidden" name="student_id" id="list_enr_student_id" value="">
                        <input type="hidden" name="teacher_id" id="list_enr_teacher_id" value="">
                        <input type="hidden" name="return_student_list" value="1">
                        <p class="text-body-secondary small">One row per classroom and batch for this teacher. The first row
                            sets the student’s primary classroom and batch for display.</p>
                        <div id="student-list-enrollment-rows" class="mb-3"></div>
                        <button type="button" class="btn btn-sm btn-label-primary" id="student-list-add-enrollment-row">
                            <i class="icon-base ti tabler-plus me-1"></i> Add row
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="studentListSaveEnrollmentsBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <template id="student-list-enrollment-row-template">
        <div class="row g-2 align-items-end student-list-enrollment-row mb-3 pb-3 border-bottom">
            <div class="col-md-5">
                <label class="form-label small mb-1">Classroom</label>
                <select class="form-select list-enr-classroom" name="map_classroom_id[]">
                    <option value="">Choose classroom</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small mb-1">Batch</label>
                <select class="form-select list-enr-batch" name="map_batch_id[]" disabled>
                    <option value="">Choose classroom first</option>
                </select>
            </div>
            <div class="col-md-2 text-md-end">
                <button type="button" class="btn btn-sm btn-label-danger student-list-remove-enr-row" title="Remove row">
                    <i class="icon-base ti tabler-trash"></i>
                </button>
            </div>
        </div>
    </template>

    <!-- Import students (CSV) -->
    <div class="modal fade" id="importStudentsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import students (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3 small">
                        <strong>1.</strong> Choose the teacher the students belong to.<br>
                        <strong>2.</strong> Download the sample and fill rows using that teacher’s classroom and batch names (or numeric IDs).<br>
                        <strong>3.</strong> Required columns:
                        <strong>name, email, phone, classroom, batch</strong> (first pair = primary display).<br>
                        <strong>Optional extra enrollments</strong> (same teacher only):
                        <strong>classroom_2, batch_2</strong> and <strong>classroom_3, batch_3</strong> — leave blank if not used.<br>
                        For each import row, all pairs you fill <strong>replace</strong> that student’s classroom/batch mappings for this teacher (same idea as the school icon on the student list).<br>
                        Optional parent columns: <strong>parent_name, parent_email, parent_phone</strong>.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teacher <span class="text-danger">*</span></label>
                        <select class="form-select" id="import_student_teacher_id" required>
                            <option value="">Select teacher</option>
                            @foreach ($teachers ?? [] as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-label-primary" id="btn-download-student-sample">
                            <i class="icon-base ti tabler-download me-1"></i> Download sample CSV
                        </button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CSV file</label>
                        <input type="file" class="form-control" id="import_student_csv_file" accept=".csv,text/csv">
                        <small class="text-body-secondary">Max 5 MB</small>
                    </div>
                    <div id="import-student-msg" class="mb-0"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="btn-import-students-upload">
                        <i class="icon-base ti tabler-upload me-1"></i> Upload
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        function escapeHtmlImport(text) {
            if (text === null || text === undefined) return '';
            const d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        }

        $(document).ready(function() {
            const enrollmentOptionsUrl = "{{ url('admin/student/enrollment-options') }}";
            let listEnrClassrooms = [];
            let listEnrBatches = [];

            function loadListBatchesIntoSelect($batchSelect, classroomId, selectedBatchId, done) {
                selectedBatchId = selectedBatchId || '';
                if (!classroomId) {
                    $batchSelect.html('<option value="">Choose classroom first</option>').prop('disabled', true);
                    if (typeof done === 'function') done();
                    return;
                }
                const batches = listEnrBatches.filter(function(b) {
                    return String(b.classroom_id) === String(classroomId);
                });
                if (!batches.length) {
                    $batchSelect.html('<option value="">No batches found</option>').prop('disabled', true);
                    if (typeof done === 'function') done();
                    return;
                }
                $batchSelect.empty().append($('<option/>', {
                    value: '',
                    text: 'Choose batch'
                }));
                batches.forEach(function(batch) {
                    const o = $('<option/>').attr('value', batch.id).text(batch.name);
                    if (String(batch.id) === String(selectedBatchId)) {
                        o.prop('selected', true);
                    }
                    $batchSelect.append(o);
                });
                $batchSelect.prop('disabled', false);
                if (typeof done === 'function') done();
            }

            function appendListEnrollmentRow(classroomId, batchId) {
                const tpl = document.getElementById('student-list-enrollment-row-template');
                if (!tpl) {
                    return;
                }
                const frag = tpl.content.cloneNode(true);
                const el = frag.querySelector('.student-list-enrollment-row');
                $('#student-list-enrollment-rows').append(el);
                const $row = $(el);
                const $c = $row.find('.list-enr-classroom');
                const $b = $row.find('.list-enr-batch');
                listEnrClassrooms.forEach(function(c) {
                    $c.append($('<option/>').attr('value', c.id).text(c.name));
                });
                if (classroomId) {
                    $c.val(String(classroomId));
                }
                loadListBatchesIntoSelect($b, $c.val(), batchId);
            }

            $(document).on('click', '.open-student-enrollments-list', function() {
                const studentId = $(this).data('student-id');
                const teacherId = $(this).data('teacher-id');
                let maps = $(this).attr('data-maps');
                let parsed = [];
                try {
                    parsed = maps ? JSON.parse(maps) : [];
                } catch (e) {
                    parsed = [];
                }
                $('#list_enr_student_id').val(studentId);
                $('#list_enr_teacher_id').val(teacherId);
                clearAjaxErrors('#studentListEnrollmentsModal');
                $('#student-list-enrollment-rows').html(
                    '<p class="small text-muted mb-0">Loading…</p>');
                $.get(enrollmentOptionsUrl, {
                    teacher_id: teacherId
                }, function(res) {
                    if (res.status != 1) {
                        alert(res.error || 'Could not load classrooms.');
                        $('#student-list-enrollment-rows').empty();
                        return;
                    }
                    listEnrClassrooms = res.data.classrooms || [];
                    listEnrBatches = res.data.batches || [];
                    $('#student-list-enrollment-rows').empty();
                    if (!listEnrClassrooms.length) {
                        $('#student-list-enrollment-rows').html(
                            '<div class="alert alert-warning mb-0">This teacher has no classrooms yet. Add them from the teacher page.</div>'
                        );
                    } else if (parsed && parsed.length) {
                        parsed.forEach(function(m) {
                            appendListEnrollmentRow(m.classroom_id, m.batch_id);
                        });
                    } else {
                        appendListEnrollmentRow('', '');
                    }
                    const modalEl = document.getElementById('studentListEnrollmentsModal');
                    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    } else {
                        $('#studentListEnrollmentsModal').modal('show');
                    }
                }, 'json');
            });

            $(document).on('change', '#studentListEnrollmentsModal .list-enr-classroom', function() {
                const $row = $(this).closest('.student-list-enrollment-row');
                loadListBatchesIntoSelect($row.find('.list-enr-batch'), $(this).val(), '');
            });

            $(document).on('click', '#student-list-add-enrollment-row', function() {
                if (!listEnrClassrooms.length) {
                    return;
                }
                appendListEnrollmentRow('', '');
            });

            $(document).on('click', '.student-list-remove-enr-row', function() {
                const $rows = $('#student-list-enrollment-rows .student-list-enrollment-row');
                if ($rows.length <= 1) {
                    $(this).closest('.student-list-enrollment-row').find('.list-enr-classroom').val('');
                    $(this).closest('.student-list-enrollment-row').find('.list-enr-batch').html(
                        '<option value="">Choose classroom first</option>').prop('disabled', true);
                    return;
                }
                $(this).closest('.student-list-enrollment-row').remove();
            });

            $('#studentListSaveEnrollmentsBtn').on('click', function() {
                clearAjaxErrors('#studentListEnrollmentsModal');
                const form = $('#studentListEnrollmentsForm');
                const formData = form.serializeArray();
                formData.push({
                    name: '_token',
                    value: '{{ csrf_token() }}'
                });
                $.post("{{ url('admin/teacher/sync_student_enrollments') }}", formData, function(res) {
                    if (res.status == 1) {
                        const modalEl = document.getElementById('studentListEnrollmentsModal');
                        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                            window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                        } else {
                            $('#studentListEnrollmentsModal').modal('hide');
                        }
                        processAjaxResponse(res, 500);
                    } else {
                        processAjaxResponse(res, 0, '#studentListEnrollmentsModal');
                    }
                }, 'json');
            });

            const bulkSampleUrl = '{{ url("admin/teacher/students/bulk-sample") }}';
            const bulkUploadUrl = '{{ url("admin/teacher/students/bulk-upload") }}';

            $('#importStudentsModal').on('hidden.bs.modal', function() {
                $('#import_student_csv_file').val('');
                $('#import-student-msg').html('');
            });

            $('#btn-download-student-sample').on('click', function() {
                const tid = $('#import_student_teacher_id').val();
                if (!tid) {
                    alert('Please select a teacher first.');
                    return;
                }
                window.location.href = bulkSampleUrl + (bulkSampleUrl.indexOf('?') >= 0 ? '&' : '?') + 'teacher_id=' +
                    encodeURIComponent(tid);
            });

            $('#btn-import-students-upload').on('click', function() {
                const tid = $('#import_student_teacher_id').val();
                const fileInput = document.getElementById('import_student_csv_file');
                const file = fileInput.files[0];
                const $btn = $(this);
                const $msg = $('#import-student-msg');
                $msg.html('');

                if (!tid) {
                    $msg.html('<div class="alert alert-danger mb-0">Select a teacher.</div>');
                    return;
                }
                if (!file) {
                    $msg.html('<div class="alert alert-danger mb-0">Choose a CSV file.</div>');
                    return;
                }

                const data = new FormData();
                data.append('_token', '{{ csrf_token() }}');
                data.append('teacher_id', tid);
                data.append('file', file);

                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Uploading...');
                $.ajax({
                    url: bulkUploadUrl,
                    type: 'POST',
                    data: data,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(res) {
                        $btn.prop('disabled', false).html(
                            '<i class="icon-base ti tabler-upload me-1"></i> Upload');
                        if (res.status == 1) {
                            let html = '<div class="alert alert-success mb-2">' + escapeHtmlImport(res.msg ||
                                'Upload completed') + '</div>';
                            if (res.errors && res.errors.length) {
                                const items = res.errors.slice(0, 15).map(function(err) {
                                    return '<li>' + escapeHtmlImport(err) + '</li>';
                                }).join('');
                                html +=
                                    '<div class="alert alert-warning mb-0"><strong>Row notes:</strong><ul class="mb-0 mt-2 small">' +
                                    items + '</ul></div>';
                            }
                            $msg.html(html);
                            if (res.summary && Number(res.summary.created || 0) > 0) {
                                setTimeout(function() {
                                    window.location.href = '{{ url("admin/student") }}';
                                }, 1500);
                            }
                        } else if (res.error_array) {
                            const firstKey = Object.keys(res.error_array)[0];
                            const firstErr = res.error_array[firstKey] ? res.error_array[firstKey][0] : 'Validation failed';
                            $msg.html('<div class="alert alert-danger mb-0">' + escapeHtmlImport(firstErr) +
                                '</div>');
                        } else {
                            let html = '<div class="alert alert-danger mb-2">' + escapeHtmlImport(res.error ||
                                'Upload failed') + '</div>';
                            if (res.errors && res.errors.length) {
                                const items = res.errors.slice(0, 15).map(function(err) {
                                    return '<li>' + escapeHtmlImport(err) + '</li>';
                                }).join('');
                                html +=
                                    '<div class="alert alert-warning mb-0"><ul class="mb-0 small">' + items +
                                    '</ul></div>';
                            }
                            $msg.html(html);
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(
                            '<i class="icon-base ti tabler-upload me-1"></i> Upload');
                        let serverMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON
                            .message : xhr.statusText;
                        $msg.html('<div class="alert alert-danger mb-0">' + escapeHtmlImport(serverMsg ||
                            'Upload failed.') + '</div>');
                    }
                });
            });

            $(document).on('click', '.delete-student-btn', function(e) {
                e.preventDefault();
                if (!confirm('Delete this student? This removes their teacher links and may remove the account.')) {
                    return;
                }
                const id = $(this).data('id');
                $.post('{{ url("admin/student/delete") }}', {
                    _token: '{{ csrf_token() }}',
                    id: id
                }, function(res) {
                    if (res.status == 1) {
                        if (typeof processAjaxResponse === 'function') {
                            processAjaxResponse(res, 500);
                        } else {
                            window.location.reload();
                        }
                    } else {
                        alert(res.error || 'Failed to delete student');
                    }
                }, 'json');
            });
        });
    </script>
@endsection
