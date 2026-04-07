<!doctype html>

<html lang="en" class="layout-wide customizer-hide" dir="ltr" data-skin="default" data-bs-theme="light"
    data-assets-path="{{ rtrim(url('public/admin_theme/assets'), '/') }}/" data-template="vertical-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ config('app.name') }} | Login</title>

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

    <!-- Vendor -->
    <link rel="stylesheet"
        href="{{ url('public/admin_theme/assets/vendor/libs/@form-validation/form-validation.css') }}" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/css/pages/page-auth.css') }}" />

    <link rel="stylesheet" href="{{ url('public/admin_theme/custom/custom.css') }}" />

    <!-- Helpers -->
    <script src="{{ url('public/admin_theme/assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    {{-- <script src="{{ url('public/admin_theme/assets/vendor/js/template-customizer.js') }}"></script> --}}

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="{{ url('public/admin_theme/assets/js/config.js') }}"></script>
</head>

<body>
    <!-- Content -->

    <div class="authentication-wrapper authentication-cover">
        <!-- Logo -->
        <a href="{{ url('admin') }}" class="app-brand auth-cover-brand">
            <span class="app-brand-logo demo">
                
            </span>
        </a>
        <!-- /Logo -->
        <div class="authentication-inner row m-0">
            <!-- /Left Text -->
            <div class="d-none d-xl-flex col-xl-8 p-0">
                <div class="auth-cover-bg d-flex justify-content-center align-items-center">
                    <img src="{{url('public/admin_theme/assets/img/illustrations/auth-login-illustration-light.png')}}" alt="auth-login-cover" class="img-fluid my-5 auth-illustration" data-app-light-img="illustrations/auth-login-illustration-light.png" data-app-dark-img="illustrations/auth-login-illustration-dark.png" />

                    <img src="{{url('public/admin_theme/assets/img/illustrations/bg-shape-image-light.png')}}" alt="auth-login-cover" class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png" />
                </div>
            </div>
            <!-- /Left Text -->

            <!-- Login -->
            <div class="d-flex col-12 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
                <div class="w-px-400 mx-auto mt-12 pt-5">
                    <div class="text-center mb-4">
                        <img src="{{ url('public/admin_theme/assets/img/logo.png') }}" alt="logo" style="max-height: 60px;">
                    </div>
                    <h4 class="mb-1">Welcome to {{ config('app.name') }}!</h4>
                    <p class="mb-6">Please sign-in to your account</p>

                    <form id="login-form" class="mb-6" action="{{ url('admin/verify_login') }}" method="POST">
                        @csrf
                        <div class="mb-3 ajax-msg"></div>
                        <div class="mb-6 ajax-field">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control" id="email" name="email"
                                placeholder="Enter your email" autofocus />
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-6 form-password-toggle ajax-field">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control" name="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="password" />
                                <span class="input-group-text cursor-pointer"><i
                                        class="icon-base ti tabler-eye-off"></i></span>
                            </div>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="my-8">
                            <div class="d-flex justify-content-between">
                                <div class="form-check mb-0 ms-2">
                                    <input class="form-check-input" type="checkbox" id="remember-me" />
                                    <label class="form-check-label" for="remember-me"> Remember Me </label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary d-grid w-100 submit-button">Sign in</button>
                    </form>
                </div>
            </div>
            <!-- /Login -->
        </div>
    </div>

    <!-- / Content -->

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
    <script src="{{ url('public/admin_theme/assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>

    <!-- Main JS -->

    <script src="{{ url('public/admin_theme/assets/js/main.js') }}"></script>

    <!-- Page JS -->
    <script src="{{ url('public/admin_theme/assets/js/pages-auth.js') }}"></script>
    <script src="{{ url('public/admin_theme/custom/custom.js') }}"></script>
    <script>
        $(document).ready(function() {
            $(document).on('submit', '#login-form', function(e) {
                e.preventDefault();
                clearAjaxErrors();
                const _this = $(this);
                _this.find('.submit-button').attr('disabled', 'disabled');
                _this.find('.submit-button').text('Please wait...');

                let url = $(this).attr('action');
                let data = $(this).serializeArray();

                $.post(url, data, function(res) {
                    _this.find('.submit-button').removeAttr('disabled');
                    _this.find('.submit-button').text('Sign in');
                    processAjaxResponse(res, 1000);
                }, 'json')
            })
        })
    </script>
</body>

</html>
