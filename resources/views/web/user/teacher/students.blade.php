@extends('web.user.layouts.app')
@section('content')
	<div class="container-xxl flex-grow-1 container-p-y">
		<div class="row g-6">
			<div class="col-12">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="mb-0">My Students</h5>
						<div class="d-flex gap-2">
							<a href="{{ url('user/teacher/students/bulk-sample') }}" class="btn btn-label-secondary btn-sm">
								<i class="icon-base ti tabler-download me-1"></i> Download Sample
							</a>
							<button type="button" class="btn btn-label-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkStudentModal">
								<i class="icon-base ti tabler-upload me-1"></i> Bulk Upload
							</button>
							<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#studentModal">
								<i class="icon-base ti tabler-plus me-1"></i> Add Student
							</button>
						</div>
					</div>
					<div class="card-body">
						<form method="GET" action="{{ url('user/teacher/students') }}" class="mb-4">
							<div class="row g-3">
								<div class="col-md-4">
									<label class="form-label">Search</label>
									<input type="text" name="search" class="form-control" placeholder="Name / Email / Phone" value="{{ $search ?? '' }}">
								</div>
								<div class="col-md-2">
									<label class="form-label">Classroom</label>
									<select name="classroom_id" class="form-select">
										<option value="">All Classrooms</option>
										@foreach(($classrooms ?? []) as $cr)
											<option value="{{ $cr->id }}" {{ (int)($classroom_id ?? 0) === (int)$cr->id ? 'selected' : '' }}>{{ $cr->name }}</option>
										@endforeach
									</select>
								</div>
								<div class="col-md-2">
									<label class="form-label">Batch</label>
									<select name="batch_id" class="form-select">
										<option value="">All Batches</option>
										@foreach(($batches ?? []) as $b)
											<option value="{{ $b->id }}" {{ (int)($batch_id ?? 0) === (int)$b->id ? 'selected' : '' }}>{{ $b->name }}</option>
										@endforeach
									</select>
								</div>
								<div class="col-md-2 d-flex align-items-end">
									<button type="submit" class="btn btn-primary w-100">Search</button>
								</div>
								<div class="col-md-2 d-flex align-items-end">
									<a href="{{ url('user/teacher/students') }}" class="btn btn-label-secondary w-100">Reset</a>
								</div>
							</div>
						</form>
						<div class="table-responsive">
							<table class="table table-bordered table-striped align-middle" id="studentsTable">
								<thead>
									<tr>
										<th>#</th>
										<th>Name</th>
										<th>Email</th>
										<th>Phone</th>
										<th>Classroom</th>
										<th>Batch</th>
										<th>Created</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									@forelse($students as $index => $student)
									<tr>
										<td>{{ (($page ?? 1) - 1) * ($per_page ?? 50) + $index + 1 }}</td>
										<td>{{ $student->name }}</td>
										<td>{{ $student->email }}</td>
										<td>{{ $student->phone }}</td>
										<td>{{ $student->classroom->name ?? '-' }}</td>
										<td>{{ $student->batch->name ?? '-' }}</td>
										<td><small class="text-body-secondary">{{ optional($student->created_at)->format('d-m-Y') }}</small></td>
										<td>
											<div class="d-flex gap-2">
												<a href="{{ url('user/teacher/students/view/' . base64_encode($student->id)) }}" class="btn btn-sm btn-icon btn-label-info" title="View">
													<i class="icon-base ti tabler-eye"></i>
												</a>
												@php
													$parentRow = null;
													if (!empty($student->parent_id)) {
														$parentRow = \App\Models\PortalUser::find($student->parent_id);
													}
												@endphp
												<button type="button" class="btn btn-sm btn-icon btn-label-primary edit-student"
													data-id="{{ base64_encode($student->id) }}"
													data-name="{{ $student->name }}"
													data-email="{{ $student->email }}"
													data-phone="{{ $student->phone }}"
													data-classroom_id="{{ $student->classroom_id }}"
													data-batch_id="{{ $student->batch_id }}"
													data-parent_name="{{ $parentRow->name ?? '' }}"
													data-parent_email="{{ $parentRow->email ?? '' }}"
													data-parent_phone="{{ $parentRow->phone ?? '' }}"
													title="Edit">
													<i class="icon-base ti tabler-edit"></i>
												</button>
												<button type="button" class="btn btn-sm btn-icon btn-label-danger delete-student"
													data-id="{{ base64_encode($student->id) }}"
													title="Delete">
													<i class="icon-base ti tabler-trash"></i>
												</button>
											</div>
										</td>
									</tr>
									@empty
									<tr>
										<td colspan="8" class="text-center">No students found</td>
									</tr>
									@endforelse
								</tbody>
							</table>
						</div>
						@if(($num_rows ?? 0) > ($per_page ?? 50))
							<div class="mt-3">
								<nav aria-label="Student pagination">
									<ul class="pagination justify-content-center">
										@for($i = 1; $i <= ceil(($num_rows ?? 0) / ($per_page ?? 50)); $i++)
											<li class="page-item {{ (int)($page ?? 1) === $i ? 'active' : '' }}">
												<a class="page-link" href="{{ $url }}{{ str_contains($url, '?') ? '&' : '?' }}page={{ $i }}">{{ $i }}</a>
											</li>
										@endfor
									</ul>
								</nav>
							</div>
						@endif
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Bulk Upload Students Modal -->
	<div class="modal fade" id="bulkStudentModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Bulk Upload Students (CSV)</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="alert alert-info py-2 mb-3">
						Step 1: Download sample file. Step 2: Fill data. Step 3: Upload CSV.<br>
						CSV required columns: <strong>name,email,phone,classroom,batch</strong><br>
						Optional columns: <strong>parent_name,parent_email,parent_phone</strong><br>
						Use classroom and batch names exactly as created (example: <strong>8th Class</strong>, <strong>Batch A</strong>).
					</div>
					<div class="mb-3">
						<label class="form-label">Upload CSV File</label>
						<input type="file" class="form-control" id="bulk_student_file" accept=".csv,text/csv">
						<small class="text-body-secondary">Max file size: 5MB</small>
					</div>
					<div class="mb-0" id="bulk-student-msg"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="upload-bulk-students-btn">Upload</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Add/Edit Student Modal -->
	<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-xl">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="studentModalTitle">Add Student</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3 student-msg"></div>
					<form id="student-form">
						@csrf
						<input type="hidden" name="id" id="student_id">
						<div class="row g-3">
							<div class="col-md-4 ajax-field">
								<label class="form-label">Name <span class="text-danger">*</span></label>
								<input type="text" class="form-control" name="name" id="student_name" placeholder="Enter student name">
								<span class="ajax-error text-danger small"></span>
							</div>
							<div class="col-md-4 ajax-field">
								<label class="form-label">Email <span class="text-danger">*</span></label>
								<input type="email" class="form-control" name="email" id="student_email" placeholder="Enter student email">
								<span class="ajax-error text-danger small"></span>
							</div>
							<div class="col-md-4 ajax-field">
								<label class="form-label">Phone <span class="text-danger">*</span></label>
								<input type="text" class="form-control" name="phone" id="student_phone" placeholder="Enter student phone">
								<span class="ajax-error text-danger small"></span>
							</div>
							<div class="col-md-4 ajax-field">
								<label class="form-label">Classroom <span class="text-danger">*</span></label>
								<select class="form-select" name="classroom_id" id="student_classroom_id">
									<option value="">Select Classroom</option>
									@foreach(\App\Models\Classroom::where('teacher_id', (int)(session('portal_user')['id'] ?? 0))->orderBy('name')->get() as $cr)
										<option value="{{ $cr->id }}">{{ $cr->name }}</option>
									@endforeach
								</select>
								<span class="ajax-error text-danger small"></span>
							</div>
							<div class="col-md-4 ajax-field">
								<label class="form-label">Batch <span class="text-danger">*</span></label>
								<select class="form-select" name="batch_id" id="student_batch_id">
									<option value="">Select Batch</option>
									@foreach(\App\Models\Batch::where('teacher_id', (int)(session('portal_user')['id'] ?? 0))->orderBy('name')->get() as $b)
										<option value="{{ $b->id }}" data-classroom_id="{{ $b->classroom_id }}">{{ $b->name }} ({{ $b->classroom->name ?? 'N/A' }})</option>
									@endforeach
								</select>
								<span class="ajax-error text-danger small"></span>
							</div>
							<div class="col-md-4"></div>
							<div class="col-md-4 ajax-field">
								<label class="form-label">Parent Name</label>
								<input type="text" class="form-control" name="parent_name" id="parent_name" placeholder="Enter parent name">
								<span class="ajax-error text-danger small"></span>
							</div>
							<div class="col-md-4 ajax-field">
								<label class="form-label">Parent Email</label>
								<input type="email" class="form-control" name="parent_email" id="parent_email" placeholder="parent@example.com">
								<span class="ajax-error text-danger small"></span>
							</div>
							<div class="col-md-4 ajax-field">
								<label class="form-label">Parent Phone</label>
								<input type="text" class="form-control" name="parent_phone" id="parent_phone" placeholder="e.g., 9876543210">
								<span class="ajax-error text-danger small"></span>
							</div>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="save-student-btn">Save Student</button>
				</div>
			</div>
		</div>
	</div>

@section('scripts')
	<script>
		$(document).ready(function() {
			const addStudentModal = new bootstrap.Modal(document.getElementById('studentModal'));
			const bulkStudentModal = new bootstrap.Modal(document.getElementById('bulkStudentModal'));

			function clearAjaxErrors() {
				$('.ajax-error').text('');
				$('.student-msg').html('');
			}

			function escapeHtml(text) {
				return String(text || '').replace(/[&<>"']/g, function(m) {
					return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
				});
			}

			// Filter batches by classroom (no extra request)
			function filterBatchesByClassroom() {
				const classroomId = $('#student_classroom_id').val();
				const $batch = $('#student_batch_id');
				const current = $batch.val();
				$batch.find('option').each(function(){
					const cid = $(this).data('classroom_id');
					if (!cid) return; // keep placeholder
					$(this).toggle(String(cid) === String(classroomId));
				});
				// reset if invalid
				if (current) {
					const visible = $batch.find('option[value="'+current+'"]:visible');
					if (visible.length === 0) $batch.val('');
				}
			}

			$('#student_classroom_id').on('change', filterBatchesByClassroom);

			// Clear form on hide
			$('#studentModal').on('hidden.bs.modal', function () {
				$('#student-form')[0].reset();
				$('#student_id').val('');
				$('#studentModalTitle').text('Add Student');
				filterBatchesByClassroom();
				clearAjaxErrors();
			});

			// Edit button
			$(document).on('click', '.edit-student', function() {
				const id = $(this).data('id');
				const name = $(this).data('name');
				const email = $(this).data('email');
				const phone = $(this).data('phone');
				const classroom_id = $(this).data('classroom_id');
				const batch_id = $(this).data('batch_id');
				const parent_name = $(this).data('parent_name') || '';
				const parent_email = $(this).data('parent_email') || '';
				const parent_phone = $(this).data('parent_phone') || '';

				$('#student_id').val(id);
				$('#student_name').val(name);
				$('#student_email').val(email);
				$('#student_phone').val(phone);
				$('#student_classroom_id').val(String(classroom_id));
				filterBatchesByClassroom();
				$('#student_batch_id').val(String(batch_id));
				$('#parent_name').val(parent_name);
				$('#parent_email').val(parent_email);
				$('#parent_phone').val(parent_phone);
				$('#studentModalTitle').text('Edit Student');
				addStudentModal.show();
			});

			// Save
			$(document).on('click', '#save-student-btn', function() {
				clearAjaxErrors();
				const $btn = $(this);
				$btn.attr('disabled', 'disabled').text('Please wait...');

				const formData = $('#student-form').serializeArray();
				formData.push({ name: '_token', value: '{{ csrf_token() }}' });

				$.post('{{ url("user/teacher/students/save") }}', formData, function(res) {
					$btn.removeAttr('disabled').text('Save Student');
					if (res.status == 1) {
						addStudentModal.hide();
						location.reload();
					} else if (res.error_array) {
						Object.keys(res.error_array).forEach(function(key) {
							const field = $('#student-form [name="' + key + '"]');
							if (field.length) {
								field.closest('.ajax-field').find('.ajax-error').text(res.error_array[key]);
							}
						});
					} else if (res.error) {
						$('.student-msg').html('<div class="alert alert-danger">' + res.error + '</div>');
					}
				}, 'json');
			});

			$(document).on('click', '#upload-bulk-students-btn', function() {
				const fileInput = document.getElementById('bulk_student_file');
				const file = fileInput.files[0];
				const $btn = $(this);
				const $msg = $('#bulk-student-msg');
				$msg.html('');

				if (!file) {
					$msg.html('<div class="alert alert-danger mb-0">Please select a CSV file.</div>');
					return;
				}

				const data = new FormData();
				data.append('_token', '{{ csrf_token() }}');
				data.append('file', file);

				$btn.attr('disabled', 'disabled').text('Uploading...');
				$.ajax({
					url: '{{ url("user/teacher/students/bulk-upload") }}',
					type: 'POST',
					data: data,
					processData: false,
					contentType: false,
					dataType: 'json',
					success: function(res) {
						$btn.removeAttr('disabled').text('Upload');
						if (res.status == 1) {
							let html = '<div class="alert alert-success mb-2">' + escapeHtml(res.msg || 'Upload completed') + '</div>';
							if (res.errors && res.errors.length) {
								const items = res.errors.slice(0, 15).map(function(err) {
									return '<li>' + escapeHtml(err) + '</li>';
								}).join('');
								html += '<div class="alert alert-warning mb-0"><strong>Row Issues:</strong><ul class="mb-0 mt-2">' + items + '</ul></div>';
							}
							$msg.html(html);
							const created = Number(res.summary && res.summary.created ? res.summary.created : 0);
							const updated = Number(res.summary && res.summary.updated ? res.summary.updated : 0);
							if (created > 0 || updated > 0) {
								setTimeout(function() { location.reload(); }, 1800);
							}
						} else if (res.error_array) {
							const firstKey = Object.keys(res.error_array)[0];
							const firstErr = firstKey ? res.error_array[firstKey][0] : 'Validation failed';
							$msg.html('<div class="alert alert-danger mb-0">' + escapeHtml(firstErr) + '</div>');
						} else {
							let html = '<div class="alert alert-danger mb-2">' + escapeHtml(res.error || 'Upload failed') + '</div>';
							if (res.errors && res.errors.length) {
								const items = res.errors.slice(0, 20).map(function(err) {
									return '<li>' + escapeHtml(err) + '</li>';
								}).join('');
								html += '<div class="alert alert-warning mb-0"><strong>Row Issues:</strong><ul class="mb-0 mt-2">' + items + '</ul></div>';
							}
							$msg.html(html);
						}
					},
					error: function(xhr) {
						$btn.removeAttr('disabled').text('Upload');
						const serverMsg = xhr && xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : '';
						$msg.html('<div class="alert alert-danger mb-0">' + escapeHtml(serverMsg || 'Upload failed. Please try again.') + '</div>');
					}
				});
			});

			$('#bulkStudentModal').on('hidden.bs.modal', function () {
				$('#bulk_student_file').val('');
				$('#bulk-student-msg').html('');
			});

			// Delete
			$(document).on('click', '.delete-student', function() {
				const id = $(this).data('id');
				if (confirm('Are you sure you want to delete this student?')) {
					$.post('{{ url("user/teacher/students/delete") }}', {
						_token: '{{ csrf_token() }}',
						id: id
					}, function(res) {
						if (res.status == 1) {
							location.reload();
						} else {
							alert(res.error || 'Failed to delete student.');
						}
					}, 'json');
				}
			});
		});
	</script>
@endsection
@endsection
