@extends('web.user.layouts.app')
@section('title', 'Welcome to EliteGrade')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
        <div class="row g-6">
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base ti tabler-school icon-28px"></i>
                                </span>
                            </div>
                            <h4 class="mb-0"><a href="{{ url('user/teacher/classrooms') }}">{{ $total_classrooms }}</a>
                            </h4>
                        </div>
                        <p class="mb-1"><a href="{{ url('user/teacher/classrooms') }}">Classrooms</a></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base ti tabler-stack icon-28px"></i>
                                </span>
                            </div>
                            <h4 class="mb-0"><a href="{{ url('user/teacher/batches') }}">{{ $total_batches }}</a></h4>
                        </div>
                        <p class="mb-1"><a href="{{ url('user/teacher/batches') }}">Batches</a></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base ti tabler-users icon-28px"></i>
                                </span>

                            </div>
                            <h4 class="mb-0"><a href="{{ url('user/teacher/students') }}">{{ $total_students }}</a></h4>
                        </div>
                        <p class="mb-1"><a href="{{ url('user/teacher/students') }}">Students</a></p>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mt-6 mb-3">
                <div>
                    <h5 class="mb-1">Classrooms</h5>
                    <p class="mb-0 text-body-secondary small">Open a classroom to view batches and students</p>
                </div>

            </div>

            <div class="row g-4">
                @forelse ($total_classrooms_details as $classroom)
                    <div class="col-md-6 col-xl-4">
                        <a href="{{ url('user/teacher/classrooms/details/' . $classroom->id) }}"
                            class="card h-100 text-reset text-decoration-none">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="avatar me-3 flex-shrink-0">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="icon-base ti tabler-school icon-28px"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 min-w-0 pt-1">
                                        <h5 class="mb-0 text-heading text-truncate">{{ $classroom->name }}</h5>
                                        <small class="text-body-secondary">Classroom</small>
                                    </div>
                                    <i class="icon-base ti tabler-chevron-right text-body-secondary flex-shrink-0 mt-2"></i>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-xs me-2">
                                                <span class="avatar-initial rounded bg-label-success">
                                                    <i class="icon-base ti tabler-stack icon-18px"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="mb-0">{{ (int) ($classroom->batches->count() ?? 0) }}</h5>
                                                <small class="text-body-secondary">Batches</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-xs me-2">
                                                <span class="avatar-initial rounded bg-label-info">
                                                    <i class="icon-base ti tabler-users icon-18px"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="mb-0">{{ (int) ($classroom->enrolled_student_count ?? 0) }}
                                                </h5>
                                                <small class="text-body-secondary">Students</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <div class="avatar avatar-lg mx-auto mb-3">
                                    <span class="avatar-initial rounded-circle bg-label-secondary">
                                        <i class="icon-base ti tabler-school icon-28px"></i>
                                    </span>
                                </div>
                                <h6 class="mb-2">No classrooms yet</h6>
                                <p class="text-body-secondary small mb-4">Create your first classroom to organize batches
                                    and students.</p>
                                <a href="{{ url('user/teacher/classrooms') }}" class="btn btn-primary">
                                    <i class="icon-base ti tabler-plus me-1"></i> Add classroom
                                </a>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

        </div>
    @endsection
