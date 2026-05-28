<!doctype html>

<html lang="en" class="layout-wide customizer-hide" dir="ltr" data-skin="default" data-bs-theme="light"
    data-assets-path="{{ rtrim(url('public/admin_theme/assets'), '/') }}/" data-template="vertical-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ config('app.name') }} | Associate Login</title>

    <link rel="icon" type="image/x-icon" href="{{ url('public/admin_theme/assets/img/favicon/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/node-waves/node-waves.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/pickr/pickr-themes.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/css/pages/page-auth.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/custom/custom.css') }}" />

    <script src="{{ url('public/admin_theme/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/js/template-customizer.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/js/config.js') }}"></script>
</head>

<body>
    <div class="authentication-wrapper authentication-cover">
        <a href="{{ url('/') }}" class="app-brand auth-cover-brand">
            <span class="app-brand-logo demo"></span>
        </a>

        <div class="authentication-inner row m-0">
            <div class="d-none d-xl-flex col-xl-8 p-0">
                <div class="auth-cover-bg d-flex justify-content-center align-items-center">
                    <img src="{{ url('public/admin_theme/assets/img/illustrations/auth-login-illustration-light.png') }}" alt="auth-login-cover" class="img-fluid my-5 auth-illustration" />
                    <img src="{{ url('public/admin_theme/assets/img/illustrations/bg-shape-image-light.png') }}" alt="auth-bg-cover" class="platform-bg" />
                </div>
            </div>

            <div class="d-flex col-12 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
                <div class="w-px-400 mx-auto mt-12 pt-5">
                    <div class="mb-4">
                        <img src="{{ url('public/admin_theme/assets/img/logo.png') }}" alt="logo" style="max-height: 60px;">
                    </div>
                    <h4 class="mb-1">Associate Login</h4>
                    <p class="mb-6" id="login-step-title">Enter your email to receive OTP</p>

                    <form id="associate-login-form" class="mb-6" action="{{ url('login/send-otp') }}" method="POST">
                        @csrf
                        <div class="mb-3 ajax-msg"></div>

                        <div id="associate-email-panel">
                            <div class="mb-6 ajax-field">
                                <label for="email" class="form-label">Email</label>
                                <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email"
                                    autocomplete="username" autofocus />
                                <span class="ajax-error"></span>
                            </div>
                            <button type="submit" class="btn btn-primary d-grid w-100 submit-button">Send OTP</button>
                        </div>

                        <div id="associate-otp-panel" class="d-none">
                            <p class="small text-body-secondary mb-3">We sent a 6-digit OTP to your email.</p>
                            <div class="mb-3 ajax-field">
                                <label class="form-label" for="associate-login-otp">Verification code</label>
                                <input type="text" class="form-control" id="associate-login-otp" maxlength="6"
                                    inputmode="numeric" autocomplete="one-time-code" placeholder="6-digit code" />
                                <span class="ajax-error" id="associate-otp-ajax-error"></span>
                            </div>
                            <input type="hidden" id="associate-login-pending-token" value="" />
                            <button type="button" class="btn btn-primary d-grid w-100 mb-2" id="associate-otp-verify-btn">Login</button>
                            <button type="button" class="btn btn-label-secondary d-grid w-100" id="associate-otp-back-btn">Back</button>
                        </div>
                    </form>
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
    <script src="{{ url('public/admin_theme/assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/js/main.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/js/pages-auth.js') }}"></script>
    <script src="{{ url('public/admin_theme/custom/custom.js') }}"></script>

    <script>
        function associateLoginShowOtpStep(pendingToken) {
            $('#associate-email-panel').addClass('d-none');
            $('#associate-otp-panel').removeClass('d-none');
            $('#associate-login-pending-token').val(pendingToken);
            $('#login-step-title').text('Enter your verification code');
            $('#associate-login-otp').val('').focus();
        }

        function associateLoginShowEmailStep() {
            $('#associate-otp-panel').addClass('d-none');
            $('#associate-email-panel').removeClass('d-none');
            $('#associate-login-pending-token').val('');
            $('#login-step-title').text('Enter your email to receive OTP');
            clearAjaxErrors();
        }

        $(document).ready(function() {
            $(document).on('submit', '#associate-login-form', function(e) {
                if (!$('#associate-otp-panel').hasClass('d-none')) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();
                clearAjaxErrors();
                const form = $(this);
                const btn = form.find('.submit-button');
                btn.prop('disabled', true).text('Please wait...');

                $.post(form.attr('action'), form.serializeArray(), function(res) {
                    btn.prop('disabled', false).text('Send OTP');
                    if (res.status == 1 && res.pending_token) {
                        associateLoginShowOtpStep(res.pending_token);
                        $('.ajax-msg').html('<div class="alert alert-success" role="alert">' + (res.msg || '') + '</div>');
                    } else {
                        processAjaxResponse(res, 800);
                    }
                }, 'json');
            });

            $('#associate-otp-verify-btn').on('click', function() {
                clearAjaxErrors();
                const token = $('#associate-login-pending-token').val();
                const otp = $('#associate-login-otp').val().trim();
                const btn = $(this);
                btn.prop('disabled', true).text('Verifying...');

                $.post('{{ url('login/verify-otp') }}', {
                    _token: '{{ csrf_token() }}',
                    pending_token: token,
                    otp: otp
                }, function(res) {
                    btn.prop('disabled', false).text('Login');
                    processAjaxResponse(res, 800);
                }, 'json');
            });

            $('#associate-otp-back-btn').on('click', function() {
                associateLoginShowEmailStep();
            });
        });
    </script>
</body>

</html>
