@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('admin/invoice/add') }}" class="btn btn-primary btn-sm">Add Invoice</a>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ url('admin/invoice/list') }}" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <input type="text" name="q" class="form-control"
                            placeholder="Search invoice number" value="{{ $q }}">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filter_associate_id" name="associate_id">
                            <option value="">All Associates</option>
                            @foreach ($associates as $associate)
                                <option value="{{ $associate->id }}"
                                    {{ (int) $associate_id === (int) $associate->id ? 'selected' : '' }}>
                                    {{ $associate->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filter_client_id" name="client_id"
                            data-current-client="{{ $client_id }}">
                            <option value="">All Clients</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}"
                                    {{ (int) $client_id === (int) $client->id ? 'selected' : '' }}>
                                    {{ $client->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="{{ url('admin/invoice/list') }}" class="btn btn-label-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Invoice</th>
                                <th>Associate</th>
                                <th>Client</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Pending Amount</th>
                                <th>Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td>{{ $serial_start + $index + 1 }}</td>
                                    <td>{{ $row->invoice_number }}</td>
                                    <td>{{ $row->associate->company_name ?? '—' }}</td>
                                    <td>{{ $row->client->company_name ?? '—' }}</td>
                                    <td>{{ number_format((float) $row->total_amount, 2) }}</td>
                                    <td>{{ number_format((float) $row->paid_amount, 2) }}</td>
                                    <td>{{ number_format((float) $row->pending_amount, 2) }}</td>
                                    <td>{{ $row->invoice_date?->format('d M Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap gap-1 justify-content-center">
                                            <a href="{{ url('admin/invoice/view/' . $row->id) }}"
                                                class="btn btn-sm btn-outline-info">View</a>
                                            <a href="{{ url('admin/invoice/edit/' . $row->id) }}"
                                                class="btn btn-sm btn-outline-dark">Edit</a>
                                            <a href="{{ url('admin/invoice/pdf/' . $row->id) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">PDF</a>
                                            @if ($row->pending_amount <= 0)
                                                <a href="{{ url('admin/payment/list?q=' . urlencode($row->invoice_number)) }}"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    title="View payments for this invoice">Paid</a>
                                            @else
                                                <a href="{{ url('admin/payment/add?invoice_id=' . $row->id) }}"
                                                    class="btn btn-sm btn-outline-success">Add Payment</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No invoices found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (!empty($pagination))
                    <div class="row mt-3">{!! $pagination !!}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    @include('admin.partials._client_associate_cascade_js')
@endsection
