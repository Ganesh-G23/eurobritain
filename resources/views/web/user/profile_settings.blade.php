@extends('web.user.layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
    <div class="row g-6">
        <div class="col-md-12">
            <ul class="nav nav-pills flex-column flex-md-row mb-4">
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('user/profile') }}"><i class="icon-base ti tabler-user me-1"></i>
                        My Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('user/security') }}"><i class="icon-base ti tabler-lock me-1"></i>
                        Change Password</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="javascript:void(0);"><i class="icon-base ti tabler-settings me-1"></i>
                        Settings</a>
                </li>
            </ul>
            <div class="card mb-4">
                <h5 class="card-header">Settings</h5>
                <div class="card-body">
                    <h6 class="mb-2">Absent (&quot;A&quot;) handling must be:</h6>
                    <form id="teacher-settings-form" method="POST" action="{{ url('user/profile/save_teacher_settings') }}">
                        @csrf
                        <div class="col-12 teacher-settings-msg"></div>
                        <div class="mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="count_setting" id="count_setting_1"
                                    value="1"
                                    {{ (int) ($teacher_setting->count_setting ?? 0) === \App\Models\TeacherSetting::COUNT_AS_ZERO ? 'checked' : '' }}>
                                <label class="form-check-label" for="count_setting_1">
                                    <span class="text-body-secondary me-1">i.</span> Count as 0
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="count_setting" id="count_setting_2"
                                    value="2"
                                    {{ (int) ($teacher_setting->count_setting ?? 0) === \App\Models\TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE ? 'checked' : '' }}>
                                <label class="form-check-label" for="count_setting_2">
                                    <span class="text-body-secondary me-1">ii.</span> Exclude from overall percentage
                                </label>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-2">Leaderboard visibility</h6>
                        <p class="text-body-secondary small mb-3">
                            When turned off, students in your classes will not see the leaderboard menu or rankings.
                            You can still use the leaderboard in your teacher panel.
                        </p>
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="leaderboard_visible" value="0">
                            <input class="form-check-input" type="checkbox" name="leaderboard_visible" id="leaderboard_visible"
                                value="1"
                                {{ ($teacher_setting->leaderboard_visible ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="leaderboard_visible">
                                Show leaderboard to students
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary teacher-settings-submit mt-4">Save settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
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
