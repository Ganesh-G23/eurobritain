<!doctype html>
<html lang="en" class="layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-skin="default"
	data-bs-theme="light" data-assets-path="{{ url('public/admin_theme/assets/') }}" data-template="horizontal-menu-template">
<head>
	<meta charset="utf-8" />
	<meta name="csrf-token" content="{{ csrf_token() }}" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
	<meta name="robots" content="noindex, nofollow" />
	<title>@yield('title', 'Student Panel') - EliteGrade</title>

	<link rel="icon" type="image/x-icon" href="{{ url('public/admin_theme/assets/img/favicon/favicon.ico') }}" />

	<link rel="preconnect" href="https://fonts.googleapis.com" />
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
	<link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap" rel="stylesheet" />
	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/fonts/iconify-icons.css') }}" />

	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/node-waves/node-waves.css') }}" />
	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/pickr/pickr-themes.css') }}" />
	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/css/core.css') }}" />
	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/css/demo.css') }}" />
	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/apex-charts/apex-charts.css') }}" />
	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/fonts/flag-icons.css') }}" />
	<script src="{{ url('public/admin_theme/assets/vendor/js/template-customizer.js') }}"></script>

	<script src="{{ url('public/admin_theme/assets/vendor/js/helpers.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/js/config.js') }}"></script>
</head>
<body>
	<div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
		<div class="layout-container">
			<div class="layout-page">
				<div class="content-wrapper d-flex flex-column">
					@include('web.user.student.layouts.navigate')
					<!-- <div class="container-fluid flex-grow-1 pt-2 pb-3"> -->
						@yield('content')
					<!-- </div> -->
					<footer class="content-footer footer bg-footer-theme">
						<div class="container-xxl">
							<div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
							</div>
						</div>
					</footer>
					<div class="content-backdrop fade"></div>
				</div>
			</div>
		</div>
	</div>

	<div class="layout-overlay layout-menu-toggle"></div>
	<div class="drag-target"></div>

	<script src="{{ url('public/admin_theme/assets/vendor/libs/jquery/jquery.js') }}"></script>
	@stack('portal_notification_scripts')
	<script src="{{ url('public/admin_theme/assets/vendor/libs/popper/popper.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/vendor/js/bootstrap.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/vendor/libs/node-waves/node-waves.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/vendor/libs/@algolia/autocomplete-js.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/vendor/libs/pickr/pickr.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/vendor/libs/hammer/hammer.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/vendor/libs/i18n/i18n.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/vendor/js/menu.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/js/main.js') }}"></script>
	<script src="{{ url('public/admin_theme/custom/custom.js') }}"></script>
	@yield('scripts')
</body>
</html>

