@extends('admin.layouts.app')
@php
    $isEdit = ($mode ?? 'add') === 'edit';
    $isLocked = !empty($prefilledInvoice);
    $preselectedAssociateId = (int) old('associate_id', $details->associate_id ?? ($prefilledInvoice->associate_id ?? 0));
    $preselectedClientId = (int) old('client_id', $details->client_id ?? ($prefilledInvoice->client_id ?? 0));
    $preselectedInvoiceId = (int) old('invoice_id', $details->invoice_id ?? ($prefilledInvoice->id ?? 0));
    $prefillAmount = old('amount', $details->amount ?? ($prefilledInvoice->amount ?? ''));
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('admin/payment/list') }}" class="btn btn-label-secondary btn-sm">Back</a>
            </div>
            <div class="card-body">
                <form id="ajax-form" method="POST"
                    action="{{ $isEdit ? url('admin/payment/update/' . $details->id) : url('admin/payment/save') }}">
                    @csrf

                    <div class="col-12 ajax-msg"></div>

                    <div class="row">
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="associate_id">Associate <span class="text-danger">*</span></label>
                            <select class="form-select" id="associate_id" name="associate_id" {{ $isLocked ? 'disabled' : '' }}>
                                <option value="">Select Associate</option>
                                @foreach ($associates as $associate)
                                    <option value="{{ $associate->id }}"
                                        {{ $preselectedAssociateId === (int) $associate->id ? 'selected' : '' }}>
                                        {{ $associate->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($isLocked)
                                <input type="hidden" name="associate_id" value="{{ $preselectedAssociateId }}">
                            @endif
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="client_id">Client <span class="text-danger">*</span></label>
                            <select class="form-select" id="client_id" name="client_id" {{ $isLocked ? 'disabled' : '' }}>
                                @if ($isLocked && $prefilledInvoice)
                                    <option value="{{ $prefilledInvoice->client_id }}" selected>
                                        {{ $prefilledInvoice->client->company_name ?? 'Client' }}
                                    </option>
                                @else
                                    <option value="">Select Client</option>
                                @endif
                            </select>
                            @if ($isLocked)
                                <input type="hidden" name="client_id" value="{{ $preselectedClientId }}">
                            @endif
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label" for="invoice_id">Invoice <span class="text-danger">*</span></label>
                            <select class="form-select text-select2" id="invoice_id" name="invoice_ids[]" {{ $isEdit ? '' : 'multiple' }} {{ $isLocked ? 'disabled' : '' }}>
                                @if ($isLocked && $prefilledInvoice)
                                    <option value="{{ $prefilledInvoice->id }}" selected
                                        data-amount="{{ (float) $prefilledInvoice->pending_amount }}">
                                        {{ $prefilledInvoice->invoice_number }} — Pending: {{ number_format($prefilledInvoice->pending_amount, 2) }}
                                    </option>
                                @elseif ($isEdit && $details->invoice)
                                    <option value="{{ $details->invoice_id }}" selected
                                        data-amount="{{ (float) $details->amount }}">
                                        {{ $details->invoice->invoice_number }} — Amount: {{ number_format($details->amount, 2) }}
                                    </option>
                                @else
                                    @if ($isEdit)
                                        <option value="">Select Invoice</option>
                                    @endif
                                @endif
                            </select>
                            @if ($isLocked)
                                <input type="hidden" name="invoice_ids[]" value="{{ $preselectedInvoiceId }}">
                            @endif
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="amount">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount"
                                value="{{ $prefillAmount }}" readonly>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label" for="payment_date">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date"
                                value="{{ old('payment_date', $payment_date) }}">
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
        const isLocked = {{ $isLocked ? 'true' : 'false' }};
        const isEdit = {{ $isEdit ? 'true' : 'false' }};
        const paymentId = {{ $isEdit ? (int) $details->id : 0 }};
        const preselectedAssociateId = {{ $preselectedAssociateId }};
        const preselectedClientId = {{ $preselectedClientId }};
        const preselectedInvoiceId = {{ $preselectedInvoiceId }};
        const clientsUrl = '{{ url('admin/payment/clients') }}';
        const invoicesUrl = '{{ url('admin/payment/invoices') }}';

        function fillAmountFromInvoice() {
            let total = 0;
            $('#invoice_id option:selected').each(function() {
                total += parseFloat($(this).data('amount')) || 0;
            });
            $('#amount').val(total > 0 ? total.toFixed(2) : '0.00');
        }

        function loadClients(associateId, selectedClientId, callback) {
            const $client = $('#client_id');
            $client.prop('disabled', true).empty().append('<option value="">Loading...</option>');

            if (!associateId) {
                $client.empty().append('<option value="">Select Client</option>').prop('disabled', true);
                resetInvoices();
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

        function resetInvoices() {
            const $invoice = $('#invoice_id');
            $invoice.empty();
            if (isEdit) {
                $invoice.append('<option value="">Select Invoice</option>');
            }
            $invoice.prop('disabled', true);
            if ($invoice.hasClass('select2-hidden-accessible')) {
                $invoice.val(null).trigger('change');
            }
            $('#amount').val('0.00');
        }

        function loadInvoices(clientId, selectedInvoiceId) {
            const $invoice = $('#invoice_id');
            resetInvoices();

            if (!clientId) {
                return;
            }

            $invoice.prop('disabled', true);
            const params = {
                client_id: clientId
            };
            if (paymentId > 0) {
                params.payment_id = paymentId;
            }

            $.get(invoicesUrl, params, function(res) {
                $invoice.empty();
                if (isEdit) {
                    $invoice.append('<option value="">Select Invoice</option>');
                }
                (res || []).forEach(function(inv) {
                    const isSel = Array.isArray(selectedInvoiceId)
                        ? selectedInvoiceId.map(String).includes(String(inv.id))
                        : String(inv.id) === String(selectedInvoiceId);
                    const selected = isSel ? ' selected' : '';
                    $invoice.append(
                        '<option value="' + inv.id + '" data-amount="' + inv.amount + '"' + selected + '>' +
                        $('<div>').text(inv.label).html() + '</option>'
                    );
                });
                $invoice.prop('disabled', false);

                if (!isEdit && !$invoice.hasClass('select2-hidden-accessible')) {
                    $invoice.select2({
                        placeholder: 'Select invoices',
                        allowClear: true,
                        width: '100%'
                    });
                }

                if (selectedInvoiceId) {
                    if (Array.isArray(selectedInvoiceId)) {
                        $invoice.val(selectedInvoiceId.map(String)).trigger('change');
                    } else {
                        $invoice.val(String(selectedInvoiceId)).trigger('change');
                    }
                } else {
                    $invoice.trigger('change');
                }
                fillAmountFromInvoice();
            }, 'json');
        }

        if (!isLocked) {
            $('#associate_id').on('change', function() {
                const associateId = $(this).val();
                resetInvoices();
                loadClients(associateId, null, function() {
                    $('#client_id').val('').trigger('change');
                });
            });

            $('#client_id').on('change', function() {
                const clientId = $(this).val();
                loadInvoices(clientId, null);
            });

            $('#invoice_id').on('change', fillAmountFromInvoice);

            $(function() {
                if (preselectedAssociateId > 0) {
                    loadClients(preselectedAssociateId, preselectedClientId, function() {
                        if (preselectedClientId > 0) {
                            loadInvoices(preselectedClientId, preselectedInvoiceId);
                        }
                    });
                }
            });
        } else {
            fillAmountFromInvoice();
        }

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
    </script>
@endsection
