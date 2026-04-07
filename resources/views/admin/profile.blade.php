@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-md-12">
                <ul class="nav nav-pills flex-column flex-md-row mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:void(0);"><i class="icon-base ti tabler-user me-1"></i> My
                            Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('admin/security') }}"><i class="icon-base ti tabler-lock me-1"></i>
                            Change Password</a>
                    </li>
                </ul>
                <div class="card mb-4">
                    <h5 class="card-header">Profile Details</h5>
                    <div class="card-body">
                        <form id="ajax-form" method="POST" action="{{ url('admin/profile/save_profile') }}">
                            @csrf
                            <input type="hidden" name="id" value="{{ $details->id }}" />
                            <div class="col-12 ajax-msg"></div>
                            <div class="row">
                                <div class="mb-3 col-md-6 ajax-field">
                                    <label for="name" class="form-label">Name</label>
                                    <input class="form-control" type="text" id="name" name="name"
                                        value="{{ $details->name }}" autofocus />
                                    <span class="ajax-error"></span>
                                </div>
                                <div class="mb-3 col-md-6 ajax-field">
                                    <label for="email" class="form-label">Email</label>
                                    <input class="form-control" type="text" id="email" name="email"
                                        value="{{ $details->email }}" placeholder="john.doe@example.com" />
                                    <span class="ajax-error"></span>
                                </div>
                                <div class="mb-3 col-md-6 ajax-field">
                                    <label class="form-label" for="phoneNumber">Phone Number</label>
                                    <input type="text" id="phoneNumber" name="phone" class="form-control"
                                            placeholder="202 555 0111" value="{{ $details->phone }}" />
                                    <span class="ajax-error"></span>
                                </div>

                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary me-2">Save changes</button>
                                <button type="reset" class="btn btn-label-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                    <!-- /Account -->
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
