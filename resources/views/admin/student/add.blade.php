@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-md-12">
                <div class="card" id="studentAddPageCard">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">Add student</h5>
                        <a href="{{ url('admin/student') }}" class="btn btn-sm btn-label-secondary">
                            <i class="icon-base ti tabler-arrow-left me-1"></i> Back to students
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="ajax-msg mb-3"></div>

                        <p class="text-body-secondary small mb-3">Choose the teacher this student belongs to, then enter
                            student and optional parent details. Classrooms and batches can be set from that teacher’s page
                            (school icon in the student list) after saving.</p>

                        <form id="adminStudentAddForm">
                            <input type="hidden" name="return_student_list" value="1">

                            <div class="row g-3">
                                <div class="col-12 ajax-field">
                                    <label class="form-label">Teacher <span class="text-danger">*</span></label>
                                    <select name="teacher_id" id="add_student_teacher_id" class="form-select" required>
                                        <option value="">Select teacher</option>
                                        @foreach ($teachers ?? [] as $t)
                                            <option value="{{ $t->id }}"
                                                data-view-url="{{ url('admin/teacher/view?id=' . base64_encode($t->id)) }}">
                                                {{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                    
                                    <span class="ajax-error text-danger small"></span>
                                </div>

                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Student Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" required>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" required>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="phone" required>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Parent Name</label>
                                    <input type="text" class="form-control" name="parent_name">
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Parent Email</label>
                                    <input type="email" class="form-control" name="parent_email"
                                        placeholder="parent@example.com">
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-md-4 ajax-field">
                                    <label class="form-label">Parent Phone</label>
                                    <input type="text" class="form-control" name="parent_phone"
                                        placeholder="e.g., 9876543210">
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                            </div>

                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary" id="adminStudentAddSaveBtn">Add student</button>
                                <a href="#" class="btn btn-label-secondary disabled" id="openTeacherPageLink"
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
            const $form = $('#adminStudentAddForm');
            const $teacher = $('#add_student_teacher_id');
            const $openTeacher = $('#openTeacherPageLink');

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
                clearAjaxErrors('#studentAddPageCard');
                const formData = $form.serializeArray();
                formData.push({
                    name: '_token',
                    value: "{{ csrf_token() }}"
                });
                const savedTeacherId = $teacher.val();
                $.post("{{ url('admin/teacher/save_student') }}", formData, function(res) {
                    if (res.status == 1) {
                        el.reset();
                        $form.find('[name="return_student_list"]').val('1');
                        if (savedTeacherId) {
                            $teacher.val(savedTeacherId);
                        }
                        syncTeacherLink();
                        clearAjaxErrors('#studentAddPageCard');
                        processAjaxResponse(res, 500, '#studentAddPageCard');
                    } else {
                        processAjaxResponse(res, 0, '#studentAddPageCard');
                    }
                }, 'json');
            });
        });
    </script>
@endsection
