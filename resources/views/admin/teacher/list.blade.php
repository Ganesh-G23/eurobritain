@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Teachers List</h5>
                        <a href="{{ url('admin/teacher/form') }}" class="btn btn-primary">
                            <i class="icon-base ti tabler-plus me-1"></i> Add Teacher
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ url('admin/teacher') }}" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control" placeholder="Search by name, email or phone" value="{{ $search ?? '' }}">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    <a href="{{ url('admin/teacher') }}" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Created At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(count($teacher_list) > 0)
                                        @foreach($teacher_list as $key => $teacher)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>
                                                    <a href="{{ url('admin/teacher/login_as/' . base64_encode($teacher->id)) }}" class="text-primary text-decoration-underline" target="_blank" rel="noopener noreferrer">
                                                        {{ $teacher->name }}
                                                    </a>
                                                </td>
                                                <td>{{ $teacher->email ?? '-' }}</td>
                                                <td>{{ $teacher->phone ?? '-' }}</td>
                                                <td>{{ $teacher->created_at->setTimezone(config('app.timezone'))->format('Y-m-d h:i A') }}</td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="{{ url('admin/teacher/view?id=' . base64_encode($teacher->id)) }}" 
                                                           class="btn btn-sm btn-icon btn-label-info" 
                                                           title="View"
                                                           style="color: #03c3ec;">
                                                            <i class="icon-base ti tabler-eye"></i>
                                                        </a>
                                                        <a href="{{ url('admin/teacher/form?id=' . base64_encode($teacher->id)) }}" 
                                                           class="btn btn-sm btn-icon btn-label-primary" 
                                                           title="Edit"
                                                           style="color: #696cff;">
                                                            <i class="icon-base ti tabler-edit"></i>
                                                        </a>
                                                        <a href="javascript:void(0);" 
                                                           class="btn btn-sm btn-icon btn-label-danger delete-teacher" 
                                                           data-id="{{ base64_encode($teacher->id) }}"
                                                           title="Delete"
                                                           style="color: #ff3e1d;">
                                                            <i class="icon-base ti tabler-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="6" class="text-center">No teachers found</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        @if($num_rows > $per_page)
                            <div class="mt-3">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        @for($i = 1; $i <= ceil($num_rows / $per_page); $i++)
                                            <li class="page-item {{ $page == $i ? 'active' : '' }}">
                                                <a class="page-link" href="{{ $url }}&page={{ $i }}">{{ $i }}</a>
                                            </li>
                                        @endfor
                                    </ul>
                                </nav>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
<script>
    $(document).ready(function() {
        // Delete teacher
        $(document).on('click', '.delete-teacher', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this teacher?')) {
                const teacherId = $(this).data('id');
                $.post('{{ url("admin/teacher/delete") }}', {
                    _token: '{{ csrf_token() }}',
                    id: teacherId
                }, function(res) {
                    if (res.status == 1) {
                        processAjaxResponse(res, 500);
                    } else {
                        alert(res.error || 'Failed to delete teacher');
                    }
                }, 'json');
            }
        });
    });
</script>
@endsection