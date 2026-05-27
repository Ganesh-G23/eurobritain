@extends('admin.layouts.app')
@php
    $isEdit = ($mode ?? 'add') === 'edit';
    $selectedCertIds = $isEdit ? ($details->certificate_ids ?? []) : [];
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('admin/invoice/list') }}" class="btn btn-label-secondary btn-sm">Back</a>
            </div>
            <div class="card-body">
                <form id="ajax-form" method="POST"
                    action="{{ $isEdit ? url('admin/invoice/update/' . $details->id) : url('admin/invoice/save') }}">
                    @csrf

                    <div class="col-12 ajax-msg"></div>

                    <div class="row">
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="associate_id">Associate <span class="text-danger">*</span></label>
                            <select class="form-select" id="associate_id" name="associate_id">
                                <option value="">Select Associate</option>
                                @foreach ($associates as $associate)
                                    <option value="{{ $associate->id }}"
                                        {{ (int) old('associate_id', $details->associate_id ?? 0) === (int) $associate->id ? 'selected' : '' }}>
                                        {{ $associate->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="client_id">Client <span class="text-danger">*</span></label>
                            <select class="form-select" id="client_id" name="client_id" disabled>
                                <option value="">Select Client</option>
                            </select>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label" for="certificate_ids">Certificates <span class="text-danger">*</span></label>
                            <select class="form-select" id="certificate_ids" name="certificate_ids[]" multiple disabled>
                            </select>
                            <small class="text-muted">Only unbilled certificates for the selected client are shown.</small>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="amount">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount"
                                value="{{ old('amount', $details->amount ?? '') }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="invoice_date" name="invoice_date"
                                value="{{ old('invoice_date', $invoice_date) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label" for="admin_note">Admin Note</label>
                            <textarea class="form-control" id="admin_note" name="admin_note" rows="3">{{ old('admin_note', $details->admin_note) }}</textarea>
                            <span class="ajax-error"></span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary submit-button">{{ $isEdit ? 'Update' : 'Save' }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const isEdit = {{ $isEdit ? 'true' : 'false' }};
        const invoiceId = {{ $isEdit ? (int) $details->id : 0 }};
        const preselectedAssociateId = {{ (int) old('associate_id', $details->associate_id ?? 0) }};
        const preselectedClientId = {{ (int) old('client_id', $details->client_id ?? 0) }};
        const preselectedCertIds = @json(array_map('intval', old('certificate_ids', $selectedCertIds)));
        const clientsUrl = '{{ url('admin/invoice/clients') }}';
        const certificatesUrl = '{{ url('admin/invoice/certificates') }}';

        function recalcAmount() {
            let total = 0;
            $('#certificate_ids option:selected').each(function() {
                total += parseFloat($(this).data('amount')) || 0;
            });
            $('#amount').val(total > 0 ? total.toFixed(2) : '');
        }

        function initCertificateSelect2() {
            const $cert = $('#certificate_ids');
            if (!$cert.hasClass('select2-hidden-accessible')) {
                $cert.select2({
                    placeholder: 'Select certificates',
                    allowClear: true,
                    width: '100%'
                });
                $cert.off('change.invoiceAmount').on('change.invoiceAmount', recalcAmount);
            }
        }

        function loadClients(associateId, selectedClientId, callback) {
            const $client = $('#client_id');
            $client.prop('disabled', true).empty().append('<option value="">Loading...</option>');

            if (!associateId) {
                $client.empty().append('<option value="">Select Client</option>').prop('disabled', true);
                resetCertificates();
                if (callback) callback();
                return;
            }

            $.get(clientsUrl, {
                associate_id: associateId
            }, function(res) {
                $client.empty().append('<option value="">Select Client</option>');
                (res || []).forEach(function(c) {
                    $client.append('<option value="' + c.id + '">' + c.company_name + '</option>');
                });
                if (selectedClientId) {
                    $client.val(String(selectedClientId));
                }
                $client.prop('disabled', false);
                if (callback) callback();
            }, 'json');
        }

        function resetCertificates() {
            const $cert = $('#certificate_ids');
            $cert.empty().prop('disabled', true);
            if ($cert.hasClass('select2-hidden-accessible')) {
                $cert.trigger('change');
            }
            $('#amount').val('');
        }

        function loadCertificates(clientId, selectedIds) {
            const $cert = $('#certificate_ids');
            resetCertificates();

            if (!clientId) {
                return;
            }

            $cert.prop('disabled', true);
            const params = {
                client_id: clientId
            };
            if (invoiceId > 0) {
                params.invoice_id = invoiceId;
            }

            $.get(certificatesUrl, params, function(res) {
                (res || []).forEach(function(c) {
                    const selected = (selectedIds || []).map(String).includes(String(c.id)) ? ' selected' : '';
                    $cert.append(
                        '<option value="' + c.id + '" data-amount="' + c.amount + '"' + selected + '>' +
                        $('<div>').text(c.label).html() + '</option>'
                    );
                });

                // On edit, ensure already-billed certs on this invoice remain selected even if not in unbilled list
                if (isEdit && preselectedCertIds.length) {
                    const existingIds = {};
                    $cert.find('option').each(function() {
                        existingIds[$(this).val()] = true;
                    });
                    preselectedCertIds.forEach(function(id) {
                        if (!existingIds[String(id)]) {
                            $cert.append('<option value="' + id + '" data-amount="0" selected>Certificate #' +
                                id + ' (on this invoice)</option>');
                        }
                    });
                }

                $cert.prop('disabled', false);
                initCertificateSelect2();
                if (selectedIds && selectedIds.length) {
                    $cert.val(selectedIds.map(String)).trigger('change');
                } else {
                    $cert.trigger('change');
                    recalcAmount();
                }
            }, 'json');
        }

        $('#associate_id').on('change', function() {
            const associateId = $(this).val();
            resetCertificates();
            loadClients(associateId, null, function() {
                $('#client_id').val('').trigger('change');
            });
        });

        $('#client_id').on('change', function() {
            const clientId = $(this).val();
            loadCertificates(clientId, []);
        });

        $(document).on('submit', '#ajax-form', function(e) {
            e.preventDefault();
            clearAjaxErrors();
            const _this = $(this);
            const saveBtn = _this.find('.submit-button');
            saveBtn.prop('disabled', true).text('Saving...');

            $.post(_this.attr('action'), _this.serializeArray(), function(res) {
                saveBtn.prop('disabled', false).text('{{ $isEdit ? 'Update' : 'Save' }}');
                processAjaxResponse(res, 1000, _this);
            }, 'json').fail(function() {
                saveBtn.prop('disabled', false).text('{{ $isEdit ? 'Update' : 'Save' }}');
            });
        });

        $(function() {
            initCertificateSelect2();
            if (preselectedAssociateId > 0) {
                loadClients(preselectedAssociateId, preselectedClientId, function() {
                    if (preselectedClientId > 0) {
                        loadCertificates(preselectedClientId, preselectedCertIds);
                    }
                });
            }
        });
    </script>
@endsection
