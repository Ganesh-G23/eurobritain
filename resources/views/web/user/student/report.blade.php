@extends('web.user.student.layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
	<h4 class="mb-1">{{ $title ?? 'My Reports' }}</h4>
	<p class="text-body-secondary small mb-4">
		Download academic reports as PDF.
		@if($context_label ?? null)
			<span class="d-block mt-1">{{ $context_label }}</span>
		@endif
	</p>

	@include('web.user.partials.academic_reports_cards', [
		'download_base_url' => $download_base_url ?? url('user/student/report/download'),
	])
</div>
@endsection
