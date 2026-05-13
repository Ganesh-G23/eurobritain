@extends('web.user.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-12 col-xl-5">
                <div class="card" id="parentLeaveFormCard">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Request leave</h5>
                            @if (!empty($student?->name))
                                <p class="mb-0 text-body-secondary small">Leave request for
                                    <span class="fw-medium text-heading">{{ $student->name }}</span>
                                </p>
                            @endif
                        </div>
                        <a href="{{ url('user/parent/leave') }}" class="btn btn-sm btn-label-primary">View leave list</a>
                    </div>
                    <div class="card-body">
                        <div class="ajax-msg mb-3"></div>
                        <form id="parent-leave-ajax-form" method="post"
                            action="{{ url('user/parent/leave/store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12 ajax-field">
                                    <label class="form-label">Classroom <span class="text-danger">*</span></label>
                                    <select name="classroom_id" id="leave_classroom_id" class="form-select">
                                        <option value="">Select classroom</option>
                                        @foreach ($classrooms ?? [] as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-12 ajax-field">
                                    <label class="form-label">Batch <span class="text-danger">*</span></label>
                                    <select name="batch_id" id="leave_batch_id" class="form-select">
                                        <option value="">Select batch</option>
                                        @foreach ($batches ?? [] as $b)
                                            <option value="{{ $b->id }}"
                                                data-classroom-id="{{ $b->classroom_id }}">{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-12 col-md-6 ajax-field">
                                    <label class="form-label">From date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="from_date">
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-12 col-md-6 ajax-field">
                                    <label class="form-label">To date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="to_date">
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="col-12 ajax-field">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3"></textarea>
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                            </div>
                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary submit-button">Submit request</button>
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

        $(document).on('submit', '#parent-leave-ajax-form', function(e) {
            e.preventDefault();
            clearAjaxErrors();

            const _this = $(this);

            _this.find('.submit-button').attr('disabled', 'disabled');
            _this.find('.submit-button').text('Saving...');

            const url = _this.attr('action');
            const data = _this.serializeArray();

            $.post(url, data, function(res) {
                _this.find('.submit-button').removeAttr('disabled');
                _this.find('.submit-button').text('Submit request');

                processAjaxResponse(res, 1000);
            }, 'json');
        });
    </script>
@endsection
