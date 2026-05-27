@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ $title }}</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ url('admin/report/associate') }}" class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="associate_id">Associate</label>
                        <select class="form-select" id="associate_id" name="associate_id">
                            <option value="">All Associates</option>
                            @foreach ($associateOptions as $associate)
                                <option value="{{ $associate->id }}"
                                    {{ $selectedAssociateId === (int) $associate->id ? 'selected' : '' }}>
                                    {{ $associate->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="{{ url('admin/report/associate') }}" class="btn btn-label-secondary">Reset</a>
                    </div>
                </form>

                @forelse ($rows as $row)
                    @php
                        $totalPayments = (float) ($row->invoices_sum_amount ?? 0);
                        $completedPayments = (float) ($row->completed_payments_sum_amount ?? 0);
                        $duePayments = max(0, $totalPayments - $completedPayments);
                    @endphp
                    <div class="row border-bottom py-3">
                        <div class="col-md-4">
                            <h6 class="mb-0">{{ $row->company_name }}</h6>
                        </div>
                        <div class="col-md-8 text-end">
                            <div><strong>Total Clients:</strong> {{ $row->clients_count }}</div>
                            <div><strong>Total Certificates:</strong> {{ $row->certificates_count }}</div>
                            <div><strong>Total Payments:</strong> {{ number_format($totalPayments, 2) }}</div>
                            <div><strong>Completed Payments:</strong> {{ number_format($completedPayments, 2) }}</div>
                            <div><strong>Due Payments:</strong> {{ number_format($duePayments, 2) }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No associates found.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
