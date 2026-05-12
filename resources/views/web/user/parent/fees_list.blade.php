@extends('web.user.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Fees</h5>
                            @if (!empty($student_name))
                                <small class="text-body-secondary">Showing fees for {{ $student_name }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Teacher</th>
                                        <th>Classroom</th>
                                        <th>Batch</th>
                                        <th>Amount</th>
                                        <th>Mode of payment</th>
                                        <th>Added at</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($fees ?? [] as $fee)
                                        <tr>
                                            <td>{{ $fee->teacher->name ?? '—' }}</td>
                                            <td>{{ $fee->classroom->name ?? '—' }}</td>
                                            <td>{{ $fee->batch->name ?? '—' }}</td>
                                            <td>{{ number_format((float) $fee->amount, 2) }}</td>
                                            <td>{{ $fee->payment_mode }}</td>
                                            <td>{{ $fee->created_at ? $fee->created_at->format('d M Y H:i') : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-body-secondary py-4">No fees recorded yet.</td>
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
@endsection
