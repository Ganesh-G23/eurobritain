@extends('web.user.layouts.app')

@section('title', 'Welcome to EliteGrade')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
        <div class="row g-6">
            <!-- Card Border Shadow -->


            <div class="col-lg-3 col-sm-6">
                <div class="card card-border-shadow-primary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="icon-base ti tabler-school icon-28px"></i>
                                </span>
                            </div>
                            <h4 class="mb-0">{{ $total_classrooms }}</h4>
                        </div>
                        <p class="mb-1">Classrooms</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card card-border-shadow-primary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="icon-base ti tabler-stack icon-28px"></i>
                                </span>
                            </div>
                            <h4 class="mb-0">{{ $total_batches }}</h4>
                        </div>
                        <p class="mb-1">Batches</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card card-border-shadow-primary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="icon-base ti tabler-users icon-28px"></i>
                                </span>

                            </div>
                            <h4 class="mb-0">{{ $total_students }}</h4>
                        </div>
                        <p class="mb-1">Students</p>
                    </div>
                </div>
            </div>





            <!-- <div class="col-lg-3 col-sm-6">
                                        <div class="card card-border-shadow-warning h-100">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="avatar me-4">
                                                        <span class="avatar-initial rounded bg-label-warning"><i
                                                                class="icon-base ti tabler-alert-triangle icon-28px"></i></span>
                                                    </div>
                                                    <h4 class="mb-0">8</h4>
                                                </div>
                                                <p class="mb-1">Vehicles with errors</p>
                                                <p class="mb-0">
                                                    <span class="text-heading fw-medium me-2">-8.7%</span>
                                                    <small class="text-body-secondary">than last week</small>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="card card-border-shadow-danger h-100">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="avatar me-4">
                                                        <span class="avatar-initial rounded bg-label-danger"><i
                                                                class="icon-base ti tabler-git-fork icon-28px"></i></span>
                                                    </div>
                                                    <h4 class="mb-0">27</h4>
                                                </div>
                                                <p class="mb-1">Deviated from route</p>
                                                <p class="mb-0">
                                                    <span class="text-heading fw-medium me-2">+4.3%</span>
                                                    <small class="text-body-secondary">than last week</small>
                                                </p>
                                            </div>
                                        </div>
                                    </div> -->

        </div>


    </div>
    <div class="container-xxl flex-grow-1 container-p-y pt-0 pb-0">
    <div class="row g-6 mt-0">
        @foreach($total_classrooms_details as $classroom)
        <div class="col-lg-2 col-6 mb-lg-0">
            <div class="card h-100">
                <div class="card-body">
                    <div class="badge p-2 bg-label-danger mb-3 rounded">
                        <i class="icon-base ti tabler-school icon-28px"></i>
                    </div>
                    <h5 class="card-title mb-1"><a href="{{ url('user/teacher/classrooms/details/' . $classroom->id) }}">{{ $classroom->name }}</a></h5>
                    <p class="text-heading mb-3 mt-1">{{ $classroom->batches->count() }} Batches</p>
                    <p class="text-heading mb-3 mt-1">{{ $classroom->students->count() }} Students</p>
                </div>
            </div>
        </div>
        @endforeach

        
    </div>
    </div>
@endsection
