<!doctype html>

<html lang="en" class="layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-skin="default"
    data-bs-theme="light" data-assets-path="{{ rtrim(url('public/admin_theme/assets'), '/') }}/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ config('app.name') }} | {{ $title ?? 'Dashboard' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

    <!-- endbuild -->
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/swiper/swiper.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/fonts/flag-icons.css') }}" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/css/pages/cards-advance.css') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">

    <link rel="stylesheet" href="{{ url('public/admin_theme/custom/custom.css') }}" />
    <!-- Helpers -->
    <script src="{{ url('public/admin_theme/assets/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    {{-- <script src="{{ url('public/admin_theme/assets/vendor/js/template-customizer.js') }}"></script> --}}

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script>
        // Fix assetsPath immediately before config.js loads
        (function() {
            const html = document.documentElement;
            let assetsPath = html.getAttribute('data-assets-path');
            if (assetsPath && !assetsPath.endsWith('/')) {
                html.setAttribute('data-assets-path', assetsPath + '/');
            }
        })();
    </script>
    <script src="{{ url('public/admin_theme/assets/js/config.js') }}"></script>
    <script>
        // Ensure window.assetsPath has trailing slash after config.js sets it
        if (window.assetsPath && !window.assetsPath.endsWith('/')) {
            window.assetsPath += '/';
        }
    </script>
</head>

<body>
    @php
        $panelUser = session('admin');
    @endphp
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->

            <aside id="layout-menu" class="layout-menu menu-vertical menu">
                <div class="app-brand demo">
                    <a href="{{ url('admin') }}" class="app-brand-link">
                        <span class="app-brand-logo demo mt-4 pb-4">
                            <img src="{{ url('public/admin_theme/assets/img/logo.png') }}" alt="logo" class="img-fluid" style="max-height: 60px;">
                        </span>
                    </a>

                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                        <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
                        <i class="icon-base ti tabler-x d-block d-xl-none"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    @include('admin.layouts.navigate')
                </ul>
            </aside>

            <div class="menu-mobile-toggler d-xl-none rounded-1">
                <a href="javascript:void(0);"
                    class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
                    <i class="ti tabler-menu icon-base"></i>
                    <i class="ti tabler-chevron-right icon-base"></i>
                </a>
            </div>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->

                <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
                    id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                            <i class="icon-base ti tabler-menu-2 icon-md"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">

                        <ul class="navbar-nav flex-row align-items-center ms-md-auto">

                            <!-- Style Switcher -->
                             <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill"
                                    id="nav-theme" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="icon-base ti tabler-sun icon-22px theme-icon-active text-heading"></i>
                                    <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
                                    <li>
                                        <button type="button" class="dropdown-item align-items-center active"
                                            data-bs-theme-value="light" aria-pressed="false">
                                            <span><i class="icon-base ti tabler-sun icon-22px me-3"
                                                    data-icon="sun"></i>Light</span>
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item align-items-center"
                                            data-bs-theme-value="dark" aria-pressed="true">
                                            <span><i class="icon-base ti tabler-moon-stars icon-22px me-3"
                                                    data-icon="moon-stars"></i>Dark</span>
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item align-items-center"
                                            data-bs-theme-value="system" aria-pressed="false">
                                            <span><i class="icon-base ti tabler-device-desktop-analytics icon-22px me-3"
                                                    data-icon="device-desktop-analytics"></i>System</span>
                                        </button>
                                    </li>
                                </ul>
                            </li> 
                            <!-- / Style Switcher-->

                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);"
                                    data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="{{ url('public/admin_theme/assets/img/avatars/1.png') }}" alt class="rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item mt-0" href="{{ url('admin/profile') }}">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-2">
                                                    <div class="avatar avatar-online">
                                                        <img src="{{ url('public/admin_theme/assets/img/avatars/1.png') }}" alt
                                                            class="rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0">{{ $panelUser['name'] ?? '' }}</h6>
                                                    <small class="text-body-secondary">{{ $panelUser['email'] ?? '' }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider my-1 mx-n2"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ url('admin/profile') }}">
                                            <i class="icon-base ti tabler-user me-3 icon-md"></i><span
                                                class="align-middle">My Profile</span>
                                        </a>
                                    </li>
                                    {{-- <li>
                                        <a class="dropdown-item" href="pages-account-settings-account.html">
                                            <i class="icon-base ti tabler-settings me-3 icon-md"></i><span
                                                class="align-middle">Settings</span>
                                        </a>
                                    </li> --}}
                                    <li>
                                        <div class="dropdown-divider my-1 mx-n2"></div>
                                    </li>

                                    <li>
                                        <div class="d-grid px-2 pt-2 pb-1">
                                            <a class="btn btn-sm btn-danger d-flex" href="{{ url('admin/logout') }}">
                                                <small class="align-middle">Logout</small>
                                                <i class="icon-base ti tabler-logout ms-2 icon-14px"></i>
                                            </a>
                                        </div>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>
                    </div>
                </nav>

                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    @yield('content')
                    <!-- / Content -->

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
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>

        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    @if(false)
    <div class="modal fade" id="userPasswordPopupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3 teacher-password-msg"></div>
                    <form id="user-password-popup-form">
                        <div class="mb-3 ajax-field">
                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" placeholder="Enter new password">
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-0 ajax-field">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm password">
                            <span class="ajax-error text-danger small"></span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" id="skip-user-password-popup">Skip</button>
                    <button type="button" class="btn btn-primary" id="save-user-password-popup">Save Password</button>
                </div>
            </div>
        </div>
    </div>
    @endif

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

    <!-- GLightbox from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>

    <!-- endbuild -->
    <!-- Vendors JS -->
    <script src="{{ url('public/admin_theme/assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/swiper/swiper.js') }}"></script>
    <script src="{{ url('public/admin_theme/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>

    <!-- Main JS -->
    <script src="{{ url('public/admin_theme/assets/js/main.js') }}"></script>
    <script src="{{ url('public/admin_theme/custom/custom.js') }}"></script>
    <script>
        // Fix assetsPath to ensure it ends with a slash
        if (window.assetsPath && !window.assetsPath.endsWith('/')) {
            window.assetsPath += '/';
        }
    </script>
    @if(false)
    <script>
        $(document).ready(function () {
            const popupModal = new bootstrap.Modal(document.getElementById('userPasswordPopupModal'), {
                backdrop: 'static',
                keyboard: false
            });
            popupModal.show();

            function clearPopupErrors() {
                $('#user-password-popup-form .ajax-error').text('');
                $('.teacher-password-msg').html('');
            }

            $(document).on('click', '#save-user-password-popup', function () {
                clearPopupErrors();
                const $btn = $(this);
                $btn.attr('disabled', 'disabled').text('Please wait...');

                const formData = $('#user-password-popup-form').serializeArray();
                formData.push({ name: '_token', value: '{{ csrf_token() }}' });

                $.post('{{ url("user/update_password_popup") }}', formData, function (res) {
                    $btn.removeAttr('disabled').text('Save Password');
                    if (res.status == 1) {
                        popupModal.hide();
                    } else if (res.error_array) {
                        Object.keys(res.error_array).forEach(function (key) {
                            const field = $('#user-password-popup-form [name="' + key + '"]');
                            if (field.length) {
                                field.closest('.ajax-field').find('.ajax-error').text(res.error_array[key]);
                            }
                        });
                    } else if (res.error) {
                        $('.teacher-password-msg').html('<div class="alert alert-danger">' + res.error + '</div>');
                    }
                }, 'json');
            });

            $(document).on('click', '#skip-user-password-popup', function () {
                $.post('{{ url("user/skip_password_popup") }}', {
                    _token: '{{ csrf_token() }}'
                }, function () {
                    popupModal.hide();
                }, 'json');
            });
        });
    </script>
    @endif
    @yield('scripts')
</body>

</html>