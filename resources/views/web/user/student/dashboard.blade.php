@extends('web.user.student.layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 pt-2 pb-3">
	<div class="d-flex align-items-center justify-content-between">
		<h4 class="mb-0">{{ $title ?? 'Dashboard' }}</h4>
	</div>

	@if($teacher)
		<div class="card mb-3">
			<div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
				<div>
					<div class="fw-bold">Selected Teacher</div>
					<div>{{ $teacher->name }} ({{ $teacher->email }})</div>
					@if($teacher->phone)<div class="text-muted small">Phone: {{ $teacher->phone }}</div>@endif
				</div>
				<div class="d-flex gap-2">
					<a class="btn btn-primary" href="{{ url('user/student/attendance') }}">View Attendance</a>
					<a class="btn btn-label-secondary" href="{{ url('user/student/report') }}">View Report</a>
				</div>
			</div>
		</div>
	@endif

	<div class="row g-3">
		<div class="col-sm-4">
			<div class="card">
				<div class="card-body">
					<div class="text-muted">Total Classes</div>
					<div class="h4 mb-0">{{ $stats['totalClasses'] ?? 0 }}</div>
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="card">
				<div class="card-body">
					<div class="text-muted">Attendance Rate</div>
					<div class="h4 mb-0">{{ $stats['attendanceRate'] ?? 0 }}%</div>
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="card">
				<div class="card-body">
					<div class="text-muted">Reports Available</div>
					<div class="h4 mb-0">{{ $stats['reportsAvailable'] ?? 0 }}</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

