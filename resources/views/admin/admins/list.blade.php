@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">Administrators</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                data-bs-target="#addAdminModal">
                                <i class="icon-base ti tabler-user-plus me-1"></i> Add administrator
                            </button>
                            <button type="button" class="btn btn-sm btn-label-primary" data-bs-toggle="modal"
                                data-bs-target="#importAdminsModal">
                                <i class="icon-base ti tabler-upload me-1"></i> Import CSV
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ url('admin/admins') }}" class="mb-4">
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-6 col-md-8">
                                    <label class="form-label">Search</label>
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Name, email or phone" value="{{ $search ?? '' }}">
                                </div>
                                <div class="col-lg-3 col-md-4 d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-primary">Apply</button>
                                    <a href="{{ url('admin/admins') }}" class="btn btn-label-secondary">Reset</a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th class="text-center">Level</th>
                                        <th class="text-center">Email 2FA</th>
                                        <th>Last login</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($admins ?? [] as $row)
                                        <tr>
                                            <td>{{ $row->id }}</td>
                                            <td class="fw-medium">{{ $row->name }}</td>
                                            <td>{{ $row->email }}</td>
                                            <td>{{ $row->phone ?? '—' }}</td>
                                            <td class="text-center">
                                                {{ (int) $row->user_level === 1 ? 'Super' : 'Admin' }}
                                            </td>
                                            <td class="text-center">
                                                {{ $row->email_two_factor_enabled ? 'On' : 'Off' }}
                                            </td>
                                            <td>{{ $row->last_login_at ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-body-secondary">No administrators found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if (isset($admins) && method_exists($admins, 'links'))
                            <div class="mt-3">{{ $admins->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add administrator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="add-admin-form" method="POST" action="{{ url('admin/admins') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-12 ajax-msg mb-2"></div>
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Visible name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Email (sign-in) <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required autocomplete="off">
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-3 ajax-field">
                            <label class="form-label">Temporary password <span class="text-danger">*</span></label>
                            <input type="text" name="password" class="form-control" required autocomplete="new-password"
                                minlength="6">
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="mb-0 ajax-field">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <p class="form-text small mb-0 mt-2">New accounts are created as standard admins (level 2) and must
                            change this password after first sign-in.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary submit-button">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importAdminsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import administrators (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3 small">
                        <strong>Required columns:</strong> <code>email</code>, <code>name</code>,
                        <code>temporary_password</code> (or <code>password</code>).<br>
                        <strong>Optional:</strong> <code>phone</code>.<br>
                        All imported accounts are standard admins (level 2) and must change their password on first sign-in.
                    </div>
                    <div class="mb-3">
                        <a href="{{ url('admin/admins/bulk-sample') }}" class="btn btn-label-primary">
                            <i class="icon-base ti tabler-download me-1"></i> Download sample CSV
                        </a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CSV file</label>
                        <input type="file" class="form-control" id="import_admin_csv_file" accept=".csv,text/csv">
                        <small class="text-body-secondary">Max 5 MB</small>
                    </div>
                    <div id="import-admin-msg" class="mb-0 small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="btn-import-admins-upload">
                        <i class="icon-base ti tabler-upload me-1"></i> Upload
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).on('submit', '#add-admin-form', function(e) {
            e.preventDefault();
            clearAjaxErrors();
            const form = $(this);
            const btn = form.find('.submit-button');
            btn.prop('disabled', true).text('Saving...');
            $.post(form.attr('action'), form.serializeArray(), function(res) {
                btn.prop('disabled', false).text('Create');
                processAjaxResponse(res, 800);
            }, 'json');
        });

        $('#btn-import-admins-upload').on('click', function() {
            const input = document.getElementById('import_admin_csv_file');
            const msg = $('#import-admin-msg');
            msg.html('');
            if (!input.files || !input.files.length) {
                msg.html('<span class="text-danger">Choose a CSV file first.</span>');
                return;
            }
            const fd = new FormData();
            fd.append('file', input.files[0]);
            fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
            const btn = $(this);
            btn.prop('disabled', true).text('Uploading...');
            $.ajax({
                url: '{{ url('admin/admins/bulk-upload') }}',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(res) {
                    btn.prop('disabled', false).html(
                        '<i class="icon-base ti tabler-upload me-1"></i> Upload');
                    if (res.status == 1) {
                        msg.html('<span class="text-success">' + (res.msg || 'Done') + '</span>');
                        setTimeout(function() {
                            window.location.href = res.redirect_url || '{{ url('admin/admins') }}';
                        }, 1200);
                    } else if (res.error) {
                        msg.html('<span class="text-danger">' + res.error + '</span>');
                    } else {
                        msg.html('<span class="text-danger">Import failed.</span>');
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html(
                        '<i class="icon-base ti tabler-upload me-1"></i> Upload');
                    msg.html('<span class="text-danger">Request failed.</span>');
                }
            });
        });
    </script>
@endsection
