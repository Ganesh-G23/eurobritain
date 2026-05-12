@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="row g-6">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Fees List</h5>
                        <a href="{{ url('user/teacher/fees') }}" class="btn btn-sm btn-primary">Add Fee</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Classroom</th>
                                    <th>Batch</th>
                                    <th>Amount</th>
                                    <th>Mode of payment</th>
                                    <th>Remark</th>
                                    <th>Added at</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($fees ?? [] as $fee)
                                    <tr>
                                        <td>
                                            <div>{{ $fee->student->name ?? '-' }}</div>
                                            @if (!empty($fee->student->email))
                                                <small class="text-body-secondary">{{ $fee->student->email }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $fee->classroom->name ?? '-' }}</td>
                                        <td>{{ $fee->batch->name ?? '-' }}</td>
                                        <td>{{ number_format((float) $fee->amount, 2) }}</td>
                                        <td>{{ $fee->payment_mode }}</td>
                                        <td class="text-break" style="max-width: 220px;">{{ $fee->remark ?: '—' }}</td>
                                        <td>{{ $fee->created_at ? $fee->created_at->format('d M Y H:i') : '—' }}</td>
                                        <td>
                                            <div class="d-flex justify-content-end">
                                                <a href="{{ url('user/teacher/fees/edit/' . $fee->id) }}"
                                                    class="btn btn-sm btn-icon btn-label-primary" title="Edit">
                                                    <i class="icon-base ti tabler-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-body-secondary py-4">No fees recorded yet.</td>
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
