<div id="old-client-form-wrap" class="d-none">
    <form id="old-client-form" method="POST" action="{{ url('admin/certificate-application/resolve_old_client') }}">
        @csrf
        <input type="hidden" name="client_id" id="old_form_client_id" value="">

        <div class="col-12 ajax-msg"></div>

        <div class="row">
            <div class="mb-3 col-md-6">
                <label class="form-label" for="old_app_type">Type <span class="text-danger">*</span></label>
                <select class="form-select" id="old_app_type">
                    <option value="">Select Type</option>
                    @foreach ($typeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3 col-md-6">
                <label class="form-label" for="old_app_category">Certificate Type <span class="text-danger">*</span></label>
                <select class="form-select" id="old_app_category">
                    <option value="">Select Certificate Type</option>
                    @foreach ($categoryOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-3 ajax-field">
            <label class="form-label" for="old_certificate_type_id">Service Requested System Standard <span class="text-danger">*</span></label>
            <select class="form-select" name="certificate_type_id" id="old_certificate_type_id" disabled>
                <option value="">Select System Standard</option>
            </select>
            <span class="ajax-error"></span>
        </div>

        <button type="submit" class="btn btn-primary submit-button">Submit</button>
    </form>
</div>
