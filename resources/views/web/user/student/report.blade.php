@extends('web.user.student.layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
	<h4 class="mb-3">{{ $title ?? 'My Reports' }}</h4>
	@if($teacher)
		<p class="text-muted mb-3">Teacher: <strong>{{ $teacher->name }}</strong> ({{ $teacher->email }})</p>
	@endif

	<div class="card">
		<div class="card-body">
			@if(($reports ?? collect())->count() === 0)
				<div class="text-center text-muted py-4">No reports available yet.</div>
			@else
				<div class="table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th>Report</th>
								<th>Date</th>
								<th>Score</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							@foreach($reports as $rep)
								<tr>
									<td>{{ $rep->title }}</td>
									<td>{{ $rep->date }}</td>
									<td>{{ $rep->score }}</td>
									<td>{{ $rep->status }}</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			@endif
		</div>
	</div>
</div>
@endsection

