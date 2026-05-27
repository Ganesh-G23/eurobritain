@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">{{ $title }}</h5>
                <a href="{{ url('admin/client/add') }}" class="btn btn-primary btn-sm">
                    <i class="icon-base ti tabler-plus me-1"></i> Add Client
                </a>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ url('admin/client/list') }}" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <input type="text" name="q" class="form-control" placeholder="Search company, contact, email, mobile, city..."
                            value="{{ $q }}">
                    </div>
                    <div class="col-md-4">
                        <select name="associate_id" class="form-select">
                            <option value="">All Associates</option>
                            @foreach ($associates as $associate)
                                <option value="{{ $associate->id }}"
                                    {{ (string) $associate_id === (string) $associate->id ? 'selected' : '' }}>
                                    {{ $associate->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="{{ url('admin/client/list') }}" class="btn btn-label-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Associate</th>
                                <th>Company</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>City</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td>{{ $serial_start + $index + 1 }}</td>
                                    <td>{{ $row->associate->company_name ?? '—' }}</td>
                                    <td>{{ $row->company_name }}</td>
                                    <td>{{ $row->contact_person }}</td>
                                    <td>{{ $row->contact_email }}</td>
                                    <td>{{ $row->contact_mobile }}</td>
                                    <td>{{ $row->city }}</td>
                                    <td class="text-center text-nowrap">
                                        <a href="{{ url('admin/client/edit/' . $row->id) }}"
                                            class="btn btn-sm btn-icon btn-label-primary" title="Edit">
                                            <i class="icon-base ti tabler-edit"></i>
                                        </a>
                                        <a href="{{ url('admin/client/view/' . $row->id) }}"
                                            class="btn btn-sm btn-icon btn-label-info" title="View">
                                            <i class="icon-base ti tabler-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No clients found.</td>
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
