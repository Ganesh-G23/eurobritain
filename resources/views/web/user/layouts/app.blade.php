<!doctype html>

<html lang="en" class="layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-skin="default"
    data-bs-theme="light" data-assets-path="{{ url('public/admin_theme/assets/') }}"
    data-template="horizontal-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>EliteGrade</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ url('public/admin_theme/assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/fonts/iconify-icons.css') }}" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/node-waves/node-waves.css') }}" />

    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/pickr/pickr-themes.css') }}" />

    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/css/demo.css') }}" />

    <!-- Vendors CSS -->

    <link rel="stylesheet"
        href="{{ url('public/admin_theme/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

    <!-- endbuild -->

    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/apex-charts/apex-charts.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/fonts/flag-icons.css') }}" />

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="{{ url('public/admin_theme/assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="{{ url('public/admin_theme/assets/js/config.js') }}"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper d-flex flex-column">
                @include('web.user.layouts.navigate') 
                    <div class="container-fluid flex-grow-1 pt-4 pb-4">
                        @yield('content')
                    </div>

                    <!-- Footer -->
                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl">
                            <div
                                class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                                

                            </div>
                        </div>
                    </footer>
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!--/ Content wrapper -->
            </div>

            <!--/ Layout container -->
        </div>
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>

    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>

    <!--/ Layout wrapper -->

    @php
        $portalUserSession = session('portal_user') ?? [];
        $suppressPasswordPopup = (int)($portalUserSession['is_password'] ?? 0) === 1;
        $shouldShowPasswordPopup = (int)(session('show_teacher_password_popup') ?? 0) === 1 && !$suppressPasswordPopup;
    @endphp

    <!-- Change Password Required Modal -->
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
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm new password">
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

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/theme.js  -->

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

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ url('public/admin_theme/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>

    <!-- Main JS -->

    <script src="{{ url('public/admin_theme/assets/js/main.js') }}"></script>
    <script src="{{ url('public/admin_theme/custom/custom.js') }}"></script>

    <!-- Page JS -->
    <script src="{{ url('public/admin_theme/assets/js/dashboards-crm.js') }}"></script>

    <script>
        function clearAjaxState(scope) {
            const $scope = scope ? $(scope) : $(document);
            $scope.find('.ajax-error').text('');
            $scope.find('.portal-pass-msg').html('');
        }
        function showAjaxErrors(errors, scope) {
            const $scope = scope ? $(scope) : $(document);
            Object.keys(errors || {}).forEach(function (key) {
                const field = $scope.find('[name="'+ key +'"]');
                if (field.length) {
                    field.closest('.ajax-field').find('.ajax-error').text(Array.isArray(errors[key]) ? errors[key][0] : errors[key]);
                }
            });
        }
        function showAjaxMessage(type, text, scope) {
            const $scope = scope ? $(scope) : $(document);
            $scope.find('.portal-pass-msg').html('<div class="alert alert-' + type + ' mb-2">' + text + '</div>');
        }

        $(function(){
            var shouldShowPopup = {{ $shouldShowPasswordPopup ? 'true' : 'false' }};
            if (shouldShowPopup) {
                var modal = new bootstrap.Modal(document.getElementById('portalChangePasswordModal'));
                modal.show();

                $('#portalChangePasswordModal').on('hidden.bs.modal', function () {
                    clearAjaxState('#portalChangePasswordModal');
                    $('#portal-change-pass-form')[0].reset();
                });

                $('#portal-pass-skip-btn').on('click', function(){
                    $.post('{{ url("user/skip_password_popup") }}', {
                        _token: '{{ csrf_token() }}'
                    }, function(res){
                        if (res.status == 1) {
                            modal.hide();
                        } else {
                            showAjaxMessage('danger', res.error || 'Failed to skip', '#portalChangePasswordModal');
                        }
                    }, 'json');
                });

                $('#portal-pass-save-btn').on('click', function(){
                    clearAjaxState('#portalChangePasswordModal');
                    var btn = $(this);
                    btn.attr('disabled', true).text('Please wait...');
                    var payload = $('#portal-change-pass-form').serializeArray();
                    payload.push({ name: '_token', value: '{{ csrf_token() }}' });
                    $.post('{{ url("user/update_password_popup") }}', payload, function(res){
                        btn.attr('disabled', false).text('Update Password');
                        if (res.status == 1) {
                            showAjaxMessage('success', res.msg || 'Password updated', '#portalChangePasswordModal');
                            setTimeout(function(){ modal.hide(); }, 700);
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
    @yield('scripts')
</body>
</html>
