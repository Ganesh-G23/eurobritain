@php
    $student = $student ?? null;
@endphp
@extends('web.user.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Parent Dashboard</h5>
                    </div>
                    @if ($student)
                        <div class="card-body">
                                <div class="mb-2"><strong>Name:</strong> {{ $student->name }}</div>
                                <div class="mb-2"><strong>Email:</strong> {{ $student->email }}</div>
                                <div class="mb-2"><strong>Phone:</strong> {{ $student->phone }}</div>
                            </div>
                        @else
                            <p>No student selected. <a href="{{ url('user/select-student') }}">Select a student</a>.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
