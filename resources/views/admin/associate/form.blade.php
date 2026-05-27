@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('admin/associate/list') }}" class="btn btn-label-secondary btn-sm">Back to list</a>
            </div>
            <div class="card-body">
                <form id="ajax-form" method="POST"
                    action="{{ $mode === 'edit' ? url('admin/associate/update/' . $details->id) : url('admin/associate/save') }}">
                    @csrf
                    <div class="col-12 ajax-msg"></div>
                    <div class="row">
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="company_name">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_name" name="company_name"
                                value="{{ old('company_name', $details->company_name) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="contact_person">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person"
                                value="{{ old('contact_person', $details->contact_person) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="contact_email">Contact Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email"
                                value="{{ old('contact_email', $details->contact_email) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="contact_mobile">Contact Mobile <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_mobile" name="contact_mobile"
                                value="{{ old('contact_mobile', $details->contact_mobile) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label" for="address">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $details->address) }}</textarea>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="country_id">Country <span class="text-danger">*</span></label>
                            <select class="form-select" id="country_id" name="country_id">
                                <option value="">Select country</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}"
                                        {{ (string) old('country_id', $details->country_id) === (string) $country->id ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="state_id">State <span class="text-danger">*</span></label>
                            <select class="form-select" id="state_id" name="state_id">
                                <option value="">Select state</option>
                                @foreach ($preselectedStates as $state)
                                    <option value="{{ $state->id }}"
                                        {{ (string) old('state_id', $details->state_id) === (string) $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="city">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="city" name="city"
                                value="{{ old('city', $details->city) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="pincode">Pincode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="pincode" name="pincode"
                                value="{{ old('pincode', $details->pincode) }}">
                            <span class="ajax-error"></span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary me-2 submit-button">Save</button>
                        <a href="{{ url('admin/associate/list') }}" class="btn btn-label-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        function loadStates(countryId, selectedStateId) {
            const $state = $('#state_id');
            $state.empty().append('<option value="">Select state</option>');
            if (!countryId) {
                return;
            }
            $state.append('<option value="">Loading...</option>');
            $.get('{{ url('admin/common/states') }}', {
                country_id: countryId
            }, function(res) {
                $state.empty().append('<option value="">Select state</option>');
                (res || []).forEach(function(s) {
                    const selected = String(selectedStateId) === String(s.id) ? ' selected' : '';
                    $state.append('<option value="' + s.id + '"' + selected + '>' + s.name + '</option>');
                });
            }, 'json');
        }

        $('#country_id').on('change', function() {
            loadStates($(this).val(), '');
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
