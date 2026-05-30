@extends('associate.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('certificate-application/list') }}" class="btn btn-label-secondary btn-sm">Application List</a>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="client_id">Company Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="client_id">
                            <option value="">Select company</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="client-forms-area">
                    @include('associate.certificate_application.partials.new_form')
                    @include('associate.certificate_application.partials.old_form')
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const uploadUrl = '{{ url('common/upload_files') }}';
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        function resetCertificateTypeDropdown(prefix) {
            $('#' + prefix + '_certificate_type_id')
                .prop('disabled', true)
                .empty()
                .append('<option value="">Select System Standard</option>');
        }

        function loadCertificateTypesFor(prefix) {
            const type = $('#' + prefix + '_app_type').val();
            const category = $('#' + prefix + '_app_category').val();
            const $dropdown = $('#' + prefix + '_certificate_type_id');

            resetCertificateTypeDropdown(prefix);

            if (!type || !category) {
                return;
            }

            $dropdown.prop('disabled', true).empty().append('<option value="">Loading...</option>');

            $.get('{{ url('certificate-application/certificate-types') }}', {
                type: type,
                category: category
            }, function(res) {
                $dropdown.empty().append('<option value="">Select System Standard</option>');
                (res || []).forEach(function(ct) {
                    $dropdown.append('<option value="' + ct.id + '">' + ct.description + '</option>');
                });
                $dropdown.prop('disabled', false);
            }, 'json').fail(function() {
                resetCertificateTypeDropdown(prefix);
            });
        }

        function resetClientForms() {
            $('#new-client-form-wrap, #old-client-form-wrap').addClass('d-none');
            $('#new-client-form, #old-client-form').each(function() {
                this.reset();
                $(this).find('input[type="hidden"]').not('[name="_token"]').val('');
                $(this).find('.file-upload-status').text('');
            });
            resetCertificateTypeDropdown('new');
            resetCertificateTypeDropdown('old');
        }

        function setClientIdOnForms(clientId) {
            $('#new_form_client_id, #old_form_client_id').val(clientId);
        }

        $(document).on('change', '#new_app_type, #new_app_category', function() {
            loadCertificateTypesFor('new');
        });

        $(document).on('change', '#old_app_type, #old_app_category', function() {
            loadCertificateTypesFor('old');
        });

        $('#client_id').on('change', function() {
            const clientId = $(this).val();
            resetClientForms();

            if (!clientId) {
                return;
            }

            $.get('{{ url('certificate-application/check_client') }}', {
                client_id: clientId
            }, function(res) {
                setClientIdOnForms(clientId);
                if (res.is_new) {
                    $('#new-client-form-wrap').removeClass('d-none');
                } else {
                    $('#old-client-form-wrap').removeClass('d-none');
                }
            }, 'json');
        });

        $(document).on('change', '.doc-file-input', function() {
            const $input = $(this);
            const field = $input.data('field');
            const file = this.files[0];
            const $hidden = $('#' + field);
            const $status = $('.file-upload-status[data-for="' + field + '"]');

            if (!file) {
                $hidden.val('');
                $status.text('');
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
                error: function(xhr) {
                    $hidden.val('');
                    let msg = 'Upload failed.';
                    if (xhr && xhr.status === 419) {
                        msg = 'Upload failed (CSRF token expired). Please reload the page.';
                    } else if (xhr && xhr.status === 413) {
                        msg = 'Upload failed (file too large).';
                    } else if (xhr && xhr.responseText) {
                        try {
                            const parsed = JSON.parse(xhr.responseText);
                            if (parsed && parsed.message) {
                                msg = 'Upload failed: ' + parsed.message;
                            }
                        } catch (e) {}
                    }
                    $status.removeClass('text-success').addClass('text-danger').text(msg);
                }
            });
        });

        function bindCertAppFormSubmit(formSelector) {
            $(document).on('submit', formSelector, function(e) {
                e.preventDefault();
                clearAjaxErrors();
                const _this = $(this);
                const saveBtn = _this.find('.submit-button');
                saveBtn.prop('disabled', true).text('Submitting...');

                $.post(_this.attr('action'), _this.serializeArray(), function(res) {
                    saveBtn.prop('disabled', false).text('Submit');
                    processAjaxResponse(res, 1000, _this);
                }, 'json').fail(function() {
                    saveBtn.prop('disabled', false).text('Submit');
                });
            });
        }

        bindCertAppFormSubmit('#new-client-form');
        bindCertAppFormSubmit('#old-client-form');

        resetCertificateTypeDropdown('new');
        resetCertificateTypeDropdown('old');
    </script>
@endsection
