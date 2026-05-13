@extends('web.user.student.layouts.app')
@section('title', $title ?? 'Progress')
@section('content')
    <div class="container-xxl flex-grow-1 pb-2 pt-0">
        <div class="row g-4 mb-2">
            <div class="col-12">
                <h4 class="mb-1">Progress</h4>
                <p class="mb-0 text-body-secondary small">
                    @if ($teacher)
                        Assessments and trends for classes with <span class="fw-medium text-heading">{{ $teacher->name }}</span>.
                    @else
                        Assessments and trends for your selected teacher context.
                    @endif
                </p>
            </div>
        </div>
        @include('web.user.partials.dashboard_progress_tab', ['progress_chart_suffix' => 'stu'])
    </div>
@endsection
