@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6 mb-6">
            <div class="col-lg-4 col-md-6">
                <a href="{{ url('admin/student') }}" class="card h-100 text-body text-decoration-none">
                    <div class="card-body">
                        <div class="badge p-2 bg-label-primary mb-3 rounded">
                            <i class="icon-base ti tabler-school icon-28px"></i>
                        </div>
                        <h5 class="card-title mb-1">Students</h5>
                        <p class="card-subtitle mb-0">Total portal students</p>
                        <p class="text-heading mb-0 mt-3">{{ number_format($studentCount) }}</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="{{ url('admin/teacher') }}" class="card h-100 text-body text-decoration-none">
                    <div class="card-body">
                        <div class="badge p-2 bg-label-info mb-3 rounded">
                            <i class="icon-base ti tabler-users icon-28px"></i>
                        </div>
                        <h5 class="card-title mb-1">Teachers</h5>
                        <p class="card-subtitle mb-0">Total portal teachers</p>
                        <p class="text-heading mb-0 mt-3">{{ number_format($teacherCount) }}</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="badge p-2 bg-label-warning mb-3 rounded">
                            <i class="icon-base ti tabler-shield-lock icon-28px"></i>
                        </div>
                        <h5 class="card-title mb-1">Admins</h5>
                        <p class="card-subtitle mb-0">Panel accounts (users)</p>
                        <p class="text-heading mb-0 mt-3">{{ number_format($adminCount) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-6">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Shortcuts</h5>
                        <p class="card-subtitle mb-0">Jump to a page</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($shortcuts as $item)
                                <div class="col-6 col-md-4 col-lg-3">
                                    <a href="{{ $item['url'] }}"
                                        class="card shadow-none border h-100 text-body text-decoration-none text-center py-4 px-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                        <span class="badge bg-label-secondary rounded p-2">
                                            <i class="icon-base ti {{ $item['icon'] }} icon-md"></i>
                                        </span>
                                        <span class="fw-medium small">{{ $item['label'] }}</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
