@extends('web.user.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">{{ $title ?? 'Student leave requests' }}</h5>
                            @if (!empty($student?->name))
                                <p class="mb-0 text-body-secondary small">Showing leaves for
                                    <span class="fw-medium text-heading">{{ $student->name }}</span>
                                </p>
                            @endif
                        </div>
                        <a href="{{ url('user/parent/leave/request') }}" class="btn btn-sm btn-primary">Request leave</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Classroom</th>
                                    <th>Batch</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Teacher note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($student_leave_requests ?? [] as $index => $req)
                                    @php
                                        $st = strtolower((string) ($req->status ?? 'pending'));
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $req->student->name ?? '—' }}</td>
                                        <td>{{ $req->classroom->name ?? '—' }}</td>
                                        <td>{{ $req->batch->name ?? '—' }}</td>
                                        <td>{{ $req->from_date ? \Illuminate\Support\Carbon::parse($req->from_date)->format('d M Y') : '—' }}
                                        </td>
                                        <td>{{ $req->to_date ? \Illuminate\Support\Carbon::parse($req->to_date)->format('d M Y') : '—' }}
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
                                            @if ($st === 'rejected' && !empty($req->reject_reason))
                                                <small class="text-body-secondary">{{ $req->reject_reason }}</small>
                                            @else
                                                <span class="text-body-secondary">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-body-secondary py-4">No leave requests
                                            yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
@endsection
