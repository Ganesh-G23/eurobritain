<div id="new-client-form-wrap" class="d-none">
    <form id="new-client-form" method="POST" action="{{ url('certificate-application/save_documents') }}">
        @csrf
        <input type="hidden" name="client_id" id="new_form_client_id" value="">
        <div class="col-12 ajax-msg"></div>

        <div class="row">
            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">Legal Proof of company <span class="text-danger">*</span> <small class="text-muted">(Firm Reg/ COI/ Partnership Deed)</small></label>
                <input type="file" class="form-control doc-file-input" data-field="legal_proof" accept="image/*,.pdf">
                <input type="hidden" name="legal_proof" id="legal_proof" value="">
                <span class="small text-muted file-upload-status" data-for="legal_proof"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">PAN / Sales Tax Number / Income Tax Proof <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="pan" id="pan" placeholder="PAN">
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">MSME / Udyog Aadhar</label>
                <input type="text" class="form-control" name="msme_udyog_aadhar" placeholder="MSME / Udyog Aadhar">
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">GSTN</label>
                <input type="text" class="form-control" name="gstn" placeholder="GSTN">
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">Factory Registration</label>
                <input type="file" class="form-control doc-file-input" data-field="factory_registration" accept="image/*,.pdf">
                <input type="hidden" name="factory_registration" id="factory_registration" value="">
                <span class="small text-muted file-upload-status" data-for="factory_registration"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">Copy of purchase bills-2 <span class="text-danger">*</span></label>
                <input type="file" class="form-control doc-file-input" data-field="purchase_bills" accept="image/*,.pdf">
                <input type="hidden" name="purchase_bills" id="purchase_bills" value="">
                <span class="small text-muted file-upload-status" data-for="purchase_bills"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">Copy of sales bills-2</label>
                <input type="file" class="form-control doc-file-input" data-field="sales_bills" accept="image/*,.pdf">
                <input type="hidden" name="sales_bills" id="sales_bills" value="">
                <span class="small text-muted file-upload-status" data-for="sales_bills"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">Copies of staff biodata -2</label>
                <input type="file" class="form-control doc-file-input" data-field="staff_biodata" accept="image/*,.pdf">
                <input type="hidden" name="staff_biodata" id="staff_biodata" value="">
                <span class="small text-muted file-upload-status" data-for="staff_biodata"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">Copy electricity bill</label>
                <input type="file" class="form-control doc-file-input" data-field="electricity_bill" accept="image/*,.pdf">
                <input type="hidden" name="electricity_bill" id="electricity_bill" value="">
                <span class="small text-muted file-upload-status" data-for="electricity_bill"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">Product Inspection / Testing Report</label>
                <input type="file" class="form-control doc-file-input" data-field="product_inspection" accept="image/*,.pdf">
                <input type="hidden" name="product_inspection" id="product_inspection" value="">
                <span class="small text-muted file-upload-status" data-for="product_inspection"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">List of employees/ Competence matrix</label>
                <input type="file" class="form-control doc-file-input" data-field="employee_competence_matrix" accept="image/*,.pdf">
                <input type="hidden" name="employee_competence_matrix" id="employee_competence_matrix" value="">
                <span class="small text-muted file-upload-status" data-for="employee_competence_matrix"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">Copy of Previous ISO/CE certificate</label>
                <input type="file" class="form-control doc-file-input" data-field="previous_iso_ce_certificate" accept="image/*,.pdf">
                <input type="hidden" name="previous_iso_ce_certificate" id="previous_iso_ce_certificate" value="">
                <span class="small text-muted file-upload-status" data-for="previous_iso_ce_certificate"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">List of suppliers</label>
                <input type="file" class="form-control doc-file-input" data-field="suppliers_list" accept="image/*,.pdf">
                <input type="hidden" name="suppliers_list" id="suppliers_list" value="">
                <span class="small text-muted file-upload-status" data-for="suppliers_list"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">Product Catalogued/Brochure</label>
                <input type="file" class="form-control doc-file-input" data-field="product_catalogue_brochure" accept="image/*,.pdf">
                <input type="hidden" name="product_catalogue_brochure" id="product_catalogue_brochure" value="">
                <span class="small text-muted file-upload-status" data-for="product_catalogue_brochure"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label">Pollution Clearance certificate of factories</label>
                <input type="file" class="form-control doc-file-input" data-field="pollution_clearance_certificate" accept="image/*,.pdf">
                <input type="hidden" name="pollution_clearance_certificate" id="pollution_clearance_certificate" value="">
                <span class="small text-muted file-upload-status" data-for="pollution_clearance_certificate"></span>
                <span class="ajax-error"></span>
            </div>

            <div class="mb-3 col-md-6 ajax-field">
                <label class="form-label" for="new_certificate_type_id">Service Requested System Standard <span class="text-danger">*</span></label>
                <select class="form-select" name="certificate_type_id" id="new_certificate_type_id">
                    <option value="">Select System Standard</option>
                    @foreach ($certificateTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->description }}</option>
                    @endforeach
                </select>
                <span class="ajax-error"></span>
            </div>
        </div>

        <button type="submit" class="btn btn-primary submit-button">Submit</button>
    </form>
</div>
