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

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Associate Name</th>
                                <th class="text-center">Total Clients</th>
                                <th class="text-center">Total Certificates</th>
                                <th class="text-end">Total Payments</th>
                                <th class="text-end">Completed Payments</th>
                                <th class="text-end">Due Payments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td><strong>{{ $row->company_name }}</strong></td>
                                    <td class="text-center">{{ $row->clients_count }}</td>
                                    <td class="text-center">{{ $row->certificates_count }}</td>
                                    <td class="text-end">₹ {{ number_format((float) ($row->total_payments ?? 0), 2) }}</td>
                                    <td class="text-end">₹ {{ number_format((float) ($row->completed_payments ?? 0), 2) }}</td>
                                    <td class="text-end">₹ {{ number_format((float) ($row->due_payments ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
