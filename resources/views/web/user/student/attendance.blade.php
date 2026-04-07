@extends('web.user.student.layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
	<h4 class="mb-3">{{ $title ?? 'My Attendance' }}</h4>
	@if($teacher)
		<p class="text-muted mb-3">Teacher: <strong>{{ $teacher->name }}</strong> ({{ $teacher->email }})</p>
	@endif

	<div class="row g-3 mb-3">
		<div class="col-sm-4">
			<div class="card">
				<div class="card-body d-flex align-items-center justify-content-between">
					<span>Present</span>
					<strong>{{ $summary['present'] ?? 0 }}</strong>
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="card">
				<div class="card-body d-flex align-items-center justify-content-between">
					<span>Absent</span>
					<strong>{{ $summary['absent'] ?? 0 }}</strong>
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="card">
				<div class="card-body d-flex align-items-center justify-content-between">
					<span>Late</span>
					<strong>{{ $summary['late'] ?? 0 }}</strong>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table">
					<thead>
						<tr>
							<th>Date</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						@if(($records ?? collect())->count() === 0)
							<tr><td colspan="2" class="text-center text-muted">No attendance records yet.</td></tr>
						@else
							@foreach($records as $r)
								<tr>
									<td>{{ $r->date }}</td>
									<td>{{ $r->status }}</td>
								</tr>
							@endforeach
						@endif
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
@endsection

