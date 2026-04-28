@extends('web.user.student.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-md-6">
                <div class="card" id="studentAddPageCard">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">Leave Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="ajax-msg mb-3"></div>
                        <form id="ajax-form" method="post" action="{{ url('user/student/leave/store') }}">
                        @csrf
                            <div class="row g-3">
                                <div class="col-6 ajax-field">
                                    <label class="form-label">Classroom <span class="text-danger"></span></label>
                                    <select name="classroom_id" id="leave_classroom_id" class="form-select">
                                        <option value="">Select Classroom</option>
                                        @foreach ($classrooms as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>

                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-6 ajax-field">
                                    <label class="form-label">Batch <span class="text-danger"></span></label>
                                    <select name="batch_id" id="leave_batch_id" class="form-select">
                                        <option value="">Select Batch</option>
                                        @foreach ($batches as $b)
                                            <option value="{{ $b->id }}" data-classroom-id="{{ $b->classroom_id }}">{{ $b->name }}</option>
                                        @endforeach
                                    </select>

                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-6 ajax-field">
                                    <label class="form-label">From Date <span class="text-danger"></span></label>
                                    <input type="date" class="form-control" name="from_date">
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-6 ajax-field">
                                    <label class="form-label">To Date <span class="text-danger"></span></label>
                                    <input type="date" class="form-control" name="to_date">
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-12 ajax-field">
                                    <label class="form-label">Reason <span class="text-danger"></span></label>
                                    <textarea name="reason" class="form-control" rows="3"></textarea>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                            </div>

                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary me-2 submit-button">Save</button>
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
    function leaveFilterBatches() {
        var cid = $('#leave_classroom_id').val();
        $('#leave_batch_id option').each(function() {
            var $o = $(this);
            if ($o.val() === '') {
                return;
            }
            if (String($o.data('classroom-id')) === String(cid)) {
                $o.show();
            } else {
                $o.hide();
                if ($o.is(':selected')) {
                    $('#leave_batch_id').val('');
                }
            }
        });
    }
    $(document).on('change', '#leave_classroom_id', leaveFilterBatches);
    $(function() {
        leaveFilterBatches();
    });

    $(document).on('submit', '#ajax-form', function(e) {
        e.preventDefault();
        clearAjaxErrors();

        const _this = $(this);

        _this.find('.submit-button').attr('disabled', 'disabled');
        _this.find('.submit-button').text('Saving...');

        const url = _this.attr('action');
        const data = _this.serializeArray();

        $.post(url, data, function(res) {
            _this.find('.submit-button').removeAttr('disabled');
            _this.find('.submit-button').text('Save');

            processAjaxResponse(res, 1000);
        }, 'json');
    })
</script>
@endsection
