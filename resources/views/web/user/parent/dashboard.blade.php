@php
    $student = $student ?? null;
@endphp
@extends('web.user.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base ti tabler-school icon-28px"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ (int) ($student_classroom_count ?? 0) }}</h4>
                                <p class="mb-0 text-body-secondary small">
                                    Classrooms 
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base ti tabler-stack icon-28px"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ (int) ($student_batch_count ?? 0) }}</h4>
                                <p class="mb-0 text-body-secondary small">
                                    Batches
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Shortcuts</h5>
                        <p class="card-subtitle mb-0">Jump to a page</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/parent/classrooms') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-school icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Classrooms</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/parent/events') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-calendar icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Events</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/parent/leave') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-calendar-off icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Student leave</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/parent/complaints') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-message-report icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Complaints</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/parent/fees') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-receipt icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Fees</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/profile') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-user icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Profile</span>
                                </a>
                            </div>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ url('user/security') }}"
                                    class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                    <span class="badge bg-label-secondary rounded p-2">
                                        <i class="icon-base ti tabler-shield-lock icon-md"></i>
                                    </span>
                                    <span class="fw-medium small">Security</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
