@extends('web.user.layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y pb-2">
<div class="row g-6 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">My Classrooms</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#classroomModal">
            <i class="icon-base ti tabler-plus me-1"></i> Add Classroom
        </button>
    </div>
</div>
<div class="row g-6">
    @forelse($classrooms as $classroom)
    <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <h6 class="mb-1">{{ $classroom->name }}</h6>
                        <small class="text-body-secondary d-block">Created {{ optional($classroom->created_at)->format('d M y') }}</small>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-icon btn-label-primary edit-classroom" 
                            data-id="{{ $classroom->id }}" 
                            data-name="{{ $classroom->name }}" title="Edit">
                            <i class="icon-base ti tabler-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-icon btn-label-danger delete-classroom" 
                            data-id="{{ $classroom->id }}" title="Delete">
                            <i class="icon-base ti tabler-trash"></i>
                        </button>
                    </div>
                </div>
                @php
                    $batches = \App\Models\Batch::where('classroom_id', $classroom->id)->orderBy('name')->get();
                    $batchCount = $batches->count();
                    $studentCount = (int) ($classroom->enrolled_student_count ?? 0);
                @endphp
                <div class="mt-3">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="d-flex align-items-center">
                            <i class="icon-base ti tabler-folders me-2 text-primary"></i>
                            <span class="fw-medium">{{ $batchCount }}</span>
                            <small class="ms-1 text-body-secondary">Batches</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="icon-base ti tabler-users me-2 text-success"></i>
                            <span class="fw-medium">{{ $studentCount }}</span>
                            <small class="ms-1 text-body-secondary">Students</small>
                        </div>
                    </div>
                    @if($batchCount)
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($batches->take(3) as $b)
                                <span class="badge bg-label-primary">{{ $b->name }}</span>
                            @endforeach
                            @if($batchCount > 3)
                                <span class="badge bg-label-secondary">+{{ $batchCount - 3 }} more</span>
                            @endif
                        </div>
                    @else
                        <small class="text-body-secondary">No batches yet</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info mb-0">No classrooms found</div>
    </div>
    @endforelse
</div>

<!-- Add/Edit Classroom Modal -->
<div class="modal fade" id="classroomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Classroom</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3 ajax-field">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" placeholder="Enter classroom name">
            <span class="ajax-error text-danger small"></span>
        </div>
        <input type="hidden" name="id" value="">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="save-classroom">Save</button>
      </div>
    </div>
  </div>
</div>

@section('scripts')
<script>
$(function(){
    function clearErrors(modal) {
        modal.find('.ajax-error').text('');
    }
    $('#save-classroom').on('click', function(){
        const modal = $('#classroomModal');
        clearErrors(modal);
        const payload = {
            _token: '{{ csrf_token() }}',
            id: modal.find('input[name="id"]').val(),
            name: modal.find('input[name="name"]').val()
        };
        const btn = $(this).attr('disabled', true).text('Saving...');
        $.post('{{ url("user/teacher/classrooms/save") }}', payload, function(res){
            btn.attr('disabled', false).text('Save');
            if (res.status == 1) {
                location.reload();
            } else if (res.error_array) {
                if (res.error_array.name) modal.find('.ajax-field .ajax-error').text(res.error_array.name[0]);
            } else if (res.error) {
                alert(res.error);
            }
        }, 'json');
    });

    $('.edit-classroom').on('click', function(){
        const id = $(this).data('id');
        const name = $(this).data('name');
        const modal = $('#classroomModal');
        modal.find('.modal-title').text('Edit Classroom');
        modal.find('input[name="id"]').val(id);
        modal.find('input[name="name"]').val(name);
        modal.modal('show');
    });

    $('.delete-classroom').on('click', function(){
        if (!confirm('Delete this classroom?')) return;
        const id = $(this).data('id');
        $.post('{{ url("user/teacher/classrooms/delete") }}', {
            _token: '{{ csrf_token() }}',
            id
        }, function(res){
            if (res.status == 1) {
                location.reload();
            } else {
                alert(res.error || 'Failed');
            }
        }, 'json');
    });

    $('#classroomModal').on('hidden.bs.modal', function(){
        const modal = $(this);
        modal.find('.modal-title').text('Add Classroom');
        modal.find('input[name="id"]').val('');
        modal.find('input[name="name"]').val('');
        clearErrors(modal);
    });
});
</script>
@endsection
</div>
@endsection

