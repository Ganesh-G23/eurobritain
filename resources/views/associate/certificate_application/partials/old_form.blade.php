<div id="old-client-form-wrap" class="d-none">
    <form id="old-client-form" method="POST" action="{{ url('certificate-application/resolve_old_client') }}">
        @csrf
        <input type="hidden" name="client_id" id="old_form_client_id" value="">

        <div class="col-12 ajax-msg"></div>

        <div class="mb-3 ajax-field">
            <label class="form-label" for="old_certificate_type_id">Service Requested System Standard <span class="text-danger">*</span></label>
            <select class="form-select" name="certificate_type_id" id="old_certificate_type_id">
                <option value="">Select System Standard</option>
                @foreach ($certificateTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->description }}</option>
                @endforeach
            </select>
            <span class="ajax-error"></span>
        </div>

        <button type="submit" class="btn btn-primary submit-button">Submit</button>
    </form>
</div>
