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
                        @php
                            $filterTeacherId = (int) ($teacher_id ?? 0);
                        @endphp
                        <form method="GET" action="{{ url('admin/student') }}" class="mb-4" id="student-list-filters">
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label">Search</label>
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Name, email or phone" value="{{ $search ?? '' }}">
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label class="form-label">Teacher</label>
                                    <select name="teacher_id" id="student-list-filter-teacher" class="form-select">
                                        <option value="">All teachers</option>
                                        @foreach ($teachers ?? [] as $t)
                                            <option value="{{ $t->id }}"
                                                {{ $filterTeacherId === (int) $t->id ? 'selected' : '' }}>
                                                {{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label class="form-label">Classroom</label>
                                    <select name="classroom_id" id="student-list-filter-classroom" class="form-select"
                                        @if ($filterTeacherId <= 0) disabled title="Select a teacher first to filter by classroom" @endif>
                                        @if ($filterTeacherId <= 0)
                                            <option value="">Select a teacher first</option>
                                        @else
                                            <option value="">All classrooms</option>
                                            @foreach ($classrooms ?? [] as $c)
                                                <option value="{{ $c->id }}"
                                                    {{ (int) ($classroom_id ?? 0) === (int) $c->id ? 'selected' : '' }}>
                                                    {{ $c->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label class="form-label">Batch</label>
                                    <select name="batch_id" id="student-list-filter-batch" class="form-select"
                                        @if ($filterTeacherId <= 0) disabled title="Select a teacher first to filter by batch" @endif>
                                        @if ($filterTeacherId <= 0)
                                            <option value="">Select a teacher first</option>
                                        @else
                                            <option value="">All batches</option>
                                            @foreach ($batches ?? [] as $b)
                                                <option value="{{ $b->id }}"
                                                    {{ (int) ($batch_id ?? 0) === (int) $b->id ? 'selected' : '' }}>
                                                    {{ $b->name }}</option>
                                            @endforeach
                                        @endif
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
                                            $maps = $student->studentClassroomMaps;
                                            $teacherCount = $student->teachers->count();
                                            if ($maps->isNotEmpty()) {
                                                $classroomCount = $maps->pluck('classroom_id')->filter()->unique()->count();
                                                $batchCount = $maps->pluck('batch_id')->filter()->unique()->count();
                                                $dominantTeacherId = (int) $maps->groupBy('teacher_id')->map->count()->sortDesc()->keys()->first();
                                                $enrollmentTeacher = $student->teachers->firstWhere('id', $dominantTeacherId)
                                                    ?? \App\Models\PortalUser::where('role', 1)->whereKey($dominantTeacherId)->first();
                                                if (!$enrollmentTeacher) {
                                                    $enrollmentTeacher = $student->teachers->first();
                                                }
                                            } else {
                                                $classroomCount = $student->classroom_id ? 1 : 0;
                                                $batchCount = $student->batch_id ? 1 : 0;
                                                $enrollmentTeacher = $student->teachers->first();
                                            }
                                            $enrollmentTeachersList = $student->teachers
                                                ->map(static fn ($t) => ['id' => (int) $t->id, 'name' => (string) $t->name])
                                                ->values();
                                            $enrollmentMapsByTeacher = $student->teachers
                                                ->map(static function ($t) use ($maps) {
                                                    return [
                                                        'teacher_id' => (int) $t->id,
                                                        'maps' => $maps->where('teacher_id', $t->id)->map(static function ($m) {
                                                            return [
                                                                'classroom_id' => (int) $m->classroom_id,
                                                                'batch_id' => (int) $m->batch_id,
                                                            ];
                                                        })->values()->all(),
                                                    ];
                                                })
                                                ->values();
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
                                                    <button type="button"
                                                        class="btn btn-sm btn-icon btn-label-secondary open-student-enrollments-list"
                                                        data-student-id="{{ base64_encode($student->id) }}"
                                                        data-teacher-id="{{ $enrollmentTeacher?->id ?? 0 }}"
                                                        data-maps-by-teacher='@json($enrollmentMapsByTeacher)'
                                                        title="Classrooms &amp; batches">
                                                        <i class="icon-base ti tabler-school"></i>
                                                    </button>
                                                    <a href="{{ url('admin/student/edit?id=' . base64_encode($student->id)) }}"
                                                        class="btn btn-sm btn-icon btn-label-primary" title="Edit">
                                                        <i class="icon-base ti tabler-edit"></i>
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-sm btn-icon btn-label-danger delete-student-btn"
                                                        data-id="{{ base64_encode($student->id) }}" title="Delete">
                                                        <i class="icon-base ti tabler-trash"></i>
                                                    </button>
                                                    <a href="{{ url('admin/student/view?id=' . base64_encode($student->id)) }}"
                                                        class="btn btn-sm btn-icon btn-label-info" title="View">
                                                        <i class="icon-base ti tabler-eye"></i>
                                                    </a>

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

    <style>
        #studentListEnrollmentsModal .student-list-teacher-block {
            background: rgba(var(--bs-body-color-rgb), 0.03);
        }

        #studentListEnrollmentsModal .student-list-enrollment-row {
            border-bottom: 1px solid rgba(var(--bs-border-color-rgb), 0.65);
        }

        #studentListEnrollmentsModal .student-list-enrollment-row:last-child {
            border-bottom: 0;
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
        }

        #studentListEnrollmentsModal .student-list-enr-remove-btn {
            width: 2.375rem;
            height: 2.375rem;
            padding: 0;
            border: 0;
            color: var(--bs-danger);
            background: rgba(var(--bs-danger-rgb), 0.08);
            transition: color 0.15s ease, background-color 0.15s ease;
        }

        #studentListEnrollmentsModal .student-list-enr-remove-btn:hover,
        #studentListEnrollmentsModal .student-list-enr-remove-btn:focus {
            color: var(--bs-danger);
            background: rgba(var(--bs-danger-rgb), 0.16);
        }
    </style>

    <!-- Classrooms & batches (same behavior as teacher student list) -->
    <div class="modal fade" id="studentListEnrollmentsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Classrooms &amp; batches</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="ajax-msg mb-3"></div>
                    <form id="studentListEnrollmentsForm">
                        <input type="hidden" name="student_id" id="list_enr_student_id" value="">
                        <input type="hidden" name="return_student_list" value="1">
                        <input type="hidden" name="multi_teacher_enrollments" value="1">
                        <div id="list-enr-initial-teacher-ids"></div>
                        <p class="text-body-secondary small mb-3">Add one section per teacher. Select the teacher first — classrooms and batches shown are only for that teacher. At most one row per classroom per teacher. The first row of the first section sets the student’s primary classroom and batch for display.</p>
                        <div id="student-list-enrollment-blocks" class="mb-3"></div>
                        <button type="button" class="btn btn-sm btn-label-primary" id="student-list-add-teacher-block">
                            <i class="icon-base ti tabler-plus me-1"></i> Add teacher
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

    <template id="student-list-teacher-block-template">
        <div class="student-list-teacher-block border rounded-3 p-3 mb-3">
            <div class="row g-2 align-items-end mb-2">
                <div class="col">
                    <label class="form-label mb-1">Teacher</label>
                    <select class="form-select list-enr-block-teacher" name="enrollment_blocks[__IDX__][teacher_id]" autocomplete="off">
                        <option value="">Select teacher</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-1 invisible d-block" aria-hidden="true">Remove</label>
                    <button type="button"
                        class="btn btn-sm rounded-2 student-list-enr-remove-btn student-list-enr-remove-btn--section student-list-remove-teacher-block"
                        title="Remove this teacher">
                        <i class="icon-base ti tabler-trash"></i>
                    </button>
                </div>
            </div>
            <p class="form-text small mb-3">Classrooms and batches below are only for this teacher.</p>
            <div class="student-list-enrollment-rows mb-2"></div>
            <button type="button" class="btn btn-sm btn-label-primary student-list-add-enrollment-row-in-block">
                <i class="icon-base ti tabler-plus me-1"></i> Add row
            </button>
        </div>
    </template>

    <template id="student-list-enrollment-row-template">
        <div class="row g-2 align-items-end student-list-enrollment-row mb-3 pb-3">
            <div class="col-sm-5">
                <label class="form-label small mb-1">Classroom</label>
                <select class="form-select list-enr-classroom" data-name="enrollment_blocks[__IDX__][map_classroom_id][]">
                    <option value="">Choose classroom</option>
                </select>
            </div>
            <div class="col-sm-5">
                <label class="form-label small mb-1">Batch</label>
                <select class="form-select list-enr-batch" data-name="enrollment_blocks[__IDX__][map_batch_id][]" disabled>
                    <option value="">Choose classroom first</option>
                </select>
            </div>
            <div class="col-sm-2 col-auto ms-sm-auto">
                <label class="form-label small mb-1 invisible d-block" aria-hidden="true">Remove</label>
                <button type="button"
                    class="btn btn-sm rounded-2 student-list-enr-remove-btn student-list-remove-enr-row"
                    title="Remove row">
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
                        <strong>Optional extra enrollments</strong> (same teacher only; use a <strong>different</strong> classroom in each pair — only one batch allowed per classroom):
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
            $('#student-list-filter-teacher').on('change', function() {
                $('#student-list-filter-classroom').val('');
                $('#student-list-filter-batch').val('');
            });

            @include('admin.student.partials.list_enrollments_script')

            $('#studentListSaveEnrollmentsBtn').on('click', function() {
                clearAjaxErrors('#studentListEnrollmentsModal');
                const clientCheck = typeof validateListEnrollmentFormBeforeSave === 'function'
                    ? validateListEnrollmentFormBeforeSave()
                    : { valid: true, error: '' };
                if (!clientCheck.valid) {
                    processAjaxResponse({ status: 0, error: clientCheck.error }, 0, '#studentListEnrollmentsModal');
                    if (clientCheck.error) {
                        alert(clientCheck.error);
                    }
                    return;
                }
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
                        if (res.error) {
                            alert(res.error);
                        }
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
