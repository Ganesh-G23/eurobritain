@extends('web.user.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">Classrooms</h5>
                        <p class="mb-0 text-body-secondary small">
                            Classrooms for <span class="fw-semibold text-heading">{{ $student->name ?? 'your child' }}</span>
                            across their teachers
                        </p>
                    </div>
                </div>

                <div class="row g-4">
                    @forelse (($parent_classrooms ?? collect()) as $classroom)
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
                                            <small class="text-body-secondary d-block text-truncate">
                                                {{ $classroom->teacher_name ?? 'Teacher' }}
                                            </small>
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
                                <a href="{{ url('user/parent/classroom/' . $classroom->id) }}" class="stretched-link"
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
                                    <h6 class="mb-2">No classrooms yet</h6>
                                    <p class="text-body-secondary small mb-0">
                                        This student is not enrolled in any classroom mappings yet.
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
