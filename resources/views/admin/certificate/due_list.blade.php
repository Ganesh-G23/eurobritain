@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ url('admin/certificate/due-list') }}" class="row g-3 mb-4">
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
                    <div class="col-md-3">
                        <select class="form-select" name="certificate_type_id">
                            <option value="">All Certificate Types</option>
                            @foreach ($certificateTypes as $certType)
                                <option value="{{ $certType->id }}"
                                    {{ (int) $certificate_type_id === (int) $certType->id ? 'selected' : '' }}>
                                    {{ $certType->description ?? $certType->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="{{ url('admin/certificate/due-list') }}" class="btn btn-label-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Application</th>
                                <th>Company</th>
                                <th>Associate</th>
                                <th>Certificate Type</th>
                                <th>Expiry</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td>{{ $serial_start + $index + 1 }}</td>
                                    <td>{{ $row->application_number }}</td>
                                    <td>{{ $row->company_name }}</td>
                                    <td>{{ $row->client->associate->company_name ?? '—' }}</td>
                                    <td>{{ $row->certificateType->description ?? $row->certificateType->code ?? '—' }}</td>
                                    <td>
                                        @if ($row->date_of_expiry)
                                            {{ $row->date_of_expiry->format('d M Y') }}
                                        @else
                                            <span class="badge bg-label-warning">First issue due</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ url('admin/certificate/add?application_id=' . $row->id) }}"
                                            class="btn btn-sm btn-primary">Certificate</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No due certificates found.</td>
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
