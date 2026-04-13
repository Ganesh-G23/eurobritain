@extends('web.user.student.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 pb-2 pt-0">
        <!-- <div class="row g-6 mb-4">
            <div class="col-12">
                <h4 class="mb-1">{{ $title ?? 'Student Dashboard' }}</h4>
                @if ($teacher)
    <p class="mb-0 text-body-secondary">
                        Selected teacher: <span class="fw-semibold text-heading">{{ $teacher->name }}</span>
                    </p>
    @endif
            </div>
        </div> -->

        <!-- <div class="row g-6 mb-4">
            <div class="col-lg-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base ti tabler-school icon-28px"></i>
                                </span>
                            </div>
                            <h4 class="mb-0">{{ (int) ($total_classrooms ?? 0) }}</h4>
                        </div>
                        <p class="mb-1">Classrooms</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base ti tabler-stack icon-28px"></i>
                                </span>
                            </div>
                            <h4 class="mb-0">{{ (int) ($total_batches ?? 0) }}</h4>
                        </div>
                        <p class="mb-1">Batches</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base ti tabler-users icon-28px"></i>
                                </span>
                            </div>
                            <h4 class="mb-0">{{ (int) ($total_students ?? 0) }}</h4>
                        </div>
                        <p class="mb-1">Students in these classrooms</p>
                    </div>
                </div>
            </div>
        </div> -->
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">My Classrooms</h5>
                        <p class="mb-0 text-body-secondary small">Classrooms available under your selected teacher</p>
                    </div>
                </div>

                <div class="row g-4">
                    @forelse (($student_classrooms ?? collect()) as $classroom)
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 position-relative">
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
                                                    <h5 class="mb-0">{{ (int) ($classroom->total_batches ?? 0) }}</h5>
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
                                                    <h5 class="mb-0">{{ (int) ($classroom->total_students ?? 0) }}</h5>
                                                    <small class="text-body-secondary">Students</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ url('user/student/classroom/' . $classroom->id) }}" class="stretched-link"
                                    aria-label="View classroom {{ $classroom->name }}"></a>
                            </div>
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
                                    <h6 class="mb-2">No classrooms available</h6>
                                    <p class="text-body-secondary small mb-0">
                                        You are not mapped to any classroom for this teacher yet.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
