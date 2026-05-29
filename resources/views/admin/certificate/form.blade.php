@extends('admin.layouts.app')
@php
    $isEdit = ($mode ?? 'add') === 'edit';
    $certMode = $cert_mode ?? ($isEdit ? 'edit' : 'add');
    $requireLatestAudit = ! $isEdit;
    $showAuditExpiryPreview = ! $isEdit;
    $initialGrantedValue = old(
        'initial_certificate_granted_on',
        $isEdit
            ? $details->initial_certificate_granted_on?->format('Y-m-d')
            : ($certMode === 'renewal' ? ($initial_granted_default ?? '') : '')
    );
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
                        <input type="hidden" name="cert_mode" value="{{ $certMode }}">
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
                            <input type="date" class="form-control" id="issue_date" name="issue_date"
                                value="{{ old('issue_date', $issue_date) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Initial Certificate Granted On</label>
                            <input type="date" class="form-control" id="initial_certificate_granted_on"
                                name="initial_certificate_granted_on" value="{{ $initialGrantedValue }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Date of Latest Audit
                                @if ($requireLatestAudit)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <input type="date" class="form-control" id="latest_audit_date" name="latest_audit_date"
                                value="{{ old('latest_audit_date', $details->latest_audit_date?->format('Y-m-d')) }}"
                                @if ($requireLatestAudit) required @endif>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Date of Expiry</label>
                            <input type="date" class="form-control" id="date_of_expiry" readonly
                                value="{{ $date_of_expiry }}">
                        </div>
                        @if ($showAuditExpiryPreview)
                            <div class="mb-3 col-md-6">
                                <label class="form-label">Audit Expiry Date</label>
                                <input type="date" class="form-control" id="audit_expiry_date_preview" readonly
                                    value="{{ $audit_expiry_date }}">
                            </div>
                        @endif
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label">Scope</label>
                            <textarea class="form-control" name="scope" rows="3">{{ old('scope', $details->scope ?? ($isEdit ? null : ($application->scope ?? ''))) }}</textarea>
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
        const renewalYears = {{ (int) ($renewal_years ?? 1) }};
        const auditYears = {{ (int) ($audit_years ?? 1) }};
        const certMode = @json($certMode);
        const renewalPeriodRaw = @json($certificateType->renewal_period ?? '');
        const auditPeriodRaw = @json($certificateType->audit_period ?? '');
        const logExpiryUrl = '{{ url('admin/certificate/log-expiry-calc') }}';
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const showAuditExpiryPreview = {{ $showAuditExpiryPreview ? 'true' : 'false' }};

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

        function recalcAuditExpiryPreview() {
            if (!showAuditExpiryPreview) {
                return;
            }
            const latestAuditVal = $('#latest_audit_date').val();
            if (!latestAuditVal) {
                $('#audit_expiry_date_preview').val('');
                return;
            }
            const latestAudit = new Date(latestAuditVal + 'T00:00:00');
            const auditExpiry = new Date(latestAudit);
            auditExpiry.setFullYear(auditExpiry.getFullYear() + auditYears);
            const calculatedAuditExpiry = formatLocalDate(auditExpiry);
            $('#audit_expiry_date_preview').val(calculatedAuditExpiry);

            logClientExpiry({
                latest_audit_date: latestAuditVal,
                audit_period_raw: auditPeriodRaw,
                audit_years_used_in_js: auditYears,
                calculated_audit_expiry_date: calculatedAuditExpiry,
                client_formula: 'audit_expiry_date = latest_audit_date + audit_years_used_in_js'
            });
        }

        function prefillFromIssueDate() {
            const issueVal = $('#issue_date').val();
            if (!issueVal) {
                return;
            }
            if (!$('#latest_audit_date').val()) {
                $('#latest_audit_date').val(issueVal);
            }
            if (certMode === 'first_issue' && !$('#initial_certificate_granted_on').val()) {
                $('#initial_certificate_granted_on').val(issueVal);
            }
        }

        function recalcExpiryDates() {
            const issueVal = $('#issue_date').val();
            if (!issueVal) {
                return;
            }
            const issue = new Date(issueVal + 'T00:00:00');
            const expiry = new Date(issue);
            expiry.setFullYear(expiry.getFullYear() + renewalYears);
            const calculatedExpiry = formatLocalDate(expiry);
            $('#date_of_expiry').val(calculatedExpiry);

            prefillFromIssueDate();
            recalcAuditExpiryPreview();

            logClientExpiry({
                issue_date: issueVal,
                renewal_period_raw: renewalPeriodRaw,
                audit_period_raw: auditPeriodRaw,
                renewal_years_used_in_js: renewalYears,
                audit_years_used_in_js: auditYears,
                calculated_date_of_expiry: calculatedExpiry,
                client_formula: 'date_of_expiry = issue_date + renewal_years; audit_expiry_date = latest_audit_date + audit_years'
            });
        }

        $('#issue_date').on('change', recalcExpiryDates);
        $('#latest_audit_date').on('change', recalcAuditExpiryPreview);

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
