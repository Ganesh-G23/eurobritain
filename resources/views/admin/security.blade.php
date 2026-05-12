@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-md-12">
                <ul class="nav nav-pills flex-column flex-md-row mb-4">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('admin/profile') }}"><i class="icon-base ti tabler-user me-1"></i> Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:void(0);"><i class="icon-base ti tabler-lock me-1"></i> Security</a>
                    </li>
                </ul>

                @if ($details->force_password_change ?? false)
                    <div class="alert alert-warning mb-4" role="alert">
                        You must change your temporary password before using the rest of the admin panel.
                    </div>
                @endif

                <!-- Change Password -->
                <div class="card mb-4">
                    <h5 class="card-header">Change password</h5>
                    <div class="card-body">
                        <form id="ajax-form" method="POST" action="{{ url('admin/security/save_change_password') }}">
                            @csrf
                            <div class="col-12 ajax-msg"></div>
                            <div class="row">
                                <div class="mb-3 col-md-6 form-password-toggle ajax-field">
                                    <label class="form-label" for="currentPassword">Current password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="current_password" class="form-control" name="current_password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password" autocomplete="current-password" />
                                        <span class="input-group-text cursor-pointer"><i
                                                class="icon-base ti tabler-eye-off"></i></span>
                                    </div>
                                    <span class="ajax-error"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-6 form-password-toggle ajax-field">
                                    <label class="form-label" for="newPassword">New password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="password" class="form-control" name="password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password" autocomplete="new-password" />
                                        <span class="input-group-text cursor-pointer"><i
                                                class="icon-base ti tabler-eye-off"></i></span>
                                    </div>
                                    <span class="ajax-error"></span>
                                </div>

                                <div class="mb-3 col-md-6 form-password-toggle ajax-field">
                                    <label class="form-label" for="confirmPassword">Confirm new password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="confirmPassword" class="form-control" name="password_confirmation"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            aria-describedby="password" autocomplete="new-password" />
                                        <span class="input-group-text cursor-pointer"><i
                                                class="icon-base ti tabler-eye-off"></i></span>
                                    </div>
                                    <span class="ajax-error"></span>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary me-2 submit-button">Save</button>
                                    <button type="reset" class="btn btn-label-secondary">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <h5 class="card-header">Email sign-in code (2FA)</h5>
                    <div class="card-body">
                        @php
                            $canEnable2fa = !($details->force_password_change ?? false) && filled($details->recovery_email ?? null);
                            $twoFaInteract = $canEnable2fa || ($details->email_two_factor_enabled ?? false);
                        @endphp
                        @if (! $canEnable2fa && !($details->email_two_factor_enabled ?? false))
                            <div class="alert alert-info py-2 mb-3 small">
                                @if ($details->force_password_change ?? false)
                                    Change your temporary password above first.
                                @else
                                    Add a <strong>recovery email</strong> on <a href="{{ url('admin/profile') }}">My Profile</a> before you can enable email codes at sign-in.
                                @endif
                                Sign-in codes are always sent to your <strong>sign-in email</strong>.
                            </div>
                        @endif
                        <form id="two-factor-form" method="POST" action="{{ url('admin/profile/email-two-factor') }}">
                            @csrf
                            <div class="col-12 ajax-msg-2fa mb-3"></div>
                            <input type="hidden" name="email_two_factor_enabled" value="0">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="email_two_factor_enabled"
                                    name="email_two_factor_enabled" value="1"
                                    {{ ($details->email_two_factor_enabled ?? false) ? 'checked' : '' }}
                                    {{ $twoFaInteract ? '' : 'disabled' }}>
                                <label class="form-check-label" for="email_two_factor_enabled">Require a 6-digit email code when I sign in to the admin panel</label>
                            </div>
                            <button type="submit" class="btn btn-primary submit-button-2fa" {{ $twoFaInteract ? '' : 'disabled' }}>Save security preference</button>
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
            const saveBtn = _this.find('.submit-button');
            saveBtn.prop('disabled', true).text('Saving...');

            const url = _this.attr('action');
            const data = _this.serializeArray();

            $.post(url, data, function(res) {
                saveBtn.prop('disabled', false).text('Save');
                processAjaxResponse(res, 1000);
            }, 'json');
        });

        $(document).on('submit', '#two-factor-form', function(e) {
            e.preventDefault();
            const form = $(this);
            const msg = $('.ajax-msg-2fa');
            const btn = form.find('.submit-button-2fa');
            msg.html('');
            btn.prop('disabled', true).text('Saving...');
            $.post(form.attr('action'), form.serializeArray(), function(res) {
                btn.prop('disabled', false).text('Save security preference');
                if (res.status == 1) {
                    msg.html('<div class="alert alert-success">' + (res.msg || 'Saved') + '</div>');
                    if (res.redirect_url) {
                        setTimeout(function() {
                            window.location.href = res.redirect_url;
                        }, 600);
                    }
                } else if (res.error) {
                    msg.html('<div class="alert alert-danger">' + res.error + '</div>');
                } else if (res.error_array) {
                    msg.html('<div class="alert alert-danger">Please check the form.</div>');
                }
            }, 'json').fail(function() {
                btn.prop('disabled', false).text('Save security preference');
                msg.html('<div class="alert alert-danger">Request failed.</div>');
            });
        });
    </script>
@endsection
