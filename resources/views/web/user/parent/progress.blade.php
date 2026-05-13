@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
        <div class="row g-4 mb-2">
            <div class="col-12">
                <h4 class="mb-1">Progress</h4>
                <p class="mb-0 text-body-secondary small">
                    @if ($student ?? null)
                        Exam history and charts for <span class="fw-medium text-heading">{{ $student->name }}</span>.
                    @else
                        Exam history and charts for the selected student.
                    @endif
                </p>
            </div>
        </div>
        @include('web.user.partials.dashboard_progress_tab', ['progress_chart_suffix' => 'par'])
    </div>
@endsection
