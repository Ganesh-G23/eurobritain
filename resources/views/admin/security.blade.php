@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-md-12">
                <ul class="nav nav-pills flex-column flex-md-row mb-4">
                    <li class="nav-item">
                        <a class="nav-link" href="{{url('admin/profile')}}"><i class="icon-base ti tabler-user me-1"></i> Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:void(0);"><i class="icon-base ti tabler-lock me-1"></i> Change Password</a>
                    </li>
                </ul>
                <!-- Change Password -->
                <div class="card mb-4">
                    <h5 class="card-header">Change Password</h5>
                    <div class="card-body">
                        <form id="ajax-form" method="POST" action="{{url('admin/security/save_change_password')}}">
                            @csrf
                            <div class="col-12 ajax-msg"></div>
                            <div class="row">
                                <div class="mb-3 col-md-6 form-password-toggle ajax-field">
                                    <label class="form-label" for="currentPassword">Current Password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="current_password" class="form-control" name="current_password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password" />
                                        <span class="input-group-text cursor-pointer"><i
                                                class="icon-base ti tabler-eye-off"></i></span>
                                    </div>
                                    <span class="ajax-error"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-6 form-password-toggle ajax-field">
                                    <label class="form-label" for="newPassword">New Password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="password" class="form-control" name="password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password" />
                                        <span class="input-group-text cursor-pointer"><i
                                                class="icon-base ti tabler-eye-off"></i></span>
                                    </div>
                                    <span class="ajax-error"></span>
                                </div>
    
                                <div class="mb-3 col-md-6 form-password-toggle ajax-field">
                                    <label class="form-label" for="confirmPassword">Confirm New Password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="confirmPassword" class="form-control" name="password_confirmation"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password" />
                                        <span class="input-group-text cursor-pointer"><i
                                                class="icon-base ti tabler-eye-off"></i></span>
                                    </div>
                                    <span class="ajax-error"></span>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary me-2">Save changes</button>
                                    <button type="reset" class="btn btn-label-secondary">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!--/ Change Password -->
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
