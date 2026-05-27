@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('admin/certificate-type/add') }}" class="btn btn-primary btn-sm">
                    <i class="icon-base ti tabler-plus me-1"></i> Add Certificate Type
                </a>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ url('admin/certificate-type/list') }}" class="row g-3 mb-4">
                    <div class="col-md-8">
                        <input type="text" name="q" class="form-control" placeholder="Search code, prefix, description..."
                            value="{{ $q }}">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="{{ url('admin/certificate-type/list') }}" class="btn btn-label-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Prefix</th>
                                <th>Audit Period</th>
                                <th>Renewal Period</th>
                                <th>Price</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td>{{ $serial_start + $index + 1 }}</td>
                                    <td>{{ $row->code }}</td>
                                    <td>{{ $row->prefix }}</td>
                                    <td>{{ $row->audit_period ?? '—' }}</td>
                                    <td>{{ $row->renewal_period ?? '—' }}</td>
                                    <td>{{ number_format((float) $row->price, 2) }}</td>
                                    <td class="text-center text-nowrap">
                                        <a href="{{ url('admin/certificate-type/edit/' . $row->id) }}"
                                            class="btn btn-sm btn-icon btn-label-primary" title="Edit">
                                            <i class="icon-base ti tabler-edit"></i>
                                        </a>
                                        <a href="{{ url('admin/certificate-type/view/' . $row->id) }}"
                                            class="btn btn-sm btn-icon btn-label-info" title="View">
                                            <i class="icon-base ti tabler-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No certificate types found.</td>
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
