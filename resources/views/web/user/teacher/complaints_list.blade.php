@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
        <div class="row g-6">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Add Complaint</h5>
                    </div>
                    <div class="card-body">
                        <div class="ajax-msg mb-3"></div>
                        <form id="ajax-form" method="post" action="{{ url('user/teacher/complaints/store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12 ajax-field">
                                    <label class="form-label">Student <span class="text-danger">*</span></label>
                                    <select name="student_id" class="form-select"
                                        {{ empty($students) || count($students) === 0 ? 'disabled' : '' }}>
                                        <option value="">Select Student</option>
                                        @foreach ($students ?? [] as $student)
                                            <option value="{{ $student->id }}">{{ $student->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-12 ajax-field">
                                    <label class="form-label">Complaint Remarks <span class="text-danger">*</span></label>
                                    <textarea name="remark" class="form-control" rows="4" placeholder="Enter complaint remarks"></textarea>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                            </div>
                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary submit-button"
                                    {{ empty($students) || count($students) === 0 ? 'disabled' : '' }}>
                                    Submit
                                </button>
                            </div>
                            @if (empty($students) || count($students) === 0)
                                <p class="text-warning mt-2 mb-0 small">
                                    No students linked with this teacher yet.
                                </p>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).on('submit', '#ajax-form', function(e) {
        e.preventDefault();
        clearAjaxErrors();

        const _this = $(this);
        _this.find('.submit-button').attr('disabled', 'disabled');
        _this.find('.submit-button').text('Saving...');

        $.post(_this.attr('action'), _this.serializeArray(), function(res) {
            _this.find('.submit-button').removeAttr('disabled');
            _this.find('.submit-button').text('Submit');
            processAjaxResponse(res, 1000);
        }, 'json');
    });
</script>
@endsection
