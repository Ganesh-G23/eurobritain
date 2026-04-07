@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-4 align-items-stretch">
            <!-- Left Side - Teacher Details -->
            <div class="col-lg-4 col-md-5 d-flex">
                <div class="card mb-4 w-100">
                    <div class="card-body text-center">
                        <div class="avatar avatar-xl mb-3 mx-auto">
                            <span class="avatar-initial rounded-circle bg-label-primary" style="font-size: 3rem; width: 80px; height: 80px; line-height: 80px; display: flex; align-items: center; justify-content: center;">
                                {{ strtoupper(substr($teacher->name, 0, 1)) }}
                            </span>
                        </div>
                        <h4 class="mb-2">{{ $teacher->name }}</h4>
                        <p class="mb-4">
                            <span class="badge bg-label-primary">Teacher</span>
                        </p>
                    </div>
                    <hr class="my-0">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold d-block mb-2">
                                <i class="icon-base ti tabler-mail me-1"></i> Email
                            </label>
                            <p class="mb-0">{{ $teacher->email ?? '-' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold d-block mb-2">
                                <i class="icon-base ti tabler-phone me-1"></i> Phone Number
                            </label>
                            <p class="mb-0">{{ $teacher->phone ?? '-' }}</p>
                        </div>
                        <div class="mb-0">
                            <p class="mb-1">
                                <span class="fw-semibold">Teacher ID :</span> #{{ $teacher->id }}
                            </p>
                            <p class="mb-1">
                                <span class="fw-semibold">Created At :</span> {{ \Carbon\Carbon::parse($teacher->created_at)->setTimezone(config('app.timezone', 'Asia/Kolkata'))->format('d-m-Y') }}
                            </p>
                            <p class="mb-0">
                                <span class="fw-semibold">Updated At :</span> {{ \Carbon\Carbon::parse($teacher->updated_at)->setTimezone(config('app.timezone', 'Asia/Kolkata'))->format('d-m-Y') }}
                            </p>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-grid gap-2">
                            <a href="{{ url('admin/teacher/form?id=' . base64_encode($teacher->id)) }}" class="btn btn-primary">
                                <i class="icon-base ti tabler-edit me-1"></i> Edit Teacher
                            </a>
                            <a href="{{ url('admin/teacher') }}" class="btn btn-label-secondary">
                                <i class="icon-base ti tabler-arrow-left me-1"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Tab Panel -->
            <div class="col-lg-8 col-md-7 d-flex flex-column">
                <!-- Action Dropdown -->
                <div class="d-flex justify-content-end mb-3">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="icon-base ti tabler-plus me-1"></i> Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                    <i class="icon-base ti tabler-user-plus me-2"></i> Add Student
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#addClassroomModal">
                                    <i class="icon-base ti tabler-building me-2"></i> Add Classroom
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#addBatchModal">
                                    <i class="icon-base ti tabler-users-group me-2"></i> Add Batch
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tab Panel -->
                <div class="card tab-panel-card flex-grow-1 d-flex flex-column">
                    <div class="card-header border-bottom">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" role="tab" aria-selected="true">
                                    <i class="icon-base ti tabler-dashboard me-1"></i> Overview
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#classrooms" role="tab" aria-selected="false">
                                    <i class="icon-base ti tabler-building me-1"></i> Classrooms
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#batches" role="tab" aria-selected="false">
                                    <i class="icon-base ti tabler-users-group me-1"></i> Batches
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#students" role="tab" aria-selected="false">
                                    <i class="icon-base ti tabler-users me-1"></i> Students
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content flex-grow-1">
                        <!-- Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="card bg-label-primary">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">Total Students</h5>
                                                        <h3 class="mb-0">{{ $total_students ?? 0 }}</h3>
                                                    </div>
                                                    <div class="avatar avatar-lg">
                                                        <span class="avatar-initial rounded-circle bg-primary">
                                                            <i class="icon-base ti tabler-users"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-label-info">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">Total Classrooms</h5>
                                                        <h3 class="mb-0">{{ $total_classrooms ?? 0 }}</h3>
                                                    </div>
                                                    <div class="avatar avatar-lg">
                                                        <span class="avatar-initial rounded-circle bg-info">
                                                            <i class="icon-base ti tabler-building"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-label-success">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">Total Batches</h5>
                                                        <h3 class="mb-0">{{ $total_batches ?? 0 }}</h3>
                                                    </div>
                                                    <div class="avatar avatar-lg">
                                                        <span class="avatar-initial rounded-circle bg-success">
                                                            <i class="icon-base ti tabler-users-group"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>

                        <!-- Students Tab -->
                        <div class="tab-pane fade" id="students" role="tabpanel">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Students</h6>
                                    <div class="d-flex gap-2">
                                        <a href="{{ url('admin/teacher/students/bulk-sample?teacher_id=' . $teacher->id) }}" class="btn btn-sm btn-label-secondary">
                                            <i class="icon-base ti tabler-download me-1"></i> Download Sample
                                        </a>
                                        <button type="button" class="btn btn-sm btn-label-primary" data-bs-toggle="modal" data-bs-target="#adminBulkStudentModal">
                                            <i class="icon-base ti tabler-upload me-1"></i> Bulk Upload
                                        </button>
                                    </div>
                                </div>
                                <div class="students-table-wrap">
                                    <table class="table table-hover students-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(count($students) > 0)
                                                @foreach($students as $index => $student)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ $student->name }}</td>
                                                        <td>{{ $student->email }}</td>
                                                        <td>{{ $student->phone }}</td>
                                                        <td>
                                                            <div class="d-flex gap-1">
                                                                <a class="btn btn-sm btn-icon btn-label-info"
                                                                   href="{{ url('admin/teacher/student_view?id=' . base64_encode($student->id) . '&teacher_id=' . base64_encode($teacher->id)) }}"
                                                                   title="View">
                                                                    <i class="icon-base ti tabler-eye"></i>
                                                                </a>
                                                                @php
                                                                    $parentRow = null;
                                                                    if (!empty($student->parent_id)) {
                                                                        $parentRow = \App\Models\PortalUser::find($student->parent_id);
                                                                    }
                                                                @endphp
                                                                <button class="btn btn-sm btn-icon btn-label-primary edit-student" 
                                                                        data-id="{{ base64_encode($student->id) }}"
                                                                        data-name="{{ $student->name }}"
                                                                        data-email="{{ $student->email }}"
                                                                        data-phone="{{ $student->phone }}"
                                                                        data-classroom-id="{{ $student->classroom_id ?? optional($student->batch)->classroom_id ?? '' }}"
                                                                        data-batch-id="{{ $student->batch_id }}"
                                                                        data-parent_name="{{ $parentRow->name ?? '' }}"
                                                                        data-parent_email="{{ $parentRow->email ?? '' }}"
                                                                        data-parent_phone="{{ $parentRow->phone ?? '' }}"
                                                                        title="Edit">
                                                                    <i class="icon-base ti tabler-edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-icon btn-label-danger delete-student" 
                                                                        data-id="{{ base64_encode($student->id) }}"
                                                                        title="Delete">
                                                                    <i class="icon-base ti tabler-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="7" class="text-center">No students found</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Classrooms Tab -->
                        <div class="tab-pane fade" id="classrooms" role="tabpanel">
                            <div class="card-body">
                                <div class="row g-4">
                                    @if(count($classrooms) > 0)
                                        @foreach($classrooms as $classroom)
                                            <div class="col-md-6 col-lg-4">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                                            <h5 class="card-title mb-0">{{ $classroom->name }}</h5>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="icon-base ti tabler-calendar me-2 text-info"></i>
                                                            <span class="text-muted">
                                                                {{ \Carbon\Carbon::parse($classroom->created_at)->setTimezone(config('app.timezone', 'Asia/Kolkata'))->format('d-m-Y') }}
                                                            </span>
                                                        </div>
                                                        <div class="d-flex gap-1">
                                                                <button class="btn btn-sm btn-icon btn-label-primary edit-classroom" 
                                                                        data-id="{{ base64_encode($classroom->id) }}"
                                                                        data-name="{{ $classroom->name }}"
                                                                        data-teacher-id="{{ $classroom->teacher_id }}"
                                                                        title="Edit"
                                                                        style="color: #696cff;">
                                                                    <i class="icon-base ti tabler-edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-icon btn-label-danger delete-classroom" 
                                                                        data-id="{{ base64_encode($classroom->id) }}"
                                                                        title="Delete"
                                                                        style="color: #ff3e1d;">
                                                                    <i class="icon-base ti tabler-trash"></i>
                                                                </button>
                                                            </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="col-12">
                                            <div class="text-center py-5">
                                                <i class="icon-base ti tabler-building text-muted" style="font-size: 3rem;"></i>
                                                <p class="text-muted mt-3">No classrooms found</p>
                                            </div>
                                        </div>
                                    @endif

                                    
                                </div>
                            </div>
                        </div>

                        <!-- Batches Tab -->
                        <div class="tab-pane fade" id="batches" role="tabpanel">
                            <div class="card-body">
                                <div class="row g-3">
                                    @if(count($batches) > 0)
                                        @foreach($batches as $batch)
                                        <div class="col-auto">
                                            <div class="card batch-card">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0 small"><strong>{{ $batch->name }}</strong></h6>
                                                    </div>
                                                    <div class="mb-2">
                                                        <div class="mb-1">
                                                            <strong>Classroom:</strong> {{ optional($batch->classroom)->name ?? '-' }}
                                                        </div>
                                                        <small class="text-muted d-block mb-1">
                                                            <strong>Status:</strong> 
                                                            <span class="badge bg-label-{{ $batch->status == 'active' ? 'success' : ($batch->status == 'pending' ? 'warning' : 'secondary') }} badge-sm">
                                                                {{ ucfirst($batch->status) }}
                                                            </span>
                                                        </small>
                                                        @if($batch->schedule)
                                                        <small class="text-muted d-block">
                                                            <i class="icon-base ti tabler-calendar me-1"></i>
                                                            {{ $batch->schedule }}
                                                        </small>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex gap-1">
                                                        <button class="btn btn-sm btn-icon btn-label-primary edit-batch" 
                                                                data-id="{{ base64_encode($batch->id) }}"
                                                                data-name="{{ $batch->name }}"
                                                                data-classroom-id="{{ $batch->classroom_id }}"
                                                                data-status="{{ $batch->status }}"
                                                                data-schedule="{{ $batch->schedule }}">
                                                            <i class="icon-base ti tabler-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-icon btn-label-danger delete-batch" 
                                                                data-id="{{ base64_encode($batch->id) }}">
                                                            <i class="icon-base ti tabler-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="col-12">
                                            <div class="card h-100 border-dashed">
                                                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 220px;">
                                                    <div class="mb-3">
                                                        <i class="icon-base ti tabler-users-group text-muted" style="font-size: 3rem;"></i>
                                                    </div>
                                                    <h6 class="mb-2">No batches found</h6>
                                                    <p class="text-muted small mb-0">No batches available for this teacher.</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Classroom Modal -->
    <div class="modal fade" id="addClassroomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Classroom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addClassroomForm">
                        <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Classroom Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                            <span class="ajax-error text-danger small"></span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveClassroomBtn">Add Classroom</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Classroom Modal -->
    <div class="modal fade" id="editClassroomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Classroom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editClassroomForm">
                        <input type="hidden" name="id" id="edit_classroom_id">
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Classroom Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit_classroom_name" required>
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Teacher <span class="text-danger">*</span></label>
                            <select class="form-select" name="teacher_id" id="edit_classroom_teacher_id" required>
                                @foreach($teachers as $teacherOption)
                                    <option value="{{ $teacherOption->id }}">{{ $teacherOption->name }}</option>
                                @endforeach
                            </select>
                            <span class="ajax-error text-danger small"></span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateClassroomBtn">Update Classroom</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Batch Modal -->
    <div class="modal fade" id="addBatchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addBatchForm">
                        <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Batch Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Select Classroom <span class="text-danger">*</span></label>
                            <select class="form-select" name="classroom_id" required>
                                <option value="">Choose Classroom</option>
                                @foreach($classrooms as $classroom)
                                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                                @endforeach
                            </select>
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Schedule</label>
                            <input type="text" class="form-control" name="schedule" placeholder="e.g., Mon-Fri 9:00 AM - 5:00 PM">
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <span class="ajax-error text-danger small"></span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveBatchBtn">Add Batch</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Batch Modal -->
    <div class="modal fade" id="editBatchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editBatchForm">
                        <input type="hidden" name="id" id="edit_batch_id">
                        <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Batch Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit_batch_name" required>
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Select Classroom <span class="text-danger">*</span></label>
                            <select class="form-select" name="classroom_id" id="edit_batch_classroom_id" required>
                                <option value="">Choose Classroom</option>
                                @foreach($classrooms as $classroom)
                                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                                @endforeach
                            </select>
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="edit_batch_status" required>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Schedule</label>
                            <input type="text" class="form-control" name="schedule" id="edit_batch_schedule" placeholder="e.g., Mon-Fri 9:00 AM - 5:00 PM">
                            <span class="ajax-error text-danger small"></span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateBatchBtn">Update Batch</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm">
                        <div class="row g-3">
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Student Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                                <span class="ajax-error text-danger small"></span>
                            </div>
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required>
                                <span class="ajax-error text-danger small"></span>
                            </div>
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="phone" required>
                                <span class="ajax-error text-danger small"></span>
                            </div>

                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Select Classroom <span class="text-danger">*</span></label>
                                <select class="form-select" name="classroom_id" id="add_student_classroom_id" required>
                                    <option value="">Choose Classroom</option>
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                                <span class="ajax-error text-danger small"></span>
                            </div>
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Select Batch <span class="text-danger">*</span></label>
                                <select class="form-select" name="batch_id" id="add_student_batch_id" required disabled>
                                    <option value="">First select a classroom</option>
                                </select>
                                <span class="ajax-error text-danger small"></span>
                            </div>

                            <div class="col-md-4"></div>

                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Parent Name</label>
                                <input type="text" class="form-control" name="parent_name">
                                <span class="ajax-error text-danger small"></span>
                            </div>
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Parent Email</label>
                                <input type="email" class="form-control" name="parent_email" placeholder="parent@example.com">
                                <span class="ajax-error text-danger small"></span>
                            </div>
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Parent Phone</label>
                                <input type="text" class="form-control" name="parent_phone" placeholder="e.g., 9876543210">
                                <span class="ajax-error text-danger small"></span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveStudentBtn">Add Student</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm">
                        <input type="hidden" name="id" id="edit_student_id">
                        <div class="row g-3">
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Student Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="edit_student_name" required>
                                <span class="ajax-error text-danger small"></span>
                            </div>
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" id="edit_student_email" required>
                                <span class="ajax-error text-danger small"></span>
                            </div>
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="phone" id="edit_student_phone" required>
                                <span class="ajax-error text-danger small"></span>
                            </div>

                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Select Classroom <span class="text-danger">*</span></label>
                                <select class="form-select" name="classroom_id" id="edit_student_classroom_id" required>
                                    <option value="">Choose Classroom</option>
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                                <span class="ajax-error text-danger small"></span>
                            </div>
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Select Batch <span class="text-danger">*</span></label>
                                <select class="form-select" name="batch_id" id="edit_student_batch_id" required>
                                    <option value="">Choose Batch</option>
                                </select>
                                <span class="ajax-error text-danger small"></span>
                            </div>

                            <div class="col-md-4"></div>

                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Parent Name</label>
                                <input type="text" class="form-control" name="parent_name" id="edit_parent_name">
                                <span class="ajax-error text-danger small"></span>
                            </div>
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Parent Email</label>
                                <input type="email" class="form-control" name="parent_email" id="edit_parent_email" placeholder="parent@example.com">
                                <span class="ajax-error text-danger small"></span>
                            </div>
                            <div class="col-md-4 ajax-field">
                                <label class="form-label">Parent Phone</label>
                                <input type="text" class="form-control" name="parent_phone" id="edit_parent_phone" placeholder="e.g., 9876543210">
                                <span class="ajax-error text-danger small"></span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateStudentBtn">Update Student</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Bulk Upload Students Modal -->
    <div class="modal fade" id="adminBulkStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Upload Students (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        Step 1: Download sample. Step 2: Fill data. Step 3: Upload.<br>
                        Required columns: <strong>name,email,phone,classroom,batch</strong><br>
                        Optional columns: <strong>parent_name,parent_email,parent_phone</strong><br>
                        Use exact classroom and batch names as created for this teacher.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload CSV File</label>
                        <input type="file" class="form-control" id="admin_bulk_student_file" accept=".csv,text/csv">
                        <small class="text-body-secondary">Max file size: 5MB</small>
                    </div>
                    <div class="mb-0" id="admin-bulk-student-msg"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="admin-upload-bulk-students-btn">Upload</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Clear errors when modals are opened
        $('#addClassroomModal').on('show.bs.modal', function() {
            clearAjaxErrors('#addClassroomModal');
        });
        $('#editClassroomModal').on('show.bs.modal', function() {
            clearAjaxErrors('#editClassroomModal');
        });
        $('#addBatchModal').on('show.bs.modal', function() {
            clearAjaxErrors('#addBatchModal');
        });
        $('#editBatchModal').on('show.bs.modal', function() {
            clearAjaxErrors('#editBatchModal');
        });
        $('#addStudentModal').on('show.bs.modal', function() {
            clearAjaxErrors('#addStudentModal');
        });
        $('#editStudentModal').on('show.bs.modal', function() {
            clearAjaxErrors('#editStudentModal');
        });

        // Add Classroom
        $('#saveClassroomBtn').on('click', function() {
            const form = $('#addClassroomForm');
            clearAjaxErrors('#addClassroomModal');
            const formData = form.serializeArray();
            formData.push({name: '_token', value: '{{ csrf_token() }}'});

            $.post('{{ url("admin/teacher/save_classroom") }}', formData, function(res) {
                if (res.status == 1) {
                    $('#addClassroomModal').modal('hide');
                    form[0].reset();
                    clearAjaxErrors('#addClassroomModal');
                    processAjaxResponse(res, 500);
                } else {
                    processAjaxResponse(res, 0, '#addClassroomModal');
                }
            }, 'json');
        });

        // Edit Classroom - Open Modal
        $(document).on('click', '.edit-classroom', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const teacherId = $(this).data('teacher-id');

            clearAjaxErrors('#editClassroomModal');
            $('#edit_classroom_id').val(id);
            $('#edit_classroom_name').val(name);
            $('#edit_classroom_teacher_id').val(teacherId);
            $('#editClassroomModal').modal('show');
        });

        // Update Classroom
        $('#updateClassroomBtn').on('click', function() {
            const form = $('#editClassroomForm');
            clearAjaxErrors('#editClassroomModal');
            const formData = form.serializeArray();
            formData.push({name: '_token', value: '{{ csrf_token() }}'});

            $.post('{{ url("admin/teacher/save_classroom") }}', formData, function(res) {
                if (res.status == 1) {
                    $('#editClassroomModal').modal('hide');
                    clearAjaxErrors('#editClassroomModal');
                    processAjaxResponse(res, 500);
                } else {
                    processAjaxResponse(res, 0, '#editClassroomModal');
                }
            }, 'json');
        });

        // Delete Classroom
        $(document).on('click', '.delete-classroom', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this classroom?')) {
                const classroomId = $(this).data('id');
                $.post('{{ url("admin/teacher/delete_classroom") }}', {
                    _token: '{{ csrf_token() }}',
                    id: classroomId
                }, function(res) {
                    if (res.status == 1) {
                        processAjaxResponse(res, 500);
                    } else {
                        alert(res.error || 'Failed to delete classroom');
                    }
                }, 'json');
            }
        });

        // Change Teacher for Classroom
        $(document).on('change', '.teacher-select', function() {
            const classroomId = $(this).data('classroom-id');
            const classroomName = $(this).data('classroom-name');
            const teacherId = $(this).val();
            const originalValue = $(this).data('original-value') || $(this).find('option[selected]').val() || $(this).find('option').first().val();
            
            if (confirm('Are you sure you want to change the teacher for this classroom?')) {
                $.post('{{ url("admin/teacher/save_classroom") }}', {
                    _token: '{{ csrf_token() }}',
                    id: classroomId,
                    name: classroomName,
                    teacher_id: teacherId
                }, function(res) {
                    if (res.status == 1) {
                        processAjaxResponse(res, 500);
                    } else {
                        alert(res.error || 'Failed to update classroom');
                        location.reload();
                    }
                }, 'json');
            } else {
                $(this).val(originalValue);
            }
        });

        // Add Batch
        $('#saveBatchBtn').on('click', function() {
            const form = $('#addBatchForm');
            clearAjaxErrors('#addBatchModal');
            const formData = form.serializeArray();
            formData.push({name: '_token', value: '{{ csrf_token() }}'});

            $.post('{{ url("admin/teacher/save_batch") }}', formData, function(res) {
                if (res.status == 1) {
                    $('#addBatchModal').modal('hide');
                    form[0].reset();
                    clearAjaxErrors('#addBatchModal');
                    processAjaxResponse(res, 500);
                } else {
                    processAjaxResponse(res, 0, '#addBatchModal');
                }
            }, 'json');
        });

        // Edit Batch
        $(document).on('click', '.edit-batch', function() {
            const batchId = $(this).data('id');
            const batchName = $(this).data('name');
            const classroomId = $(this).data('classroom-id');
            const status = $(this).data('status');
            const schedule = $(this).data('schedule');

            clearAjaxErrors('#editBatchModal');
            $('#edit_batch_id').val(batchId);
            $('#edit_batch_name').val(batchName);
            $('#edit_batch_classroom_id').val(classroomId);
            $('#edit_batch_status').val(status);
            $('#edit_batch_schedule').val(schedule || '');

            $('#editBatchModal').modal('show');
        });

        // Update Batch
        $('#updateBatchBtn').on('click', function() {
            const form = $('#editBatchForm');
            clearAjaxErrors('#editBatchModal');
            const formData = form.serializeArray();
            formData.push({name: '_token', value: '{{ csrf_token() }}'});

            $.post('{{ url("admin/teacher/save_batch") }}', formData, function(res) {
                if (res.status == 1) {
                    $('#editBatchModal').modal('hide');
                    clearAjaxErrors('#editBatchModal');
                    processAjaxResponse(res, 500);
                } else {
                    processAjaxResponse(res, 0, '#editBatchModal');
                }
            }, 'json');
        });

        // Delete Batch
        $(document).on('click', '.delete-batch', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this batch?')) {
                const batchId = $(this).data('id');
                $.post('{{ url("admin/teacher/delete_batch") }}', {
                    _token: '{{ csrf_token() }}',
                    id: batchId
                }, function(res) {
                    if (res.status == 1) {
                        processAjaxResponse(res, 500);
                    } else {
                        alert(res.error || 'Failed to delete batch');
                    }
                }, 'json');
            }
        });

        // Update Batch Classroom
        $(document).on('change', '.batch-classroom-select', function() {
            const batchId = $(this).data('batch-id');
            const classroomId = $(this).val();
            const $card = $(this).closest('.card');
            const batchName = $card.find('.card-title').text().trim();
            const statusText = $card.find('.badge').text().trim().toLowerCase();
            
            if (!classroomId) {
                alert('Please select a classroom');
                location.reload();
                return;
            }

            if (confirm('Are you sure you want to change the classroom for this batch?')) {
                $.post('{{ url("admin/teacher/save_batch") }}', {
                    _token: '{{ csrf_token() }}',
                    id: batchId,
                    teacher_id: '{{ $teacher->id }}',
                    classroom_id: classroomId,
                    name: batchName,
                    status: statusText
                }, function(res) {
                    if (res.status == 1) {
                        processAjaxResponse(res, 500);
                    } else {
                        alert(res.error || 'Failed to update batch');
                        location.reload();
                    }
                }, 'json');
            } else {
                location.reload();
            }
        });

        // Load batches by classroom (for Add Student)
        $(document).on('change', '#add_student_classroom_id', function() {
            const classroomId = $(this).val();
            const batchSelect = $('#add_student_batch_id');
            
            batchSelect.html('<option value="">Loading...</option>').prop('disabled', true);
            
            if (!classroomId) {
                batchSelect.html('<option value="">First select a classroom</option>').prop('disabled', true);
                return;
            }
            
            $.get('{{ url("admin/teacher/get_batches_by_classroom") }}', {classroom_id: classroomId}, function(res) {
                if (res.status == 1 && res.data) {
                    batchSelect.html('<option value="">Choose Batch</option>');
                    $.each(res.data, function(index, batch) {
                        batchSelect.append('<option value="' + batch.id + '">' + batch.name + '</option>');
                    });
                    batchSelect.prop('disabled', false);
                } else {
                    batchSelect.html('<option value="">No batches found</option>').prop('disabled', true);
                }
            }, 'json');
        });

        // Add Student
        $(document).on('click', '#saveStudentBtn', function() {
            const form = $('#addStudentForm');
            clearAjaxErrors('#addStudentModal');
            const formData = form.serializeArray();
            formData.push({name: '_token', value: '{{ csrf_token() }}'});

            $.post('{{ url("admin/teacher/save_student") }}', formData, function(res) {
                if (res.status == 1) {
                    $('#addStudentModal').modal('hide');
                    form[0].reset();
                    $('#add_student_batch_id').html('<option value="">First select a classroom</option>').prop('disabled', true);
                    clearAjaxErrors('#addStudentModal');
                    processAjaxResponse(res, 500);
                } else {
                    processAjaxResponse(res, 0, '#addStudentModal');
                }
            }, 'json');
        });

        // Load batches by classroom (for Edit Student)
        function loadBatchesForEdit(classroomId, selectedBatchId) {
            const batchSelect = $('#edit_student_batch_id');
            
            if (!classroomId) {
                batchSelect.html('<option value="">Choose Batch</option>').prop('disabled', true);
                return;
            }
            
            batchSelect.html('<option value="">Loading...</option>').prop('disabled', true);
            
            $.get('{{ url("admin/teacher/get_batches_by_classroom") }}', {classroom_id: classroomId}, function(res) {
                if (res.status == 1 && res.data) {
                    batchSelect.html('<option value="">Choose Batch</option>');
                    $.each(res.data, function(index, batch) {
                        const selected = (batch.id == selectedBatchId) ? 'selected' : '';
                        batchSelect.append('<option value="' + batch.id + '" ' + selected + '>' + batch.name + '</option>');
                    });
                    batchSelect.prop('disabled', false);
                } else {
                    batchSelect.html('<option value="">No batches found</option>').prop('disabled', true);
                }
            }, 'json');
        }

        $(document).on('change', '#edit_student_classroom_id', function() {
            const classroomId = $(this).val();
            loadBatchesForEdit(classroomId, '');
        });

        // Edit Student
        $(document).on('click', '.edit-student', function() {
            const studentId = $(this).data('id');
            const studentName = $(this).data('name');
            const studentEmail = $(this).data('email');
            const studentPhone = $(this).data('phone');
            const classroomId = $(this).data('classroom-id');
            const batchId = $(this).data('batch-id');
            const parentName = $(this).data('parent_name') || '';
            const parentEmail = $(this).data('parent_email') || '';
            const parentPhone = $(this).data('parent_phone') || '';

            clearAjaxErrors('#editStudentModal');
            $('#edit_student_id').val(studentId);
            $('#edit_student_name').val(studentName);
            $('#edit_student_email').val(studentEmail);
            $('#edit_student_phone').val(studentPhone);
            $('#edit_student_classroom_id').val(classroomId);
            $('#edit_parent_name').val(parentName);
            $('#edit_parent_email').val(parentEmail);
            $('#edit_parent_phone').val(parentPhone);
            
            // Load batches for the selected classroom
            loadBatchesForEdit(classroomId, batchId);

            $('#editStudentModal').modal('show');
        });

        // Update Student
        $('#updateStudentBtn').on('click', function() {
            const form = $('#editStudentForm');
            clearAjaxErrors('#editStudentModal');
            const formData = form.serializeArray();
            formData.push({name: '_token', value: '{{ csrf_token() }}'});

            $.post('{{ url("admin/teacher/save_student") }}', formData, function(res) {
                if (res.status == 1) {
                    $('#editStudentModal').modal('hide');
                    clearAjaxErrors('#editStudentModal');
                    processAjaxResponse(res, 500);
                } else {
                    processAjaxResponse(res, 0, '#editStudentModal');
                }
            }, 'json');
        });

        // Delete Student
        $(document).on('click', '.delete-student', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this student?')) {
                const studentId = $(this).data('id');
                $.post('{{ url("admin/teacher/delete_student") }}', {
                    _token: '{{ csrf_token() }}',
                    id: studentId
                }, function(res) {
                    if (res.status == 1) {
                        processAjaxResponse(res, 500);
                    } else {
                        alert(res.error || 'Failed to delete student');
                    }
                }, 'json');
            }
        });

        // Admin bulk upload students
        $(document).on('click', '#admin-upload-bulk-students-btn', function() {
            const fileInput = document.getElementById('admin_bulk_student_file');
            const file = fileInput.files[0];
            const $btn = $(this);
            const $msg = $('#admin-bulk-student-msg');
            $msg.html('');

            if (!file) {
                $msg.html('<div class="alert alert-danger mb-0">Please select a CSV file.</div>');
                return;
            }

            const data = new FormData();
            data.append('_token', '{{ csrf_token() }}');
            data.append('teacher_id', '{{ $teacher->id }}');
            data.append('file', file);

            $btn.attr('disabled', 'disabled').text('Uploading...');
            $.ajax({
                url: '{{ url("admin/teacher/students/bulk-upload") }}',
                type: 'POST',
                data: data,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    $btn.removeAttr('disabled').text('Upload');
                    if (res.status == 1) {
                        let html = '<div class="alert alert-success mb-2">' + escapeHtml(res.msg || 'Upload completed') + '</div>';
                        if (res.errors && res.errors.length) {
                            const items = res.errors.slice(0, 15).map(function(err) {
                                return '<li>' + escapeHtml(err) + '</li>';
                            }).join('');
                            html += '<div class="alert alert-warning mb-0"><strong>Row Issues:</strong><ul class="mb-0 mt-2">' + items + '</ul></div>';
                        }
                        $msg.html(html);
                        if (res.summary && Number(res.summary.created || 0) > 0) {
                            setTimeout(function(){ location.reload(); }, 1500);
                        }
                    } else if (res.error_array) {
                        const firstKey = Object.keys(res.error_array)[0];
                        const firstErr = firstKey ? res.error_array[firstKey][0] : 'Validation failed';
                        $msg.html('<div class="alert alert-danger mb-0">' + escapeHtml(firstErr) + '</div>');
                    } else {
                        let html = '<div class="alert alert-danger mb-2">' + escapeHtml(res.error || 'Upload failed') + '</div>';
                        if (res.errors && res.errors.length) {
                            const items = res.errors.slice(0, 20).map(function(err) {
                                return '<li>' + escapeHtml(err) + '</li>';
                            }).join('');
                            html += '<div class="alert alert-warning mb-0"><strong>Row Issues:</strong><ul class="mb-0 mt-2">' + items + '</ul></div>';
                        }
                        $msg.html(html);
                    }
                },
                error: function(xhr) {
                    $btn.removeAttr('disabled').text('Upload');
                    const serverMsg = xhr && xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : '';
                    $msg.html('<div class="alert alert-danger mb-0">' + escapeHtml(serverMsg || 'Upload failed. Please try again.') + '</div>');
                }
            });
        });

        // Escape helper used above
        function escapeHtml(text) {
            return String(text || '').replace(/[&<>"']/g, function(m) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
            });
        }
    });
</script>
@endsection

@section('styles')
<style>
    .border-dashed {
        border: 2px dashed var(--bs-border-color) !important;
    }
    .avatar-initial {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.25rem 1.125rem 0 rgba(47, 43, 61, 0.16);
    }
    .tab-panel-card {
        max-height: none;
        overflow: visible;
    }
    .tab-panel-card .tab-content {
        max-height: none;
        overflow: visible;
    }
    .tab-pane {
        min-height: auto;
    }
    .tab-content > .tab-pane {
        display: block;
    }
    .tab-content > .tab-pane:not(.active) {
        display: none;
    }
    .batch-card {
        width: 200px;
        height: 220px;
        min-width: 200px;
        min-height: 220px;
    }
    .batch-card .card-body {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .batch-card .form-select-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    .students-table-wrap {
        width: 100%;
    }
    .students-table {
        width: 100%;
        table-layout: fixed;
    }
    .students-table th,
    .students-table td {
        white-space: normal;
        word-break: break-word;
        vertical-align: middle;
    }
    /* Compact spacing inside student forms */
    #addStudentModal .modal-body .mb-3,
    #editStudentModal .modal-body .mb-3 {
        margin-bottom: 0.75rem !important;
    }
    /* Slightly reduce modal body padding to reduce perceived height */
    #addStudentModal .modal-body,
    #editStudentModal .modal-body {
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }
</style>
@endsection
