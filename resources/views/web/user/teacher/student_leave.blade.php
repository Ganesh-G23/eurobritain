@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y pt-2 pb-2">
        <div class="row g-6">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $title ?? 'Student Leave' }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student name</th>
                                        <th>From date</th>
                                        <th>To date</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th class="text-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($student_leave_requests ?? [] as $index => $req)
                                        @php
                                            $st = strtolower((string) ($req->status ?? 'pending'));
                                            $isPending = $st === 'pending';
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $req->student->name ?? '—' }}</td>
                                            <td><small
                                                    class="text-body-secondary">{{ $req->from_date ? \Illuminate\Support\Carbon::parse($req->from_date)->format('d M Y') : '—' }}</small>
                                            </td>
                                            <td><small
                                                    class="text-body-secondary">{{ $req->to_date ? \Illuminate\Support\Carbon::parse($req->to_date)->format('d M Y') : '—' }}</small>
                                            </td>
                                            <td>{{ $req->reason !== null && $req->reason !== '' ? $req->reason : '—' }}</td>
                                            <td>
                                                @if ($st === 'approved')
                                                    <span class="badge bg-label-success">Approved</span>
                                                @elseif ($st === 'rejected')
                                                    <span class="badge bg-label-danger">Rejected</span>
                                                @else
                                                    <span class="badge bg-label-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($isPending)
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <button type="button"
                                                            class="btn btn-sm btn-success leave-action-btn"
                                                            data-id="{{ $req->id }}"
                                                            data-action="approve">Approve</button>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger leave-action-btn"
                                                            data-id="{{ $req->id }}"
                                                            data-action="reject">Reject</button>
                                                    </div>
                                                @else
                                                    <span class="text-body-secondary small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-body-secondary py-4">No leave
                                                requests yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reject_leave_id">
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" id="reject_reason" rows="3" placeholder="Enter reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmReject">
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
   <script>
document.addEventListener('DOMContentLoaded', function () {

    let rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));

    document.querySelectorAll('.leave-action-btn').forEach(button => {
        button.addEventListener('click', function () {

            let id = this.dataset.id;
            let action = this.dataset.action;
            if (action === 'approve') {

                if (!confirm('Approve this request?')) return;

                sendRequest(id, action);
            }
            if (action === 'reject') {
                document.getElementById('reject_leave_id').value = id;
                document.getElementById('reject_reason').value = '';
                rejectModal.show();
            }
        });
    });

    document.getElementById('confirmReject').addEventListener('click', function () {

        let id = document.getElementById('reject_leave_id').value;
        let reason = document.getElementById('reject_reason').value;

        if (reason.trim() === '') {
            alert('Please enter reason');
            return;
        }

        sendRequest(id, 'reject', reason);
        rejectModal.hide();
    });

    function sendRequest(id, action, reason = null) {
        fetch("{{ url('user/teacher/leave-action') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                id: id,
                action: action,
                reason: reason
            })
        })
        .then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            });
        })
        .then(function (result) {
            if (result.data && result.data.success) {
                location.reload();
                return;
            }
            var msg = (result.data && result.data.message) ? result.data.message : 'Could not update leave request.';
            alert(msg);
        })
        .catch(function () {
            alert('Network error. Please try again.');
        });
    }

});
</script>

@endsection
