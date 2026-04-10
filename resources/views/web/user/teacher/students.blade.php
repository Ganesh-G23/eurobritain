@extends('web.user.layouts.app')
@section('content')
	<div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
		<div class="row g-6">
		
			<div class="col-12">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="mb-0">My Students</h5>
						<div class="d-flex gap-2">
							<button type="button" class="btn btn-label-primary btn-sm" data-bs-toggle="modal"
								data-bs-target="#portalImportStudentsModal">
								<i class="icon-base ti tabler-upload me-1"></i> Import students
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
									@php
										$mapsForRow = $student->studentClassroomMaps;
										if ($mapsForRow->isNotEmpty()) {
											$crParts = [];
											$batchParts = [];
											foreach ($mapsForRow as $m) {
												$cn = trim((string) ($m->classroom->name ?? ''));
												$bn = trim((string) ($m->batch->name ?? ''));
												$crParts[] = $cn !== '' ? $cn : '—';
												$batchParts[] = $bn !== '' ? $bn : '—';
											}
											$crDisplay = implode(', ', $crParts);
											$batchDisplay = implode(', ', $batchParts);
										} else {
											$crDisplay = $student->classroom->name ?? '—';
											$batchDisplay = $student->batch->name ?? '—';
										}
										$mapsJson = $mapsForRow->map(static function ($m) {
											return ['classroom_id' => (int) $m->classroom_id, 'batch_id' => (int) $m->batch_id];
										})->values()->toJson();
										$parentRow = !empty($student->parent_id) ? \App\Models\PortalUser::find($student->parent_id) : null;
									@endphp
									<tr>
										<td>{{ (($page ?? 1) - 1) * ($per_page ?? 50) + $index + 1 }}</td>
										<td>{{ $student->name }}</td>
										<td>{{ $student->email }}</td>
										<td>{{ $student->phone }}</td>
										<td>{{ $crDisplay }}</td>
										<td>{{ $batchDisplay }}</td>
										<td><small class="text-body-secondary">{{ optional($student->created_at)->format('d-m-Y') }}</small></td>
										<td>
											<div class="d-flex flex-wrap gap-1">
												<a href="{{ url('user/teacher/students/view/' . base64_encode($student->id)) }}" class="btn btn-sm btn-icon btn-label-info" title="View">
													<i class="icon-base ti tabler-eye"></i>
												</a>
												<button type="button" class="btn btn-sm btn-icon btn-label-secondary open-student-enrollments"
													data-student-id="{{ base64_encode($student->id) }}"
													data-maps="{{ $mapsJson }}"
													title="Classrooms &amp; batches">
													<i class="icon-base ti tabler-school"></i>
												</button>
												<button type="button" class="btn btn-sm btn-icon btn-label-primary edit-student"
													data-id="{{ base64_encode($student->id) }}"
													data-name="{{ $student->name }}"
													data-email="{{ $student->email }}"
													data-phone="{{ $student->phone }}"
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

	<!-- Import students (CSV) — same flow as admin -->
	<div class="modal fade" id="portalImportStudentsModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Import students (CSV)</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="alert alert-info py-2 mb-3 small">
						<strong>1.</strong> Students are imported for your account
						@if(!empty($teacher_name)) (<strong>{{ $teacher_name }}</strong>) @endif.<br>
						<strong>2.</strong> Download the sample and fill rows using your classroom and batch names (or numeric IDs).<br>
						<strong>3.</strong> Required columns:
						<strong>name, email, phone, classroom, batch</strong> (first pair = primary display).<br>
						<strong>Optional extra enrollments:</strong>
						<strong>classroom_2, batch_2</strong> and <strong>classroom_3, batch_3</strong> — leave blank if not used.<br>
						Each row’s pairs <strong>replace</strong> that student’s classroom/batch mappings for you (same as the school icon in the list).<br>
						Optional parent columns: <strong>parent_name, parent_email, parent_phone</strong>.
					</div>
					<div class="mb-3">
						<label class="form-label">Teacher</label>
						<input type="text" class="form-control" value="{{ $teacher_name ?? 'You' }}" readonly>
					</div>
					<div class="mb-3">
						<button type="button" class="btn btn-label-primary" id="btn-portal-import-download-sample">
							<i class="icon-base ti tabler-download me-1"></i> Download sample CSV
						</button>
					</div>
					<div class="mb-3">
						<label class="form-label">CSV file</label>
						<input type="file" class="form-control" id="portal_import_student_csv_file" accept=".csv,text/csv">
						<small class="text-body-secondary">Max 5 MB</small>
					</div>
					<div id="portal-import-student-msg" class="mb-0"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="btn-portal-import-students-upload">
						<i class="icon-base ti tabler-upload me-1"></i> Upload
					</button>
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
						<p class="text-body-secondary small mb-3">Set classrooms and batches using the <strong>school</strong> icon in the list after saving.</p>
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

	<div class="modal fade" id="studentEnrollmentsModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Classrooms &amp; batches</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form id="studentEnrollmentsForm">
						<input type="hidden" name="student_id" id="enr_student_id" value="">
						<input type="hidden" name="teacher_id" value="{{ $teacher_id ?? 0 }}">
						<p class="text-body-secondary small">Add one row per classroom and batch. The first row sets the student&apos;s primary classroom and batch.</p>
						<div id="student-enrollment-rows" class="mb-3"></div>
						<button type="button" class="btn btn-sm btn-label-primary" id="add-enrollment-row">
							<i class="icon-base ti tabler-plus me-1"></i> Add row
						</button>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" id="saveStudentEnrollmentsBtn">Save</button>
				</div>
			</div>
		</div>
	</div>

	<template id="enrollment-row-template">
		<div class="row g-2 align-items-end enrollment-row mb-3 pb-3 border-bottom">
			<div class="col-md-5">
				<label class="form-label small mb-1">Classroom</label>
				<select class="form-select enr-classroom" name="map_classroom_id[]">
					<option value="">Choose classroom</option>
					@foreach(($classrooms ?? []) as $classroom)
						<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
					@endforeach
				</select>
			</div>
			<div class="col-md-5">
				<label class="form-label small mb-1">Batch</label>
				<select class="form-select enr-batch" name="map_batch_id[]" disabled>
					<option value="">Choose classroom first</option>
				</select>
			</div>
			<div class="col-md-2 text-md-end">
				<button type="button" class="btn btn-sm btn-label-danger remove-enr-row" title="Remove row">
					<i class="icon-base ti tabler-trash"></i>
				</button>
			</div>
		</div>
	</template>
@endsection

@section('scripts')
	<script>
		$(document).ready(function() {
			const addStudentModal = new bootstrap.Modal(document.getElementById('studentModal'));
			const studentEnrollmentsModal = new bootstrap.Modal(document.getElementById('studentEnrollmentsModal'));

			const portalBulkSampleUrl = '{{ url("user/teacher/students/bulk-sample") }}';

			$('#portalImportStudentsModal').on('hidden.bs.modal', function() {
				$('#portal_import_student_csv_file').val('');
				$('#portal-import-student-msg').html('');
			});

			$('#btn-portal-import-download-sample').on('click', function() {
				window.location.href = portalBulkSampleUrl;
			});

			function clearAjaxErrors() {
				$('.ajax-error').text('');
				$('.student-msg').html('');
			}

			function escapeHtml(text) {
				return String(text || '').replace(/[&<>"']/g, function(m) {
					return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
				});
			}

			function loadBatchesIntoSelect($batchSelect, classroomId, selectedBatchId, done) {
				selectedBatchId = selectedBatchId || '';
				if (!classroomId) {
					$batchSelect.html('<option value="">Choose classroom first</option>').prop('disabled', true);
					if (typeof done === 'function') done();
					return;
				}
				$batchSelect.html('<option value="">Loading...</option>').prop('disabled', true);
				$.get('{{ url("user/teacher/batches-by-classroom") }}', { classroom_id: classroomId }, function(res) {
					if (res.status == 1 && res.data && res.data.length) {
						$batchSelect.html('<option value="">Choose batch</option>');
						$.each(res.data, function(index, batch) {
							const selected = String(batch.id) === String(selectedBatchId) ? ' selected' : '';
							$batchSelect.append('<option value="' + batch.id + '"' + selected + '>' + batch.name + '</option>');
						});
						$batchSelect.prop('disabled', false);
					} else {
						$batchSelect.html('<option value="">No batches found</option>').prop('disabled', true);
					}
					if (typeof done === 'function') done();
				}, 'json');
			}

			function appendEnrollmentRow(classroomId, batchId) {
				const tpl = document.getElementById('enrollment-row-template');
				const frag = tpl.content.cloneNode(true);
				const el = frag.querySelector('.enrollment-row');
				$('#student-enrollment-rows').append(el);
				const $row = $(el);
				const $c = $row.find('.enr-classroom');
				const $b = $row.find('.enr-batch');
				if (classroomId) {
					$c.val(String(classroomId));
				}
				loadBatchesIntoSelect($b, $c.val(), batchId);
			}

			$(document).on('change', '#studentEnrollmentsModal .enr-classroom', function() {
				const $row = $(this).closest('.enrollment-row');
				loadBatchesIntoSelect($row.find('.enr-batch'), $(this).val(), '');
			});

			$(document).on('click', '#add-enrollment-row', function() {
				appendEnrollmentRow('', '');
			});

			$(document).on('click', '.remove-enr-row', function() {
				const $rows = $('#student-enrollment-rows .enrollment-row');
				if ($rows.length <= 1) {
					$(this).closest('.enrollment-row').find('.enr-classroom').val('');
					$(this).closest('.enrollment-row').find('.enr-batch').html('<option value="">Choose classroom first</option>').prop('disabled', true);
					return;
				}
				$(this).closest('.enrollment-row').remove();
			});

			$(document).on('click', '.open-student-enrollments', function() {
				const studentId = $(this).data('student-id');
				let maps = $(this).attr('data-maps');
				let parsed = [];
				try {
					parsed = maps ? JSON.parse(maps) : [];
				} catch (e) {
					parsed = [];
				}
				$('#enr_student_id').val(studentId);
				$('#student-enrollment-rows').empty();
				if (parsed && parsed.length) {
					parsed.forEach(function(m) {
						appendEnrollmentRow(m.classroom_id, m.batch_id);
					});
				} else {
					appendEnrollmentRow('', '');
				}
				studentEnrollmentsModal.show();
			});

			$(document).on('click', '#saveStudentEnrollmentsBtn', function() {
				const formData = $('#studentEnrollmentsForm').serializeArray();
				formData.push({ name: '_token', value: '{{ csrf_token() }}' });
				$.post('{{ url("user/teacher/students/sync-enrollments") }}', formData, function(res) {
					if (res.status == 1) {
						studentEnrollmentsModal.hide();
						location.reload();
					} else if (res.error) {
						alert(res.error);
					}
				}, 'json');
			});

			// Clear form on hide
			$('#studentModal').on('hidden.bs.modal', function () {
				$('#student-form')[0].reset();
				$('#student_id').val('');
				$('#studentModalTitle').text('Add Student');
				clearAjaxErrors();
			});

			// Edit button
			$(document).on('click', '.edit-student', function() {
				const id = $(this).data('id');
				const name = $(this).data('name');
				const email = $(this).data('email');
				const phone = $(this).data('phone');
				const parent_name = $(this).data('parent_name') || '';
				const parent_email = $(this).data('parent_email') || '';
				const parent_phone = $(this).data('parent_phone') || '';

				$('#student_id').val(id);
				$('#student_name').val(name);
				$('#student_email').val(email);
				$('#student_phone').val(phone);
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

			$('#btn-portal-import-students-upload').on('click', function() {
				const fileInput = document.getElementById('portal_import_student_csv_file');
				const file = fileInput.files[0];
				const $btn = $(this);
				const $msg = $('#portal-import-student-msg');
				$msg.html('');

				if (!file) {
					$msg.html('<div class="alert alert-danger mb-0">Choose a CSV file.</div>');
					return;
				}

				const data = new FormData();
				data.append('_token', '{{ csrf_token() }}');
				data.append('file', file);

				$btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Uploading...');
				$.ajax({
					url: '{{ url("user/teacher/students/bulk-upload") }}',
					type: 'POST',
					data: data,
					processData: false,
					contentType: false,
					dataType: 'json',
					success: function(res) {
						$btn.prop('disabled', false).html(
							'<i class="icon-base ti tabler-upload me-1"></i> Upload');
						if (res.status == 1) {
							let html = '<div class="alert alert-success mb-2">' + escapeHtml(res.msg || 'Upload completed') + '</div>';
							if (res.errors && res.errors.length) {
								const items = res.errors.slice(0, 15).map(function(err) {
									return '<li>' + escapeHtml(err) + '</li>';
								}).join('');
								html +=
									'<div class="alert alert-warning mb-0"><strong>Row notes:</strong><ul class="mb-0 mt-2 small">' +
									items + '</ul></div>';
							}
							$msg.html(html);
							const created = Number(res.summary && res.summary.created ? res.summary.created : 0);
							const updated = Number(res.summary && res.summary.updated ? res.summary.updated : 0);
							if (created > 0 || updated > 0) {
								setTimeout(function() { location.reload(); }, 1500);
							}
						} else if (res.error_array) {
							const firstKey = Object.keys(res.error_array)[0];
							const firstErr = res.error_array[firstKey] ? res.error_array[firstKey][0] : 'Validation failed';
							$msg.html('<div class="alert alert-danger mb-0">' + escapeHtml(firstErr) + '</div>');
						} else {
							let html = '<div class="alert alert-danger mb-2">' + escapeHtml(res.error || 'Upload failed') + '</div>';
							if (res.errors && res.errors.length) {
								const items = res.errors.slice(0, 15).map(function(err) {
									return '<li>' + escapeHtml(err) + '</li>';
								}).join('');
								html += '<div class="alert alert-warning mb-0"><ul class="mb-0 small">' + items + '</ul></div>';
							}
							$msg.html(html);
						}
					},
					error: function(xhr) {
						$btn.prop('disabled', false).html(
							'<i class="icon-base ti tabler-upload me-1"></i> Upload');
						let serverMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : xhr.statusText;
						$msg.html('<div class="alert alert-danger mb-0">' + escapeHtml(serverMsg || 'Upload failed.') + '</div>');
					}
				});
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
