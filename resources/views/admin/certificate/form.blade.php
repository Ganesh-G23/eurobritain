@extends('admin.layouts.app')
@php
    $isEdit = ($mode ?? 'add') === 'edit';
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ $isEdit ? url('admin/certificate/list') : url('admin/certificate/due-list') }}"
                    class="btn btn-label-secondary btn-sm">Back</a>
            </div>
            <div class="card-body">
                <form id="ajax-form" method="POST"
                    action="{{ $isEdit ? url('admin/certificate/update/' . $details->id) : url('admin/certificate/save') }}">
                    @csrf
                    @if (!$isEdit)
                        <input type="hidden" name="certificate_application_id" value="{{ $application->id }}">
                    @endif

                    <div class="col-12 ajax-msg"></div>

                    <div class="row">
                        <div class="mb-3 col-md-4">
                            <label class="form-label">Associate</label>
                            <input type="text" class="form-control" readonly
                                value="{{ $associate->company_name ?? '—' }}">
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label">Client</label>
                            <input type="text" class="form-control" readonly value="{{ $client->company_name ?? '—' }}">
                        </div>
                        <div class="mb-3 col-md-4">
                            <label class="form-label">Certificate Type</label>
                            <input type="text" class="form-control" readonly
                                value="{{ $certificateType->description ?? $certificateType->code ?? '—' }}">
                        </div>
                        @if ($isEdit)
                            <div class="mb-3 col-md-6">
                                <label class="form-label">Certificate Number</label>
                                <input type="text" class="form-control" readonly value="{{ $details->certificate_number }}">
                            </div>
                        @endif
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" name="amount"
                                value="{{ old('amount', $details->amount ?? $certificateType->price ?? '') }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="issue_date"
                                value="{{ old('issue_date', $issue_date) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Initial Certificate Granted On</label>
                            <input type="date" class="form-control" id="issue_date"  name="initial_certificate_granted_on"
                                value="{{ old('initial_certificate_granted_on', $details->initial_certificate_granted_on?->format('Y-m-d')) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Date of Latest Audit</label>
                            <input type="date" class="form-control" name="latest_audit_date"
                                value="{{ old('latest_audit_date', $details->latest_audit_date?->format('Y-m-d')) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Date of Expiry</label>
                            <input type="date" class="form-control" id="date_of_expiry" readonly
                                value="{{ $date_of_expiry }}">
                        </div>
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label">Scope</label>
                            <textarea class="form-control" name="scope" rows="3">{{ old('scope', $details->scope) }}</textarea>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label">Admin Note</label>
                            <textarea class="form-control" name="admin_note" rows="3">{{ old('admin_note', $details->admin_note) }}</textarea>
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
        const renewalDays = {{ (int) ($renewal_days ?? 365) }};
        const auditDays = {{ (int) ($audit_days ?? 365) }};
        const renewalPeriodRaw = @json($certificateType->renewal_period ?? '');
        const auditPeriodRaw = @json($certificateType->audit_period ?? '');
        const logExpiryUrl = '{{ url('admin/certificate/log-expiry-calc') }}';
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        function formatLocalDate(d) {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function logClientExpiry(payload) {
            console.log('[CertificateExpiry] client_form_recalc', payload);
            $.post(logExpiryUrl, Object.assign({
                _token: csrfToken,
                source: 'certificate_form'
            }, payload));
        }

        function recalcExpiryDates() {
            const issueVal = $('#issue_date').val();
            if (!issueVal) {
                return;
            }
            const issue = new Date(issueVal + 'T00:00:00');
            const expiry = new Date(issue);
            expiry.setDate(expiry.getDate() + renewalDays);
            const calculatedExpiry = formatLocalDate(expiry);
            $('#date_of_expiry').val(calculatedExpiry);

            logClientExpiry({
                issue_date: issueVal,
                renewal_period_raw: renewalPeriodRaw,
                audit_period_raw: auditPeriodRaw,
                renewal_days_used_in_js: renewalDays,
                audit_days_used_in_js: auditDays,
                calculated_date_of_expiry: calculatedExpiry,
                client_formula: 'date_of_expiry = issue_date + renewal_days_used_in_js (audit_days NOT applied on this form)',
                note: renewalDays !== parseInt(String(renewalPeriodRaw).replace(/\D/g, ''), 10)
                    ? 'JS renewal_days may not match first number in renewal_period_raw — check server parse_period_days log'
                    : null
            });
        }

        $('#issue_date').on('change', recalcExpiryDates);

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
