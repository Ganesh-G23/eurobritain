@extends('web.user.layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y pb-2">
    <div class="row g-6 mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">My Batches</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#batchModal">Add Batch</button>
        </div>
    </div>
    <div class="row g-6">
        @forelse($batches as $batch)
        <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="mb-1">{{ $batch->name }}</h6>
                        <small class="d-block text-body-secondary">Classroom: {{ $batch->classroom->name ?? '-' }}</small>
                        <small class="text-body-secondary">Status: {{ ucfirst($batch->status ?? '-') }}</small>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-sm btn-label-primary edit-batch"
                            data-id="{{ $batch->id }}"
                            data-name="{{ $batch->name }}"
                            data-classroom_id="{{ $batch->classroom_id }}"
                            data-status="{{ $batch->status }}">Edit</button>
                        <button class="btn btn-sm btn-label-danger delete-batch" data-id="{{ $batch->id }}">Delete</button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">No batches found</div>
        </div>
        @endforelse
    </div>
</div>

<!-- Add/Edit Batch Modal -->
<div class="modal fade" id="batchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Batch</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3 ajax-field">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" placeholder="Enter batch name">
            <span class="ajax-error text-danger small"></span>
        </div>
        <div class="mb-3 ajax-field">
            <label class="form-label">Classroom <span class="text-danger">*</span></label>
            <select class="form-select" name="classroom_id">
                <option value="">Select classroom</option>
                @foreach(\App\Models\Classroom::where('teacher_id', (int)(session('portal_user')['id'] ?? 0))->orderBy('name')->get() as $cr)
                    <option value="{{ $cr->id }}">{{ $cr->name }}</option>
                @endforeach
            </select>
            <span class="ajax-error text-danger small"></span>
        </div>
        <div class="mb-3 ajax-field">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select" name="status">
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="inactive">Inactive</option>
            </select>
            <span class="ajax-error text-danger small"></span>
        </div>
        <input type="hidden" name="id" value="">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="save-batch">Save</button>
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
    $('#save-batch').on('click', function(){
        const modal = $('#batchModal');
        clearErrors(modal);
        const payload = {
            _token: '{{ csrf_token() }}',
            id: modal.find('input[name="id"]').val(),
            name: modal.find('input[name="name"]').val(),
            classroom_id: modal.find('select[name="classroom_id"]').val(),
            status: modal.find('select[name="status"]').val()
        };
        const btn = $(this).attr('disabled', true).text('Saving...');
        $.post('{{ url("user/teacher/batches/save") }}', payload, function(res){
            btn.attr('disabled', false).text('Save');
            if (res.status == 1) {
                window.location.href = res.redirect_url;
            } else if (res.error_array) {
                ['name','classroom_id','status'].forEach(function(f){
                    if (res.error_array[f]) modal.find('[name="'+f+'"]').closest('.ajax-field').find('.ajax-error').text(res.error_array[f][0]);
                });
            } else if (res.error) {
                alert(res.error);
            }
        }, 'json');
    });

    $('.edit-batch').on('click', function(){
        const modal = $('#batchModal');
        modal.find('.modal-title').text('Edit Batch');
        modal.find('input[name="id"]').val($(this).data('id'));
        modal.find('input[name="name"]').val($(this).data('name'));
        modal.find('select[name="classroom_id"]').val(String($(this).data('classroom_id')));
        modal.find('select[name="status"]').val(String($(this).data('status')));
        modal.modal('show');
    });

    $('.delete-batch').on('click', function(){
        if (!confirm('Delete this batch?')) return;
        const id = $(this).data('id');
        $.post('{{ url("user/teacher/batches/delete") }}', {
            _token: '{{ csrf_token() }}',
            id
        }, function(res){
            if (res.status == 1) {
                window.location.href = res.redirect_url;
            } else {
                alert(res.error || 'Failed');
            }
        }, 'json');
    });

    $('#batchModal').on('hidden.bs.modal', function(){
        const modal = $(this);
        modal.find('.modal-title').text('Add Batch');
        modal.find('input[name="id"]').val('');
        modal.find('input[name="name"]').val('');
        modal.find('select[name="classroom_id"]').val('');
        modal.find('select[name="status"]').val('active');
        clearErrors(modal);
    });
});
</script>
@endsection
@endsection
