@extends('web.user.layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
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
                                <label for="email" class="form-label">Recovery Email</label>
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

            @if ((int) (session('portal_user')['role'] ?? 0) === 1 && ($teacher_setting ?? null))
                <div class="card mb-4">
                    <h5 class="card-header">Settings</h5>
                    <div class="card-body">
                        <h6 class="mb-2">Absent (&quot;A&quot;) handling must be:</h6>
                        <!-- <p class="text-body-secondary small mb-3">Teacher-controlled setting:</p> -->
                        <form id="teacher-settings-form" method="POST"
                            action="{{ url('user/profile/save_teacher_settings') }}">
                            @csrf
                            <div class="col-12 teacher-settings-msg"></div>
                            <div class="mb-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="count_setting"
                                        id="count_setting_1" value="1"
                                        {{ (int) ($teacher_setting->count_setting ?? 0) === \App\Models\TeacherSetting::COUNT_AS_ZERO ? 'checked' : '' }}>
                                    <label class="form-check-label" for="count_setting_1">
                                        <span class="text-body-secondary me-1">i.</span> Count as 0
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="count_setting"
                                        id="count_setting_2" value="2"
                                        {{ (int) ($teacher_setting->count_setting ?? 0) === \App\Models\TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE ? 'checked' : '' }}>
                                    <label class="form-check-label" for="count_setting_2">
                                        <span class="text-body-secondary me-1">ii.</span> Exclude from overall
                                        percentage
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary teacher-settings-submit">Save settings</button>
                        </form>
                    </div>
                </div>
            @endif
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

    $(document).on('submit', '#teacher-settings-form', function(e) {
        e.preventDefault();
        const _this = $(this);
        _this.find('.teacher-settings-msg').html('');
        const btn = _this.find('.teacher-settings-submit');
        btn.prop('disabled', true).text('Saving...');
        $.post(_this.attr('action'), _this.serializeArray(), function(res) {
            btn.prop('disabled', false).text('Save settings');
            if (res.status == 1) {
                _this.find('.teacher-settings-msg').html(
                    '<div class="alert alert-success mb-3">' + (res.msg || 'Saved') + '</div>');
            } else if (res.error_array) {
                var msg = '';
                try {
                    Object.keys(res.error_array).forEach(function(k) {
                        var v = res.error_array[k];
                        msg += (Array.isArray(v) ? v[0] : v) + ' ';
                    });
                } catch (err) {}
                _this.find('.teacher-settings-msg').html(
                    '<div class="alert alert-danger mb-3">' + (msg || 'Validation failed') + '</div>');
            } else if (res.error) {
                _this.find('.teacher-settings-msg').html(
                    '<div class="alert alert-danger mb-3">' + res.error + '</div>');
            }
        }, 'json');
    });
</script>
@endsection

