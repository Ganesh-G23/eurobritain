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
                        <input type="text" name="q" class="form-control" placeholder="Search name, code, prefix, description..."
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
                                <th>Name</th>
                                <th>Code</th>
                                <th>Prefix</th>
                                <th>Type</th>
                                <th>Certificate Type</th>
                                <th>Audit Period (Years)</th>
                                <th>Renewal Period (Years)</th>
                                <th>Price</th>
                                <!-- <th>Template</th> -->
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td>{{ $serial_start + $index + 1 }}</td>
                                    <td>{{ $row->name ?? '—' }}</td>
                                    <td>{{ $row->code }}</td>
                                    <td>{{ $row->prefix }}</td>
                                    <td>
                                        @php
                                            $rowTypes = (array) ($row->types ?? []);
                                        @endphp
                                        @if (!empty($rowTypes))
                                            {{ collect($rowTypes)->map(fn ($type) => $type_options[$type] ?? $type)->implode(', ') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $category_options[$row->category] ?? '—' }}</td>
                                    <td>{{ $row->audit_period ? $row->audit_period.' Year'.((int) $row->audit_period > 1 ? 's' : '') : '—' }}</td>
                                    <td>{{ $row->renewal_period ? $row->renewal_period.' Year'.((int) $row->renewal_period > 1 ? 's' : '') : '—' }}</td>
                                    <td>{{ '₹ '.number_format((float) $row->price, 2) }}</td>
                                    <!-- <td>
                                        @if ($row->certificate_template)
                                            <a href="{{ url('storage/app/uploads/temp/' . $row->certificate_template) }}" target="_blank" title="View template">View</a>
                                        @else
                                            —
                                        @endif
                                    </td> -->
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
                                    <td colspan="11" class="text-center text-muted py-4">No certificate types found.</td>
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
