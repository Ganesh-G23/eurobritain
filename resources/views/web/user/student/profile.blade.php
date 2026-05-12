@extends('web.user.student.layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 pt-2 pb-2">
    <div class="row g-6">
        <div class="col-md-12">
            <ul class="nav nav-pills flex-column flex-md-row mb-4">
                <li class="nav-item">
                    <a class="nav-link active" href="javascript:void(0);"><i class="icon-base ti tabler-user me-1"></i> My
                        Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('user/security') }}"><i class="icon-base ti tabler-lock me-1"></i>
                        Change Password</a>
                </li>
            </ul>
            <div class="card mb-4">
                <h5 class="card-header">Profile Details</h5>
                <div class="card-body">
                    <form id="ajax-form" method="POST" action="{{ url('user/profile/save_profile') }}">
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
                            <div class="mb-3 col-md-6 ajax-field">
                                <label for="recovery_email" class="form-label">Recovery Email</label>
                                <input class="form-control" type="text" id="recovery_email" name="recovery_email"
                                    value="{{ $details->recovery_email }}" placeholder="john.doe@example.com" />
                                <span class="ajax-error"></span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2 submit-button">Save changes</button>
                            <button type="reset" class="btn btn-label-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <h5 class="card-header">Email sign-in verification</h5>
                <div class="card-body">
                    <p class="text-muted small mb-3">When enabled, we email you a one-time code each time you sign in with your password.</p>
                    <form id="two-factor-form" method="POST" action="{{ url('user/profile/email-two-factor') }}">
                        @csrf
                        <div class="col-12 two-factor-ajax-msg"></div>
                        <input type="hidden" name="email_two_factor_enabled" value="0">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="email_two_factor_enabled"
                                name="email_two_factor_enabled" value="1"
                                {{ $details->email_two_factor_enabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_two_factor_enabled">Require email verification code when I sign in</label>
                        </div>
                        <div class="mb-3">
                            <label for="two_factor_current_password" class="form-label">Current password (required to change this setting)</label>
                            <input type="password" class="form-control" id="two_factor_current_password" name="current_password"
                                autocomplete="current-password" required>
                            <span class="text-danger small two-factor-field-error" data-for="current_password"></span>
                        </div>
                        <button type="submit" class="btn btn-primary two-factor-submit">Save</button>
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

        const url = _this.attr('action');
        const data = _this.serializeArray();

        $.post(url, data, function(res) {
            _this.find('.submit-button').removeAttr('disabled');
            _this.find('.submit-button').text('Save changes');
            processAjaxResponse(res, 1000);
        }, 'json');
    });

    $(document).on('submit', '#two-factor-form', function(e) {
        e.preventDefault();
        const _this = $(this);
        _this.find('.two-factor-ajax-msg').html('');
        _this.find('.two-factor-field-error').text('');
        _this.find('.two-factor-submit').prop('disabled', true).text('Saving...');
        $.post(_this.attr('action'), _this.serialize(), function(res) {
            _this.find('.two-factor-submit').prop('disabled', false).text('Save');
            if (res.status == 1) {
                _this.find('.two-factor-ajax-msg').html('<div class="alert alert-success">' + (res.msg || 'Saved') + '</div>');
                return;
            }
            if (res.error_array) {
                Object.keys(res.error_array).forEach(function(key) {
                    _this.find('.two-factor-field-error[data-for="' + key + '"]').text(res.error_array[key]);
                });
            }
            if (res.error) {
                _this.find('.two-factor-ajax-msg').html('<div class="alert alert-danger">' + res.error + '</div>');
            }
        }, 'json');
    });
</script>
@endsection
