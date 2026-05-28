@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ url('admin/certificate/list') }}" class="row g-3 mb-4">
                    <div class="col-md-2">
                        <input type="text" name="q" class="form-control"
                            placeholder="Search certificate number" value="{{ $q }}">
                    </div>
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                    <div class="col-md-2">
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="certificate" {{ $type === 'certificate' ? 'selected' : '' }}>Certificate</option>
                            <option value="audit" {{ $type === 'audit' ? 'selected' : '' }}>Audit</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="uploaded" class="form-select">
                            <option value="">Upload Certificate (All)</option>
                            <option value="yes" {{ $uploaded === 'yes' ? 'selected' : '' }}>Yes</option>
                            <option value="no" {{ $uploaded === 'no' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="{{ url('admin/certificate/list') }}" class="btn btn-label-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Application</th>
                                <th>Certificate Number</th>
                                <th>Type</th>
                                <th>Associate</th>
                                <th>Client</th>
                                <th>Certificate Type</th>
                                <th>Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td>{{ $serial_start + $index + 1 }}</td>
                                    <td>{{ $row->certificateApplication->application_number }}</td>
                                    <td>{{ $row->certificate_number }}</td>
                                    <td>
                                        @if ($row->type === 'audit')
                                            <span class="badge bg-label-warning">Audit</span>
                                        @else
                                            <span class="badge bg-label-primary">Certificate</span>
                                        @endif
                                    </td>
                                    <td>{{ $row->associate->company_name ?? '—' }}</td>
                                    <td>{{ $row->client->company_name ?? '—' }}</td>
                                    <td>{{ $row->certificateType->description ?? $row->certificateType->code ?? '—' }}</td>
                                    <td>{{ $row->issue_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap gap-1 justify-content-center">
                                            <a href="{{ url('admin/certificate/view/' . $row->id) }}"
                                                class="btn btn-sm btn-outline-info">View</a>
                                            <a href="{{ url('admin/certificate/edit/' . $row->id) }}"
                                                class="btn btn-sm btn-outline-dark">Edit</a>
                                            @if (filled($row->certificate))
                                                <a href="{{ url('admin/certificate/upload/' . $row->id) }}"
                                                    class="btn btn-sm btn-outline-success">Uploaded</a>
                                            @else
                                                <a href="{{ url('admin/certificate/upload/' . $row->id) }}"
                                                    class="btn btn-sm btn-outline-primary">Upload Certificate</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No certificates found.</td>
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
