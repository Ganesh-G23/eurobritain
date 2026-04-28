@extends('web.user.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="row g-6">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Timeline List</h5>
                        <a href="{{ url('user/teacher/timelines') }}" class="btn btn-sm btn-primary">Add Timeline</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Topic</th>
                                    <th>Classroom</th>
                                    <th>Batch</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($timelines ?? [] as $timeline)
                                    <tr>
                                        <td>{{ $timeline->topic }}</td>
                                        <td>{{ $timeline->classroom->name ?? '-' }}</td>
                                        <td>{{ $timeline->batch->name ?? '-' }}</td>
                                        <td>{{ $timeline->start_date ? \Illuminate\Support\Carbon::parse($timeline->start_date)->format('d M Y') : '-' }}</td>
                                        <td>{{ $timeline->end_date ? \Illuminate\Support\Carbon::parse($timeline->end_date)->format('d M Y') : '-' }}</td>
                                        <td>
                                            @if ((int) $timeline->status === 1)
                                                <span class="badge bg-label-success">Active</span>
                                            @elseif ((int) $timeline->status === 0)
                                                <span class="badge bg-label-secondary">Inactive</span>
                                            @elseif ((int) $timeline->status === 2)
                                                <span class="badge bg-label-primary">Completed</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-end">
                                                <a href="{{ url('user/teacher/timelines/edit/' . $timeline->id) }}"
                                                    class="btn btn-sm btn-icon btn-label-primary" title="Edit">
                                                    <i class="icon-base ti tabler-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-body-secondary py-4">No timelines added yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
  
@endsection
