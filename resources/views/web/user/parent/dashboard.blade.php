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
    </div>
@endsection
