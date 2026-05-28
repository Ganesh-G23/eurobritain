@extends('associate.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('certificate-application/list') }}" class="btn btn-label-secondary btn-sm">Back to list</a>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Company: <strong>{{ $application->company_name }}</strong></p>

                @if ($application->application_document)
                    <div class="alert alert-info py-2 mb-3">
                        Current file:
                        <a href="{{ $application->application_document_url }}" target="_blank">{{ $application->application_document }}</a>
                    </div>
                @endif

                <form id="ajax-form" method="POST" action="{{ url('certificate-application/save-document/' . $application->id) }}">
                    @csrf
                    <div class="col-12 ajax-msg"></div>
                    <div class="mb-3 ajax-field">
                        <label class="form-label">Application Document <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="application_document_file" accept="image/*,.pdf">
                        <input type="hidden" name="application_document" id="application_document" value="{{ $application->application_document }}">
                        <span class="small text-muted file-upload-status"></span>
                        <span class="ajax-error"></span>
                    </div>
                    <button type="submit" class="btn btn-primary submit-button">Save</button>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const uploadUrl = '{{ url('common/upload_files') }}';
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        $('#application_document_file').on('change', function() {
            const file = this.files[0];
            const $hidden = $('#application_document');
            const $status = $('.file-upload-status');

            if (!file) {
                return;
            }

            $status.text('Uploading...');
            const formData = new FormData();
            formData.append('files[]', file);
            formData.append('_token', csrfToken);

            $.ajax({
                url: uploadUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(res) {
                    if (res && res.status == 1 && res.data && res.data[0]) {
                        $hidden.val(res.data[0].fileName);
                        $status.removeClass('text-danger').addClass('text-success').text('Uploaded: ' + res.data[0]
                            .fileName);
                    } else {
                        $hidden.val('');
                        $status.removeClass('text-success').addClass('text-danger').text('Upload failed.');
                    }
                },
                error: function() {
                    $hidden.val('');
                    $status.removeClass('text-success').addClass('text-danger').text('Upload failed.');
                }
            });
        });

        $(document).on('submit', '#ajax-form', function(e) {
            e.preventDefault();
            clearAjaxErrors();
            const _this = $(this);
            const saveBtn = _this.find('.submit-button');
            saveBtn.prop('disabled', true).text('Saving...');

            $.post(_this.attr('action'), _this.serializeArray(), function(res) {
                saveBtn.prop('disabled', false).text('Save');
                processAjaxResponse(res, 1000, _this);
            }, 'json').fail(function() {
                saveBtn.prop('disabled', false).text('Save');
            });
        });
    </script>
@endsection
