@extends('web.user.layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
	<div class="d-flex align-items-center justify-content-between mb-3">
		<h4 class="mb-0">{{ $title ?? 'Attendance' }}</h4>
	</div>

	<div class="card mb-3">
		<div class="card-body">
			<div class="row g-3">
				<div class="col-sm-4">
					<label class="form-label">Classroom</label>
					<select class="form-select">
						<option value="">All</option>
						@foreach(($filters['classrooms'] ?? []) as $c)
							<option value="{{ $c->id }}">{{ $c->name }}</option>
						@endforeach
					</select>
				</div>
				<div class="col-sm-4">
					<label class="form-label">Batch</label>
					<select class="form-select">
						<option value="">All</option>
						@foreach(($filters['batches'] ?? []) as $b)
							<option value="{{ $b->id }}">{{ $b->name }}</option>
						@endforeach
					</select>
				</div>
				<div class="col-sm-4 d-flex align-items-end">
					<button class="btn btn-primary me-2">Filter</button>
					<button class="btn btn-label-secondary">Reset</button>
				</div>
			</div>
		</div>
	</div>

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
							<th>Student</th>
							<th>Classroom</th>
							<th>Batch</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						@if(($records ?? collect())->count() === 0)
							<tr>
								<td colspan="5" class="text-center text-muted">No attendance records yet.</td>
							</tr>
						@else
							@foreach($records as $r)
								<tr>
									<td>{{ $r->date }}</td>
									<td>{{ $r->student_name }}</td>
									<td>{{ $r->classroom_name }}</td>
									<td>{{ $r->batch_name }}</td>
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

