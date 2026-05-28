@extends('associate.layouts.app')
@php
    $isEdit = ($mode ?? 'add') === 'edit';
    $director = old('director_details', $details->director_details ?? []);
    $employee = old('employee_details', $details->employee_details ?? []);
    $addressShifts = old('address_shift_details', $details->address_shift_details ?? []);
    $selectedAuditTypes = old('service_request_audit_type', $details->service_request_audit_type ?? []);
    $companyName = $isEdit ? $details->company_name : ($client->company_name ?? '');
    $address = $isEdit ? $details->address : ($client->address ?? '');
    $contactMobile = $isEdit ? $details->contact_mobile : ($client->contact_mobile ?? '');
    $contactEmail = $isEdit ? $details->contact_email : ($client->contact_email ?? '');
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('certificate-application/list') }}" class="btn btn-label-secondary btn-sm">Back to list</a>
            </div>
            <div class="card-body">
                <form id="ajax-form" method="POST"
                    action="{{ $isEdit ? url('certificate-application/update/' . $details->id) : url('certificate-application/save_application') }}">
                    @csrf
                    <input type="hidden" name="client_id" value="{{ $isEdit ? $details->client_id : $client_id }}">
                    <input type="hidden" name="certificate_type_id" value="{{ $isEdit ? $details->certificate_type_id : $certificate_type_id }}">

                    <div class="col-12 ajax-msg"></div>

                    @if (!$isEdit && isset($certificateType))
                        <div class="alert alert-secondary py-2 mb-4">
                            <strong>Certificate Type:</strong> {{ $certificateType->description ?? $certificateType->code }}
                        </div>
                    @endif

                    <div class="row">
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label">Full Name Of The Company <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="company_name" value="{{ $companyName }}" readonly>
                        </div>
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label">Full Address of The Company <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="address" rows="2" readonly>{{ $address }}</textarea>
                        </div>
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label">Scope (In English) <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="scope" rows="3">{{ old('scope', $details->scope) }}</textarea>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contact_mobile" value="{{ $contactMobile }}" readonly>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Fax Number</label>
                            <input type="text" class="form-control" name="fax_number" value="{{ old('fax_number', $details->fax_number) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="contact_email" value="{{ $contactEmail }}" readonly>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Website</label>
                            <input type="text" class="form-control" name="website" value="{{ old('website', $details->website) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Name and Title of the person who will be communicated <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="communication_person" value="{{ old('communication_person', $details->communication_person) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Management Representative Name</label>
                            <input type="text" class="form-control" name="management_representative" value="{{ old('management_representative', $details->management_representative) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Name and Title of the Top Manager</label>
                            <input type="text" class="form-control" name="top_manager" value="{{ old('top_manager', $details->top_manager) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Mobile Phone of the Top Management</label>
                            <input type="text" class="form-control" name="top_management_mobile" value="{{ old('top_management_mobile', $details->top_management_mobile) }}">
                            <span class="ajax-error"></span>
                        </div>
                    </div>

                    <h6 class="mb-3">Name of Director/Partner/Proprietor</h6>
                    <div class="row">
                        <div class="mb-3 col-md-4 ajax-field">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="director_details[first_name]" value="{{ old('director_details.first_name', $director['first_name'] ?? '') }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-4 ajax-field">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="director_details[middle_name]" value="{{ old('director_details.middle_name', $director['middle_name'] ?? '') }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-4 ajax-field">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="director_details[last_name]" value="{{ old('director_details.last_name', $director['last_name'] ?? '') }}">
                            <span class="ajax-error"></span>
                        </div>
                    </div>

                    <h6 class="mb-3">Employee Details</h6>
                    <div class="row">
                        <div class="mb-3 col-md-4 ajax-field">
                            <label class="form-label">Employee Number</label>
                            <input type="text" class="form-control" name="employee_details[employee_number]" value="{{ old('employee_details.employee_number', $employee['employee_number'] ?? '') }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-4 ajax-field">
                            <label class="form-label">Full Time</label>
                            <input type="text" class="form-control" name="employee_details[full_time]" value="{{ old('employee_details.full_time', $employee['full_time'] ?? '') }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-4 ajax-field">
                            <label class="form-label">Part Time</label>
                            <input type="text" class="form-control" name="employee_details[part_time]" value="{{ old('employee_details.part_time', $employee['part_time'] ?? '') }}">
                            <span class="ajax-error"></span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Number of sites & shifts (if more than one please provide the details) <span class="text-danger">*</span></h6>
                        <button type="button" class="btn btn-sm btn-success" id="add-address-block">+</button>
                    </div>
                    <div id="address-shift-container"></div>
                    <span class="ajax-error d-block" data-field="address_shift_details"></span>

                    <div class="row mt-2">
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Subcontractor</label>
                            <input type="text" class="form-control" name="subcontractor" value="{{ old('subcontractor', $details->subcontractor) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">In main Process</label>
                            <input type="text" class="form-control" name="in_main_process" value="{{ old('in_main_process', $details->in_main_process) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Number of Executive Personnel</label>
                            <input type="text" class="form-control" name="executive_personnel" value="{{ old('executive_personnel', $details->executive_personnel) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">In Design</label>
                            <input type="text" class="form-control" name="in_design" value="{{ old('in_design', $details->in_design) }}">
                            <span class="ajax-error"></span>
                        </div>
                    </div>

                    <div class="mb-3 ajax-field">
                        <label class="form-label d-block">Service Requested Audit Type <span class="text-danger">*</span></label>
                        @foreach ($auditTypes as $auditType)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="service_request_audit_type[]"
                                    id="audit_type_{{ $auditType->id }}" value="{{ $auditType->id }}"
                                    {{ in_array($auditType->id, array_map('intval', (array) $selectedAuditTypes)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="audit_type_{{ $auditType->id }}">{{ $auditType->name }}</label>
                            </div>
                        @endforeach
                        <span class="ajax-error d-block"></span>
                    </div>

                    <button type="submit" class="btn btn-primary submit-button">{{ $isEdit ? 'Update' : 'Submit' }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const initialAddressShifts = @json($addressShifts);

        function buildShiftRow(addrIndex, shiftIndex, fromVal = '', toVal = '') {
            return `
                <div class="row g-2 align-items-end shift-row mb-2" data-shift-index="${shiftIndex}">
                    <div class="col-md-5">
                        <label class="form-label small">From</label>
                        <input type="text" class="form-control" name="address_shift_details[${addrIndex}][shifts][${shiftIndex}][from]" value="${fromVal}" placeholder="09:00">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">To</label>
                        <input type="text" class="form-control" name="address_shift_details[${addrIndex}][shifts][${shiftIndex}][to]" value="${toVal}" placeholder="18:00">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-shift-row">×</button>
                    </div>
                </div>`;
        }

        function buildAddressBlock(addrIndex, addressVal = '', shifts = [{
            from: '',
            to: ''
        }]) {
            let shiftsHtml = '';
            (shifts || [{
                from: '',
                to: ''
            }]).forEach(function(shift, sIdx) {
                shiftsHtml += buildShiftRow(addrIndex, sIdx, shift.from || '', shift.to || '');
            });
            return `
                <div class="card mb-3 address-block" data-address-index="${addrIndex}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Site ${addrIndex + 1}</strong>
                            <div>
                                <button type="button" class="btn btn-sm btn-success add-shift-row">+ Shift</button>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-address-block">Remove Site</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="address_shift_details[${addrIndex}][address]" rows="2">${addressVal}</textarea>
                        </div>
                        <div class="shifts-container">${shiftsHtml}</div>
                    </div>
                </div>`;
        }

        function reindexAddressBlocks() {
            $('#address-shift-container .address-block').each(function(addrIdx) {
                $(this).attr('data-address-index', addrIdx);
                $(this).find('strong').first().text('Site ' + (addrIdx + 1));
                $(this).find('[name^="address_shift_details"]').each(function() {
                    const name = $(this).attr('name');
                    if (!name) return;
                    const updated = name.replace(/address_shift_details\[\d+\]/, 'address_shift_details[' +
                        addrIdx + ']');
                    $(this).attr('name', updated);
                });
                $(this).find('.shift-row').each(function(shiftIdx) {
                    $(this).attr('data-shift-index', shiftIdx);
                    $(this).find('[name*="[shifts]"]').each(function() {
                        const name = $(this).attr('name');
                        const updated = name.replace(/\[shifts\]\[\d+\]/, '[shifts][' + shiftIdx +
                            ']');
                        $(this).attr('name', updated);
                    });
                });
            });
        }

        function addAddressBlock(addressVal = '', shifts = null) {
            const addrIndex = $('#address-shift-container .address-block').length;
            $('#address-shift-container').append(buildAddressBlock(addrIndex, addressVal, shifts || [{
                from: '',
                to: ''
            }]));
        }

        if (initialAddressShifts && initialAddressShifts.length) {
            initialAddressShifts.forEach(function(item) {
                addAddressBlock(item.address || '', item.shifts || [{
                    from: '',
                    to: ''
                }]);
            });
        } else {
            addAddressBlock();
        }

        $('#add-address-block').on('click', function() {
            addAddressBlock();
            reindexAddressBlocks();
        });

        $(document).on('click', '.remove-address-block', function() {
            if ($('#address-shift-container .address-block').length <= 1) {
                alert('At least one site is required.');
                return;
            }
            $(this).closest('.address-block').remove();
            reindexAddressBlocks();
        });

        $(document).on('click', '.add-shift-row', function() {
            const $block = $(this).closest('.address-block');
            const addrIndex = $block.data('address-index');
            const shiftIndex = $block.find('.shift-row').length;
            $block.find('.shifts-container').append(buildShiftRow(addrIndex, shiftIndex));
        });

        $(document).on('click', '.remove-shift-row', function() {
            const $block = $(this).closest('.address-block');
            if ($block.find('.shift-row').length <= 1) {
                alert('At least one shift is required per site.');
                return;
            }
            $(this).closest('.shift-row').remove();
            reindexAddressBlocks();
        });

        $(document).on('submit', '#ajax-form', function(e) {
            e.preventDefault();
            clearAjaxErrors();
            const _this = $(this);
            const saveBtn = _this.find('.submit-button');
            saveBtn.prop('disabled', true).text('Saving...');

            $.post(_this.attr('action'), _this.serializeArray(), function(res) {
                saveBtn.prop('disabled', false).text('{{ $isEdit ? 'Update' : 'Submit' }}');
                processAjaxResponse(res, 1000, _this);
            }, 'json').fail(function() {
                saveBtn.prop('disabled', false).text('{{ $isEdit ? 'Update' : 'Submit' }}');
            });
        });
    </script>
@endsection
