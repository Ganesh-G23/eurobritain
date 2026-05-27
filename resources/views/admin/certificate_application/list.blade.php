@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('admin/certificate-application/add') }}" class="btn btn-primary btn-sm">
                    <i class="icon-base ti tabler-plus me-1"></i> Create applications
                </a>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ url('admin/certificate-application/list') }}" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <select class="form-select" id="filter_client_id" name="client_id">
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
                        <input type="text" name="email" class="form-control" placeholder="Email"
                            value="{{ $email }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="phone_number" class="form-control" placeholder="Phone Number"
                            value="{{ $phone_number }}">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="{{ url('admin/certificate-application/list') }}" class="btn btn-success">Refresh</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Application</th>
                                <th>Company Name</th>
                                <th>Details</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td>{{ $serial_start + $index + 1 }}</td>
                                    <td>{{ $row->application_number ?: '—' }}</td>
                                    <td>{{ $row->company_name }}</td>
                                    <td>
                                        <div><strong>Email:</strong> {{ $row->contact_email ?: '—' }}</div>
                                        <div><strong>Phone Number:</strong> {{ $row->contact_mobile ?: '—' }}</div>
                                        <div><strong>Company Address:</strong> {{ $row->address ?: '—' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap gap-1 justify-content-center">
                                            <a href="{{ url('admin/certificate-application/edit/' . $row->id) }}"
                                                class="btn btn-sm btn-outline-dark">Edit</a>
                                            <button type="button" class="btn btn-sm btn-outline-primary view-pdf-btn"
                                                data-id="{{ $row->id }}">View Pdf</button>
                                            <a href="{{ url('admin/certificate-application/view/' . $row->id) }}"
                                                class="btn btn-sm btn-outline-info">View Details</a>
                                            <a href="{{ url('admin/certificate-application/documents/' . $row->id) }}"
                                                class="btn btn-sm btn-outline-success">Documents</a>
                                            <a href="{{ url('admin/certificate-application/upload-document/' . $row->id) }}"
                                                class="btn btn-sm btn-outline-warning">Upload</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No applications found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (!empty($pagination))
                    <div class="row mt-3">
                        {!! $pagination !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).on('click', '.view-pdf-btn', function() {
            const id = $(this).data('id');
            window.open('{{ url('admin/certificate-application/pdf') }}/' + id, '_blank');
        });
    </script>
@endsection
