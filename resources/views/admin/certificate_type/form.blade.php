@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('admin/certificate-type/list') }}" class="btn btn-label-secondary btn-sm">Back to list</a>
            </div>
            <div class="card-body">
                <form id="ajax-form" method="POST"
                    action="{{ $mode === 'edit' ? url('admin/certificate-type/update/' . $details->id) : url('admin/certificate-type/save') }}">
                    @csrf
                    <div class="col-12 ajax-msg"></div>
                    @php
                        $selectedTypes = old('types', $details->types ?? []);
                    @endphp
                    <div class="row">
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="{{ old('name', $details->name) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="types">Type <span class="text-danger">*</span></label>
                            <select class="form-select text-select2" id="types" name="types[]" multiple>
                                @foreach ($type_options as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ in_array($value, (array) $selectedTypes, true) ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="category">Certificate Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Select</option>
                                @foreach ($category_options as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('category', $details->category) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code"
                                value="{{ old('code', $details->code) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="prefix">Prefix <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="prefix" name="prefix"
                                value="{{ old('prefix', $details->prefix) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="description">Description</label>
                            <input type="text" class="form-control" id="description" name="description"
                                value="{{ old('description', $details->description) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="audit_period">Audit Period (Years) <span class="text-danger">*</span></label>
                            <input type="number" min="1" step="1" class="form-control" id="audit_period" name="audit_period"
                                value="{{ old('audit_period', $details->audit_period) }}"
                                placeholder="e.g. 1">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="renewal_period">Renewal Period (Years) <span class="text-danger">*</span></label>
                            <input type="number" min="1" step="1" class="form-control" id="renewal_period" name="renewal_period"
                                value="{{ old('renewal_period', $details->renewal_period) }}"
                                placeholder="e.g. 3">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="price">Base Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="price" name="price"
                                value="{{ old('price', $details->price) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Certificate Template</label>
                            <input type="file" class="form-control" id="certificate_template_file" accept="image/*,.pdf,.doc,.docx">
                            <input type="hidden" name="certificate_template" id="certificate_template"
                                value="{{ old('certificate_template', $details->certificate_template) }}">
                            @if (!empty($details->certificate_template))
                                <div class="mt-1 small">
                                    Current:
                                    <a href="{{ url('storage/app/uploads/temp/' . $details->certificate_template) }}" target="_blank">{{ $details->certificate_template }}</a>
                                </div>
                            @endif
                            <span class="small text-muted file-upload-status" data-for="certificate_template"></span>
                            <span class="ajax-error"></span>
                            @if ($mode === 'edit' && !empty($details->certificate_template))
                                <div class="mt-2">
                                    <a href="{{ url('admin/certificate-type/template-coords/' . $details->id) }}"
                                        class="btn btn-sm btn-label-primary">
                                        <i class="icon-base ti tabler-target me-1"></i> Configure Template Fields
                                    </a>
                                </div>
                            @elseif ($mode === 'add')
                                <div class="form-text">Save the certificate type first, then configure template field positions from the list page.</div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary me-2 submit-button">Save</button>
                        <a href="{{ url('admin/certificate-type/list') }}" class="btn btn-label-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const uploadUrl = '{{ url('admin/common/upload_files') }}';
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        $(function() {
            if ($.fn.select2) {
                $('#types').select2({
                    placeholder: 'Select type(s)',
                    allowClear: true,
                    width: '100%',
                });
            }
        });

        $('#certificate_template_file').on('change', function() {
            const file = this.files[0];
            const $hidden = $('#certificate_template');
            const $status = $('.file-upload-status[data-for="certificate_template"]');

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
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(res) {
                    if (res && res.status == 1 && res.data && res.data[0]) {
                        $hidden.val(res.data[0].fileName);
                        $status.removeClass('text-danger').addClass('text-success').text('Uploaded: ' + res.data[0].fileName);
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
            }, 'json');
        });
    </script>
@endsection
