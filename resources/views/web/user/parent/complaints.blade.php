@extends('web.user.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Complaint List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Teacher</th>
                                        <th>Student</th>
                                        <th>Date</th>
                                        <th>Complaint</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($complaints ?? [] as $index => $complaint)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $complaint->teacher->name ?? '—' }}</td>
                                            <td>{{ $complaint->student->name ?? '—' }}</td>
                                            <td>{{ $complaint->created_at ? $complaint->created_at->format('d M Y') : '—' }}</td>
                                            <td>{{ $complaint->remark !== null && $complaint->remark !== '' ? $complaint->remark : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-body-secondary py-4">
                                                No complaints yet.
                                            </td>
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

