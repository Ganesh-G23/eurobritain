@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-md-12">
                <div class="card" id="studentEditPageCard">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">Edit student</h5>
                        <a href="{{ url('admin/student') }}" class="btn btn-sm btn-label-secondary">
                            <i class="icon-base ti tabler-arrow-left me-1"></i> Back to students
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="ajax-msg mb-3"></div>

                        <p class="text-body-secondary small mb-3">Update student details and the teacher link used for this
                            save. Classrooms and batches can be managed from the student list (school icon) or the teacher
                            page.</p>

                        <form id="adminStudentEditForm">
                            <input type="hidden" name="id" value="{{ base64_encode($student->id) }}">
                            <input type="hidden" name="return_student_list" value="1">

                            <div class="row g-3">
                                <div class="col-12 ajax-field">
                                    <label class="form-label">Teacher <span class="text-danger">*</span></label>
                                    <select name="teacher_id" id="edit_student_teacher_id" class="form-select" required>
                                        <option value="">Select teacher</option>
                                        @foreach ($teachers ?? [] as $t)
                                            <option value="{{ $t->id }}"
                                                {{ (int) ($default_teacher_id ?? 0) === (int) $t->id ? 'selected' : '' }}
                                                data-view-url="{{ url('admin/teacher/view?id=' . base64_encode($t->id)) }}">
                                                {{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="ajax-error text-danger small"></span>
                                </div>

                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Student Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" value="{{ $student->name }}"
                                        required>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email"
                                        value="{{ $student->email }}" required>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="phone"
                                        value="{{ $student->phone }}" required>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Parent Name</label>
                                    <input type="text" class="form-control" name="parent_name"
                                        value="{{ $parent_row->name ?? '' }}">
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Parent Email</label>
                                    <input type="email" class="form-control" name="parent_email"
                                        value="{{ $parent_row->email ?? '' }}" placeholder="parent@example.com">
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Parent Phone</label>
                                    <input type="text" class="form-control" name="parent_phone"
                                        value="{{ $parent_row->phone ?? '' }}" placeholder="e.g., 9876543210">
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                            </div>

                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">Save changes</button>
                                <a href="#" class="btn btn-label-secondary disabled" id="openTeacherPageLinkEdit"
                                    aria-disabled="true">Open teacher page</a>
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
        $(document).ready(function() {
            const $form = $('#adminStudentEditForm');
            const $teacher = $('#edit_student_teacher_id');
            const $openTeacher = $('#openTeacherPageLinkEdit');

            function syncTeacherLink() {
                const $opt = $teacher.find('option:selected');
                const u = $opt.data('view-url');
                if (u) {
                    $openTeacher.attr('href', u).attr('aria-disabled', 'false').removeClass('disabled');
                } else {
                    $openTeacher.attr('href', '#').attr('aria-disabled', 'true').addClass('disabled');
                }
            }
            $teacher.on('change', syncTeacherLink);
            syncTeacherLink();

            $openTeacher.on('click', function(e) {
                const h = $(this).attr('href');
                if (!h || h === '#' || $(this).hasClass('disabled')) {
                    e.preventDefault();
                }
            });

            $form.on('submit', function(e) {
                e.preventDefault();
                const el = this;
                if (typeof el.reportValidity === 'function' && !el.reportValidity()) {
                    return;
                }
                clearAjaxErrors('#studentEditPageCard');
                const formData = $form.serializeArray();
                formData.push({
                    name: '_token',
                    value: "{{ csrf_token() }}"
                });
                $.post("{{ url('admin/teacher/save_student') }}", formData, function(res) {
                    if (res.status == 1) {
                        clearAjaxErrors('#studentEditPageCard');
                        processAjaxResponse(res, 500, '#studentEditPageCard');
                    } else {
                        processAjaxResponse(res, 0, '#studentEditPageCard');
                    }
                }, 'json');
            });
        });
    </script>
@endsection
