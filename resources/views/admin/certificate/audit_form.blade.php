@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('admin/certificate/due-audit-list') }}" class="btn btn-label-secondary btn-sm">Back</a>
            </div>
            <div class="card-body">
                <form id="ajax-form" method="POST" action="{{ url('admin/certificate/save-audit') }}">
                    @csrf
                    <input type="hidden" name="certificate_application_id" value="{{ $application->id }}">

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
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="issue_date" name="issue_date"
                                value="{{ old('issue_date', $issue_date) }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6 ajax-field">
                            <label class="form-label">Latest Audit Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="latest_audit_date" name="latest_audit_date"
                                value="{{ old('latest_audit_date') }}">
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label">Audit Expiry Date</label>
                            <input type="date" class="form-control" id="audit_expiry_date" readonly
                                value="{{ $audit_expiry_date }}">
                        </div>
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label">Scope</label>
                            <textarea class="form-control" name="scope" rows="3">{{ old('scope') }}</textarea>
                            <span class="ajax-error"></span>
                        </div>
                        <div class="mb-3 col-12 ajax-field">
                            <label class="form-label">Admin Note</label>
                            <textarea class="form-control" name="admin_note" rows="3">{{ old('admin_note') }}</textarea>
                            <span class="ajax-error"></span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary submit-button">Save Audit</button>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const auditDays = {{ (int) ($audit_days ?? 365) }};
        const auditPeriodRaw = @json($certificateType->audit_period ?? '');
        const renewalPeriodRaw = @json($certificateType->renewal_period ?? '');
        const logExpiryUrl = '{{ url('admin/certificate/log-expiry-calc') }}';
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        function formatLocalDate(d) {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function recalcAuditExpiry() {
            const latestAuditVal = $('#latest_audit_date').val();
            if (!latestAuditVal) {
                return;
            }
            const latestAudit = new Date(latestAuditVal + 'T00:00:00');
            const auditExpiry = new Date(latestAudit);
            auditExpiry.setDate(auditExpiry.getDate() + auditDays);
            const calculatedAuditExpiry = formatLocalDate(auditExpiry);
            $('#audit_expiry_date').val(calculatedAuditExpiry);

            const payload = {
                source: 'audit_form',
                latest_audit_date: latestAuditVal,
                audit_period_raw: auditPeriodRaw,
                renewal_period_raw: renewalPeriodRaw,
                audit_days_used_in_js: auditDays,
                calculated_audit_expiry_date: calculatedAuditExpiry,
                client_formula: 'audit_expiry_date = latest_audit_date + audit_days_used_in_js'
            };
            console.log('[CertificateExpiry] client_form_recalc', payload);
            $.post(logExpiryUrl, Object.assign({ _token: csrfToken }, payload));
        }

        $('#latest_audit_date').on('change', recalcAuditExpiry);

        $(document).on('submit', '#ajax-form', function(e) {
            e.preventDefault();
            clearAjaxErrors();
            const _this = $(this);
            const saveBtn = _this.find('.submit-button');
            saveBtn.prop('disabled', true).text('Saving...');

            $.post(_this.attr('action'), _this.serializeArray(), function(res) {
                saveBtn.prop('disabled', false).text('Save Audit');
                processAjaxResponse(res, 1000, _this);
            }, 'json').fail(function() {
                saveBtn.prop('disabled', false).text('Save Audit');
            });
        });
    </script>
@endsection
