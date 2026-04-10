<!doctype html>
<html lang="en" class="layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-skin="default"
    data-bs-theme="light" data-assets-path="{{ url('public/admin_theme/assets/') }}"
    data-template="horizontal-menu-template">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Select Teacher - EliteGrade</title>

    <link rel="icon" type="image/x-icon" href="{{ url('public/admin_theme/assets/img/favicon/favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/fonts/iconify-icons.css') }}">
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/node-waves/node-waves.css') }}">
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/pickr/pickr-themes.css') }}">
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/css/core.css') }}">
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/css/demo.css') }}">
    <link rel="stylesheet"
        href="{{ url('public/admin_theme/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}">

    <style>
        .select-teacher-sheet {
            max-width: 36rem;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        .select-teacher-greet {
            color: var(--bs-heading-color);
            font-weight: 700;
            font-size: 1.15rem;
            margin-bottom: 6px;
        }

        .select-teacher-page-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--bs-heading-color);
        }

        .select-teacher-subtext {
            color: var(--bs-secondary-color);
            margin-bottom: 14px;
        }

        .card-teacher {
            border: 1px solid var(--bs-border-color);
            border-radius: 10px;
            background: var(--bs-card-bg);
            padding: 14px 16px;
            margin-bottom: 12px;
        }

        .card-teacher:last-child {
            margin-bottom: 0;
        }

        .card-teacher .teacher-row-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: var(--bs-heading-color);
        }

        .card-teacher .teacher-row-sub {
            color: var(--bs-secondary-color);
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        .card-teacher .teacher-row-sub + .teacher-row-sub {
            margin-top: 4px;
        }

        .btn-access {
            white-space: nowrap;
        }
    </style>

    <script src="{{ url('public/admin_theme/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/js/template-customizer.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/js/config.js') }}"></script>
</head>

<body>
    <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper d-flex flex-column">
                    <nav class="layout-navbar navbar navbar-expand-xl align-items-center border-bottom" id="layout-navbar">
                        <div class="container-xxl d-flex align-items-center justify-content-between w-100 py-2">
                            @php
                                $pu = session('portal_user') ?? [];
                                $initial = strtoupper(substr(trim($pu['name'] ?? 'U'), 0, 1));
                            @endphp
                            <a href="{{ url('user/select-teacher') }}" class="d-flex align-items-center">
                                <img src="{{ url('public/admin_theme/assets/img/logo.png') }}" alt="EliteGrade"
                                    class="img-fluid" style="max-height: 28px;">
                            </a>
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="avatar avatar-online">
                                        <span class="avatar-initial rounded-circle bg-label-primary">{{ $initial }}</span>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ url('user/logout') }}">Logout</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </nav>

                    <div class="container-xxl flex-grow-1 container-p-y px-3 px-sm-4">
                        <div class="select-teacher-sheet">
                            <div class="card">
                                <div class="card-body">
                                    @php $student = session('portal_user') ?? []; @endphp
                                    <div class="select-teacher-greet">Hello {{ $student['name'] ?? 'Student' }}</div>
                                    <div class="select-teacher-page-title">Select Your Teacher</div>
                                    <div class="select-teacher-subtext">You are a part of the following teachers. Choose one to
                                        access now.</div>

                                    @if (($teachers ?? collect())->count() === 0)
                                        <div class="card-teacher">
                                            <div class="teacher-row-title">No teachers assigned yet</div>
                                            <div class="teacher-row-sub">Please contact your institute or teacher to be assigned.</div>
                                        </div>
                                    @else
                                        @foreach ($teachers as $t)
                                            <div class="card-teacher">
                                                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                                                    <div class="flex-grow-1">
                                                        <div class="teacher-row-title">{{ $t->teacher_name }}</div>
                                                        <div class="teacher-row-sub">Email: {{ $t->teacher_email }}</div>
                                                        @if ($t->teacher_phone)
                                                            <div class="teacher-row-sub">Phone: {{ $t->teacher_phone }}</div>
                                                        @endif
                                                        @if ($t->batch_name)
                                                            <div class="teacher-row-sub">Batch: {{ $t->batch_name }}</div>
                                                        @endif
                                                        @if ($t->classroom_name)
                                                            <div class="teacher-row-sub">Classroom: {{ $t->classroom_name }}</div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <form method="post" action="{{ url('user/select-teacher/access') }}">
                                                            @csrf
                                                            <input type="hidden" name="teacher_id"
                                                                value="{{ (int) $t->teacher_id }}">
                                                            <button type="submit" class="btn btn-primary btn-access">
                                                                <span style="font-size: 12px;">&#10132;</span> Access
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="layout-overlay layout-menu-toggle"></div>
    <div class="drag-target"></div>

    @php
        $portalUserSession = session('portal_user') ?? [];
        $suppressPasswordPopup = (int) ($portalUserSession['is_password'] ?? 0) === 1;
        $shouldShowPasswordPopup =
            (int) (session('show_teacher_password_popup') ?? 0) === 1 && ! $suppressPasswordPopup;
    @endphp

    <div class="modal fade" id="portalChangePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Your Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        For your account security, please set a new password.
                    </div>
                    <div class="mb-3 portal-pass-msg"></div>
                    <form id="portal-change-pass-form">
                        @csrf
                        <div class="mb-3 ajax-field">
                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Enter new password">
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-0 ajax-field">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control"
                                placeholder="Confirm new password">
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-0 ajax-field">
                            <label for="recovery_email" class="form-label">Recovery Email<span
                                    class="text-danger">*</span></label>
                            <input class="form-control" type="text" id="recovery_email" name="recovery_email"
                                placeholder="john.doe@example.com">
                            <span class="ajax-error text-danger small"></span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" id="portal-pass-skip-btn">Skip for now</button>
                    <button type="button" class="btn btn-primary" id="portal-pass-save-btn">Update Password</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ url('public/admin_theme/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/@algolia/autocomplete-js.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/pickr/pickr.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/js/menu.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/js/main.js') }}"></script>
    <script src="{{ url('public/admin_theme/custom/custom.js') }}"></script>

    <script>
        function clearAjaxState(scope) {
            const $scope = scope ? $(scope) : $(document);
            $scope.find('.ajax-error').text('');
            $scope.find('.portal-pass-msg').html('');
        }

        function showAjaxErrors(errors, scope) {
            const $scope = scope ? $(scope) : $(document);
            Object.keys(errors || {}).forEach(function(key) {
                const field = $scope.find('[name="' + key + '"]');
                if (field.length) {
                    field.closest('.ajax-field').find('.ajax-error').text(Array.isArray(errors[key]) ? errors[key][
                        0
                    ] : errors[key]);
                }
            });
        }

        function showAjaxMessage(type, text, scope) {
            const $scope = scope ? $(scope) : $(document);
            $scope.find('.portal-pass-msg').html('<div class="alert alert-' + type + ' mb-2">' + text + '</div>');
        }

        $(function() {
            var shouldShowPopup = {{ $shouldShowPasswordPopup ? 'true' : 'false' }};
            if (shouldShowPopup) {
                var modal = new bootstrap.Modal(document.getElementById('portalChangePasswordModal'));
                modal.show();

                $('#portalChangePasswordModal').on('hidden.bs.modal', function() {
                    clearAjaxState('#portalChangePasswordModal');
                    $('#portal-change-pass-form')[0].reset();
                });

                $('#portal-pass-skip-btn').on('click', function() {
                    $.post('{{ url("user/skip_password_popup") }}', {
                        _token: '{{ csrf_token() }}'
                    }, function(res) {
                        if (res.status == 1) {
                            modal.hide();
                        } else {
                            showAjaxMessage('danger', res.error || 'Failed to skip',
                                '#portalChangePasswordModal');
                        }
                    }, 'json');
                });

                $('#portal-pass-save-btn').on('click', function() {
                    clearAjaxState('#portalChangePasswordModal');
                    var btn = $(this);
                    btn.attr('disabled', true).text('Please wait...');
                    var payload = $('#portal-change-pass-form').serializeArray();
                    payload.push({
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    });
                    $.post('{{ url("user/update_password_popup") }}', payload, function(res) {
                        btn.attr('disabled', false).text('Update Password');
                        if (res.status == 1) {
                            showAjaxMessage('success', res.msg || 'Password updated',
                                '#portalChangePasswordModal');
                            setTimeout(function() {
                                modal.hide();
                            }, 700);
                        } else if (res.error_array) {
                            showAjaxErrors(res.error_array, '#portalChangePasswordModal');
                        } else if (res.error) {
                            showAjaxMessage('danger', res.error, '#portalChangePasswordModal');
                        }
                    }, 'json');
                });
            }
        });
    </script>
</body>

</html>
