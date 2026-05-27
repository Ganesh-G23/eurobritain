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
                    <div class="row">
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
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $details->description) }}</textarea>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="audit_period">Audit Period <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="audit_period" name="audit_period"
                                value="{{ old('audit_period', $details->audit_period) }}"
                                placeholder="e.g. 365 days">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="renewal_period">Renewal Period <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="renewal_period" name="renewal_period"
                                value="{{ old('renewal_period', $details->renewal_period) }}"
                                placeholder="e.g. 365 days">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="price">Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="price" name="price"
                                value="{{ old('price', $details->price) }}">
                            <span class="ajax-error"></span>
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
