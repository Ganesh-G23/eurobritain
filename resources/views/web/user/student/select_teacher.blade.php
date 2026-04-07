<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Select Teacher - EliteGrade</title>
	<link rel="icon" type="image/x-icon" href="{{ url('public/admin_theme/assets/img/favicon/favicon.ico') }}" />
	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/css/core.css') }}" />
	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/css/demo.css') }}" />
	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/node-waves/node-waves.css') }}" />
	<link rel="stylesheet" href="{{ url('public/admin_theme/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
	<style>
		body { background-color: #f5f5f9; min-height: 100vh; }
		.page-wrap { width: 100%; }
		.page-header { background: #fff; border-bottom: 1px solid #e5e7eb; }
		.page-header-inner { max-width: 1200px; margin: 0 auto; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
		.brand { font-weight: 700; font-size: 1.05rem; color: #111827; }
		.header-actions { display: flex; align-items: center; gap: 8px; }
		.btn-logout { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #111827; background: #111827; color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; }
		.btn-logout:hover { background: #000; color: #fff; }

		.headerless-container { max-width: 1100px; margin: 0 auto; padding: 20px; }
		.card { border: 1px solid #e0e0e0; border-radius: 10px; background: #fff; }
		.card-body { padding: 18px; }
		.card-teacher { border: 1px solid #e0e0e0; border-radius: 10px; background: #fff; padding: 14px 16px; margin-bottom: 12px; }
		.card-title { font-size: 1.05rem; font-weight: 700; margin: 0 0 8px; text-transform: uppercase; letter-spacing: .02em; }
		.card-sub { color: #6b7280; margin-bottom: 0; }
		.meta { font-size: 0.95rem; color: #374151; }
		.meta strong { color: #111827; }
		.btn-access { white-space: nowrap; }
		.btn-dark { background: #111827; color: #fff; border: 1px solid #111827; }
		.btn-dark:hover { background: #000; color: #fff; }
		.page-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; }
		.greet { color: #111827; font-weight: 700; font-size: 1.15rem; margin-bottom: 6px; }
		.subtext { color: #6b7280; margin-bottom: 14px; }
	</style>
</head>
<body>
	<div class="page-wrap">
		<div class="page-header">
			<div class="page-header-inner">
				@php $pu = session('portal_user') ?? []; $initial = strtoupper(substr(trim($pu['name'] ?? 'U'), 0, 1)); @endphp
				<div class="brand"><img src="{{ url('public/admin_theme/assets/img/logo.png') }}" alt="EliteGrade" style="height:28px;"></div>
				<div class="header-actions dropdown">
					<a class="d-inline-flex align-items-center justify-content-center rounded-circle" href="javascript:void(0)" id="miniUserMenu" data-bs-toggle="dropdown" aria-expanded="false" style="width:36px;height:36px;background:#e5e7eb;color:#111827;text-decoration:none;">
						<span style="font-weight:700;">{{ $initial }}</span>
					</a>
					<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="miniUserMenu">
						<li><a class="dropdown-item" href="{{ url('user/logout') }}">Logout</a></li>
					</ul>
				</div>
			</div>
            
		</div>
		<div class="headerless-container">
			<div class="card">
				<div class="card-body">
					@php $student = session('portal_user') ?? []; @endphp
					<div class="greet">Hello {{ $student['name'] ?? 'Student' }}</div>
					<div class="page-title">Select Your Teacher</div>
					<div class="subtext">You are a part of the following teachers. Choose one to access now.</div>

					@if(($teachers ?? collect())->count() === 0)
						<div class="card-teacher">
							<div class="card-title">No teachers assigned yet</div>
							<div class="card-sub">Please contact your institute or teacher to be assigned.</div>
						</div>
					@else
						@foreach($teachers as $t)
							<div class="card-teacher">
								<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
									<div class="flex-grow-1">
										<div class="card-title">{{ $t->teacher_name }}</div>
										<div class="card-sub">Email: {{ $t->teacher_email }}</div>
										@if($t->teacher_phone)<div class="card-sub">Phone: {{ $t->teacher_phone }}</div>@endif
										@if($t->batch_name)<div class="card-sub">Batch: {{ $t->batch_name }}</div>@endif
										@if($t->classroom_name)<div class="card-sub">Classroom: {{ $t->classroom_name }}</div>@endif
									</div>
									<div>
										<form method="post" action="{{ url('user/select-teacher/access') }}">
											@csrf
											<input type="hidden" name="teacher_id" value="{{ (int)$t->teacher_id }}">
											<button type="submit" class="btn btn-dark btn-access">
												<span style="font-size:12px;">&#10132;</span> Access
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

	@php
		$portalUserSession = session('portal_user') ?? [];
		$suppressPasswordPopup = (int)($portalUserSession['is_password'] ?? 0) === 1;
		$shouldShowPasswordPopup = (int)(session('show_teacher_password_popup') ?? 0) === 1 && !$suppressPasswordPopup;
	@endphp

	<!-- Change Password Required Modal (copied minimal, no header/footer layout) -->
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

	<script src="{{ url('public/admin_theme/assets/vendor/libs/jquery/jquery.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/vendor/libs/popper/popper.js') }}"></script>
	<script src="{{ url('public/admin_theme/assets/vendor/js/bootstrap.js') }}"></script>
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
</body>
</html>

